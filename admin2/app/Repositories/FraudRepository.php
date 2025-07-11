<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 03/02/2016
 * Time: 12:31
 */

namespace App\Repositories;

use App\Classes\DateRange;
use App\Helpers\DataFormatHelper;
use App\Helpers\DownloadHelper;
use App\Models\Deposit;
use App\Models\UserDailyStatistics;
use Carbon\Carbon;
use App\Extensions\Database\FManager as DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class FraudRepository
{
    /** @var int $pagination Maximum number of elements that will display in each page list. */
    public $pagination;

    /** @var Application $app */
    protected $app;

    /** @var  TransactionsRepository $trans_repo */
    public $trans_repo;

    /**
     * FraudRepository constructor. Uses 40 elements per page by default.
     *
     * @param Application $app
     * @param int $pagination
     */
    public function __construct(Application $app, $pagination = 40)
    {
        $this->app = $app;
        $this->trans_repo = new TransactionsRepository($app);
        $this->pagination = $pagination;
    }

    /**
     * Returns a Builder object with the query on the high depositors of each day with a threshold given.
     *
     * @param Request $request
     * @param DateRange $date
     * @return Builder
     */
    public function getHighDepositsQuery(Request $request, DateRange $date)
    {
        $builder = DB::table('deposits as d')
            ->selectRaw("d.*, ROUND(d.amount/100, 0) as amount, IFNULL(us.value, 0) AS verified, u.country, u.username")
            ->leftJoin('users_settings AS us', function (JoinClause $join) {
                $join->on('us.user_id', '=', 'd.user_id')->where('us.setting', '=', 'verified');
            })
            ->leftJoin('users AS u', 'u.id', '=', 'd.user_id')
            ->whereRaw("d.user_id IN (SELECT user_id FROM (SELECT user_id, (sum(d.amount)/c.multiplier) AS sum_amount
                                                                            FROM deposits d
                                                                              LEFT JOIN currencies c ON c.code = d.currency
                                                                            WHERE d.timestamp BETWEEN ? AND ?
                                                                            GROUP BY user_id
                                                                            HAVING sum_amount > ?) s )", [
                $date->getStart('timestamp'),
                $date->getEnd('timestamp'),
                $request->get('amount', 2000) * 100
            ])->whereBetween('d.timestamp', $date->getWhereBetweenArray());

        if (!empty($request->get('country')) && strtolower($request->get('country') != 'all')) {
            $builder->where('u.country', $request->get('country'));
        }
        if (!empty($request->get('username'))) {
            $builder->where('u.username', 'like', "%{$request->get('username')}%");
        }

        return $builder;
    }

    /**
     * Populate the array and export it into a CSV file as streamed response.
     *
     * @param array $high_deposits
     * @param string $name
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportHighDeposits($high_deposits, $name)
    {
        $records[] = ['User ID', 'Username', 'Verified', 'Country', 'Status', 'Payment Method', 'Scheme', 'Card Hash', 'Amount', 'Currency', 'Internal Transaction Id',
            'Time', 'Date', 'External Transaction Id', 'Recorded IP'];

        foreach ($high_deposits as $deposit) {
            $records[] = [
                $deposit->user_id,
                $deposit->username,
                $deposit->verified == 1 ? 'Yes' : 'No',
                $deposit->country,
                $deposit->status,
                $deposit->dep_type,
                $deposit->scheme,
                $deposit->card_hash,
                $deposit->amount,
                $deposit->currency,
                $deposit->id,
                Carbon::parse($deposit->timestamp)->toTimeString(),
                Carbon::parse($deposit->timestamp)->toDateString(),
                $deposit->ext_id,
                $deposit->ip_num,
            ];
        }
        return DownloadHelper::streamAsCsv($this->app, $records, $name);
    }

    /**
     * List of customers depositing and requesting withdrawals, having wagered less than 100%. Total amount of deposits
     * from second last withdrawal to last withdrawal versus wagering.
     *
     * Returns an array with all the queried non turned-over withdrawals. It checks the oldest bet in the 'bets' table
     * and always query as that timestamp as the oldest one. It gets from the 'money_laundry' table as the percentage
     * ratio between two withdrawals using the deposits versus the wagered amount.
     *
     * @param array $query_data
     * @return mixed
     */
    public function getNonTurnedOver($query_data)
    {
        $threshold = $query_data['percent'] / 100;

        $last_bet_query = DB::shsSelect('bets', "SELECT MIN(created_at) AS datetime FROM bets");

        if (is_null($last_bet_query[0]->datetime)) {
            $condition_timestamp = Carbon::now()->subMonth(3)->format('Y-m-d H:i:s');
        } else {
            $last_bet = Carbon::parse($last_bet_query[0]->datetime);

            if ($query_data['date'] == 'No filter' || is_null($query_data['date']) || $query_data['date'] == '') {
                $condition_timestamp = $last_bet_query[0]->datetime;
            } elseif (Carbon::createFromFormat('Y-m-d', $query_data['date']) < $last_bet) {
                $condition_timestamp = $last_bet_query[0]->datetime;
            } else {
                $condition_timestamp = "{$query_data['date']} 00:00:00";
            }
        }

        return DB::shsSelect('money_laundry', "SELECT
                        ml.*,
                        u.username                            AS username,
                        us.value                              AS verified,
                        u.country                             AS country,
                        w1.amount                             AS w_amount1,
                        w2.amount                             AS w_amount2,
                        w1.payment_method                     AS w_method1,
                        w2.payment_method                     AS w_method2,
                        ROUND((ml.wager_sum / ml.dep_sum), 2) AS percent,
                        ml.dep_sum - ml.wager_sum             AS non_turned
                      FROM money_laundry ml
                        LEFT JOIN pending_withdrawals w1
                          ON ml.w_id1 = w1.id
                        LEFT JOIN pending_withdrawals w2
                          ON ml.w_id2 = w2.id
                        LEFT JOIN users u
                          ON ml.user_id = u.id
                        LEFT JOIN users_settings us
                          ON ml.user_id = us.user_id AND us.setting = 'verified'
                        WHERE ml.dep_sum > 0
                        AND ml.w_stamp2 > :dateparam
                        AND ml.w_stamp2 = (SELECT MAX(sub_ml.w_stamp2) FROM money_laundry sub_ml WHERE ml.user_id = sub_ml.user_id)
                        HAVING (ml.wager_sum / ml.dep_sum) < :threshold
                      ORDER BY ml.w_stamp2 DESC", ['dateparam' => $condition_timestamp, 'threshold' => $threshold]);
    }

    /**
     * Populate the array and export it into a CSV file as streamed response.
     *
     * @param array $non_turned
     * @param string $name
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportNonTurnedOver($non_turned, $name)
    {
        $records[] = ['User ID', 'Username', 'Verified', 'Country', 'Currency', 'Withd. 1 amount', 'Withd. 1 method',
            'Withd. 1 timestamp', 'Withd. 2 amount', 'Withd. 2 method', 'Withd. 2 timestamp', 'Number of deposits',
            'Deposited sum', 'Wagered sum', 'Non Turned-over amount', 'Non Turned-over Percentage'];

        foreach ($non_turned as $element) {
            $records[] = [
                $element->user_id,
                $element->username,
                $element->verified == 1 ? 'Yes' : 'No',
                $element->country,
                $element->currency,
                $element->w_amount1 / 100,
                $element->w_method1,
                $element->w_stamp1,
                $element->w_amount2 / 100,
                $element->w_method2,
                $element->w_stamp2,
                $element->dep_cnt,
                round($element->dep_sum / 100, 2),
                round($element->wager_sum / 100, 2),
                $element->non_turned / 100,
                $element->percent * 100
            ];
        }
        return DownloadHelper::streamAsCsv($this->app, $records, $name);
    }

    /**
     * List of transactions made by customers depositing with fully anonymous methods (currently Paysafe) within 24hours
     *
     * @param array $query_data
     * @return Builder
     */
    public function getAnonymousMethodsQuery($query_data)
    {
        return Deposit::with(['user', 'user.settings' => function ($query) {
            $query->where('setting', 'verified');
        }])
            ->where('dep_type', '=', 'paysafe')
            ->whereBetween('timestamp', [$query_data['start_date'], $query_data['end_date']])
            ->orderBy('timestamp', 'asc');
    }

    /**
     * Populate the array and export it into a CSV file as streamed response.
     *
     * @param array $anonymous_methods
     * @param string $name
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportAnonymousMethods($anonymous_methods, $name)
    {
        $records[] = ['User ID', 'Username', 'Verified', 'Country', 'Status', 'Amount', 'Currency', 'Internal Transaction Id',
            'Time', 'Date', 'External Transaction Id', 'Recorded IP'];

        foreach ($anonymous_methods as $deposit) {
            $records[] = [
                $deposit->user_id,
                $deposit->user->username,
                $deposit->user->settings()->where('setting', 'verified')->first()->value == 1 ? 'Yes' : 'No',
                $deposit->user->country,
                $deposit->status,
                $deposit->amount / 100,
                $deposit->currency,
                $deposit->id,
                Carbon::createFromFormat('Y-m-d H:i:s', $deposit->timestamp)->toTimeString(),
                Carbon::createFromFormat('Y-m-d H:i:s', $deposit->timestamp)->toDateString(),
                $deposit->ext_id,
                $deposit->ip_num,
            ];
        }

        return DownloadHelper::streamAsCsv($this->app, $records, $name);
    }

    /**
     * List of transactions made by customers who depositing and withdrawing with 2 or more different methods within.
     * Deposit type 'emp' must be rewritten as 'wirecard'.
     *
     * @param array $query_data
     * @return array
     */
    public function getMultiMethodsTransactions($query_data)
    {

        return DB::shsSelect(
            'deposits',
            "SELECT
              gr.user_id                    AS user_id,
              u.username                    AS username,
              u.country                     AS country,
              us.value                      AS verified,
              de.id                         AS internal_id,
              'deposit'                     AS type,
              de.dep_type                   AS payment_method,
              de.card_hash                  AS card_hash,
              de.timestamp                  AS transaction_time,
              de.status                     AS status,
              de.currency                   AS currency,
              de.ext_id                     AS ext_id,
              de.amount                     AS amount,
              CONVERT(de.ip_num USING utf8) AS ip_num
            FROM deposits de JOIN (
                                    SELECT dm.user_id AS user_id
                                    FROM (
                                           SELECT
                                             CASE WHEN d.dep_type = 'emp'
                                               THEN 'wirecard'
                                             ELSE d.dep_type END AS pay_method,
                                             d.card_hash         AS card_hash,
                                             d.user_id           AS user_id
    
                                           FROM deposits d
                                           WHERE d.timestamp BETWEEN :start_date1 AND :end_date1
                                           UNION
                                           SELECT
                                             p.payment_method      AS pay_method,
                                             SUBSTR(p.scheme, -19) AS card_hash,
                                             p.user_id             AS user_id
                                           FROM pending_withdrawals p
                                           WHERE p.approved_at BETWEEN :start_date2 AND :end_date2) dm
                                    GROUP BY dm.user_id
                                    HAVING COUNT(DISTINCT dm.pay_method, dm.card_hash) >= :threshold1) gr
                ON gr.user_id = de.user_id AND de.timestamp BETWEEN :start_date3 AND :end_date3
              LEFT JOIN users u ON u.id = gr.user_id
              LEFT JOIN users_settings us ON gr.user_id = us.user_id AND us.setting = 'verified'

            UNION
    
            SELECT
              pe.user_id                    AS user_id,
              u.username                    AS username,
              u.country                     AS country,
              us.value                      AS verified,
              pe.id                         AS internal_id,
              'withdrawal'                  AS type,
              pe.payment_method             AS payment_method,
              SUBSTR(pe.scheme, -19)        AS card_hash,
              pe.approved_at                AS transaction_time,
              pe.status                     AS status,
              pe.currency                   AS currency,
              pe.ext_id                     AS ext_id,
              pe.amount                     AS amount,
              CONVERT(pe.ip_num USING utf8) AS ip_num
            FROM pending_withdrawals pe JOIN (
                                               SELECT dm.user_id AS user_id
                                               FROM (
                                                      SELECT
                                                        CASE WHEN d.dep_type = 'emp'
                                                          THEN 'wirecard'
                                                        ELSE d.dep_type END AS pay_method,
                                                        d.card_hash         AS card_hash,
                                                        d.user_id           AS user_id
    
                                                      FROM deposits d
                                                      WHERE d.timestamp BETWEEN :start_date4 AND :end_date4
                                                      UNION
                                                      SELECT
                                                        p.payment_method      AS pay_method,
                                                        SUBSTR(p.scheme, -19) AS card_hash,
                                                        p.user_id             AS user_id
                                                      FROM pending_withdrawals p
                                                      WHERE
                                                        p.approved_at BETWEEN :start_date5 AND :end_date5) dm
                                               GROUP BY dm.user_id
                                               HAVING COUNT(DISTINCT dm.pay_method, dm.card_hash) >= :threshold2) gr
                ON gr.user_id = pe.user_id AND pe.approved_at BETWEEN :start_date6 AND :end_date6
              LEFT JOIN users u ON u.id = gr.user_id
              LEFT JOIN users_settings us ON gr.user_id = us.user_id AND us.setting = 'verified'",
            [
                'start_date1' => $query_data['start_date'],
                'end_date1' => $query_data['end_date'],
                'start_date2' => $query_data['start_date'],
                'end_date2' => $query_data['end_date'],
                'start_date3' => $query_data['start_date'],
                'end_date3' => $query_data['end_date'],
                'start_date4' => $query_data['start_date'],
                'end_date4' => $query_data['end_date'],
                'start_date5' => $query_data['start_date'],
                'end_date5' => $query_data['end_date'],
                'start_date6' => $query_data['start_date'],
                'end_date6' => $query_data['end_date'],
                'threshold1' => $query_data['count'],
                'threshold2' => $query_data['count'],
            ]
        );
    }

    /**
     * Populate the array and export it into a CSV file as streamed response.
     *
     * @param \Illuminate\Support\Collection $multi_methods
     * @param string $name
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportMultiMethodsTransactions($multi_methods, $name)
    {
        $records[] = ['Transaction Type', 'Payment Method', 'Card Hash', 'User ID', 'Username', 'Verified', 'Country', 'Status',
            'Amount', 'Currency', 'Internal Transaction Id', 'Time', 'Date', 'External Transaction Id', 'Recorded IP'];

        foreach ($multi_methods as $transaction) {
            $records[] = [
                $transaction->type,
                $transaction->payment_method,
                $transaction->card_hash,
                $transaction->user_id,
                $transaction->username,
                $transaction->verified == 1 ? 'Yes' : 'No',
                $transaction->country,
                $transaction->status,
                $transaction->amount / 100,
                $transaction->currency,
                $transaction->internal_id,
                Carbon::createFromFormat('Y-m-d H:i:s', $transaction->transaction_time)->toTimeString(),
                Carbon::createFromFormat('Y-m-d H:i:s', $transaction->transaction_time)->toDateString(),
                $transaction->ext_id,
                $transaction->ip_num,
            ];
        }
        return DownloadHelper::streamAsCsv($this->app, $records, $name);
    }

    /**
     * Process a transaction list, firstly removing all the paysafe transactions and later on collapsing all the duplicated
     * transactions.
     *
     * @param \Illuminate\Support\Collection $transaction_list
     * @return \Illuminate\Support\Collection
     */
    public function collapseDuplicatedTransactions($transaction_list)
    {
        $paysafe_transactions = $transaction_list->where('payment_method', 'paysafe');

        $non_paysafe_transactions = $transaction_list->reject(function ($item) {
            return $item->payment_method == 'paysafe';
        });

        $unique_non_paysafe = $non_paysafe_transactions->unique(function ($item) {
            return $item->user_id . $item->payment_method . $item->card_hash;
        });

        $processed_transactions = $unique_non_paysafe->merge($paysafe_transactions->values()->all());

        return $processed_transactions->sortBy(function ($item) {
            return $item->user_id;
        });
    }

    /**
     * List of customers within 24 hours (00:00 – 23:59) that have won or lost more than a threshold. A currency
     * conversion must be done.
     *
     * @param Request $request
     * @param DateRange $date_range
     * @param string $operator
     * @param $default_amount
     * @return array
     */
    public function getBigPlayers(Request $request, DateRange $date_range, $operator, $default_amount)
    {
        $amount = $request->get('amount', $default_amount) * 100;
        if ($operator == '<') {
            $amount = $amount * -1;
        }

        $uds_extra_where = '';
        $bw_extra_where = '';
        if (!empty($request->get('country')) && strtolower($request->get('country')) != 'all') {
            $uds_extra_where .= " AND uds.country = '{$request->get('country')}'";
            $bw_extra_where .= " AND u.country = '{$request->get('country')}'";
        }
        if (!empty($request->get('username'))) {
            $uds_extra_where .= " AND uds.username LIKE '%{$request->get('username')}%'";
            $bw_extra_where .= " AND u.username LIKE '%{$request->get('username')}%'";
        }

        if ($date_range->getEnd()->isToday() || $date_range->getEnd()->isFuture()) {
            if ($date_range->getStart()->isSameDay($date_range->getEnd())) {
                $start_date_bw = $start_date_uds = $end_date_uds = Carbon::now()->toDateString();
            } else {
                $start_date_uds = $date_range->getStart('date');
                $end_date_uds = Carbon::now()->subDay()->toDateString();
                $start_date_bw = Carbon::now()->toDateString();
            }

            $end_date_ngr = Carbon::now()->toDateString();

            $query = "
                SELECT
                    submain.wins,
                    submain.bets,
                    submain.gross,
                    submain.user_id,
                    u.username,
                    u.country,
                    u.currency,
                    us.value AS verified,
                    currencies.multiplier,
                    submain.gross - ifnull(rewards.amount,0) + ifnull(failed_rewards.amount,0) AS ngr
                FROM (
                    SELECT
                        sub.user_id,
                        sum(sub.t_wins)  AS wins,
                        sum(sub.t_bets)  AS bets,
                        sum(sub.t_gross) AS gross,
                        currency,
                        currencies.multiplier
                    FROM (
                        SELECT -- users daily stats sub query
                            uds.user_id,
                            sum(uds.wins) + sum(uds.frb_wins)   AS t_wins,
                            sum(uds.bets)                       AS t_bets,
                            sum(uds.gross)                      AS t_gross,
                            uds.country,
                            uds.currency
                        FROM users_daily_stats AS uds
                        WHERE uds.date BETWEEN '{$start_date_uds}' AND '{$end_date_uds}' 
                        {$uds_extra_where}
                        GROUP BY uds.user_id
                        UNION
                        SELECT -- bets sub query
                            bets.user_id,
                            0                     AS t_wins,
                            sum(bets.amount)      AS t_bets,
                            sum(bets.amount)      AS t_gross,
                            u.country,
                            bets.currency
                        FROM bets
                        LEFT JOIN users AS u ON bets.user_id = u.id
                        WHERE created_at >= '{$start_date_bw} 00:00:00'
                        {$bw_extra_where}
                        GROUP BY bets.user_id
                        UNION
                        SELECT -- wins sub query
                            wins.user_id,
                            sum(wins.amount)      AS t_wins,
                            0                     AS t_bets,
                            sum(wins.amount) * -1 AS t_gross,
                            u.country,
                            wins.currency
                        FROM wins
                        LEFT JOIN users AS u ON wins.user_id = u.id
                        WHERE created_at >= '{$start_date_bw} 00:00:00'
                        {$bw_extra_where}
                        GROUP BY wins.user_id
                    ) AS sub
                    LEFT JOIN currencies ON sub.currency = currencies.code
                    GROUP BY sub.user_id
                    HAVING (gross / currencies.multiplier) {$operator} {$amount}
                ) AS submain
                LEFT JOIN currencies ON submain.currency = currencies.code
                LEFT JOIN users AS u ON submain.user_id = u.id
                LEFT JOIN users_settings AS us ON submain.user_id = us.user_id AND us.setting = 'verified'
                LEFT JOIN (
                    SELECT 
                        ct.user_id,
                        SUM(ABS(ct.amount)) AS amount
                    FROM cash_transactions ct
                    WHERE ct.timestamp BETWEEN '{$start_date_uds}'
                                        AND '{$end_date_ngr}' 
                    AND transactiontype IN (
                        14,32,31,51,66,69,74,77,80,82,84,85,86
                    )
                    GROUP BY ct.user_id
                ) AS rewards ON submain.user_id = rewards.user_id
                LEFT JOIN (
                    SELECT
                        ct.user_id, 
                        SUM(ABS(ct.amount)) AS amount
                    FROM cash_transactions ct
                    LEFT JOIN bonus_types bt ON bt.id = ct.bonus_id
                    WHERE ct.timestamp BETWEEN '{$start_date_uds}'
                                        AND '{$end_date_ngr}' 
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
                    GROUP BY ct.user_id
                )  AS failed_rewards ON submain.user_id = failed_rewards.user_id
                GROUP BY submain.user_id";

            return DB::shsSelect('bets', $query);
        } else {
            if ($date_range->getStart()->isSameDay($date_range->getEnd())) {
                $date_where = " = '{$date_range->getStart('date')}'";
            } else {
                $date_where = " BETWEEN '{$date_range->getStart('timestamp')}' AND '{$date_range->getEnd('timestamp')}'";
            }
            $query = "
                SELECT
                    uds.user_id,
                    sum(uds.wins) + sum(uds.frb_wins)  AS wins,
                    sum(uds.bets)  AS bets,
                    sum(uds.gross) AS gross,
                    uds.country,
                    uds.username,
                    uds.currency,
                    us.value AS verified,
                    currencies.multiplier,
                    sum(uds.gross) - ifnull(rewards.amount,0) + ifnull(rewards.amount,0) as ngr
                FROM users_daily_stats AS uds
                    LEFT JOIN currencies ON uds.currency = currencies.code
                    LEFT JOIN users_settings AS us ON uds.user_id = us.user_id AND us.setting = 'verified'
                    LEFT JOIN (
                        SELECT 
                            ct.user_id,
                            SUM(ABS(ct.amount)) AS amount
                        FROM cash_transactions ct
                        WHERE ct.timestamp {$date_where}
                        AND transactiontype IN (
                          14,32,31,51,66,69,74,77,80,82,84,85,86
                        )
                        GROUP BY ct.user_id
                    ) AS rewards ON uds.user_id = rewards.user_id
                    LEFT JOIN (
                        SELECT
                            ct.user_id, 
                            SUM(ABS(ct.amount)) AS amount
                        FROM cash_transactions ct
                        LEFT JOIN bonus_types bt ON bt.id = ct.bonus_id
                        WHERE ct.timestamp {$date_where}  
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
                        GROUP BY ct.user_id
                    )  AS failed_rewards ON uds.user_id = failed_rewards.user_id
                WHERE uds.date {$date_where} 
                $uds_extra_where
                GROUP BY uds.user_id
                HAVING gross / currencies.multiplier {$operator} {$amount}";

            return DB::shsSelect('users_daily_stats', $query);
        }
    }

    /**
     * List of customers within 24 hours (00:00 – 23:59) that have deposited more than a threshold. A currency
     * conversion must be done.
     *
     * @param Request $request
     * @param DateRange $date_range
     * @return array
     */
    public function getBigDepositors(Request $request, DateRange $date_range)
    {
        $amount = $request->get('amount', 3000) * 100;
        $country = $request->get('country', 'all');
        
        $uds_extra_where = $bw_extra_where = '';
        if (!empty($country)) {
            if (is_array($country) ) {
                $country = DataFormatHelper::arrayToSql($country);
                $uds_extra_where .= " AND uds.country IN $country";
                $bw_extra_where .= " AND u.country IN $country";
            } elseif (strtolower($country) != 'all') {
                $uds_extra_where .= " AND uds.country = '$country'";
                $bw_extra_where .= " AND u.country = '$country'";
            }
        }
        if (!empty($request->get('username'))) {
            $uds_extra_where .= " AND uds.username LIKE '%{$request->get('username')}%'";
            $bw_extra_where .= " AND u.username LIKE '%{$request->get('username')}%'";
        }

        if ($date_range->getEnd()->isToday() || $date_range->getEnd()->isFuture()) {
            if ($date_range->getStart()->isSameDay($date_range->getEnd())) {
                $start_date_bw = $start_date_uds = $end_date_uds = Carbon::now()->toDateString();
            } else {
                $start_date_uds = $date_range->getStart('date');
                $end_date_uds = Carbon::now()->subDay()->toDateString();
                $start_date_bw = Carbon::now()->toDateString();
            }

            $query = "SELECT
                  submain.deposits,
                  submain.user_id,
                  u.username,
                  u.country,
                  u.currency,
                  us.value AS verified,
                  currencies.multiplier
                FROM (
                       SELECT
                         sub.user_id,
                         sum(sub.t_deposits)  AS deposits,
                         currency,
                         currencies.multiplier
                       FROM
                         (SELECT -- users daily stats sub query
                            uds.user_id,
                            sum(uds.deposits)   AS t_deposits,
                            uds.country,
                            uds.currency
                          FROM `users_daily_stats` AS `uds`
                          WHERE `uds`.`date` BETWEEN '{$start_date_uds}' AND '{$end_date_uds}' 
                          {$uds_extra_where}
                           GROUP BY uds.user_id
                         UNION
                          SELECT -- deposits sub query
                            deposits.user_id,
                            sum(deposits.amount)   AS t_deposits,
                            u.country,
                            deposits.currency
                          FROM deposits
                            LEFT JOIN `users` AS `u` ON deposits.`user_id` = `u`.`id`
                          WHERE timestamp >= '{$start_date_bw} 00:00:00'
                            {$bw_extra_where}
                          GROUP BY deposits.user_id
                         ) AS sub
                       LEFT JOIN `currencies` ON sub.`currency` = `currencies`.`code`
                         GROUP BY sub.user_id
                       HAVING (deposits / currencies.multiplier) > {$amount}) AS submain
                  LEFT JOIN `currencies` ON submain.`currency` = `currencies`.`code`
                  LEFT JOIN `users` AS `u` ON submain.`user_id` = `u`.`id`
                  LEFT JOIN `users_settings` AS `us` ON submain.`user_id` = `us`.`user_id` AND `us`.`setting` = 'verified'
                GROUP BY submain.user_id";

            return DB::shsSelect('deposits', $query);
        } else {
            if ($date_range->getStart()->isSameDay($date_range->getEnd())) {
                $date_where = " = '{$date_range->getStart('date')}'";
            } else {
                $date_where = " BETWEEN '{$date_range->getStart('date')}' AND '{$date_range->getEnd('date')}'";
            }
            $query = "SELECT
                  uds.user_id,
                  sum(deposits)  AS deposits,
                  uds.country,
                  uds.username,
                  uds.currency,
                  us.value AS verified,
                  currencies.multiplier
                FROM `users_daily_stats` AS `uds`
                  LEFT JOIN `currencies` ON uds.`currency` = `currencies`.`code`
                  LEFT JOIN `users_settings` AS `us` ON uds.`user_id` = `us`.`user_id` AND `us`.`setting` = 'verified'
                WHERE `uds`.`date` $date_where
                  $uds_extra_where
                GROUP BY uds.user_id
                HAVING deposits / currencies.multiplier > {$amount}";

            return DB::shsSelect('users_daily_stats', $query);
        }
    }

    /**
     * Populate the array and export it into a CSV file as streamed response.
     *
     * @param mixed $big_players
     * @param string $name Report filename
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportBigPlayers($big_players, $name)
    {
        $records[] = ['User ID', 'Username', 'Verified', 'Country', 'Currency', 'Wagered Sum', 'Wins Sum', 'NGR'];

        foreach ($big_players as $transaction) {
            $records[] = [
                $transaction->user_id,
                $transaction->username,
                $transaction->verified == 1 ? 'Yes' : 'No',
                $transaction->country,
                $transaction->currency,
                $transaction->bets / 100,
                $transaction->wins / 100,
                $transaction->ngr / 100,
            ];
        }

        return DownloadHelper::streamAsCsv($this->app, $records, $name);
    }

    /**
     * List of customers participating in a Battle of Slots tournament within a date range. All the freeroll tournaments
     * must be excluded. The tournament number of spins is stored on 'xspin_info' and it must be multiply by the column
     * 'spin_m' as it is a spin multiplier. In order to get the spin ratio, the number of spins left by an user in a
     * tournament entry must be divided by the total spins of the related tournament.
     *
     * @param array $query_data
     * @return array
     */
    public function getDailyGladiators($query_data)
    {
        if ($query_data['end_date'] == Carbon::now()->format('Y-m-d')) {
            if ($query_data['start_date'] == Carbon::now()->format('Y-m-d')){
                $live_data = $this->getTodayOnlyGladiatorsData();
            } else{
                $live_data = DB::shsSelect('bets_mp', "SELECT
                                      user_id, username, alias, country, verified,
                                      count(DISTINCT e_id)                                                    AS daily_battles,
                                      SUM(CASE WHEN result_place = 1 THEN 1 ELSE 0 END)                       AS daily_win_count,
                                      SUM(xspin_info * spin_m)                                                AS total_spins,
                                      SUM(spins_left)                                                         AS total_spins_left,
                                      100 - ROUND(SUM(spins_left) * 100 / SUM(xspin_info * spin_m), 2)        AS spin_ratio
                                    FROM (SELECT
                                            bm.user_id,
                                            u.username,
                                            u.alias,
                                            u.country,
                                            us.value  AS verified,
                                            bm.e_id,
                                            te.result_place,
                                            te.spins_left,
                                            t.xspin_info,
                                            t.spin_m,
                                            bm.t_id
                                          FROM bets_mp bm
                                            LEFT JOIN tournament_entries te ON te.id = bm.e_id
                                            LEFT JOIN tournaments t ON t.id = bm.t_id
                                            LEFT JOIN users u ON u.id = bm.user_id
                                            LEFT JOIN users_settings us ON bm.user_id = us.user_id AND us.setting = 'verified'
                                          WHERE bm.created_at BETWEEN :start_date AND :end_date
                                                AND t.category <> 'freeroll'
                                          GROUP BY bm.user_id, bm.e_id) a
                                    GROUP BY user_id", [
                    'start_date' => Carbon::now()->startOfDay()->format('Y-m-d H:i:s'),
                    'end_date' => Carbon::now()->endOfDay()->format('Y-m-d H:i:s')
                ]);
            }

            if (count($live_data) == 0) {
                return $this->getAllTimeGladiatorsData($query_data);
            }

            $tmp = [];
            foreach ($live_data as $live_elem) {
                $tmp[$live_elem->user_id] = $live_elem;
            }

            $res = [];

            if($query_data['start_date'] != Carbon::now()->format('Y-m-d')) {
                $extra_select = ",(SELECT SUM(te3.spins_left) AS spins_left
                           FROM users_daily_stats_mp mp3
                             LEFT JOIN tournaments t3
                               ON t3.id = mp3.t_id
                             LEFT JOIN tournament_entries te3
                               ON te3.id = mp3.e_id
                           WHERE mp3.user_id = mp.user_id
                                 AND t3.category <> 'freeroll')                                   AS lifetime_spins_left
                          ,(SELECT SUM(t3.xspin_info * t.spin_m) AS total_spins
                            FROM users_daily_stats_mp mp3
                              LEFT JOIN tournaments t3
                                ON t3.id = mp3.t_id
                              LEFT JOIN tournament_entries te3
                                ON te3.id = mp3.e_id
                            WHERE mp3.user_id = mp.user_id
                                  AND t3.category <> 'freeroll')                                   AS lifetime_total_spins";

                //previous days until today
                $cached_data = $this->getAllTimeGladiatorsData($query_data, $extra_select);

                //start and (end_date = today)
                foreach ($cached_data as $cached_elem) {
                    if (!empty($tmp[$cached_elem->user_id])) {
                        $te = $tmp[$cached_elem->user_id];
                        unset($tmp[$cached_elem->user_id]);
                        $cached_elem->daily_battles += $te->daily_battles;
                        $cached_elem->lifetime_battles += $te->lifetime_battles + $te->daily_battles;
                        $cached_elem->daily_win_count += $te->daily_win_count;
                        $cached_elem->lifetime_win_count += $te->lifetime_win_count + $te->daily_win_count;
                        $cached_elem->total_spins += $te->total_spins;
                        $cached_elem->total_spins_left += $te->total_spins_left;
                        $cached_elem->spin_ratio = round(100 - ($cached_elem->total_spins_left + $te->total_spins_left) * 100 / ($cached_elem->total_spins + $te->total_spins), 2);
                        $cached_elem->lifetime_spin_ratio = round(100 - ($cached_elem->lifetime_spins_left + $te->total_spins_left) * 100 / ($cached_elem->lifetime_total_spins + $te->total_spins), 2);
                    }
                    $res[] = $cached_elem;
                }
                unset($cached_data);
            }

            //today only
            if (count($tmp) > 0) {
                foreach ($tmp as $today_only_elem) {
                    $new_line = new \stdClass();
                    $new_line->user_id = $today_only_elem->user_id;
                    $new_line->username = $today_only_elem->username;
                    $new_line->battle_alias = $today_only_elem->alias;
                    $new_line->country = $today_only_elem->country;
                    $new_line->verified = $today_only_elem->verified;
                    $new_line->daily_battles = $today_only_elem->daily_battles;
                    $new_line->lifetime_battles = $today_only_elem->lifetime_battles + $today_only_elem->daily_battles;
                    $new_line->daily_win_count = $today_only_elem->daily_win_count;
                    $new_line->lifetime_win_count = $today_only_elem->lifetime_win_count + $today_only_elem->daily_win_count;
                    $new_line->total_spins = $today_only_elem->total_spins;
                    $new_line->total_spins_left = $today_only_elem->total_spins_left;
                    $new_line->spin_ratio = round(100 - $today_only_elem->total_spins_left * 100 / $today_only_elem->total_spins, 2);
                    $new_line->lifetime_spin_ratio = round(100 - ($today_only_elem->lifetime_spins_left + $today_only_elem->total_spins_left) * 100 / ($today_only_elem->lifetime_total_spins + $today_only_elem->total_spins), 2);
                    $res[] = $new_line;
                }
            }
            return $res;
        } else {
            return $this->getAllTimeGladiatorsData($query_data);
        }
    }

    private function getTodayOnlyGladiatorsData(){
        return DB::shsSelect('bets_mp', "SELECT
                                      a.user_id, username, alias, country, verified,
                                      count(DISTINCT e_id)                                                    AS daily_battles,
                                      COALESCE(lifetime_battles.battles,0,lifetime_battles.battles)          AS lifetime_battles,															
                                      SUM(CASE WHEN result_place = 1 THEN 1 ELSE 0 END)                       AS daily_win_count,
                                      COALESCE(lifetime_win_count.sub_win_count, 0, lifetime_win_count.sub_win_count) AS lifetime_win_count,                     
                                      SUM(xspin_info * spin_m)                                                AS total_spins,
                                      SUM(a.spins_left)                                                         AS total_spins_left,
                                      100 - ROUND(SUM(a.spins_left) * 100 / SUM(xspin_info * spin_m), 2)        AS spin_ratio,
                                      COALESCE(lifetime_total_spins.total_spins, 0, lifetime_total_spins.total_spins) AS lifetime_total_spins,
                                      COALESCE(lifetime_spins_left.spins_left, 0, lifetime_spins_left.spins_left) AS lifetime_spins_left
                                    FROM (SELECT
                                            bm.user_id,
                                            u.username,
                                            u.alias,
                                            u.country,
                                            us.value  AS verified,
                                            bm.e_id,
                                            te.result_place,
                                            te.spins_left,
                                            t.xspin_info,
                                            t.spin_m,
                                            bm.t_id
                                          FROM bets_mp bm
                                            LEFT JOIN tournament_entries te ON te.id = bm.e_id
                                            LEFT JOIN tournaments t ON t.id = bm.t_id
                                            LEFT JOIN users u ON u.id = bm.user_id
                                            LEFT JOIN users_settings us ON bm.user_id = us.user_id AND us.setting = 'verified'
                                          WHERE bm.created_at BETWEEN :start_date AND :end_date
                                          AND t.category <> 'freeroll'
                                          GROUP BY bm.user_id, bm.e_id) a
                                    LEFT JOIN (SELECT SUM(te3.spins_left) AS spins_left, mp3.user_id
                                              FROM users_daily_stats_mp mp3
                                                LEFT JOIN tournaments t3 ON t3.id = mp3.t_id
                                                LEFT JOIN tournament_entries te3 ON te3.id = mp3.e_id
                                              WHERE t3.category <> 'freeroll'
									          GROUP BY mp3.user_id) AS lifetime_spins_left ON lifetime_spins_left.user_id = a.user_id
									LEFT JOIN (SELECT SUM(t3.xspin_info * t3.spin_m) AS total_spins, mp3.user_id
                                              FROM users_daily_stats_mp mp3
                                                LEFT JOIN tournaments t3 ON t3.id = mp3.t_id
                                                LEFT JOIN tournament_entries te3 ON te3.id = mp3.e_id
                                              WHERE t3.category <> 'freeroll'
									          GROUP BY mp3.user_id) AS lifetime_total_spins ON lifetime_total_spins.user_id = a.user_id
									LEFT JOIN  (SELECT COUNT(*) as battles, mp1.user_id
									            FROM users_daily_stats_mp mp1
                                                  LEFT JOIN tournaments t1 ON t1.id = mp1.t_id
                                                WHERE t1.category <> 'freeroll'
									            GROUP BY mp1.user_id) AS lifetime_battles ON lifetime_battles.user_id = a.user_id
									LEFT JOIN (SELECT SUM(CASE WHEN te2.result_place = 1 THEN 1 ELSE 0 END) AS sub_win_count, mp2.user_id
                                              FROM users_daily_stats_mp mp2
                                                LEFT JOIN tournaments t2 ON t2.id = mp2.t_id
                                                LEFT JOIN tournament_entries te2 ON te2.id = mp2.e_id
                                              WHERE t2.category <> 'freeroll'
									          GROUP BY mp2.user_id)  AS lifetime_win_count ON lifetime_win_count.user_id = a.user_id
                                    GROUP BY a.user_id", [
            'start_date' => Carbon::now()->startOfDay()->format('Y-m-d H:i:s'),
            'end_date' => Carbon::now()->endOfDay()->format('Y-m-d H:i:s')
        ]);

    }

    private function getAllTimeGladiatorsData($query_data, $extra_select = '')
    {
        return DB::shsSelect('users_daily_stats_mp', "SELECT
                          mp.user_id                                                              AS user_id,
                          mp.username                                                             AS username,
                          us.value                                                                AS verified,
                          u.alias                                                                 AS battle_alias,
                          mp.country                                                              AS country,
                          COUNT(mp.e_id)                                                          AS daily_battles,
                          (SELECT COUNT(*) FROM users_daily_stats_mp mp1
                             LEFT JOIN tournaments t1
                               ON t1.id = mp1.t_id
                           WHERE mp.user_id = mp1.user_id
                                 AND t1.category <> 'freeroll')                                   AS lifetime_battles,
                          SUM(CASE WHEN te.result_place = 1 THEN 1 ELSE 0 END)                    AS daily_win_count,
                          (SELECT SUM(CASE WHEN te2.result_place = 1 THEN 1 ELSE 0 END) AS sub_win_count
                           FROM users_daily_stats_mp mp2
                             LEFT JOIN tournaments t2
                               ON t2.id = mp2.t_id
                             LEFT JOIN tournament_entries te2
                               ON te2.id = mp2.e_id
                           WHERE mp2.user_id = mp.user_id
                                 AND t2.category <> 'freeroll')                                   AS lifetime_win_count,
                          SUM(t.xspin_info * t.spin_m)                                            AS total_spins,
                          SUM(te.spins_left)                                                      AS total_spins_left,
                          100 - ROUND(SUM(te.spins_left) * 100 / SUM(t.xspin_info * t.spin_m), 2) AS spin_ratio,
                          (SELECT 100 - ROUND(SUM(te3.spins_left) * 100 / SUM(t3.xspin_info * t.spin_m), 2) AS spin_ratio
                           FROM users_daily_stats_mp mp3
                             LEFT JOIN tournaments t3
                               ON t3.id = mp3.t_id
                             LEFT JOIN tournament_entries te3
                               ON te3.id = mp3.e_id
                           WHERE mp3.user_id = mp.user_id
                                 AND t3.category <> 'freeroll')                                   AS lifetime_spin_ratio
                           $extra_select
                        FROM users_daily_stats_mp mp
                          LEFT JOIN tournaments t
                            ON t.id = mp.t_id
                          LEFT JOIN tournament_entries te
                            ON te.id = mp.e_id
                          LEFT JOIN users u
                            ON mp.user_id = u.id
                          LEFT JOIN users_settings us
                            ON mp.user_id = us.user_id AND us.setting = 'verified'
                        WHERE mp.date BETWEEN :start_date AND :end_date
                              AND t.category <> 'freeroll'
                        GROUP BY mp.user_id
                        ORDER BY spin_ratio ASC", ['start_date' => $query_data['start_date'], 'end_date' => $query_data['end_date']]);
    }

    /**
     * Populate the array and export it into a CSV file as streamed response.
     *
     * @param array $daily_gladiators
     * @param string $name Report filename
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportDailyGladiators($daily_gladiators, $name)
    {
        $records[] = ['User ID', 'Username', 'Verified', 'Battle Alias', 'Country', 'Lifetime number of battles participated',
            'Number of battles participated', 'Lifetime win / loss ratio %', 'Win / loss ratio %',
            'Lifetime spin ratio %', 'Spins', 'Spins Left', 'Spin ratio %'];

        foreach ($daily_gladiators as $transaction) {
            $records[] = [
                $transaction->user_id,
                $transaction->username,
                $transaction->verified == 1 ? 'Yes' : 'No',
                $transaction->battle_alias,
                $transaction->country,
                $transaction->lifetime_battles,
                $transaction->daily_battles,
                $transaction->lifetime_battles / 100 * $transaction->lifetime_win_count,
                $transaction->daily_battles / 100 * $transaction->daily_win_count,
                $transaction->lifetime_spin_ratio,
                $transaction->total_spins,
                $transaction->total_spins_left,
                $transaction->spin_ratio,
            ];
        }
        return DownloadHelper::streamAsCsv($this->app, $records, $name);
    }

    /**
     * Options:
     * 1. Only users daily stats
     * 2. Only live data (today)
     * 3. Live data + users daily stats
     *
     * @param $request
     * @param DateRange $date_range
     * @return array
     */
    public function getBonusAbusersData(Request $request, DateRange $date_range)
    {
        /** @var Carbon $start */
        $start = $date_range->getStart();
        /** @var Carbon $end */
        $end = $date_range->getEnd();

        $param_bindings = [
            'start_date' => $date_range->getStart('date'),
            'end_date' => $date_range->getEnd('date'),
            'bonus_threshold' => $request->get('bonus-threshold', 4),
            'wager_threshold' => $request->get('wager-threshold', 1000) * 100,
        ];

        if (empty($request->get('transactiontype'))) {
            if (!$start->isPast() || !$end->isPast()) {
                $j = $k = 2;
            } else {
                $j = $k = 0;
            }
        } else {
            if (!$start->isPast() || !$end->isPast()) {
                $j = $k = 2;
            } else {
                $j = $k = 1;
            }
        }

        $username_sql = $country_sql = '';
        if (!empty($request->get('country'))) {
            for ($i = 0; $i <= $j; $i++) {
                $in_clause_array = [];
                foreach ($request->get('country') as $key => $value) {
                    $in_key = "country_{$key}_{$i}";
                    $in_clause_array[":$in_key"] = $value;
                    $param_bindings[$in_key] = $value;
                }
                $in_clause_string = implode(" , ", array_keys($in_clause_array));
                $country_sql[$i] = " AND u.country IN ($in_clause_string)";
            }
        }
        if (!empty($request->get('username'))) {
            for ($i = 0; $i <= $k; $i++) {
                $in_clause_array = [];
                foreach (explode(',', $request->get('username')) as $key => $value) {
                    $in_key = "username_{$key}_{$i}";
                    $in_clause_array[":$in_key"] = $value;
                    $param_bindings[$in_key] = $value;
                }
                $in_clause_string = implode(" , ", array_keys($in_clause_array));
                $username_sql[$i] = " AND u.username IN ($in_clause_string)";
            }
        }

        if (empty($request->get('transactiontype')) || $request->get('transactiontype') == 'All') {
            if (!$start->isPast() || !$end->isPast()) {
                $extra_select = "sum(uds.bets) + sq.sub_bet_sum                                                AS wagered_sum,
                                 sum(uds.rewards) + sub_rewards_sum                                            AS bonus_sum,
                                 (sum(uds.rewards) + sub_rewards_sum) * 100 / (sum(uds.bets) + sq.sub_bet_sum) AS bonus_percentage";
                $extra_join = "INNER JOIN (
                                   SELECT
                                     user_id,
                                     sum(amount) AS sub_bet_sum
                                   FROM bets
                                   LEFT JOIN users u ON u.id = bets.user_id
                                   WHERE created_at BETWEEN :start_timestamp_1 AND :end_timestamp_1
                                    {$username_sql[0]} {$country_sql[0]}
                                   GROUP BY user_id
                                 ) AS sq ON sq.user_id = uds.user_id
                                 INNER JOIN (
                                   SELECT
                                     user_id,
                                     sum(amount) AS sub_rewards_sum
                                   FROM cash_transactions
                                   LEFT JOIN users u ON u.id = cash_transactions.user_id
                                   WHERE timestamp BETWEEN :start_timestamp_2 AND :end_timestamp_2
                                      AND transactiontype IN (14, 32, 31, 51, 66, 69, 74, 77, 80, 82, 84, 85, 86)
                                      {$username_sql[1]} {$country_sql[1]}
                                   GROUP BY user_id
                                 ) AS sq2 ON sq2.user_id = uds.user_id";
                $param_bindings['start_timestamp_1'] = Carbon::now()->startOfDay()->toDateTimeString();
                $param_bindings['start_timestamp_2'] = Carbon::now()->startOfDay()->toDateTimeString();
                $param_bindings['end_timestamp_1'] = Carbon::now()->endOfDay()->toDateTimeString();
                $param_bindings['end_timestamp_2'] = Carbon::now()->endOfDay()->toDateTimeString();
            } else {
                $extra_select = "sum(uds.bets)                          AS wagered_sum,
                                 sum(uds.rewards)                       AS bonus_sum,
                                 sum(uds.rewards) * 100 / sum(uds.bets) AS bonus_percentage";
                $extra_join = '';
            }
        } else {
            $param_bindings['transactiontype'] = $request->get('transactiontype');
            if (!$start->isPast() || !$end->isPast()) {
                $extra_select = "sum(uds.bets) + sq.sub_bet_sum                             AS wagered_sum,
                                 sq2.sub_rewards_sum                                            AS bonus_sum,
                                 sq2.sub_rewards_sum * 100 / (sum(uds.bets) + sq.sub_bet_sum)   AS bonus_percentage";
                $extra_join = "INNER JOIN (
                                   SELECT
                                     user_id,
                                     sum(amount) AS sub_bet_sum
                                   FROM bets
                                   LEFT JOIN users u ON u.id = bets.user_id
                                   WHERE created_at BETWEEN :start_timestamp_1 AND :end_timestamp_1
                                    {$username_sql[0]} {$country_sql[0]}
                                   GROUP BY user_id
                                 ) AS sq ON sq.user_id = uds.user_id
                                 INNER JOIN (
                                   SELECT
                                     user_id,
                                     sum(amount) AS sub_rewards_sum
                                   FROM cash_transactions
                                   LEFT JOIN users u ON u.id = cash_transactions.user_id
                                   WHERE timestamp BETWEEN :start_timestamp_3 AND :end_timestamp_3
                                     AND transactiontype = :transactiontype
                                     {$username_sql[1]} {$country_sql[1]}
                                   GROUP BY user_id
                                 ) AS sq2 ON sq2.user_id = uds.user_id";
                $param_bindings['start_timestamp_1'] = Carbon::now()->startOfDay()->toDateTimeString();
                $param_bindings['end_timestamp_1'] = Carbon::now()->endOfDay()->toDateTimeString();
                $param_bindings['start_timestamp_3'] = $param_bindings['start_date'];
                $param_bindings['end_timestamp_3'] = $param_bindings['end_date'];
            } else {
                $extra_select = "sum(uds.bets)                                  AS wagered_sum,
                                 sq3.sub_rewards_sum                       AS bonus_sum,
                                 sq3.sub_rewards_sum * 100 / sum(uds.bets) AS bonus_percentage";
                $extra_join = "INNER JOIN (
                                   SELECT
                                     user_id,
                                     sum(amount) AS sub_rewards_sum
                                   FROM cash_transactions
                                   LEFT JOIN users u ON u.id = cash_transactions.user_id
                                   WHERE timestamp BETWEEN :start_timestamp_3 AND :end_timestamp_3
                                     AND transactiontype = :transactiontype
                                    {$username_sql[0]} {$country_sql[0]}
                                   GROUP BY user_id
                                 ) AS sq3 ON sq3.user_id = uds.user_id";
                $param_bindings['start_timestamp_3'] = $param_bindings['start_date'];
                $param_bindings['end_timestamp_3'] = $param_bindings['end_date'];
            }
        }


        $query_res = DB::shsSelect('users_daily_stats', "SELECT
              uds.user_id                                                                   AS user_id,
              u.username                                                                    AS username,
              u.country                                                                     AS country,
              u.currency                                                                    AS currency,
              us.value                                                                      AS verified,
              $extra_select
            FROM users_daily_stats uds
              LEFT JOIN users u ON u.id = uds.user_id
              LEFT JOIN users_settings us ON uds.user_id = us.user_id AND us.setting = 'verified'
              $extra_join
            WHERE uds.date BETWEEN :start_date AND :end_date {$username_sql[$j]} {$country_sql[$k]}
            GROUP BY uds.user_id
            HAVING bonus_percentage > :bonus_threshold AND wagered_sum > :wager_threshold
            ORDER BY bonus_percentage DESC", $param_bindings);

        return [
            'list' => $query_res,
            'bonus_trans_types' => ['All', 14, 32, 31, 51, 66, 69, 74, 77, 80, 82, 84, 85, 86]
        ];
    }

    public function exportFailedDeposits($list, $name)
    {
        $records[] = ['Date', 'User ID', 'Status', 'Method', 'Internal ID', 'External ID', 'Reason'];

        foreach ($list as $transaction) {
            $records[] = [
                $transaction['created_at'],
                $transaction['user_id'],
                $transaction['status'],
                $transaction['supplier'],
                $transaction['id'],
                $transaction['reference_id'],
                implode(',', $transaction['reasons'])
            ];
        }
        return DownloadHelper::streamAsCsv($this->app, $records, $name);
    }

    /**
     * Returns the url query used by the download button.
     *
     * @param array $query_data
     * @return string as the url query
     */
    public function generateDownloadPath($query_data)
    {
        $query_data['export'] = 1;
        return '?' . http_build_query($query_data);
    }

}