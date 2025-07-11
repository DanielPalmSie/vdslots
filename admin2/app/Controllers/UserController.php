<?php

/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.17.
 * Time: 9:29
 */

namespace App\Controllers;

use App\Classes\DateRange;
use App\Classes\Dmapi;
use App\Extensions\Database\Builder;
use App\Extensions\Database\FManager as DB;
use App\Helpers\DataFormatHelper;
use App\Helpers\GrsHelper;
use App\Helpers\PaginationHelper;
use App\Models\RiskProfileRating;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Repositories\UserSearchRepository;
use Carbon\Carbon;
use Illuminate\Database\Query\JoinClause;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Repositories\RiskProfileRatingRepository;

require_once getenv('VIDEOSLOTS_PATH') . '/phive/modules/DBUserHandler/DBUser.php';


class UserController implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/', 'App\Controllers\UserController::userSearchForm')
            ->bind('user')
            ->before(function () use ($app) {
                if (!p('users.section')) {
                    $app->abort(403);
                }
            });

        $factory->match('/user-search/', 'App\Controllers\UserController::userSearchList')
            ->bind('user.search')
            ->before(function () use ($app) {
                if (!p('users.section')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->post('/user-ajax/', 'App\Controllers\UserController::userAjax')
            ->bind('user-ajax')
            ->before(function () use ($app) {
                if (!p('users.section')) {
                    $app->abort(403);
                }
            });

        $factory->match('/user-search-export/', 'App\Controllers\UserController::userSearchExport')
            ->bind('user.search.export')
            ->before(function () use ($app) {
                $download_config_status = empty($app['vs.config']['active.sections']['user.download']) || !$app['vs.config']['active.sections']['user.download'] ? true : false;
                if ((!p('users.section') || !p('download.csv')) || $download_config_status){
                    $app->abort(403);
                }
            });

        $factory->get('/delete-all-recently-searched-users/', 'App\Controllers\UserController::deleteRecentUsers')
            ->bind('user.delete.recent')
            ->before(function () use ($app) {
                if (!p('users.section')) {
                    $app->abort(403);
                }
            });


        $factory->get('/listdocuments/', 'App\Controllers\DocumentController::listDocuments')
            ->bind('admin.user-documents-list')
            ->before(function () use ($app) {
                if (!(p('menuer.list-documents'))) {
                    $app->abort(401);
                }
            });

        $factory->get('/listsourceoffunds/', 'App\Controllers\DocumentController::listSourceOfFunds')
            ->bind('admin.user-sourceoffunds-list')
            ->before(function () use ($app) {
                if (!(p('users.list.sourceoffunds'))) {
                    $app->abort(401);
                }
            });

        $factory->match('/user-risk-score-report/', 'App\Controllers\UserController::riskScoreReport')
            ->bind('admin.user-risk-score-report')
            ->before(function () use ($app) {
                if (!(p('users.risk.score.report'))) {
                    $app->abort(401);
                }
            })
            ->method('GET|POST');

        $factory->match('/admin.user-risk-score-report-special/', 'App\Controllers\UserController::riskScoreReportSpecial')
                ->bind('admin.admin.user-risk-score-report-special')
                ->before(function () use ($app) {
                    if (!(p('users.risk.score.report'))) {
                        $app->abort(401);
                    }
                })
                ->method('GET|POST');

        $factory->match('/user-follow-up-report/', 'App\Controllers\UserController::followUpReport')
            ->bind('admin.user-follow-up-report')
            ->before(function () use ($app) {
                if (!(p('users.follow.up.report'))) {
                    $app->abort(401);
                }
            })
            ->method('GET|POST');

        $factory->match('/user-monitored-accounts-report/', 'App\Controllers\UserController::monitoredAccountsReport')
            ->bind('admin.user-monitored-accounts-report')
            ->before(function () use ($app) {
                if (!(p('users.monitored.accounts.report'))) {
                    $app->abort(401);
                }
            })
            ->method('GET|POST');

        $factory->match('/user-monitoring-log-report/', 'App\Controllers\UserController::monitoringLogReport')
            ->bind('admin.user-monitoring-log-report')
            ->before(function () use ($app) {
                if (!(p('users.monitoring.log.report'))) {
                    $app->abort(401);
                }
            })
            ->method('GET|POST');

        $factory->match('/user-force-limits-report/', 'App\Controllers\UserController::forceLimitsReport')
            ->bind('admin.user-force-limits-report')
            ->before(function () use ($app) {
                if (!(p('users.force.limits.report'))) {
                    $app->abort(401);
                }
            })
            ->method('GET|POST');

        $factory->match('/user-docs-report/', 'App\Controllers\UserController::documentsManagementReport')
            ->bind('admin.user-docs-report')
            ->before(function () use ($app) {
                if (!(p('users.documents.management.report'))) {
                    $app->abort(401);
                }
            })
            ->method('GET|POST');


        /* I have no clue about this or where is coming from
 		$factory->match('/user-form/', 'App\Controllers\UserController::userForm')
           ->bind('user')
           ->before(function () use ($app) {
                if (!p('users.section')) {
                   $app->abort(403);
               }
           });
        */

        return $factory;
        /*
        $routes = new RoutingGenerator($app, $this); //TODO finish or discard this (see RoutingGenerator for more info)
        $routes->add('/', 'userSearchForm', 'user', ['users.section']);
        $routes->add('/user-search/', 'userSearchList', 'user.search', ['users.section']);
        $routes->add('/user-ajax/', 'userAjax', 'user-ajax', ['users.section']);
        $routes->add('/user-search-export/', 'userSearchExport', 'user.search.export', ['users.section', 'download.csv']);
        $routes->add('/delete-all-recently-searched-users/', 'deleteRecentUsers', 'user.delete.recent', ['users.section']);
        return $routes->load();
        */
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function riskScoreReport(Application $app, Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            foreach ($request->get('form') as $form_elem) {
                $request->request->set($form_elem['name'], $form_elem['value']);
            };
        }

        $params['user_id'] = $request->get('user_id');

        //todo port to config model just a quick fix as it was not working
        $exclude_countries = array_filter(array_keys(phive('Config')->valAsArray('exclude-countries', 'risk-score-report')));

        $period_date['first'] = $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_YESTERDAY, 'date-range-start');
        $period_date['second'] = $date_range_2 = DateRange::rangeFromRequest($request, DateRange::DEFAULT_DAY_BEFORE_YESTERDAY, 'date-range-end');

        $bindings = [
                'start1' => $date_range->getStart('timestamp'),
                'start2' => $date_range_2->getStart('timestamp'),
                'end1' => $date_range->getEnd('timestamp'),
                'end2' => $date_range_2->getEnd('timestamp'),
        ];

        $getCondition = function($s = '1') use ($request, &$bindings, $exclude_countries) {

            $where = " AND tl.created_at BETWEEN :start{$s} AND :end{$s}";

            if (!empty($user_id = $request->get('user_id'))) {
                $where .= " AND u.id LIKE :user_id{$s}";
                $bindings["user_id" . $s] = $user_id;
            }

            if (($country = $request->get('country', 'all')) != 'all') {
                $where .= " AND country = :country{$s}";
                $bindings["country" . $s] = $country;
            } elseif (!empty($exclude_countries)) {
                $where .= " AND country NOT IN " . DataFormatHelper::arrayToSql($exclude_countries, true);
            }

            if (($type = $request->get('trigger-type', 'all')) != 'all') {
                $where .= " AND tl.trigger_name LIKE :triggertype{$s}";
                $bindings["triggertype" . $s] = strtoupper($type) . "%";
            }

            return $where;
        };

        $table = implode(" UNION ALL ", [
                UserRepository::getRiskScoreQuery('first', 'sum(t.score)', '0', $getCondition()),
                UserRepository::getRiskScoreQuery('second', '0', 'sum(t.score)', $getCondition('2'))
        ]);

        $query = "
            SELECT user_id, username, country, sum(first_score) AS first, sum(second_score) AS second, declaration_proof
            FROM ($table) as sub
            GROUP BY user_id
        ";

        $page = (new PaginationHelper(
                $data = DB::shsSelect('triggers_log', $query, $bindings),
                $request,
                [
                        'length' => 25,
                        'order' => ['column' => 'score', 'dir' => 'DESC']
                ]
        ))->getPage(!$request->isXmlHttpRequest());

        return $request->isXmlHttpRequest()
                ? $app->json($page)
                : $app['blade']->view()->make(
                        'admin.user.risk-score-report', compact('page', 'app', 'period_date', 'exclude_countries', 'params')
                )->render();
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Exception
     */
    public function riskScoreReportSpecial(Application $app, Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            foreach ($request->get('form') as $form_elem) {
                $request->request->set($form_elem['name'], $form_elem['value']);
            };
        }

        $risk_profile_repo = $app['risk_profile_rating.repository'];

        $forwarded = $request->get('forwarded', 'ALL');
        $max_score = RiskProfileRatingRepository::getMaxRiskScore($forwarded);
        $reviewers = RiskProfileRatingRepository::getAllReviewers($forwarded);

        $params = [
            'profile_rating' => [
                'start' => $request->get('section_profile_rating_start', \App\Repositories\RiskProfileRatingRepository::PROFILE_RATING_MIN_TAG),
                'end' => $request->get('section_profile_rating_end', \App\Repositories\RiskProfileRatingRepository::PROFILE_RATING_MAX_TAG),
            ],
            'user_score' => [
                'start' => $request->get('user_score_start', 21),
                'end' => $request->get('user_score_end', $max_score),
            ],
            'reviewer' => $request->get('reviewer', 'all'),
            'country' => $request->get('country', 'all'),
            'user_id' => $request->get('user_id', ''),
            'selected_reviewer' => $request->get('reviewer', 'all'),
            'reviewers' => $reviewers,
        ];

        $cached = false;
        if($request->isMethod('GET')){
            $cached = true;
        }

        $trigger_type = strtoupper($request->get('forwarded', $request->get('trigger-type', 'ALL')));

        switch ($forwarded) {
            case RiskProfileRating::RG_SECTION:
                $default_date_range = DateRange::DEFAULT_TODAY;
                $comment_tags = ['limits', 'rg-risk-group'];
                break;

            case RiskProfileRating::AML_SECTION:
                $default_date_range = DateRange::DEFAULT_YESTERDAY;
                $comment_tags = ['amlfraud', 'aml-risk-group'];
                break;
        }

        $params['comment_tags'] = phive('SQL')->makeIn($comment_tags);

        //todo port to config model just a quick fix as it was not working
        $exclude_countries = array_filter(array_keys(phive('Config')->valAsArray('exclude-countries', 'risk-score-report')));

        $period_date['score'] = $date_range = DateRange::rangeFromRequest($request, $default_date_range, 'date-range-start');
        $rating_tags = GrsHelper::getRatingScoreFilterRange($app, $params['profile_rating']['start'], $params['profile_rating']['end'], true);
        $bindings = [
            'start1' => $date_range->getStart('timestamp'),
            'end1' => $date_range->getEnd('timestamp'),
            "score_start" => $params['user_score']['start'],
            "score_end" => $params['user_score']['end'],
            "country" => $request->get('country', 'all'),
            'profile_rating_tags' => $rating_tags,
        ];

        $data = $risk_profile_repo->getUsersWithRiskScores($trigger_type, $params, $bindings, $cached, $exclude_countries);
        $page = (new PaginationHelper(
            $data,
            $request,
            [
                'length' => 25,
                'order' => ['column' => 'score', 'dir' => 'DESC']
            ]
        ))->getPage(!$request->isXmlHttpRequest());

        return $request->isXmlHttpRequest()
            ? $app->json($page)
            : $app['blade']->view()->make(
                'admin.user.risk-score-report-special',
                compact('page',
                    'app',
                    'period_date',
                    'exclude_countries',
                    'forwarded',
                    'comment_tags',
                    'params',
                    'reviewers'
                )
            )->render();
    }

    /**
     * @param Application $app
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function followUpReport(Application $app, Request $request)
    {

        if ($request->isXmlHttpRequest()) {
            foreach ($request->get('form') as $form_elem) {
                $request->request->set($form_elem['name'], $form_elem['value']);
            };
        }

        $date = empty($trigger_day = $request->get('trigger-day'))
            ? Carbon::now()->toDateString()
            : Carbon::parse($trigger_day)->toDateString();

        $follow_up_outer_conditions = "AND (
            (follow_up.period LIKE '%weekly%' AND WEEKDAY(DATE(follow_up.created_at)) = WEEKDAY('{$date}')) OR
            (follow_up.period LIKE '%monthly%' AND MOD(DATEDIFF('{$date}', follow_up.created_at), 30) = 0) OR
            (follow_up.period LIKE '%quarterly%' AND MOD(DATEDIFF('{$date}', follow_up.created_at), 30*4) = 0) OR
            (follow_up.period LIKE '%halfyearly%' AND MOD(DATEDIFF('{$date}', follow_up.created_at), 30*6) = 0) OR
            (follow_up.period LIKE '%yearly%' AND MOD(DATEDIFF('{$date}', follow_up.created_at), 365) = 0)
        )";
        $follow_up_inner_conditions = " AND DATE(created_at) <> '{$date}' ";

        $period_column = " SUBSTRING_INDEX(SUBSTRING_INDEX(setting, '-', 2), '-', -1) ";
        $category_column = " SUBSTRING_INDEX(setting, '-', 1) ";

        $username = !empty($username = $request->get('username'))
            ? " AND u.username LIKE '%{$username}%'"
            : '';

        $country = ($country = $request->get('country', 'all')) != 'all'
            ? " AND u.country = '{$country}'"
            : '';

        $has_trigger_type = ($trigger_type = $request->get('trigger-type', 'all')) != 'all';
        $has_risk_group = ($risk_group_value = $request->get('risk-group', 'all')) != 'all';

        // prevent $date usage when risk-group is present
        if ($has_risk_group) {
            $follow_up_outer_conditions = " AND risk_group.value LIKE '%{$risk_group_value}%' ";
            $follow_up_inner_conditions = '';
        }

        $follow_up_inner_conditions .= $has_trigger_type
            ? " AND {$category_column} = '{$trigger_type}' "
            : '';

        if (($time = $request->get('trigger-time', 'all')) != 'all') {
            $follow_up_inner_conditions .= " AND {$period_column} = '{$time}' ";
            $follow_up_outer_conditions .= " AND follow_up.period = '{$time}' ";
        }

        $risk_group_condition = '';
        if ($has_trigger_type) {
            $risk_group_condition = $has_risk_group
                ? "AND setting = '{$trigger_type}-risk-group'"
                : "AND setting LIKE '%-risk-group'";

            $risk_group_condition .= $has_risk_group
                ? " AND value = '{$risk_group_value}' "
                : '';
        }


        $has_risk_group = $has_risk_group
            ? " OR risk_group.value IS NOT NULL "
            : '';

        $query = "
            SELECT
                u.username,
                u.country,
                u.last_login,
                u.id AS user_id,
                IFNULL(risk_group.created_at, follow_up.category) AS created_at,
                IFNULL(risk_group.category, follow_up.category) AS category,
                IFNULL(follow_up.period, '-') AS period,
                IFNULL(risk_group.value, '-') AS risk_group
            FROM users AS u
                LEFT JOIN (
                    SELECT
                      GROUP_CONCAT($period_column SEPARATOR ',') AS period,
                      GROUP_CONCAT($category_column  SEPARATOR ', ') AS category,
                      created_at,
                      user_id
                    FROM users_settings
                    WHERE setting LIKE '%follow-up'
                        AND value = 1
                        {$follow_up_inner_conditions}
                    GROUP BY user_id
                ) AS follow_up ON follow_up.user_id = u.id
                LEFT JOIN (
                    SELECT
                        GROUP_CONCAT(value  SEPARATOR ', ') AS value,
                        GROUP_CONCAT($category_column  SEPARATOR ', ') AS category,
                        created_at,
                        user_id
                    FROM users_settings
                    WHERE setting LIKE '%-risk-group'
                    {$risk_group_condition}
                    GROUP BY user_id
                ) AS risk_group ON risk_group.user_id = u.id
            WHERE (
                follow_up.category IS NOT NULL OR
                follow_up.period IS NOT NULL
                {$has_risk_group}
            )
            {$username}
            {$country}
            {$follow_up_outer_conditions}
        ";

        $data = DB::shsSelect('users_settings', $query);

        $paginator = new PaginationHelper($data, $request, [
            'length' => 25,
            'order' => ['column' => 'last_login', 'dir' => 'ASC']
        ]);
        if ($request->isXmlHttpRequest()) {
            return $app->json($paginator->getPage(false));
        } else {
            $page = $paginator->getPage();
            return $app['blade']->view()
                ->make('admin.user.follow-up-report', compact('page', 'app'))
                ->render();
        }
    }


    public function monitoredAccountsReport(Application $app, Request $request)
    {

        if ($request->isXmlHttpRequest()) {
            foreach ($request->get('form') as $form_elem) {
                $request->request->set($form_elem['name'], $form_elem['value']);
            };
        }

        $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_TODAY);

        $query = DB::table('users_settings AS us')
            ->selectRaw('us.user_id, u.username, u.country, us.setting, us.created_at')
            ->leftJoin('users AS u', 'u.id', '=', 'us.user_id')
            ->whereBetween('us.created_at', $date_range->getWhereBetweenArray());

        if ($request->get('trigger-type', 'all') != 'all') {
            $query->where('us.setting', $request->get('trigger-type'));
        } else {
            $query->whereIn('us.setting', array_keys(DataFormatHelper::getManualFlags()));
        }

        if (!empty($request->get('username'))) {
            $query->where('u.username', 'LIKE', "%{$request->get('username')}%");
        }

        if ($request->get('country', 'all') != 'all') {
            $query->where('u.country', $request->get('country'));
        }

        $processPage = function (&$data) {
            foreach ($data as $elem) {
                $elem->setting = DataFormatHelper::getManualFlags($elem->setting);
            }
        };

        $paginator = new PaginationHelper($query, $request, ['length' => 25, 'order' => ['column' => 'created_at', 'dir' => 'ASC']]);
        if ($request->isXmlHttpRequest()) {
            $page = $paginator->getPage(false);
            $processPage($page['data']);
            return $app->json($page);
        } else {
            $page = $paginator->getPage();
            $processPage($page['data']);
            return $app['blade']->view()
                ->make('admin.user.monitored-accounts-report', compact('page', 'app', 'date_range'))
                ->render();
        }
    }


    public function monitoringLogReport(Application $app, Request $request)
    {

        if ($request->isXmlHttpRequest()) {
            foreach ($request->get('form') as $form_elem) {
                $request->request->set($form_elem['name'], $form_elem['value']);
            };
        }

        $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_TODAY);

        /** @var Builder $query */
        $query = DB::table('actions AS a')
            ->selectRaw('a.*, u.country, u.username as target_username')
            ->leftJoin('users AS u', 'u.id', '=', 'a.target')
            ->whereBetween('a.created_at', $date_range->getWhereBetweenArray());

        if ($request->get('tag', 'all') != 'all') {
            $query->where('a.tag', $request->get('tag'));
        } else {
            $query->whereIn('a.tag', array_keys(DataFormatHelper::getMonitoringLogOptions()));
        }

        if (!empty($request->get('actor'))) {
            $query->where('a.actor_username', 'LIKE', "%{$request->get('actor')}%");
        }

        if ($request->get('country', 'all') != 'all') {
            $query->where('u.country', $request->get('country'));
        }


        if (!empty($request->get('unique'))) {
            $query->groupByRaw('a.target, a.tag, a.actor', true);
            $query = $query->get();
        }

        $processPage = function (&$data) {
            foreach ($data as $elem) {
                $elem->tag = DataFormatHelper::getMonitoringLogOptions($elem->tag);
            }
        };

        $paginator = new PaginationHelper($query, $request, ['length' => 25, 'order' => ['column' => 'created_at', 'dir' => 'DESC']]);
        if ($request->isXmlHttpRequest()) {
            $page = $paginator->getPage(false);
            $processPage($page['data']);
            return $app->json($page);
        } else {
            $page = $paginator->getPage();
            $processPage($page['data']);
            return $app['blade']->view()
                ->make('admin.user.monitoring-log-report', compact('page', 'app', 'date_range'))
                ->render();
        }
    }

    public function forceLimitsReport(Application $app, Request $request)
    {

        if ($request->isXmlHttpRequest()) {
            foreach ($request->get('form') as $form_elem) {
                $request->request->set($form_elem['name'], $form_elem['value']);
            };
        }

        //$date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_LAST_6_MONTHS);

        /** @var Builder $query */
        $query = DB::table('users_settings AS us')
            ->selectRaw(" u.id,
                          u.username,
                          u.country,
                          u.currency,
                          us.setting,
                          SUBSTRING_INDEX(us.setting, '-', 2) as limit_type,
                          us.value as force_expiration_time,
                          us.created_at as forced_at,
                          ROUND(curlim.value/100) as current_limit,
                          curduration.value as current_duration,
                          CASE  WHEN SUBSTRING_INDEX(us.setting, '-', 2) = 'lgaloss-lim'
                                  THEN IFNULL(ROUND((curlim.value - lgaloss.val) / 100), ROUND(curlim.value/100))
                                WHEN SUBSTRING_INDEX(us.setting, '-', 2) = 'lgawager-lim'
                                  THEN IFNULL(ROUND((curlim.value - lgawager.val) / 100), ROUND(curlim.value/100))
                                WHEN SUBSTRING_INDEX(us.setting, '-', 2) = 'dep-lim'
                                  THEN IFNULL(ROUND((curlim.value - (SELECT sum(d.amount) FROM deposits d WHERE d.user_id = us.user_id AND d.timestamp > us.created_at)) / 100), ROUND(curlim.value/100))
                                ELSE '' END AS remaining")
            ->leftJoin('users AS u', 'u.id', '=', 'us.user_id')
            ->leftJoin('users_settings AS curlim', function (JoinClause $join) {
                $join->on('curlim.user_id', '=', 'us.user_id')->whereRaw("curlim.setting = CONCAT('cur-', SUBSTRING_INDEX(us.setting, '-', 2))");
            })
            ->leftJoin('users_settings AS curduration', function (JoinClause $join) {
                $join->on('curduration.user_id', '=', 'us.user_id')->whereRaw("curduration.setting = CONCAT(SUBSTRING_INDEX(us.setting, '-', 2), '_duration')");
            })
            ->leftJoin('lga_log AS lgaloss', function (JoinClause $join) {
                $join->on('lgaloss.user_id', '=', 'us.user_id')->where('lgaloss.nm', 'lossamount');
            })
            ->leftJoin('lga_log AS lgawager', function (JoinClause $join) {
                $join->on('lgawager.user_id', '=', 'us.user_id')->where('lgawager.nm', 'betamount');
            })
            ->where('us.setting', 'LIKE', '%forced_until%');

        //->whereBetween('a.created_at', $date_range->getWhereBetweenArray());

        if ($request->get('country', 'GB') != 'all') {
            $query->where('u.country', $request->get('country', 'GB'));
        }

        if ($request->get('limit-type', 'all') != 'all') {
            $query->whereRaw("SUBSTRING_INDEX(us.setting, '-', 2) = '{$request->get('limit-type')}'");
        }

        $processPage = function (&$data) {
            foreach ($data as $elem) {
                $elem->limit_type = DataFormatHelper::getLimitsNames($elem->limit_type);
            }
        };

        $paginator = new PaginationHelper($query, $request, ['length' => 25, 'order' => ['column' => 'force_expiration_time', 'dir' => 'ASC']]);
        if ($request->isXmlHttpRequest()) {
            $page = $paginator->getPage(false);
            $processPage($page['data']);
            return $app->json($page);
        } else {
            $page = $paginator->getPage();
            $processPage($page['data']);
            return $app['blade']->view()
                ->make('admin.user.force-limits-report', compact('page', 'app', 'date_range'))
                ->render();
        }
    }

    public function documentsManagementReport(Application $app, Request $request)
    {
        $dmapi = new Dmapi($app);

        if ($request->isXmlHttpRequest()) {
            foreach ($request->get('form') as $form_elem) {
                $request->request->set($form_elem['name'], $form_elem['value']);
            };
        }

        $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_LAST_7_DAYS);

        $params = [
            'start_uploaded_time' => $date_range->getStart('date'),
            'end_uploaded_time' => $date_range->getEnd('date')
        ];

        if (!empty($request->get('target'))) {
            $params['user_id'] = User::findByUsername($request->get('target'))->id;
        }

        if ($request->get('agent', 'all') != 'all') {
            $params['agent_id'] = $request->get('agent');
        }

        if ($request->get('doc-tag', 'all') != 'all') {
            $params['docs_tag'] = $request->get('doc-tag');
        }

        if ($request->isXmlHttpRequest()) {
            $order = $request->get('order')[0];

            $docs = $dmapi->getDocumentsStats(
                $params,
                $request->get('length'),
                $request->get('start'),
                $request->get('columns')[$order['column']]['data'],
                $order['dir'],
                $request->get('draw')
            );
            return $app->json($docs);
        } else {
            $params['get-agents'] = 1;
            $docs = $dmapi->getDocumentsStats($params, 25, 0, 'documents.created_at', 'desc', []);

            return $app['blade']->view()->make('admin.user.docs-report', compact('app', 'docs', 'date_range'))->render();
        }
    }

    public function deleteRecentUsers(Application $app, Request $request)
    {
        try {
            unset($_SESSION['recent-users']);
        } catch (\Exception $e) {
            return new RedirectResponse($app['url_generator']->generate('user'));
        }
        return new RedirectResponse($app['url_generator']->generate('user'));
    }

    public function userSearchForm(Application $app)
    {
        $us = new User();
        $user_table_columns = $us->getTableColumns();

        return $app['blade']->view()->make('admin.user.search', compact('app', 'user_table_columns'))->render();
    }

    public function userSearchExport(Application $app, Request $request)
    {
        if (!p('download.csv')) {
            $app->abort('403');
        }
        $search_repo = new UserSearchRepository($app);
        return $search_repo->export($request);
    }

    public function userAjax(Application $app, Request $request)
    {
        $search_repo = new UserSearchRepository($app);
        $columns = $search_repo->getUserSearchColumnsList();

        if (!isset($_COOKIE['user-search-visible'])) {
            foreach ($columns['default_visibility'] as $k) {
                $columns['visible'][] = "col-$k";
            }
            setcookie('user-search-visible', json_encode($columns['visible']), 0, '/');
            $_COOKIE['user-search-visible'] = json_encode($columns['visible']);
        } else {
            $columns['visible'] = json_decode($_COOKIE['user-search-visible'], true);
        }



        return $app->json($this->getUserList($request, $app, ['ajax' => true]));
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param array $attributes
     * @return array
     */
    private function getUserList($request, $app, $attributes)
    {
        $search_repo = new UserSearchRepository($app);

        if ($attributes['sendtobrowse'] != 1) {
            $search_query = $search_repo->getUserSearchQuery($request);
        } else {
            $search_query = $search_repo->getUserSearchQuery($request, false, $attributes['users_list']);
        }

        $non_archived_count = DB::shsAggregate('users', "SELECT count(*) as total FROM ({$search_query->toSql()}) as sub", array_values($search_query->getBindings()))[0]->total;

        if ($attributes['sendtobrowse'] != 1 && $app['vs.config']['archive.db.support'] && $search_repo->not_use_archived == false) {
            $archived_search_query = $search_repo->getUserSearchQuery($request, true);
            try {
                $archived_count = DB::connection('videoslots_archived')->table(DB::raw("({$archived_search_query->toSql()}) as sub"))
                    ->mergeBindings($search_query)
                    ->count();
            } catch (\Exception $e) {
                $archived_count = 0;
            }
            $total_records = $non_archived_count + $archived_count;
        } else {
            $archived_count = 0;
            $total_records = $non_archived_count;
        }

        if ($attributes['ajax'] == true) {
            $start = $request->get('start');
            $length = $request->get('length');
            $order = $request->get('order')[0];
            $order_column = $request->get('columns')[$order['column']]['data'];
            $order_dir = $order['dir'];
        } else {
            $start = 0;
            $length = $total_records < $attributes['length'] ? $total_records : $attributes['length'];
            $order_column = 'id';
            $order_dir = 'DESC';
        }

        if ($attributes['sendtobrowse'] !== 1 && $app['vs.config']['archive.db.support'] && $archived_count > 0) {
            $non_archived_records = $search_query->orderBy($order_column, $order_dir)->limit($length)->skip($start)->get();
            $non_archived_slice_count = $non_archived_records;
            if ($non_archived_slice_count < $length) {
                $next_length = $length - $non_archived_slice_count;
                $next_start = $start - $non_archived_count;
                if ($next_start < 0) {
                    $next_start = 0;
                }
                $archived_records = $archived_search_query->orderBy($order_column, $order_dir)->limit($next_length)->skip($next_start)->get();
                if ($non_archived_slice_count > 0) {
                    $data = $non_archived_records->merge($archived_records);
                } else {
                    $data = $archived_records;
                }
            } else {
                $data = $non_archived_records;
            }
        } else {
            $data = $search_query->orderBy($order_column, $order_dir)->limit($length)->skip($start)->get();
        }

        if ($request->isXmlHttpRequest()) {
            $app['monolog']->addError(json_encode(DB::getAllConnectionsQueryLog()));
        }


        $final = $data->map(function($element) use ($search_repo, $privacy_settings) {
            foreach (array_keys((array) $element) as $key) {
                if ($search_repo->getPermission($key) === false) {
                    if (is_object($element)) {
                        $element->{$key} = "************";
                    } else {
                        $element[$key] = "************";
                    }
                }

                if (in_array($key, $privacy_settings)) {
                    if (is_object($element)) {
                        $element->{$key} = $element->{$key} == '1' ? 'Yes' : 'No';
                    } else {
                        $element[$key] = $element[$key] == '1' ? 'Yes' : 'No';
                    }
                }
            }
            return $element;
        })->toArray();

        // Commented out, as the bonus abuser check was not realiable - Fernando

        /*
        $get_ = $request->get('other');

        $checks = $data->toArray();
        $final = $checks;
        $abusers = [];

        $abuser = "";
        // Pagination
        if (!empty($request->get('form'))){
            foreach ($request->get('form') as $key => $value) {
                if ($value['other']['abuser'] == "yes"){
                    $abuser = "yes";
                    break;
                }
                if ($value['other']['abuser'] == "no"){
                    $abuser = "no";
                    break;
                }

            }
        }
        else{
            $abuser = $request->get('other')['abuser'];
        }
        */


        /*
        if (isset($abuser) && !empty($abuser)){
            foreach ($checks as $key => $check) {
                    // Bonus abuser check
                $user =  cu((int)$check->id);

                if ($user->isBonusBlocked()){

                    if ($abuser == "yes"){
                        $abusers[] = $check; // create a user list only of abusers
                    }
                    else{  // create a clean list
                        unset($final[$key]);
                    }
                }
            ///if ($check->id)
            }
        }

        if ($abuser == "yes"){ // we give out the
            $final = $abusers;
        }
        */
        return [
            "draw" => intval($request->get('draw')),
            "recordsTotal" => intval($total_records),
            "recordsFiltered" => intval($total_records),
            "data" => $final
        ];
    }

    /**
     * Filtering users and paginate the results
     *
     * @param Application $app
     * @param Request $request
     * @param mixed $users_list
     * @return mixed
     */
    public function userSearchList(Application $app, Request $request, $users_list = null)
    {
        $us = new User();
        $user_table_columns = $us->getTableColumns();

        $search_repo = new UserSearchRepository($app);
        $columns = $search_repo->getUserSearchColumnsList();

        if (!isset($_COOKIE['user-search-visible'])) {
            foreach ($columns['default_visibility'] as $k) {
                    $columns['visible'][] = "col-$k";
            }
            setcookie('user-search-visible', json_encode($columns['visible']), 0, '/');
            $_COOKIE['user-search-visible'] = json_encode($columns['visible']);
        } else {
            $columns['visible'] = json_decode($_COOKIE['user-search-visible'], true);
        }


        $res = $this->getUserList($request, $app, [
            'ajax' => false,
            'length' => 25,
            'sendtobrowse' => $request->get('sendtobrowse', 0),
            'users_list' => $users_list
        ]);

        $initial = [
            'data' => $res['data'],
            'defer_option' => $res['recordsTotal'],
            'initial_length' => 25
        ];

        if (count($users_list) == 1) {
            $app['request_stack']->getCurrentRequest()->request->set('user', ['id' => $users_list[0]]);
        } elseif (count($users_list) > 1) {
            $app['request_stack']->getCurrentRequest()->request->set('user', ['id' => implode(',', $users_list)]);
        }

        return $app['blade']->view()->make('admin.user.list', compact('users', 'count', 'app', 'columns', 'user_table_columns', 'initial'))->render();
    }

}
