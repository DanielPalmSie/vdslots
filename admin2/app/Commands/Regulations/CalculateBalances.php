<?php
declare(strict_types=1);

namespace App\Commands\Regulations;

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\ReplicaFManager as DBRO;
use Carbon\Carbon;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Ivoba\Silex\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CalculateBalances extends Command
{
    private string $from_date;
    private string $to_date;

    protected function configure()
    {
        $this->setName('regulations:calculate_balances')
            ->setDescription('One of user or country is required. The command will fail if there\'s already info for the period and option selected')
            ->addArgument('date_from', InputArgument::REQUIRED, 'Start of calculation period. 0 as start')
            ->addArgument('date_to', InputArgument::REQUIRED, 'End of calculation period')
            ->addOption('user', null, InputOption::VALUE_OPTIONAL, 'Calculate only for an user')
            ->addOption('country', null, InputOption::VALUE_OPTIONAL, 'Calculate for entire country')
            ->addOption('replace', null, InputOption::VALUE_NONE, 'Replace existing data. Needed if the period already has the selected data.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Output a listing of SQL instead of making the changes')
            ->addOption('currency', null, InputOption::VALUE_IS_ARRAY|InputOption::VALUE_OPTIONAL, 'List of currencies to which operate: cash', [])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user_option = $input->getOption('user');
        $country_option = $input->getOption('country');

        if (!($user_option xor $country_option)) {
            $output->writeln('Only one of user or country must be set');
            return 1;
        }

        $this->from_date = $input->getArgument('date_from');
        $this->to_date = $input->getArgument('date_to');

        loadPhive();

        $currencies = array_map('strtolower', $input->getOption('currency'));

        $failure = 0;
        if(!$currencies || in_array('cash', $currencies, true)){
            $failure += $this->calcCash($input, $output);
        }

        if (!$failure) {
            $output->writeln("Process for {$this->from_date}-{$this->to_date} " . ($country_option ? 'country=' . $country_option : 'user=' . $user_option) . ' finished.');
            return 0;
        } else {
            $output->writeln("A failure has happened {$this->from_date}-{$this->to_date} " . ($country_option ? 'country=' . $country_option : 'user=' . $user_option) . " code {$failure}");
            return $failure;
        }
    }

    protected function calcCash(InputInterface $input, OutputInterface $output): int
    {
        $user_option = $input->getOption('user');
        $country_option = $input->getOption('country');
        $casino = phive('CasinoCashier');

        $bonus_types = array_intersect_key(
            $casino->getColsForDailyStats(),
            array_flip(array_merge($casino->getCashTransactionsBonusTypes(), lic('getAdditionalReportingBonusTypes', [], $user_option, null, $country_option)))
        );

        //this value is added to rewards column in daily_stats,
        //but it's not shown as part of the user account in the frontend, so it shouldn't be added here
        //I'm not even sure it's a monetary value
        unset($bonus_types['frb_cost']);
        unset($bonus_types['tournament_ticket_shift']);

        //9 => 'Chargeback'
        $bonus_types[] = 9;
        //13 => 'Normal refund'
        $bonus_types[] = 13;
        //15 => 'Failed bonus'
        $bonus_types[] = 15;
        //34 => 'Casino tournament buy in'
        $bonus_types[] = 34;
        //38 => 'Tournament cash win'
        $bonus_types[] = 38;
        //43 => 'Inactivity fee'
        $bonus_types[] = 43;
        //50 => 'Withdrawal deduction'
        $bonus_types[] = 50;
        //52 => 'Casino tournament house fee'
        $bonus_types[] = 52;
        //54 => 'Casino tournament rebuy'
        $bonus_types[] = 54;
        //61 => 'Cancel / Unreg of casino tournament buy in'
        $bonus_types[] = 61;
        //63 => 'Cancel / Unreg of casino tournament house fee'
        $bonus_types[] = 63;
        //91 => 'Liability adjustment'
        $bonus_types[] = 91;

        //we need to use an external variable to return an error from loopNodes
        $failure = 0;

        DBRO::loopNodes(
            function (Connection $connection) use ($input, $output, $bonus_types, &$failure, $user_option, $country_option) {

                if ($failure) {
                    return;
                }

                if ($user_option) {
                    $shard_users = $user_condition = [$user_option];
                } else {
                    $user_condition = $connection->table('users')->where('country', $country_option)->select(['id']);
                    $shard_users = $this->getUsersArray($user_condition);
                }

                //we need to know where are we for writing the balances, so we get a user id
                $shard_selector = $connection->table('users')->first(['id'])->id;
                $write_connection = DB::shTable($shard_selector, 'users')->getConnection();

                $failure = $this->checkReplace($input, $output, $connection, $write_connection, $user_condition, 'cash');
                if ($failure) {
                    return;
                }

                if ($this->from_date) {
                    $previous_balances = $connection->table('external_regulatory_user_balances AS a')
                        ->select(['user_id', 'cash_balance', 'extra_balance', 'currency'])
                        ->whereIn('user_id', $user_condition)
                        ->where('id', '=', function (Builder $q) {
                            $q->from('external_regulatory_user_balances AS b')
                                ->select('id')
                                ->whereColumn('b.user_id', '=', 'a.user_id')
                                ->where('balance_date', '<', $this->from_date)
                                ->whereRaw('LENGTH(currency) = 3') //only fiat currencies
                                ->orderByDesc('balance_date')
                                ->limit(1);
                        })
                        ->get()
                        ->keyBy('user_id');
                } else {
                    $previous_balances = collect();
                }

                $bets = $connection->table('bets')
                    ->selectRaw('SUM(amount)*-1 AS amount, user_id, DATE(created_at) AS op_date, currency')
                    ->whereIn('user_id', $user_condition)
                    ->groupBy(['user_id', 'op_date']);
                $bets = $this->applyDateFilter($bets, 'created_at');

                $bets_for_archive = $connection->table('bets')
                    ->selectRaw('SUM(amount)*-1 AS amount, user_id, DATE(created_at) AS op_date, currency')
                    ->whereIn('user_id', $shard_users)
                    ->groupBy(['user_id', 'op_date']);
                $bets_for_archive = $this->applyDateFilter($bets_for_archive, 'created_at');

                //we transform the bets collection to array so that we can prepend results from the archives.
                $all_bets = $this->transformCollectionToArray($bets->get());
                //getting the sql query with bindings so that we can send it to the archive prepend function
                $bet_sql = $this->toSqlWithBindings($bets_for_archive);
                phive('SQL')->prependFromNodeArchive($all_bets, $shard_selector, $this->from_date, $bet_sql, 'bets');

                $wins = $connection->table('wins')
                    ->selectRaw('SUM(amount) AS amount, user_id, DATE(created_at) AS op_date, currency')
                    ->whereIn('user_id', $user_condition)
                    ->groupBy(['user_id', 'op_date']);
                $wins = $this->applyDateFilter($wins, 'created_at');

                $wins_for_archive = $connection->table('wins')
                    ->selectRaw('SUM(amount) AS amount, user_id, DATE(created_at) AS op_date, currency')
                    ->whereIn('user_id', $shard_users)
                    ->groupBy(['user_id', 'op_date']);
                $wins_for_archive = $this->applyDateFilter($wins_for_archive, 'created_at');

                //we transform the wins collection to array so that we can prepend results from the archives.
                $all_wins = $this->transformCollectionToArray($wins->get());
                //getting the sql query with bindings so that we can send it to the archive prepend function
                $wins_sql = $this->toSqlWithBindings($wins_for_archive);
                phive('SQL')->prependFromNodeArchive($all_wins, $shard_selector, $this->from_date, $wins_sql, 'wins');

                $deposits = $connection->table('deposits')
                    ->selectRaw('SUM(amount) AS amount, user_id, DATE(timestamp) AS op_date, currency')
                    ->whereIn('user_id', $user_condition)
                    ->where('status', 'approved')
                    ->groupBy(['user_id', 'op_date']);
                $deposits = $this->applyDateFilter($deposits, 'timestamp');

                $withdrawals = $connection->table('pending_withdrawals')
                    ->selectRaw('SUM(amount)*-1 AS amount, user_id, DATE(timestamp) AS op_date, currency')
                    ->whereIn('user_id', $user_condition)
                    ->groupBy(['user_id', 'op_date']);
                $withdrawals = $this->applyDateFilter($withdrawals, 'timestamp');

                $vault = $connection->table('cash_transactions')
                    ->selectRaw('SUM(amount) AS amount, SUM(amount)*-1 AS extra_amount, user_id, DATE(timestamp) AS op_date, currency')
                    ->whereIn('user_id', $user_condition)
                    ->whereIn('transactiontype', [100, 101])
                    ->groupBy(['user_id', 'op_date']);
                $vault = $this->applyDateFilter($vault, 'timestamp');

                $bonuses = $connection->table('cash_transactions')
                    ->selectRaw('SUM(amount) AS amount, user_id, DATE(timestamp) AS op_date, currency')
                    ->whereIn('user_id', $user_condition)
                    ->whereIn('transactiontype', $bonus_types)
                    ->groupBy(['user_id', 'op_date']);
                $bonuses = $this->applyDateFilter($bonuses, 'timestamp');

                $bet_rollbacks = $connection->table('cash_transactions')
                    ->selectRaw('SUM(amount) AS amount, user_id, DATE(timestamp) AS op_date, currency')
                    ->whereIn('user_id', $user_condition)
                    ->where('transactiontype', 7)
                    ->where("description", "LIKE", "%play mode: normal, rollback type: bets%")
                    ->orWhere(function ($q) {
                        $q->where("description", "LIKE", "%rollback adjustment of%")
                            ->where("description", "LIKE", "%play mode: normal%");
                    })
                    ->groupBy(['user_id', 'op_date']);
                $bet_rollbacks = $this->applyDateFilter($bet_rollbacks, 'timestamp');

                //merge all info into a single list
                $all = collect($all_bets)
                    ->merge(collect($all_wins))
                    ->merge($deposits->get())
                    ->merge($withdrawals->get())
                    ->merge($vault->get())
                    ->merge($bonuses->get())
                    ->merge($bet_rollbacks->get());

                $change_to_rollbacks_v2_timestamp = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    phive('Licensed')->getSetting('change_to_rollbacks_v2_timestamp')
                );

                $is_after_rollbacks_v2 = Carbon::createFromFormat(
                    'Y-m-d',
                    $this->to_date
                )->isAfter($change_to_rollbacks_v2_timestamp);

                if ($is_after_rollbacks_v2) {
                    $start = $this->from_date;

                    if (Carbon::createFromFormat(
                        'Y-m-d',
                        $this->from_date
                    )->isBefore($change_to_rollbacks_v2_timestamp)) {
                        $start = $change_to_rollbacks_v2_timestamp;
                    }

                    $rollbacks = $connection->table('cash_transactions')
                        ->selectRaw('SUM(amount) AS amount, user_id, DATE(timestamp) AS op_date, currency')
                        ->whereIn('user_id', $user_condition)
                        ->where('transactiontype', 7)
                        ->whereBetween('timestamp', [$start, $this->to_date])
                        ->groupBy(['user_id', 'op_date']);
                    $rollbacks = $this->applyDateFilter($rollbacks, 'timestamp');

                    $all->merge($rollbacks->get());
                }

                if ($all->isEmpty()) {
                    return;
                }

                $all = $all->groupBy('op_date')->sortBy(static function ($unused, $date) {
                    return $date;
                });

                foreach ($all as $date => $day_ops) {
                    /** @var Collection $ops */
                    foreach ($day_ops->groupBy('user_id') as $user_id => $ops) {
                        $previous_balance = collect($previous_balances->get($user_id));
                        $previous_balance['cash_balance'] = $previous_balance->get('cash_balance', 0) + $ops->pluck('amount')->sum();
                        $previous_balance['extra_balance'] = $previous_balance->get('extra_balance', 0) + $ops->pluck('extra_amount')->sum();
                        $previous_balance['currency'] = $previous_balance->get('currency') ?: $ops->pluck('currency')->first();

                        $previous_balances[$user_id] = $previous_balance;

                        $insert = static function (ConnectionInterface $connection) use ($user_id, $date, $previous_balance) {
                            $connection->table('external_regulatory_user_balances')
                                ->insert([
                                    'user_id'       => $user_id,
                                    'balance_date'  => $date,
                                    'cash_balance'  => $previous_balance->get('cash_balance', 0),
                                    'extra_balance' => $previous_balance->get('extra_balance', 0),
                                    'currency'      => $previous_balance->get('currency', 'EUR'),
                                ], true, true); //third parameter comes from \App\Extensions\Database\Builder::insert, to avoid the default shs
                        };

                        if ($input->getOption('dry-run')) {
                            $query = $connection->pretend($insert);

                            $output->writeln($this->formatQuery($query[0], $connection));

                        } else {
                            $insert($write_connection);
                        }

                    }
                }
            }
        );

        return $failure;
    }

    private function toSqlWithBindings(Builder $collection): string
    {
        $sql = str_replace('?', "'?'", $collection->toSql());

        return str_replace_array('?', $collection->getBindings(), $sql);
    }

    private function transformCollectionToArray(Collection $collection): array
    {
        return $collection->transform(function($x) {
            return (array) $x;
        })->toArray();
    }

    private function applyDateFilter(Builder $query, string $field): Builder
    {
        if ($this->from_date) {
            $query->whereDate($field, '>=', $this->from_date);
        }
        if ($this->to_date) {
            $query->whereDate($field, '<=', $this->to_date);
        }
        return $query;
    }

    private function formatQuery(array $query, Connection $connection)
    {
        $pdo = $connection->getPdo();
        foreach ($query['bindings'] as $i => $v) {
            $query['bindings'][$i] = $pdo->quote($v);
        }
        return Str::replaceArray('?', $query['bindings'], $query['query']) . ';';
    }

    private function getUsersArray(Builder $user_condition): array
    {
        return array_unique(array_column($user_condition->get()->toArray(),'id'));
    }

    protected function checkReplace(InputInterface $input, OutputInterface $output, Connection $connection, ConnectionInterface $write_connection, $user_condition, string $currency)
    {
        $make_query = function(ConnectionInterface $connection) use ($user_condition, $currency) {
            $query = $connection->table('external_regulatory_user_balances')
                ->whereIn('user_id', $user_condition);
            if ($currency === 'cash') {
                $query->whereRaw('LENGTH(currency)=3'); //real currency
            } else {
                $query->where('currency', $currency);
            }
            return $this->applyDateFilter($query, 'balance_date');
        };


        if (!$input->getOption('replace')) {
            $exists = $make_query($connection)->exists();
            if ($exists) {
                $output->writeln('There\'s existing data with the conditions selected. Please use --replace to continue');
                return 2;
            }
        } else {
            $delete = static function (ConnectionInterface $connection) use ($make_query) {
                $make_query($connection)->delete(null, true); //second parameter comes from \App\Extensions\Database\Builder::delete, to avoid the default shs
            };

            if ($input->getOption('dry-run')) {
                $query = $connection->pretend($delete);

                $output->writeln($this->formatQuery($query[0], $connection));
            } else {
                $delete($write_connection);
            }
        }
        return 0;
    }

}
