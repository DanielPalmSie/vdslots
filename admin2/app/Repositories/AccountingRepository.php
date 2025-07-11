<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 16/03/16
 * Time: 16:48
 */

namespace App\Repositories;

use App\Classes\DateRange;
use App\Classes\Mts;
use App\Classes\PR;
use App\Constants\Networks;
use App\Extensions\Database\Connection\Connection;
use App\Helpers\DataFormatHelper;
use App\Helpers\DownloadHelper;
use App\Helpers\PaginationHelper;
use App\Models\Config;
use App\Models\MiscCache;
use App\Models\User;
use App\Models\UserDailyBalance;
use App\Models\UserSetting;
use Carbon\Carbon;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\ReplicaFManager as ReplicaDB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use JsonException;
use Silex\Application;
use Supplier;
use Symfony\Component\HttpFoundation\Request;
use App\Repositories\LiabilityRepository as LRepo;

class AccountingRepository
{
    private $cached_data = [];

    private const LAUNCH_SPORTSBOOK_PROJECT = '23.07.2021';

    public function getConsolidationQueries(Application $app, Request $request, DateRange $date_range)
    {
        $deposits_made_query = ReplicaDB::table('deposits AS d')
            ->selectRaw(" d.id                     AS internal_id,
                          'deposit'                AS type,
                          d.status                 AS status,
                          u.username               AS user,
                          ROUND(d.amount / 100, 2) AS amount,
                          d.currency               AS currency,
                          d.timestamp              AS trans_time,
                          ex.transaction_data      AS external_data
                          ")
            ->leftJoin('external_transactions AS ex', function ($join) {
                $join->on('d.ext_id', '=', 'ex.ext_id')
                    ->where('ex.transaction_account', '=', 'deposits');
            })
            ->leftJoin('users as u', 'd.user_id', '=', 'u.id')
            ->where('d.dep_type', $request->get('provider'))
            ->whereBetween('d.timestamp', [$date_range->getStart('timestamp'), $date_range->getEnd('timestamp')]);

        $deposits_reported_query = ReplicaDB::table('external_transactions AS ex')
            ->selectRaw(" d.id                     AS internal_id,
                          'deposit'                AS type,
                          d.status                 AS status,
                          u.username               AS user,
                          ROUND(d.amount / 100, 2) AS amount,
                          d.currency               AS currency,
                          ex.ext_timestamp         AS trans_time,
                          ex.transaction_data      AS external_data
                          ")
            ->leftJoin('deposits AS d', function ($join) {
                $join->on('d.ext_id', '=', 'ex.ext_id');
            })
            ->leftJoin('users as u', 'd.user_id', '=', 'u.id')
            ->where('ex.transaction_account', 'deposits')
            ->where('ex.method', $request->get('provider'))
            ->whereBetween('d.timestamp', [$date_range->getStart('timestamp'), $date_range->getEnd('timestamp')]);

        return compact('deposits_made_query', 'deposits_reported_query');
    }

    private function createDynamicCaseStatementForMethod(
        $paymentFilterService,
        string $tableAlias = 'w',
        string $defaultColumn = 'payment_method'
    ): string
    {
        $cases = [];
        foreach ($paymentFilterService->customViaMapping as $subMethod => $via) {
            $cases[] = "WHEN {$tableAlias}.{$defaultColumn} = '{$subMethod}' THEN '{$via}'";
        }

        $cases[] = "ELSE {$tableAlias}.{$defaultColumn}";
        return "CASE " . implode(" ", $cases) . " END COLLATE utf8_general_ci AS method";
    }

    private function createDynamicCaseStatementForSubMethod(
        $paymentFilterService,
        string $tableAlias = 'w',
        string $type = 'withdrawal',
        string $masterColumn = 'payment_method',
        string $defaultColumn = 'wallet'
    ): string
    {
        $cases = [];

        foreach ($paymentFilterService->columnUsedForSubMethod(null, $type) as $column => $methods) {
            $methodsList = implode("','", $methods);
            $cases[] = "WHEN {$tableAlias}.{$masterColumn} IN ('{$methodsList}') THEN {$tableAlias}.{$column}";
        }

        $subMethodValueOverrideMap = $paymentFilterService->overrideSubmethodValue;
        if ($subMethodValueOverrideMap) {
            foreach ($subMethodValueOverrideMap as $method => $subMethodValueDetail) {
                $cases[] = "WHEN {$tableAlias}.{$masterColumn} = '{$method}'
                    AND {$tableAlias}.{$defaultColumn} = '{$subMethodValueDetail['subMethodOverrideValue']}'
                    THEN '{$subMethodValueDetail['subMethod']}'";
            }
        }

        $cases[] = "ELSE {$tableAlias}.{$defaultColumn}";
        return "CASE " . implode(" ", $cases) . " END COLLATE utf8_general_ci AS submethod";
    }

    private function applyDynamicMethodFilter(
        $paymentFilterService,
        Builder $query,
        string  $method,
        ?string  $submethod,
        string  $tableAlias = 'w',
        string  $defaultColumn = 'payment_method'
    ): void
    {
        $columnMapping = $paymentFilterService->customViaMapping;

        if (in_array($method, $columnMapping)) {
            if (!empty($method) && empty($submethod)) {
                $values = [];
                foreach ($columnMapping as $sub_method => $via) {
                    if ($method === $via) {
                        $values = array_merge($values, [$via, $sub_method]);
                    }
                }
                $query->whereIn("{$tableAlias}.{$defaultColumn}", array_unique($values));
            } else {
                foreach ($columnMapping as $sub_method => $via) {
                    if ($method === $via && $sub_method === $submethod) {
                        $query->where(function (Builder $query) use ($sub_method, $via, $defaultColumn, $tableAlias) {
                            $query->orWhere("{$tableAlias}.{$defaultColumn}", '=', $via)
                                ->orWhere("{$tableAlias}.{$defaultColumn}", '=', $sub_method);
                        });
                        break;
                    }
                }
            }
        } else {
            $query->where("{$tableAlias}.{$defaultColumn}", $method);
        }
    }

    private function applyDynamicSubmethodFilter(
        $paymentFilterService,
        Builder $query,
        string  $submethod,
        string  $tableAlias = 'w',
        string  $type = 'withdrawal',
        string  $defaultColumn = 'wallet'
    ): void
    {
        $overrideValue = array_column(
            $paymentFilterService->overrideSubmethodValue,
            'subMethodOverrideValue',
            'subMethod'
        )[$submethod] ?? null;

        if ($overrideValue !== null) {
            $query->where("{$tableAlias}.{$defaultColumn}", $overrideValue);
            return;
        }

        $columns = $paymentFilterService->columnUsedForSubMethod(null, $type) + [$defaultColumn => []];
        $query->where(function (Builder $query) use ($submethod, $columns, $tableAlias) {
            foreach (array_keys($columns) as $column) {
                $query->orWhere("{$tableAlias}.{$column}", '=', "{$submethod}");
            }
        });
    }

    private function addPspDetailsJoinsAndCase(
        Builder $query,
        string  $aliasPrefix = 'd',
        string  $methodTypeColumn = 'dep_type',
        string  $defaultDetailsColumn = 'scheme'
    ): string
    {
        $pspSettings = [
            'paypal' => [
                'fields' => ['paypal_payer_id', 'paypal_email'],
                'separator' => "\n"
            ],
        ];

        $detailsCases = [];

        foreach ($pspSettings as $psp => $config) {
            $settings = $config['fields'];
            $separator = $config['separator'] ?? ' ';

            $alias = "{$aliasPrefix}_{$psp}";

            $orderByField = "FIELD(setting, '" . implode("','", $settings) . "')";

            $query->leftJoinSub(
                function ($subQuery) use ($settings, $orderByField, $separator) {
                    $subQuery->from('users_settings')
                        ->selectRaw(
                            "user_id, GROUP_CONCAT(value ORDER BY $orderByField SEPARATOR '$separator') as psp_details"
                        )
                        ->whereIn('setting', $settings)
                        ->groupBy('user_id');
                },
                $alias,
                "{$alias}.user_id",
                '=',
                "{$aliasPrefix}.user_id"
            );

            $detailsCases[] = "WHEN {$aliasPrefix}.{$methodTypeColumn} = '$psp' THEN {$alias}.psp_details COLLATE utf8_general_ci";
        }

        return "CASE " . implode(" ", $detailsCases) . " ELSE {$aliasPrefix}.{$defaultDetailsColumn} END";
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param $query_params
     * @param bool $initial
     * @param callable $filterCallback The callback function tailored for customized filtering of records prior to pagination.
     * @return array|Builder
     */
    public function getTransferStatsList(Request $request, Application $app, $query_params, $initial = true, $filterCallback = null)
    {
        if (!empty($request->get('node'))) {
            $connection = replicaDatabaseSwitcher() == false ? $request->get('node') : 'replica_'.$request->get('node');
            $direct = true;
        } else {
            $connection = replicaDatabaseSwitcher(true);
            $direct = false;
        }

        if ($request->get('show') == 'undone') {
            $transaction_type = 103;
            $select_name = 'Undone Withdrawal';
        } else {
            $transaction_type = 13;
            $select_name = 'Withdrawal Refund';
        }

        $dep_query = ReplicaDB::table('deposits AS d', $connection, $direct)
            ->leftJoin('users AS ud', 'ud.id', '=', 'd.user_id')
            ->leftJoin('ip_log', function (JoinClause $join) {
                $join->on('d.id', '=', 'ip_log.tr_id')->where('tag', '=', 'deposits');
            })
            ->leftJoin('users AS udip', 'udip.id', '=', 'ip_log.actor')
            ->leftJoin('users_settings AS main_province', function (JoinClause $join) {
                $join->on( 'main_province.user_id', '=', 'ud.id')->where('main_province.setting', '=', 'main_province');
            })
            ->where('d.status', $request->get('status', 'approved'))
            ->whereBetween('d.timestamp', [$query_params['start_date'], $query_params['end_date']]);

        $with_query = ReplicaDB::table('pending_withdrawals AS w', $connection, $direct)
            ->leftJoin('users AS uw', 'uw.id', '=', 'w.user_id')
            ->leftJoin('users AS aw', 'aw.id', '=', 'w.approved_by')
            ->leftJoin('ip_log AS ip_log_manual', function (JoinClause $join) {
                $join->on('w.id', '=', 'ip_log_manual.tr_id')->where('ip_log_manual.tag', '=', 'manual_withdrawals');
            })
            ->leftJoin('users AS uwmip', 'uwmip.id', '=', 'ip_log_manual.actor')
            ->leftJoin('ip_log', function (JoinClause $join) {
                $join->on('w.id', '=', 'ip_log.tr_id')->where('ip_log.tag', '=', 'pending_withdrawals');
            })
            ->leftJoin('users AS uwip', 'uwip.id', '=', 'ip_log.actor')
            ->leftJoin('users_settings AS main_province', function (JoinClause $join) {
                $join->on( 'main_province.user_id', '=', 'uw.id')->where('main_province.setting', '=', 'main_province');
            })
            ->when($this->shouldApplyDateRange($query_params), function ($query) use ($query_params) {
                return $query->whereBetween('w.timestamp', [$query_params['start_date'], $query_params['end_date']]);
            });

        $cash_query = ReplicaDB::table('cash_transactions AS ct', $connection, $direct)
            ->leftJoin('users AS uc', 'uc.id', '=', 'ct.user_id')
            ->leftJoin('pending_withdrawals AS pc', 'pc.id', '=', 'ct.parent_id')
            ->leftJoin('users_settings AS main_province', function (JoinClause $join) {
                $join->on( 'main_province.user_id', '=', 'uc.id')->where('main_province.setting', '=', 'main_province');
            })
            ->whereBetween('ct.timestamp', [$query_params['start_date'], $query_params['end_date']])
            ->where('ct.transactiontype', $transaction_type);

        if (strcmp($request->get('converted'), 'yes') == 0) {
            $multiplier = '/ IFNULL(fx_rates.multiplier,1)';
            $dep_query->leftJoin('fx_rates', function (JoinClause $join) {
                $join->on('fx_rates.code', '=', 'd.currency')->where('fx_rates.day_date', '=', 'DATE(ct.timestamp)');
            });
            $with_query->leftJoin('fx_rates', function (JoinClause $join) {
                $join->on('fx_rates.code', '=', 'w.currency')->where('fx_rates.day_date', '=', 'DATE(ct.timestamp)');
            });
            $cash_query->leftJoin('fx_rates', function (JoinClause $join) {
                $join->on('fx_rates.code', '=', 'ct.currency')->where('fx_rates.day_date', '=', 'DATE(ct.timestamp)');
            });
        } else {
            $multiplier = '';
        }

        $paymentFilterService = $app['payments_method_submethod_filter_service'];

        if ($query_params['only_users_id'] == 1) {
            $dep_query->selectRaw('DISTINCT ud.id');
            $with_query->selectRaw('DISTINCT uw.id');
            $cash_query->selectRaw('DISTINCT uc.id');
        } else {
            $depMethodCase = $this->createDynamicCaseStatementForMethod($paymentFilterService, 'd', 'dep_type');
            $depSubMethodCase = $this->createDynamicCaseStatementForSubMethod($paymentFilterService, 'd', 'deposit', 'dep_type', 'scheme');
            $depositDetailsCase = $this->addPspDetailsJoinsAndCase($dep_query);
            $dep_query->selectRaw("d.timestamp    AS date,
                      d.timestamp       AS exec_date,
                      ud.username,
                      ud.email          AS user_email,
                      ud.id             AS user_id,
                      $depMethodCase,
                      CASE WHEN ip_log.id IS NULL THEN 'Deposit' ELSE 'Manual Deposit' END  AS type,
                      ROUND(d.amount/100 $multiplier,2)                                     AS amount,
                      ROUND(d.real_cost/100 $multiplier,2)                                  AS fee,
                      ROUND(d.deducted_amount/100 $multiplier,2)                            AS deducted,
                      d.currency        AS currency,
                      $depositDetailsCase AS details,
                      d.card_hash       AS card_hash,
                      ud.country        AS country,
                      main_province.value AS province,
                      d.status,
                      d.ext_id,
                      d.loc_id,
                      d.id,
                      ''                    AS stuck,
                      ''                    AS description,
                      d.mts_id,
                      $depSubMethodCase,
                      ip_log.actor          AS actor_id,
                      IFNULL(ip_log.actor_username,ip_log.actor) AS actor,
                      '' AS wallet");

            $wdMethodCase = $this->createDynamicCaseStatementForMethod($paymentFilterService);
            $wdSubMethodCase = $this->createDynamicCaseStatementForSubMethod($paymentFilterService);
            $with_query->selectRaw("w.timestamp    AS date,
                      w.approved_at     AS exec_date,
                      uw.username,
                      uw.email          AS user_email,
                      uw.id             AS user_id,
                      $wdMethodCase,
                      CASE WHEN ip_log_manual.id IS NULL THEN 'Withdrawal' ELSE 'Manual Withdrawal' END  AS type,
                      ROUND(w.amount/100 $multiplier,2) AS amount,
                      ROUND(w.real_cost/100 $multiplier,2) AS fee,
                      ROUND(w.deducted_amount/100 $multiplier,2) AS deducted,
                      w.currency        AS currency,
                      CONCAT_WS(' ', w.net_account, w.net_email, w.mb_email, w.bank_name, w.bank_account_number, w.iban) AS details,
                      w.scheme          AS card_hash,
                      uw.country             AS country,
                      main_province.value    AS province,
                      w.status,
                      w.ext_id,
                      w.loc_id,
                      w.id,
                      w.stuck,
                      w.description,
                      w.mts_id,
                      $wdSubMethodCase,
                      w.approved_by     AS actor_id,
                      IFNULL(ip_log.actor_username,w.approved_by) AS actor,
                      w.wallet");
            $cash_query->selectRaw("ct.timestamp    AS date,
                      ct.timestamp          AS exec_date,
                      uc.username,
                      uc.email              AS user_email,
                      uc.id                 AS user_id,
                      pc.payment_method     AS method,
                      '{$select_name}'          AS type,
                      ROUND(ct.amount/100 $multiplier,2) AS amount,
                      ct.currency           AS currency,
                      ''                    AS fee,
                      ''                    AS deducted,
                      ct.description        AS details,
                      ''                    AS card_hash,
                      ''                    AS status,
                      ''                    AS ext_id,
                      ''                    AS loc_id,
                      uc.country            AS country,
                      main_province.value   AS province,
                      ct.id,
                      ''                    AS stuck,
                      ''                    AS description,
                      ''                    AS mts_id,
                      ''                    AS submethod,
                      ''                    AS actor_id,
                      ''                    AS actor,
                      ''                    AS wallet");
        }

        if (!empty($request->get('country')) && $request->get('country') != 'all') {
            $dep_query->where('ud.country', $request->get('country'));
            $with_query->where('uw.country', $request->get('country'));
            $cash_query->where('uc.country', $request->get('country'));
        }

        if(!empty($request->get('province'))) {
            $dep_query->whereIn('main_province.value', $request->get('province'));
            $with_query->whereIn('main_province.value', $request->get('province'));
            $cash_query->whereIn('main_province.value', $request->get('province'));
        }

        $userID = $request->get('user-id');
        if (!empty($userID)) {
            $dep_query->where('d.user_id', 'like', "%{$userID}%");
            $with_query->where('w.user_id', 'like', "%{$userID}%");
            $cash_query->where('ct.user_id', 'like', "%{$userID}%");
        }

        if (!empty($request->get('actor'))) {
            $actors = User::where('username', 'like', "%{$request->get('actor')}%")->get()->pluck('id')->all();
            $dep_query->whereIn('udip.id', $actors);
            $with_query->whereIn('w.approved_by', $actors);
        }

        $method = $request->get('method');
        $subMethod = $request->get('submethod');

        if (!empty($method)) {
            $this->applyDynamicMethodFilter($paymentFilterService, $dep_query, $method, $subMethod, 'd', 'dep_type');
            $this->applyDynamicMethodFilter($paymentFilterService, $with_query, $method, $subMethod);
            $cash_query->where('pc.payment_method', $method);
        }

        if (!empty($subMethod)) {
            $this->applyDynamicSubmethodFilter($paymentFilterService, $dep_query, $subMethod, 'd', 'deposit', 'scheme');
            $this->applyDynamicSubmethodFilter($paymentFilterService, $with_query, $subMethod);
        }

        if (!empty($request->get('ext-id'))) {
            $bind = "%{$request->get('ext-id')}%";
            $dep_query->where(function (Builder $query) use ($bind) {
                $query->orWhere('d.ext_id', 'like', $bind)
                    ->orWhere('d.loc_id', 'like', $bind);
            });
            $with_query->where(function (Builder $query) use ($bind) {
                $query->orWhere('w.ext_id', 'like', $bind)
                    ->orWhere('w.loc_id', 'like', $bind);
            });
        }

        if (!empty($request->get('int-id'))) {
            $dep_query->where('d.id', $request->get('int-id'));
            $with_query->where('w.id', $request->get('int-id'));
            $cash_query->where('ct.id', $request->get('int-id'));
        }

        if (!empty($request->get('min'))) {
            $dep_query->where('d.amount', '>=', $request->get('min'));
            $with_query->where('w.amount', '>=', $request->get('min'));
            $cash_query->where('ct.amount', '>=', $request->get('min'));
        }

        if (!empty($request->get('max'))) {
            $dep_query->where('d.amount', '<=', $request->get('max'));
            $with_query->where('w.amount', '<=', $request->get('max'));
            $cash_query->where('ct.amount', '<=', $request->get('max'));
        }

        if (!empty($request->get('currency'))) {
            $dep_query->where('d.currency', $request->get('currency'));
            $with_query->where('w.currency', $request->get('currency'));
            $cash_query->where('ct.currency', $request->get('currency'));
        }

        if ($request->get('stuck') != '') {
            $with_query->where('w.stuck', $request->get('stuck'));
        }

        if ($request->get('status') === 'pending|processing') {
            $with_query->whereIn('w.status', ['pending', 'processing']);
        } else {
            $with_query->where('w.status', $request->get('status', $query_params['status'] ?? 'approved'));
        }

        if (!empty($request->get('account'))) {
            $bind = "%{$request->get('account')}%";
            $dep_query->where(function (Builder $query) use ($bind, $depositDetailsCase) {
                $query->orWhere('d.scheme', 'like', $bind)
                    ->orWhere('d.card_hash', 'like', $bind)
                    ->orWhereRaw("$depositDetailsCase LIKE ?", [$bind]);
            });

            $with_query->where(function (Builder $query) use ($bind) {
                $query->orWhere('w.net_account', 'like', $bind)
                    ->orWhere('w.net_email', 'like', $bind)
                    ->orWhere('w.mb_email', 'like', $bind)
                    ->orWhere('w.bank_name', 'like', $bind)
                    ->orWhere('w.bank_account_number', 'like', $bind)
                    ->orWhere('w.scheme', 'like', $bind)
                    ->orWhere('w.iban', 'like', $bind)
                    ->orWhere('w.wallet', 'like', $bind);
            });
        }

        $manual_dep = clone $dep_query;
        $manual_dep->whereNotNull('ip_log.id');
        $manual_with = clone $with_query;
        $manual_with->whereNotNull('ip_log_manual.id');

        if (@$query_params['return_query'] == true) {
            if ($request->get('show') == 'deposits') {
                return $dep_query;
            } elseif ($request->get('show') == 'manualdeposits') {
                return $manual_dep;
            } elseif ($request->get('show') == 'manualwithdrawals') {
                return $manual_with;
            } elseif ($request->get('show') == 'withdrawals') {
                return $with_query;
            } elseif ($request->get('show') == 'refunds') {
                return $cash_query;
            } elseif ($request->get('show') == 'undone') {
                return $cash_query;
            } else {
                return $dep_query->union($with_query);
            }
        } else {
            $pagination_default = ['length' => 25, 'order' => ['column' => 'date', 'dir' => 'DESC']];
            if ($request->get('show') == 'deposits') {
                $paginator = new PaginationHelper($dep_query, $request, $pagination_default);
            } elseif ($request->get('show') == 'manualdeposits') {
                $paginator = new PaginationHelper($manual_dep, $request, $pagination_default);
            } elseif ($request->get('show') == 'manualwithdrawals') {
                $paginator = new PaginationHelper($manual_with, $request, $pagination_default);
            } elseif ($request->get('show') == 'withdrawals') {
                $paginator = new PaginationHelper(
                    is_callable($filterCallback)
                        ? $this->filterDataBeforePagination($request, $with_query, $filterCallback)
                        : $with_query,
                    $request,
                    $pagination_default
                );
            } elseif ($request->get('show') == 'refunds') {
                $paginator = new PaginationHelper($cash_query, $request, $pagination_default);
            } elseif ($request->get('show') == 'undone') {
                $paginator = new PaginationHelper($cash_query, $request, $pagination_default);
            } else {
                $paginator = PaginationHelper::makeFromUnion($dep_query, $with_query, $request, $pagination_default);
            }
            $page = $paginator->getPage($initial);

            $page['data'] = (new Mts($app))->addTransactionDetails(collect($page['data']));

            $page['data'] = $this->processTransactionDetails($page['data']);

            return $page;
        }
    }

    /**
     * Filter the query results before pagination using the provided callback.
     *
     * @param Request $request The original request
     * @param Builder $query The query to filter
     * @param callable $filterCallback The callback function for filtering
     * @return Collection|Builder The filtered collection or original query
     */
    public function filterDataBeforePagination(Request $request, Builder $query, callable $filterCallback)
    {
        return call_user_func($filterCallback, $request, $query);
    }

    public function shouldApplyDateRange($requestOrParams): bool {
        if (is_object($requestOrParams)) {
            return !empty($requestOrParams->get('date-range'));
        }

        return isset($requestOrParams['start_date'], $requestOrParams['end_date'])
            && !empty($requestOrParams['start_date'])
            && !empty($requestOrParams['end_date']);
    }

    /**
     * Filter pending Withdrawal data based before pagination.
     *
     * @param Request $request The request object.
     * @param Builder|SupportCollection $queryOrCollection The query or collection which contains data to filter.
     *
     * @return SupportCollection|Builder The filtered data collection or the original query.
     */
    public function filterPWBeforePagination(Request $request, $queryOrCollection) {
        $queryAmlFlags = $request->request->get('aml-flags') ?? $request->query->get('aml-flags');

        $userObjects = [];
        if (!empty($queryAmlFlags)) {
            if ($queryOrCollection instanceof Builder) {
                $queryOrCollection = $queryOrCollection->get();
            }

            return $queryOrCollection->filter(function ($item) use ($queryAmlFlags, &$userObjects) {
                $currentUserID = $item->user_id;

                $currentUser = $userObjects[$currentUserID] ?? cu($currentUserID);
                $userObjects[$currentUserID] = $currentUser;

                $amlFlagsHtmlClasses = phive('Cashier')->fraud->getFlags($currentUser);
                $commonValues = array_intersect($amlFlagsHtmlClasses, $queryAmlFlags);
                return !empty($commonValues);
            });
        }

        return $queryOrCollection;
    }

    public function processPendingWithdrawals(array $paginator, object $request, bool $csvResult = false): array {
        $cashier = phive('Cashier');

        $isSuperAgent = phive('Permission')->isSuperAgent();

        $processingWithdrawalsCancellationMinutes = Config::getValue(
            'allow-processing-withdrawal-cancellation-after-x-minutes',
            'pending-withdrawals',
            720
        );

        $userObjects = [];
        $userSettings = [];
        $userTotalDepositSum = [];
        foreach ($paginator['data'] as $index => &$pendingWithdrawal) {
            $currentUserID = $pendingWithdrawal->user_id;
            $withdrawalStatus = $pendingWithdrawal->status;

            $currentUser = $userObjects[$currentUserID] ?? cu($currentUserID);
            $userObjects[$currentUserID] = $currentUser;

            $processedUserSettings = $userSettings[$currentUserID] ?? $this->getProcessedUserSettings($currentUser);
            $userSettings[$currentUserID] = $processedUserSettings;

            $totalDepositSum = $userTotalDepositSum[$currentUserID] ?? phive("Cashier")->getUserDepositSum($currentUserID, "'approved','pending'");
            $userTotalDepositSum[$currentUserID] = $totalDepositSum;

            $amlFlagsHtmlClasses = $cashier->fraud->getFlags($currentUser);
            $flags = array_keys($amlFlagsHtmlClasses);

            list($label, $pendingWithdrawalClass) = $cashier->getRowCssClass($currentUser, (array)$pendingWithdrawal, 'fill-odd', $flags);

            $fraudFlagColor = lic('getWithdrawalFraudFlagColor', [$pendingWithdrawal->user_id], null, null, $currentUser->data['country']);
            if ($fraudFlagColor) {
                $pendingWithdrawalClass .= " style='background-color: {$fraudFlagColor}'";
            }

            $payActionPermission = $pendingWithdrawal->stuck != $cashier::STUCK_UNKNOWN || $isSuperAgent || p('accounting.section.pending-withdrawals.actions.pay');

            $cancellationAllowedDate =\DateTime::createFromFormat('Y-m-d H:i:s', $pendingWithdrawal->date)
                ->modify("+$processingWithdrawalsCancellationMinutes minute");

            $cancelActionPermission = $withdrawalStatus === 'pending' &&
                ($isSuperAgent || p('accounting.section.pending-withdrawals.actions.cancel'));
            $cancelProcessingActionPermission = $withdrawalStatus === 'processing' &&
                $cancellationAllowedDate < (new \DateTime()) &&
                ($isSuperAgent || p('accounting.section.pending-withdrawals.actions.cancel-processing'));

            $pendingWithdrawal->actions = json_encode([
                'verifiedStatus' => $processedUserSettings['verified'],
                'mailSent' => $processedUserSettings['verify_mail_sent'],
                'permissions' => [
                    'payButton' => pIfExists($flags) && $payActionPermission,
                    'canceledButton' => $cancelActionPermission || $cancelProcessingActionPermission,
                    'unverifyButton' => p('accounting.section.pending-withdrawals.actions.unverify-user'),
                    'verificationEmailButton' => p('accounting.section.pending-withdrawals.actions.verificationemail')
                ]
            ]);

            $pendingWithdrawal->rowClass = $csvResult ? $label : $pendingWithdrawalClass;
            $pendingWithdrawal->amlFlags = $csvResult ? $flags : drawFlagSpans($currentUser, $amlFlagsHtmlClasses, true);

            $pendingWithdrawal->totalDepositSum = $totalDepositSum;
        }

        $paginator['data'] = array_values($paginator['data']);

        return $paginator;
    }

    private function getProcessedUserSettings($currentUser): array {
        $settingsToFetch = ['verified', 'verify_mail_sent'];
        $settingsValuesArray = array_fill_keys($settingsToFetch, '');

        $whereInQueryString = "setting IN ('" . implode("', '", $settingsToFetch) . "')";
        $currentUserSettings = $currentUser->getAllSettings($whereInQueryString);

        foreach ($currentUserSettings as $setting) {
            $settingsValuesArray[$setting['setting']] = $setting['setting'] === 'verified' ? 'verified' : $setting['value'];
        }

        return $settingsValuesArray;
    }

    public static function getSiteTotalCash($currency = null)
    {
        /** @var Builder $query */
        $query = User::leftJoin('currencies', 'users.currency', '=', 'currencies.code');
        if (!empty($currency)) {
            $query->where('users.currency', $currency);
            return $query->sum('cash_balance');
        } else {
            return $query->sum(DB::raw('users.cash_balance/currencies.multiplier'));
        }
    }

    public static function getSiteTotalBonusBalance($currency = null)
    {
        /** @var Builder $query */
        $query = User::leftJoin('currencies', 'users.currency', '=', 'currencies.code')
            ->leftJoin('bonus_entries', 'bonus_entries.user_id', '=', 'users.id')
            ->where('bonus_entries.status', 'active');

        if (!empty($currency)) {
            $query->where('users.currency', $currency);
        }
        return $query->sum(DB::raw('bonus_entries.balance'));
    }

    public function getTransferStatsSum(Request $request, $formatted = false)
    {
        if (empty($request->get('username'))) {
            return [
                'cash' => !$formatted ? self::getSiteTotalCash($request->get('currency')) : DataFormatHelper::nf(self::getSiteTotalCash($request->get('currency'))),
                'bonus' => !$formatted ? self::getSiteTotalBonusBalance($request->get('currency')) : DataFormatHelper::nf(self::getSiteTotalBonusBalance($request->get('currency')))
            ];
        } else {
            /** @var User $user */
            $user = User::findByUsername($request->get('username'));
            return [
                'cash' => !$formatted ? $user->cash_balance : $user->currency . ' ' . DataFormatHelper::nf($user->cash_balance),
                'bonus' => !$formatted ? $user->getBonusBalance() : $user->currency . ' ' . DataFormatHelper::nf($user->getBonusBalance())
            ];
        }
    }

    //todo refactor this to make it global for the project transaction details'
    /**
     * @param object|array $data
     */
    private function processTransactionDetails($data): array
    {
        $result = [];
        $userSettings = [];

        foreach ($data as $transaction) {
            $transaction->actor = $this->tempGetUsername($transaction);

            $full_card_hash = strtolower($transaction->type) === 'withdrawal'
                ? DataFormatHelper::getCardType($transaction->card_hash, true)
                : $transaction->card_hash;

            $transaction->details .= ' ' . $full_card_hash;

            if ($transaction->wallet) {
                $transaction->details = ucfirst($transaction->wallet) . ' ' . $transaction->details;
            }

            $transactionType = strtolower($transaction->type);

            if ($transaction->method === Supplier::Skrill && $transactionType !== 'withdrawal') {
                $transaction->details .= $this->getDetailsFromSettings(
                    $transaction->user_id,
                    $userSettings,
                    ['mb_email']
                );
            } elseif ($transaction->method === Supplier::Neteller && $transactionType !== 'withdrawal') {
                $transaction->details = $this->getDetailsFromSettings(
                    $transaction->user_id,
                    $userSettings,
                    ['net_account']
                );
            } elseif ($transaction->method === Supplier::Neosurf) {
                $ref = explode('|', $transaction->ext_id);
                $transaction->ext_id = $ref[0];
            } elseif ($transaction->method === Supplier::Trustly && $transactionType === 'withdrawal') {
                $rawDetail = str_replace('_', ' ', $transaction->details);
                $upperCaseDetail = mb_convert_case($rawDetail, MB_CASE_TITLE, 'UTF-8');
                $transaction->details = preg_replace('/\*/', ' - *', $upperCaseDetail, 1);
            }

            $transaction->details .= $transaction->transaction_details['credit_card']['details'] ?? '';

            $result[] = $transaction;
        }

        return $result;
    }

    // TODO: [BAN-12503] Remove this method once all PSPs using it are handled within `addPspDetailsJoinsAndCase()`.
    private function getDetailsFromSettings(
        string $userId,
        array  &$userSettings,
        array  $settingsKeys,
        string $separator = ' '
    ): string
    {
        $missingSettingKeys = array_filter($settingsKeys, fn($key) => empty($userSettings[$userId][$key]));

        if ($missingSettingKeys) {
            $fetchedSettings = $this->getKvSettingsByUserId((int)$userId, $missingSettingKeys);

            if ($fetchedSettings) {
                $userSettings[$userId] = array_merge($userSettings[$userId] ?? [], $fetchedSettings);
            }
        }

        // Preserve the original order of settings keys-values
        $orderedValues = array_filter(
            array_map(fn($key) => $userSettings[$userId][$key], $settingsKeys)
        );

        return empty($orderedValues) ? '' : $separator . implode($separator, $orderedValues);
    }

    private function getKvSettingsByUserId(int $userId, array $settingKeys): array
    {
        $query = sprintf(
            "SELECT DISTINCT setting, value FROM users_settings WHERE user_id = %d AND setting IN (%s)",
            $userId,
            phive('SQL')->makeIn($settingKeys)
        );

        return (array) phive('SQL')
            ->readOnly()
            ->sh($userId, '', 'users_settings')
            ->loadKeyValues($query, 'setting', 'value');
    }

    //todo this is a temporal function until we refactor
    private function tempGetUsername($elem)
    {
        if (is_numeric($elem->actor)) {
            if (empty($this->cached_data['users_cache'][$elem->actor])) {
                $actor = ReplicaDB::shTable($elem->actor, 'users')->find($elem->actor);
                $this->cached_data['users_cache'][$actor->id] = $actor->username;
            }
            return $this->cached_data['users_cache'][$elem->actor];
        } else {
            return $elem->actor;
        }
    }

    //todo refactor all of this
    private function processTransactionDetailsByOne($elem)
    {
        $elem->actor = $this->tempGetUsername($elem);
        $full_card_hash = $elem->type == 'withdrawal' ? DataFormatHelper::getCardType($elem->card_hash, true) : $elem->card_hash;
        $elem->details .= ' ' . $full_card_hash;
        if ($elem->method == 'skrill' && $elem->type != 'withdrawal') {
            //$elem->details .= ' ' . UserSetting::select('value')->where(['user_id' => $elem->user_id, 'setting' => 'mb_email'])->get()[0]->value;
        } elseif ($elem->method == 'neteller' && $elem->type != 'withdrawal') {
            //$elem->details = UserSetting::select('value')->where(['user_id' => $elem->user_id, 'setting' => 'net_account'])->get()[0]->value;
        } elseif ($elem->method == 'neosurf') {
            $ref = explode('|', $elem->ext_id);
            $elem->ext_id = $ref[0];
            //$elem->details = $ref[1];
        }

        return $elem;
    }

    public function exportTransferStats(Application $app, Builder $query, Request $request)
    {
        $start_time = microtime(true);
        $status = $request->get('status');

        $record = ['Date', 'Exec date', 'User', 'Method', 'Sub Method', 'Details', 'Type', 'Currency', 'Amount', 'Fee', 'Deducted', 'Status', 'External ID', 'Internal ID', 'Loc ID', 'Appr. By', 'Country', 'Province', 'MTS ID'];

        if ($status === 'disapproved') {
            $record[] = 'Error Reason';
        }

        $records[] = $record;
        $records[] = [' '];

        set_time_limit(320);
        //$data = $this->processTransactionDetails($query->orderBy('date', 'desc')->get());

        $data = $query->orderBy('date', 'desc')->get();

        $data = (new Mts($app))->addTransactionDetails($data);

        foreach ($data as $element) {
            $element = $this->processTransactionDetailsByOne($element);

            $record = [
                $element->date,
                $element->exec_date,
                $element->user_id,
                $element->method,
                $element->submethod,
                trim($element->details) . $element->transaction_details['credit_card']['details'] ?? '',
                $element->type,
                $element->currency,
                $element->amount,
                $element->fee,
                $element->deducted,
                $element->status,
                $element->ext_id,
                $element->id,
                $element->loc_id,
                $element->actor,
                $element->country,
                $element->province,
                $element->mts_id,
            ];

            if ($status === 'disapproved') {
                $record[] = $element->transaction_details['transaction_error']['description'] ?? '';
            }

            $records[] = $record;
        }

        $records[] = [' '];

        $time = (string)(microtime(true) - $start_time);
        $app['monolog']->addError("Transaction report download took {$time}");

        return DownloadHelper::streamAsCsv(
            $app,
            $records,
            "transfer_stats"
        );
    }

    public function exportPendingWithdrawals(Application $app, array $paginator)
    {
        $start_time = microtime(true);

        (new Mts($app))->addTransactionDetails(collect($paginator['data']));

        $paginator['data'] = $this->processTransactionDetails($paginator['data']);

        $records = [];
        foreach ($paginator['data'] as $element) {
            $stuckStatus = $element->rowClass;
            $record = [
                'AMl Flags' => implode(', ', $element->amlFlags),
                'Stuck Status' => ($stuckStatus === 'default') ? 'non-stuck' : $stuckStatus,
                'Date' => $element->date,
                'User ID' => $element->user_id,
                'Username' => $element->username,
                'Description' => strip_tags($element->description),
                'Method' => $element->method,
                'Details' => trim($element->details),
                'Currency' => $element->currency,
                'Tot. Dep. Sum' => $element->totalDepositSum,
                'Amount' => $element->amount,
                'Fee' => $element->fee,
                'Deducted' => $element->deducted,
                'External ID' => $element->ext_id,
                'Internal ID' => $element->id,
                'Country' => $element->country,
                'Province' => $element->province,
                'MTS ID' => $element->mts_id
            ];

            if (!p('accounting.section.pending-withdrawals.aml-flags')) {
                unset($record['AMl Flags']);
            }

            if (!p('accounting.section.pending-withdrawals.stuck-statuses')) {
                unset($record['Stuck Status']);
            }

            $records[] = $record;
        }

        $firstRecord = reset($records);
        $headers = [array_keys($firstRecord)];
        $recordsValuesArray = array_map('array_values', $records);

        $csvData = array_merge($headers, $recordsValuesArray);

        $time = (string)(microtime(true) - $start_time);
        $app['monolog']->addError("Pending Withdrawals download took {$time}");

        $downloadTimestamp = date('Y-m-d_H-i-s', $start_time);

        return DownloadHelper::streamAsCsv(
            $app,
            $csvData,
            "pending_withdrawals_{$downloadTimestamp}"
        );
    }

    public function getTransferStatsData(Request $request, $params = null)
    {
        $method_map = [
            'ppal' => 'paypal'
        ];
        $dep_query = ReplicaDB::table('deposits AS d');
        $with_query = ReplicaDB::table('pending_withdrawals AS w');

        if (empty($request->get('currency'))) {
            $multiplier = ' / IFNULL(fx_rates.multiplier,1)';
            $dep_query->leftJoin('fx_rates', function (JoinClause $join) {
                $join->on('fx_rates.code', '=', 'd.currency')->where('fx_rates.day_date', '=', 'DATE(d.timestamp)');
            });
            $with_query->leftJoin('fx_rates', function (JoinClause $join) {
                $join->on('fx_rates.code', '=', 'w.currency')->where('fx_rates.day_date', '=', 'DATE(d.timestamp)');
            });
        } else {
            $multiplier = '';
            $dep_query->where('currency', $request->get('currency'));
            $with_query->where('currency', $request->get('currency'));
        }

        $dep_query->selectRaw("dep_type,
                              sum(amount$multiplier) AS dep_amount,
                              count(d.id) AS dep_total,
                              count(DISTINCT user_id) AS dep_unique,
                              sum(real_cost$multiplier) AS dep_cost,
                              sum(deducted_amount$multiplier) AS dep_deducted")
            ->where('status', 'approved')
            ->groupBy('dep_type');

        $with_query->selectRaw("payment_method,
                              sum(amount$multiplier) AS w_amount,
                              count(w.id) AS w_total,
                              count(DISTINCT user_id) AS w_unique,
                              sum(real_cost$multiplier) AS w_cost,
                              sum(deducted_amount$multiplier) AS w_deducted")
            ->where('status', 'approved')
            ->groupBy('payment_method');

        if (!empty($params)) {
            $dep_query->whereBetween('d.timestamp', [$params['start_date'], $params['end_date']]);
            $with_query->whereBetween('w.timestamp', [$params['start_date'], $params['end_date']]);
        }

        $res = [];
        foreach ($dep_query->get() as $elem) {
            if (isset($method_map[$elem->dep_type])) {
                $elem->dep_type = $method_map[$elem->dep_type];
            }
            $res[$elem->dep_type] = [
                'dep_amount' => $elem->dep_amount,
                'dep_total' => $elem->dep_total,
                'dep_unique' => $elem->dep_unique,
                'total_trans' => $elem->dep_amount,
                'transfer_fees' => $elem->dep_cost,
                'deducted_fees' => $elem->dep_deducted
            ];
        }

        foreach ($with_query->get() as $elem) {
            $res[$elem->payment_method]['w_amount'] = $elem->w_amount;
            $res[$elem->payment_method]['w_total'] = $elem->w_total;
            $res[$elem->payment_method]['w_unique'] = $elem->w_unique;
            $res[$elem->payment_method]['total_trans'] = empty($res[$elem->payment_method]['total_trans']) ? $elem->w_amount : $res[$elem->payment_method]['total_trans'] + $elem->w_amount;
            $res[$elem->payment_method]['transfer_fees'] = empty($res[$elem->payment_method]['transfer_fees']) ? $elem->w_cost : $res[$elem->payment_method]['transfer_fees'] + $elem->w_cost;
            $res[$elem->payment_method]['deducted_fees'] = empty($res[$elem->payment_method]['deducted_fees']) ? $elem->w_deducted : $res[$elem->payment_method]['transfer_fees'] + $elem->w_deducted;
        }

        foreach ($res as $key => $value) {
            $res[$key]['effective'] = round(($value['transfer_fees'] / $value['total_trans']) * 100, 2);
            $res[$key]['balance'] = $value['dep_amount'] - $value['w_amount'];
        }

        $res_col = collect($res);
        $total = [
            'dep_amount' => $res_col->sum('dep_amount'),
            'dep_total' => $res_col->sum('dep_total'),
            'dep_unique' => $res_col->sum('dep_unique'),
            'w_amount' => $res_col->sum('w_amount'),
            'w_total' => $res_col->sum('w_total'),
            'w_unique' => $res_col->sum('w_unique'),
            'total_trans' => $res_col->sum('total_trans'),
            'transfer_fees' => $res_col->sum('transfer_fees'),
            'deducted_fees' => $res_col->sum('deducted_fees')
        ];

        $res['total all methods'] = $total;

        return $res;
    }

    public function getUserBalanceData(Request $request, $query_params, $initial = true)
    {
        $query = UserDailyBalance::on(replicaDatabaseSwitcher(true))->selectRaw('
                users_daily_balance_stats.user_id,
                bank_countries.printable_name as country,
                users_daily_balance_stats.currency,
                users_daily_balance_stats.cash_balance,
                users_daily_balance_stats.bonus_balance,
                users_daily_balance_stats.extra_balance
            ')->leftJoin('users', 'users_daily_balance_stats.user_id', '=', 'users.id')
            ->leftJoin('bank_countries', 'users_daily_balance_stats.country', '=', 'bank_countries.iso')
            ->where('date', $query_params['date']);

        if (!empty($query_params['currency'])) {
            $query->where('users_daily_balance_stats.currency', $query_params['currency']);
        }

        if (!empty($query_params['country'])) {
            $query->where('users_daily_balance_stats.country', $query_params['country']);
        }

        if (@$query_params['return_query'] == true) {
            return $query;
        } else {
            $paginator = new PaginationHelper($query, $request, ['length' => 25, 'order' => ['column' => 'users_daily_balance_stats.cash_balance', 'dir' => 'ASC']]); // fix 340
            return $paginator->getPage($initial);
        }
    }

    public function exportUserBalanceData(Application $app, $query, $query_params)
    {
//        ini_set('max_execution_time', 90); // TODO check if this is needed on live too, on office1 it was failing

        $records[] = ['Date:', $query_params['date']];
        $records[] = ['Currency:', empty($query_params['currency']) ? 'ALL' : $query_params['currency']];
        $records[] = ['Country:', empty($query_params['country']) ? 'ALL' : $query_params['country']];
        $records[] = [' '];

        $records[] = ['User ID', 'Username', 'Country', 'Currency', 'Cash Balance', 'Bonus Balance', 'Extra Balance'];
        $records[] = [' '];
        foreach ($query->get() as $elem) {
            $records[] = [
                $elem['user_id'],
                $elem['username'],
                $elem['country'],
                $elem['currency'],
                empty($elem['cash_balance']) ? 0 : DataFormatHelper::nf($elem['cash_balance']),
                empty($elem['bonus_balance']) ? 0 : DataFormatHelper::nf($elem['bonus_balance']),
                empty($elem['extra_balance']) ? 0 : DataFormatHelper::nf($elem['extra_balance'])
            ];
        }

        $records[] = [' '];

        return DownloadHelper::streamAsCsv(
            $app,
            $records,
            "player_balance_{$query_params['date']}"
        );
    }

    public function getTotalBalanceData($query_data)
    {
        $date_obj = Carbon::create($query_data['year'], $query_data['month']);

        $query = ReplicaDB::connection(replicaDatabaseSwitcher(true))->table('users_daily_balance_stats')
            ->selectRaw('date, sum(cash_balance) AS `real`, sum(bonus_balance) AS bonus, sum(extra_balance) as extra')
            ->whereBetween('date', [$date_obj->startOfMonth()->toDateString(), $date_obj->endOfMonth()->toDateString()])
            ->where('currency', $query_data['currency'])
            ->groupBy(['date']);

        if ($query_data['source'] != 'all') {
            $map = [
                'vs' => 0,
                'pr' => 1
            ];
            $query->where('source', $map[$query_data['source']]);
        }

        if (!empty($query_data['country']) && $query_data['country'] != 'all') {
            $query->where('country', $query_data['country']);
        }

        if (!empty($query_data['provinces'])) {
            $query->where(function ($query) use ($query_data) {
                foreach ($query_data['provinces'] as $province)
                $query->orWhere('province', $province);
            });
        }
        $data = $query->get()->keyBy('date')->all();

        $res = [];
        if (in_array($query_data['source'], ['all', 'vs'])) {
            $misc_query_res = MiscCache::on(replicaDatabaseSwitcher(true))->where(
                'id_str',
                'LIKE',
                "{$query_data['year']}-{$query_data['month']}-%-cash-balance-{$query_data['currency']}"
            )->get();
            foreach ($misc_query_res as $elem) {
                $content = unserialize($elem->cache_value);
                $date_array = explode('-', $elem->id_str);
                $date = $date_array[0] . '-' . $date_array[1] . '-' . $date_array[2];
                $row_data = (array)$data[$date];
                $res[] = [
                    'date' => $date,
                    'pending' => $content['pending'],
                    'real' => $date == '2016-12-01' ? $content['real'] : $row_data['real'],
                    'bonus' => $date == '2016-12-01' ? $content['bonus'] : $row_data['bonus'],
                    'extra' => $row_data['extra']
                ];
            }
        }

        return $res;
    }

    public function getLegacyTotalBalanceData($query_data)
    {
        $misc_query_res = MiscCache::on(replicaDatabaseSwitcher(true))->where('id_str', 'LIKE', "{$query_data['year']}-{$query_data['month']}-%-cash-balance-{$query_data['currency']}")->get();

        if (in_array($query_data['source'], ['all', 'pr'])) {
            $pr_data = [];
            $date_obj = Carbon::create($query_data['year'], $query_data['month']);
            if ($date_obj->gte(Carbon::create(2016, 11))) {
                /** @var Collection $udbs_query_res */
                $udbs_query_res = UserDailyBalance::on(replicaDatabaseSwitcher(true))->selectRaw('date, sum(cash_balance) AS cash, sum(bonus_balance) AS bonus')
                    ->whereBetween('date', [$date_obj->startOfMonth()->toDateString(), $date_obj->endOfMonth()->toDateString()])
                    ->where('currency', $query_data['currency'])
                    ->where('source', 1)
                    ->groupBy('date')
                    ->get();
                $pr_data = $udbs_query_res->keyBy('date')->all();
            }
            $data = [];
            foreach ($misc_query_res as $elem) {
                $content = unserialize($elem->cache_value);
                $date_array = explode('-', $elem->id_str);
                $date = $date_array[0] . '-' . $date_array[1] . '-' . $date_array[2];
                if ($query_data['source'] == 'all') {
                    $data[] = [
                        'date' => $date,
                        'pending' => $content['pending'],
                        'real' => empty($pr_data) ? $content['real'] : ($content['real'] + $pr_data[$date]['cash']),
                        'bonus' => empty($pr_data) ? $content['bonus'] : ($content['bonus'] + $pr_data[$date]['bonus']),
                        'extra' => 0 // we don't have this on legacy data
                    ];
                } else {
                    $data[] = [
                        'date' => $date,
                        'pending' => 0,
                        'real' => $pr_data[$date]['cash'],
                        'bonus' => $pr_data[$date]['bonus'],
                        'extra' => 0 // we don't have this on legacy data
                    ];
                }
            }
            return $data;
        } else {
            $data = [];
            foreach ($misc_query_res as $elem) {
                $content = unserialize($elem->cache_value);
                $date_array = explode('-', $elem->id_str);
                $data[] = [
                    'date' => $date_array[0] . '-' . $date_array[1] . '-' . $date_array[2],
                    'pending' => $content['pending'],
                    'real' => $content['real'],
                    'bonus' => $content['bonus'],
                ];
            }
            return $data;
        }
    }

    public function getBalanceData($query_data, Request $request, LiabilityRepository $liability_repo)
    {
        $date = Carbon::create($query_data['year'], $query_data['month'], 1);
        //if (($request->get('year') == 2016 && $request->get('month') == 9) || ($request->get('year') >= 2016 && )) {
        if (($request->get('year') == 2016 && $request->get('month') == 9) || $date->gt(Carbon::create(2016, 12, 1))) {
            $query = UserDailyBalance::on(replicaDatabaseSwitcher(true))->selectRaw("(sum(cash_balance) + sum(bonus_balance)) AS sum")
                ->where(['date' => $date->format('Y-m-d'), 'currency' => $query_data['currency']]);
            if ($query_data['country'] != 'all') {
                $query->where('country', $query_data['country']);
            }
            if ($query_data['source'] != 'all') {
                $query->where('source', $liability_repo->source);
            }
            if (!empty($query_data['province'])) {
                $query->whereIn('province', $query_data['province']);
            }
            return $query->first()->sum;
        } elseif ($date->eq(Carbon::create(2016, 12, 1))) {
            $res = MiscCache::where('id_str', "2016-12-01-cash-balance-{$query_data['currency']}")->first();
            $content = unserialize($res->cache_value);
            $vs_balances = $content['real'] + $content['bonus'];
            $pr_query = UserDailyBalance::on(replicaDatabaseSwitcher(true))->selectRaw("(sum(cash_balance) + sum(bonus_balance)) AS sum")
                ->where(['date' => $date->format('Y-m-d'), 'currency' => $query_data['currency'], 'source' => 1]);
            if ($request->get('source') == 'vs') {
                return $vs_balances;
            } elseif ($request->get('source') == 'pr') {
                return $pr_query->first()->sum;
            } else {
                return $pr_query->first()->sum + $vs_balances;
            }
        } elseif ($request->get('year') == 2016 && $request->get('month') == 11) {
            $res = MiscCache::where('id_str', "{$date->format('Y')}-{$date->format('m')}-01-cash-balance-{$query_data['currency']}")->first();
            $content = unserialize($res->cache_value);
            if ($query_data['source'] == 'vs') {
                return $content['real'] + $content['bonus'];
            } elseif ($query_data['source'] == 'pr') {
                return UserDailyBalance::on(replicaDatabaseSwitcher(true))->selectRaw("(sum(cash_balance) + sum(bonus_balance)) AS sum")
                    ->where(['date' => $date->format('Y-m-d'), 'currency' => $query_data['currency']])
                    ->where('source', 1)->first()->sum;
            } else {
                $pr_sum = UserDailyBalance::on(replicaDatabaseSwitcher(true))->selectRaw("(sum(cash_balance) + sum(bonus_balance)) AS sum")
                    ->where(['date' => $date->format('Y-m-d'), 'currency' => $query_data['currency']])
                    ->where('source', 1)->first()->sum;
                return $content['real'] + $content['bonus'] + $pr_sum;
            }
        } else {
            $res = MiscCache::where('id_str', "{$date->format('Y')}-{$date->format('m')}-01-cash-balance-{$query_data['currency']}")->first();
            $content = unserialize($res->cache_value);
            //if ($query_data['year'] >= 2016 && $query_data['month'] >= 6) {
            if ($date->gte(Carbon::create(2016, 6, 1))) {
                return $content['real'] + $content['bonus'];
            } else {
                return $content['real'];
            }
        }
    }

    public function exportTotalBalanceData($app, $data, $query_data)
    {
        $records[] = ['Countries:', $query_data['country']];
        if($query_data['provinces']) {
            $records[] = ['Provinces:', implode(',', $query_data['provinces'])];
        }
        $records[] = ['Currency:', $query_data['currency']];
        $records[] = ['From:', Carbon::create($query_data['year'], $query_data['month'])->startOfMonth()->format('d-m-Y')];
        $records[] = ['To:', Carbon::create($query_data['year'], $query_data['month'])->endOfMonth()->format('d-m-Y')];
        $records[] = [' '];

        if ($this->useBonusBalance($query_data)) {
            if ($query_data['country'] == 'all') {
                $records[] = ['Date', 'Balance', 'Pending balance', 'Bonus balance', 'Extra balance'];
            } else {
                $records[] = ['Date', 'Balance', 'Bonus balance', 'Extra balance'];
            }

            $records[] = [' '];
            foreach ($data as $elem) {
                if ($query_data['country'] == 'all') {
                    $records[] = [
                        $elem['date'],
                        empty($elem['real']) ? 0 : $elem['real'] / 100,
                        empty($elem['pending']) ? 0 : $elem['pending'] / 100,
                        empty($elem['bonus']) ? 0 : $elem['bonus'] / 100,
                        empty($elem['extra']) ? 0 : $elem['extra'] / 100
                    ];
                } else {
                    $records[] = [
                        $elem['date'],
                        empty($elem['real']) ? 0 : $elem['real'] / 100,
                        empty($elem['bonus']) ? 0 : $elem['bonus'] / 100,
                        empty($elem['extra']) ? 0 : $elem['extra'] / 100
                    ];
                }
            }
        } else {
            $records[] = ['Date', 'Balance', 'Pending balance'];
            $records[] = [' '];
            foreach ($data as $elem) {
                $records[] = [
                    $elem['date'],
                    empty($elem['real']) ? 0 : $elem['real'] / 100,
                    empty($elem['pending']) ? 0 : $elem['pending'] / 100
                ];
            }
        }

        $records[] = [' '];
        $province = empty($query_data['provinces'])? "" : "_".implode(',', $query_data['provinces']) ;

        return DownloadHelper::streamAsCsv(
            $app,
            $records,
            "player_total_balance_{$query_data['currency']}_{$query_data['country']}_{$query_data['year']}-{$query_data['month']}{$province}"
        );
    }

    public function useBonusBalance($query_data)
    {
        return Carbon::create($query_data['year'], $query_data['month'])->startOfMonth() >= Carbon::create(2016, 6, 1)->startOfMonth();
    }

    /**
     * I need the results as fast as possible that is why first I get all of them and then I can process sharded or non sharded scenarios
     */
    public function cacheBalanceByPlayer()
    {
        DB::connection()->setFetchMode(\PDO::FETCH_ASSOC);

        $list = DB::shsSelect(
            'users',
            "SELECT u.id AS user_id,
                        :date AS date,
                        u.cash_balance,
                        IFNULL(sum(be.balance), 0) AS bonus_balance,
                        u.currency,
                        u.country,
                        IFNULL(us.value, '') as province
                    FROM users u
                        LEFT JOIN bonus_entries be ON be.user_id = u.id AND be.status = 'active'
                        LEFT JOIN users_settings us ON us.user_id = u.id AND us.setting = 'main_province'
                    WHERE u.cash_balance > 0 OR be.balance > 0
                    GROUP BY u.id",
            ['date' => Carbon::now()->format('Y-m-d')]
        );

        return UserDailyBalance::bulkInsert($list);
    }

    public function cacheBalanceByCompanyFromPR($app)
    {
        $pr_rpc = new PR($app);
        $now = Carbon::now()->format('Y-m-d');

        $data = $pr_rpc->execFetch("SELECT company_id AS user_id, '$now' AS date, cash_balance, currency, country, 1 as source
                                    FROM companies c
                                    GROUP BY company_id");

        return UserDailyBalance::bulkInsert($data);
    }

    /**
     * This needs to be called after the last "cacheXXX" function in the DailyCommand.
     * We need to have all the other data before to properly calculate the "extra_balance" column in the "users_daily_balance_stats".
     * (For now is only booster, but in the future we may add other things.)
     *
     * This function will take care of updating or inserting rows (Ex. user with something in the vault but 0 cash_balance / bonus_balance)
     */
    public function addExtraBalanceToPlayerBalanceCache($date = null) {
        DB::connection()->setFetchMode(\PDO::FETCH_ASSOC);

        if(empty($date)) {
            $date = Carbon::today()->toDateString();
        }

        $inserts = [];
        $users_daily_balance_stats = DB::getMasterConnection()->table('users_daily_balance_stats')
            ->where('date', $date)->where('source', 0)->get()->keyBy('user_id');

        $users_daily_booster_repo = new UsersDailyBoosterStatsRepository();
        $users_booster_vault = $users_daily_booster_repo->getUsersCachedBoosterVault($date);

        foreach($users_daily_balance_stats as $user_id => $user_stat) {
            if($users_booster_vault[$user_id]) {
                $extra_balance = $users_booster_vault[$user_id]['cached_booster'];
                $sql = "UPDATE users_daily_balance_stats SET extra_balance = {$extra_balance} WHERE user_id = {$user_id} AND date = '{$date}' AND source = 0";
                DB::loopNodes(function (Connection $connection) use ($sql) {
                    return $connection->unprepared($sql);
                }, true);

                unset($users_booster_vault[$user_id]);
            }
        }

        // If the array is not empty it means that we still need to add users without balance or bonus, but with extra_balance
        if(count($users_booster_vault)) {
            foreach($users_booster_vault as $user_id=>$data) {
                // TODO add a check if extra_balance on the previous MAX(date) on users_daily_balance_stats is == "cached_booster", if so we don't need to insert data again.
                $inserts[] = [
                   'user_id' => $user_id,
                   'date' => date('Y-m-d'),
                   'cash_balance' => 0,
                   'bonus_balance' => 0,
                   'country' => $data['country'],
                   'province' => cu($user_id)->getSetting('main_province'),
                   'currency' => $data['currency'],
                   'extra_balance' => $data['cached_booster'],
                   'source' => 0
                ];
            }
        }
        if(count($inserts)) {
            UserDailyBalance::bulkInsert($inserts);
        }
    }

    /**
     * @param DateRange $month_range
     * @param string $jurisdiction
     * @return array
     * @throws \Exception
     */
    public function getGameRevenue(DateRange $month_range, string $jurisdiction)
    {
        $jurisdiction_query_map = $this->jurisdictionFilter();

        $sportsbook_juridictions = [
            'mga sportsbook',
            'mt sportsbook',
            'se sportsbook',
            'gb sportsbook'
        ];
        $poolx_juridictions = [
            'sga pool bet online',
        ];

        $is_sportsbook = in_array($jurisdiction, $sportsbook_juridictions);
        $is_poolx = in_array($jurisdiction, $poolx_juridictions);

        $sportsbook_discriminator = '';
        $poolx_discriminator = '';
        if ($jurisdiction !== 'all') {
            $sportsbook_values = [
                'sportradar',
                Networks::BETRADAR['product'] . '_' . Networks::BETRADAR['name'],
                Networks::ALTENAR['product'] . '_' . Networks::ALTENAR['name'],
            ];

            $poolx_values = [
                Networks::POOLX['product'] . '_' . Networks::POOLX['name'],
            ];

            $sportsbook_discriminator = sprintf(
                "AND sub_cat %s ('%s')",
                $is_sportsbook ? "IN" : "NOT IN",
                implode("', '", $sportsbook_values)
            );

            $poolx_discriminator = sprintf(
                "AND sub_cat %s ('%s')",
                $is_poolx ? "IN" : "NOT IN",
                implode("', '", $poolx_values)
            );
        }

        $bindings = $this->setupDateBindings($month_range, $is_sportsbook, $is_poolx);

        $wagers_and_other_incentives_categories_in = phive('SQL')->makeIn([
            LRepo::CAT_BETS,
            LRepo::CAT_BET_REFUND_7,
            LRepo::CAT_BOS_BUYIN_34,
            LRepo::CAT_BOS_HOUSE_RAKE_52,
            LRepo::CAT_BOS_REBUY_54,
            LRepo::CAT_BOS_CANCEL_BUYIN_61,
            LRepo::CAT_BOS_CANCEL_HOUSE_FEE_63,
            LRepo::CAT_BOS_CANCEL_REBUY_64,
            LRepo::CAT_BOS_CANCEL_PAYBACK_65,
            LRepo::CAT_SPORTSBOOK_VOIDS,
            LRepo::CAT_POOLX_VOIDS,
        ]);

        $total_winnings_categories_in = phive('SQL')->makeIn([
            LRepo::CAT_WINS,
            LRepo::CAT_WIN_ROLLBACK_7,
            LRepo::CAT_FRB_WINS,
            LRepo::CAT_REWARDS,
            LRepo::CAT_BOS_PRIZES_38,
        ]);

        $wagers_query = "SELECT 'wagers' as type, SUM(amount) as total, currency
                FROM users_monthly_liability uml
                WHERE
                    main_cat IN ($wagers_and_other_incentives_categories_in)  AND
                    STR_TO_DATE(CONCAT_WS('-',year,month,1), '%Y-%m-%d') BETWEEN :date_from1 AND :date_to1
                    $jurisdiction_query_map[$jurisdiction] $sportsbook_discriminator $poolx_discriminator
                GROUP BY currency";

        $total_winning_query = "SELECT 'total_winnings' as type, sum(amount) as total, currency
                FROM users_monthly_liability uml
                WHERE
                    main_cat IN ($total_winnings_categories_in) AND
                    STR_TO_DATE(CONCAT_WS('-',year,month,1), '%Y-%m-%d') BETWEEN :date_from2 AND :date_to2
                    $jurisdiction_query_map[$jurisdiction] $sportsbook_discriminator $poolx_discriminator
                GROUP BY currency";

        $bonus_wagers_and_other_incentives_query = "SELECT
                    'wagers_and_other_incentives' as type,
                    sum(frb_cost) as total,
                    currency COLLATE utf8_unicode_ci AS currency
                FROM users_daily_stats uds
                WHERE
                    date BETWEEN :date_from3 AND :date_to3
                    $jurisdiction_query_map[$jurisdiction]
                GROUP BY currency
                HAVING total > 0";

        $gaming_revenue_query = '
            SELECT
                sub.currency,
                type,
                SUM(total) as amount
            FROM (' . $wagers_query . ' UNION ALL ' . $total_winning_query . ($is_sportsbook || $is_poolx ? '' : ' UNION ALL ' . $bonus_wagers_and_other_incentives_query)
            . ') sub
            GROUP BY sub.currency, type';

        $gaming_revenue_result = ReplicaDB::connection(replicaDatabaseSwitcher(true))->select($gaming_revenue_query, $bindings);
        $result = collect($gaming_revenue_result)->groupBy('currency')->transform(function ($item, $k) {
            return $item->keyBy('type');
        });

        return compact('result', 'jurisdiction_query_map');
    }

    /**
     * Set up date bindings for the given date range and jurisdiction type.
     *
     * @param DateRange $month_range
     * @param bool $is_sportsbook
     * @param bool $is_poolx
     * @return array
     */
    private function setupDateBindings(DateRange $month_range, bool $is_sportsbook, bool $is_poolx): array
    {
        $bindings = [];
        foreach (range(1, $is_sportsbook || $is_poolx ? 2 : 3) as $num) {
            $bindings["date_from$num"] = $month_range->getStart()->startOfMonth()->format('Y-m-d');
            $bindings["date_to$num"] = $month_range->getEnd()->endOfMonth()->format('Y-m-d');
        }

        return $bindings;
    }

    /**
     * Return a query filter for jurisdictions
     * exm. " AND country = 'MT' "
     *
     * @throws JsonException
     */
    private function jurisdictionFilter(): array
    {
        $jurisdiction_query = ReplicaDB::connection(replicaDatabaseSwitcher(true))->table('config')
            ->where('config_name', "admin2.jurisdiction")
            ->first();

        return $jurisdiction_query ? json_decode($jurisdiction_query->config_value, true, 512, JSON_THROW_ON_ERROR) : [];
    }

    /**
     * @param Application $app
     * @param $data
     * @param DateRange $date_range
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function gamingRevenueReportExport(Application $app, $data, DateRange $date_range)
    {
        $records[] = ['From:', $date_range->getStart()->startOfMonth()->format('d-m-Y')];
        $records[] = ['To:', $date_range->getEnd()->endOfMonth()->format('d-m-Y')];
        $records[] = [' '];
        $records[] = ['Currency', 'Wagers', 'Total Winnings', 'Bonus wagers and other incentives'];
        $records[] = [' '];

        foreach ($data as $currency => $element) {
            $records[] = [
                    $currency,
                    DataFormatHelper::nf(abs($element['wagers']->amount)),
                    DataFormatHelper::nf($element['total_winnings']->amount),
                    DataFormatHelper::nf(abs($element['wagers_and_other_incentives']->amount)),
            ];
        }

        $records[] = [' '];
        $records[] = [' Report generated: ', Carbon::create()->format('Y-m-d H:i:s')];

        return DownloadHelper::streamAsCsv(
                $app,
                $records,
                "game_revenue_report_{$date_range->getStart()->year}-{$date_range->getStart()->month}_{$date_range->getEnd()->year}-{$date_range->getEnd()->month}"
        );
    }

    public function getJackpotLog($request) {
        $query = ReplicaDB::connection(replicaDatabaseSwitcher(true))->table('jp_log')
            ->select([
                'jp_log.created_at',
                'jp_log.jp_name',
                'jp_log.jp_id',
                'micro_games.game_name',
                'jp_log.network',
                'jp_log.currency',
                'jp_log.jp_value',
                'jp_log.contributions',
                'jp_log.trigger_amount',
                'micro_jps.local',
                'jp_log.configuration'
            ])
            ->join('micro_games', 'game_ref', '=', 'ext_game_name') // INNER cause we don't want jackpot listed for games we don't have
            ->leftJoin('micro_jps', 'jp_log.jp_id', '=', 'micro_jps.jp_id');

        if ($request->get('jackpot_name', 'all') != 'all') {
            $query->where('jp_log.jp_name','LIKE', "%".$request->get('jackpot_name')."%");
        }

        if ($request->get('game_name', 'all') != 'all') {
            $query->where('micro_games.game_name','LIKE', "%".$request->get('game_name')."%");
        }

        if ($request->get('currency', 'all') != 'all') {
            $query->where("jp_log.currency", "=", $request->get('currency'));
        }

        if ($request->get('network', 'all') != 'all') {
            $query->where("jp_log.network", "=", $request->get('network'));
        }

//dd($query->toSql(), $query->get());
        return $query;
    }

    /**
     * @param DateRange $month_range
     * @param string $jurisdiction
     *
     * @return \Illuminate\Support\Collection
     */
    public function getOpenBets(DateRange $month_range, string $jurisdiction)
    {
        $jurisdiction_query_map = [
            'all' => '',
            'sga sportsbook'  => "AND u.country = 'SE'",
            'mga sportsbook'  => "AND u.country NOT IN ('GB', 'SE', 'DK', 'IT', 'ES') AND NOT exists( select value from users_settings us where u.id = us.user_id AND us.setting = 'main_province' AND us.value = 'ON' )",
            'ukgc sportsbook' => "AND u.country = 'GB'"
        ];

        $bindings_date = [];
        foreach (range(1, 3) as $num) {
            $date_time = new \DateTime($month_range->getEnd()->endOfMonth());
            $bindings_date["first_day_next_month$num"] = $date_time->modify('+1 day')->format('Y-m-d');
            /* We want to get open bets only from date when we launch sportsbook project, and start date is modifying */
            if($num < 3) {
                $bindings_date["launch_date_start$num"] = $month_range->getStart()->modify(self::LAUNCH_SPORTSBOOK_PROJECT)->format('Y-m-d');
            }
        }

        $total_open_bets_query = "
            SELECT sub.currency, SUM(sub.amount) as total_amount_open , count(1) as number_bets
            FROM (
                SELECT st.currency, st.amount
                FROM sport_transactions st
                JOIN users u ON st.user_id = u.id
                WHERE ticket_id NOT IN (
                    SELECT ticket_id FROM sport_transactions st
                    WHERE st.bet_type IN ('win','void')
                    AND st.created_at BETWEEN :launch_date_start1 AND :first_day_next_month1
                )

                AND st.created_at BETWEEN :launch_date_start2 AND :first_day_next_month2
                AND st.bet_type = 'bet'
                AND ((st.ticket_settled = 0 AND st.settled_at IS NULL) OR (st.ticket_settled = 1 AND st.settled_at > :first_day_next_month3))
                $jurisdiction_query_map[$jurisdiction] GROUP BY st.currency, st.ticket_id) sub
                GROUP BY sub.currency
        ";

        $open_bets_result = ReplicaDB::shsSelect('sport_transaction_details', $total_open_bets_query, $bindings_date);

        /* To union all shards data fields by currency */
        $result = collect($open_bets_result)->groupBy('currency');
        $result = $result->map(function ($item, $key) {
            $item = collect($item);
            return $item->pipe(function ($collection) use ($key) {
                return collect([
                    'currency' => $key,
                    'total_amount_open' => $collection->sum('total_amount_open'),
                    'number_bets' => $collection->sum('number_bets'),
                ]);
            });
        });

        return compact('result', 'jurisdiction_query_map');
    }

    /**
     * @param Application $app
     * @param $data
     * @param DateRange $date_range
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function getOpenBetsExport(Application $app, $data, DateRange $date_range)
    {
        $records[] = ['From:', $date_range->getStart()->format('d-m-Y')];
        $records[] = ['To:', $date_range->getEnd()->endOfMonth()->format('d-m-Y')];
        $records[] = [' '];
        $records[] = ['Currency', 'Number of Bets', 'Total Amount Open'];
        $records[] = [' '];

        foreach ($data as $currency => $element) {
            $records[] = [
                $currency,
                $element['number_bets'],
                DataFormatHelper::nf($element['total_amount_open']),
            ];
        }

        $records[] = [' '];
        $records[] = [' Report generated: ', Carbon::create()->format('Y-m-d H:i:s')];

        return DownloadHelper::streamAsCsv(
            $app,
            $records,
            "open_bets_report_{$date_range->getStart()->year}-{$date_range->getStart()->month}_{$date_range->getEnd()->year}-{$date_range->getEnd()->month}"
        );
    }
}
