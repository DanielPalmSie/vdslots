<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 16/03/16
 * Time: 16:48
 */

namespace App\Repositories;

use App\Classes\PR;
use App\Extensions\Database\Connection\Connection;
use App\Helpers\Common;
use App\Helpers\DataFormatHelper;
use App\Helpers\DownloadHelper;
use App\Models\CashTransaction;
use App\Models\Tournament;
use App\Models\User;
use App\Models\UserChangeStats;
use App\Models\UserMonthlyLiability;
use Carbon\Carbon;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\ReplicaFManager as ReplicaDB;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\Schema\Blueprint;
use Silex\Application;
use App\Exceptions\LiabilitiesProcessedException;
use App\Exceptions\UpdateLiabilitiesException;

class LiabilityRepository
{
    protected $year;

    protected $month;

    protected $day;

    protected $user_id;

    /** @var  Connection|\Illuminate\Database\Connection $connection */
    protected $connection = null;

    public $source;

    const CREDIT = 'credit';
    const DEBIT = 'debit';

    const CAT_DEPOSIT = 1;
    const CAT_WITHDRAWAL = 2;
    const CAT_PENDING_WITHDRAWAL = 3;
    const CAT_BETS = 4;
    const CAT_WINS = 5;
    const CAT_BOS_BUYIN_34 = 6;
    const CAT_BOS_PRIZES_38 = 7;
    const CAT_BOS_HOUSE_RAKE_52 = 8;
    const CAT_BOS_REBUY_54 = 9;
    const CAT_BOS_CANCEL_BUYIN_61 = 10;
    const CAT_BOS_CANCEL_HOUSE_FEE_63 = 11;
    const CAT_BOS_CANCEL_REBUY_64 = 12;
    const CAT_BOS_CANCEL_PAYBACK_65 = 13;
    const CAT_REWARDS = 14;
    const CAT_MANUAL = 15;
    const CAT_AFF_PAYOUTS = 16;
    const CAT_REFUND_13 = 17;
    const CAT_INACTIVITY_43 = 18;
    const CAT_WITHDRAWAL_DEDUCTION_50 = 19;
    const CAT_TEST_CASH_42 = 20;
    const CAT_ZEROING_OUT_BAL_60 = 21;
    const CAT_JACKPOT_WIN_12 = 22;
    const CAT_BUDDY_TRANSFER_29 = 23;
    const CAT_BET_REFUND_7 = 24;
    const CAT_WIN_ROLLBACK_7 = 32;
    const CAT_CHARGEBACK_9 = 25;
    const CAT_FRB_WINS = 26;
    const CAT_JP_WINS = 27;
    const CAT_LIABILITY_ADJUST = 28;
    const CAT_BOOSTER_VAULT_TRANSFER = 29;
    const CAT_TRANSFER_FROM_BOOSTER_VAULT = 30;
    const CAT_TRANSFER_TO_BOOSTER_VAULT = 31;
    const CAT_TURNOVER_TAX_WAGER = 32;
    const CAT_TURNOVER_TAX_WAGER_REFUND = 33;

    const CAT_CHARGEBACK_SETTLEMENT = 92;
    const CAT_SPORTSBOOK_VOIDS = 30;
    const CAT_TAX_DEDUCTION = 31;
    const CAT_POOLX_VOIDS = 32;
    const CAT_SPORTS_AGENT_FEE = 33;
    const CAT_SPORTS_AGENT_FEE_REFUND = 34;

    const PERMISSION_LIABILITY_ADJUST = "admin.liability-adjust";

    const MISC_CACHE_LIABILITY_REPORT_ADJUST_MONTH = 'liability-report-adjusted-month';

    const SOURCE_DISPLAY_NAME = 'vs';

    //constructor
    public function __construct($year = null, $month = null, $source = null, $user_id = null, $day = null)
    {
        $this->year = is_null($year) ? null : $year;
        $this->month = is_null($month) ? null : $month;
        $this->day = is_null($day) ? null : $day;
        $this->user_id = is_null($user_id) ? null : $user_id;
        $this->setSource($source);
    }

    /**
     * @param Connection|\Illuminate\Database\Connection $connection
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    protected function connection()
    {
        return empty($this->connection) ? DB::connection() : $this->connection;
    }

    private function setSource($source = 'all')
    {
        if ($this->year <= 2016 && $this->month < 11) {
            $this->source = false;
        } elseif (is_numeric($source)) {
            $this->source = $source;
        } else {
            $source_map = [
                'vs' => 0,
                'pr' => 1
            ];

            if (isset($source_map[$source])) {
                $this->source = $source_map[$source];
            } else {
                $this->source = false;
            }
        }
    }

    /**
     * @return Carbon
     */
    public function getDate()
    {
        return Carbon::create($this->year, $this->month);
    }

    public function getMonth()
    {
        return $this->month;
    }

    public function getYear()
    {
        return $this->year;
    }

    private function generateDates($date_only = false, $carbon_date = false)
    {
        if (empty($this->year) || empty($this->month)) {
            throw new \Exception("Year or month not set.", 501);
        }

        $start = Carbon::create($this->year, $this->month)->startOfMonth();
        $end = !empty($this->day) ?
            Carbon::create($this->year, $this->month, $this->day)->startOfDay() :
            Carbon::create($this->year, $this->month)->endOfMonth();

        if ($carbon_date) {
            return [$start, $end];
        }

        $date['start'] = $date_only ? $start->format('Y-m-d') : $start->format('Y-m-d H:i:s');
        $date['end'] = $date_only ? $end->format('Y-m-d') : $end->format('Y-m-d H:i:s');

        return $date;
    }

    /**
     * @param User $user
     * @param Carbon|Carbon[] $date If not array that gets the day
     * @param bool $filter
     * @return mixed
     */
    public static function getUserTransactionListQueries(User $user, $date, $filter = false)
    {
        if (is_array($date)) {
            $start_date = $date['start']->startOfDay();
            $end_date = $date['end']->endOfDay();
        } else {
            $start_date = $date->copy()->startOfDay();
            $end_date = $date->copy()->endOfDay();
        }

        $bets = ReplicaDB::shTable($user->getKey(), 'bets as b')->selectRaw("b.id, 'bet' as type, 1 as weight, b.created_at as date, (b.amount * -1) as amount, b.balance, CASE WHEN b.bonus_bet = 1 THEN 'Bonus' END AS description, game_ref as more_info")
            ->where('b.user_id', $user->getKey())->whereBetween('b.created_at', [$start_date, $end_date]);

        $wins = ReplicaDB::shTable($user->getKey(), 'wins as w')->selectRaw("w.id,'win' as type, 2 as weight, w.created_at as date, w.amount, w.balance, CASE WHEN w.bonus_bet = 1 THEN 'Bonus' WHEN w.bonus_bet = 3 THEN 'FRB Win' END AS description, game_ref as more_info")
            ->where('w.user_id', $user->getKey())->whereBetween('w.created_at', [$start_date, $end_date]);

        $cash = ReplicaDB::shTable($user->getKey(), 'cash_transactions as ct')->selectRaw("ct.id, ct.transactiontype as type, 4 as weight, ct.timestamp as date, ct.amount, ct.balance as balance, ct.description AS description, '' as more_info")
            ->where('ct.user_id', $user->getKey())->whereBetween('ct.timestamp', [$start_date, $end_date]);
        
        $sports = ReplicaDB::shTable($user->getKey(), 'sport_transactions as st')->selectRaw("
                st.id,
                st.bet_type                        AS type,
                3                                  AS weight,
                st.created_at                      AS date,
                CASE WHEN st.bet_type = 'bet' THEN st.amount * -1 ELSE st.amount END,
                st.balance                         AS balance,
                CASE
                    WHEN st.product = 'S' THEN 'sportsbet'
                    WHEN st.product = 'P' THEN 'poolbet'
                    ELSE 'other'
                END                                AS description,
                st.network COLLATE utf8_general_ci AS more_info
            ")
            ->where('st.user_id', $user->getKey())->whereBetween('st.created_at', [$start_date, $end_date]);

        if ($filter == true) {
            $cash->whereRaw("(transactiontype IN (1, 2, 3, 7, 8, 9, 12, 13, 29, 42, 43, 50, 60, 34, 38, 52, 54, 61, 63, 64, 65, 31, 32, 66, 67, 69, 72, 73, 76, 77, 78, 79, 80, 81, 84, 85, 86, 90, 91, 100, 101, 103, 104, 105, 106, 107)
                        OR (transactiontype = 14 AND description NOT LIKE '%-aid-%')
                        OR (transactiontype IN (15, 53) AND description NOT LIKE '%-cancelled%' AND description NOT LIKE '%Super blocked so did not payout%')
                        )");
        }

        return compact('bets', 'wins', 'cash', 'sports');
    }

    /**
     * @param Application $app
     * @param string $name
     * @param array $queries
     * @param bool $calculate_balances
     * @param int $opening_balance
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public static function exportUserTransactionList(
        Application $app, string $name, array $queries, bool $calculate_balances = false, int $opening_balance = 0
    )
    {
        $headers = [
            'Date',
            'Type',
            'Transaction ID',
            'Amount (cents)',
            'Balance (cents)',
            'Description',
            'More info'
        ];

        if ($calculate_balances) {
            array_splice( $headers, 5, 0, 'Calculated');
            array_splice( $headers, 6, 0, 'Difference');
        }

        $records[] = $headers;

        $records[] = [
            '',
            'Opening Balance',
            '',
            '0',
            $opening_balance,
            '',
            ''
        ];

        $data = $queries['bets']
            ->union($queries['wins'])
            ->union($queries['cash'])
            ->union($queries['sports'])
            ->orderBy('date', 'asc')
            ->get();

        $data = $calculate_balances ? LiabilityRepository::calculateRunningBalance($data, $opening_balance) : $data;

        foreach ($data as $row) {
            $type = is_numeric($row->type) ?
                DataFormatHelper::getCashTransactionsTypeName($row->type) : ucwords($row->type);
            $record = [
                $row->date,
                $type,
                $row->id,
                $row->amount,
                $row->balance,
                $row->description,
                $row->more_info,
            ];

            if ($calculate_balances) {
                array_splice( $record, 5, 0, $row->running_balance);
                array_splice( $record, 6, 0, $row->difference);
            }

            $records[] = $record;
        }
        unset($data);
        return DownloadHelper::streamAsCsv($app, $records, $name);
    }

    public function getCurrentMonth(User $user)
    {
        $start_date = Carbon::create($this->year, $this->month, 1)->startOfMonth()->startOfDay();
        $data = [];
        $net_liability = 0;

        //todo do something when uds while uds is recalculate
        $last_stats_date = Carbon::parse(ReplicaDB::table('users_daily_stats','default',true)->selectRaw('MAX(date) as m')->first()->m)->addDay()->toDateTimeString();

        $bets_and_wins = ReplicaDB::shSelect($user->getKey(), 'users_daily_stats', "SELECT
              (IFNULL(sum(bets),0) + (SELECT IFNULL(sum(b.amount),0) FROM bets AS b WHERE b.user_id = :b_user AND b.created_at >= '{$last_stats_date}')) * -1 AS bets,
              IFNULL(sum(wins),0) + (SELECT IFNULL(sum(w.amount),0) FROM wins AS w WHERE w.user_id = :w_user AND w.bonus_bet != 3 AND w.award_type != 4 AND w.created_at >= '{$last_stats_date}') AS wins,
              IFNULL(sum(frb_wins),0) + (SELECT IFNULL(sum(fw.amount),0) FROM wins AS fw WHERE fw.user_id = :fw_user AND fw.bonus_bet = 3 AND fw.award_type != 4 AND fw.created_at >= '{$last_stats_date}') AS frb_wins
            FROM users_daily_stats AS uds
            WHERE uds.date >= :from_date AND uds.user_id = :user_id", [
            'from_date' => $start_date->toDateString(),
            'user_id' => $user->getKey(),
            'b_user' => $user->getKey(),
            'w_user' => $user->getKey(),
            'fw_user' => $user->getKey()
        ])[0];

        if (!empty($bets_and_wins->bets)) {
            $data[self::CAT_BETS] = $bets_and_wins->bets;
            $net_liability += $bets_and_wins->bets;
        }
        if (!empty($bets_and_wins->wins)) {
            $data[self::CAT_WINS] = $bets_and_wins->wins;
            $net_liability += $bets_and_wins->wins;
        }
        if (!empty($bets_and_wins->frb_wins)) {
            $data[self::CAT_FRB_WINS] = $bets_and_wins->frb_wins;
            $net_liability += $bets_and_wins->frb_wins;
        }

        $jp_wins = ReplicaDB::shTable($user->getKey(), 'wins as w')->selectRaw('IFNULL(sum(w.amount),0) AS sum')
            ->where('w.user_id', $user->getKey())->where('w.award_type', 4)
            ->where('w.created_at', '>=', $start_date->toDateTimeString())->first();

        if (!empty($jp_wins->sum)) {
            $data[self::CAT_JP_WINS] = $jp_wins->sum;
            $net_liability += $jp_wins->sum;
        }

//        $query = DB::table('cash_transactions as ct',$nodeName, true)
        $query = ReplicaDB::shTable($user->getKey(), 'cash_transactions as ct')
            ->leftJoin('ip_log', function (JoinClause $join) {
                $join->on('ip_log.tr_id', '=', 'ct.id')->where('ip_log.tag', '=', 'cash_transactions');
            })
            ->where('ct.user_id', $user->getKey())
            ->where('ct.timestamp', '>=', $start_date->toDateString());

        $manual_query = clone $query;
        $manual = $manual_query->selectRaw('SUM(CASE WHEN ct.amount<0 THEN ct.amount ELSE 0 END) as negative, SUM(CASE WHEN ct.amount>=0 THEN ct.amount ELSE 0 END) as positive')
            ->whereRaw('ip_log.id IS NOT NULL')
            ->whereRaw("(ct.transactiontype IN (1, 2, 3, 7, 8, 31, 32, 66, 67, 69, 72, 73, 76, 77, 78, 79, 80, 81, 84, 85, 86, 90, 105, 104, 103, 100, 101)
                        OR (ct.transactiontype = 14 AND ct.description NOT LIKE '%-aid-%')
                        OR (ct.transactiontype IN (15, 53) AND ct.description NOT LIKE '%-cancelled%' AND ct.description NOT LIKE '%Super blocked so did not payout%'))")
            ->first();

        if (!empty($manual->positive) || !empty($manual->negative)) {
            $data[self::CAT_MANUAL] = ['in' => $manual->positive, 'out' => $manual->negative];
            $net_liability += $manual->positive + $manual->negative;
        }

        $cash_query = clone $query;
        $cash = $cash_query->whereIn('ct.transactiontype', array_keys(self::transactionTypeMap()))->groupBy('ct.transactiontype')
            ->selectRaw('ct.transactiontype, SUM(ct.amount) as sum')->whereRaw('ip_log.id IS NULL')->get();

        if (!empty($cash)) {
            foreach ($cash as $elem) {
                $data[self::transactionTypeMap($elem->transactiontype)] = $elem->sum;
                $net_liability += $elem->sum;
            }
        }

        $rewards_query = clone $query;
        $rewards = $rewards_query->selectRaw('SUM(CASE WHEN ct.amount<0 THEN ct.amount ELSE 0 END) as negative, SUM(CASE WHEN ct.amount>=0 THEN ct.amount ELSE 0 END) as positive')
            ->whereRaw("(ct.transactiontype IN (7, 31, 32, 66, 67, 69, 72, 73, 76, 77, 78, 79, 80, 81, 84, 85, 86, 90)
                        OR (ct.transactiontype = 14 AND ct.description NOT LIKE '%-aid-%')
                        OR (ct.transactiontype IN (15, 53) AND ct.description NOT LIKE '%-cancelled%' AND ct.description NOT LIKE '%Super blocked so did not payout%'))")
            ->whereRaw('ip_log.id IS NULL')->first();

        if (!empty($rewards)) {
            $data[self::CAT_REWARDS] = ['in' => $rewards->positive, 'out' => $rewards->negative];
            $net_liability += $rewards->positive + $rewards->negative;
        }

        $last_sports_stats_date = ReplicaDB::shSelect($user->getKey(), 'users_daily_stats_sports', 'SELECT MAX(date) as m FROM users_daily_stats_sports;')[0]->m;
        $last_sports_stats_date = Carbon::parse($last_sports_stats_date)->addDay()->toDateTimeString();

        $bets_and_wins_sports = ReplicaDB::shSelect($user->getKey(), 'users_daily_stats_sports', "SELECT
              IFNULL(sum(bets),0) * -1 as bets,
              IFNULL(sum(wins),0) as wins,
              IFNULL(sum(void),0) as void
            FROM users_daily_stats_sports AS udss
            WHERE udss.date >= :from_date AND udss.user_id = :user_id", [
            'from_date' => $start_date->toDateString(),
            'user_id' => $user->getKey(),
        ])[0];

        if (!empty($bets_and_wins_sports->bets)) {
            $data[self::CAT_BETS] = $bets_and_wins_sports->bets;
            $net_liability += $bets_and_wins_sports->bets;
        }
        if (!empty($bets_and_wins_sports->wins)) {
            $data[self::CAT_WINS] = $bets_and_wins_sports->wins;
            $net_liability += $bets_and_wins_sports->wins;
        }
        if (!empty($bets_and_wins_sports->void)) {
            $data[self::CAT_SPORTSBOOK_VOIDS] = $bets_and_wins_sports->void;
            $net_liability += $bets_and_wins_sports->void;
        }

        $sport_transactions = ReplicaDB::shTable($user->getKey(), 'sport_transactions as st')
            ->selectRaw("SUM(CASE WHEN st.bet_type='win' THEN st.amount ELSE 0 END) as wins, SUM(CASE WHEN st.bet_type='bet' THEN st.amount * -1 ELSE 0 END) as bets,  SUM(CASE WHEN st.bet_type='void' THEN st.amount ELSE 0 END) as void")
            ->where('st.user_id', $user->getKey())
            ->where('st.created_at', '>=', $last_sports_stats_date)->first();

        if (!empty($sport_transactions->wins)) {
            $data[self::CAT_WINS] = $sport_transactions->wins;
            $net_liability += $sport_transactions->wins;
        }

        if (!empty($sport_transactions->bets)) {
            $data[self::CAT_BETS] = $sport_transactions->bets;
            $net_liability += $sport_transactions->bets;
        }

        if (!empty($sport_transactions->void)) {
            $data[self::CAT_SPORTSBOOK_VOIDS] = $sport_transactions->void;
            $net_liability += $sport_transactions->void;
        }
        return compact('data', 'net_liability');
    }

    /**
     * @param User $user
     * @param int $threshold
     * @param Carbon[]|Carbon|null $date If is not an array will be a whole month
     * @param bool $ret_all
     * @throws \Exception
     * @return array
     */
    public function getUnallocatedAmount(User $user, $threshold = 10, $date = null, $ret_all = false)
    {
        if (is_array($date)) {
            $start_date = $date['start'];
            $end_date = $date['end'];
        } elseif (!is_null($date) && ($date instanceof Carbon)) {
            $start_date = $date->startOfMonth();
            $end_date = $date->endOfMonth();
        } elseif (empty($date) && !empty($this->year) && !empty($this->month)) {
            $start_date = Carbon::create($this->year, $this->month)->startOfMonth();
            $end_date = Carbon::create($this->year, $this->month)->endOfMonth();
        } else {
            throw new \Exception("Date not valid or not present.");
        }

        $last_stats_date = ReplicaDB::shTable($user->getKey(), 'users_daily_stats')->selectRaw('MAX(date) as m')->first()->m;
        $last_stats_date = Carbon::parse($last_stats_date);

        $res = [];
        while ($start_date->lte($end_date) && !$start_date->isFuture()) {
            $cur_date = $start_date->copy();

            $opening_bal = $user->repo->getBalance($cur_date->copy());
            $closing_bal = $user->repo->getBalance($cur_date->copy()->addDay());

            if ($cur_date <= $last_stats_date) {
                $gross = ReplicaDB::shTable($user->getKey(), 'users_daily_stats')->selectRaw('IFNULL((sum(bets)*-1) + sum(wins) + sum(frb_wins),0) as sum')
                    ->where(['user_id' => $user->getKey(), 'date' => $cur_date->toDateString()])->first()->sum;
                $jp_wins = ReplicaDB::shTable($user->getKey(), 'wins')->selectRaw('IFNULL(sum(amount),0) as sum')
                    ->where(['user_id' => $user->getKey(), 'award_type' => 4])
                    ->whereBetween('created_at', [
                        $start_date->copy()->startOfDay()->toDateTimeString(),
                        $start_date->copy()->endOfDay()->toDateTimeString()
                    ])->first()->sum;
                $gross = $gross + $jp_wins;

                $gross_sports = ReplicaDB::shTable($user->getKey(), 'users_daily_stats_sports')->selectRaw('IFNULL((sum(bets)*-1) + sum(wins) + sum(void),0) as sum')
                    ->where(['user_id' => $user->getKey(), 'date' => $cur_date->toDateString()])->first()->sum;

                $gross += $gross_sports;
            } else {
                $bets = $user->repo->getBetsWinsSum($cur_date->copy(), 'bets');
                $wins = $user->repo->getBetsWinsSum($cur_date->copy(), 'wins');
                $gross = $wins - $bets;

                $sport_transactions = ReplicaDB::shTable($user->getKey(), 'sport_transactions as st')
                    ->selectRaw("SUM(CASE WHEN st.bet_type='win' THEN st.amount ELSE 0 END) as wins, SUM(CASE WHEN st.bet_type='bet' THEN st.amount * -1 ELSE 0 END) as bets, SUM(CASE WHEN st.bet_type='void' THEN st.amount ELSE 0 END) as void")
                    ->where('st.user_id', $user->getKey())
                    ->where('st.created_at', '>=', $cur_date->copy())->first();

                foreach (['wins', 'bets', 'void'] as $type) {
                    if (!empty($sport_transactions->{$type})) {
                        $gross += $sport_transactions->{$type};
                    }
                }
            }

            $deposits = $user->repo->getDepositsSum($cur_date->copy(), false);
            $withdrawals = $user->repo->getWithdrawalsSum($cur_date->copy(), false);

            $cash_raw = "(transactiontype IN (105, 104, 103, 100, 101, 7, 9, 12, 13, 29, 42, 43, 50, 60, 34, 38, 52, 54, 61, 63, 64, 65, 31, 32, 66, 67, 69, 72, 73, 76, 77, 78, 79, 80, 81, 84, 85, 86, 90, 91)
                        OR (transactiontype = 14 AND description NOT LIKE '%-aid-%')
                        OR (transactiontype IN (15, 53) AND description NOT LIKE '%-cancelled%' AND description NOT LIKE '%Super blocked so did not payout%')
                        )";

            $cash = $user->repo->getCashTransactionsSum($cur_date->copy(), false, $cash_raw);

            $n_rollbacks = ReplicaDB::shTable($user->id, 'cash_transactions')->selectRaw("count(*) as c")
                ->where('user_id', $user->id)->where('transactiontype', 7)
                ->whereBetween('timestamp', [$cur_date->copy()->startOfDay()->toDateTimeString(), $cur_date->copy()->endOfDay()->toDateTimeString()])
                ->first()->c;

            $net_liability = $gross + $deposits + $withdrawals + $cash;

            $diff_cents = $net_liability + $opening_bal - $closing_bal;

            if ($diff_cents > $threshold || $diff_cents < ($threshold * -1) || $ret_all === true) {
                $res[$cur_date->toDateString()] = [
                    'date' => $cur_date->toDateString(),
                    'opening' => intval($opening_bal),
                    'liability' => intval($net_liability),
                    'closing' => intval($closing_bal),
                    'unallocated' => $diff_cents,
                    'rollbacks' => $n_rollbacks
                ];
            }

            $start_date->addDay();
        }

        return $res;
    }


    public function getDifferencesPerCurrency(array $query_data)
    {
        $res = ReplicaDB::shsSelect('users_monthly_liability', "SELECT user_id,
                                u.username,
                                u.currency,
                                sum(liab_amount)  AS net_liab,
                                sum(open_amount)  AS opening,
                                sum(close_amount) AS closing,
                                sum(open_amount) + sum(liab_amount) - sum(close_amount) AS diff,
                                abs(sum(open_amount) + sum(liab_amount) - sum(close_amount)) AS abs_diff
                    FROM (
                           SELECT
                             user_id,
                             sum(amount) AS liab_amount,
                             0           AS open_amount,
                             0           AS close_amount
                           FROM users_monthly_liability
                           WHERE year = :l_year AND month = :l_month AND users_monthly_liability.currency = :cur_1 AND source = 0
                           GROUP BY user_id
                           UNION
                           SELECT
                             user_id,
                             0                            AS liab_amount,
                             cash_balance + bonus_balance AS open_amount,
                             0                            AS close_amount
                           FROM users_daily_balance_stats
                           WHERE date = :opening_date AND currency = :cur_2 AND source = 0
                           GROUP BY user_id
                           UNION
                           SELECT
                             user_id,
                             0                            AS liab_amount,
                             0                            AS open_amount,
                             cash_balance + bonus_balance AS close_amount
                           FROM users_daily_balance_stats
                           WHERE date = :closing_date AND currency = :cur_3 AND source = 0
                           GROUP BY user_id) AS sub
                      LEFT JOIN users AS u ON u.id = sub.user_id
                    GROUP BY user_id
                    HAVING diff <> 0
                    ORDER BY abs_diff DESC", [
            'l_year' => $this->year,
            'l_month' => $this->month,
            'opening_date' => Carbon::create($this->year, $this->month, 1)->toDateString(),
            'closing_date' => Carbon::create($this->year, $this->month, 1)->addMonth()->startOfMonth()->toDateString(),
            'cur_1' => $query_data['currency'],
            'cur_2' => $query_data['currency'],
            'cur_3' => $query_data['currency'],
        ]);

        return $res;
    }

    public function exportDifferencesPerCurrency(Application $app, $data, $name)
    {
        $records[] = ['User ID', 'Username', 'Currency', 'Opening Balance', 'Net Liability', 'Closing Balance', 'Unallocated Amount'];

        foreach ($data as $row) {
            $records[] = [
                $row->user_id,
                $row->username,
                $row->currency,
                DataFormatHelper::nf($row->opening),
                DataFormatHelper::nf($row->net_liab),
                DataFormatHelper::nf($row->closing),
                DataFormatHelper::nf($row->diff)
            ];
        }
        return DownloadHelper::streamAsCsv($app, $records, $name);
    }

    /**
     * @param $currency
     * @param string $country
     * @param array|string $group_by
     * @param mixed $user_id
     * @param bool $previous_month
     * @return mixed
     */
    public function getMonthlyData($currency = null, $country = 'all', $province = 'all', $user_id = null, $group_by = null, $previous_month = false)
    {
        if (!$previous_month) {
            $month = $this->month;
            $year = $this->year;
        } else {
            $new_date = Carbon::create($this->year, $this->month);
            $pre_month = $new_date->subMonth();
            $month = $pre_month->month;
            $year = $pre_month->year;
        }

        if (!empty($user_id)) {
            /** @var Builder $query */
            $query = ReplicaDB::shTable($user_id, 'users_monthly_liability')->where('user_id', $user_id);
        } else {
            /** @var Builder $query */
            $query = ReplicaDB::table('users_monthly_liability', replicaDatabaseSwitcher(true), true)->where('users_monthly_liability.currency', $currency);
        }

        $query->where('month', $month)
            ->where('year', $year)
            ->orderBy('main_cat');

        $add_select = 'year, month, type, source, main_cat, sub_cat, sum(transactions) as transactions, sum(amount) as amount, users_monthly_liability.currency';


        if ($this->source !== false) {
            $query->where('source', $this->source);
        }

        if (empty($group_by)) {
            $query->groupBy(['main_cat', 'type']);
        } else {
            $query->groupBy($group_by);
        }

        if (!empty($country) && $country != 'all') {
            $query->where('country', $country);
            $add_select .= ', country';
        }

        if (!empty($province)) {
            $query->whereIn('province', $province);
            $add_select .= ', province';
        }

        $query->selectRaw($add_select);

        /** @var \Illuminate\Database\Eloquent\Collection $res */
        $res = $query->get();
        return $res;
    }

    public function getMonthlyVaultData($currency = null, $country = 'all', $province = 'all', $yearReport)
    {
        $query = ReplicaDB::table('users_monthly_liability', replicaDatabaseSwitcher(true), true);


        $query->where('year',  $this->year)
            ->whereIn('main_cat', [29])
            ->orderBy('main_cat');

            $add_select = "  currency,
                            SUM(CASE WHEN type = 'debit' THEN  amount ELSE 0 END) as from_vault,
                            SUM(CASE WHEN type = 'credit' THEN  amount ELSE 0 END) as to_vault,
                            SUM(amount) as vault_balance,
                            country";

            if (!$yearReport) {
                $query->where('month', $this->month);

            }
            if ($currency){
            $query->where('users_monthly_liability.currency', $currency);
            }

            if ($this->source !== false) {
            $query->where('source', $this->source);
            }
                 $query->groupBy(['country']) ;


            if (!empty($country) && $country != 'all') {
            $query->where('country', $country);
            $add_select .= ', country';
            }

            if (!empty($province)) {
            $query->whereIn('province', $province);
            $add_select .= ', province';
            }

            $query->selectRaw($add_select);

            /** @var \Illuminate\Database\Eloquent\Collection $res */
                $res = $query->get();
                return $res;
    }
    public function getVaultBalanceData($data)
    {

        $date = $data['year']. '-' . str_pad($data['month'], 2, '0', STR_PAD_LEFT) . '-01';
        $query = UserMonthlyLiability::on(replicaDatabaseSwitcher(true))
            ->selectRaw("SUM(amount) AS total_amount")
            ->whereRaw(" CONCAT(year, '-', LPAD(month, 2, '0'), '-01') < '$date'")
            ->where('main_cat', 29);

        if (!($data['country'] == 'SE' || $data['currency'] == 'SEK' || $data['country'] == 'CA' || $data['currency'] == 'CAD')) {
            $query->whereRaw(" CONCAT(year, '-', LPAD(month, 2, '0'), '-01') > '2024-02-01'"); // added logic change release date(RHEA-538) except SE, SEK, CA, or CAD
        }
        if (!empty($data['currency'])) {
            $query->where('currency', $data['currency']);
        }
        if ($data['country'] != 'all') {
            $query->where('country', $data['country']);
        }
        if ($data['source'] != 'all') {
            $query->where('source', $this->source);
        }

        $result = $query->first();
        return $result->total_amount ?? 0;
    }

    /**
     * @param $currency
     * @param $cat
     * @param string $country
     * @param string $type
     * @param bool $with_format
     * @return array
     */
    public function getMonthlyDataPerCategory($currency, $cat, $country = 'all', $province = 'all', $type = null, $with_format = false)
    {
        /** @var Builder $query */
        $query = UserMonthlyLiability::on(replicaDatabaseSwitcher(true))->where('month', $this->month)
            ->where('year', $this->year)
            ->where('main_cat', $cat)
            ->groupBy('sub_cat')
            ->orderBy('sub_cat');
        $add_select = 'type, main_cat, sub_cat, source, sum(transactions) as transactions, sum(amount) as amount, users_monthly_liability.currency';

        if (!empty($this->user_id)) {
            $query->where('users_monthly_liability.user_id', $this->user_id);
        } else {
            $query->where('users_monthly_liability.currency', $currency);
        }

        if ($this->source !== false) {
            $query->where('source', $this->source);
        }

        if (!empty($type)) {
            $query->where('type', $type);
        }

        if (!empty($country) && $country != 'all') {
            $query->where('country', $country);
            $add_select .= ', country';
        }

        if (!empty($province)) {
            $query->whereIn('province', $province);
            $add_select .= ', province';
        }

        $query->selectRaw($add_select);

        $rewards_map = [
            31 => 'Weekend Booster',
            32 => 'Race winnings',
            14 => 'Other bonuses',
            53 => 'Failed casino bonus winnings',
            15 => 'Failed bonus'
        ];

        $res = [];
        foreach ($query->get() as $e) {
            $e->transactions = empty($e->transactions) ? 'N/A' : $e->transactions;
            if ($with_format) {
                $e->amount = DataFormatHelper::nf(abs($e->amount));
            }
            if ($cat == self::CAT_MANUAL && strlen($e->sub_cat) <= 3) {
                $e->sub_cat = isset($rewards_map[$e->sub_cat]) ? $rewards_map[$e->sub_cat] : DataFormatHelper::getCashTransactionsTypeName($e->sub_cat);
            } elseif ($cat == self::CAT_REWARDS) {
                $e->sub_cat = isset($rewards_map[$e->sub_cat]) ? $rewards_map[$e->sub_cat] : (is_numeric($e->sub_cat) ? DataFormatHelper::getCashTransactionsTypeName($e->sub_cat) : $e->sub_cat);
            }
            $e->sub_cat = ucwords($e->sub_cat);
            $res[] = $e;
        }
        return $res;
    }

    public function exportMonthlyData($data, $opening_data, $closing_data, $app, $year, $month, $extra, $breakdown)
    {
        /*$records[] = [
            'Countries:',
            $country == 'all' ? 'ALL' : BankCountry::select('printable_name')->where('iso', $country)->first()->printable_name
        ];*/
        $trans_type = [14, 13, 15, 17, 21, self::CAT_LIABILITY_ADJUST, self::CAT_BOOSTER_VAULT_TRANSFER, self::CAT_TAX_DEDUCTION];
        foreach ($extra as $k => $v) {
            $v = is_array($v) ? implode(', ', $v) : $v;
            $records[] = [ucwords($k) . ':', $v];
        }
        $records[] = ['From:', Carbon::create($year, $month)->startOfMonth()->format('d-m-Y')];
        $records[] = ['To:', Carbon::create($year, $month)->endOfMonth()->format('d-m-Y')];
        $records[] = [' '];
        $records[] = ['Name', 'Debit', 'Transactions', 'Credit', 'Transactions', 'Net'];
        $records[] = [' '];
        $records[] = ['Opening Player Liability', '', '', '', '', $opening_data['net'] / 100];
        $records[] = [' '];

        foreach ($data as $elem) {
            if ($elem->type == 'credit') {
                $records[] = [
                    $elem->main_cat == in_array($elem->main_cat, $trans_type) ? self::getLiabilityCategoryName($elem->main_cat) . ' (IN)' : self::getLiabilityCategoryName($elem->main_cat),
                    0,
                    0,
                    $elem->amount / 100,
                    $elem->transactions == 0 ? 'N/A' : $elem->transactions,
                    $elem->amount / 100
                ];
            } elseif ($elem->type == 'debit') {
                $amount = ($elem->amount > 0) ? $elem->amount * (-1) : $elem->amount;
                $records[] = [
                    $elem->main_cat == in_array($elem->main_cat, $trans_type) ? self::getLiabilityCategoryName($elem->main_cat) . ' (OUT)' : self::getLiabilityCategoryName($elem->main_cat),
                    abs($elem->amount) / 100,
                    $elem->transactions == 0 ? 'N/A' : $elem->transactions,
                    0,
                    0,
                    $amount / 100
                ];
            }
            if ($breakdown == 1) {
                $type = in_array($elem->main_cat, $trans_type) ? $elem->type : null;
                $childs = $this->getMonthlyDataPerCategory(
                    $extra['currency'],
                    $elem->main_cat,
                    $extra['country'],
                    $extra['province'],
                    $type
                );
                if (count($childs) > 1) {
                    foreach ($childs as $child) {
                        if ($child->type == 'credit') {
                            $records[] = [
                                '  ' . $child->sub_cat,
                                0,
                                0,
                                $child->amount / 100,
                                $child->transactions == 0 ? 'N/A' : $child->transactions,
                                ''
                            ];
                        } elseif ($child->type == 'debit') {
                            $records[] = [
                                '  ' . $child->sub_cat,
                                abs($child->amount) / 100,
                                $child->transactions == 0 ? 'N/A' : $child->transactions,
                                0,
                                0,
                                ''
                            ];
                        }
                    }
                } elseif (count($childs) == 1) {
                    $child = $childs[0];
                    if (!empty($child->sub_cat)) {
                        if ($child->type == 'credit') {
                            $records[] = [
                                '  ' . $child->sub_cat,
                                0,
                                0,
                                $child->amount / 100,
                                $child->transactions == 0 ? 'N/A' : $child->transactions,
                                ''
                            ];
                        } elseif ($child->type == 'debit') {
                            $records[] = [
                                '  ' . $child->sub_cat,
                                abs($child->amount) / 100,
                                $child->transactions == 0 ? 'N/A' : $child->transactions,
                                0,
                                0,
                                ''
                            ];
                        }
                    }
                }
                $records[] = [' '];
            }
        }

        $records[] = [
            'Unallocated amount', '', '', '', '', $closing_data['non_categorized_amount'] / 100
        ];

        $records[] = [' '];

        $records[] = [
            'Total Net Liability', '', '', '', '', ($closing_data['net_liability'] + $closing_data['non_categorized_amount']) / 100
        ];

        $records[] = [
            'Closing Player Liability', '', '', '', '', ($closing_data['net_liability'] + $closing_data['non_categorized_amount'] + $opening_data['net']) / 100
        ];

        $extra = array_map(fn ($element) => is_array($element) ? implode('_', array_values($element)) : $element, $extra);

        return DownloadHelper::streamAsCsv(
            $app,
            $records,
            "player_liability_" . implode('_', $extra) . "_$year-$month"
        );
    }

    private function getSubCatName($sub_cat)
    {
        $rewards_map = [
            31 => 'Weekend Booster',
            32 => 'Race winnings',
            14 => 'Other bonuses',
            53 => 'Failed casino bonus winnings',
            15 => 'Failed bonus'
        ];

        if (is_numeric($sub_cat)) {
            return isset($rewards_map[$sub_cat]) ? $rewards_map[$sub_cat] : DataFormatHelper::getCashTransactionsTypeName($sub_cat);
        } else {
            return $sub_cat;
        }
    }


    /**
     * @param bool $do_manual
     * @param null $user
     * @return array
     * @throws \Exception
     */
    public function getDeposits($do_manual = false, $user = null)
    {
        list($start, $end) = $this->generateDates(false, true);

        if ($do_manual === true) {
            $extra_where = "AND ip_log.id IS NOT NULL";
            $main_cat = self::CAT_MANUAL;
        } else {
            $extra_where = "AND ip_log.id IS NULL";
            $main_cat = self::CAT_DEPOSIT;
        }

        $changes_config = $this->getChangeStatsConfig($user, $start, $end, $join_key = "d.user_id");
        $res = $users = [];
        $current = $end;

        while ($start < $current) {
            list ($start, $current) = $this->explodeInterval($changes_config, $start, $end);

            $list = $this->connection()->select("
                SELECT
                    d.user_id AS user_id, d.dep_type AS sub_cat, count(d.id) AS transactions, sum(d.amount) AS amount, d.currency, ip_log.id AS ip_log_id,
                    {$this->getCountry($changes_config)} as country,
                    {$this->getProvince($changes_config)} as province
                    {$changes_config['left_join_select']}
                FROM deposits d
                    LEFT JOIN ip_log ON ip_log.tag = 'deposits' AND ip_log.tr_id = d.id
                    LEFT JOIN users ON users.id = {$join_key}
                    LEFT JOIN users_settings ON users_settings.user_id = users.id AND users_settings.setting = 'main_province'
                    {$changes_config['left_join_query']}
                WHERE d.timestamp BETWEEN :start_date AND :end_date AND status != 'disapproved' {$extra_where} {$changes_config['user_condition']}
                GROUP BY d.user_id, d.dep_type
            ", ['start_date' => $start->toDateTimeString(), 'end_date' => $current->toDateTimeString()]);

            foreach ($list as $elem) {
                if (!empty($elem['changes'])) {
                    $users[] = $elem['user_id'];
                    continue;
                }
                $res[] = [
                    'year' => $this->year,
                    'month' => $this->month,
                    'user_id' => $elem['user_id'],
                    'type' => $elem['amount'] >= 0 ? self::CREDIT : self::DEBIT,
                    'main_cat' => $main_cat,
                    'sub_cat' => $do_manual === true ? 3 : $elem['sub_cat'],
                    'transactions' => $elem['transactions'],
                    'amount' => $elem['amount'],
                    'currency' => $elem['currency'],
                    'country' => $elem['country'],
                    'province' => $elem['province'],
                ];
            }
            unset($list);
        }

        if (!empty($users = array_unique($users ?? []))) {
            foreach ($users as $user) {
                $res = array_merge($res, $a = $this->getDeposits($do_manual, $user));
            }
        }

        return $res;
    }

    /**
     * This will check mismatches between cash_transactions type 3 and deposits table
     *
     * @param null $user
     * @return array
     * @throws \Exception
     */
    public function getMismatchedDeposits($user = null)
    {
        list($start, $end) = $this->generateDates(false, true);

        $changes_config = $this->getChangeStatsConfig($user, $start, $end, $join_key = "sub.user_id");
        $res = $users = [];
        $current = $end;

        while ($start < $current) {
            list ($start, $current) = $this->explodeInterval($changes_config, $start, $end);

            $list = $this->connection()->select($sql = "
                SELECT
                    sub.user_id, sum(ct_sum), sum(d_sum), sum(ct_sum) - sum(d_sum) AS diff, sub.currency,
                    {$this->getCountry($changes_config)} as country,
                    {$this->getProvince($changes_config)} as province
                    {$changes_config['left_join_select']}
                FROM (
                    SELECT ct.user_id AS user_id, sum(ct.amount) AS ct_sum, 0 AS d_sum, currency
                    FROM cash_transactions AS ct
                    WHERE ct.transactiontype = 3 AND ct.timestamp BETWEEN :start_date AND :end_date
                    GROUP BY user_id
                    UNION
                    SELECT d.user_id AS user_id, 0 AS ct_sum, sum(amount) AS d_sum, currency COLLATE utf8_unicode_ci AS currency
                    FROM deposits AS d
                    WHERE d.timestamp BETWEEN :start_date_d AND :end_date_d AND d.status != 'disapproved'
                    GROUP BY user_id
                ) AS sub
                    LEFT JOIN users ON users.id = {$join_key}
                    LEFT JOIN users_settings ON users_settings.user_id = users.id AND users_settings.setting = 'main_province'
                    {$changes_config['left_join_query']}
                WHERE 1 {$changes_config['user_condition']}
                GROUP BY user_id HAVING diff > 0
            ", [
                'start_date' => $start->toDateTimeString(),
                'end_date' => $current->toDateTimeString(),
                'start_date_d' => $start->toDateTimeString(),
                'end_date_d' => $current->toDateTimeString()
            ]);

            foreach ($list as $elem) {
                if (!empty($elem['changes'])) {
                    $users[] = $elem['user_id'];
                    continue;
                }
                $res[] = [
                    'year' => $this->year,
                    'month' => $this->month,
                    'user_id' => $elem['user_id'],
                    'type' => $elem['diff'] > 0 ? self::CREDIT : self::DEBIT,
                    'main_cat' => self::CAT_DEPOSIT,
                    'sub_cat' => "Mismatch with transactions [User: {$elem['user_id']}]",
                    'transactions' => 1,
                    'amount' => $elem['diff'],
                    'currency' => $elem['currency'],
                    'country' => $elem['country'],
                    'province' => $elem['province'],
                ];
            }
            unset($list);
        }

        if (!empty($users = array_unique($users ?? []))) {
            foreach ($users as $user) {
                $res = array_merge($res, $a = $this->getMismatchedDeposits($user));
            }
        }

        return $res;
    }

    /**
     * @param bool $pending
     * @param null $user
     * @return array
     * @throws \Exception
     */
    public function getWithdrawals($pending = false, $user = null)
    {
        list($start, $end) = $this->generateDates(false, true);

        $status_condition = $pending ? "AND status = 'pending'" : "AND status NOT IN ('pending', 'initiated')";
        $category = $pending ? self::CAT_PENDING_WITHDRAWAL : self::CAT_WITHDRAWAL;

        $res = [];
        $changes_config = $this->getChangeStatsConfig($user, $start, $end, $join_key = "w.user_id");

        $current = $end;
        while ($start < $current) {
            list ($start, $current) = $this->explodeInterval($changes_config, $start, $end);

            $list = $this->connection()->select("
                SELECT
                    w.user_id, 'debit' as type, :y as year, :m as month, :cat as main_cat, w.payment_method as sub_cat, count(w.id) as transactions, sum(w.amount) * -1 AS amount, w.currency,
                    {$this->getCountry($changes_config)} as country,
                    {$this->getProvince($changes_config)} as province
                    {$changes_config['left_join_select']}
                FROM pending_withdrawals w
                    LEFT JOIN users ON users.id = {$join_key}
                    LEFT JOIN users_settings ON users_settings.user_id = users.id AND users_settings.setting = 'main_province'
                    {$changes_config['left_join_query']}
                WHERE w.timestamp BETWEEN :start_date AND :end_date AND amount > 0 AND w.user_id > 0 $status_condition {$changes_config['user_condition']}
                GROUP BY w.user_id, w.payment_method
            ", [
                'y' => $this->year,
                'm' => $this->month,
                'cat' => $category,
                'start_date' => $start->toDateTimeString(),
                'end_date' => $current->toDateTimeString()
            ]);

            foreach ($list as $elem) {
                if (!empty($elem['changes'])) {
                    $users[] = $elem['user_id'];
                    continue;
                }
                unset($elem['changes']);
                $res[] = $elem;
            }
            unset($list);
        }

        if (!empty($users = array_unique($users ?? []))) {
            foreach ($users as $user) {
                $res = array_merge($res, $a = $this->getWithdrawals($pending, $user));
            }
        }

        return $res;
    }

    /**
     * @param PR $pr_rpc
     * @param bool $pending
     * @return array
     */
    public function getPRWithdrawals(PR $pr_rpc, $pending = false)
    {
        $date = $this->generateDates();
        $status_condition = $pending ? "AND status = 'pending'" : "AND status != 'pending'";
        $category = $pending ? self::CAT_PENDING_WITHDRAWAL : self::CAT_WITHDRAWAL;

        return $pr_rpc->execFetch("
            SELECT 1 as source, u.company_id as user_id, 'debit' as type, {$this->year} as year, {$this->month} as month, $category as main_cat, w.payment_method as sub_cat, count(w.id) as transactions, sum(w.amount) * -1 AS amount, w.currency, c.country, us.value as province
            FROM pending_withdrawals w
            LEFT JOIN users u ON u.id = w.user_id
            LEFT JOIN companies c ON c.company_id = u.company_id
            LEFT JOIN users_settings us ON us.user_id = u.id AND us.setting = 'main_province'
            WHERE w.timestamp BETWEEN '{$date['start']}' AND '{$date['end']}' $status_condition AND amount != 0
            GROUP BY w.user_id, w.payment_method");
    }

    /**
     * @param PR $pr_rpc
     * @return array
     */
    public function getPRTransactions(PR $pr_rpc)
    {
        $date = $this->generateDates();
        $type_map = [
            5 => self::CAT_AFF_PAYOUTS,
            20 => self::CAT_AFF_PAYOUTS,
            13 => self::CAT_MANUAL,
            91 => self::CAT_LIABILITY_ADJUST
        ];

        $list = $pr_rpc->execFetch("
            SELECT u.company_id as user_id, transactiontype, ct.currency, sum(amount) as amount, count(ct.transaction_id) as count, c.country, us.value as province
            FROM cash_transactions ct
                LEFT JOIN users u ON u.id = ct.user_id
                LEFT JOIN companies c ON c.company_id = u.company_id
                LEFT JOIN users_settings us ON us.user_id = u.id AND us.setting = 'main_province'
            WHERE ct.transactiontype IN (5, 20, 13, 91)
                AND ct.timestamp BETWEEN '{$date['start']}' AND '{$date['end']}'
                AND ct.amount != 0
            GROUP BY user_id, transactiontype");

        $res = [];
        foreach ($list as $elem) {
            $res[] = [
                'source' => 1, //PR
                'year' => $this->year,
                'month' => $this->month,
                'user_id' => $elem['user_id'],
                'type' => $elem['amount'] > 0 ? self::CREDIT : self::DEBIT,
                'main_cat' => $type_map[$elem['transactiontype']],
                'sub_cat' => $elem['transactiontype'],
                'transactions' => $elem['count'],
                'amount' => $elem['amount'],
                'currency' => $elem['currency'],
                'country' => $elem['country'],
                'province' => $elem['province'],
            ];
        }
        unset($list);
        return $res;
    }


    /**
     * The logic when using this function is as follows:
     *  We first do the main query, left join the user changes and detect the users who changed country.
     *  Each such user will then be processed separately and we'll aggregate the data between changes.
     *
     *  this function will break the moment user changed his country in two parts
     *     resulting in [start, changed_time], [changed_time, end]
     *
     * @param $config
     * @param Carbon $start
     * @param Carbon $end
     * @return array
     */
    private function explodeInterval(&$config, $start, $end) {
        $changes = $config['changed_days'];

        // start from the last temporary interval end
        if (!empty($config['temp_interval_end'])) {
            $start = $config['temp_interval_end'];
            $config['change'] = $config["new_change"];
            $config['temp_interval_end'] = null;
        }

        // we know that user changed his country
        if (!empty($changes)) {

            // prepare to set the time of change as temporary end time
            $temp_interval_end = array_first($changes, function ($change) use ($start, $end) {
                return $start->lessThan($change['created_at']) && $end->greaterThanOrEqualTo($change['created_at']);
            });

            // found a change so we overwrite the current end of interval with the temporary one and remember this action
            if (!empty($temp_interval_end)) {
                $end = $config['temp_interval_end'] = $temp_interval_end['created_at'];
                $config['change'] = $temp_interval_end["old_change"];
                $config["new_change"] = $temp_interval_end["new_change"];
                $config['type'] = $temp_interval_end['type'];
            }
        }

        // make sure that the interval closes as soon as possible to allow custom addDay, etc
        if (empty($config['temp_interval_end'])) {
            $config['temp_interval_end'] = $end;
        }

        // make sure we don't go over the interval end
        if ($end->greaterThanOrEqualTo($config['end_date'])) {
            $end = $config['end_date'];
        }

        return [$start, $end];
    }

    /**
     * @param $data
     * @param string $default
     * @return string
     */
    private function getCountry($data, $default = 'users.country') {
        if (empty($data['change']) || $data['type'] !== 'country') {
            return $default;
        }

        return $data['change'] === 'N/A' ? "''" : "'{$data['change']}'";
    }

    /**
     * @param array $data
     * @return string
     */
    private function getProvince(array $data = []) {
        if (empty($data['change']) || $data['type'] !== 'main_province') {
            return "IF ({$this->getCountry($data)} = 'CA', users_settings.value, NULL)";
        }

        return $data['change'] === 'N/A' ? 'NULL' :"'{$data['change']}'";
    }

    /**
     * Returns the query to get the entries from the users_changes_stats table
     * by the given user id, start date, and end date (optional).
     *
     * @param $user
     * @param $start
     * @param $end
     * @return string
     */
    private function getChangesStatsQuery($user, $start, $end = null)
    {
        $query = "
            SELECT
                ucs.type,
                ucs.created_at,
                IFNULL(NULLIF(ucs.pre_value , ''), 'N/A') AS old_change,
                ucs.post_value AS new_change
            FROM users_changes_stats ucs
            WHERE
                ucs.user_id = $user AND
                ucs.type IN ('country', 'main_province') AND
                ucs.created_at >= '{$start}'
        ";

        if (!empty($end)) {
            $query .= " AND ucs.created_at < '{$end}'";
        }

        return $query;
    }

    /**
     * @param $user
     * @param Carbon $start
     * @param Carbon $end
     * @param string $lj_on
     * @return array
     */
    private function getChangeStatsConfig($user, $start, $end, $lj_on)
    {
        $data = [
            'changed_days' => [],
            'end_date' => $end,
            'user_condition' => '',
            'left_join_select' => ", IFNULL(ucs.changes, 0) as changes",
            'left_join_query' => "LEFT JOIN (
                SELECT count(*) AS changes, user_id FROM users_changes_stats WHERE type IN ('country', 'main_province') GROUP BY user_id
            )  AS ucs ON ucs.user_id = $lj_on"
        ];

        if (!empty($user)) {
            $data['left_join_select'] = $data['left_join_query'] = '';
            $data['user_condition'] = "AND $lj_on = $user";
            $data['changed_days'] = $this->connection()->select($this->getChangesStatsQuery($user, $start, $end));

            $min = null;
            foreach ($data['changed_days'] as $i => $change) {
                $date = new Carbon($change['created_at']);
                $change['created_at'] = $date;
                $data['changed_days'][$i] = $change;

                if (empty($min)) {
                    $min = $change;
                } else {
                    $min = $date->lessThan($min['created_at']) ? $change : $min;
                }
            }

            $data['type'] = $min['type'];
            $data['change'] = $data["new_change"] = $min["old_change"];

            // When we want to regenerate the report data for a specific month in the past,
            // this conditional will resolve the country and main_province correctly.
            if (empty($min)) {
                $changes = $this->connection()->select($this->getChangesStatsQuery($user, $start));

                foreach ($changes as &$c) {
                    $c['created_at'] = new Carbon($c['created_at']);

                    if (empty($min)) {
                        $min = $c;
                    } else {
                        $min = $c['created_at']->lessThan($min['created_at']) ? $c : $min;
                    }
                }

                if (!empty($min)) {
                    $data['type'] = $min['type'];
                    $data['change'] = $data["new_change"] = $min["old_change"];
                }
            }
        }

        return $data;
    }

    /**
     * @param $category_id
     * @param null $user
     * @return array
     * @throws \Exception
     */
    public function getBetsAndWins($category_id, $user = null)
    {
        list($start, $end) = $this->generateDates(false, true);
        $tmp_tbl_name = 'users_monthly_liability_tmp';

        $this->connection()->getSchemaBuilder()->dropIfExists($tmp_tbl_name);
        $this->connection()->getSchemaBuilder()->create($tmp_tbl_name, function (Blueprint $table) {
            $table->asMaster();
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
            $table->string('network', 100);
            $table->integer('transactions');
            $table->bigInteger('amount');
            $table->string('currency', 4);
            $table->string('country', 3);
            $table->string('province', 4)->nullable();
        });

        $extra = '';
        if ($category_id == self::CAT_BETS) {
            $tbl_name = 'bets';
            $amount_sql = 'amount * -1 as amount';
        } elseif ($category_id == self::CAT_WINS || $category_id == self::CAT_FRB_WINS) {
            $tbl_name = 'wins';
            $amount_sql = 'amount';
            $extra = $category_id == self::CAT_WINS ? 'AND wins.bonus_bet != 3' : 'AND wins.bonus_bet = 3';
        } else {
            return false;
        }

        $changes_config = $this->getChangeStatsConfig($user, $start, $end, "$tbl_name.user_id");

        $current = $start;
        while ($start <= $current) {
            list ($start, $current) = $this->explodeInterval($changes_config, $start, $current->copy()->addDay());

            $list = $this->connection()->select("
                SELECT
                    DISTINCT $tbl_name.id, $tbl_name.user_id, network, $amount_sql, $tbl_name.currency, micro_games.ext_game_name,
                    {$this->getCountry($changes_config)} as country,
                    {$this->getProvince($changes_config)} as province
                    {$changes_config['left_join_select']}
                FROM $tbl_name
                    LEFT JOIN micro_games ON micro_games.ext_game_name = $tbl_name.game_ref AND micro_games.device_type_num = $tbl_name.device_type
                    INNER JOIN users ON users.id = $tbl_name.user_id
                    LEFT JOIN users_settings ON users_settings.user_id = users.id AND users_settings.setting = 'main_province'
                    {$changes_config['left_join_query']}
                WHERE $tbl_name.created_at >= :start AND $tbl_name.created_at < :end AND $tbl_name.amount != 0 $extra
                      {$changes_config['user_condition']}
            ", ['start' => $start->toDateTimeString(), 'end' => $current->toDateTimeString()],
                true,
                false,
                [],
                false,
                true
            );

            $tmp = [];
            foreach ($list as $elem) {
                if (!empty($elem['changes'])) {
                    $users[] = $elem['user_id'];
                    continue;
                }

                if ($elem['amount'] == 0) {
                    continue;
                }

                if (empty($elem['network'])) {
                    if ($elem['ext_game_name'] == 'netent_megajackpot_sw') {
                        $elem['network'] = 'netent';
                    } else {
                        $elem['network'] = 'not-categorized';
                    }
                }

                $tmp[$elem['user_id']][$elem['network']] = [
                    'amount' => empty($tmp[$elem['user_id']][$elem['network']]['amount']) ? $elem['amount'] : $tmp[$elem['user_id']][$elem['network']]['amount'] + $elem['amount'],
                    'currency' => $elem['currency'],
                    'country' => $elem['country'],
                    'province' => $elem['province'],
                    'transactions' => empty($tmp[$elem['user_id']][$elem['network']]['transactions']) ? 1 : $tmp[$elem['user_id']][$elem['network']]['transactions'] + 1
                ];
            }
            unset($list);

            $data = [];
            foreach ($tmp as $key => $val) {
                foreach ($val as $k => $v) {
                    $data[] = [
                        'user_id' => $key,
                        'network' => $k,
                        'transactions' => $v['transactions'],
                        'amount' => $v['amount'],
                        'currency' => $v['currency'],
                        'province' => $v['province'],
                        'country' => $v['country'],
                    ];
                }
            }

            if (count($data) > 0) {
                foreach (array_chunk($data, 3000) as $batch) {
                    $this->connection()->table($tmp_tbl_name)->insert($batch, null, true);
                }
            }
        }

        $res = $this->connection()->select($sql = "
            SELECT user_id, network as sub_cat, currency, sum(transactions) as transactions, sum(amount) as amount, :y as year, :m as month, :type as type, :main_cat as main_cat, country, province
            FROM $tmp_tbl_name
            GROUP BY user_id, network, country, province
        ", [
            'y' => $this->year,
            'm' => $this->month,
            'type' => $category_id == self::CAT_BETS ? self::DEBIT : self::CREDIT,
            'main_cat' => $category_id
        ]);

        $this->connection()->getSchemaBuilder()->dropIfExists($tmp_tbl_name);
        if (!empty($users = array_unique($users ?? []))) {
            foreach ($users as $user) {
                $res = array_merge($res, $a = $this->getBetsAndWins($category_id, $user));
            }
        }
        return $res;
    }

    /**
     * @param $category_id
     * @param null $user
     * @return array
     * @throws \Exception
     */
    public function getTournament($category_id, $user = null)
    {
        list($start, $end) = $this->generateDates(false, true);

        $transaction_type_map = [
            self::CAT_BOS_BUYIN_34 => 34,
            self::CAT_BOS_PRIZES_38 => 38,
            self::CAT_BOS_HOUSE_RAKE_52 => 52,
            self::CAT_BOS_REBUY_54 => 54
        ];

        $changes_config = $this->getChangeStatsConfig($user, $start, $end, $join_key = "ct.user_id");
        $res = $users = [];
        $current = $end;

        while ($start < $current) {
            list ($start, $current) = $this->explodeInterval($changes_config, $start, $end);

            $list = $this->connection()->select($sql = "
                SELECT
                    ct.user_id, ct.transactiontype, ct.currency, ct.description, ct.amount, ip_log.id AS ip_log_id,
                    {$this->getCountry($changes_config)} as country,
                    {$this->getProvince($changes_config)} as province
                    {$changes_config['left_join_select']}
                FROM cash_transactions ct
                    LEFT JOIN ip_log ON ip_log.tag = 'cash_transactions' AND ip_log.tr_id = ct.id
                    LEFT JOIN users ON users.id = {$join_key}
                    LEFT JOIN users_settings ON users_settings.user_id = users.id AND users_settings.setting = 'main_province'
                    {$changes_config['left_join_query']}
                WHERE ct.transactiontype = :type
                AND ct.amount != 0
                AND ct.timestamp BETWEEN :start_date AND :end_date {$changes_config['user_condition']}
            ", [
                'type' => $transaction_type_map[$category_id],
                'start_date' => $start->toDateTimeString(),
                'end_date' => $current->toDateTimeString()
            ]);

            $game_list = [];
            foreach ($list as $elem) {
                $game_ref = explode('-', $elem['description'])[1];
                if (!in_array($game_ref, $game_list)) {
                    $game_list[] = $game_ref;
                }
            }

            $tournaments_with_network = Tournament::select('tournaments.id', 'micro_games.network')
                ->leftJoin('micro_games', 'micro_games.ext_game_name', '=', 'tournaments.game_ref')
                ->whereIn('tournaments.id', $game_list)
                ->get()->pluck('network', 'id')->toArray();

            $tmp = [];
            foreach ($list as $elem) {
                if (!empty($elem['changes'])) {
                    $users[] = $elem['user_id'];
                    continue;
                }
                $network = $tournaments_with_network[explode('-', $elem['description'])[1]];
                $tmp[$elem['user_id']][$network] = [
                    'amount' => empty($tmp[$elem['user_id']][$network]['amount']) ? $elem['amount'] : $tmp[$elem['user_id']][$network]['amount'] + $elem['amount'],
                    'count' => empty($tmp[$elem['user_id']][$network]['count']) ? 1 : $tmp[$elem['user_id']][$network]['count'] + 1,
                    'currency' => $elem['currency'],
                    'country' => $elem['country'],
                    'province' => $elem['province'],
                ];
            }

            unset($list, $tournaments_with_network);

            foreach ($tmp as $key => $value) {
                foreach ($value as $k => $v) {

                    $res[] = [
                        'year' => $this->year,
                        'month' => $this->month,
                        'user_id' => $key,
                        'type' => $v['amount'] > 0 ? self::CREDIT : self::DEBIT,
                        'main_cat' => empty($v['ip_log_id']) ? $category_id : self::CAT_MANUAL,
                        'sub_cat' => empty($v['ip_log_id']) ? $k : self::CAT_MANUAL,
                        'transactions' => $v['count'],
                        'amount' => $v['amount'],
                        'currency' => $v['currency'],
                        'country' => $v['country'],
                        'province' => $v['province'],
                    ];
                }
            }
        }

        if (!empty($users = array_unique($users ?? []))) {
            foreach ($users as $user) {
                $res = array_merge($res, $a = $this->getTournament($category_id, $user));
            }
        }

        return $res;
    }

    /**
     * @param $category_id
     * @param $user
     * @return array
     * @throws \Exception
     */
    public function getCancelledTournament($category_id, $user = null)
    {
        list($start, $end) = $this->generateDates(false, true);

        $transaction_type_map = [
            self::CAT_BOS_CANCEL_BUYIN_61 => 61,
            self::CAT_BOS_CANCEL_HOUSE_FEE_63 => 63,
            self::CAT_BOS_CANCEL_REBUY_64 => 64,
            self::CAT_BOS_CANCEL_PAYBACK_65 => 65,
        ];

        $changes_config = $this->getChangeStatsConfig($user, $start, $end, $join_key = "ct.user_id");
        $res = $users = [];
        $current = $end;

        while ($start < $current) {
            list ($start, $current) = $this->explodeInterval($changes_config, $start, $end);

            $list = $this->connection()->select("
                SELECT
                    ct.id, ct.user_id, ct.transactiontype, ct.currency, ct.description, ip_log.id AS ip_log_id, ct.amount,
                    {$this->getCountry($changes_config)} as country,
                    {$this->getProvince($changes_config)} as province
                    {$changes_config['left_join_select']}
                FROM cash_transactions ct
                    LEFT JOIN ip_log ON ip_log.tag = 'cash_transactions' AND ip_log.tr_id = ct.id
                    LEFT JOIN users ON users.id = $join_key
                    LEFT JOIN users_settings ON users_settings.user_id = users.id AND users_settings.setting = 'main_province'
                    {$changes_config['left_join_query']}
                WHERE transactiontype = :type
                AND ct.amount != 0
                AND timestamp BETWEEN :start_date AND :end_date {$changes_config['user_condition']}
            ", [
                'type' => $transaction_type_map[$category_id],
                'start_date' => $start->toDateTimeString(),
                'end_date' => $current->toDateTimeString()
            ]);

            $ct_list = [];
            foreach ($list as $key => $value) {
                $original_ct = explode('-', $value['description'])[0];
                $list[$key]['description'] = $original_ct;
                if (!in_array($original_ct, $ct_list)) {
                    $ct_list[] = $original_ct;
                }
            }

            $original_cash_transaction_list = $this->connection()
                ->table('cash_transactions')->select('id', 'description')
                ->whereIn('id', $ct_list)
                ->get()->pluck('description', 'id')->toArray();

            $game_list = [];
            foreach ($original_cash_transaction_list as $key => $value) {
                $game_ref = explode('-', $value)[1];
                if (!in_array($game_ref, $game_list)) {
                    $game_list[] = $game_ref;
                }
            }

            $tournaments_with_network = $this->connection()->table('tournaments')
                ->select('tournaments.id', 'micro_games.network')
                ->leftJoin('micro_games', 'micro_games.ext_game_name', '=', 'tournaments.game_ref')
                ->whereIn('tournaments.id', $game_list)
                ->get()->pluck('network', 'id')->toArray();

            $ct_linked_to_networks = [];
            foreach ($original_cash_transaction_list as $key => $value) {
                $ct_linked_to_networks[$key] = $tournaments_with_network[explode('-', $value)[1]];
            }

            $tmp = [];
            foreach ($list as $elem) {
                if (!empty($elem['changes'])) {
                    $users[] = $elem['user_id'];
                    continue;
                }
                $network = $ct_linked_to_networks[$elem['description']];
                $tmp[$elem['user_id']][$network] = [
                    'amount' => empty($tmp[$elem['user_id']][$network]['amount']) ? $elem['amount'] : $tmp[$elem['user_id']][$network]['amount'] + $elem['amount'],
                    'count' => empty($tmp[$elem['user_id']][$network]['count']) ? 1 : $tmp[$elem['user_id']][$network]['count'] + 1,
                    'currency' => $elem['currency'],
                    'country' => $elem['country'],
                    'province' => $elem['province'],
                ];
            }

            unset($list);
            unset($tournaments_with_network);

            foreach ($tmp as $key => $value) {
                foreach ($value as $k => $v) {
                    $res[] = [
                        'year' => $this->year,
                        'month' => $this->month,
                        'user_id' => $key,
                        'type' => $v['amount'] > 0 ? self::CREDIT : self::DEBIT,
                        'main_cat' => empty($v['ip_log_id']) ? $category_id : self::CAT_MANUAL,
                        'sub_cat' => empty($v['ip_log_id']) ? $k : self::CAT_MANUAL,
                        'transactions' => $v['count'],
                        'amount' => $v['amount'],
                        'currency' => $v['currency'],
                        'country' => $v['country'],
                        'province' => $v['province'],
                    ];
                }
            }
            unset($tmp);
        }

        if (!empty($users = array_unique($users ?? []))) {
            foreach ($users as $user) {
                $res = array_merge($res, $a = $this->getCancelledTournament($category_id, $user));
            }
        }

        return $res;
    }

    /**
     * @param null $user
     * @return array
     * @throws \Exception
     */
    public function getRewards($user = null)
    {
        list($start, $end) = $this->generateDates(false, true);

        $res = [];
        $changes_config = $this->getChangeStatsConfig($user, $start, $end, $join_key = "ct.user_id");

        $current = $end;
        while ($start < $current) {
            list ($start, $current) = $this->explodeInterval($changes_config, $start, $end);

            $list = $this->connection()->select("
                SELECT
                    ct.user_id, ct.transactiontype, ct.currency, sum(ct.amount) AS amount, count(ct.id) AS count, ip_log.id AS ip_log_id,
                    {$this->getCountry($changes_config)} as country,
                    {$this->getProvince($changes_config)} as province
                    {$changes_config['left_join_select']}
                FROM cash_transactions ct
                    LEFT JOIN ip_log ON ip_log.tag = 'cash_transactions' AND ip_log.tr_id = ct.id
                    LEFT JOIN users ON users.id = {$join_key}
                    LEFT JOIN users_settings ON users_settings.user_id = users.id AND users_settings.setting = 'main_province'
                    {$changes_config['left_join_query']}
                WHERE ct.transactiontype IN (31, 32, 66, 67, 69, 72, 73, 76, 77, 78, 79, 80, 81, 84, 85, 86, 90)
                AND ct.timestamp BETWEEN :start_date AND :end_date
                AND ct.amount != 0 {$changes_config['user_condition']}
                GROUP BY user_id, transactiontype, ip_log.tr_id
            ", ['start_date' => $start->toDateTimeString(), 'end_date' => $current->toDateTimeString()]);

            foreach ($list as $elem) {
                if (!empty($elem['changes'])) {
                    $users[] = $elem['user_id'];
                    continue;
                }

                $main_cat = empty($elem['ip_log_id']) ? self::CAT_REWARDS : self::CAT_MANUAL;
                $data = [
                    'year' => $this->year,
                    'month' => $this->month,
                    'user_id' => $elem['user_id'],
                    'type' => $elem['amount'] > 0 ? self::CREDIT : self::DEBIT,
                    'main_cat' => $main_cat,
                    'sub_cat' => $elem['transactiontype'],
                    'transactions' => $elem['count'],
                    'amount' => $elem['amount'],
                    'currency' => $elem['currency'],
                    'country' => $elem['country'],
                    'province' => $elem['province'],
                ];
                $key = $data['user_id'].$data['main_cat'].$data['sub_cat'];

                // only on manual adjustment we'll go into the else block
                if (empty($res[$key])) {
                    $res[$key] = $data;
                } else {
                    $res[$key]['amount'] += $data['amount'];
                    $res[$key]['transactions'] += $data['transactions'];
                }
            }

            unset($list);
        }

        if (!empty($users = array_unique($users ?? []))) {
            foreach ($users as $user) {
                $res = array_merge($res, $a = $this->getRewards($user));
            }
        }

        return $res;
    }

    private function getSubCategoryFromElement($elem) {
        if ($elem['description'] == '#partial.bonus.payout') {
            $sub_cat = !empty($elem['be_bonus_type']) ? $elem['be_bonus_type'] : 'Partial bonus';
        } elseif ($this->isLike('Free cash reward', $elem['description'])) {
            $sub_cat = 'Free cash reward';
        } elseif ($this->isLike('Trophy reward top up', $elem['description'])) {
            $sub_cat = 'Trophy reward top up';
        } elseif ($this->isLike('Bonus Activation', $elem['description'])) {
            $sub_cat = !empty($elem['bt_bonus_type']) ? $elem['bt_bonus_type'] : 'Bonus activation';
        } elseif ($this->isLike('Admin transferred money', $elem['description'])) {
            $sub_type = DataFormatHelper::getCashTransactionsTypeName($elem['transactiontype']);
            $sub_cat = "Admin transfer ($sub_type)";
        } elseif ($this->isLike('Bonus reactivation', $elem['description'])) {
            $sub_cat = !empty($elem['bt_bonus_type']) ? $elem['bt_bonus_type'] : 'Bonus reactivation';
        } else {
            $sub_cat = mb_strlen($elem['description']) <= 80 ? "{$elem['description']} transaction ID {$elem['id']}" : substr($elem['id'], 0, 50) . " transaction ID {$elem['id']}";
        }
        return $sub_cat;
    }

    /**
     * @param null $user
     * @return array
     * @throws \Exception
     */
    public function getBonuses($user = null)
    {
        list($start, $end) = $this->generateDates(false, true);

        $changes_config = $this->getChangeStatsConfig($user, $start, $end, $join_key = "ct.user_id");
        $res = $users = [];
        $current = $end;

        while ($start < $current) {
            list ($start, $current) = $this->explodeInterval($changes_config, $start, $end);

            $list_a = $this->connection()->select("
                SELECT ct.*, ip_log.id AS ip_log_id, b.bonus_type AS be_bonus_type, bt.bonus_type AS bt_bonus_type,
                    {$this->getCountry($changes_config)} as country,
                    {$this->getProvince($changes_config)} as province
                    {$changes_config['left_join_select']}
                FROM cash_transactions ct
                    LEFT JOIN bonus_entries b ON ct.entry_id = b.id
                    LEFT JOIN bonus_types bt ON ct.bonus_id = bt.id
                    LEFT JOIN ip_log ON ip_log.tag = 'cash_transactions' AND ip_log.tr_id = ct.id
                    LEFT JOIN users ON users.id = {$join_key}
                    LEFT JOIN users_settings ON users_settings.user_id = users.id AND users_settings.setting = 'main_province'
                    {$changes_config['left_join_query']}
                WHERE ct.transactiontype = 14
                AND ct.amount != 0
                AND ct.timestamp BETWEEN :start_date AND :end_date {$changes_config['user_condition']}
            ", ['start_date' => $start->toDateTimeString(), 'end_date' => $current->toDateTimeString()]);

            $grouped_list = [];
            $manual_grouped_list = [];
            foreach ($list_a as $elem) {
                if ($this->isLike('-aid-', $elem['description'])) {
                    continue;
                }

                if (!empty($elem['changes'])) {
                    $users[] = $elem['user_id'];
                    continue;
                }

                $sub_cat = $this->getSubCategoryFromElement($elem);

                if (empty($elem['ip_log_id'])) {
                    $grouped_list[$elem['user_id']][$sub_cat] = [
                        'amount' => empty($grouped_list[$elem['user_id']][$sub_cat]) ? $elem['amount'] : $grouped_list[$elem['user_id']][$sub_cat]['amount'] + $elem['amount'],
                        'transactions' => empty($grouped_list[$elem['user_id']][$sub_cat]) ? 1 : $grouped_list[$elem['user_id']][$sub_cat]['transactions'] + 1,
                        'currency' => $elem['currency'],
                        'country' => $elem['country'],
                        'province' => $elem['province'],
                    ];
                } else {
                    $manual_grouped_list[$elem['user_id']][$sub_cat] = [
                        'amount' => empty($manual_grouped_list[$elem['user_id']][$sub_cat]) ? $elem['amount'] : $manual_grouped_list[$elem['user_id']][$sub_cat]['amount'] + $elem['amount'],
                        'transactions' => empty($manual_grouped_list[$elem['user_id']][$sub_cat]) ? 1 : $manual_grouped_list[$elem['user_id']][$sub_cat]['transactions'] + 1,
                        'currency' => $elem['currency'],
                        'country' => $elem['country'],
                        'province' => $elem['province'],
                    ];
                }
            }

            foreach ($grouped_list as $key => $value) {
                foreach ($value as $k => $v) {
                    $res[] = [
                        'year' => $this->year,
                        'month' => $this->month,
                        'user_id' => $key,
                        'type' => $v['amount'] > 0 ? self::CREDIT : self::DEBIT,
                        'main_cat' => self::CAT_REWARDS,
                        'sub_cat' => $k,
                        'transactions' => $v['transactions'],
                        'amount' => $v['amount'],
                        'currency' => $v['currency'],
                        'country' => $v['country'],
                        'province' => $v['province'],
                    ];
                }
            }
            foreach ($manual_grouped_list as $key => $value) {
                foreach ($value as $k => $v) {
                    $res[] = [
                        'year' => $this->year,
                        'month' => $this->month,
                        'user_id' => $key,
                        'type' => $v['amount'] > 0 ? self::CREDIT : self::DEBIT,
                        'main_cat' => self::CAT_MANUAL,
                        'sub_cat' => $k,
                        'transactions' => $v['transactions'],
                        'amount' => $v['amount'],
                        'currency' => $v['currency'],
                        'country' => $v['country'],
                        'province' => $v['province'],
                    ];
                }
            }

            unset($grouped_list, $manual_grouped_list);
        }

        if (!empty($users = array_unique($users ?? []))) {
            foreach ($users as $user) {
                $res = array_merge($res, $a = $this->getBonuses($user));
            }
        }

        return $res;
    }

    private function isLike($pattern, $subject)
    {
        return Common::isLike($pattern, $subject);
    }

    /**
     * @param null $user
     * @return array
     * @throws \Exception
     */
    public function getFailedBonus($user = null)
    {
        list($start, $end) = $this->generateDates(false, true);

        $changes_config = $this->getChangeStatsConfig($user, $start, $end, $join_key = "ct.user_id");
        $res = $users = [];
        $current = $end;

        while ($start < $current) {
            list ($start, $current) = $this->explodeInterval($changes_config, $start, $end);

            $list = $this->connection()->select("
                SELECT
                    ct.user_id, ct.transactiontype, ct.currency, ct.amount, ip_log.id AS ip_log_id,
                    {$this->getCountry($changes_config)} as country,
                    {$this->getProvince($changes_config)} as province
                    {$changes_config['left_join_select']}
                FROM cash_transactions ct
                    LEFT JOIN ip_log ON ip_log.tag = 'cash_transactions' AND ip_log.tr_id = ct.id
                    LEFT JOIN users ON users.id = {$join_key}
                    LEFT JOIN users_settings ON users_settings.user_id = users.id AND users_settings.setting = 'main_province'
                    {$changes_config['left_join_query']}
                WHERE ct.transactiontype IN (15, 53)
                AND ct.amount != 0
                AND ct.timestamp BETWEEN :start_date AND :end_date  {$changes_config['user_condition']}
            ", ['start_date' => $start->toDateTimeString(), 'end_date' => $current->toDateTimeString()]);

            $grouped_list = [];
            foreach ($list as $elem) {
                if (!empty($elem['changes'])) {
                    $users[] = $elem['user_id'];
                    continue;
                }

                if ($elem['transactiontype'] == 15) {
                    if ($this->isLike('Super blocked so did not payout', $elem['description'])) {
                        continue;
                    } elseif ($this->isLike('-cancelled', $elem['description'])) {
                        continue;
                    } elseif (empty($elem['ip_log_id'])) {
                        $main_cat = self::CAT_REWARDS;
                        $sub_cat = 15;
                    } else {
                        $main_cat = self::CAT_MANUAL;
                        $sub_cat = 15;
                    }
                    //$elem['description'] == 'Super blocked cash balance zeroed.' //todo doubt regarding this one

                } else {
                    $main_cat = empty($elem['ip_log_id']) ? self::CAT_REWARDS : self::CAT_MANUAL;
                    $sub_cat = $elem['transactiontype'];
                }

                $grouped_list[$elem['user_id']][$main_cat][$sub_cat] = [
                    'amount' => empty($grouped_list[$elem['user_id']][$main_cat][$sub_cat]) ? $elem['amount'] : $grouped_list[$elem['user_id']][$main_cat][$sub_cat]['amount'] + $elem['amount'],
                    'transactions' => empty($grouped_list[$elem['user_id']][$main_cat][$sub_cat]) ? 1 : $grouped_list[$elem['user_id']][$main_cat][$sub_cat]['transactions'] + 1,
                    'currency' => $elem['currency'],
                    'country' => $elem['country'],
                    'province' => $elem['province'],
                ];
            }

            foreach ($grouped_list as $key => $value) {
                foreach ($value as $km => $vm) {
                    foreach ($vm as $ks => $vs) {
                        $res[] = [
                            'year' => $this->year,
                            'month' => $this->month,
                            'user_id' => $key,
                            'type' => $vs['amount'] > 0 ? self::CREDIT : self::DEBIT,
                            'main_cat' => $km,
                            'sub_cat' => $ks,
                            'transactions' => $vs['transactions'],
                            'amount' => $vs['amount'],
                            'currency' => $vs['currency'],
                            'country' => $vs['country'],
                            'province' => $vs['province'],
                        ];
                    }
                }
            }

            unset($grouped_list);
        }
        if (!empty($users = array_unique($users ?? []))) {
            foreach ($users as $user) {
                $res = array_merge($res, $a = $this->getFailedBonus($user));
            }
        }

        return $res;
    }

    /**
     * @param null $user
     * @return array
     * @throws \Exception
     */
    public function getRollbacks($category_id, $user = null)
    {
        if ($category_id === self::CAT_BET_REFUND_7) {
            $where_amount = "AND ct.amount > 0";
            $type = self::CREDIT;
            $main_cat = self::CAT_BET_REFUND_7;
        } elseif ($category_id === self::CAT_WIN_ROLLBACK_7) {
            $where_amount = "AND ct.amount < 0";
            $type = self::DEBIT;
            $main_cat = self::CAT_WIN_ROLLBACK_7;
        } else {
            return false;
        }

        list($start, $end) = $this->generateDates(false, true);

        $changes_config = $this->getChangeStatsConfig($user, $start, $end, $join_key = "ct.user_id");

        $current = $start;
        $res = [];
        while ($start <= $current) {
            list ($start, $current) = $this->explodeInterval($changes_config, $start, $current->copy()->addDay());
            $list = $this->connection()->select($sql = "
                SELECT ct.user_id,
                       ct.transactiontype,
                       ct.currency,
                       amount,
                       ct.description,
                       {$this->getCountry($changes_config)} AS country,
                       {$this->getProvince($changes_config)} AS province
                       {$changes_config['left_join_select']}
                FROM cash_transactions ct
                    LEFT JOIN users ON users.id = {$join_key}
                    LEFT JOIN users_settings ON users_settings.user_id = users.id AND users_settings.setting = 'main_province'
                    {$changes_config['left_join_query']}
                WHERE ct.transactiontype = 7
                  {$where_amount}
                  AND ct.timestamp BETWEEN :start_date AND :end_date {$changes_config['user_condition']}
            ", ['start_date' => $start->toDateTimeString(), 'end_date' => $current->toDateTimeString()],
                true,
                false,
                [],
                false,
                true
            );

            $tmp = [];

            foreach ($list as $elem) {
                if (!empty($elem['changes'])) {
                    $users[] = $elem['user_id'];
                    continue;
                }

                $elem['network'] = strtolower(explode(' ', trim($elem['description']))[0]);
                if (empty($elem['network'])) {
                    $elem['network'] = 'not-categorized';
                }

                $tmp[$elem['user_id']][$elem['network']] = [
                    'amount' => empty($tmp[$elem['user_id']][$elem['network']]['amount']) ?
                        $elem['amount'] : $tmp[$elem['user_id']][$elem['network']]['amount'] + $elem['amount'],
                    'currency' => $elem['currency'],
                    'country' => $elem['country'],
                    'province' => $elem['province'],
                    'transactions' => empty($tmp[$elem['user_id']][$elem['network']]['transactions']) ?
                        1 : $tmp[$elem['user_id']][$elem['network']]['transactions'] + 1
                ];
            }
            unset($list);

            foreach ($tmp as $user_id => $data) {
                foreach ($data as $network => $details) {
                    $res[] = [
                        'user_id' => $user_id,
                        'sub_cat' => $network,
                        'transactions' => $details['transactions'],
                        'amount' => $details['amount'],
                        'currency' => $details['currency'],
                        'country' => $details['country'],
                        'province' => $details['province'],
                        'year' => $this->year,
                        'month' => $this->month,
                        'type' => $type,
                        'main_cat' => $main_cat
                    ];
                }
            }
        }

        if (!empty($users = array_unique($users ?? []))) {
            foreach ($users as $user) {
                $res = array_merge($res, $a = $this->getRollbacks($category_id, $user));
            }
        }

        return $res;
    }

    /**
     * @param null $user
     * @return array
     * @throws \Exception
     */
    public function getMisc($user = null)
    {
        list($start, $end) = $this->generateDates(false, true);

        $changes_config = $this->getChangeStatsConfig($user, $start, $end, $join_key = "ct.user_id");
        $res = $users = [];
        $current = $end;

        while ($start < $current) {
            list ($start, $current) = $this->explodeInterval($changes_config, $start, $end);

            $list = $this->connection()->select("
                SELECT
                    ct.user_id, ct.transactiontype, ct.currency, ct.amount, ct.description, ip_log.id AS ip_log_id,
                    {$this->getCountry($changes_config)} as country,
                    {$this->getProvince($changes_config)} as province
                    {$changes_config['left_join_select']}
                FROM cash_transactions ct
                    LEFT JOIN ip_log ON ip_log.tag = 'cash_transactions' AND ip_log.tr_id = ct.id
                    LEFT JOIN users ON users.id = {$join_key}
                    LEFT JOIN users_settings ON users_settings.user_id = users.id AND users_settings.setting = 'main_province'
                    {$changes_config['left_join_query']}
                WHERE ct.transactiontype IN (1, 2, 9, 12, 13, 29, 42, 43, 50, 60, 91)
                AND ct.amount != 0
                AND ct.timestamp BETWEEN :start_date AND :end_date {$changes_config['user_condition']}
            ", ['start_date' => $start->toDateTimeString(), 'end_date' => $current->toDateTimeString()]);

            $grouped_list = [];
            foreach ($list as $elem) {
                if (!empty($elem['changes'])) {
                    $users[] = $elem['user_id'];
                    continue;
                }
                if ($elem['transactiontype'] == 13) { //normal refund 21
                    $main_cat = empty($elem['ip_log_id']) ? self::CAT_REFUND_13 : self::CAT_MANUAL;
                    $sub_cat = empty($elem['ip_log_id']) ? $elem['description'] : 13;

                } elseif ($elem['transactiontype'] == 43) { //inactivity fee 18
                    $main_cat = empty($elem['ip_log_id']) ? self::CAT_INACTIVITY_43 : self::CAT_MANUAL;
                    $sub_cat = empty($elem['ip_log_id']) ? '' : 'Inactivity fee';

                } elseif ($elem['transactiontype'] == 1) { //manual bet 1
                    $main_cat = empty($elem['ip_log_id']) ? self::CAT_BETS : self::CAT_MANUAL;
                    $sub_cat = empty($elem['ip_log_id']) ? '' : 'Manual bet';

                } elseif ($elem['transactiontype'] == 2) { //manual win 2
                    $main_cat = empty($elem['ip_log_id']) ? self::CAT_WINS : self::CAT_MANUAL;
                    $sub_cat = empty($elem['ip_log_id']) ? '' : 'Manual win';

                } elseif ($elem['transactiontype'] == 9) { //chargeback 9
                    $main_cat = empty($elem['ip_log_id']) ? self::CAT_CHARGEBACK_9 : self::CAT_MANUAL;
                    $sub_cat = empty($elem['ip_log_id']) ? '' : 'Chargeback';

                } elseif ($elem['transactiontype'] == 50) { //withdrawal deduction 16
                    $main_cat = empty($elem['ip_log_id']) ? self::CAT_WITHDRAWAL_DEDUCTION_50 : self::CAT_MANUAL;
                    $sub_cat = empty($elem['ip_log_id']) ? '' : 'Withdrawal deduction';

                } elseif ($elem['transactiontype'] == 12) { //jackpot win 23
                    $main_cat = empty($elem['ip_log_id']) ? self::CAT_JACKPOT_WIN_12 : self::CAT_MANUAL;
                    $sub_cat = empty($elem['ip_log_id']) ? '' : 'Jackpot win';

                } elseif ($elem['transactiontype'] == 29) { //buddy transfer 22
                    $main_cat = empty($elem['ip_log_id']) ? self::CAT_BUDDY_TRANSFER_29 : self::CAT_MANUAL;
                    $sub_cat = empty($elem['ip_log_id']) ? '' : 'Buddy transfer';

                } elseif ($elem['transactiontype'] == 42) { //Test cash 19
                    $main_cat = empty($elem['ip_log_id']) ? self::CAT_TEST_CASH_42 : self::CAT_MANUAL;
                    $sub_cat = empty($elem['ip_log_id']) ? "Test cash to user: {$elem['user_id']}" : "Test cash to user: {$elem['user_id']}";

                } elseif ($elem['transactiontype'] == 60) { //zeroing out of balance 20
                    $main_cat = empty($elem['ip_log_id']) ? self::CAT_ZEROING_OUT_BAL_60 : self::CAT_MANUAL;
                    $sub_cat = empty($elem['ip_log_id']) ? '' : 'Zeroing out of balance';

                } elseif ($elem['transactiontype'] == 91) { //liability adjustment
                    $main_cat = empty($elem['ip_log_id']) ? self::CAT_LIABILITY_ADJUST : self::CAT_MANUAL;
                    $sub_cat = empty($elem['ip_log_id']) ? $elem['description'] : "Liability adjustment // user {$elem['user_id']}";

                } else {
                    $main_cat = empty($elem['ip_log_id']) ? 'Non-categorized' : self::CAT_MANUAL;
                    $sub_cat = empty($elem['ip_log_id']) ? '' : 'Non-categorized';

                }

                $grouped_list[$elem['user_id']][$main_cat][$sub_cat] = [
                    'amount' => empty($grouped_list[$elem['user_id']][$main_cat][$sub_cat]) ? $elem['amount'] : $grouped_list[$elem['user_id']][$main_cat][$sub_cat]['amount'] + $elem['amount'],
                    'transactions' => empty($grouped_list[$elem['user_id']][$main_cat][$sub_cat]) ? 1 : $grouped_list[$elem['user_id']][$main_cat][$sub_cat]['transactions'] + 1,
                    'currency' => $elem['currency'],
                    'country' => $elem['country'],
                    'province' => $elem['province'],
                ];
            }

            foreach ($grouped_list as $key => $value) {
                foreach ($value as $km => $vm) {
                    foreach ($vm as $ks => $vs) {
                        $res[] = [
                            'year' => $this->year,
                            'month' => $this->month,
                            'user_id' => $key,
                            'type' => $vs['amount'] > 0 ? self::CREDIT : self::DEBIT,
                            'main_cat' => $km,
                            'sub_cat' => $ks,
                            'transactions' => $vs['transactions'],
                            'amount' => $vs['amount'],
                            'currency' => $vs['currency'],
                            'country' => $vs['country'],
                            'province' => $vs['province'],
                        ];
                    }
                }
            }

            unset($grouped_list);
        }
        if (!empty($users = array_unique($users ?? []))) {
            foreach ($users as $user) {
                $res = array_merge($res, $a = $this->getMisc($user));
            }
        }

        return $res;
    }

    /**
     * @param null $user
     * @return array
     * @throws \Exception
     */
    public function getAffiliatePayouts($user = null)
    {
        list($start, $end) = $this->generateDates(false, true);

        $res = [];
        $changes_config = $this->getChangeStatsConfig($user, $start, $end, $join_key = "ct.user_id");

        $current = $end;
        while ($start < $current) {
            list ($start, $current) = $this->explodeInterval($changes_config, $start, $end);

            $list = $this->connection()->select("
                SELECT
                    ct.user_id, ct.transactiontype, ct.currency, sum(ct.amount) AS amount, count(ct.id) AS count, ip_log.id AS ip_log_id,
                    {$this->getCountry($changes_config)} as country,
                    {$this->getProvince($changes_config)} as province
                    {$changes_config['left_join_select']}
                FROM cash_transactions ct
                    LEFT JOIN ip_log ON ip_log.tag = 'cash_transactions' AND ip_log.tr_id = ct.id
                    LEFT JOIN users ON users.id = {$join_key}
                    LEFT JOIN users_settings ON users_settings.user_id = users.id AND users_settings.setting = 'main_province'
                    {$changes_config['left_join_query']}
                WHERE ct.transactiontype IN (5, 20)
                AND ct.timestamp BETWEEN :start_date AND :end_date
                AND ct.amount != 0 {$changes_config['user_condition']}
                GROUP BY user_id
            ", ['start_date' =>  $start->toDateTimeString(), 'end_date' =>  $current->toDateTimeString()]);

            foreach ($list as $elem) {
                if (!empty($elem['changes'])) {
                    $users[] = $elem['user_id'];
                    continue;
                }
                $res[] = [
                    'year' => $this->year,
                    'month' => $this->month,
                    'user_id' => $elem['user_id'],
                    'type' => $elem['amount'] > 0 ? self::CREDIT : self::DEBIT,
                    'main_cat' => empty($elem['ip_log_id']) ? self::CAT_AFF_PAYOUTS : self::CAT_MANUAL,
                    'sub_cat' => empty($elem['ip_log_id']) ? '' : $elem['transactiontype'],
                    'transactions' => $elem['count'],
                    'amount' => $elem['amount'],
                    'currency' => $elem['currency'],
                    'country' => $elem['country'],
                    'province' => $elem['province'],
                ];
            }

            unset($list);
        }
        if (!empty($users = array_unique($users ?? []))) {
            foreach ($users as $user) {
                $res = array_merge($res, $a = $this->getAffiliatePayouts($user));
            }
        }

        return $res;
    }

    /**
     * @param null $user
     * @return array
     * @throws \Exception
     */
    public function getBoosterVaultTransfer($user = null)
    {
        list($start, $end) = $this->generateDates(false, true);

        $res = [];
        $changes_config = $this->getChangeStatsConfig($user, $start, $end, $join_key = "ct.user_id");

        $current = $end;
        while ($start < $current) {
            list ($start, $current) = $this->explodeInterval($changes_config, $start, $end);

            $list = $this->connection()->select("
                SELECT
                    ct.user_id, ct.transactiontype, ct.currency, sum(ct.amount) AS amount, count(ct.id) AS count, ip_log.id AS ip_log_id,
                    {$this->getCountry($changes_config)} as country,
                    {$this->getProvince($changes_config)} as province
                    {$changes_config['left_join_select']}
                FROM cash_transactions ct
                    LEFT JOIN ip_log ON ip_log.tag = 'cash_transactions' AND ip_log.tr_id = ct.id
                    LEFT JOIN users ON users.id = {$join_key}
                    LEFT JOIN users_settings ON users_settings.user_id = users.id AND users_settings.setting = 'main_province'
                    {$changes_config['left_join_query']}
                WHERE ct.transactiontype IN (100, 101)
                AND ct.timestamp BETWEEN :start_date AND :end_date
                AND ct.amount != 0 {$changes_config['user_condition']}
                GROUP BY user_id, ct.transactiontype
            ", ['start_date' =>  $start->toDateTimeString(), 'end_date' =>  $current->toDateTimeString()]);

            foreach ($list as $elem) {
                if (!empty($elem['changes'])) {
                    $users[] = $elem['user_id'];
                    continue;
                }
                $res[] = [
                    'year' => $this->year,
                    'month' => $this->month,
                    'user_id' => $elem['user_id'],
                    'type' => $elem['amount'] > 0 ? self::CREDIT : self::DEBIT,
                    'main_cat' => empty($elem['ip_log_id']) ? self::CAT_BOOSTER_VAULT_TRANSFER : self::CAT_MANUAL,
                    'sub_cat' => $elem['amount'] > 0 ? 'Transfer from Booster Vault' : 'Transfer to Booster Vault',
                    'transactions' => $elem['count'],
                    'amount' => $elem['amount'],
                    'currency' => $elem['currency'],
                    'country' => $elem['country'],
                    'province' => $elem['province'],
                ];
            }

            unset($list);
        }
        if (!empty($users = array_unique($users ?? []))) {
            foreach ($users as $user) {
                $res = array_merge($res, $a = $this->getBoosterVaultTransfer($user));
            }
        }
        return $res;
    }

    /**
     * @param null $user
     * @return array
     * @throws \Exception
     */
    public function getChargebackSettlement($user = null)
    {
        list($start, $end) = $this->generateDates(false, true);

        $res = [];
        $changes_config = $this->getChangeStatsConfig($user, $start, $end, $join_key = "ct.user_id");

        $current = $end;
        while ($start < $current) {
            list ($start, $current) = $this->explodeInterval($changes_config, $start, $end);

            $list = $this->connection()->select("
                SELECT
                    ct.user_id, ct.transactiontype, ct.currency, sum(ct.amount) AS amount, count(ct.id) AS count, ip_log.id AS ip_log_id,
                    {$this->getCountry($changes_config)} as country,
                    {$this->getProvince($changes_config)} as province
                    {$changes_config['left_join_select']}
                FROM cash_transactions ct
                    LEFT JOIN ip_log ON ip_log.tag = 'cash_transactions' AND ip_log.tr_id = ct.id
                    LEFT JOIN users ON users.id = {$join_key}
                    LEFT JOIN users_settings ON users_settings.user_id = users.id AND users_settings.setting = 'main_province'
                    {$changes_config['left_join_query']}
                WHERE ct.transactiontype IN (92)
                AND ct.timestamp BETWEEN :start_date AND :end_date
                AND ct.amount != 0 {$changes_config['user_condition']}
                GROUP BY user_id, transactiontype
            ", ['start_date' => $start->toDateTimeString(), 'end_date' => $current->toDateTimeString()]);

            foreach ($list as $elem) {
                if (!empty($elem['changes'])) {
                    $users[] = $elem['user_id'];
                    continue;
                }

                $main_cat = empty($elem['ip_log_id']) ? self::CAT_CHARGEBACK_SETTLEMENT : self::CAT_MANUAL;

                $res[] = [
                    'year' => $this->year,
                    'month' => $this->month,
                    'user_id' => $elem['user_id'],
                    'type' => $elem['amount'] > 0 ? self::CREDIT : self::DEBIT,
                    'main_cat' => $main_cat,
                    'sub_cat' => $elem['transactiontype'],
                    'transactions' => $elem['count'],
                    'amount' => $elem['amount'],
                    'currency' => $elem['currency'],
                    'country' => $elem['country'],
                    'province' => $elem['province'],
                ];
            }

            unset($list);
        }

        if (!empty($users = array_unique($users ?? []))) {
            foreach ($users as $user) {
                $res = array_merge($res, $a = $this->getChargebackSettlement($user));
            }
        }

        return $res;
    }

    /**
     * We get tax deductions as in Germany
     *
     * @param null $user
     * @return array
     * @throws \Exception
     */
    public function getTaxDeductions($user = null)
    {
        list($start, $end) = $this->generateDates(false, true);

        $res = [];
        $changes_config = $this->getChangeStatsConfig($user, $start, $end, $join_key = "ct.user_id");

        $current = $end;
        while ($start < $current) {
            list ($start, $current) = $this->explodeInterval($changes_config, $start, $end);

            $list = $this->connection()->select("
                SELECT
                    ct.user_id, ct.transactiontype, ct.currency, sum(ct.amount) AS amount, count(ct.id) AS count, ip_log.id AS ip_log_id,
                    {$this->getCountry($changes_config)} as country,
                    {$this->getProvince($changes_config)} as province
                    {$changes_config['left_join_select']}
                FROM cash_transactions ct
                    LEFT JOIN ip_log ON ip_log.tag = 'cash_transactions' AND ip_log.tr_id = ct.id
                    LEFT JOIN users ON users.id = {$join_key}
                    LEFT JOIN users_settings ON users_settings.user_id = users.id AND users_settings.setting = 'main_province'
                    {$changes_config['left_join_query']}
                WHERE ct.transactiontype IN (104, 105)
                AND ct.timestamp BETWEEN :start_date AND :end_date
                AND ct.amount != 0 {$changes_config['user_condition']}
                GROUP BY user_id, transactiontype
            ", ['start_date' => $start->toDateTimeString(), 'end_date' => $current->toDateTimeString()]);

            foreach ($list as $elem) {
                if (!empty($elem['changes'])) {
                    $users[] = $elem['user_id'];
                    continue;
                }

                $main_cat = empty($elem['ip_log_id']) ? self::CAT_TAX_DEDUCTION : self::CAT_MANUAL;

                $res[] = [
                    'year' => $this->year,
                    'month' => $this->month,
                    'user_id' => $elem['user_id'],
                    'type' => $elem['amount'] > 0 ? self::CREDIT : self::DEBIT,
                    'main_cat' => $main_cat,
                    'sub_cat' => $elem['amount'] < 0 ? 'Tax deduction' : 'Tax deduction reversal',
                    'transactions' => $elem['count'],
                    'amount' => $elem['amount'],
                    'currency' => $elem['currency'],
                    'country' => $elem['country'],
                    'province' => $elem['province'],
                ];
            }

            unset($list);
        }

        if (!empty($users = array_unique($users ?? []))) {
            foreach ($users as $user) {
                $res = array_merge($res, $a = $this->getTaxDeductions($user));
            }
        }

        return $res;
    }


    /**
     * Get undone withdrawals, by default zimpler
     *
     * @param null $user
     * @return array
     * @throws \Exception
     */
    public function getUndoneWithdrawals($user = null, $type = 'zimpler')
    {
        list($start, $end) = $this->generateDates(false, true);

        $res = [];
        $changes_config = $this->getChangeStatsConfig($user, $start, $end, $join_key = "ct.user_id");

        $current = $end;
        while ($start < $current) {
            list ($start, $current) = $this->explodeInterval($changes_config, $start, $end);

            $list = $this->connection()->select("
                SELECT
                    ct.user_id, ct.transactiontype, ct.currency, sum(ct.amount) AS amount, count(ct.id) AS count, ip_log.id AS ip_log_id,
                    {$this->getCountry($changes_config)} as country,
                    {$this->getProvince($changes_config)} as province
                    {$changes_config['left_join_select']}
                FROM cash_transactions ct
                    LEFT JOIN ip_log ON ip_log.tag = 'cash_transactions' AND ip_log.tr_id = ct.id
                    LEFT JOIN users ON users.id = {$join_key}
                    LEFT JOIN users_settings ON users_settings.user_id = users.id AND users_settings.setting = 'main_province'
                    {$changes_config['left_join_query']}
                WHERE ct.transactiontype IN (103)
                AND ct.timestamp BETWEEN :start_date AND :end_date
                AND ct.amount != 0 {$changes_config['user_condition']}
                GROUP BY user_id, transactiontype
            ", ['start_date' => $start->toDateTimeString(), 'end_date' => $current->toDateTimeString()]);

            $type = ucfirst($type);
            foreach ($list as $elem) {
                if (!empty($elem['changes'])) {
                    $users[] = $elem['user_id'];
                    continue;
                }

                $main_cat = self::CAT_REFUND_13;

                $res[] = [
                    'year' => $this->year,
                    'month' => $this->month,
                    'user_id' => $elem['user_id'],
                    'type' => $elem['amount'] > 0 ? self::CREDIT : self::DEBIT,
                    'main_cat' => $main_cat,
                    'sub_cat' => "$type Withdrawals - Rev",
                    'transactions' => $elem['count'],
                    'amount' => $elem['amount'],
                    'currency' => $elem['currency'],
                    'country' => $elem['country'],
                    'province' => $elem['province'],
                ];
            }

            unset($list);
        }

        if (!empty($users = array_unique($users ?? []))) {
            foreach ($users as $user) {
                $res = array_merge($res, $a = $this->getUndoneWithdrawals($user, $type));
            }
        }

        return $res;
    }


    /**
     * Get the transaction data for sportsbook
     *
     * @param string $data_type
     * @param null $user
     * @return array
     * @throws \Exception
     */
    public function getSportsbookData($data_type, $user = null)
    {
        list($start, $end) = $this->generateDates(false, true);

        $changes_config = $this->getChangeStatsConfig($user, $start, $end, $join_key = "spt.user_id");
        $res = $users = [];
        $current = $end;
        $bet_type = 'win';
        $bet_category = self::CAT_WINS;

        if ($data_type == 'bets') {
            $bet_type = 'bet';
            $bet_category = self::CAT_BETS;
        } else if ($data_type == 'void') {
            $bet_type = 'void';
            $bet_category = self::CAT_SPORTSBOOK_VOIDS;
        }

        while ($start < $current) {
            list ($start, $current) = $this->explodeInterval($changes_config, $start, $end);

            $list = $this->connection()->select("
                SELECT
                    spt.user_id AS user_id,
                    network,
                    product,
                    sum(spt.amount) AS amount,
                    count(spt.id) as transactions,
                    spt.currency,
                    ip_log.id AS ip_log_id,
                    {$this->getCountry($changes_config)} as country,
                    {$this->getProvince($changes_config)} as province
                    {$changes_config['left_join_select']}
                FROM sport_transactions spt
                    LEFT JOIN ip_log ON ip_log.tag = 'deposits' AND ip_log.tr_id = spt.id
                    LEFT JOIN users ON users.id = {$join_key}
                    LEFT JOIN users_settings ON users_settings.user_id = users.id AND users_settings.setting = 'main_province'
                    {$changes_config['left_join_query']}
                WHERE spt.created_at BETWEEN :start_date AND :end_date {$changes_config['user_condition']}
                AND spt.bet_type = '{$bet_type}'
                GROUP BY spt.user_id, network, product, spt.currency
            ", ['start_date' => $start->toDateTimeString(), 'end_date' => $current->toDateTimeString()]);

            foreach ($list as $elem) {

                if (!empty($elem['changes'])) {
                    $users[] = $elem['user_id'];
                    continue;
                }

                $res[] = [
                    'year' => $this->year,
                    'month' => $this->month,
                    'user_id' => $elem['user_id'],
                    'type' => ($bet_type != 'bet')? self::CREDIT : self::DEBIT,
                    'main_cat' => $bet_category,
                    'sub_cat' => $elem['product'] . "_" . $elem['network'],
                    'transactions' => $elem['transactions'],
                    'amount' => ($bet_category == self::CAT_BETS)? gmp_neg($elem['amount']) : $elem['amount'],
                    'currency' => $elem['currency'],
                    'country' => $elem['country'],
                    'province' => $elem['province']
                ];
            }
            unset($list);
        }

        if (!empty($users = array_unique($users ?? []))) {
            foreach ($users as $user) {
                $res = array_merge($res, $a = $this->getSportsbookData($data_type, $user));
            }
        }

        return $res;
    }

    public function getSportsAgentFee($trans_type, $user = null)
    {
        list($start, $end) = $this->generateDates(false, true);

        $res = [];
        $changes_config = $this->getChangeStatsConfig($user, $start, $end, $join_key = "ct.user_id");

        $current = $end;
        while ($start < $current) {
            list ($start, $current) = $this->explodeInterval($changes_config, $start, $end);
            $list = $this->connection()->select("
                SELECT
                    ct.user_id, ct.transactiontype, ct.currency, sum(ct.amount) AS amount, count(ct.id) AS count, ip_log.id AS ip_log_id, st.product, st.network,
                    {$this->getCountry($changes_config)} as country,
                    {$this->getProvince($changes_config)} as province
                    {$changes_config['left_join_select']}
                FROM cash_transactions ct
                    LEFT JOIN ip_log ON ip_log.tag = 'cash_transactions' AND ip_log.tr_id = ct.id
                    LEFT JOIN users ON users.id = {$join_key}
                    LEFT JOIN users_settings ON users_settings.user_id = users.id AND users_settings.setting = 'main_province'
                    LEFT JOIN sport_transactions st ON st.id = ct.parent_id
                    {$changes_config['left_join_query']}
                WHERE ct.transactiontype IN ({$trans_type})
                AND ct.timestamp BETWEEN :start_date AND :end_date
                AND ct.amount != 0 {$changes_config['user_condition']}
                GROUP BY user_id, transactiontype, st.network, st.product
            ", ['start_date' => $start->toDateTimeString(), 'end_date' => $current->toDateTimeString()]);

            $main_cat_type = $trans_type === 106 ? self::CAT_SPORTS_AGENT_FEE : self::CAT_SPORTS_AGENT_FEE_REFUND;
            foreach ($list as $elem) {
                if (!empty($elem['changes'])) {
                    $users[] = $elem['user_id'];
                    continue;
                }

                $main_cat = empty($elem['ip_log_id']) ? $main_cat_type : self::CAT_MANUAL;
                $product = $elem['product'] ?? 'undefined';
                $network = $elem['network'] ?? 'undefined';

                $res[] = [
                    'year' => $this->year,
                    'month' => $this->month,
                    'user_id' => $elem['user_id'],
                    'type' => $elem['amount'] > 0 ? self::CREDIT : self::DEBIT,
                    'main_cat' => $main_cat,
                    'sub_cat' => $product . "_" . $network,
                    'transactions' => $elem['count'],
                    'amount' => $elem['amount'],
                    'currency' => $elem['currency'],
                    'country' => $elem['country'],
                    'province' => $elem['province'],
                ];
            }

            unset($list);
        }

        if (!empty($users = array_unique($users ?? []))) {
            foreach ($users as $user) {
                $res = array_merge($res, $a = $this->getSportsAgentFee($trans_type, $user));
            }
        }

        return $res;
    }

    public static function getLiabilityCategoryName($category_idx)
    {
        $map = [
            self::CAT_DEPOSIT => 'Deposits',
            self::CAT_WITHDRAWAL => 'Withdrawals',
            self::CAT_PENDING_WITHDRAWAL => 'Pending withdrawals',
            self::CAT_BETS => 'Bets',
            self::CAT_WINS => 'Wins',
            self::CAT_BOS_BUYIN_34 => 'Battle of Slots - Buy-in', //debit
            self::CAT_BOS_PRIZES_38 => 'Battle of Slots - Prize pay-out', //credit
            self::CAT_BOS_HOUSE_RAKE_52 => 'Battle of Slots - House fee', //debit
            self::CAT_BOS_REBUY_54 => 'Battle of Slots - Re-buy', //debit
            self::CAT_BOS_CANCEL_BUYIN_61 => 'Battle of Slots - Cancelled Buy-in', //credit
            self::CAT_BOS_CANCEL_HOUSE_FEE_63 => 'Battle of Slots - Cancelled House fee', //credit
            self::CAT_BOS_CANCEL_REBUY_64 => 'Battle of Slots - Cancelled Re-buy',
            self::CAT_BOS_CANCEL_PAYBACK_65 => 'Battle of Slots - Cancelled Payback',
            self::CAT_REWARDS => 'Bonus rewards',
            self::CAT_MANUAL => 'Manual adjustments',
            self::CAT_AFF_PAYOUTS => 'Affiliate Payouts',
            self::CAT_WITHDRAWAL_DEDUCTION_50 => 'Withdrawal deduction',
            self::CAT_BET_REFUND_7 => 'Bet Refunds',
            self::CAT_WIN_ROLLBACK_7 => 'Win Rollbacks',
            self::CAT_INACTIVITY_43 => 'Inactivity fee',
            self::CAT_TEST_CASH_42 => 'Test cash',
            self::CAT_ZEROING_OUT_BAL_60 => 'Zeroing due too high win rollback amount',
            self::CAT_REFUND_13 => 'Normal refund',
            self::CAT_BUDDY_TRANSFER_29 => 'Buddy transfer',
            self::CAT_JACKPOT_WIN_12 => 'Jackpot win',
            self::CAT_CHARGEBACK_9 => 'Chargeback',
            self::CAT_FRB_WINS => 'Wins (Free spins)',
            self::CAT_JP_WINS => 'Wins (Jackpot)',
            self::CAT_LIABILITY_ADJUST => 'Liability adjustment',
            self::CAT_BOOSTER_VAULT_TRANSFER => 'Booster Vault Transfer',
            self::CAT_CHARGEBACK_SETTLEMENT => 'Chargeback Settlement',
            self::CAT_SPORTSBOOK_VOIDS => 'Sportsbook voids',
            self::CAT_TAX_DEDUCTION => 'Tax Deductions',
            self::CAT_SPORTS_AGENT_FEE => 'Sports agent fee', // debit
            self::CAT_SPORTS_AGENT_FEE_REFUND => 'Sports agent fee refund' // credit
        ];

        return isset($map[$category_idx]) ? $map[$category_idx] : "Name not set";
    }

    public static function transactionTypeMap($transaction_type = null)
    {
        $map = [
            3 => self::CAT_DEPOSIT,
            8 => self::CAT_WITHDRAWAL,
            54 => self::CAT_BOS_REBUY_54,
            34 => self::CAT_BOS_BUYIN_34,
            52 => self::CAT_BOS_HOUSE_RAKE_52,
            38 => self::CAT_BOS_PRIZES_38,
            61 => self::CAT_BOS_CANCEL_BUYIN_61,
            63 => self::CAT_BOS_CANCEL_HOUSE_FEE_63,
            64 => self::CAT_BOS_CANCEL_REBUY_64,
            43 => self::CAT_INACTIVITY_43,
            13 => self::CAT_REFUND_13,
            50 => self::CAT_WITHDRAWAL_DEDUCTION_50,
            42 => self::CAT_TEST_CASH_42,
            9 => self::CAT_CHARGEBACK_9,
            60 => self::CAT_ZEROING_OUT_BAL_60,
            12 => self::CAT_JACKPOT_WIN_12,
            29 => self::CAT_BUDDY_TRANSFER_29,
            91 => self::CAT_LIABILITY_ADJUST,
            92 => self::CAT_CHARGEBACK_SETTLEMENT,
            100 => self::CAT_BOOSTER_VAULT_TRANSFER,
            101 => self::CAT_BOOSTER_VAULT_TRANSFER,
            104 => self::CAT_TAX_DEDUCTION,
            105 => self::CAT_TAX_DEDUCTION
        ];

        return is_null($transaction_type) ? $map : (isset($map[$transaction_type]) ? $map[$transaction_type] : "Not set");
    }

    /**
     * Returns the unallocated amount for the current and previous month.
     *
     * @param User $user
     * @return array[]
     */
    public function getLastTotalLastPeriod(User $user): array
    {
        //Current month total data
        $current_net = $this->getCurrentMonth($user)['net_liability'];

        $current_closing = $user->cash_balance;
        $current_closing += (int)ReplicaDB::shSelect(
            $user->id,
            'bonus_entries',
            "SELECT SUM(balance) AS bal FROM bonus_entries WHERE user_id = :user_id AND status  = 'active'",
            ['user_id' => $user->id],
            true
        )[0]->bal;

        //Previous month total data
        $previous_month['opening_balance'] = $user->repo->getBalance(Carbon::now()->subMonth()->startOfMonth());

        $previous_month['closing_balance'] = $user->repo->getBalance(Carbon::now()->startOfMonth());

        $previous_month['net_liability'] = ReplicaDB::shSelect($user->id, 'users_monthly_liability', "SELECT IFNULL(sum(amount),0) AS total
                          FROM users_monthly_liability
                          WHERE source = 0 AND year = :d_year AND month = :d_month AND user_id = :user_id", [
            'd_year' => Carbon::now()->subMonth()->year,
            'd_month' => Carbon::now()->subMonth()->month,
            'user_id' => $user->id
        ], true)[0]->total;

        $current = $previous_month['closing_balance'] + $current_net - $current_closing;

        $previous = $previous_month['opening_balance'] + $previous_month['net_liability'] - $previous_month['closing_balance'];

        if (empty($current) && empty($previous)) {
            return compact('previous', 'current');
        } else {
            return [
                'previous' => [
                    'opening' => $previous_month['opening_balance'],
                    'net' => $previous_month['net_liability'],
                    'closing' => $previous_month['closing_balance'],
                    'unallocated' => $previous
                ],
                'current' => [
                    'opening' => $previous_month['closing_balance'],
                    'net' => $current_net,
                    'closing' => $current_closing,
                    'unallocated' => $current
                ]
            ];
        }
    }

    /**
     * Adds a liability adjustment for current or previous month.
     *
     * @param User $user
     * @param int $amount
     * @param bool $for_previous_month
     * @param string $description
     * @return void
     */
    public static function addLiabilityAdjustment(User $user, int $amount, bool $for_previous_month, string $description): void
    {
        CashTransaction::sh($user->id)->create([
            'user_id' => $user->id,
            'currency' => $user->currency,
            'amount' => $amount,
            'balance' => 0,
            'description' => $description,
            'session_id' => 0,
            'transactiontype' => 91,
            'timestamp' => $for_previous_month ?
                Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d H:i:s') :
                Carbon::now()->format('Y-m-d H:i:s')
        ]);

        $province = cu($user->id)->getProvince();
        if ($for_previous_month) {
            $uml_insert = [
                'user_id' => $user->id,
                'year' => Carbon::now()->subMonth()->format('Y'),
                'month' => Carbon::now()->subMonth()->format('m'),
                'type' => $amount > 0 ? 'credit' : 'debit',
                'main_cat' => self::CAT_LIABILITY_ADJUST,
                'sub_cat' => $description,
                'transactions' => 1,
                'amount' => $amount,
                'currency' => $user->currency,
                'country' => $user->country,
                'source' => 0,
                'province' => empty($province) ? null : $province
            ];

            UserMonthlyLiability::bulkInsert([$uml_insert], 'user_id', DB::getMasterConnection());
            UserMonthlyLiability::create($uml_insert);
        }
    }

    /**
     * Returns true if the liabilities - unallocated entries has been analysed and processed for last month
     *
     * @return bool
     * @throws LiabilitiesProcessedException
     */
    public static function wereLiabilitiesProcessedForLastMonth(): bool
    {
        $misc_cache_name = 'liability-report-adjusted-month';
        $processed_until = phive()->getMiscCache($misc_cache_name);

        if (empty($processed_until) || !preg_match('/^\d{4}-\d{1,2}$/', $processed_until)) {
            throw new LiabilitiesProcessedException();
        }

        $processed_until = Carbon::createFromFormat('Y-m', $processed_until);

        $last_month = Carbon::now()->subMonth();
        return $last_month->isSameMonth($processed_until, true) || $processed_until->isAfter($last_month);
    }

    /*
     * Adds running balance and difference to a list of transactions
     * If we don't give an opening balance the method will calculate based on the first entry in the dataset
     *
     * @param $data
     * @param int|null $opening_balance
     * @return array
     */
    public static function calculateRunningBalance($data, int $opening_balance = null): array
    {
        // For wins and voids we store the balance after the transaction completed
        // Everything else we take the balance before the transaction started
        if (empty($opening_balance)) {
            $running_balance = strtolower($data[0]->type) == 'win' && $data[0]->more_info !== 'sportsbook' ||
            strtolower($data[0]->type) == 'void' && $data[0]->more_info !== 'sportsbook' ?
                $data[0]->balance : $data[0]->balance - $data[0]->amount;
        } else {
            $running_balance = $opening_balance;
        }

        $processed = [];
        foreach ($data as $row) {
            $liability_related = LiabilityRepository::isLiabilityRelatedTransaction($row);

            if ($liability_related) {
                $running_balance = $running_balance + $row->amount;
                $difference = strtolower($row->type) == 'win' && $row->more_info !== 'sportsbook' ||
                strtolower($row->type) == 'void' && $row->more_info !== 'sportsbook'?
                    $row->amount + $row->balance - $running_balance :
                    $row->balance - $running_balance;
            } else {
                $difference = $row->balance - $running_balance;
                $row->type = 'Misc Cash: ' . $row->type;
            }

            $row->running_balance = $running_balance;
            $row->difference = $difference;

            $processed[] = $row;
        }

        return $processed;
    }

    /**
     * Checks if a transaction should or should not deduct from the balance
     * for user's liability page and transaction list downloads.
     *
     * @param $transaction
     * @return bool
     */
    public function isLiabilityRelatedTransaction($transaction): bool
    {
        // 91 Liability Adjustment is an exception since it does not move the balance, but we always should consider
        // it as it did change the balance in order to it work in reports and running balance analysis.
        $liability_related_transactions = [1, 2, 3, 7, 8, 9, 12, 13, 29, 42, 43, 50, 60, 34, 38, 52, 54, 61, 63,
            64, 65, 31, 32, 66, 67, 69, 72, 73, 76, 77, 78, 79, 80, 81, 84, 85, 86, 90, 91, 100, 101, 103, 104, 105];

        if (!is_numeric($transaction->type_original) ||
            in_array($transaction->type_original, $liability_related_transactions) ||
            ($transaction->type_original === 14 && !strpos($transaction->desctiption, '-aid-')) ||
            (in_array($transaction->type_original, [15, 53]) &&
            !strpos($transaction->desctiption, '-cancelled') &&
            !strpos($transaction->desctiption, 'Super blocked so did not payout')) ||
            ($transaction->type_original === 7 && !strpos($transaction->desctiption, 'tournament'))
        ) {
            return true;
        }

        return false;
    }
    /**
     * Returns true if the liabilities - unallocated entries has been analysed and processed for the month
     *
     * @return bool|string
     */
    public function wereLiabilitiesProcessedForTheMonth()
    {

        $misc_cache_name = self::MISC_CACHE_LIABILITY_REPORT_ADJUST_MONTH;
        $current_value = phive()->getMiscCache($misc_cache_name);

        if (empty($current_value) || !preg_match('/^\d{4}-\d{1,2}$/', $current_value)) {
            throw new LiabilitiesProcessedException("{$misc_cache_name} value cannot be found or not in format Y-m");
        }

        $processed_until = Carbon::createFromFormat('Y-m', $current_value);

        return $processed_until->isAfter(Carbon::create($this->year, $this->month));
    }

    /**
     * @param $year
     * @param $month
     * @return bool|string
     */
    public function updateLiabilitiesProcessedMonth($year, $month)
    {
        if (!is_numeric($year) || !is_numeric($month)) {
            throw new UpdateLiabilitiesException("Year or month is not numeric.");
        }

        $misc_cache_name = self::MISC_CACHE_LIABILITY_REPORT_ADJUST_MONTH;
        $current_value = phive()->getMiscCache($misc_cache_name);

        if (empty($current_value) || !preg_match('/^\d{4}-\d{1,2}$/', $current_value)) {
            throw new UpdateLiabilitiesException("{$misc_cache_name} value cannot be found or not in format Y-m");
        }

        $allowed_value = Carbon::createFromFormat('Y-m', $current_value)->addMonth();
        $month_to_update_to = Carbon::create($year, $month);

        if (!$month_to_update_to->isSameMonth($allowed_value)) {
            throw new UpdateLiabilitiesException("Incorrect month provided. Not the next month after the latest processed month.");
        }

        phive()->miscCache('liability-report-adjusted-month', "{$year}-{$month}", true);

        return true;
    }

}
