<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 4/20/16
 * Time: 12:28 PM
 */

namespace App\Commands;


use App\Extensions\Database\Connection\Connection;
use App\Models\User;
use App\Models\UserMonthlyInteractionReport;
use App\Repositories\BlockRepository;
use App\Repositories\UserMonthlyInteractionReportRepository;
use Carbon\Carbon;
use Ivoba\Silex\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Extensions\Database\FManager as DB;

class UserMonthlyInteractionReportCommand extends Command
{
    const SELF_LOCKED = 'self_locked';
    const SELF_EXCLUDED = 'self_excluded';

    /** @var  OutputInterface $output */
    protected $output;

    /** @var array $data Stores data in the class to save data over the commands */
    protected $data = [];


    protected $year;

    protected $month;

    protected function configure()
    {
        $this->setName("UserMonthlyInteractionReport")
            ->addArgument('month', InputArgument::OPTIONAL, 'Month for which to generate report (default: last month)')
            ->addArgument('year', InputArgument::OPTIONAL, 'Year for which to generate report (default: year of the last month, if the month is filled out the default year is the year of present month!')
            ->setDescription("Generate monthly interaction report for previous month into the table user_monthly_interaction_result_reportHide.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $current_date = Carbon::now();
        $last_month_date = Carbon::now()->subMonth();
        $default_year = $last_month_date->year;
        $month_input = $input->getArgument('month');
        $year_input = $input->getArgument('year');
        if ($month_input || !$year_input) {
            $default_year = $current_date->year;
        }

        $this->year =  $year_input ?? $default_year;
        $this->month = $month_input ?? $last_month_date->month;
        $this->output = $output;
        $this->generateData();

        return 0;
    }

    protected function generateData()
    {
        $repo = new UserMonthlyInteractionReportRepository($this->year, $this->month);

        $this->output->writeln("{$this->now()} Starting User Monthly Interaction Report data generation for year {$this->year} and month {$this->month}. ");

        $callback = function (Connection $connection) use ($repo) {

            $this->output->writeln("{$this->now()} Processing: VS {$connection->getName()}");

            $connection->setFetchMode(\PDO::FETCH_ASSOC);
            $this->data['connection'] = $connection;
            $repo->setConnection($connection);
            $repo->truncateDataForMonth(Carbon::createFromDate($this->year, $this->month));
            $raw_data = $repo->getRawData();
            $processed_data = $this->processRawData($raw_data);

            $this->insertData($processed_data);
        };

        if (DB::isSharded((new UserMonthlyInteractionReport())->getTable())) {
            DB::loopNodes($callback); //table will be global and sharded
        } else {
            $callback(DB::getMasterConnection());
        }

        $this->output->writeln("{$this->now()} Finished.");
    }

    /**
     * @param array $data
     * @return array
     */
    protected function processRawData(array $data)
    {
        $processed_data = [];

        foreach ($data as $row) {
            $user = User::sh($row['user_id'])->find($row['user_id']);
            if (!$user) {
              continue;
            }
            $processed_row['user_id'] = $row['user_id'];
            $processed_row['date'] = Carbon::create($this->year, $this->month)->toDateString();
            $processed_row['country'] = $user->country;
            $processed_row['actions'] = $row['actions'];
            $processed_row['has_limit'] = $row['has_limit'];
            $processed_row['deposited'] = $this->getPercentageDifference($row['current_deposit_amount'], $row['previous_deposit_amount']);
            $processed_row['total_loss'] = $this->getPercentageDifference($row['current_loss_amount'], $row['previous_loss_amount']);
            $processed_row['time_spent'] = $this->getPercentageDifference($row['current_time_spent_seconds'], $row['previous_time_spent_seconds']);
            $processed_row['active'] = $row['current_time_spent_seconds'] ? 1 : 0;
            $processed_row['user_blocks'] = $this->getUserBlocksString($user);

            $processed_data[] = $processed_row;
        }

        return $processed_data;
    }

    /**
     * @param User $user
     * @return string
     */
    protected function getUserBlocksString(User $user){
        $block_repo = new BlockRepository($user);
        $block_repo->populateSettings();
        $blocks_string = '';

        if ($block_repo->isSelfExcluded() || $block_repo->isExternalSelfExcluded()) {
            $blocks_string .= self::SELF_EXCLUDED.' ';
        }

        if ($block_repo->isSelfLocked()) {
            $blocks_string .= self::SELF_LOCKED.' ';
        }

        return $blocks_string;
    }

    /**
     * @param float|int $current_val
     * @param float|int $previous_val
     * @return float|int
     */
    protected function getPercentageDifference($current_val, $previous_val)
    {
        // if the values are zeros or are equal then the raise or decrease is 0
        if (
          ($previous_val == 0 && $current_val == 0) ||
          ($previous_val == $current_val)
        ) {
            return 0;
        }
        // we cannot divide by 0 so the percentage raise is always the 100% increase
        if ($previous_val == 0) {
          return 100;
        }
        // percentage decrease
        else if ($current_val < $previous_val) {
            $intermediate_sum = -1 * ($previous_val - $current_val);
        }
        // percentage increase
        else {
          $intermediate_sum = $current_val - $previous_val;
        }

      return $intermediate_sum / $previous_val * 100;
    }

    protected function insertData($data, $connection = false)
    {
        if ($connection === false) {
            UserMonthlyInteractionReport::bulkInsert($data, null, $this->data['connection']);
            if (UserMonthlyInteractionReport::isMasterAndSharded()) {
                UserMonthlyInteractionReport::bulkInsert($data, null, DB::getMasterConnection());
            }
        } else {
            UserMonthlyInteractionReport::bulkInsert($data, 'user_id', $connection);
        }
    }

    protected function now()
    {
        return '[' . Carbon::now()->format('Y-m-d H:i:s') . ']';
    }

}