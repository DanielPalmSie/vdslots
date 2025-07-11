<?php

/**
 * A wrapper for handling the Realtime stats logic related to the users_realtime_stats table.
 */
class RealtimeStats
{
    /**
     *
     * @param $user_id
     * @param null $start_date - yyyy-mm-dd - if empty falls back to beginning of month
     * @param null $end_date - yyyy-mm-dd - if empty falls back to now
     * @return false|int|string
     */
    public function getPlayerNgrInPeriod($user_id, $start_date = null, $end_date = null)
    {
        if(empty($start_date)) {
            $start_date = (new DateTime('first day of this month'))->format('Y-m-d');
        }

        if(empty($end_date)) {
            $end_date = (new DateTime('now'))->format('Y-m-d');
        }

        $query = "
            SELECT 
                IFNULL(SUM(bets - wins - frb_wins - jp_contrib - rewards + fails),0) as NGR
            FROM 
                users_realtime_stats
            WHERE 
                user_id = {$user_id}
                AND date BETWEEN '{$start_date}' AND '{$end_date}'
        ";

        return phive('SQL')->sh($user_id)->getValue($query);
    }

    /**
     * Update real time stats for bets. If received a FS bet (amount = 0) then we ignore it, not even for the count since
     * we don't get FS bets on all suppliers.
     *
     * @param int $user_id
     * @param array $bet
     *
     * @return mixed
     */
    public function onBet(int $user_id, array $bet)
    {
        if (empty($bet['amount'])) {
            return true;
        }
        $values = ['bets' => $bet['amount'], 'bet_count' => 1, 'jp_contrib' => $bet['jp_contrib']];

        return $this->updateTable($user_id, $values, $bet['currency']);
    }

    /**
     * Update real time stats for wins, if bonus_bet is 3 then it is a frb win and we story the amount on
     *
     * @param int $user_id
     * @param array $win
     *
     * @return mixed
     */
    public function onWin(int $user_id, array $win)
    {
        $values = ['win_count' => 1];
        if((int)$win['bonus_bet'] == 3) {
            $values['frb_wins'] += $win['amount'];
        } else {
            $values['wins'] += $win['amount'];
        }

        return $this->updateTable($user_id, $values, $win['currency']);
    }

    /**
     * Handle updates on real time stats for rollbacks on bets and wins
     *
     * @param DBUser $user
     * @param string $type
     * @param string $amount
     *
     * @return mixed
     */
    public function onRollback(DBUser $user, string $type, string $amount)
    {
        if (!in_array($type, ['bets', 'wins'])) {
            return false;
        }

        return $this->updateTable($user->getId(), ["{$type}_rollback" => $amount], $user->getCurrency());
    }

    /**
     * Handle updates on real time stats for cash transactions: deposits, withdrawals, rewards and tournament transactions
     *
     * @param DBUser $user
     * @param int $type
     * @param string $amount
     *
     * @return mixed
     */
    public function onCashTransaction(DBUser $user, int $type, string $amount)
    {
        // TODO compare with admin2 query to see if anything is missing.
        if ($type == 3) {
            $values['deposits'] = $amount;
        } elseif (in_array($type, [31, 32, 66, 69, 77, 80, 82, 84, 90, 94, 95, 96])) {
            $values['rewards'] = $amount;
        } elseif (in_array($type, [67, 72])) {
            $values['fails'] = $amount;
        } elseif (in_array($type, [34, 38, 52, 54, 61, 63, 64, 74, 75, 85, 86])) {
            $values['tournaments'] = $amount;
        } else {
            return false;
        }

        return $this->updateTable($user->getId(), $values, $user->getCurrency());
    }


    /**
     * Handle updates on real time stats for withdrawals only done in approval stage
     *
     * @param int $user_id
     * @param string $amount
     * @param string $currency
     * @return mixed
     */
    public function onWithdrawalApproval(int $user_id, string $amount, string $currency)
    {
        return $this->updateTable($user_id, ['withdrawals' => $amount], $currency);
    }

    /**
     * Common function to do all the real time stats updates
     *
     * @param int $user_id
     * @param array $values
     * @param string $currency
     * @param null|string $date
     *
     * @return mixed
     */
    public function updateTable(int $user_id, array $values, string $currency, $date = null)
    {
        return phive('SQL')->incrOrInsertValues(
            'users_realtime_stats',
            $values,
            ['date' => $date ?? phive()->today(), 'user_id' => $user_id, 'currency' => $currency],
            $user_id
        );
    }

    /**
     * This function recalculates one day, for now only supports basic NGR calculation related data.
     *
     * TODO implement support for the rest of the data
     *
     * @param null $day
     */
    public function recalculateDay($day = null)
    {
        $day = $day ?? phive()->yesterday();

        phive('SQL')->loopShardsSynced(function(SQL $db) use($day){

            $generator = clone $db;

            $generator->deleteBatched("SELECT id FROM users_realtime_stats WHERE date = '{$day}'", function($r) use ($db){
                $db->delete('users_realtime_stats', ['id' => $r['id']]);
            }, 500);

            $uds = $db->loadArray("SELECT * FROM users_daily_stats WHERE date = '{$day}'");
            foreach ($uds AS $row) {
                $insert = [
                    'date' => $day,
                    'user_id' => $row['user_id'],
                    'currency' => $row['currency'],
                    'bets' => $row['bets'],
                    'wins' => $row['wins'],
                    'rewards' => $row['rewards'],
                    'fails' => $row['fails'],
                    'jp_contrib' => $row['jp_contrib']
                ];

                $db->insertArray('users_realtime_stats', $insert);
            }
        });
    }
}