<?php
namespace App\Commands\Helpers;

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Models\UserDailyBalance;
use App\Repositories\UsersDailyBoosterStatsRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;
use Ivoba\Silex\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use PDO;

class CalculateDailyBoosterVaultCommand extends Command
{
    /** @var UsersDailyBoosterStatsRepository $repo */
    private $repo;
    /** @var OutputInterface $output */
    private $output;
    /** @var Carbon $start_date */
    private $start_date;
    /** @var Carbon $end_date */
    private $end_date;
    /** @var string $users_list */
    private $users_list;

    protected function configure()
    {
        $this->setName("booster:calculateVault")
            ->setDescription("(Re)Calculate the booster_vault for all the players between start_date / end_date")
            ->addArgument('start_date', InputArgument::OPTIONAL, 'Start date for the recalculation')
            ->addArgument('end_date', InputArgument::OPTIONAL, 'End date for the recalculation')
            ->addArgument('filter_users', InputArgument::OPTIONAL, 'Type of users filter to be used')
            ->addArgument('users_list', InputArgument::OPTIONAL, 'Comma separated users ids list, used only with filter_users=USERS_LIST')
            ->addArgument('skip_booster', InputArgument::OPTIONAL, 'Set true if want to calculate only balance stats and skip booster stats');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->repo = new UsersDailyBoosterStatsRepository();
        $this->output = $output;
        $skip_booster = $input->getArgument('skip_booster');
        $filter_users = $input->getArgument('filter_users');
        $this->users_list = $input->getArgument('users_list');
        $this->start_date = $this->validateAndProcessDate($input->getArgument('start_date'));
        $this->end_date = $this->validateAndProcessDate($input->getArgument('end_date'));

        // First day ever with cash_transaction 100 will be 2019-07-04.
        if ($this->start_date < '2019-07-04') {
            $this->start_date = '2019-07-04';
        }

        // we cannot calculate in the future, or the current day... (it will be inserted at midnight)
        if ($this->end_date >= date('Y-m-d')) {
            $this->end_date = date('Y-m-d', strtotime('-1 day'));
        }

        $this->start_date = Carbon::parse($this->start_date);
        $this->end_date = Carbon::parse($this->end_date);
        $this->users_list = $this->repo->setupUsersFilter($filter_users, $this->users_list);

        if (!$skip_booster) {
            $this->output->writeln("Recalculating the booster statistics between {$this->start_date->toDateString()} and {$this->end_date->toDateString()} for $filter_users.");
            $this->cacheBooster();
        }

        $this->output->writeln("Syncing users_daily_balance_stats.");
        $this->syncDailyBalanceStats();

        return 0;
    }

    /**
     * Get the date, ensuring it is not today.
     * If the provided date is empty or today, return yesterday's date.
     * Otherwise return the date sent
     *
     * @param string|null $start_date
     * @return string
     */
    private function validateAndProcessDate($date)
    {
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        if (empty($date) || $date === date('Y-m-d')) {
            return $yesterday;
        }

        return $date;
    }

    /**
     * STEP 1 we calculate for each day of the period the generated & released booster
     */
    private function cacheBooster()
    {
        $this->loopDateInterval($this->start_date, $this->end_date, function($date) {
            $this->output->writeln('Calculating generated booster on ' . $date->toDateString() . ' (remove users_daily_booster_stats on day + re-populate from cash_transactions)');
            $this->repo->cacheGeneratedBoosterByPlayer($date->toDateString(), $this->users_list);
        });
    }

    /**
     * Execute action on each day in a date interval
     *
     * @param Carbon $start_date
     * @param Carbon $end_date
     * @param \Closure $callback
     */
    private function loopDateInterval(Carbon $start_date, Carbon $end_date, \Closure $callback)
    {
        $tmp_date = $start_date->copy();
        while ($tmp_date <= $end_date) {
            $callback($tmp_date);
            $tmp_date->addDay();
        }
    }

    /**
     * Insert or update balance stats in both shard and master databases
     * Used mysql update query because of exception
     * -Update not supported on master and sharded tables
     *
     * @param $inserts
     * @param $updates
     * @param Connection $shard_connection
     * @return void
     */
    public function updateOrInsertOnShardAndMaster($inserts, $updates, Connection $shard_connection): void
    {
        $master_connection = DB::getMasterConnection();
        $table = 'users_daily_balance_stats';
        if ($inserts) {
            foreach ($inserts as $insert) {
                $hasUser = $master_connection->table($table)
                    ->where('user_id', $insert['user_id'])
                    ->where('date', $insert['date'])
                    ->where(['source' => 0])
                    ->exists();

                if ($hasUser) {
                    $query = "UPDATE users_daily_balance_stats SET extra_balance = {$insert['extra_balance']} WHERE user_id = {$insert['user_id']} AND date = '{$insert['date']}' AND source = 0;";
                    $shard_connection->unprepared($query);
                    $master_connection->unprepared($query);
                } else {
                    $query = "INSERT INTO users_daily_balance_stats (user_id,`date`,cash_balance,bonus_balance,currency,country,province,source,extra_balance) VALUES ({$insert['user_id']},'{$insert['date']}',{$insert['cash_balance']},{$insert['bonus_balance']},'{$insert['currency']}','{$insert['country']}','{$insert['province']}',{$insert['source']},{$insert['extra_balance']});";
                    $shard_connection->unprepared($query);
                    $master_connection->unprepared($query);
                }
            }
        }

        if ($updates) {
            foreach (array_chunk($updates, 100) as $i => $chunk) {
                $shard_connection->unprepared(implode(";", $chunk));
            }

            foreach (array_chunk($updates, 100) as $i => $chunk) {
                $master_connection->unprepared(implode(";", $chunk));
            }
        }
    }
    /**
     * Ensure that users daily balance stats is up to date with correct values
     */
    private function syncDailyBalanceStats()
    {
        DB::loopNodes(function (Connection $connection) {
            echo "{$connection->getName()} \n";
            list($updates, $inserts) = $this->syncDailyBalanceStatsOnNode($connection);
            $this->updateOrInsertOnShardAndMaster($inserts, $updates, $connection);
        });
    }

    /**
     * STEP 2
     * A) CHECK for existing amount on "booster_vault" before the $start_date (this need to be added on extra_balance the first day)
     * B) GET ALL the daily booster transaction IN/OUT the vault for each single user
     * C) CHECK if the right value for "extra_balance" is set on "users_daily_balance_stats", otherwise we store the query (insert/update) to be run.
     *    to calculate we take in account previous days balance (A) + we sum/subtract daily generated/released booster from (B)
     * D) populate (INSERT) Bulk insert with all rows with cash_balance/bonus_balance = 0
     * E) populate (UPDATE) Bulk updates with all rows fetched on C)
     *
     * TODO step C/D) will work fine for now that we only have the weekend booster, if we add more things on "extra_balance" we need to take those into account to properly recalculate the value
     *
     * @param Connection $connection
     * @return array[]
     */
    private function syncDailyBalanceStatsOnNode(Connection $connection): array
    {
        $updates = [];
        $inserts = [];
        $latest_vault_by_user_date = $this->getLatestVaultBeforePeriod($connection);
        $daily_booster_transactions_by_user = $this->getDailyBoosterTransactionsByUserInPeriod($connection);
        $existing_daily_balance_stats_by_user = $this->getExistingDailyBalanceStatsInPeriod($connection);
        $users = $connection->table('users')
            ->select(['id', 'currency', 'country'])
            ->whereIn('id', $daily_booster_transactions_by_user->keys())
            ->get()
            ->keyBy('id');

        foreach ($daily_booster_transactions_by_user as $user_id => $dates) {
            $extra_balance = 0;
            // previously existing value, only on first existing day for each user
            if (isset($latest_vault_by_user_date[$user_id]) && !empty($latest_vault_by_user_date[$user_id]) && $latest_vault_by_user_date[$user_id]['accumulated_amount']) {
                $extra_balance += $latest_vault_by_user_date[$user_id]['accumulated_amount'];
            }

            foreach ($dates as $date => $booster_data) {
                // we sum/subtract the amount for that day
                $extra_balance += $booster_data['generated_booster'] + $booster_data['released_booster'];

                // we check for existing balance on the day for the user
                $current_day_balance_stat = !empty($existing_daily_balance_stats_by_user[$user_id]) ? $existing_daily_balance_stats_by_user[$user_id][$date] : [];

                if(!empty($current_day_balance_stat)) {
                    if ($extra_balance != $current_day_balance_stat['extra_balance']) {
                        $updates[] = "UPDATE users_daily_balance_stats SET extra_balance = {$extra_balance} WHERE user_id = {$user_id} AND date = '{$date}' AND source = 0";
                    }
                } elseif ($extra_balance > 0) {
                    $inserts[] = [
                        'user_id' => $user_id,
                        'date' => $date,
                        'cash_balance' => 0,
                        'bonus_balance' => 0,
                        'currency' => $users[$user_id]['currency'],
                        'country' => $users[$user_id]['country'],
                        'province' => !empty($users[$user_id]) ? cu($users[$user_id])->getSetting('main_province'): "",
                        'extra_balance' => $extra_balance,
                        'source' => 0
                    ];
                }
            }
        }

        return [$updates, $inserts];
    }

    /**
     * We calculate the amount the user had in the booster_vault before the requested recalculation period to take in account for existing balance in the vault.
     *
     * @param Connection $connection
     * @return Collection - grouped by user_id and date ['user_id'=>[DATA], ...]
     */
    private function getLatestVaultBeforePeriod(Connection $connection): Collection
    {
        $connection->setFetchMode(PDO::FETCH_ASSOC);
        return $connection->table('users_daily_booster_stats')
            ->select(['user_id', 'date', 'currency'])
            ->selectRaw('SUM(generated_booster) - ABS(SUM(released_booster)) as accumulated_amount')
            ->where('date', '<', $this->start_date)
            ->whereRaw("user_id {$this->users_list}")
            ->groupBy('user_id')
            ->having('accumulated_amount', '>', 0)
            ->orderBy('user_id')
            ->get()
            ->groupBy('user_id')
            ->map(function ($el) {
                return $el->first();
            });
    }

    /**
     * We get for each user, for each single day, the amount the user had "generated in"/"released from" the booster_vault in the requested recalculation period
     *
     * @param Connection $connection
     * @return Collection - grouped by user_id and date ['user_id'=>['date'=>[DATA]], ...]
     */
    private function getDailyBoosterTransactionsByUserInPeriod(Connection $connection): Collection
    {
        $connection->setFetchMode(PDO::FETCH_ASSOC);
        return $connection->table('users_daily_booster_stats')
            ->select(['user_id', 'date', 'currency', 'generated_booster', 'released_booster'])
            ->whereBetween('date', [$this->start_date, $this->end_date])
            ->whereRaw("user_id {$this->users_list}")
            ->orderBy('user_id')
            ->orderBy('date')
            ->get()
            ->groupBy('user_id')
            ->map(function ($el) {
                return $el->keyBy('date');
            });
    }

    /**
     * We get all the existing rows in the requested period, rows may be updated if the recalculation doesn't match the existing value.
     * A warning is thrown on the console when that happens.
     *
     * @param Connection $connection
     * @return Collection - grouped by user_id and date ['user_id'=>['date'=>[DATA]], ...]
     */
    private function getExistingDailyBalanceStatsInPeriod(Connection $connection): Collection
    {
        $connection->setFetchMode(PDO::FETCH_ASSOC);
        return $connection->table('users_daily_balance_stats')
            ->select(['user_id', 'date', 'currency', 'country'])
            ->selectRaw('SUM(cash_balance) AS cash_balance')
            ->selectRaw('SUM(bonus_balance) AS bonus_balance')
            ->selectRaw('SUM(extra_balance) AS extra_balance')
            ->whereBetween('date', [$this->start_date, $this->end_date])
            ->where('source', '=', 0) // 0 = videoslots
            ->whereRaw("user_id $this->users_list")
            ->groupBy('user_id', 'date')
            ->orderBy('user_id')
            ->get()
            ->groupBy('user_id')
            ->map(function ($el) {
                return $el->keyBy('date');
            });
    }
}
