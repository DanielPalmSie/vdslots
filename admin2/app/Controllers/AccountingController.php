<?php

/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 2016.04.21.
 * Time: 14:29
 */

namespace App\Controllers;

use App\Classes\DateRange;
use App\Helpers\DataFormatHelper as Dfh;
use App\Helpers\FilterFormHelper;
use App\Helpers\PaginationHelper;
use App\Models\CashTransaction;
use App\Models\User;
use App\Extensions\Database\FManager as DB;
use App\Repositories\AccountingRepository;
use App\Repositories\BankCountryRepository;
use App\Repositories\LiabilityRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Valitron\Validator;
use App\Exceptions\LiabilitiesProcessedException;
use App\Exceptions\UpdateLiabilitiesException;

//todo shards
class AccountingController implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/', 'App\Controllers\AccountingController::index')
            ->bind('accounting-index')
            ->before(function () use ($app) {
                if (!p('accounting.section')) {
                    $app->abort(403);
                }
            });

        $factory->match('/liability/', 'App\Controllers\AccountingController::playerLiabilityReport')
            ->bind('accounting-liability')
            ->before(function () use ($app) {
                if (!p('accounting.section.liability')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->post('/liability/per-category/', 'App\Controllers\AccountingController::getCategoryData')
            ->bind('accounting-liability-per-category')
            ->before(function () use ($app) {
                if (!p('accounting.section.liability') && !p('user.liability')) {
                    $app->abort(403);
                }
            });

        $factory->match('/site-balance/', 'App\Controllers\AccountingController::siteBalance')
            ->bind('accounting-site-balance')
            ->before(function () use ($app) {
                if (!p('accounting.section.site-balance')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/player-balance/', 'App\Controllers\AccountingController::playerBalance')
            ->bind('accounting-player-balance')
            ->before(function () use ($app) {
                if (!p('accounting.section.player-balance')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/transaction-history/', 'App\Controllers\AccountingController::transactionHistory')
            ->bind('accounting-transaction-history')
            ->before(function () use ($app) {
                if (!p('accounting.section.transaction-history')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/pending-withdrawals/', 'App\Controllers\AccountingController::pendingWithdrawals')
            ->bind('accounting-pending-withdrawals')
            ->before(function () use ($app) {
                if (!p('accounting.section.pending-withdrawals')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/transfer-stats/', 'App\Controllers\AccountingController::transferStats')
            ->bind('accounting-transfer-stats')
            ->before(function () use ($app) {
                if (!p('accounting.section.transfer-stats')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/consolidation/', 'App\Controllers\AccountingController::consolidation')
            ->bind('accounting-consolidation')
            ->before(function () use ($app) {
                if (!p('accounting.section.consolidation')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/gaming-revenue-report/', 'App\Controllers\AccountingController::gamingRevenueReport')
            ->bind('accounting-gaming-revenue-report')
            ->before(function () use ($app) {
                if (!p('accounting.section.gaming-revenue')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/jackpot-log/', 'App\Controllers\AccountingController::jackpotLog')
            ->bind('accounting-jackpot-log')
            ->before(function () use ($app) {
                if (!p('accounting.section.jackpot-logs')) {
                    $app->abort(403);
                }
            })->method('GET|POST');

        $factory->match('/open-bets/', 'App\Controllers\AccountingController::openBets')
            ->bind('accounting-open-bets')
            ->before(function () use ($app) {
                if (!p('accounting.section.open-bets')) {
                    $app->abort(403);
                }
            })->method('GET|POST');

        $factory->match('/vaults/', 'App\Controllers\AccountingController::playerVaultReport')
            ->bind('accounting-vaults')
            ->before(function () use ($app) {
                if (!p('accounting.section.open-bets')) {
                    $app->abort(403);
                }
            })->method('GET|POST');

        return $factory;
    }

    public function index(Application $app, Request $request)
    {
        return $app['blade']->view()->make('admin.accounting.index', compact('app'))->render();
    }

    public function transferStats(Application $app, Request $request)
    {
        $repo = new AccountingRepository();
        $params = [];
        if (empty($request->get('date-range'))) {
            $params['start_date'] = Carbon::now()->firstOfMonth()->format('Y-m-d');
            $params['end_date'] = Carbon::now()->endOfMonth()->format('Y-m-d');
        } else {
            $params['start_date'] = explode(' - ', $request->get('date-range'))[0];
            $params['end_date'] = explode(' - ', $request->get('date-range'))[1];
        }

        $stats_period = $repo->getTransferStatsData($request, $params);
        $stats_total = $repo->getTransferStatsData($request);
        //p('accounting.section.transfer-stats.download.csv')

        return $app['blade']->view()->make('admin.accounting.transfer-stats', compact('app', 'paginator', 'params', 'stats_period', 'stats_total', 'balance_stats', 'currency'))->render();
    }

    public function transactionHistory(Application $app, Request $request)
    {
        $repo = new AccountingRepository();

        if ($request->isMethod('POST')) {
            $params = [];
            FilterFormHelper::processSerializedFormData($request);

            $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_CUR_MONTH);
            $params['start_date'] = $date_range->getStart('timestamp');
            $params['end_date'] = $date_range->getEnd('timestamp');
            $paginator = $repo->getTransferStatsList($request, $app, $params, false);

            return $app->json($paginator);
        } else {
            $params = [];
            $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_PAST_WEEK);
            $params['start_date'] = $date_range->getStart('timestamp');
            $params['end_date'] = $date_range->getEnd('timestamp');
            if (!empty($request->get('export')) && empty($request->get('sendtobrowse')) && p('accounting.section.transaction-history.download.csv')) {
                try {
                    $params['return_query'] = 1;
                    return $repo->exportTransferStats($app, $repo->getTransferStatsList($request, $app, $params), $request);
                } catch (\Exception $e) {
                    $app['monolog']->addError("Tx history download failed due to {$e->getMessage()}");
                }
            } elseif ($request->get('sendtobrowse') == 1) {
                $params['return_query'] = 1;
                $params['only_users_id'] = 1;
                $user_list = collect($repo->getTransferStatsList($request, $app, $params)->get())->pluck('id')->all();
                $uc = new UserController();
                return $uc->userSearchList($app, $request, $user_list);
            }
            $paginator = $repo->getTransferStatsList($request, $app, $params);

            $status = $request->get('status');

            return $app['blade']->view()->make('admin.accounting.transaction-history', compact('app', 'paginator', 'params', 'date_range', 'currency', 'status'))->render();
        }
    }

    public function pendingWithdrawals(Application $app, Request $request)
    {
        $repo = new AccountingRepository();

        $request->query->set('show', 'withdrawals');

        $params = [];
        $date_range = $repo->shouldApplyDateRange($request) ? DateRange::rangeFromRequest($request) : '';

        $params['start_date'] = ($date_range instanceof DateRange) ? $date_range->getStart('timestamp') : '';
        $params['end_date'] = ($date_range instanceof DateRange) ? $date_range->getEnd('timestamp') : '';
        $params['status'] = 'pending';

        if ($request->isMethod('POST')) {
            FilterFormHelper::processSerializedFormData($request);

            $paginator = $repo->processPendingWithdrawals(
                $repo->getTransferStatsList($request, $app, $params, false, [$repo, 'filterPWBeforePagination']),
                $request
            );

            return $app->json($paginator);
        }

        if (!empty($request->get('export')) && p('accounting.section.pending-withdrawals.download.csv')) {
            try {
                $params['return_query'] = 1;

                $dataCollection = $repo->getTransferStatsList($request, $app, $params)->orderBy('date', 'desc')->get();
                $filteredData = $repo->filterPWBeforePagination($request, $dataCollection);
                $paginator = $repo->processPendingWithdrawals(['data' => $filteredData->toArray()], $request, true);

                return $repo->exportPendingWithdrawals($app, $paginator);
            } catch (\Exception $e) {
                $app['monolog']->addError("Pending Withdrawals download failed due to {$e->getMessage()}");
            }
        } else {
            $paginator = $repo->getTransferStatsList($request, $app, $params, true, [$repo, 'filterPWBeforePagination']);
        }

        $paginator = $repo->processPendingWithdrawals($paginator, $request);
        return $app['blade']->view()->make('admin.accounting.pending-withdrawals', compact('app', 'paginator', 'params', 'date_range'))->render();
    }

    public function consolidation(Application $app, Request $request)
    {
        $repo = new AccountingRepository();

        //$app['request_stack']->getCurrentRequest()->get()

        if ($request->isXmlHttpRequest()) {
            $date_range = DateRange::rangeFromRequest($request);
            $queries = $repo->getConsolidationQueries($app, $request, $date_range);
            $default = ['order' => ['column' => 'trans_time', 'dir' => 'desc'], 'length' => 25];
            $dep_made_pag = new PaginationHelper($queries['deposits_made_query'], $request, $default);
            $dep_reported_pag = new PaginationHelper($queries['deposits_reported_query'], $request, $default);
            $data = [
                'dep_made' => $dep_made_pag->getPage(false),
                'dep_reported' => $dep_reported_pag->getPage(false)
            ];

            return $app->json($data['dep_made']);
        } else {
            if (empty($request->get('date-range')) || empty($request->get('provider'))) {
                $date_range = new DateRange(null, null);
            } else {
                $date_range = DateRange::rangeFromRequest($request);
                $queries = $repo->getConsolidationQueries($app, $request, $date_range);
                $default = ['order' => ['column' => 'trans_time', 'dir' => 'desc'], 'length' => 25];
                $dep_made_pag = new PaginationHelper($queries['deposits_made_query'], $request, $default);
                $dep_reported_pag = new PaginationHelper($queries['deposits_reported_query'], $request, $default);
                $data = [
                    'dep_made' => $dep_made_pag->getPage(),
                    'dep_reported' => $dep_reported_pag->getPage()
                ];
            }
            $providers = DB::select('SELECT DISTINCT method FROM external_transactions');
            return $app['blade']->view()->make('admin.accounting.consolidation', compact('app', 'date_range', 'data', 'providers'))->render();
        }
    }

    public function playerBalance(Application $app, Request $request)
    {
        $repo = new AccountingRepository();

        if ($request->isMethod('POST')) {
            $query_data = [];
            foreach ($request->get('form') as $form_elem) {
                $query_data[$form_elem['name']] = $form_elem['value'];
            };
            $paginator = $repo->getUserBalanceData($request, $query_data, false);
            return $app->json($paginator);
        }

        $query_params = [
            'date' => $request->get('date'),
            'currency' => $request->get('currency'),
            'country' => $request->get('country')
        ];
        if (!empty($request->get('export')) && p('accounting.section.player-balance.download.csv')) {
            $query_params['return_query'] = 1;
            return $repo->exportUserBalanceData($app, $repo->getUserBalanceData($request, $query_params), $query_params);
        }
        $paginator = $repo->getUserBalanceData($request, $query_params);

        return $app['blade']->view()->make('admin.accounting.player-balance', compact('app', 'paginator', 'query_params'))->render();
    }

    public function siteBalance(Application $app, Request $request)
    {
        $data = [];

        if (!empty($request->get('year')) && !empty($request->get('month')) && !empty($request->get('currency'))) {
            $repo = new AccountingRepository();
            $date = Carbon::create($request->get('year'), $request->get('month'));
            $query_data = [
                'year' => $date->format('Y'),
                'month' => $date->format('m'),
                'currency' => $request->get('currency', 'EUR'),
                'country' => $request->get('country', 'all'),
                'provinces' => $request->get('provinces', ''),
                'source' => $request->get('source', 'all'),
            ];

            if ($date->gte(Carbon::create(2016, 12))) {
                $data = $repo->getTotalBalanceData($query_data);
                $use_bonus_balance = true;
            } else {
                $data = $repo->getLegacyTotalBalanceData($query_data);
                $use_bonus_balance = $repo->useBonusBalance($query_data);
            }

            if (!empty($request->get('export')) && p('accounting.section.liability.download.csv')) {
                return $repo->exportTotalBalanceData($app, $data, $query_data);
            }
        }
        return $app['blade']->view()->make('admin.accounting.site-balance', compact('app', 'data', 'query_data', 'use_bonus_balance'))->render();
    }

    /**
     * This data is only available since 2016-12-01
     *
     * @param Application $app
     * @param Request $request
     * @param User $user
     * @return mixed
     */
    public function playerLiability(Application $app, Request $request, User $user)
    {
        if ($request->isMethod('GET') && (empty($request->get('type') || !empty($request->get('export'))))) {
            return $app['blade']->view()->make('admin.user.liability.main', compact('app', 'user'))->render();
        } else {
            if ($request->get('type') == 'monthly') {
                if (!empty($request->get('year'))) {
                    $date = Carbon::create($request->get('year'), $request->get('month'));
                } else {
                    $date = Carbon::createFromFormat('F-Y', $request->get('month'));
                }

                $repo = new LiabilityRepository($date->year, $date->month, 0);

                $query_data = [
                    'year' => $date->year,
                    'month' => $date->month,
                    'currency' => $user->currency,
                    'country' => 'all',
                    'province' => [],
                    'source' => 0,
                    'type' => 'monthly'
                ];

                $opening_data['net'] = $user->repo->getBalance($date->copy()->startOfMonth());

                if ($date->isCurrentMonth()) {
                    $closing_data['closing_balance'] = $user->repo->getBalance();
                    $cur_month = $repo->getCurrentMonth($user);

                    $data = $cur_month['data'];
                    $closing_data['net_liability'] = $cur_month['net_liability'];

                    $view = 'admin.user.liability.current-month';
                } else {
                    $closing_data['closing_balance'] = $user->repo->getBalance($date->copy()->addMonth()->startOfMonth());

                    $closing_data['net_liability'] = DB::shSelect($user->getKey(), 'users_monthly_liability', "SELECT IFNULL(SUM(amount),0) AS total
                          FROM users_monthly_liability
                          WHERE source = 0 AND year = :d_year AND month = :d_month AND user_id = :user_id", [
                        'd_year' => $date->year,
                        'd_month' => $date->month,
                        'user_id' => $user->getKey()
                    ])[0]->total;

                    $data = $repo->getMonthlyData($query_data['currency'], $query_data['country'], $query_data['province'], $user->getKey());

                    $view = 'admin.accounting.partials.liability-table';
                }

                $closing_data['non_categorized_amount'] = (($opening_data['net'] + $closing_data['net_liability']) - $closing_data['closing_balance']) * -1;

                if (!empty($request->get('export')) && p('user.liability.transactions.download.csv')) {
                    $extra = [
                        'user id' => $user->getKey(),
                        'username' => $user->username
                    ];
                    return $repo->exportMonthlyData($data, $opening_data, $closing_data, $app, $request->get('year'), $request->get('month'), $extra, $request->get('breakdown'));
                }

                if (!empty($request->get('export_monthly_transactions'))
                    && p('user.liability.transactions.download.csv')) {
                    $date_to_export = [
                        'start' => $date->copy()->startOfMonth(),
                        'end' => $date->copy()->endOfMonth()
                    ];
                    $queries = LiabilityRepository::getUserTransactionListQueries($user, $date_to_export, true);

                    return LiabilityRepository::exportUserTransactionList(
                        $app,
                        "Transactions_list_{$user->id}_{$user->username}_{$date->copy()->format('F-Y')}",
                        $queries,
                        true,
                        $user->repo->getBalance($date->copy()->startOfMonth())
                    );
                }

                if ($request->isMethod('GET')) {
                    return $app['blade']->view()->make('admin.user.liability.main', compact('app', 'user', 'view', 'data', 'opening_data', 'closing_data', 'query_data'))->render();
                } else {
                    return $app->json([
                        'html' => $app['blade']->view()->make($view, compact('app', 'user', 'data', 'opening_data', 'closing_data', 'query_data'))->render(),
                        'type' => 'monthly',
                        'unallocated' => $closing_data['non_categorized_amount']
                    ]);
                }

            } elseif ($request->get('type') == 'daily') {
                $filter = $request->get('filter', $request->get('transactions', 'liability') == 'all' ? false : true);
                $day = $request->get('day', Carbon::now()->toDateString());

                $queries = LiabilityRepository::getUserTransactionListQueries($user, Carbon::parse($day), $filter);

                if (!empty($request->get('export')) && p('user.liability.report.download.csv')) {
                    return LiabilityRepository::exportUserTransactionList(
                        $app,
                        "Transactions_list_{$user->id}_{$user->username}_{$day}",
                        $queries,
                        true,
                        $user->repo->getBalance(Carbon::createFromFormat('Y-m-d', $day))
                    );
                }

                $paginator = PaginationHelper::makeFromUnion(
                    $queries['bets'],
                    [$queries['wins'], $queries['cash'], $queries['sports']],
                    $request,
                    ['order' => ['column' => 'date', 'dir' => 'asc'], 'length' => 5000],
                );

                $page = $paginator->getPage($request->get('page') == 1);

                $page['data'] = array_map(function ($value) {
                    $value->type_original = $value->type;
                    $value->type = is_numeric($value->type) ? Dfh::getCashTransactionsTypeName($value->type) : ucwords($value->type);
                    return $value;
                }, $page['data']);

                $page['data'] = LiabilityRepository::calculateRunningBalance($page['data']);
                if ($request->get('page') == 1) {
                    return $app->json([
                        'html' => $app['blade']->view()->make('admin.user.liability.transactions-list', compact('app', 'user', 'filter', 'day', 'page'))->render(),
                        'type' => 'daily',
                        'recordsTotal' => $page['recordsTotal']
                    ]);
                } else {
                    return $app->json($page);
                }

            } elseif ($request->get('type') == 'unallocated') {
                $date = Carbon::createFromFormat('F-Y', $request->get('month'))->startOfMonth();

                $repo = new LiabilityRepository($date->year, $date->month);
                $data = $repo->getUnallocatedAmount($user, 0, null, true);

                return $app->json([
                    'html' => $app['blade']->view()->make('admin.user.liability.unallocated-month', compact('app', 'user', 'data', 'date'))->render(),
                    'type' => 'unallocated'
                ]);
            } else {
                return $app->json([
                    'html' => '<div>Type not supported.</div>',
                    'type' => 'none'
                ]);
            }
        }
    }

    public function getCategoryData(Application $app, Request $request)
    {
        $repo = new LiabilityRepository($request->get('year'), $request->get('month'), $request->get('source', 'all'), $request->get('user'));
        $type = in_array($request->get('cat'), [13, 14, 15, 17, 21, LiabilityRepository::CAT_LIABILITY_ADJUST, LiabilityRepository::CAT_BOOSTER_VAULT_TRANSFER, LiabilityRepository::CAT_TAX_DEDUCTION]) ? $request->get('type') : null;
        return $app->json($repo->getMonthlyDataPerCategory(
            $request->get('currency'),
            $request->get('cat'),
            $request->get('country', 'all'),
            $request->get('province', []),
            $type,
            true
        ));
    }

    /**
     * Player Liability Adjustment Page Logic
     *
     * @param Application $app
     * @param Request $request
     * @param User $user
     * @return mixed
     * @throws \Exception
     */
    public function playerLiabilityAdjust(Application $app, Request $request, User $user)
    {
        $success = null;
        $form_submittable = true;
        $errors = [];

        $repo = new LiabilityRepository(Carbon::now()->year, Carbon::now()->month);
        $unallocated_amounts = $repo->getLastTotalLastPeriod($user);

        $not_allowed_jurisdictions = Phive('Config')->getValue('liabilities', 'not_allowed_jurisdiction', 'AGCO,DGOJ,ADM');
        $not_allowed_jurisdictions = explode( ',', $not_allowed_jurisdictions);
        $jurisdiction = $user->repo->getJurisdiction();
        $valid_jurisdiction = !in_array($jurisdiction, $not_allowed_jurisdictions);
        if (!$valid_jurisdiction) {
            $errors['valid_jurisdiction'] = ["Liability Adjustments are not allowed in this Jurisdiction"];
            $form_submittable = false;
        }

        if ($request->isMethod('POST')) {
            $errors = $this->validatePlayerLiabilityAdjustmentRequest($request, $user);

            if (empty($errors)) {
                LiabilityRepository::addLiabilityAdjustment(
                    $user,
                    $request->get('amount'),
                    $request->get('target_month') === 'Previous',
                    $request->get('description')
                );

                $success = true;
            } else {
                $success = false;
            }
        }

        $target_month_list[] = 'Current';
        try {
            $were_liabilities_processed_for_last_month = LiabilityRepository::wereLiabilitiesProcessedForLastMonth();
        } catch(LiabilitiesProcessedException $e) {
            $were_liabilities_processed_for_last_month = false;
            $errors[] = ['config_error' => 'Failed to retrieve last processed year and month for liabilities'];
            $success = false;
        }

        if (!$were_liabilities_processed_for_last_month) {
            $target_month_list[] = 'Previous';
        }


        if (is_string($were_liabilities_processed_for_last_month)) {
            $errors[] = ['misc_cache' => 'Failed to retrieve last processed year and month for liabilities'];
            $success = false;
        }

        return $app['blade']->view()->make('admin.user.liability.liability-adjust',
            compact(
                'app',
                'user',
                'target_month_list',
                'success',
                'unallocated_amounts',
                'errors',
                'jurisdiction',
                'form_submittable'
            ))->render();
    }

    public function playerLiabilityReport(Application $app, Request $request)
    {
        $province = array_filter($request->get('province', []), fn($p) => $p !== 'all');

        if ($request->isMethod('POST')) {
            $repo = new LiabilityRepository($request->get('year'), $request->get('month'), $request->get('source', 'all'));
            $type = in_array($request->get('cat'), [13, 14, 15, 17, 21, LiabilityRepository::CAT_LIABILITY_ADJUST, LiabilityRepository::CAT_BOOSTER_VAULT_TRANSFER]) ? $request->get('type') : null;
            return $app->json($repo->getMonthlyDataPerCategory(
                $request->get('currency'),
                $request->get('cat'),
                $request->get('country', 'all'),
                $province,
                $type,
                true
            ));
        }


        if (!empty($request->get('year')) || !empty($request->get('month'))) {
            if (Carbon::create($request->get('year'), $request->get('month'))->lt(Carbon::create(2016, 11)) && ($request->get('source') != 'all' || $request->get('source') != 'vs')) {
                $app['flash']->add('warning', "Source not supported. Showing all by default.");
                $request->request->set('source', 'all');
            }

            if (!empty($request->get('country')) && $request->get('country') != 'all' && $request->get('source') != 'vs') {
                $app['flash']->add('warning', "Source must be Videoslots when filtering by country.");
                return new RedirectResponse($request->headers->get('referer'));
            }

            $query_data = [
                'year' => $request->get('year'),
                'month' => $request->get('month'),
                'currency' => $request->get('currency'),
                'country' => $request->get('country', 'all'),
                'province' => $province,
                'source' => $request->get('source', 'all'),
            ];
            $repo = new LiabilityRepository($request->get('year'), $request->get('month'), $request->get('source', 'all'));
            $repoAcc = new AccountingRepository();

            if ($request->get('liabilities_processed') &&
                p('accounting.section.liability-report-adjusted-month-update')) {
                $success = true;
                try {
                    $result = $repo->updateLiabilitiesProcessedMonth($query_data['year'], $query_data['month']);
                } catch (UpdateLiabilitiesException $e) {
                    $success = false;
                    $message = $e->getMessage();
                }

                return $app->json([
                    'success' => $success,
                    'message' => $message
                ]);
            }

            if (!empty($request->get('m'))) {
                $data = $repo->getDifferencesPerCurrency($query_data);

                if (!empty($request->get('export')) && p('accounting.section.liability.download.csv')) {
                    return $repo->exportDifferencesPerCurrency(
                        $app,
                        $data,
                        "PLR_unallocated_amounts_{$query_data['currency']}_{$query_data['year']}-{$query_data['month']}"
                    );
                } else {
                    return $app['blade']->view()->make('admin.accounting.liability-mismatch', compact('app', 'data', 'date', 'query_data'))->render();
                }
            }

            $opening_data['net'] = $repoAcc->getBalanceData($query_data, $request, $repo);

            $net_sum_query = DB::table('users_monthly_liability as uml', replicaDatabaseSwitcher(true), true)->selectRaw('sum(uml.amount) as total')
                ->leftJoin('users as u', 'u.id', '=', 'uml.user_id')
                ->where('year', $query_data['year'])->where('month', $query_data['month'])
                ->where('uml.currency', $query_data['currency']);

            if ($repo->source !== false) {
                $net_sum_query->where('source', $repo->source);
            }
            if ($query_data['country'] != 'all') {
                $net_sum_query->where('uml.country', $query_data['country']);
            }
            if (!empty($query_data['province'])) {
                $net_sum_query->whereIn('uml.province', $query_data['province']);
            }
            $closing_data['net_liability'] = $net_sum_query->get()[0]->total;

            $next_month = Carbon::create($query_data['year'], $query_data['month'], 1)->addMonth();
            $closing_data['closing_balance'] = $repoAcc->getBalanceData([
                'year' => $next_month->year,
                'month' => $next_month->month,
                'currency' => $query_data['currency'],
                'country' => $query_data['country'],
                'province' => $query_data['province'],
                'source' => $query_data['source']
            ],
                $request,
                $repo
            );

            if (Carbon::create($query_data['year'], $query_data['month'])->lt(Carbon::create(2016, 11)) && $query_data['country'] != 'all') {
                $opening_data['net'] = 0;
                $closing_data['closing_balance'] = 0;
                $app['flash']->add('warning', "It is not possible to get balances per country prior to December, 2016. Due to balances stats per user are not available during that period.");
            }
            $closing_data['non_categorized_amount'] = (($opening_data['net'] + $closing_data['net_liability']) - $closing_data['closing_balance']) * -1;

            if (!empty($request->get('year')) && !empty($request->get('month')) && !empty($request->get('export')) && p('accounting.section.liability.download.csv')) {
                $extra = [];
                if (!empty($query_data['country'])) {
                    $extra['country'] = $query_data['country'];
                }
                if (!empty($request->get('currency'))) {
                    $extra['currency'] = $request->get('currency');
                }
                if (!empty($query_data['province'])) {
                    $extra['province'] = $query_data['province'];
                }
                return $repo->exportMonthlyData(
                    $this->shiftFreeSpinsOrder($repo->getMonthlyData(
                        $query_data['currency'],
                        $query_data['country'],
                        $query_data['province'],
                        null,
                        null,
                        false
                    )),
                    $opening_data,
                    $closing_data,
                    $app,
                    $request->get('year'),
                    $request->get('month'),
                    $extra,
                    $request->get('breakdown')
                );
            } else {
                $data = $repo->getMonthlyData(
                    $query_data['currency'],
                    $query_data['country'],
                    $query_data['province'],
                    null,
                    null,
                    null
                );
                $data = $this->shiftFreeSpinsOrder($data);

                $liabilities_processed = $repo->wereLiabilitiesProcessedForTheMonth();

                $show_liabilities_processed_status = p('accounting.section.liability-report-adjusted-month') &&
                    !empty($request->get('year')) &&
                    !empty($request->get('month')) &&
                    $request->get('source') === LiabilityRepository::SOURCE_DISPLAY_NAME;

                return $app['blade']->view()->make('admin.accounting.liability', compact(
                    'app',
                    'data',
                    'opening_data',
                    'closing_data',
                    'query_data',
                    'liabilities_processed',
                    'show_liabilities_processed_status'
                ))->render();
            }
        } else {
            return $app['blade']->view()->make('admin.accounting.liability', compact('app', 'data', 'opening_data', 'closing_data', 'query_data'))->render();
        }

    }

    public function playerVaultReport(Application $app, Request $request)
    {

        $isYearReport = !empty($request->get('year')) && empty($request->get('month'));
        $getMonth = !empty($request->get('month')) ? $request->get('month') : Carbon::now()->month;
        $month = $isYearReport ? 1 : $getMonth;
        $year = !empty($request->get('year')) ? $request->get('year') : Carbon::now()->year;
        // for closing balance
        $monthClosingBalance = $isYearReport ? 1 : Carbon::create(0, $month)->addMonths()->month;
        $yearClosingBalance = Carbon::create($request->get('year'))->addYear()->year;
        $next_month = Carbon::create($year, $month, 1)->addMonth();

        $query_data = [
            'year' => $year,
            'month' => $month,
            'currency' => $request->get('currency'),
            'country' => $request->get('country', 'all'),
            'province' => array_filter($request->get('province', []), fn($p) => $p !== 'all'),
            'source' => $request->get('source', 'all'),
        ];

        $repo = new LiabilityRepository($year, $month, $request->get('source', 'all'));

        $opening_data['opening'] = $repo->getVaultBalanceData([
            'year' => $year,
            'month' => $month,
            'currency' => $query_data['currency'],
            'country' => $query_data['country'],
            'source' => $query_data['source']
        ]);
        $closing_data['closing_balance'] = $repo->getVaultBalanceData([
            'year' => $isYearReport ? $yearClosingBalance : $next_month->year,
            'month' => $isYearReport ? $monthClosingBalance : $next_month->month,
            'currency' => $query_data['currency'],
            'country' => $query_data['country'],
            'source' => $query_data['source']
        ]);
        $data = $repo->getMonthlyVaultData(
            $query_data['currency'],
            $query_data['country'],
            $query_data['province'],
            $isYearReport
        );
        $closing_data['net_liability'] = $data->sum('to_vault') + $data->sum('from_vault');
        $closing_data['non_categorized_amount'] = (($opening_data['opening'] + $closing_data['net_liability']) - $closing_data['closing_balance']) * -1;

        return $app['blade']->view()->make('admin.accounting.vault', compact('app', 'data', 'closing_data', 'query_data', 'opening_data'))->render();
    }

    public function gamingRevenueReport(Application $app, Request $request)
    {
        $month_range = DateRange::monthRangeFromRequest($request, DateRange::DEFAULT_PREV_MONTH, 'month-range-start', 'month-range-end');
        $jurisdiction = $request->get('jurisdiction', 'all');

        $accounting_repo = new AccountingRepository();
        $data_array = $accounting_repo->getGameRevenue($month_range, $jurisdiction);

        if (!empty($request->get('export')) && p('accounting.section.gaming-revenue.download.csv')) {

            return $accounting_repo->gamingRevenueReportExport(
                $app,
                $data_array['result'],
                $month_range
            );
        }

        return $app['blade']->view()->make('admin.accounting.gaming-revenue-report', compact('app', 'data_array', 'month_range', 'jurisdiction'))->render();
    }

    /**
     * Get jackpots log
     *
     * @param Application $app
     * @param Request $request
     * @return string
     */
    public function jackpotLog(Application $app, Request $request)
    {
        if ($is_xml_req = $request->isXmlHttpRequest()) {
            foreach ($request->get('form') as $form_elem) {
                $request->request->set($form_elem['name'], $form_elem['value']);
            }
        }

        $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_TODAY);

        $repo = new AccountingRepository();
        $query = $repo->getJackpotLog($request);
        // A value will always be set for this.
        $query->whereBetween('jp_log.created_at', $date_range->getWhereBetweenArray());

        $paginator = new PaginationHelper($query, $request, ['length' => 25, 'order' => ['column' => 'created_at', 'dir' => 'DESC']]);

        if ($request->isXmlHttpRequest()) {
            $page = $paginator->getPage(false);
            return $app->json($page);
        } else {
            $page = $paginator->getPage();

            $networks = DB::connection(replicaDatabaseSwitcher(true))->table('jp_log')->selectRaw('DISTINCT network')->get()->pluck('network');
            $jp_names = DB::connection(replicaDatabaseSwitcher(true))->table('jp_log')->selectRaw('DISTINCT jp_name')->get()->pluck('jp_name');
            $game_names = DB::connection(replicaDatabaseSwitcher(true))->table('jp_log')
                ->selectRaw('DISTINCT game_name')
                ->join('micro_games', 'game_ref', '=', 'ext_game_name')
                ->get()->pluck('game_name');

            return $app['blade']->view()
                ->make('admin.game.jackpot-log-report', compact('page', 'app', 'date_range', 'networks', 'jp_names', 'game_names'))
                ->render();
        }
    }

    /**
     * Get open bets
     *
     * @param Application $app
     * @param Request $request
     * @return string
     */
    public function openBets(Application $app, Request $request)
    {
        $month_range = DateRange::monthRangeFromRequest($request, DateRange::DEFAULT_PREV_MONTH, 'month-range-start', 'month-range-end');
        $jurisdiction = $request->get('jurisdiction', 'all');

        $accounting_repo = new AccountingRepository();
        $data_array = $accounting_repo->getOpenBets($month_range, $jurisdiction);

        if (!empty($request->get('export')) && p('accounting.section.open-bets.download.csv')) {

            return $accounting_repo->getOpenBetsExport(
                $app,
                $data_array['result'],
                $month_range
            );
        }

        return $app['blade']->view()
            ->make('admin.accounting.open-bets', compact('app', 'data_array', 'month_range', 'jurisdiction'))
            ->render();
    }

    /**
     * Move 'CAT_FRB_WINS' to be right after 'CAT_WINS' for the liability monthly report
     * @param Collection $monthly_data
     * @return Collection
     */
    private function shiftFreeSpinsOrder(Collection $monthly_data)
    {
        $cat_wins_index = $monthly_data->search(function ($item) {
            return $item->main_cat === LiabilityRepository::CAT_WINS;
        });
        $cat_frb_wins_index = $monthly_data->search(function ($item) {
            return $item->main_cat === LiabilityRepository::CAT_FRB_WINS;
        });

        if ($cat_wins_index === false || $cat_frb_wins_index === false) {
            return $monthly_data;
        }

        $cat_frb_wins = $monthly_data->get($cat_frb_wins_index);
        $monthly_data->splice($cat_frb_wins_index, 1);
        $monthly_data->splice($cat_wins_index + 1, 0, [$cat_frb_wins]);
        return $monthly_data;
    }

    /**
     *  Validates the post request for liability adjustment insertion
     *
     * @param Request $request
     * @param User $user
     * @return array
     */
    public function validatePlayerLiabilityAdjustmentRequest(Request $request, User $user): array
    {
        $data = $request->request->all();

        $validator = new Validator($data);

        $validator->addRule('validateTargetMonth', function ($field, $value) {
            return in_array($value, ['Current', 'Previous']);
        }, ['is not a valid month']);

        $allowed_maximum_adjustment = phive('Config')
            ->getValue('liabilities', 'allowed_maximum_adjustment_in_eur', 1000000);

        $allowed_maximum_adjustment = mc($allowed_maximum_adjustment, $user->currency);

        $validator->rules([
            'required' => [
                ['amount'], ['target_month'], ['description']
            ],
            'numeric' => [
                ['amount']
            ],
            'min' => [
                ['amount', $allowed_maximum_adjustment * -1]
            ],
            'max' => [
                ['amount', $allowed_maximum_adjustment]
            ],
            'validateTargetMonth' => [
                ['target_month']
            ],
            'lengthMax' => [
                ['description', 255]
            ]
        ]);

        $validator->validate();

        return $validator->errors();
    }
}
