<?php

namespace ES\Services;

use ES;
use Monolog\Logger as MonologLogger;

class SessionService
{

    const TABLE = 'ext_game_sessions';

    private Participation $participation;

    /**
     * SessionService constructor.
     */
    public function __construct()
    {
        $this->participation = new Participation();
    }

    /**
     * Start a new session and store the stake information in database
     *
     * @param \DBUser $user The user id or the (already initialized) user object
     * @param string $token_id Token shared with game provider on game launch/authenticationgetGameRegulatoryCode
     * @param array $data
     * @param array $game from micro_games table
     *
     * @return bool|int
     * @throws \Exception
     */
    public function createNewSession($user, string $token_id, array $data, array $game)
    {
        $user = cu($user);
        if (empty($user)) {
            return false;
        }
        $user_game_session = $this->getUserGameSession($user, $game);
        $bonus_game = phive('Bonuses')->getBonusGameForMobileDevice($game);
        $free_spins_entry = phive('Bonuses')->getBonusEntryByGameIdAndFreeSpinsRemaining(uid($user), $bonus_game['game_id']);

        $data['balance'] = $data['real_stake'];
        $data['won'] = 0;
        $data['wagered'] = 0;
        $data['first_load'] = true;
        $data['account_balance'] = $user->getAttr('cash_balance') - $data['real_stake'];
        $data['is_free_spin_session'] = phive('CasinoBonuses')->isFreeSpinGameSession($user, $bonus_game);

        if (!empty($free_spins_entry)) {
            $data['free_spins'] = (int)$free_spins_entry['frb_remaining'];
        }
        $this->sendSessionDataToWs($data, $user);


        phive('Logger')->getLogger('game_providers')->debug(__METHOD__, [
            'user_id' => $user->getId(),
            'data' =>  $data,
            'token_id' => $token_id,
            'ext_game_name' => $game['ext_game_name'],
            'user_game_session_id' => $user_game_session['id'] ?? '',
            'free_spins_entry' => $free_spins_entry['id'] ?? '',
        ]);

        $create_result = $this->participation->create(
            $game,
            $user,
            $user_game_session,
            $data['real_stake'],
            $data['set_reminder'],
            $data['restrict_future_check'],
            $data['restrict_future_sessions'] ?: 0,
            $token_id,
            $data['game_limit'],
            $data['account_balance'],
            $data['is_free_spin_session']
        );
        phive('Licensed/ES/ES')->logGS('SessionService createNewSession',
            "User:{$user->getId()} create new ugs session on id: {$user_game_session['id']} and game: {$game['id']} create result: {$create_result}",
            $user,
            MonologLogger::DEBUG);
        return $create_result;
    }

    /**
     * Wrapper to extract current open participation.
     *
     * @param $user
     * @return array|mixed
     */
    public function getOpenParticipation($user)
    {
        return $this->participation->getOpenParticipationByUser($user);
    }

    /**
     * Send WS updates on session creation / update to keep topbar informations in sync
     *
     * @param $data
     * @param $user
     *
     * @return bool|void
     */
    public function sendSessionDataToWs($data, $user)
    {
        $user = cu($user);
        if (empty($user)) {
            return false;
        }
        // TODO handle logic on FE via GameCommunicator to postpone showing this information after the game animation is over.
        return toWs(['target' => 'session_balance', 'msg' => $data], 'extgamesess', $user->getId());
    }

    /**
     * Interface method for getting external session information
     *
     * @param object $user The user object
     * @param $play_session_id
     * @return mixed |array|false|string
     */
    public function getPlaySessionById($user, $play_session_id)
    {
        return $this->participation->getById($user, $play_session_id);
    }

    /**
     * Interface method for getting external session information by GP token
     *
     * @param object $user The user object
     * @param $play_session_token
     * @return mixed |array|false|string
     */
    public function getPlaySessionByToken($user, $play_session_token)
    {
        return $this->participation->getByToken($user, $play_session_token);
    }

    /**
     * Interface method for incrementing/decrementing external session balance
     *
     * @param $amount
     * @return mixed |array|false|string
     */
    public function incrementSessionBalance($amount)
    {
        return $this->participation->incrementBalance($amount);
    }

    /**
     * Interface method to end the current external game session
     *
     * @param $user
     * @param $play_session_id
     * @param $end_time
     * @return bool
     */
    public function endPlayerSession($user, $play_session_id, $end_time)
    {
        $this->participation->getById($user, $play_session_id);

        if ($this->participation->endParticipation($user, $end_time)) {
            return true;
        }

        return false;
    }

    /**
     * Gets the ongoing users_game_session. If $current_session is equals to false,
     * ongoing session condition will be omitted.
     *
     * @param array $participation
     * @param bool $current_session
     * @return array|false|string
     */
    public function getGameSessionByParticipation(array $participation, bool $current_session = true)
    {
        $session_id = $participation['user_game_session_id'];
        $conditions = $current_session ? " AND end_time = '0000-00-00 00:00:00' " : '';

        $str = "
            SELECT * FROM users_game_sessions 
            WHERE id = {$session_id}
            {$conditions} 
            ORDER BY id DESC
        ";

        return phive('SQL')->sh($participation['user_id'])->loadAssoc($str);
    }

    /**
     * Start or get a user game session
     *
     * @param \DBUser $user
     * @param $game
     * @return false|string
     */
    protected function getUserGameSession($user, $game)
    {
        $ins = [
            'user_id' => $user->getId(),
            'game_ref' => $game['ext_game_name'],
            'device_type' => $game['device_type_num'],
            'amount' => 0
        ];

        $user_game_session = phive('Casino')->getGsess($ins, $user);

        // TODO verify this check... looks useless, as the session is either found or created above /Paolo
        $user_game_session = !empty($user_game_session['start_at'])
            ? $user_game_session
            : phive('SQL')->sh($user->getId())->loadAssoc(NULL, 'users_game_sessions', ['id' => $user_game_session['id']]);

        phive('Logger')->getLogger('game_providers')->debug(__METHOD__, [
            'user_id' => $user->getId(),
            'ins' => $ins,
            'ext_game_name' => $game['ext_game_name'],
            'user_game_session_id' => $user_game_session['id'],
        ]);

        return $user_game_session;
    }

    /**
     * Returns the balance of the open sessions
     *
     * !! Not really needed by ES logic, as we have 1 single session, but mandatory as it's used by ExternalGameSessionTrait.
     * TODO see if this can be cleaned/reworked on the common logic via lic functions.. Then test both ES/IT for regressions.
     *
     * @param $user
     * @return mixed
     */
    public function getAllOpenSessionsBalance($user)
    {
        $participation = $this->getOpenParticipation($user);
        return (int) ($participation['balance'] ?? 0);
    }


    /**
     * Returns the last session
     *
     * @param $user
     * @param  bool  $closed
     *
     * @return array
     */
    public function getLastSession($user, $closed = true): array
    {
        return $this->participation->getLastSession($user, $closed);
    }


    /**
     * Update tha balance in FE with the latest session data
     *
     * @param $user
     * @param $game
     */
    public function wsUpdateSessionAfterBet($user, $game)
    {
        $participation = $this->getOpenParticipation($user);
        $game_session = $this->getGameSessionByParticipation($participation);

        $data['balance'] = $participation['balance'];
        $data['won'] = $game_session['win_amount'];
        $data['wagered'] = $game_session['bet_amount'];
        $data['first_load'] = false;
        $data['account_balance'] = $participation['initial_balance'];
        $free_spins_entry = phive('Bonuses')->getBonusEntryByGameIdAndFreeSpinsRemaining(uid($user), $game['game_id']);
        if (!empty($free_spins_entry)) {
            $data['free_spins'] = (int)$free_spins_entry['frb_remaining'];
        }

        $this->sendSessionDataToWs($data, $user);
    }

    /**
     * Return all session increments for a specific user and date range
     *
     * @param integer|string $user_id The user id
     * @param string $start_date The starting date ('yyyy-mm-dd')
     * @param string $end_date The ending date ('yyyy-mm-dd')
     * @param string $page current page
     *
     * @return array An array of increments ([[session_id, participation_id, session_status, game_name, created_at, balance, amount, win]])
     */
    public function getGameSessionBalancesByUserId($user_id, $start_date, $end_date, $page = null)
    {
        return $this->participation->getGameSessionBalancesByUserId($user_id, $start_date, $end_date, $page);
    }

    /**
     * Returns all open sessions
     *
     * @param $user
     * @return mixed
     */
    public function getAllOpenExternalSessions($user)
    {
      return $this->participation->getParticipationsOpenByUser($user);
    }
}
