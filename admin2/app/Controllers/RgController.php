<?php

namespace App\Controllers;

use App\Classes\DateRange;
use App\Commands\UserMonthlyInteractionReportCommand;
use App\Extensions\Database\ReplicaFManager as ReplicaDb;
use App\Repositories\UserRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

use Carbon\Carbon;
use App\Helpers\DataFormatHelper;
use App\Helpers\PaginationHelper;

use App\Models\User;
use App\Extensions\Database\FManager as DB;
use App\Models\RiskProfileRating;
use App\Repositories\RgRepository;


class RgController extends TemplateController implements ControllerProviderInterface
{
    /**
     *
     * @var type
     */
    private $query    = [];

    private static string $section = 'rg';

    /**
     * Get and set all the parameters useful for all templates.
     * @param Application $app
     * @param Request $request
     */
    public function getParams(Application $app, Request $request, $params = [], $date_default = DateRange::DEFAULT_CUR_MONTH) {
        $query = [];
        $params[LABEL_TOT] = 'Tot';
        $params['totPlaceHolder'] = 'Having Count';
        $params['current_year_month'] = date("Y-m");
        $query['date_range']    = "";
        $query['date_range2']   = " BETWEEN :start_date2 AND :end_date2 ";
        $query['having_count']  = " HAVING COUNT(*) > :having_count ";
        $query['limit_type']    = " AND users_settings.setting = :limit_type ";
        $query['lock_type']     = " AND lock_type LIKE :lock_type ";
        $query['country']       = " AND users.country = :country";
        $query['username']      = " AND users.username = :username";
        $query['user_comment_tags'] = " AND uc.tag = :user_comment_tags ";
        $query['user_comment_tags_default'] = " AND uc.tag IN ('limits','complaint') ";
        $query['user_id'] = " AND users.id = :user_id ";

        if ($request->isMethod('POST')) {
            foreach ($request->get('form') as $form_elem) {
                $params[$form_elem['name']] = $form_elem['value'];
                if (empty($params['date-range'])) {
                    $params['start_date'] = Carbon::now()->firstOfMonth()->format('Y-m-d') . ' 00:00:00';
                    $params['end_date'] = Carbon::now()->endOfMonth()->format('Y-m-d') . ' 23:59:59';
                } else {
                    $params['start_date'] = explode(' - ', $params['date-range'])[0] . ' 00:00:00';
                    $params['end_date'] = explode(' - ', $params['date-range'])[1] . ' 23:59:59';
                }
                if (empty($params['date-range2'])) {
                    $params['start_date2'] = Carbon::now()->firstOfMonth()->format('Y-m-d') . ' 00:00:00';
                    $params['end_date2'] = Carbon::now()->endOfMonth()->format('Y-m-d') . ' 23:59:59';
                } else {
                    $params['start_date2'] = explode(' - ', $params['date-range2'])[0] . ' 00:00:00';
                    $params['end_date2'] = explode(' - ', $params['date-range2'])[1] . ' 23:59:59';
                }
            }
        } else {
            $date_range = DateRange::rangeFromRequest($request, $date_default);
            $params['start_date'] = $date_range->getStart('timestamp');
            $params['end_date'] = $date_range->getEnd('timestamp');

            if (empty($request->get('date-range2'))) {
                $params['start_date2'] = Carbon::now()->firstOfMonth()->format('Y-m-d') . ' 00:00:00';
                $params['end_date2'] = Carbon::now()->endOfMonth()->format('Y-m-d') . ' 23:59:59';
            } else {
                $params['start_date2'] = explode(' - ', $request->get('date-range2'))[0] . ' 00:00:00';
                $params['end_date2'] = explode(' - ', $request->get('date-range2'))[1] . ' 23:59:59';
            }
            $params['country'] = $request->get('country');
            $params['having_count'] = $request->get('having_count');
            $params['levenshtein_distance'] = $request->get('levenshtein_distance');
            $params['username'] = $request->get('username');
            $params['lock_type'] = $request->get('lock_type');
            $params['date-range'] = $request->get('date-range') ? $request->get('date-range') : Carbon::now()->firstOfMonth()->format('Y-m-d') . ' 00:00:00' . ' - ' . Carbon::now()->endOfMonth()->format('Y-m-d') . ' 23:59:59' ;
            $params['single_date'] = $request->get('single_date') ? $request->get('single_date') : date("Y-m-d");
            $params['limit_type'] = $request->get('limit_type');
            $params['user_comment_tags'] = $request->get('user_comment_tags');
            $params['locked_days_count'] = $request->get('locked_days_count', 0);
            $params['balance'] = $request->get('balance', 0);
            $params['user_id'] = $request->get('user_id');

        }

//        $this->paramToBindings($query, $params);
//        $this->bindings  = $bindings;
        $this->params    = $params;
        $this->query     = $query;
    }



    /**
     * Routes for all methods.
     * It use informations coming from the menu
     * @param Application $app
     * @return Application
     */
    public function connect(Application $app)
    {
        $section = self::$section;

        $factory = $app['controllers_factory'];
        $this->subMenu = $app['vs.menu']['rg']['submenu'];
        foreach ($this->subMenu as $a) {
            $url        = $a['url'];
            $method     = $a['method'];
            $methodName = $a['methodName'];
            self::$map[$methodName] = $url;
            if($url == 'responsible-gaming-monitoring')
                continue; //executed in monitoring controller
            if ($method == 'GET') {
                $factory->get($url . '/', "App\Controllers\RgController::$methodName")
                    ->bind($url)
                    ->before(function () use ($app, $section) {
                        if (!p("$section.section")) {
                            $app->abort(403);
                        }
                    });
            } elseif ($method == 'GET|POST') {
                $factory->match($url . '/', "App\Controllers\RgController::$methodName")
                    ->bind($url)
                    ->before(function () use ($app, $section) {
                        if (!p("$section.section")) {
                            $app->abort(403);
                        }
                    })->method('GET|POST');
            }
        }
        $factory->match('/{user}/risk-score/', 'App\Controllers\RgController::minFraud')
            ->convert('user', $app['userProvider'])
            ->bind('admin.user-risk-score')
            ->before(function () use ($app) {
                if (!p('user.risk-score')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');
        $factory->match('/user-risk-score-report/', 'App\Controllers\RgController::riskScoreReport')
            ->bind('rg.user-risk-score-report')
            ->before(function () use ($app) {
                if (!(p('users.risk.score.report'))) {
                    $app->abort(401);
                }
            })
            ->method('GET|POST');

        $factory->match('/grs-score-report/', 'App\Controllers\RgController::grsScoreReport')
            ->bind('rg.grs-score-report')
            ->before(function () use ($app) {
                if (!(p('rg.grs.risk.report'))) {
                    $app->abort(401);
                }
            })->method('GET|POST');

        $factory->match('/interaction-result-report/', 'App\Controllers\RgController::interactionResultReport')
            ->bind('rg.interaction-result-report')
            ->before(function () use ($app) {
                if (!(p('users.interaction.result.report'))) {
                    $app->abort(401);
                }
            })
            ->method('GET|POST');

        return $factory;
    }

    /**
     *
     * @param Application $app
     * @return type
     */
    public function dashboard(Application $app)
    {
        return $app['blade']->view()->make('admin.rg.index', compact('app'))->render();
    }
    /**
     * RG limit changes
       Should list all users who adds, changes or remove their limits.
       You should be able to filter on Username, Limit type (deposit, wager, loss, max bet and time-out limit), Action (Add, Increase, Decrease  and Removal of limit), Time period (Default current day), Country (default all)
       The list should show the below:
       Username | Country | Limit type | Action | Date/time | Previous Limit | New limit | Date/time of when new limit activates | Total deposits | Deposits current month | Average monthly deposit | Total loss | Loss current month | Average monthly loss
     * @param Application $app
     * @return type
     */
    public function limitChanges(Application $app, Request $request)
    {
        $url = self::$section . '.' .self::$map[__FUNCTION__];
        $columns = [
            'username'   =>  'username',
            'country'   =>  'country',
            'limit_type'    =>  'limit_type',
            'action'   =>  'action',
            'lock_date'   =>  'date',
            'previous_limit'   =>  'previous_limit',
            'new_limit'   =>  'new_limit',
            'date-new-limit'   =>  'date-new-limit',
            'total_deposits'   =>  'total_deposits',
            'deposits_this_month'   =>  'deposits_current_month',
            'average_monthly_deposit'   =>  'average_monthly_deposit',
            'gross'   =>  'total_loss',
            'loss_current_month'   =>  'loss_current_month',
            'average_monthly_loss'   =>  'average_monthly_loss',
        ];

        $this->getParams($app, $request);
        $params   = $this->params;
        $query    = $this->query;
        $aFields = ['country', 'limit_type', 'username'];
        $bindings = [];
        $params = $this->params;
        $query  = $this->query;
        foreach ($aFields as $field) {
            if (!empty($params[$field]) && $params[$field] != 'all') {
                $bindings[$field] = $params[$field];
                ${'where_' . $field} = $query[$field];
            }
        }
        $bindings['start_date'] = $params['start_date'];
        $bindings['end_date'] = $params['end_date'];
        /*
         * cur-dep-lim is the actual one
         * dep-lim is the new one (future)
         * cur-dep-lim-update-date when new limit will get triggered
         *
         */
        $sql = "
            SELECT 
                users.id,
                users.username,
                users.country,
                users_settings.setting as limit_type,
                '' as action,
                users_settings.created_at as lock_date,
                users_settings.setting as previous_limit,
                users_settings.value as new_limit,
                users_settings.setting as `date-new-limit`,
                SUM(users_monthly_stats.deposits) AS total_deposits,
                users_monthly_stats_two.deposits AS deposits_this_month,
                users_monthly_stats_two.gross AS loss_current_month,
                AVG(users_monthly_stats.deposits) as average_monthly_deposit,
                AVG(users_monthly_stats.gross) AS average_monthly_loss,
                SUM(users_monthly_stats.gross) AS gross,
                users_monthly_stats_two.gross as loss_current_month
            FROM 
                users
            JOIN
                users_settings 
            ON 
                users.id = users_settings.user_id
            JOIN 
                users_monthly_stats
            ON 
                users_monthly_stats.user_id = users.id
            JOIN 
            (
                SELECT * FROM 
                users_monthly_stats
                where users_monthly_stats.date = (select max(date) from users_monthly_stats) 
            )users_monthly_stats_two
            ON 
                users_monthly_stats_two.user_id = users.id
            WHERE 
                users_settings.created_at BETWEEN :start_date AND :end_date 
            $where_username
            $where_country
            $where_limit_type
            AND 
                users_settings.setting 
            IN 
                ('dep-lim', 'lgatime-lim', 'lgawager-lim', 'lgaloss-lim', 'betmax-lim')
            AND 
                users_monthly_stats.date = (select max(date) from users_monthly_stats)
            GROUP BY 
                users.id,limit_type";
            $res = DB::shsSelect("users", $sql, $bindings);
        foreach ($res as $r) {
            $r->total_deposits = DataFormatHelper::nf($r->total_deposits);
            $r->deposits_this_month = DataFormatHelper::nf($r->deposits_this_month);
            $r->average_monthly_deposit = DataFormatHelper::nf($r->average_monthly_deposit);
            $r->gross = DataFormatHelper::nf($r->gross);
            $r->loss_current_month = DataFormatHelper::nf($r->loss_current_month);
            $r->gross_this_month = DataFormatHelper::nf($r->gross_this_month);
            $r->average_monthly_loss = DataFormatHelper::nf($r->average_monthly_loss);
        }

        $paginator = new PaginationHelper($res, $request, ['length' => self::$pagLength, 'order' => ['column' => 'users.id', 'order' => 'DESC']]);
        return $this->sendToTemplate($app, $request,$sort, $columns, $url,  $paginator, $params, 'Limit Changes');
    }

    /**
    * RG self-exclusions & locked accounts
    * Should list all users who either locks their account or self-exclude.
    * You should be able to filter on Username, Lock type (Lock or Self-Exclusion), Time period (Default current day), Country (default all)
    * The list should show the below:
    * Username | Country | Lock type | Lock Date/time | Unlock Date/time | Total deposits | Deposits current month | Average monthly deposit | Total loss | Loss current month | Average monthly loss
     * @param Application $app
     * @return type
     */
    public function selfExclusionLockedAccounts(Application $app, Request $request)
    {
        $url = self::$section . '.' .self::$map[__FUNCTION__];
        $columns = [
            'id'   =>  'User ID',
            'country'   =>  'Country',
            'balance'   =>  'Balance',
            'user_currency'   =>  'Currency',
            'lock_type'    =>  'Limit type',
            'cur_lim'   =>  'Active value',
            'time_span'   =>  'Time span',
            'lock_date'   =>  'Created Date/time',
            'unlock_date'   =>  'Unlock Date/time',
            'total_deposits'   =>  'Total deposits',
            'deposits_this_month'   =>  'Deposits current month',
            'average_monthly_deposit'   =>  'Average monthly deposit',
            'gross'   =>  'Total loss',
            'gross_this_month'   =>  'Loss current month',
            'average_monthly_loss'   =>  'Average monthly loss',
        ];

        $rg_all_types  = [
            'deposit' => 'money',
            'wager'   => 'money',
            'loss'    => 'money',
            'betmax'  => 'money',
            'timeout' => 'months',
            'rc'      => 'months',
            'lock'    => 'days',
            'exclude' => 'days',
            'login'   => 'hours',
            'lockgamescat' => 'days'
        ];

        $rg_map = [
            'deposit' => 'Deposit limit',
            'wager'   => 'Wager limit',
            'loss'    => 'Loss limit',
            'betmax'  => 'Betmax',
            'timeout' => 'Timeout limit',
            'rc'      => 'Reality check',
            'lock'    => 'Self lock',
            'exclude' => 'Self exclusion',
            'exclude-external' => 'Self exclusion',
            'login'   => 'Login limit',
            'lockgamescat' => 'Spelpaus 24'
        ];

        $this->getParams($app, $request);
        $aFields = ['country', 'user_id'];
        $bindings = [];
        $params = $this->params;
        $query  = $this->query;
        foreach ($aFields as $field) {
            if (!empty($params[$field]) && $params[$field] != 'all') {
                $bindings[$field] = $params[$field];
                ${'where_' . $field} = $query[$field];
            }
        }
        $bindings['start_date'] = $params['start_date'];
        $bindings['end_date'] = $params['end_date'];
        $bindings['start_date2'] = $params['start_date'];
        $bindings['end_date2'] = $params['end_date'];
        $bindings['start_date3'] = $params['start_date'];
        $bindings['end_date3'] = $params['end_date'];
        $bindings['start_date4'] = $params['start_date'];
        $bindings['end_date4'] = $params['end_date'];
        if (!empty($params['lock_type']) && $params['lock_type']!='all') {
            $where_lock_type    = $this->query['lock_type'];
            if ($params['lock_type'] === 'exclude') {
                $bindings['lock_type'] = $params['lock_type'].'%';
            } else {
                $bindings['lock_type'] = $params['lock_type'];
            }

        }

        $sql = "SELECT lock_type, time_span, res.id AS id, res.id AS user_id, res.country AS country, cur_lim, unlock_date, total_deposits, deposits_this_month,
                        avg_deposits, gross_this_month, total_gross, average_monthly_loss, months, gross, balance, res.currency AS currency,
                        extra, lock_date, res.currency AS user_currency,
                        res.days_locked AS days_locked
                FROM (
                    SELECT rl.lock_type AS lock_type, rl.time_span AS time_span, rl.user_id AS id ,users.country AS country,
                    rl.cur_lim AS cur_lim, rl.unlock_date AS unlock_date,
                    rl.lock_date AS lock_date,
                    users_lifetime_stats.deposits AS total_deposits,
                    sum(users_daily_stats.deposits) AS deposits_this_month,
                    users_lifetime_stats.deposits / IF(TIMESTAMPDIFF(MONTH, users.register_date, CURDATE()) = 0, 1, TIMESTAMPDIFF(MONTH, users.register_date, CURDATE())) AS avg_deposits,
                    sum(users_daily_stats.gross) AS gross_this_month,
                    users_lifetime_stats.gross AS total_gross,
                    users_lifetime_stats.gross / IF(TIMESTAMPDIFF(MONTH, users.register_date, CURDATE()) = 0, 1, TIMESTAMPDIFF(MONTH, users.register_date, CURDATE())) AS average_monthly_loss,
                    TIMESTAMPDIFF(MONTH, users.register_date, CURDATE()) AS months,
                    users_lifetime_stats.gross AS gross,
                    users.cash_balance AS balance,
                    users.currency AS currency,
                    rl.extra AS extra,
                    rl.days_locked AS days_locked
                    FROM (
                        SELECT 'exclude-external' AS lock_type,
                            DATE(us1.value) AS lock_date,
                            '' AS unlock_date,
                            us1.user_id AS user_id,
                            '' AS time_span, 
                            '' AS cur_lim, 
                            '' AS extra,
                            'external' AS days_locked
                        FROM users_settings  us1
                        WHERE 
                            us1.setting = 'external-excluded'
                            AND us1.created_at between :start_date4 and :end_date4
                        GROUP BY user_id
                        UNION ALL
                        SELECT 'exclude' AS lock_type,
                            DATE(us1.value) AS lock_date,
                            DATE(us2.value) AS unlock_date,
                            us1.user_id AS user_id,
                            '' AS time_span, 
                            '' AS cur_lim, 
                            '' AS extra,
                            DATEDIFF(us2.value, us1.value) AS days_locked
                        FROM users_settings  us1
                        LEFT JOIN users_settings us2 ON us1.user_id = us2.user_id 
                        WHERE 
                             us1.setting = 'excluded-date' AND us2.setting = 'unexclude-date' 
                        AND us1.created_at between :start_date and :end_date
                        GROUP BY user_id
                        UNION ALL
                        SELECT 'lock' AS lock_type,
                            DATE(us1.created_at) AS lock_date,
                            DATE(us2.value) AS unlock_date,
                            us1.user_id AS user_id,
                            '' AS time_span, 
                            '' AS cur_lim, 
                            '' AS extra,
                            DATEDIFF(us2.value, us1.created_at) AS days_locked
                        FROM users_settings  us1
                        LEFT JOIN users_settings us2 ON us1.user_id = us2.user_id 
                        WHERE us1.setting = 'lock-hours'
                        AND us2.setting = 'unlock-date'
                        AND us1.created_at between :start_date2 and :end_date2
                        GROUP BY user_id
                        UNION ALL
                        SELECT 
                            rls.type AS lock_type, 
                            DATE(rls.updated_at) AS lock_date,
                            DATE(IF( UNIX_TIMESTAMP(rls.resets_at) = 0, rls.changes_at, rls.resets_at)) AS unlock_date,
                            rls.user_id AS user_id,
                            rls.time_span AS time_span,
                            rls.cur_lim AS cur_lim, 
                            rls.extra AS extra,
                            DATEDIFF(IF( UNIX_TIMESTAMP(rls.resets_at) = 0, rls.changes_at, rls.resets_at), rls.updated_at) AS days_locked
                        FROM rg_limits AS rls
                        WHERE type NOT IN('lock', 'exclude')
                    ) AS rl
                    JOIN
                      users 
                    ON  
                      user_id = users.id
                    JOIN 
                      users_lifetime_stats
                    ON
                      users_lifetime_stats.user_id = users.id
                    LEFT JOIN 
                        users_daily_stats 
                    ON 
                        users.id = users_daily_stats.user_id AND users_daily_stats.date BETWEEN LAST_DAY(NOW() - INTERVAL 1 MONTH) AND LAST_DAY(NOW())
                    LEFT JOIN currencies c ON c.code = users.currency 
                    GROUP BY 
                        users.id, lock_type
              ) AS res
                JOIN
                  users 
                ON  
                  res.id = users.id
                WHERE 1 
                $where_country
                $where_user_id
                $where_lock_type
                AND lock_date BETWEEN :start_date3 AND :end_date3 
                ORDER BY lock_date DESC, user_id ASC
                LIMIT 2000
                ";

        $res = DB::shsSelect("users", $sql, $bindings);

        $days_difference_fn = function ($start_date, $end_date) {

            $start_date = Carbon::parse($start_date);
            $end_date = Carbon::parse($end_date);

            return $start_date->diffInDays($end_date);
        };

        foreach ($res as $key => $r) {
            // balance filtering
            if ($params['balance'] != 0 && chgToDefault($r->currency, $r->balance)/100 <= $params['balance'] ) {
                unset($res[$key]);
                continue;
            }

            // has been blocked for more than X days filter
            if (
                $params['locked_days_count'] != 0 &&
                !empty($r->unlock_date) &&
                $params['locked_days_count'] >= $days_difference_fn($r->lock_date, $r->unlock_date)

            ) {
                unset($res[$key]);
                continue;
            }

            if ($r->lock_type === 'lockgamescat') {
                $r->cur_lim = $r->extra;
            } else if ($r->lock_type === 'exclude' || $r->lock_type === 'lock') {
                $r->cur_lim = $r->days_locked;
            } else if ($r->lock_type === 'exclude-external') {
                $r->cur_lim = 'external';
            } else if ($rg_all_types[$r->lock_type] === 'money') {
                $r->cur_lim = DataFormatHelper::nf($r->cur_lim);
            }

            if ($r->time_span === 'na') {
                $r->time_span = '';
            }

            $r->lock_type = $rg_map[$r->lock_type];
            $r->total_deposits = DataFormatHelper::nf($r->total_deposits);
            $r->gross = DataFormatHelper::nf($r->gross);
            $r->deposits_this_month = DataFormatHelper::nf($r->deposits_this_month);
            $r->average_monthly_deposit = DataFormatHelper::nf($r->avg_deposits);
            $r->average_monthly_loss = DataFormatHelper::nf($r->average_monthly_loss);
            $r->gross_this_month = DataFormatHelper::nf($r->gross_this_month);
            $r->balance = DataFormatHelper::nf($r->balance);
            $r->lock_date = DataFormatHelper::nf($r->lock_date);
            if (!empty($r->unlock_date)) {
                $r->unlock_date = DataFormatHelper::nf($r->unlock_date);
            }


        }
        // export data
        if (!is_null($request->get('export'))) {
            $rg_repo = new RgRepository($app);
            return $rg_repo->exportRgLimits($res, "rg_limits_report");

        }
        $sort = '';//'lock_date';
        $paginator = new PaginationHelper($res, $request, ['length' => self::$pagLength, 'order' => ['column' => 'lock_date', 'dir' => 'DESC']]);
        return $this->sendToTemplate($app, $request, $sort, $columns, $url,  $paginator, $params, 'Self Exclusion Locked Accounts');

    }

    /**
    Should list all users which has a comment in their profile under category “Discussion about RG limits” and “Complaint”.
    You should be able to filter on Username, Category (Complaint or Discussion about RG limits), Time period (Default current day), Country (default all)
    The list should show the below:
    Username | Country | Category | Comment | Active RG limits | | Total deposits | Deposits current month | Average monthly deposit | Total loss | Loss current month | Average monthly loss
     * @param Application $app
     * @param Request $request
     * @return type
     */
    public function interactions(Application $app, Request $request)
    {
        $url = self::$section . '.' .self::$map[__FUNCTION__];
        $columns = [
            'id'   =>  'User ID',
            'country'   =>  'Country',
            'currency'   =>  'Currency',
            'category'    =>  'Category',
            'comment'   =>  'Comment',
            'active_rg_limits'   =>  'Active RG limits',
            'total_deposits'   =>  'Total deposits',
            'deposits_this_month'   =>  'Deposits current month',
            'average_monthly_deposit'   =>  'Average monthly deposit',
            'gross'   =>  'Total loss',
            'gross_this_month'   =>  'Loss current month',
            'average_monthly_loss'   =>  'Average monthly loss',
            'created_at'   =>  'Created at',
        ];

        /*********************/
        $this->getParams($app, $request);
        $params   = $this->params;
        $query    = $this->query;
        $aFields = ['country', 'username'];
        $bindings = [];
        foreach ($aFields as $field) {
            if (!empty($params[$field]) && $params[$field] != 'all') {
                $bindings[$field] = $params[$field];
                ${'where_' . $field} = $query[$field];
            }
        }

        if(!empty($params['country']) && $params['country']!= 'all') {
            $where_country = " AND u.country = :country ";
        }
        if(!empty($params['username']) && $params['username']!= 'all') {
            $where_username = " AND u.username = :username ";
        }
        if(!empty($params['username']) && $params['username']!= 'all') {
            $where_username = " AND u.username = :username ";
        }


        $bindings['start_date'] = $params['start_date'];
        $bindings['end_date'] = $params['end_date'];
        /*********************/
        $where_tags = $this->query['user_comment_tags_default'];
        if (!empty($params['user_comment_tags']) && $params['user_comment_tags'] != 'all') {
            $where_tags = $this->query['user_comment_tags'];
            $bindings['user_comment_tags'] = $params['user_comment_tags'];
        }
        $sql = "
            SELECT
                uds.user_id as id,
                u.currency,
                u.country,
                uc.tag as category,
                uc.comment,
                uc.created_at,
                us_dep.value as dep_limit,
                us_loss.value as loss_limit,
                us_wager.value as wager_limit,
                us_betmax.value as betmax_limit,
                us_timeout.value as timeout_limit,
                sum(uds.deposits) as cur_month_deposits,
                uls.deposits as total_deposits,
                uls.deposits / IF(TIMESTAMPDIFF(MONTH, u.register_date, CURDATE()) = 0, 1, TIMESTAMPDIFF(MONTH, u.register_date, CURDATE())) as avg_deposits,
                sum(uds.gross) as cur_month_gross,
                uls.gross as total_gross,
                uls.gross / IF(TIMESTAMPDIFF(MONTH, u.register_date, CURDATE()) = 0, 1, TIMESTAMPDIFF(MONTH, u.register_date, CURDATE())) as avg_gross,
                uc.id as uc_id,
                TIMESTAMPDIFF(MONTH, u.register_date, CURDATE()) as months
             FROM users_comments uc
                LEFT JOIN users_daily_stats uds ON uc.user_id = uds.user_id AND uds.date BETWEEN LAST_DAY(NOW() - INTERVAL 1 MONTH) AND LAST_DAY(NOW())
                LEFT JOIN users_lifetime_stats uls ON uls.user_id = uds.user_id
                LEFT JOIN users u ON u.id = uds.user_id
                LEFT JOIN users_settings us_dep ON uc.user_id = us_dep.user_id AND us_dep.setting = 'dep-lim'
                LEFT JOIN users_settings us_loss ON uc.user_id = us_loss.user_id AND us_loss.setting = 'lgaloss-lim'
                LEFT JOIN users_settings us_wager ON uc.user_id = us_wager.user_id AND us_wager.setting = 'lgawager-lim'
                LEFT JOIN users_settings us_betmax ON uc.user_id = us_betmax.user_id AND us_betmax.setting = 'betmax-lim'
                LEFT JOIN users_settings us_timeout ON uc.user_id = us_timeout.user_id AND us_timeout.setting = 'lgatime-lim'
            WHERE uc.tag IN ('limits', 'complaint')
            AND u.id IS NOT NULL
            $where_tags
            $where_country
            $where_username
            AND uc.created_at BETWEEN :start_date AND :end_date
            GROUP BY uds.user_id";
        $res = DB::shsSelect("users", $sql, $bindings);
        $aLimit = [];
        foreach ($res as $r) {
            $limits = [];
            $limits[] = empty($r->dep_limit) ?: "Dep limit: {$r->dep_limit}";
            $limits[] = empty($r->loss_limit) ?: " Loss limit: {$r->loss_limit}";
            $limits[] = empty($r->wager_limit) ?: " Wager limit: {$r->wager_limit}";
            $limits[] = empty($r->betmax_limit) ?: " Betmax: {$r->betmax_limit}";
            $limits[] = empty($r->timeout_limit) ?: " Timeout: {$r->timeout_limit}";
            $r->active_rg_limits = implode(', ', array_filter($limits, function ($var) {
                return $var !== true;
            }));
            $r->total_deposits = DataFormatHelper::nf($r->total_deposits);
            $r->deposits_this_month = DataFormatHelper::nf($r->cur_month_deposits);
            $r->average_monthly_loss = DataFormatHelper::nf($r->cur_month_gross);
            $r->average_monthly_deposit = DataFormatHelper::nf($r->avg_deposits);
            $r->gross = DataFormatHelper::nf($r->total_gross);
            $r->gross_this_month = DataFormatHelper::nf($r->cur_month_gross);
        }
        $sort = 'created_at';
        $paginator = new PaginationHelper($res, $request, ['length' => self::$pagLength, 'order' => ['column' => 'created_at', 'dir' => 'DESC']]);
        return $this->sendToTemplate($app, $request, $sort, $columns, $url, $paginator, $params);
    }
    /**
     * Should list users that has increased or decreased changes in their wagering patterns.
     * You should be able to filter on Username, Country ( Default: all),
     * Time period and compare to another time period. The list should show the increase or decrease in wagering and wager per bet,
     * both sum and percentage. Also the RTP for both periods.
     *
     * @param Application $app
     * @param Request $request
     * @return type
     */
    public function changeOfPlayingPattern(Application $app, Request $request) {
        $url = self::$section . '.' .self::$map[__FUNCTION__];
        $columns = [
            'username'   =>  'Username',
            'country'   =>  'Country',
            'user_id'   =>  'User ID',
            'tot'    =>  'tot',
        ];
        $this->getParams($app, $request);
        $params   = $this->params;
        $query    = $this->query;
        $aFields = ['country', 'username'];
        $bindings = [];
        foreach ($aFields as $field) {
            if (!empty($params[$field]) && $params[$field] != 'all') {
                $bindings[$field] = $params[$field];
                ${'where_' . $field} = $query[$field];
            }
        }
        $bindings['start_date'] = $params['start_date'];
        $bindings['end_date'] = $params['end_date'];
        $sql = "";
        $res = DB::shsSelect("users",$sql,$bindings);
        $paginator = new PaginationHelper($res, $request, ['length' => self::$pagLength, 'order' => ['column' => 'users.username', 'order' => 'DESC']]);
        return $this->sendToTemplate($app, $request, $sort, $columns, $url, $paginator, $params);
    }

    /**
     * Should list users that has increased or decreased changes in their deposit patterns.
     * You should be able to filter on Username, Country ( Default: all), Time period and compare to another time period.
     * The list should show the increase or decrease in deposit and deposit per transaction. Both sum and percentage.
     * @param Application $app
     * @param Request $request
     * @return type
     */
    public function changeOfDepositPattern(Application $app, Request $request) {
        $url = self::$section . '.' .self::$map[__FUNCTION__];
        $columns = [
            'id'    =>  'User ID',
            'country'    =>  'Country',
            'first_amount'   =>  'First period amount',
            'second_amount'   =>  'Second amount',
            'currency'   =>  'Currency',
            'sum'   =>  'Sum',
            'percentage'   =>  'Percentage'
        ];

        /*********************/
        $this->getParams($app, $request);
        $params   = $this->params;
        $query    = $this->query;
        $aFields = ['country', 'username'];
        $bindings = [];
        foreach ($aFields as $field) {
            if (!empty($params[$field]) && $params[$field] != 'all') {
                $bindings[$field] = $params[$field];
                ${'where_' . $field} = $query[$field];
            }
        }

        if (empty($request->get('date-range'))) {
            $params['start_date'] = Carbon::now()->startOfMonth()->subMonth()->toDateString() . ' 00:00:00';
            $params['end_date'] = Carbon::now()->subMonth()->endOfMonth()->toDateString() . ' 23:59:59';
        }

        $bindings['start_date'] = $params['start_date'];
        $bindings['end_date'] = $params['end_date'];
        /*********************/
        $bindings['start_date2'] = $this->params['start_date2'];
        $bindings['end_date2']   = $this->params['end_date2'];
        $where_date_range2       = $this->query['date_range2'];
        $sql = "SELECT 
                    users.id as id,
                    first.currency as currency,
                    users.country as country,
                    first.amount as first_amount,second.amount as second_amount,
                    second.amount - first.amount as sum,
                    second.amount / first.amount * 100 as percentage
                FROM 
                (
                    SELECT SUM(amount)as amount,user_id,currency
                    FROM 
                        deposits
                    WHERE status='approved'
                    AND timestamp BETWEEN :start_date AND :end_date
                    GROUP BY user_id
                )first
                JOIN
                (
                    SELECT sum(amount)as amount,user_id from deposits
                    WHERE status='approved'
                    AND timestamp $where_date_range2
                    GROUP BY user_id
                )second
                ON 
                    first.user_id = second.user_id
                JOIN 
                    users
                ON 
                    users.id = first.user_id
                $where_username
                $where_country";
        $res = DB::shsSelect("users", $sql, $bindings);
        foreach ($res as $r) {
            $r->first_amount = DataFormatHelper::nf($r->first_amount);
            $r->second_amount = DataFormatHelper::nf($r->second_amount);
            $r->percentage = number_format((float)$r->percentage, 2, '.', '');
            $r->sum = DataFormatHelper::nf($r->sum);
        }
        $sort = 'percentage';
        $paginator = new PaginationHelper($res, $request, ['length' => self::$pagLength, 'order' => ['column' => 'percentage', 'dir' => 'DESC']]);
        return $this->sendToTemplate($app, $request, $sort, $columns, $url, $paginator, $params);
    }
    /**
     * Should list users that has increased or decreased changes in their deposit patterns.
     * You should be able to filter on Username, Country ( Default: all), Time period and compare to another time period.
     * The list should show the increase or decrease in deposit and deposit per transaction. Both sum and percentage.
     * @param Application $app
     * @param Request $request
     * @return type
     */
    public function changeOfWagerPattern(Application $app, Request $request) {
        $url = self::$section . '.' .self::$map[__FUNCTION__];
        $columns = [
            'id'    =>  'User ID',
            'country'    =>  'Country',
            'first_amount'   =>  'First period amount',
            'second_amount'   =>  'Second amount',
            'currency'   =>  'Currency',
            'sum'   =>  'Sum',
            'percentage'   =>  'Percentage'
        ];

        /*********************/
        $this->getParams($app, $request);
        $params   = $this->params;
        $query    = $this->query;
        $aFields = ['country', 'username'];
        $bindings = [];
        foreach ($aFields as $field) {
            if (!empty($params[$field]) && $params[$field] != 'all') {
                $bindings[$field] = $params[$field];
                ${'where_' . $field} = $query[$field];
            }
        }

        if (empty($request->get('date-range'))) {
            $params['start_date'] = Carbon::now()->startOfMonth()->subMonth()->toDateString() . ' 00:00:00';
            $params['end_date'] = Carbon::now()->subMonth()->endOfMonth()->toDateString() . ' 23:59:59';
        }

        $bindings['start_date'] = $params['start_date'];
        $bindings['end_date'] = $params['end_date'];
        /*********************/
        $bindings['start_date2'] = $this->params['start_date2'];
        $bindings['end_date2']   = $this->params['end_date2'];
        $where_date_range2       = $this->query['date_range2'];
        $sql = "SELECT 
                    users.id as id,
                    users.username as username,
                    first.currency as currency,
                    users.country as country,
                    first.amount as first_amount,second.amount as second_amount,
                    second.amount - first.amount as sum,
                    second.amount / first.amount * 100 as percentage
                FROM 
                (
                    SELECT SUM(amount)as amount,user_id,currency
                    FROM 
                        deposits
                    WHERE status='approved'
                    AND timestamp BETWEEN :start_date AND :end_date
                    GROUP BY user_id
                )first
                JOIN
                (
                    SELECT sum(amount)as amount,user_id from deposits
                    WHERE status='approved'
                    AND timestamp $where_date_range2
                    GROUP BY user_id
                )second
                ON 
                    first.user_id = second.user_id
                JOIN 
                    users
                ON 
                    users.id = first.user_id
                $where_username
                $where_country";
        $res = DB::shsSelect("users", $sql, $bindings);
        foreach ($res as $r) {
            $r->first_amount = DataFormatHelper::nf($r->first_amount);
            $r->second_amount = DataFormatHelper::nf($r->second_amount);
            $r->percentage = number_format((float)$r->percentage, 2, '.', '');
            $r->sum = DataFormatHelper::nf($r->sum);
        }
        $paginator = new PaginationHelper($res, $request, ['length' => self::$pagLength, 'order' => ['column' => 'users.username', 'order' => 'DESC']]);
        return $this->sendToTemplate($app, $request, $sort, $columns, $url, $paginator, $params);
    }

    /**
     * Should be able to select time period and filter on how many times an account got blocked. Also per username, country,
     * Show amount of time users account has been blocked.
     * Default should show last 3 months people who blocked their accounts more than 10 times.
     * @param Application $app
     * @param Request $request
     * @return type
     */
    public function frequentAccountClosingReopening(Application $app, Request $request) {
        $url = self::$section . '.' .self::$map[__FUNCTION__];
        $columns = [
            'id'   =>  'User ID',
            'country'   =>  'Country',
            'reason'   =>  'Reason',
            'tot'    =>  'tot',
        ];
        /*********************/
        $this->getParams($app, $request);
        $params   = $this->params;
        $query    = $this->query;
        $aFields = ['country', 'username'];
        $bindings = [];
        foreach ($aFields as $field) {
            if (!empty($params[$field]) && $params[$field] != 'all') {
                $bindings[$field] = $params[$field];
                ${'where_' . $field} = $query[$field];
            }
        }
        $bindings['start_date'] = $params['start_date'];
        $bindings['end_date'] = $params['end_date'];
        /*********************/
        $where_having_count       = $this->query['having_count'];
        $query                    = $this->query;
        $params['having_count']   = $params['having_count'] ? $params['having_count'] : 2;
        $bindings['having_count'] = $params['having_count'];
        $params[LABEL_TOT] = 'Blocked for more than N times:';
        $sql = "SELECT
                    users.id, 
                    count(*) as tot,
                    users.username, 
                    users.country,
                    users_blocked.reason
                FROM
                    users_blocked 
                JOIN
                    users 
                ON  
                    users_blocked.user_id = users.id
                WHERE users_blocked.date BETWEEN :start_date AND :end_date
                $where_username
                $where_country
                GROUP BY 
                    users.id,reason
                $where_having_count";
        $res = DB::shsSelect("users", $sql, $bindings);

        foreach ($res as $r) {
            $r->reason = phive('DBUserHandler')->getBlockReasonStr($r->reason);
        }
        $sort = 'tot';
        $paginator = new PaginationHelper($res, $request, ['length' => self::$pagLength, 'order' => ['column' => 'tot', 'dir' => 'DESC']]);
        return $this->sendToTemplate($app, $request, $sort, $columns, $url, $paginator, $params);
    }

    /**
     * Should list all users who adds, changes or remove their limits.
     * You should be able to filter on Username, Limit type (deposit, wager, loss, max bet and time-out limit), Action (Add, Increase, Decrease  and Removal of limit), Time period (Default current day), Country (default all)
     * The list should show the below:
     * Username | Country | Limit type | Action | Date/time | Previous Limit | New limit | Date/time of when new limit activates | Total deposits | Deposits current month | Average monthly deposit | Total loss | Loss current month | Average monthly loss
     *
     * 'dep-lim'
     * 'lgaloss-lim'
     * 'lgawager-lim'
     * 'betmax-lim'
     * 'lgatime-lim'
     * @param Application $app
     * @param Request $request
     * @return type
     */
    public function multipleChangesToRgLimits(Application $app, Request $request) {
        $url = self::$section . '.' .self::$map[__FUNCTION__];
        $columns = [
            'id'   =>  'User ID',
            'country'   =>  'Country',
            'limit_type'   =>  'Limit Types',
            'tot'    =>  'tot',
        ];
        /*********************/
        $this->getParams($app, $request);
        $params   = $this->params;
        $query    = $this->query;
        $aFields = ['country', 'username'];
        $bindings = [];
        foreach ($aFields as $field) {
            if (!empty($params[$field]) && $params[$field] != 'all') {
                $bindings[$field] = $params[$field];
                ${'where_' . $field} = $query[$field];
            }
        }
        $bindings['start_date'] = $params['start_date'];
        $bindings['end_date'] = $params['end_date'];
        /*********************/
        $where_having_count       = $this->query['having_count'];
        $params['having_count']   = isset($params['having_count']) ? $params['having_count'] : 2;
        $bindings['having_count'] = $params['having_count'];
        $params[LABEL_TOT] = 'Changed limit more than N times:';

        $sql = "SELECT 
                    users.country,
                    user_id as id,
                    setting as limit_type,
                    COUNT(*) as tot
                FROM
                    users_settings
                JOIN 
                    actions
                ON 
                    users_settings.user_id = actions.target
                JOIN 
                    users
                ON 
                    users.id = users_settings.user_id
                WHERE 
                    setting in('cur-dep-lim', 'cur-lgatime-lim', 'cur-lgawager-lim', 'cur-lgaloss-lim', 'cur-betmax-lim')
                AND 
                    actions.tag = users_settings.setting
                AND 
                    users_settings.created_at between :start_date AND :end_date
                    $where_username
                    $where_country
                AND 
                    actions.actor = actions.target
                GROUP BY 
                    users_settings.user_id, users_settings.setting
                $where_having_count
                ORDER BY 
                    users.id,tot DESC";
        $res = DB::shsSelect("users", $sql, $bindings);
        foreach ($res as $r){
            $r->limit_type = str_replace(['cur-lgatime-lim','cur-dep-lim','cur-betmax-lim','cur-lgaloss-lim','cur-lgawager-lim'],['Timeout limit','Deposit limit','Maximum bet limit','Loss limit','Wager limit'],$r->limit_type);
        }
        $sort = 'tot';
        $paginator = new PaginationHelper($res, $request, ['length' => self::$pagLength, 'order' => ['column' => 'tot', 'dir' => 'DESC']]);
        return $this->sendToTemplate($app, $request, $sort, $columns, $url, $paginator, $params);
    }
    /**
     *
     * @param Application $app
     * @param Request $request
     * @return type
     */
    public function extendedGamePlay(Application $app, Request $request) {
        $url = self::$section . '.' .self::$map[__FUNCTION__];
        $columns = [
            'username'   =>  'Username',
            'country'    =>  'Country',
            'user_id'    =>  'User ID',
            'tot'        =>  'tot',
        ];

        $this->getParams($app, $request);
        $params   = $this->params;
        $query    = $this->query;
        $bindings = [];
        $aFields = ['country', 'username'];
        foreach ($aFields as $field) {
            if (!empty($params[$field]) && $params[$field] != 'all') {
                $bindings[$field] = $params[$field];
                ${'where_' . $field} = $query[$field];
            }
        }
        $bindings['start_date'] = $params['start_date'];
        $bindings['end_date'] = $params['end_date'];
        $bindings['having_count']   = $params['having_count'] ? $params['having_count'] : 2;

//        print_r($bindings);die;
        $datetimes = $result = [];
        $params[LABEL_TOT] = 'Playing for more than (hours):';
        $time_start = microtime(true);

        $sql = "SELECT 
                users_game_sessions.start_time,
                users_game_sessions.end_time,
                users_game_sessions.user_id,
                users.username,
                users.country
                FROM 
                    users_game_sessions
                JOIN
                    users 
                ON 
                    users.id = users_game_sessions.user_id
                AND
                    start_time BETWEEN :start_date AND :end_date
                $where_username
                $where_country
                GROUP BY user_id
                HAVING( TIMESTAMPDIFF(HOUR, MIN(start_time), MAX(end_time)) > 4)
                ORDER BY 
                    user_id";

        $res = DB::shsSelect("users_game_sessions", $sql, $bindings);
        $time_end = microtime(true);
        $mem_usage_start = memory_get_usage();
        $time = $time_end - $time_start;
        $mem_usage = $mem_usage_end - $mem_usage_start;
//        echo "Executed in $time seconds\n";
//        echo "Memory usage: $mem_usage\n";
//        die ("finished");

        $sqlHours = "SELECT SUM(hours) tot
                    FROM
                    (
                      SELECT TIMESTAMPDIFF(HOUR, MIN(start_time), MAX(end_time)) hours
                        FROM
                      (
                        SELECT start_time, end_time,
                            @g := IF(@e BETWEEN start_time AND end_time OR end_time < @e, @g, @g + 1) g,
                            @e := end_time,   users_game_sessions.user_id  
                        FROM users_game_sessions 
                        CROSS JOIN 
                        (
                            SELECT @g := 0, @e := NULL
                        ) i
                        where start_time between '2017-01-01 00:00:00' and '2017-01-01 23:59:59'
                        and user_id = :user_id
                        ORDER BY start_time, end_time
                      )q
                        GROUP BY g
                        HAVING hours > 4
                    )q";


        $n = 0;
        foreach ($res as $r) {
            $n++;
            $bindingsHours['user_id'] = $r->user_id;
            $resHours = DB::shsSelect("users_game_sessions", $sqlHours, $bindingsHours);
            $r->tot = $resHours[0]->tot;
//            $datetimes[$r->user_id][] = [new \DateTime($r->start_time),new \DateTime($r->end_time)];
        }

        //0.23 within 1 day - 2451 entries
//        foreach ($datetimes as $key => $period) {
//            $total_hours = self::total_hours_per_day($period);
//            if($total_hours > $params['having_count']) {
//                $result[] = (object)['user_id'=>$key, 'tot'=>$total_hours];
//            }
//            unset($datetimes[$key]);
//        }
//        $mem_usage_end = memory_get_usage();
//
////        //3.70  within 1 day - 2451 entries
//


        $paginator = new PaginationHelper($res, $request, ['length' => self::$pagLength, 'order' => ['column' => 'users.username', 'order' => 'DESC']]);
        return $this->sendToTemplate($app, $request, $sort, $columns, $url, $paginator, $params);
    }
    /**
     * e.g. more than 1 session a day or more than 5 times a week
     * @param Application $app
     * @param Request $request
     * @return type
     */
    public function frequentGamePlay(Application $app, Request $request) {
        $url = self::$section . '.' .self::$map[__FUNCTION__];
        $columns = [
            'id'   =>  'User ID',
            'country'    =>  'Country',
            'tot'        =>  'Tot',
        ];
        /*********************/
        $this->getParams($app, $request);
        $params   = $this->params;
        $query    = $this->query;
        $aFields = ['country', 'username','having_count'];
        $bindings = [];
        foreach ($aFields as $field) {
            if (!empty($params[$field]) && $params[$field] != 'all') {
                $bindings[$field] = $params[$field];
                ${'where_' . $field} = $query[$field];
            }
        }
        $bindings['start_date'] = $params['start_date'];
        $bindings['end_date'] = $params['end_date'];
        /*********************/
        $params[LABEL_TOT] = 'More than N sessions:';
        $where_having_count       = $this->query['having_count'];
        $params['having_count']   = $params['having_count'] ? $params['having_count'] : 2;
        $bindings['having_count'] = $params['having_count'];
        $sql = "SELECT 
                    users.id as id, users.country, count(*) as tot
                FROM 
                    users_sessions
                JOIN
                    users
                ON 
                    users.id = users_sessions.user_id
                WHERE 
                    users_sessions.created_at BETWEEN :start_date AND :end_date 
                $where_country
                $where_username
                GROUP BY 
                    date(users_sessions.created_at),users_sessions.user_id
                $where_having_count";
        $res = DB::shsSelect("users", $sql,$bindings);
        $sort = 'tot';
        $paginator = new PaginationHelper($res, $request, ['length' => self::$pagLength, 'order' => ['column' => 'tot', 'dir' => 'DESC']]);
        return $this->sendToTemplate($app, $request, $sort, $columns, $url, $paginator, $params);

    }
    /**
     * Cancellation of withdrawals (e.g. 3 or more in a 24 hour period)
     * @param Application $app
     * @param Request $request
     * @return type
     */
    public function cancellationOfWithdrawals(Application $app, Request $request) {
        $url = self::$section . '.' .self::$map[__FUNCTION__];
        $columns = [
            'id'   =>  'User ID',
            'country'    =>  'Country',
            'tot'        =>  'tot',
        ];
//        $bindings                 = $this->bindings;
        /*********************/
        $this->getParams($app, $request);
        $params   = $this->params;
        $query    = $this->query;
        $aFields = ['country', 'username'];
        $bindings = [];
        foreach ($aFields as $field) {
            if (!empty($params[$field]) && $params[$field] != 'all') {
                $bindings[$field] = $params[$field];
                ${'where_' . $field} = $query[$field];
            }
        }
        $bindings['start_date'] = $params['start_date'];
        $bindings['end_date'] = $params['end_date'];
        /*********************/
        $where_having_count       = $this->query['having_count'];
        $params['having_count']   = $params['having_count'] ? $params['having_count'] : 2;
        $bindings['having_count'] = $params['having_count'];
        $params[LABEL_TOT] = 'Equals or more than (in the period)';

        $sql = "SELECT
                    users.username,
                    users.country,
                    user_id as id,
                    count(*) as tot
                FROM
                    pending_withdrawals
                JOIN 
                    users
                ON 
                    pending_withdrawals.user_id = users.id
                WHERE 
                    approved_by = user_id
                $where_username
                $where_country
                AND 
                    timestamp BETWEEN :start_date AND :end_date 
                GROUP BY 
                    user_id
                $where_having_count";
        $res = DB::shsSelect("users", $sql, $bindings);
        $sort = 'tot';
        $paginator = new PaginationHelper($res, $request, ['length' => self::$pagLength, 'order' => ['column' => 'tot', 'dir' => 'DESC']]);
        return $this->sendToTemplate($app, $request, $sort, $columns, $url, $paginator, $params);

    }
    /**
     * High wagers per bet/spin relative to deposits;
     * Should by default show users that has an average bet/spin which is over 5%(editable) of their average deposit during last x days (select between to dates)
     * Show username and country. Should also be able to search on specific username or country.
     * @param Application $app
     * @param Request $request
     * @return type
     */
    public function highWagersPerBetSpin(Application $app, Request $request) {
        $url = self::$section . '.' .self::$map[__FUNCTION__];
        $this->getParams($app, $request);
        $columns = [
            'id'   =>  'User ID',
            'avg_bet'   =>  'Avg. bet',
            'avg_deposit'   =>  'Avg. deposit',
            'country'   =>  'country'
        ];
        /*********************/
        $this->getParams($app, $request);
        $params   = $this->params;
        $query    = $this->query;
        $aFields = ['username'];
        $bindings = [];
        foreach ($aFields as $field) {
            if (!empty($params[$field]) && $params[$field] != 'all') {
                $bindings[$field] = $params[$field];
                ${'where_' . $field} = $query[$field];
            }
        }
        $bindings['start_date'] = $bindings['start_date2'] = $params['start_date'] ;
        $bindings['end_date']   = $bindings['end_date2'] = $params['end_date'];

        /*********************/
        $where_having_count       = " HAVING sum(avg_bet) > (sum(avg_deposit) * :having_count) ";
        $params['having_count']   = $params['having_count'] ? $params['having_count'] : 5;
        $bindings['having_count'] = $params['having_count'];

        if(!empty($params['country']) && $params['country']!= 'all') {
            $where_country = " AND udgs.country = :country ";
            $where_country2 = " AND u.country = :country2 ";
            $bindings['country'] = $params['country'];
            $bindings['country2'] = $params['country'];
        }

        $where_date  = " WHERE udgs.date BETWEEN :start_date AND :end_date ";
        $where_date2 = " WHERE d.timestamp BETWEEN :start_date2 AND :end_date2 ";

        if(!empty($params['username']) && $params['username']!= 'all') {
            $where_username = " AND udgs.username = :username ";
            $where_username2 = " AND u.username = :username2 ";
            $bindings['username'] = $params['username'];
            $bindings['username2'] = $params['username'];
        }
        $sql = "SELECT
                sub.user_id as id,
                sub.country,
                sum(sub.avg_bet) as avg_bet,
                sum(sub.avg_deposit) as avg_deposit
              FROM (
                SELECT
                  udgs.user_id,
                  udgs.username,
                  IFNULL(sum(udgs.bets) / sum(udgs.bets_count), 0) AS avg_bet,
                  0                                                AS avg_deposit,
                  udgs.country
                FROM users_daily_game_stats udgs
                $where_date
                $where_country
                $where_username
                AND udgs.bets > 0
                GROUP BY udgs.user_id
                UNION
                SELECT
                  d.user_id,
                  u.username,
                  0                                                 AS avg_bet,
                  IFNULL(sum(d.amount) / count(d.id), 0)            AS avg_deposit,
                  u.country
                FROM
                  deposits d
                  LEFT JOIN users u ON u.id = d.user_id
                $where_date2
                $where_country2
                $where_username2
                AND status = 'approved'
                GROUP BY d.user_id
              ) as sub
              GROUP BY sub.user_id
              $where_having_count";
        $res = DB::shsSelect("users_game_sessions", $sql, $bindings);
        $paginator = new PaginationHelper($res, $request, ['length' => self::$pagLength, 'order' => ['column' => 'users.id', 'order' => 'DESC']]);
        return $this->sendToTemplate($app, $request,$sort, $columns, $url, $paginator, $params);

    }
    /**
     *
     * @param Application $app
     * @param Request $request
     */
    public function similarAccount(Application $app, Request $request) {
        $url = 'rg.similar-account';
        $columns = [
            'user_id'   =>  'User_id',
            'username'        =>  'Username',
            'email'        =>  'Email',
            'country'        =>  'Country',
            'dob'        =>  'Date of Birth',
            'similarTo'   =>  'similarTo',
            'created_at'    =>  'Created at',
        ];
        /*********************/
        $this->getParams($app, $request);
        $params   = $this->params;
        $query    = $this->query;
        $aFields = ['country', 'username'];
        $bindings = [];
        foreach ($aFields as $field) {
            if (!empty($params[$field]) && $params[$field] != 'all') {
                $bindings[$field] = $params[$field];
                ${'where_' . $field} = $query[$field];
            }
        }
        $bindings['start_date'] = $params['start_date'];
        $bindings['end_date'] = $params['end_date'];
        /*********************/
        $sql = "SELECT 
                    distinct(user_id) as user_id,users_settings.created_at,email,country,dob,username,actions.descr,replace(actions.descr,'Blocked because too similar to ','') as similarTo
                FROM 
                    users_settings
                JOIN 
                    users
                ON 
                    users.id = users_settings.user_id
                JOIN 
                    actions
                ON 
                    actions.target = users.id
                AND 
                    tag = 'block'
                AND 
                    users_settings.created_at BETWEEN :start_date AND :end_date
                AND 
                    lower(descr) like 'blocked because too similar to%'
                AND
                setting = 'similar_fraud'
                $where_username
                $where_country";

        $res = DB::shsSelect("users_settings", $sql, $bindings);
        foreach ($res as $k => $r) {
            $strRes = '';
            $aSimilarTo = explode(',', $r->similarTo);
            if(empty($aSimilarTo)) {
                unset($res[$k]);
                continue;
            }
            foreach ($aSimilarTo as $similar) {
                $oSimilar = ud($similar);
                $strRes .= "{$oSimilar['id']}-{$oSimilar['username']}-{$oSimilar['email']} | ";

            }
            if(empty($strRes)) {
                unset($res[$k]);
            }
            $user = ud($r->user_id);
            $r->similarTo = "$strRes";
        }
        $res = collect($res);
        $paginator = new PaginationHelper($res, $request, ['length' => self::$pagLength, 'order' => ['column' => 'created_at', 'dir' => 'DESC']]);
        return $this->sendToTemplate($app, $request,$sort, $columns, $url, $paginator, $params);
    }

    /**
     *
     * @param Application $app
     * @param Request $request
     * @param User|null $user
     * @return JsonResponse
     * @throws \Exception
     */
    public function minFraud(Application $app, Request $request, User $user = NULL) {
        $columns = [
            'risk_score'    =>  'Risk Score',
            'username'   =>  'Id',
            'countryconfidence'        =>  'Country Confidence',
            'countryiso'        =>  'Country iso',
            'countrygeoname'        =>  'Country geoname',
            'countryname'        =>  'Country name',
            'locationlatitude'   =>  'Latitude',
            'locationlongitude'    =>  'Longitude',
            'accuracyradius'    =>  'Accuracy radius',
            'time_zone'    =>  'Time zone',
            'cityconfidence'    =>  'City confidence',
            'citygeoname'    =>  'City geoname',
            'cityname'    =>  'City name',
            'continentcode'    =>  'Continent code',
            'continentname'    =>  'Continent name',
            'postalcode'    =>  'Postal code',
            'regcountry'    =>  'Reg country',
            'subname'    =>  'Sub name',
            'user_type'    =>  'User type',
            'asn'    =>  'Asn',
            'aso'    =>  'Aso',
            'domain'    =>  'Domain',
            'isp'    =>  'Isp',
            'organization'    =>  'Organization',
            'ip'    =>  'IP address',
            'first_seen'    =>  'First seen',
            'is_free'    =>  'Is free',
            'is_high_risk'    =>  'Is high risk',
            'is_postal_in_city'    =>  'Is postal in city',
            'billinglatitude'    =>  'Billing latitude',
            'billinglongitude'    =>  'Billing longitude',
        ];
        /*********************/
        $this->getParams($app, $request, [], DateRange::DEFAULT_TODAY);
        $params   = $this->params;
        $query    = $this->query;
        $currentURL = $app['request_stack']->getCurrentRequest()->getRequestUri();
        $end = end(explode('/', rtrim($currentURL, '/')));
        $url = 'rg.min-fraud';
        $bindings = [];
        $rating_score = [];
        $where_country = '';
        $where_username = '';
        $where_date = '';
        if($end == 'risk-score' && !empty($user)) {
            $url = 'user.risk-score';
            $params['username'] = $user->username;
            $where_username = ' AND username = :username';
            $bindings['username'] = $user->username;
        } else {
            $where_date = ' AND created_at BETWEEN :start_date AND :end_date';
            $aFields = ['country', 'username'];
            $bindings = [];
            foreach ($aFields as $field) {
                if (!empty($params[$field]) && $params[$field] != 'all') {
                    $bindings[$field] = $params[$field];
                    ${'where_' . $field} = $query[$field];
                }
            }
            $bindings['start_date'] = $params['start_date'];
            $bindings['end_date'] = $params['end_date'];
        }

        /*********************/
        $sql = "SELECT 
                users.id,
                users.username,
                users_settings.value as value
                FROM users_settings
                JOIN
                users
                ON users.id = users_settings.user_id
                WHERE setting = 'minfraud-result'
                $where_date
                $where_username
                $where_country
                ";
        $res = ReplicaDb::shsSelect("users_settings", $sql, $bindings);

        foreach ($res as $r) {
            $json = str_replace('\\',"",$r->value);//data is escaped...need to be done in a better way...
            $json = json_decode($json);
            $r->username = $r->id;
            $r->risk = $json->ip_address->risk;
            $r->countryconfidence = $json->ip_address->country->confidence;
            $r->countryiso = $json->ip_address->country->iso_code;
            $r->countrygeoname = $json->ip_address->country->geoname_id;
            $r->countryname = $json->ip_address->country->names->en;
            $r->locationlatitude = $json->ip_address->location->latitude;
            $r->locationlongitude = $json->ip_address->location->longitude;
            $r->accuracyradius = $json->ip_address->location->accuracy_radius;
            $r->time_zone = $json->ip_address->location->time_zone;
            $r->cityconfidence = $json->ip_address->city->confidence;
            $r->citygeoname = $json->ip_address->city->geoname_id;
            $r->cityname = $json->ip_address->city->names->en;
            $r->continentcode = $json->ip_address->continent->code;
            $r->continentname = $json->ip_address->continent->names->en;
            $r->postalcode = $json->ip_address->postal->code;
            $r->regcountry = $json->ip_address->registered_country->en;
            $r->subname = $json->ip_address->subdivisions[0]->names->en;
            $r->user_type = $json->ip_address->traits->user_type;
            $r->asn = $json->ip_address->traits->autonomous_system_number;
            $r->aso = $json->ip_address->traits->autonomous_system_organization;
            $r->domain = $json->ip_address->traits->domain;
            $r->isp = $json->ip_address->traits->isp;
            $r->organization = $json->ip_address->traits->organization;
            $r->ip = $json->ip_address->traits->ip_address;
            $r->first_seen = $json->email->first_seen;
            $r->is_free = $json->email->is_free;
            $r->is_high_risk = $json->email->is_high_risk;
            $r->is_postal_in_city = $json->billing_address->is_postal_in_city;
            $r->billinglatitude = $json->billing_address->latitude;
            $r->billinglongitude = $json->billing_address->longitude;
            $r->risk_score = $json->risk_score;
        }

        $paginator = new PaginationHelper($res, $request, ['length' => 50, 'order' => ['column' => 'risk_score', 'dir' => 'desc']]);
        $risk_profile_repo = $app['risk_profile_rating.repository'];

        if(!empty($res[0]->risk_score) && !empty($user)) {
            $user_repo = new UserRepository($user);
            $rating_score = $risk_profile_repo->prepareUserRatingScore(
                $res[0]->risk_score,
                RiskProfileRating::RATING_SCORE_PARENT_CATEGORY,
                RiskProfileRating::RG_SECTION,
                $user_repo->getJurisdiction()
            );
        }

        $page = $paginator->getPage();
        $length = 50;
        $user_section = 'risk-score/';

        return $request->isXmlHttpRequest()
            ? $app->json($paginator->getPage(false))
            : $app['blade']->view()->make(
                'admin.user.risk-score',
                compact('app', 'page', 'columns', 'url', 'params', 'user', 'length', 'user_section',
                    'rating_score')
            )->render();
    }
    /**
     *
     * @param Application $app
     * @param Request $request
     */
    public function checkSimilarity(Application $app, Request $request) {

        $url = 'rg.check-similarity';

        $columns = [
            'scalar'   =>  'Similar to users'
        ];
        /*********************/
        $this->getParams($app, $request);
        $params   = $this->params;
        $query    = $this->query;
        $aFields = ['username','levenshtein_distance'];
        $bindings = [];
        foreach ($aFields as $field) {
            if (!empty($params[$field]) && $params[$field] != 'all') {
                $bindings[$field] = $params[$field];
                ${'where_' . $field} = $query[$field];
            }
        }
        $levenshtein_thold = intval($bindings['levenshtein_distance']) > 0 ? intval($bindings['levenshtein_distance']) : 0;
        $a = [];
        if(!empty($bindings['username'])) {
            $u = User::findByUsername($bindings['username']);
            $ud = cu($u->id);
            $similar = Phive('DBUserHandler')->getSimilarUsers($ud->data,$levenshtein_thold);
            $a[] = (object) $similar;
        }
        $res = collect($a);
        $paginator = new PaginationHelper($res, $request, ['length' => self::$pagLength, 'order' => ['column' => 'users.username', 'order' => 'ASC']]);
        return $this->sendToTemplate($app, $request,$sort, $columns, $url, $paginator, $params);

    }
    /**
     * Triggers related to AML (anti money laundering)
     * @param Application $app
     * @param Request $request
     * @return type
     */
    public function rgMonitoring(Application $app, Request $request)
    {
        $fraudController = new FraudController();
        return $fraudController->rgMonitoring($app, $request);
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function riskScoreReport(Application $app, Request $request)
    {
        $params_array = array_merge(array('forwarded' => RiskProfileRating::RG_SECTION), $request->query->all());
        $sub_request = Request::create($app["url_generator"]->generate('admin.admin.user-risk-score-report-special'), $request->getMethod(), $params_array, $request->cookies->all(), array(), $request->server->all());
        if ($request->getSession()) {
            $sub_request->setSession($request->getSession());
        }

        $response = $app->handle($sub_request, HttpKernelInterface::SUB_REQUEST, false);
        return $response;
    }

  /**
   * @param Application $app
   * @param Request $request
   * @return
   */
    public function interactionResultReport(Application $app, Request $request)
    {
        $number_of_months_to_show = 3;
        $view_data = [];

        $country = $request->query->get('country');
        $where_country = '';
        if(!empty($country) && $country != 'all') {
            $where_country = " AND interaction.country = :country ";
            $bindings['country'] = $request->query->get('country');
        }

        $month_date_string = $params['month-picker'] = $request->query->get('month-picker');
        if (!empty($month_date_string)) {
            $month_date = Carbon::parse($month_date_string);

            for ($i=1; $i<=$number_of_months_to_show; $i++) {
                $month_date->startOfMonth()->startOfDay()->subMonth();
                $bindings['month_start'] = $month_date->startOfMonth()->startOfDay()->toDateString();
                $bindings['month_end'] = $month_date->endOfMonth()->endOfDay()->toDateString();

                $sql = "
                    SELECT * FROM users_monthly_interaction_stats AS interaction
                    WHERE interaction.date BETWEEN :month_start AND :month_end
                    $where_country
                    GROUP BY interaction.user_id
                ";
                $res = DB::shsSelect("users_monthly_interaction_stats", $sql, $bindings);

                $classified_data = $this->classifyInteractionReports($res);
                $view_data[$month_date->format('F')] = $classified_data;
            }
        }

        return $app['blade']->view()
                ->make('admin.rg.interaction-result-report', compact('app', 'view_data', 'params'))
                ->render();
    }

    /**
     * @param array $monthly_data
     * @return array
     */
    private function classifyInteractionReports(array $monthly_data) {
        $classified_interactions_reports = [];

        foreach ($monthly_data as $monthly_row) {
            $actions = explode(' ', $monthly_row->actions);
            foreach ($actions as $action) {
                $classified_interactions_reports[$action]['action'][] = $monthly_row->user_id;
                if ($monthly_row->has_limit == 1)
                    $classified_interactions_reports[$action]['has_limit'][] = $monthly_row->user_id;

                if ($monthly_row->active == 1)
                    $classified_interactions_reports[$action]['active'][] = $monthly_row->user_id;
                else
                    $classified_interactions_reports[$action]['not_active'][] = $monthly_row->user_id;

                if (strpos($monthly_row->user_blocks, UserMonthlyInteractionReportCommand::SELF_LOCKED) !== false)
                    $classified_interactions_reports[$action]['self_locked'][] = $monthly_row->user_id;

                if (strpos($monthly_row->user_blocks, UserMonthlyInteractionReportCommand::SELF_EXCLUDED) !== false)
                    $classified_interactions_reports[$action]['self_excluded'][] = $monthly_row->user_id;

                if ($monthly_row->deposited <= -10)
                    $classified_interactions_reports[$action]['deposit_decrease'][] = $monthly_row->user_id;
                elseif ($monthly_row->deposited >= 10)
                    $classified_interactions_reports[$action]['deposit_increase'][] = $monthly_row->user_id;
                else
                    $classified_interactions_reports[$action]['deposit_same'][] = $monthly_row->user_id;

                if ($monthly_row->total_loss <= -10)
                    $classified_interactions_reports[$action]['loss_decrease'][] = $monthly_row->user_id;
                elseif ($monthly_row->total_loss >= 10)
                    $classified_interactions_reports[$action]['loss_increase'][] = $monthly_row->user_id;
                else
                    $classified_interactions_reports[$action]['loss_same'][] = $monthly_row->user_id;

                if ($monthly_row->time_spent <= -10)
                    $classified_interactions_reports[$action]['time_spent_decrease'][] = $monthly_row->user_id;
                elseif ($monthly_row->time_spent >= 10)
                    $classified_interactions_reports[$action]['time_spent_increase'][] = $monthly_row->user_id;
                else
                    $classified_interactions_reports[$action]['time_spent_same'][] = $monthly_row->user_id;
            }
        }

        return $classified_interactions_reports;
    }

    /**
     * Show all RG data from Risk_profile_rating_log
     *
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function grsScoreReport(Application $app, Request $request)
    {
        return RiskProfileRatingController::grsScoreReport($app, null, $request, 'rg');
    }
}
