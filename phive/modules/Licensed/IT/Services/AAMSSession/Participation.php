<?php
namespace IT\Services\AAMSSession;

use Exception;
use IT;
use IT\Pgda\Codes\ReturnCode as PgdaReturnCode;
use IT\Services\ProvincesService;
use SQL;
use IT\Services\Traits\InteractWithMail;
/**
 * Class Participation
 * @package IT\Services\AAMSSession
 */
class Participation
{
    use InteractWithMail;
    /**
     * The database table used
     *
     * @var string
     */
    const TABLE = 'ext_game_participations';
    const TABLE_BONUSES = 'ext_game_participations_bonuses';

    const DESKTOP_BROWSER_ANDROID = 10;
    const DESKTOP_BROWSER_OSX = 11;
    const DESKTOP_BROWSER_WINDOWS = 12;
    const DESKTOP_BROWSER_OTHER = 14;
    const MOBILE_BROWSER_ANDROID = 30;
    const MOBILE_BROWSER_IOS = 31;
    const MOBILE_BROWSER_WINDOWS = 32;
    const MOBILE_BROWSER_OTHER = 34;

    protected $participation = [];
    protected $bonus = [];

    protected $increments;

    /**
     * The IT license instance helper
     *
     * @var IT
     */
    private IT $it;

    /**
     * The IT license instance helper
     *
     * @var SQL
     */
    private SQL $db;

    /**
     * The participation summary data
     *
     * @var array|null
     */
    private $participation_summary = [];

    /**
     * Participation constructor.
     *
     * @param IT $it
     */
    public function __construct(IT $it)
    {
        $this->table = self::TABLE;
        $this->it = $it;
        $this->db = phive('SQL');
    }

    /**
     * Get participation by id
     *
     * @param mixed $user
     * @param int $id
     * @return mixed
     */
    public function getById($user, int $id)
    {
        if (empty($this->participation) || $this->participation['id'] != $id) {
            $this->participation = phive('SQL')->sh($user)->loadAssoc(null, self::TABLE, compact('id'));
            if (empty($this->participation)) {
                $this->participation = [];
            }
        }

        return $this->participation;
    }

    /**
     * Get participation by token_id
     *
     * @param mixed $user
     * @param string $token_id
     * @return mixed
     */
    public function getByToken($user, string $token_id)
    {
        if (empty($this->participation) || $this->participation['balance'] == 0) {
            $this->participation = phive('SQL')->sh(uid($user))->loadAssoc(null, self::TABLE, compact('token_id'));
            if (empty($this->participation)) {
                $this->participation = [];
            }
        }

        return $this->participation;
    }

    /**
     * Get participation by adm participation_id
     *
     * @param mixed $user
     * @param string $participation_id
     * @return mixed
     */
    public function getByParticipationId($user, string $participation_id)
    {
        if (empty($this->participation)) {
            $this->participation = phive('SQL')->sh($user)->loadAssoc(null, self::TABLE, compact('participation_id'));
            if (empty($this->participation)) {
                $this->participation = [];
            }
        }

        return $this->participation;
    }

    /**
     * Get the participations opened by the player
     *
     * @param mixed $user
     * @return mixed
     */
    public function getParticipationsOpenByUser($user)
    {
        $user_id = uid($user);
        return phive('SQL')->sh($user_id)->arrayWhere(
            self::TABLE,
            ['user_id' => $user_id, 'ended_at' => phive()->getZeroDate()]
        );
    }

    /**
     * Creating new participation
     *
     * @param mixed $user
     * @param array $game
     * @param array $ext_game_session
     * @param int $real_stake
     * @param int $bonus_stake The part of the user stake that comes from bonus balance for this game
     * @param int $play_bonus_stake
     * @param string $token_id Token coming from game provider
     * @return mixed
     */
    public function create(
        $user,
        array $game,
        array $ext_game_session,
        int $real_stake,
        int $bonus_stake,
        int $play_bonus_stake,
        string $token_id
    ) {
        $ext_participation_id = $this->admStartParticipation(
            $user,
            $game,
            $ext_game_session,
            $real_stake,
            $bonus_stake,
            $play_bonus_stake
        );
        
        if (!$ext_participation_id || empty($ext_participation_id)) {
            return false;
        }

        // getting the user_game_session and store id
        $user_game_session = $this->getUserGameSession($user, $game);

        $insert = [
            'participation_id' => $ext_participation_id,
            'external_game_session_id' => $ext_game_session['id'],
            'token_id' => $token_id,
            'user_id' => uid($user),
            'user_game_session_id' => $user_game_session['id'],
            'balance' => 0,
            'stake' => 0,
            'ext_game_id' => $game['ext_game_name'],
            'is_free_spin_session' => phive('CasinoBonuses')->isFreeSpinGameSession($user, $game),
        ];

        $ext_game_participation = phive('SQL')->sh($user)->insertArray(self::TABLE, $insert);

        if ($play_bonus_stake) {
            $insert_bonus = ['ext_game_participation_id' => $ext_game_participation, 'bonus_entry_id' => $this->bonus['id'], 'balance_start' => $play_bonus_stake, 'balance_end' => $play_bonus_stake];
            phive('SQL')->sh($user)->insertArray(self::TABLE_BONUSES, $insert_bonus);
        }

        if (empty($ext_game_participation)) {
            return false;
        }

        $this->getById($user, $ext_game_participation);
        $this->incrementStake($real_stake, $bonus_stake, $play_bonus_stake);
        return $ext_game_participation;
    }

    /**
     * Incrementing player balance for the participation
     *
     * @param int $amount
     * @return bool
     */
    public function incrementBalance(int $amount): bool
    {
        if (empty($this->participation)) {
            return false;
        }
        return phive('SQL')->incrValue(
            self::TABLE,
            'balance',
            ['id' => $this->participation['id']],
            $amount,
            [],
            $this->participation['user_id']
        );
    }

    /**
     * Incrementing player stake for the participation
     *
     * @param int $real_increment
     * @param int $bonus_increment
     * @param int $play_bonus_stake
     * @return bool
     */
    public function incrementStake(int $real_increment, int $bonus_increment, int $play_bonus_stake = 0): bool
    {
        if (new ParticipationIncrement($this->participation, $real_increment, $bonus_increment, $play_bonus_stake)) {
            return phive('SQL')->incrValue(
                self::TABLE,
                '',
                ['id' => $this->participation['id']],
                [
                    'balance' => $real_increment,
                    'stake' => ($real_increment + $bonus_increment + $play_bonus_stake)
                ],
                [],
                $this->participation['user_id']
            );
        }

        return false;
    }

    /**
     * End player participation
     *
     * @param $user
     * @param string $end_time
     * @param $external_game_session
     * @return bool
     */
    public function endParticipation($user, string $end_time, $external_game_session): bool
    {
        $data = [
            'id' => $this->participation['id'],
            'ended_at' => $end_time
        ];

        if (phive('SQL')->sh($user)->save(self::TABLE, $data)) {
            $payload = $this->getGameSessionSummaryPayload($end_time, $external_game_session);

            try {
                $response = $this->it->endParticipationFinalPlayerBalance($payload);

                if ($response['code'] !== PgdaReturnCode::SUCCESS_CODE) {
                    phive()->dumpTbl('ERROR-endParticipation', ['payload' => $payload, 'response' => $response]);
                    $this->notify('ADM Error on message 430', compact('payload', 'response' ));
                    // retry once
                    phive('Site/Publisher')->single(
                        'pgda',
                        'Licensed',
                        'doLicense',
                        ['IT', 'endParticipationFinalPlayerBalance', [$payload]]
                    );

                    return false;
                }

                return true;
            } catch (Exception $e) {
                return false;
            } // (PGDA Message 430)
        }

        return false;
    }

    /**
     * Return the ext session id
     *
     * @return string
     */
    public function getSessionId(): string
    {
        return $this->participation['external_game_session_id'];
    }

    /**
     * Get the external participation id given by PGDA Message 420
     *
     * @param mixed $user
     * @param array $game
     * @param array $ext_game_session
     * @param int $real_stake
     * @param int $bonus_stake
     * @param int $play_bonus_stake
     * @return false|mixed
     * @throws Exception
     */
    public function admStartParticipation(
        $user,
        array $game,
        array $ext_game_session,
        int $real_stake,
        int $bonus_stake,
        int $play_bonus_stake
    ) {
        if(licSetting('aams_disabled', $user)){
            // We have turned off calls to the external services because we just want to perform local testing.
            return uniqid();
        }
        $regional_code = $this->getRegionalCode($user);
        $code_type_tag = $this->getDeviceType($user);
        $stake = $real_stake + $bonus_stake;

        try {
            $payload = [
                'game_code' => AAMSSessionService::getGameRegulatoryCode($game['ext_game_name']),
                'game_type' => AAMSSessionService::getGameRegulatoryType($game['ext_game_name']),

                'central_system_session_id' => $ext_game_session['ext_session_id'],
                'participation_id_code' => empty($this->participation) ? '' : $this->participation['participation_id'],
                'progressive_participation_number' => $this->getProgressiveParticipationNumber(),
                'participation_fee' => $play_bonus_stake ?: $stake,
                'real_bonus_participation_fee' => $play_bonus_stake ? 0 : $bonus_stake,
                'participation_amount_resulting_play_bonus' => $play_bonus_stake,
                'regional_code' => $regional_code,
                'ip_address' => $user->data['cur_ip'],
                'code_license_account_holder' => (int)$this->it->config()['id_cn'],
                'network_code' => (int)$this->it->config()['network_id'],
                'gambling_account' => (string)$user->data['id'],
                'player_pseudonym' => trim($user->data['username']),
                'date_participation' => [
                    'date' => [
                        'day' => date('d'),
                        'month' => date('m'),
                        'year' => date('Y'),
                    ],
                    'time' => [
                        'hour' => date('H'),
                        'minutes' => date('i'),
                        'seconds' => date('s')
                    ],
                ],
                'initial_stage_progressive_number' => 1,
                'code_type_tag' => $code_type_tag
            ];

            $response = $this->it->acquisitionParticipationRightMessage($payload);

            if ($response['code'] !== PgdaReturnCode::SUCCESS_CODE) {
                phive()->dumpTbl('ERROR-getParticipationRight', ['payload' => $payload, 'response' => $response]);
                $this->notify('ADM Error on message 420', compact('ext_game_session','payload', 'response'));
                return false;
            }

            return $response['response']['participation_code'];
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Payload for PGDA message 430
     *
     * @param string $end_time
     * @param array $external_game_session
     * @return array
     * @see EndParticipationFinalPlayerBalanceEntity
     */
    public function getGameSessionSummaryPayload(string $end_time, array $external_game_session): array
    {
        $user = cu($this->participation['user_id']);
        $end_at = strtotime($end_time);

        $this->setParticipationBonusEntry($this->participation['user_id'], $this->participation['id']);
        $play_bonus_staked = $this->getPlayBonusStaked();
        $play_bonus_balance = $this->getPlayBonusBalance();
        $play_bonus_won = $this->getPlayBonusWins();
        $has_play_bonus = !empty($this->bonus);
        $this->participation_summary = $this->getParticipationSummary($this->participation['user_id'], $this->participation['id']);

        return [
            'game_code' => AAMSSessionService::getGameRegulatoryCode($this->participation['ext_game_id']),
            'game_type' => AAMSSessionService::getGameRegulatoryType($this->participation['ext_game_id']),
            'central_system_session_id' => $external_game_session['ext_session_id'],
            'participation_id_code' => $this->participation['participation_id'],
            'number_stage_undertaken_player' => $this->getParticipationNumberOfStages(),
            'participation_amount' => $this->getParticipationBalance(),
            'real_bonus_participation_amount' => 0,
            'play_bonus_participation_amount' => $play_bonus_balance ?: 0,
            'amount_staked' => $has_play_bonus ? $play_bonus_staked : (int)$this->getParticipationTotalBetAmount(),
            'real_bonus_staked_amount' => 0,
            'amount_staked_resulting_play_bonus' => $play_bonus_staked,
            'taxable_amount' => $has_play_bonus ? 0 : $this->getTaxableAmount(),
            'amount_returned_winnings' => $has_play_bonus ? $play_bonus_won : (int)$this->getParticipationTotalWinAmount(),
            'amount_returned_resulting_jackpots' => 0, //
            'amount_returned_resulting_additional_jackpots' => 0,
            'amount_returned_assigned_as_real_bonus' => 0,
            'amount_giver_over_play_bonus' => $play_bonus_won,
            'code_license_account_holder' => (int)$this->it->config()['id_cn'],
            'network_code' => (int)$this->it->config()['network_id'],
            'gambling_account' => (string)$user->data['id'],
            'end_stage_progressive_number' => $this->getParticipationNumberOfStages(),
            'date_final_balance' => [
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
            ],
            'jackpot_fund_amount' => 0,
        ];
    }

    /**
     * Get adm taxable amount that bet - win
     *
     * @return int
     */
    public function getTaxableAmount() : int
    {
        return 10000 * $this->participation_summary['net'];
    }

    /**
     * Get participation data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->participation;
    }

    /**
     * Get the progressive participarion number
     *
     * @return false|int|string
     */
    private function getProgressiveParticipationNumber()
    {
        return empty($this->participation) ? 1 : ParticipationIncrement::countByParticipation($this->participation) + 1;
    }

    /**
     * Get all (open, close and addon) increments of a user for a given period of days
     *
     * @param integer|string $user_id The user id
     * @param string $start_date The starting datetime, in SQL accepted format ('yyyy-mm-dd hh:mm:ss')
     * @param string $end_date The ending datetime, in SQL accepted format ('yyyy-mm-dd hh:mm:ss')
     *
     * @return array An array of increments ([[session_id, participation_id, session_status, game_name, created_at, balance, amount, win]])
     */
    public function getGameSessionBalancesByUserId($user_id, $start_date, $end_date) : array
    {
        $ext_game_participations = $this->db->sh($user_id)->loadArray("SELECT {$this->table}.*, micro_games.game_name
            FROM {$this->table}
            LEFT JOIN micro_games ON {$this->table}.ext_game_id COLLATE utf8_unicode_ci = micro_games.ext_game_name
            WHERE {$this->table}.user_id = {$user_id} AND created_at BETWEEN '{$start_date}' AND '{$end_date}' 
            ORDER BY created_at DESC");

        foreach($ext_game_participations as $i => $participation) {
            $ext_game_participations[$i]['increments'] = $this->getParticipationIncrementsByParticipationId($user_id, $participation['id']);
        }

        return $ext_game_participations;
    }

    /**
     * Get all (open, close and addon) increments of a user for a given external game session
     *
     * @param integer|string $user_id The user id
     * @param integer|string $participation_row_id The ext_game_participations.id
     *
     * @return array An array of increments ([[session_id, participation_id, session_status, game_name, created_at, balance, amount, win]])
     */
    public function getExternalGameSessionDetailsBySessionId($user_id, $session_id)
    {
        $ext_game_session_id = $this->db->getValue(null, 'ext_session_id', 'ext_game_sessions', ['id' => $session_id]);

        $ext_game_participations = $this->db->sh($user_id)->loadArray("SELECT 
                {$this->table}.*, micro_games.game_name
            FROM {$this->table}
            LEFT JOIN micro_games ON {$this->table}.ext_game_id COLLATE utf8_unicode_ci = micro_games.ext_game_name
            WHERE {$this->table}.external_game_session_id = {$session_id} 
            ORDER BY created_at DESC");

        foreach($ext_game_participations as $i => $participation) {
            $ext_game_participations[$i]['id_cn'] = $this->it->config()['id_cn'];
            $ext_game_participations[$i]['session_id'] = $ext_game_session_id;
            $ext_game_participations[$i]['increments'] = $this->getParticipationIncrementsByParticipationId($user_id, $participation['id']);
        }

        return $ext_game_participations;
    }

    /**
     * Get the participation increaments
     *
     * @param array $participation participation data
     * @return array
     */
    public function getParticipationIncrements($participation)
    {
        if (!empty($this->increments)) {
            return $this->increments;
        }

        if (empty($participation)) {
            return [];
        }

        $where = phive('SQL')->makeWhere(['participation_id' => $participation['id']]);
        $str = "SELECT * FROM ext_game_participations_increments {$where}";
        return phive('SQL')->sh($participation)->loadArray($str);
    }

    /**
     * Get the participation increaments
     *
     * @param array $participation participation data
     * @return array
     */
    public function getParticipationIncrementsByParticipationId($user_id, $participation_id)
    {
        $where = $this->db->makeWhere(compact('participation_id'));
        return $this->db->sh($user_id)->loadArray("SELECT * FROM ext_game_participations_increments {$where}");
    }

    /**
     * Return player bonus stake if any
     *
     * @param array $game
     * @param $user
     * @return float|int
     */
    public function getPlayBonusStake(array $game, $user)
    {
        $desktop_game = phive('MicroGames')->getDesktopGame($game) ?? $game;
        $this->bonus = phive('Bonuses')->getBonusEntryByGameIdAndFreeSpinsRemaining($user->getId(), $desktop_game['game_id']);
        return $this->getRemainingBonusStake();
    }

    /**
     * Returns remaining bonus stake
     * @return float|int
     */
    public function getRemainingBonusStake()
    {
        if (!empty($this->bonus)) {
            return $this->bonus['frb_denomination'] * 100 * $this->bonus['frb_lines'] * $this->bonus['frb_remaining'];
        }
        return 0;
    }

    public function getPlayBonusBalance()
    {
        if (!empty($this->bonus)) {
            return $this->getRemainingBonusStake() + $this->bonus['reward'];
        }
        return 0;
    }

    /**
     * Get the bonus associated with the participation
     *
     */
    public function setParticipationBonusEntry($user_id, $participation_id)
    {
        if (empty($user_id) || empty($participation_id)) {
            return;
        }
        $sql = "SELECT  
                            epb.id AS participation_bonus_id,
                            epb.balance_start,
                            epb.balance_end,
                            be.* ,
                            bt.frb_denomination,
                            bt.frb_lines,
                            bt.rake_percent,
                            bt.frb_coins,
                            bt.game_id
                    FROM ext_game_participations_bonuses epb, bonus_entries be, bonus_types bt 
                        WHERE 
                        epb.ext_game_participation_id = $participation_id 
                        AND epb.bonus_entry_id = be.id 
                        AND bt.id = be.bonus_id";
        $this->bonus = phive('SQL')->sh($user_id)->loadAssoc($sql);
    }

    /**
     * Freespin Amount staked by the player
     * Updates the participation bonus table with final values after participation ends
     *
     * @return float|int|mixed
     */
    public function getPlayBonusStaked()
    {
        if (!empty($this->bonus)) {
            $stake_remaining = $this->getRemainingBonusStake();
            $play_bonus_staked = $this->bonus['balance_start'] - $stake_remaining;
            $won_amount = $this->getPlayBonusWins();

            if ($play_bonus_staked != 0) {
                $this->db->sh($this->participation['user_id'])->updateArray(self::TABLE_BONUSES, ['balance_end' => $stake_remaining + $won_amount, 'won_amount' => $won_amount], ['id' => $this->bonus['participation_bonus_id']]);
                return $play_bonus_staked;
            }
        }
        return 0;
    }

    public function getPlayBonusWins(): int
    {
        return (int)$this->bonus['reward'] ?? 0;
    }

    /**
     * Start or get a user game session
     *
     * //TODO make sure that device_type_num can never be NULL
     * @param $user
     * @param $game
     * @return false|string
     */
    protected function getUserGameSession($user, $game)
    {
        $insert = [
            'user_id'     => $user->getId(),
            'game_ref'    => $game['ext_game_name'],
            'device_type' => "{$game['device_type_num']}", // surrounding with quotes to make sure mysql is not failing with empty value
            'amount'      => 0
        ];

        $user_game_session = phive('Casino')->getGsess($insert, $user);

        if(!empty($user_game_session['start_time'])) {
            return $user_game_session;
        }

        return phive('SQL')->sh($user->getId())->loadAssoc(NULL, 'users_game_sessions', ['id' => $user_game_session['id']]);
    }

    /**
     * get the user regional code
     *
     * @param object $user
     * @return int
     * @throws Exception
     */
    public function getRegionalCode(object $user) : int
    {
        return (new ProvincesService($user))->getUserRegionCode();
    }

    /**
     * get the user_game_session by id
     *
     * @param object $user
     * @param int $id
     * @return array|false|string
     */
    public function getUserGameSessionById(object $user, int $id)
    {
        return phive('SQL')->sh($user)->loadAssoc(null, 'users_game_sessions', compact('id'));
    }


    /**
     * Return the device type used by the player
     *
     * @param $user
     * @return int
     */
    public function getDeviceType($user): int
    {
        // regular expression used to parse user agent
        $re = '/^(\w+\/[\d.]+) \(([^\(\)]+)\) ?(.*)$/i';
        $ua = $user->getSetting('uagent');

        preg_match($re, $ua, $matches);

        // determining the operating system
        if (isset($matches[2])) {
            // defining the mapping between the specific re and the constant to use
            $map = [
                '/ipod|ipad|iphone/i' => static::MOBILE_BROWSER_IOS,
                '/macintosh/i' => static::DESKTOP_BROWSER_OSX,
                '/android/i' => static::MOBILE_BROWSER_ANDROID,
                '/^(?!.*(android)).*linux.*$/i' => static::DESKTOP_BROWSER_OTHER,
                '/windows (phone|mobile)/i' => static::MOBILE_BROWSER_WINDOWS,
                '/^(?!.*(phone|mobile)).*windows.*$/i' => static::DESKTOP_BROWSER_WINDOWS,
            ];

            foreach ($map as $re => $enum) {
                if (preg_match($re, $matches[2])) {
                    return $enum;
                }
            }
        }

        return static::DESKTOP_BROWSER_OTHER;
    }

    /**
     * Participation balance
     * @return int
     */
    public function getParticipationBalance(): int
    {
        return (int)($this->getPlayBonusBalance() ?: $this->getParticipationRealMoneyBalance());
    }

    /**
     * Participation balance coming from real play
     * @return false|int|string
     */
    public function getParticipationRealMoneyBalance() {
        return  $this->participation_summary['balance'] ?? $this->participation['stake'];
    }

    /**
     * The total Bet amount
     * @return mixed
     */
    public function getParticipationTotalBetAmount()
    {
        return (int)$this->participation_summary['bet'];
    }

    /**
     * The total won amount
     * @return mixed
     */
    public function getParticipationTotalWinAmount()
    {
        return (int)$this->participation_summary['win'];
    }

    /**
     * Number of rounds
     *
     * @return float|int|mixed|string
     */
    private function getParticipationNumberOfStages()
    {
        return $this->getPlayBonusStaked() ?: (int)$this->participation_summary['bets_count'];
    }

    /**
     * Get summary of participation based on the information stored on rounds table
     * // TODO only report rounds finished ch106978
     *
     * @param $user_id
     * @param $participation_id
     * @return array|false|string
     */
    private function getParticipationSummary($user_id, $participation_id) {
        $sql = "SELECT 
            ext_game_participations.user_id,
            ext_game_participations.stake - IFNULL(SUM(bets.amount), 0) + IFNULL(SUM(wins.amount), 0)  AS balance,
            ext_game_participations.stake,
	        COUNT(ext_game_participations_rounds.id) AS bets_count,
            IFNULL(SUM(bets.amount), 0) AS bet,
            IFNULL(SUM(wins.amount), 0) AS win,
            IFNULL(SUM(bets.amount), 0) - IFNULL(SUM(wins.amount), 0)  AS net
        FROM ext_game_participations 
        JOIN ext_game_participations_rounds ON ext_game_participations.id = ext_game_participations_rounds.ext_game_participation_id
        JOIN rounds ON ext_game_participations_rounds.round_id = rounds.id
        JOIN bets ON rounds.bet_id = bets.id
        LEFT JOIN wins ON rounds.win_id = wins.id
        WHERE ext_game_participations.id = $participation_id
        AND rounds.is_finished = true
        GROUP BY ext_game_participations.id";

        return $this->db->sh($user_id)->loadAssoc($sql);
    }

    public function getByUserGameSessionId($user, int $user_game_session_id)
    {
        if (empty($this->participation)) {
            $this->participation = phive('SQL')->sh($user)->loadAssoc(null, self::TABLE, compact('user_game_session_id'));
            if (empty($this->participation)) {
                $this->participation = [];
            }
        }
        return $this->participation;
    }

}
