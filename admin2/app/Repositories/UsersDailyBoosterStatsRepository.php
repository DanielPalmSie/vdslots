<?php

namespace App\Repositories;

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Models\UserDailyBoosterStat;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use PDO;

class UsersDailyBoosterStatsRepository
{
    const DEFAULT_USERS_FILTER = ' != 0';

    const FILTER_USERS = [
        'USERS_ALL' => 'USERS_ALL',
        'USERS_WITH_AMOUNT_IN_VAULT' => 'USERS_WITH_AMOUNT_IN_VAULT',
        'USERS_WITH_NO_AMOUNT_IN_VAULT' => 'USERS_WITH_NO_AMOUNT_IN_VAULT',
        'USERS_LIST' => 'USERS_LIST'
    ];

    /**
     * This will insert into "users_daily_booster_stats" the generated/released booster for each player
     * only when there was at least a 100/101 transaction
     * @param string|null $date
     * @param string $users_list
     */
    public function cacheGeneratedBoosterByPlayer($date = null, $users_list = self::DEFAULT_USERS_FILTER)
    {
        if (empty($date)) {
            $date = Carbon::yesterday()->format('Y-m-d');
        }
        $start_date = $date . ' 00:00:00';
        $end_date = $date . ' 23:59:59';

        DB::getMasterConnection()->statement("DELETE FROM users_daily_booster_stats where date = '$date'");
        DB::loopNodes(function (Connection $connection) use ($date, $start_date, $end_date, $users_list) {
            echo "{$connection->getName()} \n";
            $connection->setFetchMode(PDO::FETCH_ASSOC); // Fetch data in array
            $connection->statement("DELETE FROM users_daily_booster_stats where date = '$date' and user_id $users_list");

            // "generated_booster" and "released_booster" are inverted compared to cash_transactions (company vs user perspective)
            $list = $connection->select("
                SELECT
                    user_id,
                    '$date' AS date,
                    cash_transactions.currency,
                    u.country,
                    COALESCE(SUM(CASE WHEN transactiontype = 100 THEN amount END),0) * -1 AS generated_booster,
                    COALESCE(SUM(CASE WHEN transactiontype = 101 THEN amount END),0) * -1 AS released_booster
                FROM cash_transactions
                LEFT JOIN users u ON u.id = cash_transactions.user_id
                WHERE
                    timestamp BETWEEN '$start_date' AND '$end_date'
                    AND transactiontype IN (100, 101)
                    AND user_id $users_list
                GROUP BY
                    user_id
            ");
            UserDailyBoosterStat::bulkInsert($list, 'user_id', $connection);

            $list = $connection->select("
                SELECT
                    user_id,
                    date,
                    currency,
                    country,
                    generated_booster,
                    released_booster
                FROM
                    users_daily_booster_stats
                WHERE
                    date = '$date'
                ");
            UserDailyBoosterStat::bulkInsert($list, 'user_id', DB::getMasterConnection());
        });
    }

    /**
     * Return all the users which currently have an amount on the booster_vault, from users_settings.
     *
     * @return Collection
     */
    public function getUsersCurrentBoosterVault()
    {
        $users_setting_booster_vault = DB::shsSelect('users_settings', "
            SELECT us.user_id, us.value AS current_booster, u.country, u.currency
            FROM users_settings us
            INNER JOIN users u ON u.id = us.user_id
            WHERE setting = 'booster_vault' AND value > 0
        ");

        return collect($users_setting_booster_vault)->keyBy('user_id');
    }

    /**
     * Return all the users which have an amount in the booster vault, from users_daily_booster_stats
     * On this one we can filter on a specific date, and the result will be calculated on all the days before that.
     *
     * Note: released_booster is negative on the DB so we do ABS on the value to make it more logically readable
     *
     * @param null $date
     * @return Collection
     * @throws Exception
     */
    public function getUsersCachedBoosterVault($date = null)
    {
        if (empty($date)) {
            $date = Carbon::yesterday()->toDateString();
        }
        $users_cached_booster_vault = DB::getMasterConnection()->select("
            SELECT
                user_id,
                users.country,
                users.currency,
                COALESCE(SUM(generated_booster),0) - ABS(COALESCE(SUM(released_booster),0)) AS cached_booster
            FROM
                users_daily_booster_stats
                INNER JOIN users ON users.id = users_daily_booster_stats.user_id
            WHERE
                date <= '{$date}'
            GROUP BY
                user_id
            HAVING
                cached_booster > 0
        ");
        return collect($users_cached_booster_vault)->keyBy('user_id');
    }

    /**
     * Return all the users which should have an amount in the booster vault, from cash_transactions
     * From beginning of history until NOW()
     *
     * @return Collection
     */
    public function getUsersAbsoluteBoosterVault()
    {
        $users_absolute_booster_vault = DB::shsSelect('cash_transactions', "
            SELECT
                ct.user_id,
                ABS(COALESCE(SUM(CASE WHEN transactiontype = 100 THEN amount END),0)) - COALESCE(SUM(CASE WHEN transactiontype = 101 THEN amount END),0) AS absolute_booster,
                u.country,
                u.currency
            FROM cash_transactions ct
            INNER JOIN users u ON u.id = ct.user_id
            WHERE transactiontype IN (100, 101)
            GROUP BY user_id
            HAVING absolute_booster > 0
            ORDER BY user_id ASC
        ");

        return collect($users_absolute_booster_vault)->keyBy('user_id');
    }


    /**
     * Configure users filter based on received $type
     *
     * @param string $type
     * @param null $users_list
     * @return string
     * @throws Exception
     */
    public function setupUsersFilter($type, $users_list = null): string
    {
        if (!in_array($type, self::FILTER_USERS, true)) {
            $type = self::FILTER_USERS['USERS_ALL'];
        }

        if ($type === self::FILTER_USERS['USERS_LIST']) {
            if (empty($users_list)) {
                throw new Exception("users_list is required when $type is used");
            }
            return " in ($users_list)";
        }

        if ($type === self::FILTER_USERS['USERS_WITH_AMOUNT_IN_VAULT']) {
            return "
                in (
                    SELECT user_id FROM users_settings
                    WHERE setting = 'booster_vault'
                    AND value > 0
                )
            ";
        }

        if ($type === self::FILTER_USERS['USERS_WITH_NO_AMOUNT_IN_VAULT']) {
            // here doing <= instead of < just to make sure that there's not exception being left out
            return "
                in (
                    SELECT user_id FROM users_settings
                    WHERE setting = 'booster_vault'
                    AND value <= 0
                )
            ";
        }

        return self::DEFAULT_USERS_FILTER;
    }
}
