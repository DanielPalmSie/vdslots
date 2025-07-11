<?php
/**
 * Created by PhpStorm.
 * User: joel
 * Date: 29/05/17
 * Time: 14:59
 */

namespace App\Commands;

use App\Models\Config;
use App\Models\MessagingCampaign;
use App\Models\MessagingCampaignTemplates;
use App\Models\NamedSearch;
use App\Models\Segment;
use App\Repositories\MessagingRepository;
use Ivoba\Silex\Command\Command;
use Silex\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Extensions\Database\FManager as DB;
use Carbon\Carbon;
use App\Classes\Warehouse;

class NightlyCommand extends Command
{
    /** @var Application $app */
    private $app;
    private $output;
    private $tmp_segment_id;

    protected function configure()
    {
        $this->setName("nightly")
            ->addOption('only', 'o', InputOption::VALUE_OPTIONAL,
                'Run only one job. In: [updateSegments, updateNamedSearches, sendWiraya, pushToWarehouse, retryWarehousePush]', false)
            ->addOption('date', null, InputOption::VALUE_OPTIONAL,
                'Run pushToWarehouse for specific date [YYYY-MM-DD] or date range [YYYY-MM-DD,YYYY-MM-DD]')
            ->addOption('product', null, InputOption::VALUE_OPTIONAL,
                'Run pushToWarehouse for specific product [c] or multiple products [p,s,b], 
                note that you cannot combine c with other products as the data is stored in different tables')
            ->addUsage('--date="[2024-03-02]" --product="[c]"')
            ->addUsage('--date="[2024-03-01,2024-03-05]" --product="[p,s,b]"')
            ->setDescription("Nightly Jobs to run at 04:00");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->app = $this->getSilexApplication();

        $date_option = $input->getOption('date') ?? null;
        $product_option = $input->getOption('product') ?? null;

        $dates = [];
        if ($date_option) {
            $date_string = trim($date_option, '[]');
            if (strpos($date_string, ',') !== false) {
                list($startDate, $endDate) = array_map('trim', explode(',', $date_string));
                $start = Carbon::createFromFormat('Y-m-d', $startDate);
                $end = Carbon::createFromFormat('Y-m-d', $endDate);
                
                for ($date = $start; $date->lte($end); $date->addDay()) {
                    $dates[] = $date->format('Y-m-d');
                }
            } else {
                $dates[] = $date_string;
            }
        }

        $products = [];
        if ($product_option) {
            $product_string = trim($product_option, '[]');
            $products = strpos($product_string, ',') !== false 
                ? array_map('trim', explode(',', $product_string))
                : [$product_string];
        }     

        $this->output = $output;
        if (!empty($action = $input->getOption('only')) && $action == 'pushToWarehouse') {

            $this->log($action, [$dates, $products]);
            return 0;
        }
        else if (!empty($action)) {
            $this->log($action);
            return 0;
        }

        $this->log('updateNamedSearches');
        $this->log('updateSegments');
        $this->log('sendWiraya');
        $this->log('pushToWarehouse', [$dates, $products]);

        return 0;
    }

    private function log($method, $args = [])
    {
        $date = Carbon::now();
        $this->app['monolog']->addError("Command: nightly - Started {$method} for {$date}");

        try {
            call_user_func([$this, $method], $args);
        } catch (\Exception $e) {
            $this->app['monolog']->addError("Command: nightly - Exception on method {$method} for {$date}", [$e]);
        }

        $date = Carbon::now();

        $this->app['monolog']->addError("Command: nightly - Ended {$method} for {$date}");
    }

/**
 * Sends data to our own data warehouse.
 * 
 * @param array $args Optional arguments [dates, products]
 * @param bool $isRetry Whether this is a retry operation
 * @param array $specificTables Specific tables to push (for retry operations)
 */
private function pushToWarehouse($args = [], $isRetry = false, $specificTables = [])
{
    $wh = new Warehouse($this->app, 600);
    
    // Determine dates - for retry use today, otherwise use provided dates or yesterday
    if ($isRetry) {
        $dates = [Carbon::now()->subDays(1)->format('Y-m-d')];
    } else {
        $dates = !empty($args[0]) ? $args[0] : [Carbon::now()->subDays(1)->format('Y-m-d')];
    }
    
    $products = !empty($args[1]) ? array_map('strtoupper', $args[1]) : [];
    
    // Build date clauses
    $dateClause = count($dates) === 1 
        ? "`date` = '{$dates[0]}'"
        : "`date` BETWEEN '{$dates[0]}' AND '{$dates[count($dates)-1]}'";
    
    $userDateClause = count($dates) === 1 
        ? "EXISTS (SELECT 1 FROM users_settings WHERE users_settings.user_id = users.id AND users_settings.setting = 'registration_end_date' AND users_settings.created_at BETWEEN '{$dates[0]} 00:00:00' AND '{$dates[0]} 23:59:59')"
        : "EXISTS (SELECT 1 FROM users_settings WHERE users_settings.user_id = users.id AND users_settings.setting = 'registration_end_date' AND users_settings.created_at BETWEEN '{$dates[0]} 00:00:00' AND '{$dates[count($dates)-1]} 23:59:59')";
    
    // Handle product-specific pushes
    if (!empty($products)) {
        if (in_array('C', $products)) {
            $wh->pushData('users_daily_stats', $dateClause, [], true, [], true);
            return;
        }
        
        $wh->pushData('users_daily_stats_sports', $dateClause, [], true, $products, true);
        return;
    }
    
    // If specific tables are provided (for retry), only push those
    if (!empty($specificTables)) {
        foreach ($specificTables as $tableName) {
            $this->pushTableToWarehouse($wh, $tableName, $dateClause, $userDateClause);
        }
        return;
    }
    
    // Normal flow - push all tables
    if (!$isRetry) {
        phive()->miscCache('is-warehouse-normal-flow', true);
    }
    
    $this->pushTableToWarehouse($wh, 'users', $userDateClause, $userDateClause);
    $this->pushTableToWarehouse($wh, 'users_daily_stats', $dateClause, $userDateClause);
    $this->pushTableToWarehouse($wh, 'users_daily_stats_sports', $dateClause, $userDateClause);
    
    if (!$isRetry) {
        phive()->delMiscCache('is-warehouse-normal-flow');
    }
}

/**
 * Push a specific table to the warehouse
 * 
 * @param Warehouse $wh Warehouse instance
 * @param string $tableName Table name to push
 * @param string $dateClause SQL date clause for filtering
 * @param string $userDateClause SQL user date clause for filtering
 */
private function pushTableToWarehouse(Warehouse $wh, string $tableName, string $dateClause, string $userDateClause)
{
    switch ($tableName) {
        case 'users':
            $wh->pushData('users', $userDateClause, ['country', 'newsletter', 'bonus_code', 'register_date', 'currency', 'id'], true, [], true);
            break;
            
        case 'users_daily_stats':
            $wh->pushData('users_daily_stats', $dateClause, [], true, [], true);
            break;
            
        case 'users_daily_stats_sports':
            $wh->pushData('users_daily_stats_sports', $dateClause, [], true, [], true);
            break;
            
        // Add more cases if needed in the future
    }
}

/**
 * Retry warehouse push for failed tables based on cache keys
 */
private function retryWarehousePush()
{
    $retryCacheKeys = [
        'rerun-warehouse-push-users' => 'users',
        'rerun-warehouse-push-users_daily_stats' => 'users_daily_stats',
        'rerun-warehouse-push-users_daily_stats_sports' => 'users_daily_stats_sports'
    ];
    
    $tablesToPush = [];
    
    // Check which tables need to be pushed based on cache keys
    foreach ($retryCacheKeys as $cacheKey => $tableName) {
        if (phive()->getMiscCache($cacheKey)) {
            $tablesToPush[] = $tableName;
            phive()->delMiscCache($cacheKey);
        }
    }
    
    // If we have tables to push, process them
    if (!empty($tablesToPush)) {
        $this->pushToWarehouse([], true, $tablesToPush);
    }
    
}

    /**
     * Update the number of results for each contacts filter.
     */
    private function updateNamedSearches()
    {
        NamedSearch::all()->each(function ($named_search) {
            $named_search->result = count(DB::shsSelect('users', $named_search->sql_statement));
            $named_search->save();
        });
    }

    /**
     * Update all segments and groups.
     */
    private function updateSegments()
    {
        $current_time = Carbon::now();
        $users_count = DB::table('users')->count();
        DB::table('segments')
            ->join('segments_groups', 'segments_groups.segment_id', 'segments.id')
            ->get()
            ->each(function ($group) use ($users_count, $current_time) {
                /** @var Segment $group */

                if (empty($this->tmp_segment_id) || $this->tmp_segment_id != $group->segment_id) {
                    DB::table('segments')->where('id', $group->segment_id)->update(['users_count' => $users_count]);
                    $this->tmp_segment_id = $group->segment_id;
                }
                if (!empty($group->disabled)) {
                    // group is disabled so stop
                    DB::table('users_segments')
                        ->where('ended_at', '0000-00-00 00:00:00')
                        ->where('segment_id', $group->segment_id)
                        ->where('group_id', $group->id)
                        ->update(['ended_at' => $current_time]);

                    DB::table('segments_groups')
                        ->where('id', $group->id)
                        ->update(['users_covered' => 0]);
                    return;
                }

                $new_users = array_pluck(DB::shsSelect('users', $group->sql_statement), 'id');
                DB::table('segments_groups')
                    ->where('id', $group->id)
                    ->update(['users_covered' => count($new_users)]);

                $old_users = DB::table('users_segments')
                    ->where('ended_at', '0000-00-00 00:00:00')
                    ->where('segment_id', $group->segment_id)
                    ->where('group_id', $group->id)
                    ->pluck('user_id')
                    ->toArray();

                $flipped_users = array_flip($new_users);

                // users who are in the current group but they belong to a different one
                $users_to_unregister = array_filter($old_users, function ($user) use ($new_users, $flipped_users) {
                    return !isset($flipped_users[$user]);
                });

                foreach (array_chunk($users_to_unregister, 1000) as $chunk) {
                    DB::table('users_segments')
                        ->where('ended_at', '0000-00-00 00:00:00')
                        ->where('segment_id', $group->segment_id)
                        ->where('group_id', $group->id)
                        ->whereIn('user_id', $chunk)
                        ->update(['ended_at' => $current_time]);
                }

                $users_to_register = [];
                $flipped_old_users = array_flip($old_users);
                foreach ($new_users as $user) {
                    if (isset($flipped_old_users[$user])) {
                        continue;
                    }
                    $users_to_register[] = [
                        "user_id" => $user,
                        "segment_id" => $group->segment_id,
                        "group_id" => $group->id,
                        "started_at" => $current_time
                    ];
                }
                DB::bulkInsert('users_segments', 'user_id', $users_to_register);
            });
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException|\Exception
     */
    private function sendWiraya()
    {
        $config = Config::getValue('wiraya-language-map', 'crm', '', Config::TYPE_GROUP_LIST_WIRAYA, true);

        foreach ($config as $language_config) {
            list($language, $named_search_id, $wiraya_project_id) = explode('::', $language_config);

            $campaign = new MessagingCampaign();
            $campaign->type = MessagingCampaignTemplates::TYPE_WIRAYA;
            $campaign->status = MessagingCampaign::STATUS_PLACED;
            $campaign->campaign_template_id = 0;
            $campaign->contacts_list_name = $named_search_id;
            $campaign->bonus_id = null;
            $campaign->voucher_name = null;
            $campaign->save();

            try {
                (new MessagingRepository())->sendWirayaContacts($this->app, $campaign, $named_search_id,
                    $wiraya_project_id, $language);
            } catch (\Exception $e) {
                $campaign->invalidate($e->getMessage());
            }
        }
    }
}
