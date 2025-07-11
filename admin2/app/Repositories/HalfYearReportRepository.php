<?php

namespace App\Repositories;

use App\Classes\BlockType;
use App\Helpers\DataFormatHelper;
use App\Helpers\DownloadHelper;
use App\Models\RegulatoryStats;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use ParseCsv\Csv as PCSV;
use Silex\Application;
use App\Extensions\Database\FManager as DB;

class HalfYearReportRepository
{
    const FIVE_YEARS_IN_HOURS = 43800;

    /** @var Application $app */
    private $app;

    /** @var Carbon $start_date */
    private $start_date;

    /** @var Carbon $end_date */
    private $end_date;

    /** @var string $year_and_month_sql */
    private $year_and_month_sql;

    /** @var string $between_sql */
    private $between_sql;

    /** @var string $common_filters */
    private $common_filters;

    /** @var string $country */
    private $country = 'SE';

    /** @var string $product */
    private $product = 'Casino';

    /** @var string $user_base_user_ids_sql */
    private $user_base_user_ids_sql;

    /** @var string $users_contacted_by_license_sql */
    private $users_contacted_by_license_sql;

    /** @var array $total_net_turnover */
    private $total_net_turnover = [];

    /**
     * @param array $users
     * @return array
     */
    private function groupByAgeSex($users)
    {
        /** [
         *      "age1' => [
         *          "male" => (int)count,
         *          "female" => (int)count
         *      ],
         *      "age2' => [ ... ],
         *      ...
         * ]
         */
        // Prefill all the possible combination with 0.
        $age_range = ['18-24', '25-44', '45-64', '>65'];
        $sexes = ['Male', 'Female'];
        $result = [];
        foreach($age_range as $range) {
            $result[$range] = [];
            foreach($sexes as $sex) {
                $result[$range][$sex] = 0;
            }
        }

        collect($users)
            ->groupBy(function ($user) {
                $user_age = Carbon::parse($user['dob'])->age;

                if ($user_age >= 18 && $user_age < 25) {
                    return "18-24";
                }
                if ($user_age >= 25 && $user_age < 45) {
                    return "25-44";
                }
                if ($user_age >= 45 && $user_age < 65) {
                    return "45-64";
                }
                if ($user_age >= 65) {
                    return ">65";
                }
                return "<18";
            })
            ->mapWithKeys(function ($users, $key) use (&$result) {
                $users = $users->groupBy('sex')->mapWithKeys(function ($sub_users, $sub_key) {
                    return [$sub_key => $sub_users->count()];
                });
                $result[$key] = $users->toArray();
                return [$key => $users];
            })
            ->toArray();
        // TOTALLY REMOVE USERS <18
        unset($result['<18']);

        return $result;
    }

    /**
     * @param $action
     * @return array
     */
    private function getActionLimits($action)
    {
        $str = explode(" Limits: ", $action['descr'])[1];
        $str = explode(",", $str);
        $month = (int)array_pop($str);
        $week = (int)array_pop($str);
        $day = (int)array_pop($str);
        return [$day, $week, $month];
    }

    /**
     * @param $user_limit
     * @param $type
     * @param $value
     * @param $found_users
     * @param $user_id
     * @return mixed
     */
    private function updateLimits($user_limit, $type, $value, &$found_users, $user_id)
    {

        // value exceeds the daily limit so we report and stop checking for this user
        if ($value > $user_limit[$type]['day']) {
            $found_users[$user_id] = 1;
            return $user_limit;
        }

        // week went by so we have to reset the progress
        if ($user_limit[$type]['active_days'] % 7 === 0) {
            $user_limit[$type]['progress']['week'] = 0;
        }

        // month went by so we have to reset the progress
        if ($user_limit[$type]['active_days'] % 30 === 0) {
            $user_limit[$type]['progress']['month'] = 0;
        }

        // record the progress for week and month
        $user_limit[$type]['progress']['week'] += $value;
        $user_limit[$type]['progress']['month'] += $value;

        // progress exceeds the weekly limit so we report and stop checking for this user
        if ($user_limit[$type]['progress']['week'] > $user_limit[$type]['week']) {
            $found_users[$user_id] = 1;
            return $user_limit;
        }

        // progress exceeds the monthly limit so we report and stop checking for this user
        if ($user_limit[$type]['progress']['month'] > $user_limit[$type]['month']) {
            $found_users[$user_id] = 1;
            return $user_limit;
        }

        // another day passed by
        $user_limit[$type]['active_days'] += 1;

        return $user_limit;
    }

    /**
     * @param array $arr
     * @param string $key
     * @return int
     */
    private function countPluckUnique($arr, $key)
    {
        return count(array_unique(array_pluck($arr, $key)));
    }

    /**
     * @return array
     */
    private function getActionsAndUds()
    {
        $limits = $this->getLimits();

        $actions = [];
        DB::loopNodes(function ($connection) use (&$actions, $limits) {
            $node_actions = $connection->select("
                SELECT actions.*
                FROM actions
                WHERE tag in $limits
                    AND created_at {$this->between_sql}
                    AND target IN ({$this->user_base_user_ids_sql})
            ");
            $actions = array_merge($actions, $node_actions);
        });

        $users_daily_stats = DB::shsSelect("users_daily_stats", "
            SELECT users_daily_stats.* 
            FROM users_daily_stats
            WHERE `date` {$this->between_sql}
              AND user_id IN ({$this->user_base_user_ids_sql}) 
        ");

        $users_sessions = [];
        DB::loopNodes(function ($connection) use (&$users_sessions, $limits) {
            $node_sessions = $connection->select("
                SELECT users_sessions.* 
                FROM users_sessions
                WHERE created_at {$this->between_sql}
                  AND user_id IN ({$this->user_base_user_ids_sql})
                    
            ");
            $users_sessions = array_merge($users_sessions, $node_sessions);
        });


        return [
            $this->prepareData($actions, 'created_at'),
            $this->prepareData($users_daily_stats, 'date'),
            $this->prepareData($users_sessions, 'created_at')
        ];
    }

    /**
     * @param bool $only_limits
     * @return array|string
     */
    private function getLimits($only_limits = false)
    {
        $limits = array_merge(
            phive('DBUserHandler/RgLimits')->getMoneyLimits(),
            phive('DBUserHandler/RgLimits')->time_types
        );

        if (!empty($only_limits)) {
            return $limits;
        }

        $list = [];
        foreach ($limits as $limit) {
            $list[] = "{$limit}-rgl-add";
            $list[] = "{$limit}-rgl-change";
            $list[] = "{$limit}-rgl-remove";
        }

        return DataFormatHelper::arrayToSql($list);
    }

    /**
     * @param $list
     * @param $date_key
     * @return array
     */
    private function prepareData($list, $date_key)
    {
        $list = array_map(function ($el) {
            return (array)$el;
        }, $list);

        $list = array_sort($list, function ($action) use ($date_key) {
            return $action[$date_key];
        });

        $mapped = [];
        foreach ($list as $action) {
            $action[$date_key] = new Carbon($action[$date_key]);
            $date = $action[$date_key]->format('Y-m-d');

            if (empty($mapped[$date])) {
                $mapped[$date] = [];
            }

            $mapped[$date][] = $action;
        }
        return $mapped;
    }

    /**
     * @param Application $app
     * @return $this
     */
    public function setApp($app)
    {
        DB::connection()->setFetchMode(\PDO::FETCH_ASSOC);

        $this->app = $app;

        return $this;
    }

    /**
     * @param $country
     * @return $this
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @param $country
     * @return $this
     */
    public function setProduct($product)
    {
        switch ($product):
            case 'Casino':
                $this->product = $product;
                $this->user_base_user_ids_sql = "
                    SELECT DISTINCT user_id
                    FROM users_daily_game_stats
                    WHERE (bets > 0 OR wins > 0 OR frb_wins > 0 OR rewards > 0)
                      AND date BETWEEN '{$this->start_date}' AND '{$this->end_date}'
                      AND country = '{$this->country}'                
                ";
                break;
            case 'Sport':
                $this->product = $product;
                $this->user_base_user_ids_sql = "
                    SELECT DISTINCT user_id
                    FROM users_daily_stats_sports
                    WHERE bets > 0
                      AND date BETWEEN '{$this->start_date}' AND '{$this->end_date}'
                      AND country = '{$this->country}'                
                ";
                break;
            default:
                $this->app['monolog']->addError("regulatory-stats: product not found.");
        endswitch;

        return $this;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     */
    public function setInterval($start, $end)
    {
        $this->start_date = $start;
        $this->end_date = $end;

        $this->year_and_month_sql = " AND year = {$this->start_date->year} AND month BETWEEN {$this->start_date->month} AND {$this->end_date->month} ";
        $this->between_sql = "
            BETWEEN '{$this->getStartDateTime()}' 
              AND '{$this->getEndDateTime()}'
        ";
    }

    /**
     * @param array $items
     * @param array $columns
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadCsv($items, $columns)
    {
        $records[] = $columns;

        foreach ($items as $item) {
            $records[] = array_values($item);
        }

        return DownloadHelper::streamAsCsv($this->app, $records, 'half-year-report');
    }

    /**
     * @return mixed
     */
    public function getHalfYearDropdown()
    {
        return RegulatoryStats::query()
            ->selectRaw("distinct concat(start_date, ':', end_date) as date_interval")
            ->where('jurisdiction', '=', 'SE')
            ->get()
            ->pluck('date_interval')
            ->toArray();
    }

    /**
     * @return string
     */
    public function getStartDate()
    {
        return $this->start_date->format('Y-m-d');
    }

    /**
     * @return string
     */
    public function getEndDate()
    {
        return $this->end_date->format('Y-m-d');
    }

    /**
     * @return string
     */
    public function getStartDateTime() {
        return $this->start_date->startOfDay()->toDateTimeString();
    }

    /**
     * @return string
     */
    public function getEndDateTime() {
        return $this->end_date->endOfDay()->toDateTimeString();
    }


    /**
     * We need to get data from "trigger_logs" only after the day we started sending automated email from the system
     * @return string
     */
    private function getStartDateForRG36() {
        $start_date_for_automated_emails = Carbon::parse('2019-06-10 17:30:00');
        $start_date = $this->start_date->lt($start_date_for_automated_emails) ? $start_date_for_automated_emails->toDateTimeString() : $this->getStartDateTime();
        return $start_date;
    }

    /**
     * SE - HALF YEAR: 1. Number of registered players
     *
     * @return array
     */
    public function numberOfRegisteredPlayers()
    {
        $sql = DB::shsSelect('users', "
            SELECT id, dob, sex FROM users 
            WHERE register_date < '{$this->getEndDateTime()}' 
                AND country = '{$this->country}'
                AND users.firstname != '' AND users.lastname != ''
        ");

        return $this->groupByAgeSex($sql);
    }

    /**
     * SE - HALF YEAR: 2. Number of players who have placed bets (both with real money or freespins)
     *
     * @return array
     */
    public function numberOfUsersWhoPlacedBets()
    {
        $sql = DB::shsSelect('users',  "
            SELECT id, sex, dob 
            FROM users
            WHERE id IN ({$this->user_base_user_ids_sql})
        ");

        return $this->groupByAgeSex($sql);
    }

    /**
     * SE - HALF YEAR: 3. Number of players who have respectively raised or lowered their limit in time or money respectively
     *
     * @return array
     */
    public function playersWhoRaisedLoweredLimits()
    {
        $sql = DB::shsSelect('actions', "
            SELECT target, tag FROM actions 
            WHERE tag in {$this->getLimits()}
                AND created_at {$this->between_sql}
                AND target IN ({$this->user_base_user_ids_sql})
            GROUP BY target, tag
        ");

        $money_limit = phive('DBUserHandler/RgLimits')->getMoneyLimits();
        $money_type = phive('DBUserHandler/RgLimits')->getAllActionTags($money_limit);
        $res = [];
        foreach ($sql as $el) {
            $key = ends_with($el['tag'], '-rgl-add') ? "raised " : "lowered ";
            $key .= in_array($el['tag'], $money_type) ? "money" : "time";
            $res[$key][] = $el;
        }

        $res['lowered time'] = $this->countPluckUnique($res['lowered time'], 'target');
        $res['lowered money'] = $this->countPluckUnique($res['lowered money'], 'target');
        $res['raised time'] = $this->countPluckUnique($res['raised time'], 'target');
        $res['raised money'] = $this->countPluckUnique($res['raised money'], 'target');

        return $res;
    }

    /**
     * SE - HALF YEAR: 4. Number of players who has reached their limit in time or money respectively
     *
     *  How this works is that we loop on each day from start date to end date.
     *  First make sure that the limits for the day are set or reset according to the actions.
     *  Second add the progress for all the limits.
     *  If the progress goes over a limit, stop any other checks for the user.
     *  Finally return the number of found users.
     *
     * @return array
     */
    public function playersWhoReachedLimit()
    {
        // TODO run this split foreach month? it takes ages...
        list($actions_list, $uds_list, $sessions_list) = $this->getActionsAndUds();
        list($current, $end) = [new Carbon($this->getStartDate()), new Carbon($this->getEndDate())];

        $found_users_time = $found_users_money = $users_limits = [];
        $reset = [
            'day' => 0,
            'week' => 0,
            'month' => 0,
            'active_days' => 0,
            'has_limit' => false,
            'progress' => [
                'day' => 0,
                'week' => 0,
                'month' => 0,
            ]
        ];

        $init_user_limits = array_fill_keys($this->getLimits(true), $reset);

        while ($current <= $end) {
            $sessions = $sessions_list[$current->format('Y-m-d')] ?? [];
            $actions = $actions_list[$current->format('Y-m-d')] ?? [];
            $uds = $uds_list[$current->format('Y-m-d')] ?? [];

            echo "Doing ". $current->format('Y-m-d') . " \n";

            foreach ($actions as $action) {
                $user_id = $action['target'];

                if (!empty($found_users_time[$user_id]) && !empty($found_users_money[$user_id])) {
                    continue;
                }

                list($tag, , $limit_action) = explode("-", $action['tag']);

                if (empty($users_limits[$user_id])) {
                    $users_limits[$user_id] = $init_user_limits;
                }

                if (in_array($limit_action, ['add', 'change'])) {
                    list($day, $week, $month) = $this->getActionLimits($action);

                    // login limits are expressed in hours so we convert it to seconds to track the progress easier
                    if ($tag === 'login') {
                        $day = DataFormatHelper::convertHoursToSeconds($day);
                        $week = DataFormatHelper::convertHoursToSeconds($week);
                        $month = DataFormatHelper::convertHoursToSeconds($month);
                    }

                    $users_limits[$user_id][$tag]['day'] = $day;
                    $users_limits[$user_id][$tag]['week'] = $week;
                    $users_limits[$user_id][$tag]['month'] = $month;
                    $users_limits[$user_id][$tag]['has_limit'] = true;
                } else {
                    $users_limits[$user_id][$tag] = $reset;
                }
            }

            // handle deposit, wager, loss
            foreach ($uds as $stats) {
                $user_id = $stats['user_id'];

                if (!empty($found_users_money[$user_id])) {
                    continue;
                }

                $user_limit = $users_limits[$user_id];

                if (!empty($user_limit[$type = 'deposit']['has_limit'])) {
                    $value = $stats['deposit'];
                    $users_limits[$user_id] = $this->updateLimits($user_limit, $type, $value, $found_users_money, $user_id);
                }

                if (!empty($user_limit[$type = 'wager']['has_limit'])) {
                    $value = $stats['bets'];
                    $users_limits[$user_id] = $this->updateLimits($user_limit, $type, $value, $found_users_money, $user_id);
                }

                if (!empty($user_limit[$type = 'loss']['has_limit'])) {
                    $value = $stats['gross'] > 0 ? $stats['gross'] : 0;
                    $users_limits[$user_id] = $this->updateLimits($user_limit, $type, $value, $found_users_money, $user_id);
                }
            }

            // handle login
            foreach ($sessions as $session) {
                $user_id = $session['user_id'];

                if (!empty($found_users_time[$user_id])) {
                    continue;
                }

                $user_limit = $users_limits[$user_id];

                if (!empty($user_limit[$type = 'login']['has_limit'])) {
                    /** @var Carbon $session_start */
                    $session_start = $session['created_at']; // this was already converted to Carbon during prepareData
                    $session_end = new Carbon(!empty($session['ended_at']) ? $session['ended_at'] : $session['updated_at']);
                    $value = $session_start->diffInSeconds($session_end);

                    $users_limits[$user_id] = $this->updateLimits($user_limit, $type, $value, $found_users_time, $user_id);
                }
            }

            $current = $current->addDay(1);
        }

        return [
            'time' => count(array_unique(array_keys($found_users_time))),
            'money' => count(array_unique(array_keys($found_users_money)))
        ];
    }

    /**
     * SE - HALF YEAR: 4. Number of players who has reached their limit in time or money respectively
     *
     *  How this works is that we loop on each day from start date to end date.
     *  First make sure that the limits for the day are set or reset according to the actions.
     *  Second add the progress for all the limits.
     *  If the progress goes over a limit, stop any other checks for the user.
     *  Finally return the number of found users.
     *
     * @return array
     */
    public function playersWhoReachedLimit2()
    {
        switch ($this->product):
            case 'Casino':
                $sql_time = "
                    SELECT DISTINCT target AS user_id
                    FROM actions
                    WHERE target IN ({$this->user_base_user_ids_sql})
                      AND created_at {$this->between_sql} 
                      AND tag IN ('reached-login-day', 'reached-login-week', 'reached-login-month', 'reached-timeout-na')                
                ";
                $number_of_users_reached_time_limits = DB::shsSelect('actions', $sql_time);
                $sql_money = "
                    SELECT DISTINCT target AS user_id
                    FROM actions
                    WHERE target IN ({$this->user_base_user_ids_sql})
                      AND created_at {$this->between_sql} 
                      AND tag IN (
                        'reached-loss-day', 'reached-loss-week', 'reached-loss-month',
                        'reached-wager-day', 'reached-wager-week', 'reached-wager-month'
                        'reached-deposit-day', 'reached-deposit-week', 'reached-deposit-month'
                      )
                ";
                $number_of_users_reached_money_limit = DB::shsSelect('actions', $sql_money);

                break;
            case 'Sport':
                $number_of_users_reached_time_limits = 0;
                $sql_money = "
                    SELECT DISTINCT target AS user_id
                    FROM actions
                    WHERE target IN ({$this->user_base_user_ids_sql})
                      AND created_at {$this->between_sql} 
                      AND tag IN (
                        'reached-wager-sportsbook-day', 'reached-wager-sportsbook-week', 'reached-wager-sportsbook-month'
                        'reached-loss-sportsbook-day', 'reached-loss-sportsbook-week', 'reached-loss-sportsbook-month'
                      )
                ";
                $number_of_users_reached_money_limit = DB::shsSelect('actions', $sql_money);
                break;
            default:
                $this->app['monolog']->addError("regulatory-stats: product not found for players reached their limit.");
        endswitch;

        return [
            'time' => $this->countPluckUnique($number_of_users_reached_time_limits, 'user_id'),
            'money' => $this->countPluckUnique($number_of_users_reached_money_limit, 'user_id')
        ];
    }

    /**
     * SE - HALF YEAR: 5. Number of players who completed the self-assessment test
     *
     * @return int
     */
    public function playersWhoCompletedSelfAssessment() {
        // TODO properly parse the CSV from GamTest - for now inputting the value manually from the received report (01/01/2019-30/06/2019)
        return 0; // currently 730 - to insert manually
    }

    /**
     * SE - HALF YEAR: 6. Number of persons who has contacted the license holder about gambling issues
     *
     * @return int
     */
    public function contactedRegardingGamblingIssue()
    {
        // TODO "limits" seems the only tag related to RG inside user_comments,
        //  check if we need to add "rg-risk-group" (but seems an automated message) or "phone_contact" (but seems generic and not related to RG only)
        $sql = DB::shsSelect('users_comments', "
                SELECT user_id 
                FROM users_comments 
                WHERE tag = 'limits' 
                  AND created_at {$this->between_sql}
                  AND user_id IN ({$this->user_base_user_ids_sql})
                GROUP BY user_id 
        ");

        return $this->countPluckUnique($sql, 'user_id');
    }

    /**
     * SE - HALF YEAR: 7. Number of accounts closed by the license holder or the player respectively
     * We only grab users blocked for some specific reason ($reasons) and that are still inactive.
     * Ex. a user was blocked and then re-activated.
     *
     * @return array
     */
    public function numberOfClosedAccounts()
    {
        $reasons = DataFormatHelper::arrayToSql(
            [BlockType::ADMIN_LOCKED,BlockType::USER_LOCKED_HIMSELF, BlockType::TOO_SIMILAR_ACCOUNT, BlockType::TEMPORARY_ACCOUNT_BLOCK, BlockType::FAILED_PEP_SL_CHECK, BlockType::EXTERNAL_SELF_EXCLUSION]
            , false
        );
        $sql = DB::shsSelect('users_blocked', "
            SELECT user_id, users_blocked.reason 
            FROM users_blocked 
                LEFT JOIN users ON users.id = users_blocked.user_id
            WHERE date {$this->between_sql}
              AND users_blocked.reason IN {$reasons}
              AND users.active = 0  
              AND user_id IN ({$this->user_base_user_ids_sql})    
            GROUP BY user_id
        ");

        $res = [];
        foreach ($sql as $el) {
            if (in_array($el['reason'], [BlockType::USER_LOCKED_HIMSELF, BlockType::EXTERNAL_SELF_EXCLUSION])) {
                $res['terminated by player'][] = $el;
            } else {
                $res['terminated by licensee'][] = $el;
            }
        }

        $res['terminated by player'] = $this->countPluckUnique($res['terminated by player'], 'user_id');
        $res['terminated by licensee'] = $this->countPluckUnique($res['terminated by licensee'], 'user_id');

        return $res;
    }

    /**
     * Helper method used in "SE - HALF YEAR: 8" to get the number of excluded accounts for 24h
     *
     * @return array
     */
    private function shutDown24h()
    {
        // 1) Spelpaus 24 - Lock game categories for 24 hours
        $spelpaus_24 = DB::shsSelect('actions', "
            SELECT actions.target, users.dob, users.sex 
            FROM actions 
                LEFT JOIN users on users.id = actions.target
            WHERE tag LIKE '%lockgamescat%' AND (descr LIKE '%Limits: 1,%' OR descr LIKE '%Limits: 1')
               AND target IN ({$this->user_base_user_ids_sql})
            GROUP BY target
        ");

        // 2) Lock account X days (where period is 1 day)
        $lock_1_day = DB::shsSelect('actions', "
            SELECT actions.target, users.dob, users.sex 
            FROM actions 
                LEFT JOIN users on users.id = actions.target
            WHERE descr LIKE '% set lock-hours to 24'
               AND target IN ({$this->user_base_user_ids_sql})            
            GROUP BY target
        ");

        // external self exclusions should not count into this question
//        // 3) External 1 day exclusion via Spelpaus
//        $external_1_day = DB::shsSelect('actions', "
//            SELECT actions.target, actions.descr, users.dob, users.sex FROM actions
//            LEFT JOIN users on users.id = actions.target
//            WHERE tag = 'profile-unlock' AND descr LIKE 'Self-exclusion on external%'
//                $this->common_filters
//            GROUP BY target
//        ");
//        // Description example: "Self-exclusion on external register since 2019-10-21 14:33:01 ended on 2019-10-21 14:51:19."
//        $external_1_day = array_filter($external_1_day, function($action) {
//            $action['descr'] = str_replace(["Self-exclusion on external register since ", "."], "", $action['descr']);
//
//            list($start, $end) = explode(" ended on ", $action['descr']);
//            return Carbon::parse($start)->startOfDay()->diffInDays(Carbon::parse($end)->startOfDay()) == 1;
//        });

        return array_merge($spelpaus_24, $lock_1_day);
    }

    /**
     * Helper method used in "SE - HALF YEAR: 8" to get the number of excluded accounts for certain time period
     *
     * @return array
     */
    public function certainPeriodShutdown()
    {
        //1) Lock account for X days (where period is 2 days to 5 years)
        $lock_x_days = DB::shsSelect('actions', "
            SELECT actions.target, actions.descr, users.dob, users.sex 
            FROM actions 
                LEFT JOIN users on users.id = actions.target
            WHERE descr NOT LIKE '% set lock-hours to 24' AND descr LIKE '% set lock-hours to%'
              AND target IN ({$this->user_base_user_ids_sql})
        ");
        $lock_x_days = array_filter($lock_x_days, function($action) {
            $action['descr'] = intval(str_after($action['descr'], 'set lock-hours to '));

            return $action['descr'] >= 48 && $action['descr'] <= self::FIVE_YEARS_IN_HOURS;
        });

        //2) Self exclusion set periods (5m, 1y, 2y, 3y, 5y)
        $self_exclusion = DB::shsSelect('actions', "
            SELECT actions.target, actions.descr, actions.created_at, users.dob, users.sex 
            FROM actions 
                LEFT JOIN users on users.id = actions.target
            WHERE tag = 'unexclude-date'
              AND target IN ({$this->user_base_user_ids_sql})
        ");
        $self_exclusion = array_filter($self_exclusion, function($action) {
            $end = Carbon::parse(str_after($action['descr'], 'set unexclude-date to '));
            $start = Carbon::parse($action['created_at']);
            return $end->diffInDays($start) >= 2 && $end->diffInYears($start) <= 5;
        });

        // external exclusions should not count into this question
//        //3) External definite exclusion period via Spelapus (1m, 3m, 6m)
//        $external_exclusion = DB::shsSelect('actions', "
//            SELECT actions.target, actions.descr, users.dob, users.sex FROM actions
//            LEFT JOIN users on users.id = actions.target
//            WHERE tag = 'profile-unlock' AND descr LIKE 'Self-exclusion on external%'
//                $this->common_filters
//            GROUP BY target
//        ");
//        // Description example: "Self-exclusion on external register since 2019-10-21 14:33:01 ended on 2019-10-21 14:51:19."
//        $external_exclusion = array_filter($external_exclusion, function($action) {
//            $action['descr'] = str_replace(["Self-exclusion on external register since ", "."], "", $action['descr']);
//
//            list($start, $end) = explode(" ended on ", $action['descr']);
//
//            return Carbon::parse($start)->startOfDay()->diffInDays(Carbon::parse($end)->startOfDay()) >= 2;
//        });

        return array_merge($lock_x_days, $self_exclusion);
    }

    /**
     * Helper method used in "SE - HALF YEAR: 8" to get the number of excluded accounts for continuously respectively
     *
     * @return array
     */
    public function indefiniteShutdown()
    {
        // 1) Lock account for X days (where period is >5 years)
        $lock_x_days = DB::shsSelect('actions', "
            SELECT actions.target, actions.descr, users.dob, users.sex 
            FROM actions 
                LEFT JOIN users on users.id = actions.target
            WHERE descr NOT LIKE '% set lock-hours to 24' AND descr LIKE '% set lock-hours to%'
              AND target IN ({$this->user_base_user_ids_sql})
        ");
        $lock_x_days = array_filter($lock_x_days, function($action) {
            return intval(str_after($action['descr'], 'set lock-hours to ')) > self::FIVE_YEARS_IN_HOURS;
        });

        // 2) External indefinite exclusion period via Spelpaus
        $external_exclusion = DB::shsSelect('actions', "
            SELECT actions.target, actions.tag, users.dob, users.sex 
            FROM actions
                LEFT JOIN users on users.id = actions.target
            WHERE tag in ('external-excluded', 'external-unexcluded-date')
              AND target IN ({$this->user_base_user_ids_sql})
            GROUP BY target, tag
        ");
        $external_exclusion = collect($external_exclusion)
            ->groupBy('target')
            ->filter(function($actions) {
                // if only 1 action is registered then user only excluded but did not return
                // if 2 actions registered then user excluded and also unexcluded
                return count($actions) === 1;
            })
            ->flatten(1)
            ->toArray();

        return array_merge($lock_x_days, $external_exclusion);
    }

    /**
     * SE - HALF YEAR: 8. Number of excluded accounts for 24h, certain time period or continuously respectively
     * - profile-lock - common action for lock/unlock
     * - super-blocked - superblocked user
     *
     * TODO
     *  - MID priority: Do we need to exclude this 2 action?? it's UNLOCK not LOCK...
     *      - "ActionRepository::logAction($this->user, "Early revoke on locked account. Previous unlock date: $previous_date.", "profile-lock", true);"
     *      - "ActionRepository::logAction($this->user, "Super block lifted.", "profile-lock", true);"
     *  - LOW priority: check if any action under "block" needs to be reported (if so we need to do a join with users_blocked to grab only those reasons)
     * @return array
     */
    public function excludedAccounts()
    {
        $this->common_filters = "
            AND created_at {$this->between_sql}
            AND users.country = '{$this->country}'
            AND users.firstname != '' AND users.lastname != ''
        ";
        return [
            '24 hour shutdowns' => $this->groupByAgeSex($this->shutDown24h()),
            'certain period shutdowns' => $this->groupByAgeSex($this->certainPeriodShutdown()),
            'until further notice shutdowns' => $this->groupByAgeSex($this->indefiniteShutdown()),
        ];
    }

    /**
     * SE - HALF YEAR: 9. Number of players who a has been contacted by license holder by suspected or confirmed gambling issues
     *
     * @return int
     */
    public function suspectedOrConfirmedGamblingIssue()
    {
        // TODO can we store the data from this (adding max(created_at)) to avoid doing 2 subqueries on 10 and 11?
        $sql = DB::shsSelect('actions', $this->users_contacted_by_license_sql = "
            SELECT target AS user_id, max(actions.created_at) AS date
            FROM actions 
            WHERE tag IN ('call-to-user', 'email-to-user', 'force_deposit_limit', 'force_self_assessment_test')
              AND created_at {$this->between_sql}
              AND target IN ({$this->user_base_user_ids_sql})
            GROUP BY target
            UNION
            SELECT user_id, max(triggers_log.created_at) AS date
            FROM triggers_log
            WHERE (trigger_name = 'RG36' OR trigger_name = 'RG21')
              AND created_at {$this->between_sql}
              AND user_id IN ({$this->user_base_user_ids_sql})
            GROUP BY user_id
        ");

        return $this->countPluckUnique($sql, 'user_id'); // on actions we get "target as user_id"
    }

    /**
     * SE - HALF YEAR: 10. Number of the contacted players according to point 9 who reduced gambling and how much the gambling was reduced in average in percent
     *
     * @return array
     */
    public function gamblingIssueReduced()
    {
        // users who have been called
        $sql = "
            SELECT target as user_id, max(actions.created_at) as date 
            FROM actions
            WHERE tag IN ('call-to-user', 'email-to-user', 'force_deposit_limit', 'force_self_assessment_test')
                AND created_at {$this->between_sql}
                AND target IN ({$this->user_base_user_ids_sql})
            GROUP BY target
            UNION
            SELECT user_id, max(triggers_log.created_at) as date 
            FROM triggers_log
            WHERE (trigger_name = 'RG36' OR trigger_name = 'RG21')
                AND created_at {$this->between_sql}
                AND user_id IN ({$this->user_base_user_ids_sql})
            GROUP BY user_id
        ";

        switch ($this->product):
            case 'Casino':
                $sql = "
                    SELECT sum(uds.bets) as bets, uds.user_id, 'before' as period from users_daily_stats as uds
                    LEFT JOIN ($sql) as called_users on called_users.user_id = uds.user_id
                    WHERE uds.date BETWEEN '{$this->getStartDate()}' AND called_users.date
                    GROUP BY uds.user_id
                    UNION ALL 
                    SELECT sum(uds.bets) as bets, uds.user_id, 'after' as period from users_daily_stats as uds
                    LEFT JOIN ($sql) as called_users on called_users.user_id = uds.user_id
                    WHERE uds.date BETWEEN called_users.date AND '{$this->getEndDate()}' 
                    GROUP BY uds.user_id
                ";
                break;
            case 'Sport':
                $sql = "
                    SELECT sum(uds.bets) as bets, uds.user_id, 'before' as period from users_daily_stats_sports as uds
                    LEFT JOIN ($sql) as called_users on called_users.user_id = uds.user_id
                    WHERE uds.date BETWEEN '{$this->getStartDate()}' AND called_users.date
                    GROUP BY uds.user_id
                    UNION ALL 
                    SELECT sum(uds.bets) as bets, uds.user_id, 'after' as period from users_daily_stats_sports as uds
                    LEFT JOIN ($sql) as called_users on called_users.user_id = uds.user_id
                    WHERE uds.date BETWEEN called_users.date AND '{$this->getEndDate()}' 
                    GROUP BY uds.user_id
                ";
                break;
            default:
                $this->app['monolog']->addError("regulatory-stats: product not found for total net turnover.");
        endswitch;

        /** @var Collection $sql */
        $sql = collect(DB::shsSelect('users_daily_stats', $sql))
            ->groupBy('user_id')
            ->mapWithKeys(function ($user, $key) {
                /** @var Collection $user */
                if ($user[0]['period'] == 'before') {
                    list($before, $after) = $user->toArray();
                } else {
                    list($after, $before) = $user->toArray();
                }

                $data = [
                    'user_id' => $before['user_id'],
                    'reduced' => false
                ];

                if (empty($after)) {
                    return $data;
                }

                $data['reduced'] = $before['bets'] > $after['bets'];
                $data['total_bets'] = $before['bets'] + $after['bets'];
                $data['reduced_amount'] = $after['bets'] - $before['bets'];
                $data['reduced_percentage'] = 100 * abs($data['reduced_amount']) / $data['total_bets'];

                return [$key => $data];
            });

        $reduced = $sql->values()->filter(function ($el) {
            return $el['reduced'] === true;
        });

        return [
            // if you change the strings for subcategory here, change them on HalfYearReportCommand too
            'number of players who reduced gambling' => $reduced->count(),
            'how much the gambling was reduced in average in percent' => round($reduced->average('reduced_percentage'),2)
        ];
    }

    /**
     * SE - HALF YEAR: 11. Number of the contacted players according to point 9 who chose to exclude themselves from gambling
     *
     * @return int
     */
    public function gamblingIssueSelfExcluded()
    {
        // users who have been called
        $sql = "
            SELECT target as user_id, max(created_at) as contacted_time 
            FROM actions
            WHERE tag IN ('call-to-user', 'email-to-user', 'force_deposit_limit', 'force_self_assessment_test')
                AND created_at {$this->between_sql}
                AND target IN ($this->user_base_user_ids_sql)
            GROUP BY target
            UNION
            SELECT user_id, max(triggers_log.created_at) as contacted_time 
            FROM triggers_log                
            WHERE (trigger_name = 'RG36' OR trigger_name = 'RG21')
                AND created_at {$this->between_sql}
                AND user_id IN ({$this->user_base_user_ids_sql})
            GROUP BY user_id
        ";

        // self excluded users after they've been called
        $sql = "
            SELECT target FROM actions 
            RIGHT JOIN ($sql) AS called_users 
              ON called_users.user_id = actions.target AND called_users.contacted_time < actions.created_at
            WHERE tag = 'profile-lock' 
            AND created_at {$this->between_sql}
            GROUP BY target
        ";

        $sql = DB::shsSelect('actions', $sql);

        return $this->countPluckUnique($sql, 'target');
    }

    // always return all + the requested percentage
    private function getTotalNetTurnover($percentage = 5) {

        if(!empty($this->total_net_turnover)) {
            $full_data = $this->total_net_turnover;
        } else {
            switch ($this->product):
                case 'Casino':
                    $categories = DataFormatHelper::arrayToSql([
                        LiabilityRepository::CAT_BOS_BUYIN_34, LiabilityRepository::CAT_BOS_PRIZES_38,
                        LiabilityRepository::CAT_BOS_HOUSE_RAKE_52, LiabilityRepository::CAT_BOS_REBUY_54, LiabilityRepository::CAT_BOS_CANCEL_BUYIN_61, LiabilityRepository::CAT_BOS_CANCEL_HOUSE_FEE_63,
                        LiabilityRepository::CAT_BOS_CANCEL_REBUY_64, LiabilityRepository::CAT_BOS_CANCEL_PAYBACK_65, LiabilityRepository::CAT_REWARDS, LiabilityRepository::CAT_JACKPOT_WIN_12,
                        LiabilityRepository::CAT_FRB_WINS, LiabilityRepository::CAT_JP_WINS
                    ], false);

                    $main_cat_bets = LiabilityRepository::CAT_BETS;
                    $main_cat_wins = LiabilityRepository::CAT_WINS;
                    $main_cat_sql = "
                        (main_cat IN {$categories} 
                        OR (main_cat = {$main_cat_bets} AND sub_cat != 'sportradar') 
                        OR (main_cat = {$main_cat_wins} AND sub_cat != 'sportradar')) 
                    ";
                    break;
                case 'Sport':
                    $main_cat_bets = LiabilityRepository::CAT_BETS;
                    $main_cat_wins = LiabilityRepository::CAT_WINS;
                    $main_cat_voids = LiabilityRepository::CAT_SPORTSBOOK_VOIDS;
                    $main_cat_sql = "
                        (main_cat = {$main_cat_voids}
                        OR (main_cat = {$main_cat_bets} AND sub_cat = 'sportradar') 
                        OR (main_cat = {$main_cat_wins} AND sub_cat = 'sportradar')) 
                    ";
                    break;
                default:
                    $this->app['monolog']->addError("regulatory-stats: product not found for total net turnover.");
            endswitch;

            // we multiply net_turnover by -1 to get the positive value we earned. (liability is based on user prospective)
            $full_data = DB::shsSelect("users_monthly_liability", "
                SELECT 
                    user_id, (sum(amount)*-1) as net_turnover
                FROM 
                    users_monthly_liability
                WHERE 
                    {$main_cat_sql}
                    AND source = 0
                    {$this->year_and_month_sql}
                    AND country = '{$this->country}'
                GROUP BY user_id
                ORDER BY (sum(amount)*-1) DESC
            ");

            usort($full_data, function($a, $b) { return $b['net_turnover'] - $a['net_turnover']; });
            $this->total_net_turnover = $full_data;
        }

        $top_x_percent = (int)round(count($full_data) * $percentage / 100);
        $top_x_percent = array_slice($full_data, 0, $top_x_percent);

        return [collect($full_data), collect($top_x_percent)];
    }

    /**
     * SE - HALF YEAR: 12. Share of the total net sales which is from the top 5% ranked players according to net sales
     *
     * @return float
     */
    public function sharesFromTop5Percent()
    {
        list($full_data, $top_five_percent) = $this->getTotalNetTurnover(5);

        // out of the total gross how much do the five percent own
        $shares = $top_five_percent->sum('net_turnover') * 100 / $full_data->sum('net_turnover');

        return round($shares, 2);
    }

    /**
     * SE - HALF YEAR: 13. Net sales, in average and median, for players according to point 12
     *
     * @return array
     */
    public function netSalesAverageMedian()
    {
        list($full_data, $top_five_percent) = $this->getTotalNetTurnover(5);

        return [
            'average' => DataFormatHelper::nf($top_five_percent->average('net_turnover')),
            'median' => DataFormatHelper::nf($top_five_percent->median('net_turnover'))
        ];
    }

    /**
     * SE - HALF YEAR: 14. Share of point 12 players who has been contacted by license holder
     *
     * @return int
     */
    public function topPlayersContacted()
    {
        list($full_data, $top_five_percent) = $this->getTotalNetTurnover(5);
        $user_ids = array_pluck($top_five_percent, 'user_id');

        $contacted_users = [];
        foreach (array_chunk($user_ids, 1000) as $ids) {
            $users = DataFormatHelper::arrayToSql($ids, false);

            $sql = "
                SELECT target AS user_id, max(actions.created_at) AS date
                FROM actions 
                WHERE tag IN ('call-to-user', 'email-to-user', 'force_deposit_limit', 'force_self_assessment_test')
                  AND created_at {$this->between_sql}
                  AND target IN {$users}
                GROUP BY target
                UNION
                SELECT user_id, max(triggers_log.created_at) AS date
                FROM triggers_log
                WHERE (trigger_name = 'RG36' OR trigger_name = 'RG21')
                  AND created_at {$this->between_sql}
                  AND user_id IN {$users}
                GROUP BY user_id
            ";

            $contacted_users = array_merge($contacted_users, DB::shsSelect('actions', $sql));
        }

        return round((($this->countPluckUnique($contacted_users, 'user_id') / count($user_ids)) * 100), 2);
    }

    /**
     * This function provides the raw data for the finance team to calculate NGR per player
     * and saves it into storage and to the reports folder.
     *
     * @return array|float|int|object|object[]
     */
    public function NGRPerPlayerReport()
    {
        $query = "
        SELECT user_id,
               year,
               month,
               uml.country,
               uml.currency,
               CASE
                   WHEN main_cat = 1
                       THEN 'deposits'
                   WHEN main_cat = 2
                       THEN 'withdrawals'
                   WHEN main_cat = 4
                       THEN 'bet'
                   WHEN main_cat = 5
                       THEN 'win'
                   WHEN main_cat = 26
                       THEN 'frb_win'
                   WHEN main_cat = 14
                       THEN 'rewards'
                   WHEN main_cat = 6
                       THEN 'Battle of Slots Buying'
                   WHEN main_cat = 7
                       THEN 'Battle of Slots Prizes'
                   WHEN main_cat = 8
                       THEN 'Battle of Slots Rake 52'
                   WHEN main_cat = 9
                       THEN 'Battle of Slots Rebuy'
                   WHEN main_cat = 10
                       THEN 'Battle of Slots Cancel Buying'
                   WHEN main_cat = 11
                       THEN 'Battle of Slots Cancel House Fee'
                   WHEN main_cat = 12
                       THEN 'Battle of Slots Rebuy'
                   WHEN main_cat = 13
                       THEN 'Battle of Slots Payback'
                   WHEN main_cat = 15
                       THEN 'Manual adjustments (IN)'
                   WHEN main_cat = 17
                       THEN 'Normal refund (IN)' 
                   WHEN main_cat = 30
                        THEN 'Voided Sportsbook Bets'     
                   END AS type,
               CASE
                   WHEN main_cat = 4
                       THEN ctype.name
                   WHEN main_cat = 5
                       THEN ctype.name
                   WHEN main_cat = 26
                       THEN ctype.name
                   WHEN main_cat = 14
                       THEN ctype.name
                   WHEN main_cat = 4
                       THEN sub_cat
                   WHEN main_cat = 5
                       THEN sub_cat
                   WHEN main_cat = 15 AND sub_cat = 3
                       THEN 'Deposit'
                   WHEN main_cat = 17 AND sub_cat = 'Withdrawal Refund'
                       THEN 'Withdrawal Refund'
                   END AS sub_type,
               sub_cat,
               amount
        FROM users_monthly_liability uml
                 LEFT JOIN transactions_types_tmp AS ctype ON ctype.type = uml.sub_cat
        WHERE source = 0
          {$this->year_and_month_sql}
          AND country = '{$this->country}'
          AND ((main_cat IN (1, 2, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 26, 30))
            OR (main_cat = 15 AND sub_cat = 3)
            OR (main_cat = 17 AND sub_cat = 'Withdrawal Refund'));
        ";
        $result = DB::getMasterConnection()->select($query);
        $this->saveCsv($result, "SGA_NGR_Per_Player_Report_{$this->product}_{$this->start_date}-{$this->end_date}_Finance", true);

        return $result;
    }

    public function saveCsv($data, $filename = 'no_file_name_supplied', $verbose = false, $folder = null)
    {
        if (empty($data)) {
            echo "No data supplied\n";
            return false;
        }

        $storage = getenv('STORAGE_PATH') . "/reports/";
        $csv = new PCSV();
        $data = json_decode(json_encode($data), true);
        $csv->data = $data;
        $csv->heading = true;
        $csv->titles = array_keys($data[0]);
        $storage .= $folder;
        mkdir($storage);

        if ($verbose) {
            echo "---------------------------\n";
            echo "Saving: {$storage}/{$filename}" . "\n";
            echo "---------------------------\n";
        }
        return $csv->save($storage . "/{$filename}.csv");
    }

}

