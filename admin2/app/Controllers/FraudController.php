<?php

/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 2015.11.17.
 * Time: 9:29
 */

namespace App\Controllers;

use App\Classes\DateRange;
use App\Classes\FormBuilder\Elements\ElementInterface;
use App\Classes\FormBuilder\FormBuilder;
use App\Classes\Fraud\MasterConnectionHelper;
use App\Classes\GoAML\Export;
use App\Classes\GoAML\Jurisdiction;
use App\Classes\Mts;
use App\Extensions\Database\FManager as DB;
use App\Classes\GoAML\GoAML;
use App\Helpers\PaginationHelper;
use App\Models\BankCountry;
use App\Models\FraudGroup;
use App\Models\FraudRule;
use App\Models\RiskProfileRating;
use App\Models\User;
use App\Repositories\FraudGroupsRepository;
use App\Repositories\FraudRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Pimple\Container;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;


class FraudController implements ControllerProviderInterface
{
    /**
     * @var \App\Classes\Fraud\MasterConnectionHelper
     */
    private MasterConnectionHelper $masterConnectionHelper;

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/', 'App\Controllers\FraudController::dashboard')
          ->bind('fraud-dashboard')
          ->before(function () use ($app) {
              if (!p('fraud.section')) {
                  $app->abort(403);
              }
          });

        $factory->match('/high-deposits/',
          'App\Controllers\FraudController::highDeposits')
          ->bind('fraud-high-deposits')
          ->before(function () use ($app) {
              if (!p('fraud.section') || !p('fraud.section.high-depositors')) {
                  $app->abort(403);
              }
          })->method('GET|POST');

        $factory->get('/non-turned-over-withdrawals/',
          'App\Controllers\FraudController::nonTurned')
          ->bind('fraud-non-turned-over-withdrawals')
          ->before(function () use ($app) {
              if (!p('fraud.section') || !p('fraud.section.non-turned-over-withdrawals')) {
                  $app->abort(403);
              }
          });

        $factory->get('/anonymous-methods/',
          'App\Controllers\FraudController::anonymousMethods')
          ->bind('fraud-anonymous-methods')
          ->before(function () use ($app) {
              if (!p('fraud.section') || !p('fraud.section.anonymous-methods')) {
                  $app->abort(403);
              }
          });

        $factory->get('/multi-method-transactions/',
          'App\Controllers\FraudController::multiMethods')
          ->bind('fraud-multi-method-transactions')
          ->before(function () use ($app) {
              if (!p('fraud.section') || !p('fraud.section.multi-method-transactions')) {
                  $app->abort(403);
              }
          });

        $factory->get('/big-winners/',
          'App\Controllers\FraudController::bigWinners')
          ->bind('fraud-big-winners')
          ->before(function () use ($app) {
              if (!p('fraud.section') || !p('fraud.section.big-winners')) {
                  $app->abort(403);
              }
          });

        $factory->get('/big-losers/',
          'App\Controllers\FraudController::bigLosers')
          ->bind('fraud-big-losers')
          ->before(function () use ($app) {
              if (!p('fraud.section') || !p('fraud.section.big-losers')) {
                  $app->abort(403);
              }
          });

        $factory->get('/big-depositors/',
          'App\Controllers\FraudController::bigDepositors')
          ->bind('fraud-big-depositors')
          ->before(function () use ($app) {
              if (!p('fraud.section') || !p('fraud.section.big-depositors')) {
                  $app->abort(403);
              }
          });

        $factory->get('/daily-gladiators/',
          'App\Controllers\FraudController::dailyGladiators')
          ->bind('fraud-daily-gladiators')
          ->before(function () use ($app) {
              if (!p('fraud.section') || !p('fraud.section.daily-gladiators')) {
                  $app->abort(403);
              }
          });

        $factory->match('/failed-deposits/',
          'App\Controllers\FraudController::failedDeposits')
          ->bind('fraud-failed-deposits')
          ->before(function () use ($app) {
              if (!p('fraud.section') || !p('fraud.section.failed-deposits')) {
                  $app->abort(403);
              }
          })
          ->method('GET|POST');

        $factory->match('/bonus-abusers/',
          'App\Controllers\FraudController::bonusAbusers')
          ->bind('fraud-bonus-abusers')
          ->before(function () use ($app) {
              if (!p('fraud.section') || !p('fraud.section.bonus-abusers')) {
                  $app->abort(403);
              }
          })
          ->method('GET|POST');

        $factory->match('/fraud-groups/',
          'App\Controllers\FraudController::fraudGroups')
          ->bind('fraud-groups')
          ->before(function () use ($app) {
              if (!p('fraud.section') || !p('fraud.section.fraud-groups')) {
                  $app->abort(403);
              }
          })
          ->method('GET|POST');

        $factory->match('/fraud-groups/{id}/',
          'App\Controllers\FraudController::fraudGroups')
          ->assert('id', '\d+')
          ->bind('groups')
          ->before(function () use ($app) {
              if (!p('fraud.section') || !p('fraud.section.fraud-groups')) {
                  $app->abort(403);
              }
          })
          ->method('GET|POST');

        $factory->post('/fraud-rules/delete/',
          'App\Controllers\FraudController::fraudRuleDelete')
          ->before(function () use ($app) {
              if (!p('fraud.section') || !p('fraud.fraud.section.fraud-rule-sets')) {
                  $app->abort(403);
              }
          });

        $factory->match('/fraud-rules/',
          'App\Controllers\FraudController::fraudRules')
          ->bind('fraud-rules')
          ->before(function () use ($app) {
              if (!p('fraud.section') || !p('fraud.fraud.section.fraud-rule-sets')) {
                  $app->abort(403);
              }
          })
          ->method('GET|POST');

        $factory->match('/fraud-rules/{id}/',
          'App\Controllers\FraudController::fraudRules')
          ->assert('id', '\d+')
          ->bind('rules')
          ->before(function () use ($app) {
              if (!p('fraud.section') || !p('fraud.fraud.section.fraud-rule-sets')) {
                  $app->abort(403);
              }
          })
          ->method('GET|POST');

        $factory->match('/similar-account/',
          'App\Controllers\RgController::similarAccount')
          ->bind('similar-account')
          ->before(function () use ($app) {
              if (!p('fraud.section') || !p('fraud.fraud.section.similar-account')) {
                  $app->abort(403);
              }
          })
          ->method('GET|POST');
        $factory->match('/min-fraud/', 'App\Controllers\RgController::minFraud')
          ->bind('min-fraud')
          ->before(function () use ($app) {
              if (!p('fraud.section') || !p('fraud.fraud.section.min-fraud')) {
                  $app->abort(403);
              }
          })
          ->method('GET|POST');
        $factory->match('/check-similarity/',
          'App\Controllers\RgController::checkSimilarity')
          ->bind('admin.fraud.check-similarity')
          ->before(function () use ($app) {
              if (!p('fraud.section') || !p('fraud.fraud.section.check-similarity')) {
                  $app->abort(403);
              }
          })
          ->method('GET|POST');

        $factory->get('/fraud-rules/get-fields/',
          'App\Controllers\FraudController::getFields')
          ->before(function () use ($app) {
              if (!p('fraud.section') || !p('fraud.fraud.section.fraud-rule-sets')) {
                  $app->abort(403);
              }
          });
        $factory->match('/user-risk-score-report/',
          'App\Controllers\FraudController::riskScoreReport')
          ->bind('fraud.user-risk-score-report')
          ->before(function () use ($app) {
              if (!(p('users.risk.score.report'))) {
                  $app->abort(401);
              }
          })
          ->method('GET|POST');

        $factory->match('/go-aml/', 'App\Controllers\FraudController::goAML')
          ->bind('fraud.go-aml')
          ->before(function () use ($app) {
              if (!(p('fraud.section.goaml'))) {
                  $app->abort(403);
              }
          })
          ->method('GET|POST');

        $factory->match('/grs-score-report/', 'App\Controllers\FraudController::grsScoreReport')
            ->bind('fraud.grs-score-report')
            ->before(function () use ($app) {
                if (!(p('aml.grs.score.report'))) {
                    $app->abort(401);
                }
            })->method('GET|POST');

        return $factory;
    }

    /**
     * Filtering the query results and paginate the results and then it will
     * render the paginator into a view, if the request contains the export
     * parameter, it will generate a streamed response to the client with a CSV
     * file.
     *
     * @param Application $app
     * @param Request $request
     *
     * @return mixed
     */
    public function highDeposits(Application $app, Request $request)
    {
        $repo = new FraudRepository($app);
        $permission = 'fraud.section.high-depositors.download.csv';

        if ($request->isXmlHttpRequest()) {
            foreach ($request->get('form') as $form_elem) {
                $request->request->set($form_elem['name'], $form_elem['value']);
            };
        }

        $date_range = DateRange::rangeFromRequest($request,
          DateRange::DEFAULT_TODAY);

        $sort = ['column' => 0, 'type' => "desc"];

        $hig_dep_query = $repo->getHighDepositsQuery($request, $date_range);

        if (!is_null($request->get('export')) && p($permission)) {
            return $repo->exportHighDeposits(
              $hig_dep_query->orderBy('user_id')->get()->toArray(),
              "high-depositors_{$date_range->getRange('date')}"
            );

        } else {
            $paginator = new PaginationHelper($hig_dep_query, $request, [
              'length' => 25,
              'order' => ['column' => 'd.user_id', 'order' => 'DESC'],
            ]);
            if ($request->isXmlHttpRequest()) {
                return $app->json($paginator->getPage(false));
            } else {
                $page = $paginator->getPage();
                return $app['blade']->view()
                  ->make('admin.fraud.highdeps',
                    compact('page', 'app', 'date_range', 'sort', 'permission',
                      'repo'))
                  ->render();
            }
        }
    }

    /**
     * @param Application $app
     * @param Request $request
     *
     * @return mixed
     * @see highDeposits()
     *
     */
    public function nonTurned(Application $app, Request $request)
    {
        $fraud_repo = new FraudRepository($app, 100);
        $permission = 'fraud.section.non-turned-over-withdrawals.download.csv';

        $query_data = [
          'percent' => $request->get('percent') != "" ? $request->get('percent',
            100) : 100,
          'date' => $request->get('date') != "" ? $request->get('date') : '',
        ];
        $sort = ['column' => 9, 'type' => "desc"];

        $non_turned_over = $fraud_repo->getNonTurnedOver($query_data);

        if (!is_null($request->get('export')) && p($permission)) {
            return $fraud_repo->exportNonTurnedOver($non_turned_over,
              "non-turned-over_{$query_data['date']}");

        } else {
            $download_path = $fraud_repo->generateDownloadPath($query_data);
            return $app['blade']->view()
              ->make('admin.fraud.nonturned',
                compact('non_turned_over', 'app', 'download_path', 'query_data',
                  'sort', 'permission'))
              ->render();
        }
    }

    /**
     * @param Application $app
     * @param Request $request
     *
     * @return mixed
     * @see highDeposits()
     *
     */
    public function anonymousMethods(Application $app, Request $request)
    {
        $fraud_repo = new FraudRepository($app);
        $permission = 'fraud.section.anonymous-methods.download.csv';

        $query_data = [
          'date' => $request->get('date') != "" ? $request->get('date') : Carbon::now()
            ->format('Y-m-d'),
          'start_date' => $request->get('date') != "" ? Carbon::parse($request->get('date'))
            ->format('Y-m-d 00:00:00') : Carbon::now()
            ->format('Y-m-d 00:00:00'),
          'end_date' => $request->get('date') != "" ? Carbon::parse($request->get('date'))
            ->format('Y-m-d 23:59:59') : Carbon::now()
            ->format('Y-m-d 23:59:59'),
        ];
        $sort = ['column' => 8, 'type' => "asc"];

        $anonymous_methods = $fraud_repo->getAnonymousMethodsQuery($query_data)
          ->get();

        if (!is_null($request->get('export')) && p($permission)) {
            return $fraud_repo->exportAnonymousMethods($anonymous_methods,
              "anonymous-methods-transactions_{$query_data['date']}");

        } else {
            $download_path = $fraud_repo->generateDownloadPath(['date' => $query_data['date']]);
            return $app['blade']->view()
              ->make('admin.fraud.anonymousmethods',
                compact('anonymous_methods', 'app', 'download_path',
                  'query_data', 'sort', 'permission'))
              ->render();
        }
    }

    /**
     * @param Application $app
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|mixed
     * @see highDeposits()
     *
     */
    public function multiMethods(Application $app, Request $request)
    {
        $fraud_repo = new FraudRepository($app);
        $permission = 'fraud.section.multi-method-transactions.download.csv';

        $query_data = [
          'date' => $request->get('date') != "" ? $request->get('date') : Carbon::now()
            ->format('Y-m-d'),
          'start_date' => $request->get('date') != "" ? Carbon::parse($request->get('date'))
            ->format('Y-m-d 00:00:00') : Carbon::now()
            ->format('Y-m-d 00:00:00'),
          'end_date' => $request->get('date') != "" ? Carbon::parse($request->get('date'))
            ->format('Y-m-d 23:59:59') : Carbon::now()
            ->format('Y-m-d 23:59:59'),
          'count' => $request->get('count') < 2 ? 2 : $request->get('count', 2),
          'collapse' => $request->get('collapse', 1),
        ];
        $sort = ['column' => 3, 'type' => "desc"];

        /** @var Collection $multi_methods */
        $multi_methods = collect($fraud_repo->getMultiMethodsTransactions($query_data));

        if ($query_data['collapse'] == 1) {
            $multi_methods = $fraud_repo->collapseDuplicatedTransactions($multi_methods);
        }

        if (!is_null($request->get('export')) && p($permission)) {
            return $fraud_repo->exportMultiMethodsTransactions($multi_methods,
              "multi-methods-transactions_{$query_data['date']}");

        } else {
            $download_path = $fraud_repo->generateDownloadPath([
              'date' => $query_data['date'],
              'count' => $query_data['count'],
              'collapse' => $query_data['collapse'],
            ]);
            return $app['blade']->view()
              ->make('admin.fraud.multimethods',
                compact('multi_methods', 'app', 'download_path', 'query_data',
                  'sort', 'permission'))
              ->render();
        }
    }

    /**
     * @param Application $app
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function bigWinners(Application $app, Request $request)
    {
        return $this->handleBigPlayers(
          $app,
          $request,
          '<',
          'fraud.section.big-winners.download.csv',
          ['column' => 6, 'type' => "asc"],
          'big-winners',
          'Big winners list'
        );
    }

    private function handleBigPlayers(
      $app,
      $request,
      $operator,
      $permission,
      $sort,
      $file_name,
      $title
    ) {
        $fraud_repo = new FraudRepository($app);
        $default_amount = 3000;

        $date_range = DateRange::rangeFromRequest($request,
          DateRange::DEFAULT_TODAY);

        $big_players = $fraud_repo->getBigPlayers($request, $date_range,
          $operator, $default_amount);

        if (!is_null($request->get('export')) && p($permission)) {
            return $fraud_repo->exportBigPlayers($big_players,
              "{$file_name}_{$request->get('date-range')}");
        } else {
            return $app['blade']->view()->make('admin.fraud.bigplayers', [
              'big_players' => $big_players,
              'title' => $title,
              'app' => $app,
              'sort' => $sort,
              'permission' => $permission,
              'date_range' => $date_range,
              'default_amount' => $default_amount,
            ])->render();
        }
    }

    /**
     * @param Application $app
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|mixed
     */
    public function bigLosers(Application $app, Request $request)
    {
        return $this->handleBigPlayers(
          $app,
          $request,
          '>',
          'fraud.section.big-losers.download.csv',
          ['column' => 6, 'type' => "desc"],
          'big-losers',
          'Big losers list'
        );
    }

    /**
     * @param Application $app
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function bigDepositors(Application $app, Request $request)
    {
        $fraud_repo = new FraudRepository($app);
        $default_amount = 3000;

        $date_range = DateRange::rangeFromRequest($request,
          DateRange::DEFAULT_TODAY);

        $big_players = $fraud_repo->getBigDepositors($request, $date_range);

        if (!is_null($request->get('export')) && p('fraud.section.big-depositors')) {
            return $fraud_repo->exportBigPlayers($big_players,
              "fraud.section.big-depositors.download.csv_{$request->get('date-range')}");
        } else {
            return $app['blade']->view()->make('admin.fraud.bigdepositors', [
              'big_players' => $big_players,
              'title' => 'Big Depositors',
              'app' => $app,
              'sort' => ['column' => 4, 'type' => "desc"],
              'permission' => 'fraud.section.big-depositors',
              'date_range' => $date_range,
              'default_amount' => $default_amount,
            ])->render();
        }
    }

    /**
     * @param Application $app
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @see highDeposits()
     *
     */
    public function dailyGladiators(Application $app, Request $request)
    {
        $fraud_repo = new FraudRepository($app);
        $permission = 'fraud.section.daily-gladiators.download.csv';

        $query_data = [
          'start_date' => $request->get('start_date') != "" ? $request->get('start_date') : Carbon::now()
            ->format('Y-m-d'),
          'end_date' => $request->get('end_date') != "" ? $request->get('end_date') : Carbon::now()
            ->format('Y-m-d'),
        ];

        $diff = Carbon::parse($request->get('start_date'))
          ->diffInDays(Carbon::parse($request->get('end_date')));
        if ($diff > 7) {
            $query_data['end_date'] = Carbon::parse($request->get('start_date'))
              ->addDays(7)
              ->format('Y-m-d');
        }

        $sort = ['column' => 10, 'type' => "desc"];

        $daily_gladiators = $fraud_repo->getDailyGladiators($query_data);

        if (!is_null($request->get('export')) && p($permission)) {
            return $fraud_repo->exportDailyGladiators($daily_gladiators,
              "daily-gladiators_{$query_data['start_date']}_to_{$query_data['end_date']}");

        } else {
            $download_path = $fraud_repo->generateDownloadPath($query_data);
            return $app['blade']->view()
              ->make('admin.fraud.dailygladiators',
                compact('daily_gladiators', 'app', 'download_path',
                  'query_data', 'sort', 'permission'))
              ->render();
        }
    }

    /**
     * @param Application $app
     * @param Request $request
     *
     * @return mixed
     * @see highDeposits()
     *
     */
    public function failedDeposits(Application $app, Request $request)
    {
        $fraud_repo = new FraudRepository($app);
        $mts = new Mts($app);
        $permission = 'fraud.section.failed-deposits.download.csv';

        if ($request->isXmlHttpRequest()) {
            $query_data = [];
            foreach ($request->get('form') as $form_elem) {
                $query_data[$form_elem['name']] = $form_elem['value'];
            };

            $date_range = DateRange::rangeFromRawDate(@$query_data['date-range']);
            $order = $request->get('order')[0];
            $deposits = $mts->getFailedDeposits(
              0,
              $date_range->getStart('timestamp'),
              $date_range->getEnd('timestamp'),
              $request->get('length'),
              $request->get('start'),
              [
                'user_info' => 1,
                'column' => $request->get('columns')[$order['column']]['data'],
                'dir' => $order['dir'],
                'draw' => $request->get('draw'),
              ]
            );

            return $app->json($deposits);
        } else {
            $date_range = DateRange::rangeFromRequest($request);
            $date_range->validate($app, 1);
            if (!is_null($request->get('export')) && p($permission)) {
                $mts = new Mts($app, 30.0);
                $download_data = $mts->getFailedDeposits(0,
                  $date_range->getStart('timestamp'),
                  $date_range->getEnd('timestamp'), 10000, 0, [
                    'column' => 'created_at',
                    'dir' => 'desc',
                    'draw' => $request->get('draw'),
                  ]);
                return $fraud_repo->exportFailedDeposits($download_data['data'],
                  "failed_deposits_{$date_range->getRange('date')}");
            } else {
                $deposits = $mts->getFailedDeposits(0,
                  $date_range->getStart('timestamp'),
                  $date_range->getEnd('timestamp'), 25, 0, [
                    'user_info' => 1,
                    'column' => 'created_at',
                    'dir' => 'desc',
                    'draw' => $request->get('draw'),
                  ]);
                $download_path = $fraud_repo->generateDownloadPath(['date-range' => $request->get('date-range')]);
                return $app['blade']->view()
                  ->make('admin.fraud.failed-deposits',
                    compact('app', 'deposits', 'download_path', 'date_range',
                      'permission'))
                  ->render();
            }
        }
    }

    /**
     * @param Application $app
     * @param Request $request
     *
     * @return mixed
     * @see highDeposits()
     *
     * Permissions: fraud.section.bonus-abusers
     *              fraud.section.bonus-abusers.download.csv
     *
     * //$permission = 'fraud.section.failed-deposits.download.csv';
     * //if (!empty($request->get('export')) && p($permission)) {
     *
     */
    public function bonusAbusers(Application $app, Request $request)
    {
        $fraud_repo = new FraudRepository($app);

        if (empty($request->get('date-range'))) {
            $request->request->set('transactiontype', 69);
        }

        $date_range = DateRange::rangeFromRequest($request,
          DateRange::DEFAULT_LAST_2_DAYS);
        $data = $fraud_repo->getBonusAbusersData($request, $date_range);
        $sort = ['column' => 6, 'order' => 'desc'];

        return $app['blade']->view()
          ->make('admin.fraud.bonus-abusers',
            compact('data', 'app', 'sort', 'date_range'))
          ->render();
    }

    // TODO create functions similar to this one to support Experian(dob) + ID3 (pep) + Acuris (pep) /Paolo
    public function showId3Data(Application $app, User $user, Request $request)
    {
        $data = $user->repo->getSetting('id3global_full_res');

        $data = str_replace('\\', "", $data);

        //$data = json_decode($data);


        return $app['blade']->view()
          ->make('admin.user.id3global-info', compact('data', 'app', 'user'))
          ->render();
    }

    /**
     * Renders the admin dashboard
     *
     * @param Application $app
     *
     * @return mixed
     */
    public function dashboard(Application $app)
    {
        return $app['blade']->view()
          ->make('admin.fraud.index', compact('app'))
          ->render();
    }

    public function getFields(Application $app, Request $request)
    {
        $tbl = $request->get('tbl');

        if ($tbl == '') {
            return json_encode([]);
        }

        $tables_list = $this->getTablesList($app);
        $columnName = $this->getColumnName($app);

        $tables = [];
        foreach ($tables_list as $table) {
            $tables[] = $table->$columnName;
        }

        if (!in_array($tbl, $tables)) {
            return json_encode([]);
        }

        $fields_list = DB::select('SHOW COLUMNS FROM `' . $tbl . '`');
        $fields = [];
        foreach ($fields_list as $field) {
            $fields[] = $field->Field;
        }

        return json_encode($fields);
    }

    public function fraudRuleDelete(Application $app, Request $request)
    {
        if (!empty($request->get('id'))) {
            $rule = FraudRule::find($request->get('id'));
            $rule->delete();
        }

        return json_encode(['success' => true]);
    }

    public function fraudRules(Application $app, Request $request)
    {
        $fraud_repository = new FraudGroupsRepository($app);

        if ((int)$request->get('id') > 0) {
            $item = FraudRule::find((int)$request->get('id'));
        } else {
            $item = new FraudRule();
        }

        if ($request->isMethod('post')) {

            // create new race here
            if ($request->get('save') <= 0) {
                $item = new FraudRule();
            }

            $item->group_id = $request->get('group_id');
            $item->country = $request->get('country');
            $item->tbl = $request->get('tbl');
            $item->field = $request->get('field');
            $item->start_value = $request->get('start_value');
            $item->end_value = $request->get('end_value');
            $item->like_value = $request->get('like_value');
            $item->value_exists = $request->get('value_exists');
            $item->alternative_ids = $request->get('alternative_ids');
            $item->not_like_value = $request->get('not_like_value');
            $item->value_in = $request->get('value_in');
            $item->value_not_in = $request->get('value_not_in');
            $item->save();
        }

        $oFormBuilder = new FormBuilder();

        $groups_list = $fraud_repository->getGroups();
        $groups = [];
        $groups_output = [];
        foreach ($groups_list as $group) {
            $groups[] = [
              'value' => $group->id,
              'text' => $group->tag,
              'attr' => [],
            ];
            $groups_output[$group->id] = $group->tag;
        }

        $countries_list = BankCountry::orderBy('printable_name', 'ASC')->get();
        $countries = [];
        foreach ($countries_list as $country) {
            $countries[] = [
              'value' => $country->iso,
              'text' => $country->printable_name,
              'attr' => [],
            ];
        }

        $tables_list = $this->getTablesList($app);
        $columnName = $this->getColumnName($app);

        $tables = [];
        $tables_to_ckeck = [];

        foreach ($tables_list as $table) {
            $tablesInMaster = $table->$columnName;
            $tables_to_ckeck[] = $tablesInMaster;
            $tables[] = [
              'value' => $tablesInMaster,
              'text' => $tablesInMaster,
              'attr' => [],
            ];
        }

        $fields_list = DB::select('SHOW COLUMNS FROM `' . (($item->tbl != '' && in_array($item->tbl,
              $tables_to_ckeck)) ? $item->tbl : $tables[0]['value']) . '`');
        $fields = [];
        foreach ($fields_list as $field) {
            $fields[] = [
              'value' => $field->Field,
              'text' => $field->Field,
              'attr' => [],
            ];
        }

        $oFormBuilder->createSelect([
          'name' => 'group_id',
          'value' => $item->group_id,
          'label' => [
            'text' => 'Group',
            'wrap' => false,
            'after' => false,
          ],
          'comment' => 'Connection to the rule group the rules are attached to. We loop all active groups during registration, deposit and game load. If any group matches completely we block the player.',
          'attr' => [
            'class' => 'form-control',
          ],
          'options' => $groups,
          'rules' => [
            'required' => true,
          ],
        ]);

        $oFormBuilder->createSelect([
          'name' => 'country',
          'value' => $item->country,
          'label' => [
            'text' => 'Country',
            'wrap' => false,
            'after' => false,
          ],
          'comment' => 'We filter all rules by country, if the player\'s country doesn\'t match the rule won\'t be checked. Leave out country from all rules under a specific rule set / group to have the rules apply to people form all countries.',
          'attr' => [
            'class' => 'form-control',
          ],
          'options' => array_merge([
            [
              'value' => '',
              'text' => 'Please select',
              'attr' => [],
            ],
          ], $countries),
          'rules' => [
            'required' => false,
          ],
        ]);

        $oFormBuilder->createSelect([
          'name' => 'tbl',
          'value' => $item->tbl,
          'label' => [
            'text' => 'Table',
            'wrap' => false,
            'after' => false,
          ],
          'comment' => 'The database table we want to examine / work with.',
          'attr' => [
            'class' => 'form-control',
            'onchange' => 'fraudRules.getFields(this.value);',
          ],
          'options' => $tables,
          'rules' => [
            'required' => true,
          ],
        ]);

        $oFormBuilder->createSelect([
          'name' => 'field',
          'value' => $item->tbl,
          'label' => [
            'text' => 'Field',
            'wrap' => false,
            'after' => false,
          ],
          'comment' => 'The database table field we want to look at.',
          'attr' => [
            'class' => 'form-control',
            'id' => 'fraud_rule_fields',
          ],
          'options' => $fields,
          'rules' => [
            'required' => true,
          ],
        ]);

        $oFormBuilder->createInput([
          'name' => 'start_value',
          'value' => $item->start_value,
          'label' => [
            'text' => 'Start value',
            'wrap' => false,
            'after' => false,
          ], // or 'firstname'
          'comment' => 'Start value and End value: both need to be present and will make up a range the value needs to be in.',
          'attr' => [
            'class' => 'form-control',
          ],
          'rules' => [
            'required' => false,
          ],
          'template' => 'snippets/html/my-input.html',
        ]);

        $oFormBuilder->createInput([
          'name' => 'end_value',
          'value' => $item->end_value,
          'label' => [
            'text' => 'End value',
            'wrap' => false,
            'after' => false,
          ], // or 'firstname'
          'comment' => 'Start value and End value: both need to be present and will make up a range the value needs to be in.',
          'attr' => [
            'class' => 'form-control',
          ],
          'rules' => [
            'required' => false,
          ],
          'template' => 'snippets/html/my-input.html',
        ]);

        $oFormBuilder->createInput([
          'name' => 'like_value',
          'value' => $item->like_value,
          'label' => [
            'text' => 'Like value',
            'wrap' => false,
            'after' => false,
          ], // or 'firstname'
          'comment' => 'If we want to match a single value, accepts the SQL wildcard % which matches any character. Example: %yahoo.co.uk',
          'attr' => [
            'class' => 'form-control',
          ],
          'rules' => [
            'required' => false,
          ],
          'template' => 'snippets/html/my-input.html',
        ]);

        $oFormBuilder->createInput([
          'name' => 'not_like_value',
          'value' => $item->not_like_value,
          'label' => [
            'text' => 'Not Like value',
            'wrap' => false,
            'after' => false,
          ],
          'comment' => 'The opposite of like value, example: tbl: trophy_award_ownership, field: status, not like value: 1 will match anyone who has a trophy award which is not in use (status = 1). That is anyone with a trophy award that is not used yet (status = 0), used (status = 2) or expired (status = 3). NOTE that the match will fail if the SQL query doesn\'t return anything, so if a fraudster doesn\'t have any award rows in the database at all he will not be matched by this rule.',
          'attr' => [
            'class' => 'form-control',
          ],
          'rules' => [
            'required' => false,
          ],
          'template' => 'snippets/html/my-input.html',
        ]);

        $oFormBuilder->createInput([
          'name' => 'Value exists',
          'value' => $item->value_exists,
          'label' => [
            'text' => 'Value exists',
            'wrap' => false,
            'after' => false,
          ],
          'comment' => 'Just put 1 here to signal that field needs to have a value. Used to figure out if a player has a row in a table at all, example: tbl: users_game_sessions, field: user_id, value exists: 1. This will match if the player has completed a game session.',
          'attr' => [
            'class' => 'form-control',
          ],
          'rules' => [
            'required' => false,
          ],
          'template' => 'snippets/html/my-input.html',
        ]);

        $oFormBuilder->createInput([
          'name' => 'alternative_ids',
          'value' => $item->alternative_ids,
          'label' => [
            'text' => 'Alternative Ids',
            'wrap' => false,
            'after' => false,
          ],
          'comment' => 'Ids of rules to be run if a certain rule failes, note that atm you can\'t just pick any rule, example: 9,10 to run rules with id 9 and 10',
          'attr' => [
            'class' => 'form-control',
          ],
          'rules' => [
            'required' => false,
            'pattern' => '[0-9,]*',
          ],
          'template' => 'snippets/html/my-input.html',
        ]);

        $oFormBuilder->createInput([
          'name' => 'value_in',
          'value' => $item->value_in,
          'label' => [
            'text' => 'Value IN',
            'wrap' => false,
            'after' => false,
          ],
          'comment' => 'This is to be used with SQL\'s IN clause, example: tbl: trophy_award_ownership, field: status, value in: 0,3. This will match anyone who has either an unused or expired trophy award, a common scenario when dealing with criminals, they\'re not interested in completing 10 freespins. If trying to match a string value the syntax is like this: \'value1\',\'value2\'... Note that wildcards are not supported in the values.',
          'attr' => [
            'class' => 'form-control',
          ],
          'rules' => [
            'required' => false,
          ],
          'template' => 'snippets/html/my-input.html',
        ]);

        $oFormBuilder->createInput([
          'name' => 'value_not_in',
          'value' => $item->value_not_in,
          'label' => [
            'text' => 'Value NOT IN',
            'wrap' => false,
            'after' => false,
          ],
          'comment' => 'The opposite of value in, we get all rows which are not in the "list", for instance 0,3, ie everyone who are using or has completed a trophy award. NOTE that the match will fail if the SQL query doesn\'t return anything, so if a fraudster doesn\'t have any award rows in the database at all he will not be matched by this rule.',
          'attr' => [
            'class' => 'form-control',
          ],
          'rules' => [
            'required' => false,
          ],
          'template' => 'snippets/html/my-input.html',
        ]);

        // CREATE A SUBMIT BUTTON USING BUTTON
        $oFormBuilder->createButton([
          'name' => 'save',
          'type' => ElementInterface::TYPE_SUBMIT,
          'value' => $item->id,
          'text' => 'Save',
          'attr' => [
            'class' => 'success',
            'checked' => true,
            'autofocus' => true,
            'disabled' => false,
          ],
          'rules' => [
            'required' => false,
          ],
        ]);

        if ($item->id > 0) {
            $oFormBuilder->createButton([
              'name' => 'cancel',
              'type' => ElementInterface::TYPE_BUTTON,
              'value' => '-1',
              'text' => 'Cancel',
              'attr' => [
                'onclick' => 'location.href=\'../\';',
              ],
              'rules' => [
                'required' => false,
              ],
            ]);
        }

        if ($oFormBuilder->valid()) {
            // only validated on client side now (HTML5 regular expression)
        }

        $items = $fraud_repository->getRules();


        return $app['blade']->view()
          ->make('admin.fraud.rules',
            compact('app', 'items', 'groups_output', 'oFormBuilder'))
          ->render();
    }

    public function fraudGroups(Application $app, Request $request)
    {
        if ((int)$request->get('id') > 0) {
            $item = FraudGroup::find((int)$request->get('id'));
        } else {
            $item = new FraudGroup();
        }

        if ($request->isMethod('post') && !empty($request->get('tag'))) {

            // create new race here
            if ($request->get('save') <= 0) {
                $item = new FraudGroup();
            }

            $item->tag = $request->get('tag');
            $item->description = $request->get('description');
            $item->start_date = !empty($request->get('start_date')) ? $request->get('start_date') : null;
            $item->end_date = !empty($request->get('end_date')) ? $request->get('end_date') : null;
            $item->is_active = $request->get('is_active');
            $item->save();
        }

        $oFormBuilder = new FormBuilder();

        // CREATE AN INPUT
        $oFormBuilder->createInput([
          'name' => 'tag',
          'value' => $item->tag,
          'label' => [
            'text' => 'Tag',
            'wrap' => false,
            'after' => false,
          ],
          'comment' => '',
          'attr' => [
            'class' => 'form-control',
          ],
          'rules' => [
            'required' => true,
          ],
          'template' => 'snippets/html/my-input.html',
        ]);

        // CREATE AN INPUT
        $oFormBuilder->createInput([
          'name' => 'description',
          'value' => $item->description,
          'label' => [
            'text' => 'Description',
            'wrap' => false,
            'after' => false,
          ],
            //'comment' => '',
          'attr' => [
            'class' => 'form-control',
          ],
          'rules' => [
            'required' => true,
            'maxlength' => 255,
          ],
          'template' => 'snippets/html/my-input.html',
        ]);

        // CREATE AN INPUT
        $oFormBuilder->createInput([
          'name' => 'start_date',
          'value' => $item->start_date,
          'label' => [
            'text' => 'Start date',
            'wrap' => false,
            'after' => false,
          ],
            //'comment' => '',
          'attr' => [
            'class' => 'form-control datetimepicker',
          ],
          'rules' => [
            'required' => false,
            'maxlength' => 19,
            'min' => 19,
            'pattern' => '[[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}]:[0-9]{2}*',
          ],
          'template' => 'snippets/html/my-input.html',
        ]);

        // CREATE AN INPUT
        $oFormBuilder->createInput([
          'name' => 'end_date',
          'value' => $item->end_date,
          'label' => [
            'text' => 'End date',
            'wrap' => false,
            'after' => false,
          ], // or 'firstname'
            //'comment' => '',
          'attr' => [
            'class' => 'form-control datetimepicker',
          ],
          'rules' => [
            'required' => false,
            'min' => 16, // max chars
            'maxlength' => 16, // max chars
            'pattern' => '[[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}]:[0-9]{2}*',
          ],
          'template' => 'snippets/html/my-input.html',
        ]);

        $oFormBuilder->createSelect([
          'name' => 'is_active',
          'value' => !empty($item->is_active) ? 1 : 0,
          'label' => [
            'text' => 'Active',
            'wrap' => false,
            'after' => false,
          ],
            //'comment' => '',
          'attr' => [
            'class' => 'form-control',
          ],
          'options' => [
            ['value' => '1', 'text' => 'YES', 'attr' => []],
            ['value' => '0', 'text' => 'NO', 'attr' => []],
          ],
          'rules' => [
            'required' => true,
          ],
        ]);

        // CREATE A SUBMIT BUTTON USING BUTTON
        $oFormBuilder->createButton([
          'name' => 'save',
          'type' => ElementInterface::TYPE_SUBMIT,
          'value' => $item->id,
          'text' => 'Save',
          'attr' => [
            'class' => 'success',
            'checked' => true,
            'autofocus' => true,
            'disabled' => false,
          ],
          'rules' => [
            'required' => false,
          ],
        ]);

        if ($item->id > 0) {
            $oFormBuilder->createButton([
              'name' => 'cancel',
              'type' => ElementInterface::TYPE_BUTTON,
              'value' => '-1',
              'text' => 'Cancel',
              'attr' => [
                'onclick' => 'location.href=\'../\';',
              ],
              'rules' => [
                'required' => false,
              ],
            ]);
        }

        if ($oFormBuilder->valid()) {
            // only validated on client side now (HTML5 regular expression)
        }

        $fraud_repository = new FraudGroupsRepository($app);

        $groups = $fraud_repository->getGroups();


        return $app['blade']->view()
          ->make('admin.fraud.groups', compact('app', 'groups', 'oFormBuilder'))
          ->render();
    }

    /**
     * @param Application $app
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function riskScoreReport(Application $app, Request $request)
    {
        $params_array = array_merge(['forwarded' => RiskProfileRating::AML_SECTION],
          $request->query->all());
        $sub_request = Request::create($app["url_generator"]->generate('admin.admin.user-risk-score-report-special'),
          $request->getMethod(), $params_array, $request->cookies->all(), [],
          $request->server->all());
        if ($request->getSession()) {
            $sub_request->setSession($request->getSession());
        }

        $response = $app->handle($sub_request, HttpKernelInterface::SUB_REQUEST,
          false);
        return $response;
    }

    /**
     *  Display the interface for exporting of GoAML report
     *
     * @param Application $app
     * @param Request $request
     *
     * @return bool|string
     * @throws \Exception
     */
    public function goAML(Application $app, Request $request)
    {
        if ($request->isMethod('GET')) {
            $user_fields = [
              'ssn' => 'Protected SSN',
              'firstname' => 'First Name',
              'lastname' => 'Last Name',
              'middle_name' => 'Middle Name',
              'dob' => 'Birth Date',
              'sex' => 'Gender',
              'prefix' => 'Prefix',
              'birth_place' => 'Birthplace',
              'country_birth' => 'Country of birth',
              'political_exposure' => 'Political Exposed (PEP)',
              'citizenship1' => 'Citizenship 1',
              'citizenship2' => 'Citizenship 2',
              'citizenship3' => 'Citizenship 3',
              'residence' => 'Citizenship Residence',
              'occupation' => 'Occupation',
              'employer_name' => 'Employer Name',
              'tin' => 'TIN',
              'foreign_personal_identify' => 'Foreign personal identity number',
              'foreign_personal_identify_country' => 'Foreign personal identity number country',
              'dod' => 'Date of Death',
              'user_comment' => 'Comments',
              'email' => 'email',
            ];

            $phones_fields = [
              'tph_contact_type' => 'Contact Type => always TELE (for "phone number")',
              'tph_communication_type' => 'Type of communication.',
              'tph_country_prefix' => 'Telephone number country code.E.g. "46" for Sweden',
              'tph_number' => 'The phone number',
              'comments' => 'phone comment',

            ];

            $addresses_fields = [
              'address_type' => 'Type of address.',
              'address' => 'Street name and street number.',
              'town' => 'Place, could be a square or a park. Not the same as the address.',
              'city' => 'City',
              'zip' => 'Zip',
              'country_code' => 'Country code',
              'state' => 'Country',

            ];

            $identification = [
              'type' => 'Identification Type',
              'number' => 'ID number',
              'issue_date' => 'Issue Date',
              'expiry_date' => 'Expiry Date',
              'issued_by' => 'Issued By',
              'issue_country' => 'Issued Country',
              'comments' => 'An identifier for the
hardware used to authenticate a person',
            ];
            return $app['blade']->view()
              ->make('admin.fraud.exportaml',
                compact('page', 'app', 'user_fields', 'phones_fields',
                  'addresses_fields', 'identification'))
              ->render();
        }

        $goAML = new GoAML();
        $export = new Export();


        $user_id = $request->get('user_id', 0);
        $jurisdiction = $request->get('jurisdiction');
        $comment = $request->get('comment');
        $report_type = $request->get('report_type');
        $indicators = $request->get('indicators');

        $all_request_data = $request->request->all();
        $user = User::find($user_id);

        $jurisdiction = new Jurisdiction($jurisdiction);

        if (empty($user_id) || empty($user)) {
            $export->doLogAction($user, $jurisdiction, 'no_user');
            $app['flash']->add('danger', "User cannot be found");
            return new RedirectResponse($request->headers->get('referer'));
        }

        $result = $goAML->export($app, $user, $jurisdiction, $comment,
          $report_type, $indicators, $all_request_data);

        if ($result === false) {
            $export->doLogAction($user, $jurisdiction, 'no_results');

            $app['flash']->add('danger',
              "Not transaction found for this user.");
            return new RedirectResponse($request->headers->get('referer'));
        } else {
            return $result;
        }
    }

    /**
     * Show all AML data from Risk_profile_rating_log
     *
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function grsScoreReport(Application $app, Request $request)
    {
        return RiskProfileRatingController::grsScoreReport($app, null, $request, 'aml');
    }

    /**
     * Returns list of tables in masterDB from replica or default connections.
     *
     * @param \Silex\Application $app
     *
     * @return array
     */
    private function getTablesList(Application $app): array
    {
        $connection = $this->getMasterConnectionHelper($app)->getMasterConnection();

        return $connection->select("SHOW TABLES");
    }

    /**
     * Returns column name for "SHOW TABLES" query, that constists from "Tables_in" + `current masterDb name`.
     *
     * @param \Silex\Application $app
     *
     * @return string
     */
    private function getColumnName(Application $app): string
    {
        $masterDbName = $this->getMasterConnectionHelper($app)->getMasterDatabaseName();

        return "Tables_in_$masterDbName";
    }

    /**
     * @param \Silex\Application $app
     *
     * @return \App\Classes\Fraud\MasterConnectionHelper
     */
    private function getMasterConnectionHelper(Application $app): MasterConnectionHelper
    {
        if (!isset($this->masterConnectionHelper)) {
            $this->masterConnectionHelper = new MasterConnectionHelper($app);
        }

        return $this->masterConnectionHelper;
    }
}
