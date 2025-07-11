<?php

namespace ES\Services;

use Monolog\Logger as MonologLogger;

class Participation
{

    const TABLE = 'ext_game_participations';
    /** @var array  */
    protected $participation;

    /**
     * Participation constructor.
     *
     */
    public function __construct()
    {
        $this->participation = [];
    }

    /**
     * @param $user
     * @param $id
     * @return mixed
     */
    public function getById($user, $id)
    {
        $this->participation = phive('SQL')->sh($user)->loadAssoc('', self::TABLE, ['id' => $id]);
        return $this->participation;
    }

    /**
     * @param $user
     * @param $token_id
     * @return mixed
     */
    public function getByToken($user, $token_id)
    {
        if ($this->incorrectParticipationSet('token_id', $token_id)) {
            $this->participation = phive('SQL')->sh($user)->loadAssoc('', self::TABLE, ['token_id' => $token_id]);
        }
        return $this->participation;
    }

    /**
     * Creating new participation request
     *
     * @param $game array
     * @param $user
     * @param $user_game_session
     * @param $real_stake int
     * @param $set_reminder int
     * @param $restrict_future_check bool
     * @param $restrict_future_sessions int
     * @param $token_id string Token coming from game provider
     * @param $game_limit int
     * @param $initial_balance int
     * @param $is_fs_session
     * @return bool|int
     */
    public function create($game, $user, $user_game_session, $real_stake, $set_reminder, $restrict_future_check, $restrict_future_sessions, $token_id,
        $game_limit, $initial_balance, $is_fs_session)
    {
        $insert = [
            'participation_id' => $user_game_session['id'],
            'external_game_session_id' => 0,
            'token_id' => $token_id,
            'user_id' => uid($user),
            'user_game_session_id' => $user_game_session['id'],
            'balance' => $real_stake,
            'stake' => $real_stake,
            'time_limit' => $game_limit,
            'reminder' => $set_reminder,
            'restrict_future_session' => $restrict_future_check,
            'limit_future_session_for' => $restrict_future_sessions,
            'ext_game_id' => $game['ext_game_name'],
            'initial_balance' => $initial_balance,
            'is_free_spin_session' => $is_fs_session,
        ];

        phive('Logger')->getLogger('game_providers')->debug(__METHOD__, [
            'user_id' => $user->getId(),
            'ext_game_name' =>  $game['ext_game_name'],
            'insert' => $insert,
        ]);

        try {
            $session_id = phive('SQL')->sh($user)->insertArray(self::TABLE, $insert);
            phive('Licensed/ES/ES')->logGS('Participation create',
                "User:{$user->getId()} created ext_game_participations id: {$session_id}", $user, MonologLogger::DEBUG);
        } catch (\Exception $e) {
            phive('Licensed/ES/ES')->logGS('Participation create',
                "Exception while creating new egp record :{$e->getMessage()}", $user, MonologLogger::WARNING);
        }

        if (!empty($session_id)) {
            return $session_id;
        }

        return false;
    }

    /**
     * Return the open participation for a given user.
     *
     * TODO we must always have only 1 participation open!
     *  if more than 1 we need to be sure we close all except the most recent one. /Paolo
     *
     * @param $user
     * @return array|mixed
     */
    public function getOpenParticipationByUser($user)
    {
        $open_participations = phive('SQL')->sh($user)->arrayWhere(self::TABLE, ['user_id' => uid($user), 'ended_at' => phive()->getZeroDate()]);
        $count = count($open_participations);
        if($count > 1) {
            // TODO handle close logic for other open participation.
            $index = $count - 1;
            return $open_participations[$index];
        }
        return $open_participations[0] ?? [];
    }

    /**
     * Return the last closed session for a given user
     *
     * @param $user
     * @param  bool  $closed
     *
     * @return array
     */
    public function getLastSession($user, $closed = true): array
    {
        $user_id = uid($user);
        $zero_date = phive()->getZeroDate();
        if ($closed) {
            $closed = "AND ended_at != '{$zero_date}'";
        }

        return phive('SQL')->sh($user)->loadArray("
            SELECT 
                limit_future_session_for,
                restrict_future_session,
                created_at,
                ended_at,
                id,
                user_game_session_id,
                ended_at != '$zero_date' AS closed
            FROM ext_game_participations
            WHERE user_id = {$user_id} {$closed}
            ORDER BY id DESC
            LIMIT 1
        ")[0] ?? [];
    }

    /**
     * @param $amount
     * @return mixed
     */
    public function incrementBalance($amount)
    {
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
     * @param $user
     * @param $end_time
     * @return array|false
     */
    public function endParticipation($user, $end_time)
    {
        if (!empty($this->participation)) {
            $data = [
                'id' => $this->participation['id'],
                'ended_at' => $end_time
            ];
            if (phive('SQL')->sh($user)->save(self::TABLE, $data)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->participation;
    }

    /**
     * Get all (open, close and addon) increments of a user for a given period of days
     *
     * @param integer|string $user_id The user id
     * @param string $start_date The starting datetime, in SQL accepted format ('yyyy-mm-dd hh:mm:ss')
     * @param string $end_date The ending datetime, in SQL accepted format ('yyyy-mm-dd hh:mm:ss')
     * @param string $page current page
     *
     * @return array An array of increments ([[session_id, participation_id, session_status, game_name, created_at, balance, amount, win]])
     */
    public function getGameSessionBalancesByUserId($user_id, $start_date, $end_date, $page = null)
    {
        return $this->getExternalGameSessionRows($user_id, null, $start_date, $end_date, $page);
    }

    /**
     * Read ext_game_participations and ext_game_participations_increments for history
     * May read only one session (if $participation_row_id is set) or many (if $start_date and $end_date are set)
     *
     * @param integer|string $user_id The user id, used to filter the session(s)
     * @param integer|string $participation_row_id If defined, then read only participations & increments with this ext_game_participations.id
     * @param string $start_date If $participation_row_id is not defined, then use it to filter participations & increments by date
     * @param string $end_date If $participation_row_id is not defined, then use it to filter participations & increments by date
     *
     * @return array An array of increments ([[session_id, participation_id, session_status, game_name, created_at, balance, amount, win]])
     */
    private function getExternalGameSessionRows($user_id, $participation_row_id = null, $start_date = null, $end_date = null, $page = null)
    {
        $filter = $participation_row_id ? "egp.id = {$participation_row_id}" : "egp.created_at BETWEEN '$start_date' AND '$end_date'";

        //TODO fix this static after maybe they will do for now for the audit
        $page 		    = empty($page) ? 1 : $page;
        $limit 		    = 'LIMIT '.(($page - 1) * 12).',12';

        // Get only finished sessions
        $sessions = phive('SQL')->sh($user_id)->loadArray("
            SELECT
                egp.id AS session_id,
                egp.participation_id,
                egp.created_at,
                egp.stake,
                ugs.balance_start,
                ugs.bet_amount,
                ugs.win_amount,
                egp.ended_at
            FROM 
                ext_game_participations AS egp
                LEFT JOIN users_game_sessions AS ugs ON egp.participation_id = ugs.id
            WHERE 
                egp.user_id = {$user_id} 
                AND egp.ended_at != '0000-00-00 00:00:00'
                AND {$filter}
            ORDER BY 
                egp.created_at DESC
            $limit
        ");

        $result = [];
        foreach ($sessions as $session) {
            $row = $session;
            $row['balance'] = null;
            $row['amount'] = $session['stake'];
            $row['created_at'] = $session['ended_at'];
            $result[] = $row;
        }

        // If there are more than one sessions, then the result is partially sorted and we need to sort it by created_at DESC
        if (!$participation_row_id) {
            $dates = array_column($result, 'created_at');
            array_multisort($dates, SORT_DESC, $result);
        }

        return $result;
    }

    /**
     * Check if correct participation data was loaded
     *
     * @param  string  $column
     * @param  mixed|null  $value
     *
     * @return bool
     */
    private function incorrectParticipationSet(string $column, $value = null): bool
    {
        // Used the loose equality operator to cover for number provided as a string
        return empty($this->participation) || $this->participation[$column] != $value;
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
}
