<?php

namespace IT\Services\AAMSSession;

use DateInterval;
use DateTime;
use DBUser;
use Exception;
use IT;
use IT\Pgda\Codes\ReturnCode;
use IT\Services\Traits\InteractWithMail;

class AAMSSessionService
{
    use InteractWithMail;
    /**
     * Define multilayer game
     *
     * @var int;
     */
    const GAME_MULTIPLAYER = 3;

    /**
     * Define multilayer game
     *
     * @var int;
     */
    const GAME_SINGLEPLAYER = 2;

    /**
     * The database table used
     *
     * @var string
     */
    const TABLE = 'ext_game_sessions';

    /**
     * Status code close
     */
    const STATUS_CODE = 0;

    /**
     * Italy is default country
     */
    const COUNTRY = 'IT';

    const END_PLAYER_SESSION_ATTEMPTS = 3;

    /**
     *  Timeout between series of ADM requests
     */
    const REQUEST_TIMEOUT = 3;

    /**
     * The `ext_game_sessions`
     *
     * @var array
     */
    private $session = [];

    /**
     * The participation instance helper
     *
     * @var Participation
     */
    private Participation $participation;

    /**
     * The IT license instance helper
     *
     * @var IT
     */
    private IT $it;

    /**
     * The IT license instance helper
     *
     */
    private $db;

    /**
     * AAMSSessionService constructor.
     *
     * @param IT $it
     */
    public function __construct(IT $it)
    {
        $this->it = $it;
        $this->setParticipation(new Participation($it));
        $this->db = phive('SQL');
    }

    /**
     * Create new self instance
     *
     * @param IT $it
     * @return self
     */
    static public function factory(IT $it): self
    {
        return new static($it);
    }

    /**
     * Return participation
     *
     * @return Participation
     */
    public function getParticipation(): Participation
    {
        return $this->participation;
    }

    /**
     * Set participation
     *
     * @param Participation $participation
     * @return self
     */
    public function setParticipation(Participation $participation): self
    {
        $this->participation = $participation;

        return $this;
    }

    /**
     * Return already open session by game if any
     *
     * @param string $game_id
     * @return array|false
     */
    public function getOpenSessionByGame(string $game_id)
    {
        // TODO: should we chunk?
        $sql = <<<EOS
SELECT *
FROM ext_game_sessions
WHERE
    ext_game_id = {$this->db->escape($game_id)}
    AND ended_at = '0000-00-00 00:00:00',
    AND `status_code` IS NULL
EOS;
        return $this->db->loadArray($sql);
    }

    /**
     * Return the number of participation for the session
     * @param integer $external_game_session_id
     *
     * @return array|false
     */
    public function getOpenParticipationCount(int $external_game_session_id)
    {
        return $this->db->shs('sum', '', null, 'ext_game_participations')
            ->getValue("SELECT COUNT(*) FROM ext_game_participations WHERE external_game_session_id = {$external_game_session_id}");
    }

    /**
     * Start a new session on AMS system and store the stake information in database
     *
     * @param DBUser $user The user id or the (already initialized) user object
     * @param array $game from micro_games table
     * @param string $token_id Token shared with game provider on game launch/authentication
     * @param int $real_stake
     * @param int $bonus_stake
     * @return int
     * @throws Exception
     */
    public function createNewSession(
        DBUser $user,
        array $game,
        string $token_id,
        int $real_stake,
        int $bonus_stake
    ): int {
        $play_bonus_stake = $this->getParticipation()->getPlayBonusStake($game, $user);
        if ($play_bonus_stake) {
            $real_stake = 0;
            $bonus_stake = 0;
        }
        $has_deposit_bonus = 0; // TODO clean all deposit bonus logic

        $this->session = [];

        if ($this->isMultiplayer($game['ext_game_name'])) {
            // search for already opened session regarding this game
            $this->session = $this->getOpenSessionByGame($game['ext_game_name']);
        }

        // TODO Refactor this function
        // no adm game session exists so create it.
        if (empty($this->session)) {
            $this->session = $this->admStartSession($game, $has_deposit_bonus, $play_bonus_stake);
        }
        if (empty($this->session)) {
            return false;
        }
        sleep(static::REQUEST_TIMEOUT);
        return $this->getParticipation()->create(
            $user,
            $game,
            $this->session,
            $real_stake,
            $bonus_stake,
            $play_bonus_stake,
            $token_id
        );
    }

    /**
     * Get the external session id given by PGDA Message 400
     * @param array $game
     * @param bool $has_deposit_bonus
     * @param int $play_bonus_stake
     * @return array|false|string
     * @throws Exception
     */
    protected function admStartSession(array $game, bool $has_deposit_bonus = false, int $play_bonus_stake = 0)
    {
        $ext_game_session_id = $this->createExtGameSession(['ext_game_id' => $game['ext_game_name']]);

        if (!$ext_game_session_id) {
            return [];
        }

        $game_code = $this->getGameRegulatoryCode($game['ext_game_name']);
        $game_type = $this->getGameRegulatoryType($game['ext_game_name']);

        if(!$game_code) {
            return false;
        }

        // getting the current date
        $date = new DateTime('NOW');
        // getting current date and add 5 days as suggested by ADM
        $date_span = new DateTime('NOW');
        $date_span->add(new DateInterval('P5D'));

        $data = [
            'game_code' => $game_code,
            'game_type' => $game_type,

            'license_session_id' => "{$this->it->config()['ams_environment_prefix']}-{$ext_game_session_id}",
            'start_date_session' => [
                'date' => [
                    'day' => $date->format('d'),
                    'month' => $date->format('m'),
                    'year' => $date->format('Y'),
                ],
                'time' => [
                    'hour' => $date->format('H'),
                    'minutes' => $date->format('i'),
                    'seconds' => $date->format('s')
                ],
            ],
            'end_date_session' => [
                'day' => $date_span->format('d'),
                'month' => $date_span->format('m'),
                'year' => $date_span->format('Y'),
            ]
        ];

        if ($has_deposit_bonus || $play_bonus_stake) {
            $data['attributes_session_list'][0]['code'] = 'BON';
            $to_update['bonus_type'] =  $data['attributes_session_list'][0]['value'] = (!$play_bonus_stake) ? 'B' : 'F';
        }

        // (PGDA Message 400)
        $response = $this->it->startGameSessions($data);

        if ($response['code'] !== ReturnCode::SUCCESS_CODE) {
            phive()->dumpTbl('ERROR-startGameSession', ['payload' => $data, 'response' => $response]);
            $this->notify('ADM Error on message 400', compact('data', 'response'));
            return [];
        }

        $to_update['ext_session_id'] = $response['response']['session_id'];
        if (!$this->updateExtGameSession(
            $ext_game_session_id,
            $to_update
        )) {
            return [];
        }

        return $this->getExtGameSessionById($ext_game_session_id);
    }

    /**
     * Interface method for getting external session information
     *
     * @param mixed $user The user id
     * @param int|null $play_session_id
     * @return mixed array|bool
     */
    public function getPlaySessionById($user, int $play_session_id = null)
    {
        if(!$play_session_id)
        {
            return false;
        }

        return $this->getParticipation()->getById($user, $play_session_id);
    }

    /**
     * Interface method for getting external session information by GP token
     *
     * @param mixed $user The user id
     * @param string $play_session_token
     * @return mixed array|false
     */
    public function getPlaySessionByToken($user, string $play_session_token)
    {
        return $this->getParticipation()->getByToken($user, $play_session_token);
    }

    /**
     * Interface method for incrementing/decrementing external session balance
     *
     * @param int $amount
     * @return bool
     */
    public function incrementSessionBalance(int $amount): bool
    {
        return $this->getParticipation()->incrementBalance($amount);
    }

    /**
     * Interface method for incrementing external session stake
     * Will also increment the external session balance in the same transaction
     *
     * @param int $real_increment
     * @param int $bonus_increment
     * @return bool
     */
    public function incrementSessionStake(int $real_increment, int $bonus_increment): bool
    {
        $participation_data = $this->getParticipation()->getData();
        $user = cu($participation_data['user_id']);

        $game = $this->db->loadAssoc(null, 'micro_games', ['ext_game_name' => $participation_data['ext_game_id']]);

        if (!$game) {
            return false;
        }

        $external_game_session = static::getExtGameSessionById($participation_data['external_game_session_id']);

        if (!empty(
        $this->getParticipation()->admStartParticipation(
            $user,
            $game,
            $external_game_session,
            $real_increment,
            $bonus_increment,
            0
        )
        )) {
            return $this->getParticipation()->incrementStake($real_increment, $bonus_increment);
        }

        return false;
    }

    /**
     * Interface method to end the current external game session
     *
     * @param mixed $user
     * @param int $participation_id
     * @param string $end_time
     * @return bool
     */
    public function endPlayerSession($user, int $participation_id, string $end_time): bool
    {
        $participation = $this->getParticipation()->getById($user, $participation_id);

        if(empty($participation))
        {
            return false;
        }

        // refetch session to be safe
        $session = $this->getExtGameSessionById($participation['external_game_session_id']);

        if(!$session)
        {
            return false;
        }

        if ($this->getParticipation()->endParticipation($user, $end_time, $session)) {
            if ($this->isMultiplayer($participation['ext_game_id']) && $this->getOpenParticipationCount($session['id']) > 1) {
                return true;
            }
        }
        sleep(static::REQUEST_TIMEOUT);
        return $this->admEndSession($session['ext_session_id'], $end_time);
    }

    /**
     * Get ext_game_session by id
     *
     * @param int $id
     * @return array|false|string
     */
    public static function getExtGameSessionById(int $id)
    {
        return phive('SQL')->loadAssoc(null, static::TABLE, compact('id'));
    }

    /**
     * create new ext_game_session record on db without ext_session_id
     *
     * @param array $data
     * @return bool|int
     */
    protected function createExtGameSession(array $data)
    {
        return $this->db->insertArray(static::TABLE, $data);
    }

    /**
     * update ext_game_session data by id
     *
     * @param int $id
     * @param array $data
     * @return bool|int
     */
    protected function updateExtGameSession(int $id, array $data)
    {
        return $this->db->updateArray(self::TABLE, $data, compact('id'));
    }

    /**
     * Finishes the external game session
     *
     * @param string $ext_session_id
     * @param string $end_time
     * @param int $attempt
     * @return bool
     */
    public function admEndSession(string $ext_session_id, string $end_time, int $attempt = 0): bool
    {
        if (empty($data = $this->getParticipation()->getData())) {
            phive('Logger')
                ->getLogger('pgda_adm')
                ->info("ADM End Session. Participation was returned empty {$end_time}");
            return false;
        }

        $game_code = $this->getGameRegulatoryCode($data['ext_game_id']);
        $game_type = $this->getGameRegulatoryType($data['ext_game_id']);
        $end_at = strtotime($end_time);

        if(!$game_type) {
            return false;
        }

        $payload = [
            'game_code' => $game_code,
            'game_type' => $game_type,
            'central_system_session_id' => $ext_session_id,
            'session_end_date' => [
                'date' => [
                    'day' => date('d', $end_at),
                    'month' => date('m', $end_at),
                    'year' => date('Y', $end_at),
                ],
                'time' => [
                    'hour' => date('H', $end_at),
                    'minutes' => date('i', $end_at),
                    'seconds' => date('s', $end_at)
                ],
            ]
        ];

        try {
            $response = $this->it->endGameSession($payload);

            if ($response['code'] !== ReturnCode::SUCCESS_CODE) {

                phive('Logger')
                    ->getLogger('pgda_adm')
                    ->info('Response code of ' . __METHOD__, [
                        'message' => "Response code of " . __METHOD__ . " for game {$game_code} with
                            central_system_session_id {$payload['central_system_session_id']} is {$response['code']}.
                            Attempt: {$attempt}",
                        'user' => $data['user_id'],
                        'request' => $payload,
                        'response' => $response
                    ]);

                if ($attempt < static::END_PLAYER_SESSION_ATTEMPTS) {
                    phive('Site/Publisher')->single(
                        'pgda',
                        'Licensed',
                        'doLicense',
                        ['IT', 'admEndSession', [$ext_session_id, $end_time, $attempt+1]]
                    );
                }

                return false;
            }

            $success = $this->db->save(
                static::TABLE,
                [
                    'ended_at' => $end_time,
                    'status_code' => static::STATUS_CODE
                ],
                ['id' => $data['external_game_session_id']]
            );
            if ($success) {
                // here we need to dispatch query job passing ext_game_session_id and user_id when is not multiplayer
                phive('Site/Publisher')->single(
                    'pgda',
                    'Licensed',
                    'doLicense',
                    ['IT', 'sendGameSessionCommunication', [$data['external_game_session_id'], [$data['user_id']]]]
                );
            }
            return $success;
        } catch (Exception $e) {
            phive('Logger')
                ->getLogger('pgda_adm')
                ->error('Exception while ending session', [
                    'message' => "Exception while ending session",
                    'user' => $data['user_id'],
                    'request' => $payload,
                    'exception' => $e->getMessage(),
                ]);
        } // (PGDA Message 500)
    }

    /**
     * Gets information about the session that will be displayed to the player on the top bar
     *
     * @param $user
     * @return array[]
     */
    public function getSessionBalances($user)
    {
        return array_map(
            function ($p) {
                $s = $this->getExtGameSessionById($p['external_game_session_id']);

                return [
                    'balance' => $p['balance'],
                    'ext_session_id' => $s['ext_session_id'],
                    'participation_id' => $p['participation_id'],
                    'stake' => $p['stake']
                ];
            },
            $this->getParticipation()->getParticipationsOpenByUser($user)
        );
    }

    /**
     * get participation by id
     *
     * @param $user
     * @param $participation_id
     * @return array|false|string
     */
    public function getByParticipationId($user, $participation_id)
    {
        return $this->participation->getByParticipationId($user, $participation_id);
    }

    /**
     * Gets the external session id that we will send to sogei
     * - On normal slot games the external id is taken from the id from users_game_sessions table
     * - On multiplayer games it's taken from tournaments table (TODO)
     *
     * @param $user
     * @param $game_session_id
     * @param $multiplayer
     * @return string
     */
    public function getExternalId($user, $game_session_id, $multiplayer)
    {
        $prefix = licSetting('ams_environment_prefix', $user);

        if (!empty($multiplayer)) {
            return $prefix . '-t-' . $multiplayer['id'];
        }
        return !empty($prefix) ? $prefix . '-' . $game_session_id : $game_session_id;
    }

    /**
     * Start or get a user game session
     *
     * @param $user
     * @param $game
     * @return false|string
     */
    protected function getUserGameSession($user, $game)
    {
        $ins = [
            'user_id' => $user->getId(),
            'game_ref' => $game['ext_game_name'],
            'device_type' => "{$game['device_type_num']}",
            'amount' => 0
        ];

        $user_game_session = phive('Casino')->getGsess($ins, $user);

        return !empty($user_game_session['start_time']) ? $user_game_session : $this->db->sh($user->getId())->loadAssoc(NULL, 'users_game_sessions', ['id' => $user_game_session['id']]);
    }

    /**
     * Gets multiplayer session id if existing already
     *
     * @param $game
     * @return false
     */
    private function getMultiplayerSession($game)
    {
        return $this->db->getValue('', 'ext_session_id', self::TABLE, ['ext_game_id' => $game['ext_game_name'], 'ended_at' => phive()->getZeroDate()]);
    }

    /**
     * Return all session increments for a specific user and date range
     *
     * @param integer|string $user_id The user id
     * @param string $start_date The starting date ('yyyy-mm-dd')
     * @param string $end_date The ending date ('yyyy-mm-dd')
     *
     * @return array An array of increments ([[session_id, participation_id, session_status, game_name, created_at, balance, amount, win]])
     */
    public function getGameSessionBalancesByUserId($user_id, $start_date, $end_date)
    {
        return $this->getParticipation()->getGameSessionBalancesByUserId($user_id, $start_date, $end_date);
    }

    /**
     * Return all session increments for a specific user and game session
     *
     * @param integer|string $user_id The user id
     * @param integer|string $session_id The ext_game_participations.id
     *
     * @return array An array of increments ([[session_id, participation_id, session_status, game_name, created_at, balance, amount, win]])
     */
    public function getExternalGameSessionDetailsBySessionId($user_id, $session_id)
    {
        return $this->getParticipation()->getExternalGameSessionDetailsBySessionId($user_id, $session_id);
    }

    /**
     * Returns the sum of all open sessions balance
     *
     * @param $user
     * @return mixed
     */
    public function getAllOpenSessionsBalance($user)
    {
        return phive()->sum2d($this->getParticipation()->getParticipationsOpenByUser($user), 'balance');
    }

    /**
     * Returns all open sessions
     *
     * @param $user
     * @return mixed
     */
    public function getAllOpenExternalSessions($user)
    {
        return $this->getParticipation()->getParticipationsOpenByUser($user);
    }

    /**
     * Return true if the session is regarding multiplayer game
     *
     * @param string $ext_game_id
     * @return bool
     */
    public static function isMultiplayer(string $ext_game_id) : bool
    {
        // not yet implements
        return false;
    }

    /**
     * Returns the game type of the game in the AMS system
     * @param string $ext_game_id
     * @return false|int
     */
    public static function getGameRegulatoryType(string $ext_game_id)
    {
        return self::queryGameRegulatory($ext_game_id, 'game_type') ?? false;
    }

    /**
     * Returns the code of the game in the AMS system
     * @param string $ext_game_id
     * @return false|int
     */
    public static function getGameRegulatoryCode(string $ext_game_id)
    {
        return self::queryGameRegulatory($ext_game_id, 'game_regulatory_code') ?? false;
    }

    /**
     * @param string $ext_game_id
     * @param string $column
     * @return false|int
     */
    private static function queryGameRegulatory(string $ext_game_id, string $column)
    {
        $result = phive('SQL')->getValue(null, $column, 'game_country_versions', [
            'country' => self::COUNTRY,
            'ext_game_id' => $ext_game_id
        ]);
        if (empty($result)) {
            phive()->dumpTbl('error-ext-session', ['game-code-missing', $ext_game_id]);
        }

        return (int)$result ?? false;
    }
    public function getParticipationByUGSId($user, $user_game_session_id)
    {
        return $this->participation->getByUserGameSessionId($user, $user_game_session_id);
    }

}
