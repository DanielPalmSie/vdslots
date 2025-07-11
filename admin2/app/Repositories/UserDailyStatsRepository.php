<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.23.
 * Time: 9:04
 */
namespace App\Repositories;

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\ReplicaFManager as ReplicaDB;
use App\Models\User;
use App\Helpers\DataFormatHelper;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Silex\Application;

class UserDailyStatsRepository
{

    private $php_data;
    /**
     *
     * UserDailyStatsRepository constructor.
     * @param bool $php_data return data for php/javascript compatibility
     */
    public function __construct($php_data = false)
    {
        $this->php_data = $php_data;
    }

    public function getGrossByMonth(User $user, $months = 3)
    {
        $gross = $user->dailyStats()
            ->select(DB::raw("DATE_FORMAT(date, '%Y-%b') as month"), DB::raw('sum(gross) as gross'))
            ->where('date', '>=', Carbon::now('Europe/Malta')->subMonths($months)->startOfMonth())
            ->groupBy('month')
            ->orderBy('date', 'ASC')
            ->pluck('gross', 'month')->toArray();

        return $this->toFlotGraph($gross, true);
    }

    public function getBetsByMonth(User $user, $months = 3)
    {
        $bets = $user->dailyStats()
            ->select(DB::raw("DATE_FORMAT(date, '%Y-%b') as month"), DB::raw('sum(bets) as bets'))
            ->where('date', '>=', Carbon::now('Europe/Malta')->subMonths($months)->startOfMonth())
            ->groupBy('month')
            ->orderBy('date', 'ASC')
            ->pluck('bets', 'month')->toArray();

        if ($this->php_data) {
            return $bets;
        }
        return $this->toFlotGraph($bets, true);
    }

    public function getWinsByMonth(User $user, $months = 3)
    {
        $wins = $user->dailyStats()
            ->select(DB::raw("DATE_FORMAT(date, '%Y-%b') as month"), DB::raw('sum(wins) as wins'))
            ->where('date', '>=', Carbon::now('Europe/Malta')->subMonths($months)->startOfMonth())
            ->groupBy('month')
            ->orderBy('date', 'ASC')
            ->pluck('wins', 'month')->toArray();

        return $this->toFlotGraph($wins, true);
    }

    public function getDepositsByMonth(User $user, $months = 3)
    {
        $deposits = $user->dailyStats()
            ->select(DB::raw("DATE_FORMAT(date, '%Y-%b') as month"), DB::raw('sum(deposits) as deposits'))
            ->where('date', '>=', Carbon::now('Europe/Malta')->subMonths($months)->startOfMonth())
            ->groupBy('month')
            ->orderBy('date', 'ASC')
            ->pluck('deposits', 'month')->toArray();

        return $this->toFlotGraph($deposits, true);
    }

    public function getWithdrawalsByMonth(User $user, $months = 3)
    {
        $deposits = $user->dailyStats()
            ->select(DB::raw("DATE_FORMAT(date, '%Y-%b') as month"), DB::raw('sum(withdrawals) as withdrawals'))
            ->where('date', '>=', Carbon::now('Europe/Malta')->subMonths($months)->startOfMonth())
            ->groupBy('month')
            ->orderBy('date', 'ASC')
            ->pluck('withdrawals', 'month')->toArray();

        return $this->toFlotGraph($deposits, true);
    }

    public function getRewardsByMonth(User $user, $months = 3)
    {
        $user_id = $user->getKey();
        $start_time = Carbon::now('Europe/Malta')
            ->subMonths($months)
            ->startOfMonth();

        $rewards = "
            SELECT
                SUM(ABS(ct.amount)) AS all_sum,
                DATE_FORMAT(ct.timestamp, '%Y-%b') AS month
            FROM cash_transactions ct
            WHERE ct.user_id = {$user_id}
            AND ct.timestamp >= '{$start_time}'
            AND transactiontype IN (
                14,32,31,51,66,69,74,77,80,82,84,85,86
            )
            GROUP BY DATE_FORMAT(ct.timestamp, '%Y-%b')
            ORDER BY ct.timestamp ASC";

        $failed = "
            SELECT
                SUM(ABS(ct.amount)) AS all_sum,
                DATE_FORMAT(ct.timestamp, '%Y-%b') AS month
            FROM cash_transactions ct
            LEFT JOIN bonus_types bt ON bt.id = ct.bonus_id
            WHERE ct.user_id = {$user_id}
            AND  ct.timestamp >= '{$start_time}'
            AND (
                ct.transactiontype IN (
                    53,67,72,75,78,81
                ) OR (
                    ct.transactiontype = 15 AND (
                        bt.bonus_type != 'freespin' OR
                        bt.bonus_type != 'casinowager' OR
                        bt.bonus_type IS NULL
                    )
                )
            )
            GROUP BY DATE_FORMAT(ct.timestamp, '%Y-%b')
            ORDER BY ct.timestamp ASC";

        $rewards = collect(ReplicaDB::shSelect($user_id, 'users', $rewards))
            ->reduce(function ($carry, $el) {
                $carry[$el->month] = $el->all_sum;
                return $carry;
            }, []);

        $failed = collect(ReplicaDB::shSelect($user_id, 'users', $failed))
            ->reduce(function ($carry, $el) {
                $carry[$el->month] = $el->all_sum;
                return $carry;
            }, []);

        foreach ($rewards as $month => $amount) {
            $rewards[$month] -= (int)$failed[$month];
        }

        return $this->toFlotGraph($rewards, true);
    }

    public function getSiteProfitByMonth(User $user, $months = 3)
    {
        $site_prof = $user->dailyStats()
            ->select(DB::raw("DATE_FORMAT(date, '%Y-%b') as month"), DB::raw('sum(site_prof) as site_prof'))
            ->where('date', '>=', Carbon::now('Europe/Malta')->subMonths($months)->startOfMonth())
            ->groupBy('month')
            ->orderBy('date', 'ASC')
            ->pluck('site_prof', 'month')->toArray();

        return $this->toFlotGraph($site_prof, true);
    }

    public function getCashBacksByMonth(User $user, $months = 3)
    {
        $site_prof = $user->dailyStats()
            ->select(DB::raw("DATE_FORMAT(date, '%Y-%b') as month"), DB::raw('sum(paid_loyalty) as paid_loyalty'))
            ->where('date', '>=', Carbon::now('Europe/Malta')->subMonths($months)->startOfMonth())
            ->groupBy('month')
            ->orderBy('date', 'ASC')
            ->pluck('paid_loyalty', 'month')->toArray();

        return $this->toFlotGraph($site_prof, true);
    }

    public function getNgrByDays(User $user, $days = 31){
        $start_date = Carbon::now('Europe/Malta')->subDays($days);

        return $this->getNgrByDate($user, $start_date);
    }

    public function getNgrByMonth(User $user, $months = 3)
    {
        $start_date = Carbon::now('Europe/Malta')
            ->subMonths($months)
            ->startOfMonth();

        return $this->getNgrByDate($user, $start_date);
    }


    public function getNgrByDate(User $user, $start_date){
        $user_id = $user->getKey();

        $q = "
            SELECT
                'gross' as type,
                SUM(uds.gross) AS all_sum,
                DATE_FORMAT(uds.date, '%Y-%m') AS month
                FROM users_daily_stats uds
                WHERE uds.user_id = {$user_id}
                AND uds.date >= DATE('{$start_date}')
                GROUP BY DATE_FORMAT(uds.date, '%Y-%m')
            UNION ALL
                SELECT
                'gross_sportsbook' as type,
                IFNULL(SUM(bets),0) - IFNULL(SUM(wins),0) - IFNULL(SUM(void),0) AS all_sum,
                DATE_FORMAT(udss.date, '%Y-%m') AS month
                FROM users_daily_stats_sports udss
                WHERE udss.user_id = {$user_id}
                AND udss.date >= DATE('{$start_date}')
                GROUP BY DATE_FORMAT(udss.date, '%Y-%m')
            UNION ALL
            SELECT
                'rewards' as type,
                SUM(ABS(ct.amount)) AS all_sum,
                DATE_FORMAT(ct.timestamp, '%Y-%m') AS month
                FROM cash_transactions ct
                WHERE ct.user_id = {$user_id}
                AND ct.timestamp >= DATE('{$start_date}')
                AND transactiontype IN (
                    14,32,31,51,66,69,74,77,80,82,84,85,86
                )
                GROUP BY DATE_FORMAT(ct.timestamp, '%Y-%m')
            UNION ALL
            SELECT
                'failed_rewards' as type,
                SUM(ABS(ct.amount)) AS all_sum,
                DATE_FORMAT(ct.timestamp, '%Y-%m') AS month
                FROM cash_transactions ct
                LEFT JOIN bonus_types bt ON bt.id = ct.bonus_id
                WHERE ct.user_id = {$user_id}
                AND  ct.timestamp >= DATE('{$start_date}')
                AND (
                    ct.transactiontype IN (
                        53,67,72,75,78,81
                    ) OR (
                        ct.transactiontype = 15 AND (
                            bt.bonus_type != 'freespin' OR
                            bt.bonus_type != 'casinowager' OR
                            bt.bonus_type IS NULL
                        )
                    )
                )
                GROUP BY DATE_FORMAT(ct.timestamp, '%Y-%m')
        ";

        $result = collect(ReplicaDB::shSelect($user->getKey(), 'users', $q))
            ->groupBy('month')
            ->map(function ($el) {
                /** @var Collection $el */
                $el = $el->groupBy('type');

                return  (int) $el['gross'][0]->all_sum
                    +   (int) $el['gross_sportsbook'][0]->all_sum
                    -   (int) $el['rewards'][0]->all_sum
                    +   (int) $el['failed_rewards'][0]->all_sum;
            })
            ->mapWithKeys(function($value, $key) {
                $key = Carbon::parse("{$key}")->formatLocalized('%Y-%b');
                return [$key => $value];
            })
            ->all();

        if ($this->php_data) {
            return $result;
        }

        return $this->toFlotGraph($result, true);
    }




    private function toFlotGraph($data, $nfCents = false)
    {
        $ret = "";
        foreach ($data as $key => $value) {
            if ($nfCents) {
                $value = floor($value / 100);
            }
            $ret .= "['$key', $value],";
        }
        return rtrim($ret, ",");
    }

    /**
     * Fetch the total deposit of the customer for the last X days in EUR
     *
     * @param Application $app
     * @param User $user
     * @param int $days
     *
     * @return float|int
     */
    public function getTotalDepositLastXDays(Application $app, User $user, int $days = 30)
    {
        $total = DB::shSelect($user->id, 'users_daily_stats', "
            SELECT sum(deposits) AS total FROM users_daily_stats
            WHERE user_id = :user_id
            AND date between :start_date and :end_date
        ", [
                'user_id' => $user->id,
                'start_date' => Carbon::now()->subDays($days)->toDateString(),
                'end_date' => Carbon::now()->toDateString()
            ])[0]->total ?? 0;

        $total = DataFormatHelper::convertToEuro($user->currency, $total, $app, true);

        return $total;
    }
}
