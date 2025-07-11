<?php
declare(strict_types=1);

namespace App\Commands\Export;

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\ReplicaFManager as DB;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use JsonException;
use PDO;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

class ExportICS extends ExportBaseCommand
{
    protected const COUNTRIES = ['DK', 'SE', 'GB', 'MT', 'DE', 'IT', 'ES', 'NL'];

    protected string $start_date;
    protected string $end_date;
    protected string $country;
    protected string $zip_file_name = '';
    protected int $users_limit_per_shard = 0;
    protected bool $filter_users = false;
    protected bool $has_users_temp_table = false;
    protected ProgressBar $progress_bar;
    protected ?Connection $connection = null;
    protected int $parallel;
    protected ?int $generate_for_shard;
    protected array $cmd_options;
    protected string $jurisdiction = 'ics';
    protected int $query_timeout;

    protected array $tags = [
        'deposit-rgl-add',
        'deposit-rgl-remove',
        'deposit-rgl-applied',
        'deposit-rgl-lower',
        'deposit-rgl-change',
        'deposit-rgl-raise',
        'profile-update-success',
        'profile-update-by-admin',
        'user_status_changed',
        'uagent',
        'document-approved',
        'deposit-rgl-current',
    ];

    /**
     * @return void
     */
    protected function clearQueryTimeout(): void
    {
        try {
            $this->connection->unprepared('SET SESSION max_statement_time=0;'); //mariadb
        } catch (Exception $e) {
            $this->connection->unprepared('SET SESSION MAX_EXECUTION_TIME=0;'); //mysql
        }
    }

    /**
     * @return void
     */
    protected function setQueryTimeout(): void
    {
        try {
            $this->connection->unprepared("SET SESSION max_statement_time={$this->query_timeout};"); //mariadb
        } catch (Exception $e) {
            $this->connection->unprepared("SET SESSION MAX_EXECUTION_TIME={$this->query_timeout}000;"); //mysql
        }
    }

    /**
     * @throws JsonException
     */
    protected function configure()
    {
        $countries = json_encode(self::COUNTRIES, JSON_THROW_ON_ERROR);

        $this->setName("export:ics")
            ->setDescription('Export data for ICS reports testing')
            ->addArgument('country', InputArgument::REQUIRED, "Choose the country. In {$countries}")
            ->addArgument('start_date', InputArgument::REQUIRED, "Set start date. Example '2021-09-01 00:00:00'")
            ->addArgument('end_date', InputArgument::REQUIRED, "Set end date. Example '2021-10-31 23:59:59'")
            ->addArgument('users_limit_per_shard', InputArgument::OPTIONAL, 'Set limit for select users per shard')
            ->addOption(
                'filter-users',
                null,
                InputOption::VALUE_NONE,
                "Use date range to filter users by registration date"
            )
            ->addOption('parallel', null, InputOption::VALUE_OPTIONAL, 'Amount of shards to process at once', 1)
            ->addOption(
                'generate-for-shard',
                null,
                InputOption::VALUE_REQUIRED,
                'Only generate the csv files for a single shard'
            )
            ->addOption(
                'bailout-time',
                null,
                InputOption::VALUE_REQUIRED,
                'Amount of time to wait on bets and wins. Default 300 (5m)',
                300
            )
        ;

        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function init(InputInterface $input, OutputInterface $output): void
    {
        $this->country = (string)$input->getArgument('country');
        $this->start_date = trim((string)$input->getArgument('start_date'));
        $this->end_date = trim((string)$input->getArgument('end_date'));
        $this->filter_users = (bool)$input->getOption('filter-users');
        $this->users_limit_per_shard = (int)$input->getArgument('users_limit_per_shard');
        $this->parallel = (int)$input->getOption('parallel');
        $this->generate_for_shard = is_null($input->getOption('generate-for-shard')) ?
            null :
            (int)$input->getOption('generate-for-shard');
        $this->query_timeout = (int) $input->getOption('bailout-time');

        $this->cmd_options = $this->setCmdLine($input);

        //if we got only dates, add times to make them full day
        if (strlen($this->start_date) === 10) {
            $this->start_date .= ' 00:00:00';
        }

        if (strlen($this->end_date) === 10) {
            $this->end_date .= ' 23:59:59';
        }

        if (!in_array($this->country, self::COUNTRIES)) {
            throw new InvalidArgumentException('Wrong country');
        }

        $this->progress_bar = new ProgressBar($output, count(DB::getNodesList()) + 2);

        parent::init($input, $output);
    }

    /**
     * @return void
     */
    protected function collectData(): void
    {
        if (is_null($this->generate_for_shard)) {
            $this->collectDataFromMasterTables();

            $this->collectDataFromShardTables();

            $file_prefix = "{$this->jurisdiction}_{$this->country}_";
            $file_prefix .= strtr($this->start_date, [':' => '', '-' => '', ' ' => '']) . '-';
            $file_prefix .= strtr($this->end_date, [':' => '', '-' => '', ' ' => '']);

            $this->zipAll($file_prefix);
        } else {
            $this->generateShardFiles($this->generate_for_shard);
            $this->progress_bar->clear();
        }
    }

    protected function zipAll(string $file_name_prefix = 'export'): string
    {
        $this->progress_bar->advance();
        $this->zip_file_name = parent::zipAll($file_name_prefix);

        return $this->zip_file_name;
    }

    protected function finish(InputInterface $input, OutputInterface $output): void
    {
        if (is_null($this->generate_for_shard)) {
            $this->progress_bar->finish();

            $output->writeln(PHP_EOL . 'Done!' . PHP_EOL . "File's path is: `{$this->zip_file_name}`");
        } else {
            foreach ($this->files as $file) {
                $output->writeln(json_encode($file));
            }
        }
        parent::finish($input, $output);
    }

    protected function getUsers(): Collection
    {
        if ($this->has_users_temp_table) {
            return $this->connection->table($this->getUsersTableTempName())->get();
        }

        return $this->getUsersQuery()->selectRaw("id, concat('user', id) AS username, register_date, cash_balance")->get();
    }

    /**
     * @return Builder
     */
    protected function getUsersIdsBuilder(): Builder
    {
        if ($this->has_users_temp_table) {
            return $this->connection->table($this->getUsersTableTempName())
                ->select(['id'])
                ->orderBy('id');
        }

        return $this->getUsersQuery()->select(['id']);
    }

    /**
     * @return string
     */
    protected function getReducedUserIds(): string
    {
        try {
            return implode(',', array_column($this->getUsersIdsBuilder()->get()->toArray(), 'id'));
        } catch (Throwable $e){
            return '';
        }
    }

    protected function getCountry(): string
    {
        return $this->country;
    }

    protected function getActions(): Collection
    {
        return $this->connection->table('actions')
            ->whereIn('target', $this->getUsersIdsBuilder())
            ->whereIn('tag', $this->tags)
            ->whereBetween('created_at', [$this->start_date, $this->end_date])
            ->get();
    }

    protected function getAllActions(): Collection
    {
        $actions = $this->getActions()->toArray();
        $users = $this->getReducedUserIds();
        $string_tags = '\''.implode("', '", $this->tags).'\'';

        $sql = phive('SQL');
        $query = "SELECT * FROM actions WHERE created_at BETWEEN '{$this->start_date}' AND '{$this->end_date}' AND target IN ({$users}) AND tag IN ({$string_tags})";

        $sql->prependFromNodeArchive($actions, $this->getShardCurrent(), null, $query, 'actions');
        $actions = $this->removeDuplicatedArchiveData($actions);
        $actions = collect($actions);

        $actions->map(function (array $action) {
            if (!empty($action['actor_username'])) {
                $anonymize_username = substr(sha1($action['actor_username']), 0, 12);
                $action['descr'] = str_replace($action['actor_username'], $anonymize_username, $action['descr']);
                $action['actor_username'] = $anonymize_username;
            }

            return $action;
        });

        return $actions;
    }

    protected function getCashTransactions(): Collection
    {
        return $this->connection->table('cash_transactions')
            ->whereIn('user_id', $this->getUsersIdsBuilder())
            ->whereBetween('timestamp', [$this->start_date, $this->end_date])
            ->get();
    }

    protected function getDeposits(): Collection
    {
        return $this->connection->table('deposits')
            ->whereIn('user_id', $this->getUsersIdsBuilder())
            ->whereBetween('timestamp', [$this->start_date, $this->end_date])
            ->get()
            ->map(function (array $datum) {
                if (!empty($datum['ip_num'])) {
                    $datum['ip_num'] = $this->anonymizeIP($datum['ip_num']);
                }

                if (!empty($datum['card_hash'])) {
                    $datum['card_hash'] = $this->anonymizeCardHash($datum['card_hash']);
                }

                return $datum;
            });
    }

    protected function getExtGameParticipations(): Collection
    {
        return $this->connection->table('ext_game_participations')
            ->whereIn('user_game_session_id', $this->getUserGameSessionIdsBuilder(true))
            ->get();
    }

    protected function getFirstDeposits(): Collection
    {
        return $this->connection->table('first_deposits')
            ->whereIn('user_id', $this->getUsersIdsBuilder())
            ->whereBetween('timestamp', [$this->start_date, $this->end_date])
            ->get();
    }

    protected function getGameTags(): Collection
    {
        return $this->getMasterConnection()->table('game_tags')->get();
    }

    protected function getGameTagCon(): Collection
    {
        return $this->getMasterConnection()->table('game_tag_con')->get();
    }

    protected function getMicroGames(): Collection
    {
        return $this->getMasterConnection()->table('micro_games')->get();
    }

    protected function getPendingWithdrawals(): Collection
    {
        return $this->connection->table('pending_withdrawals')
            ->whereIn('user_id', $this->getUsersIdsBuilder())
            ->whereBetween('approved_at', [$this->start_date, $this->end_date])
            ->get(['id', 'user_id', 'amount', 'timestamp', 'ip_num',
                   'scheme', 'payment_method', 'status', 'approved_at'])
            ->map(function (array $datum) {
                if (!empty($datum['ip_num'])) {
                    $datum['ip_num'] = $this->anonymizeIP($datum['ip_num']);
                }

                if (!empty($datum['scheme'])) {
                    $datum['scheme'] = $this->anonymizeCardHash($datum['scheme']);
                }

                return $datum;
            });
    }

    protected function getUsersDailyStats(): Collection
    {
        return $this->connection->table('users_daily_stats')
            ->whereIn('user_id', $this->getUsersIdsBuilder())
            ->whereBetween('date', [substr($this->start_date, 0, 10), substr($this->end_date, 0, 10)])
            ->get()
            ->map(function (array $datum) {
                if (!empty($datum['username'])) {
                    $datum['username'] = substr(sha1($datum['username']), 0, 12);
                }

                unset($datum['firstname'], $datum['lastname']);

                return $datum;
            });
    }

    protected function getUsersDailyBalanceStats(): Collection
    {
        return $this->connection->table('users_daily_balance_stats')
            ->whereIn('user_id', $this->getUsersIdsBuilder())
            ->whereBetween('date', [substr($this->start_date, 0, 10), substr($this->end_date, 0, 10)])
            ->get();
    }

    protected function getUsersGameSessions(): Collection
    {
        return $this->getUserGameSessionIdsBuilder()
            ->get()
            ->map(function (array $datum) {
                if (!empty($datum['ip'])) {
                    $datum['ip'] = $this->anonymizeIP($datum['ip']);
                }

                return $datum;
            });
    }

    protected function getUsersSessions(): Collection
    {
        return $this->connection->table('users_sessions')
            ->whereIn('user_id', $this->getUsersIdsBuilder())
            ->whereBetween('created_at', [$this->start_date, $this->end_date])
            ->get();
    }

    protected function getUsersChangeStats(): Collection
    {
        return $this->connection->table('users_changes_stats')
            ->whereIn('user_id', $this->getUsersIdsBuilder())
            ->whereBetween('created_at', [$this->start_date, $this->end_date])
            ->get()
            ->map(function (array $datum) {
                $datum['pre_value'] = substr(sha1($datum['pre_value']), 0, 12);
                $datum['post_value'] = substr(sha1($datum['post_value']), 0, 12);

                return $datum;
            });
    }

    protected function getBets(): Collection
    {
        $this->setQueryTimeout();

        try {

            $bets = $this->connection->table('bets')
                ->whereBetween('created_at', [$this->start_date, $this->end_date])
                ->whereIn('user_id', $this->getUsersIdsBuilder())
                ->get();

            $this->clearQueryTimeout();

            return $bets;

        } catch (Throwable $e) {
            $this->clearQueryTimeout();

            $users = $this->getUsersIdsBuilder()->pluck('id', 'id');
            $allBets = collect();

            while ($users->isNotEmpty()) {
                $userSlice = $users->pop(50)->toArray();
                $bets = $this->connection->table('bets')
                    ->whereBetween('created_at', [$this->start_date, $this->end_date])
                    ->whereIn('user_id', $userSlice)
                    ->get();

                $allBets->push($bets);
            }

            return $allBets;
        }

    }

    protected function getAllBets(): array
    {
        $bets = $this->getBets()->toArray();
        $users = $this->getReducedUserIds();

        $sql = phive('SQL');
        $query = "SELECT * FROM bets WHERE created_at BETWEEN '{$this->start_date}' AND '{$this->end_date}' AND user_id IN ({$users})";

        $sql->prependFromNodeArchive($bets, $this->getShardCurrent(), null, $query, 'bets');

        return $this->removeDuplicatedArchiveData($bets);
    }

    protected function getWins(): Collection
    {
        $this->setQueryTimeout();

        try {

            $wins = $this->connection->table('wins')
                ->whereBetween('created_at', [$this->start_date, $this->end_date])
                ->whereIn('user_id', $this->getUsersIdsBuilder())
                ->get();

            $this->clearQueryTimeout();

            return $wins;


        } catch (Throwable $e) {

            $this->clearQueryTimeout();

            $users = $this->getUsersIdsBuilder()->pluck('id', 'id');
            $allWins = collect();

            while ($users->isNotEmpty()) {
                $userSlice = $users->pop(50)->toArray();
                $wins = $this->connection->table('wins')
                    ->whereBetween('created_at', [$this->start_date, $this->end_date])
                    ->whereIn('user_id', $userSlice)
                    ->get();

                $allWins->push($wins);
            }

            return $allWins;
        }
    }

    protected function getAllWins(): array
    {
        $wins = $this->getWins()->toArray();
        $users = $this->getReducedUserIds();

        $sql = phive('SQL');
        $query = "SELECT * FROM wins WHERE created_at BETWEEN '{$this->start_date}' AND '{$this->end_date}' AND user_id IN ({$users})";

        $sql->prependFromNodeArchive($wins, $this->getShardCurrent(), null, $query, 'wins');

        return $this->removeDuplicatedArchiveData($wins);
    }

    protected function getUsersSettings(): Collection
    {
        $settings = [
            'paypal_email',
            'paypal_payer_id',
            'nid_data',
            'nid_extra',
            'email_url',
            'email_code',
            'mb_email',
            'vega_username',
            'minfraud-result',
            'instadebit_data',
            'muchbetter_mobile',
            'mifinity_mobile',
            'astropay_mobile',
            'id3global_full_res',
            'security_question',
            'security_answer',
            'lastname',
            'lastname_second',
            'acuris_full_res',
            'acuris-qrcode',
            'acuris-alert',
            'acuris_error_monitor',
            'acuris_pep_res',
        ];

        return $this->connection->table('users_settings')
            ->whereIn('user_id', $this->getUsersIdsBuilder())
            ->whereNotIn('setting', $settings)
            ->get();
    }

    protected function collectDataFromMasterTables(): void
    {
        $this->putDataIntoFile('game_tags', $this->getGameTags());

        $this->putDataIntoFile('game_tag_con', $this->getGameTagCon());

        $this->putDataIntoFile('micro_games', $this->getMicroGames());

        $this->progress_bar->advance();
    }

    protected function getUserGameSessionIdsBuilder(bool $select_only_ids = false): Builder
    {
        $builder = $this->connection->table('users_game_sessions')
            ->whereIn('user_id', $this->getUsersIdsBuilder())
            ->whereBetween('end_time', [$this->start_date, $this->end_date]);

        if ($select_only_ids) {
            $builder->select('id');
        }

        return $builder;
    }

    public function parallelCallback(string $type, string $data): void
    {
        if ($type === Process::OUT) {
            $output = explode("\n", $data);
            foreach ($output as $line) {
                if ($line[0] === '{') {
                    $file = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                    if (isset($file['filename'])) {
                        $this->files[] = $file;
                    }
                }
            }
        }
        if($type === Process::ERR){
            echo $data;
        }
    }

    protected function collectDataFromShardTables(): void
    {
        $do_master = !count(DB::getNodesList());

        if ($do_master) {
            $this->processNode(DB::getMasterConnection());
            return;
        }

        $parallel_count = $this->parallel;

        if ($parallel_count === 1) {
            DB::loopNodes(
                function (Connection $connection) {
                    $this->processNode($connection);
                    $this->progress_bar->advance();
                }, false);
            return;
        }

        $connections = [];
        /** @var Process[] $processes */
        $processes = [];
        foreach (DB::getNodesList() as $node => $node_name) {
            $connections[] = $node;
        }
        while ($parallel_count--) {
            $shard = array_shift($connections);
            $processes[$shard] = $this->callShard($shard);
            $processes[$shard]->start([$this, 'parallelCallback']);
        }

        while (count($processes)) {
            foreach ($processes as $i => $process) {
                if (!$process->isRunning()) {
                    unset($processes[$i]);

                    if (count($connections)) {
                        $shard = array_shift($connections);
                        $processes[$shard] = $this->callShard($shard);
                        $processes[$shard]->start([$this, 'parallelCallback']);
                    }

                    $this->progress_bar->advance();
                }
            }
            usleep(1000);
        }

    }

    protected function processNode(Connection $connection): void
    {
        $this->connection = $connection;
        $this->connection->setFetchMode(PDO::FETCH_ASSOC);

        $this->setShardCurrent($this->connection->getConfig('replica_node_id'));

        $this->createUsersTableTemp();

        $users = $this->getUsers();

        if ($users->isEmpty()) {
            $this->dropUsersTempTable();
            return;
        }

        $this->putDataIntoFile('users', $users);
        $users = null; // cleaned up

        $this->putDataIntoFile('actions', $this->getAllActions());

        $this->putDataIntoFile('cash_transactions', $this->getCashTransactions());

        $this->putDataIntoFile('deposits', $this->getDeposits());

        $this->putDataIntoFile('ext_game_participations', $this->getExtGameParticipations());

        $this->putDataIntoFile('first_deposits', $this->getFirstDeposits());

        $this->putDataIntoFile('pending_withdrawals', $this->getPendingWithdrawals());

        $this->putDataIntoFile('users_daily_stats', $this->getUsersDailyStats());

        $this->putDataIntoFile('users_daily_balance_stats', $this->getUsersDailyBalanceStats());

        $this->putDataIntoFile('users_game_sessions', $this->getUsersGameSessions());

        $this->putDataIntoFile('users_sessions', $this->getUsersSessions());

        $this->putDataIntoFile('users_settings', $this->getUsersSettings());

        $this->putDataIntoFile('bets', $this->getAllBets());

        $this->putDataIntoFile('wins', $this->getAllWins());

        $this->putDataIntoFile('users_changes_stats', $this->getUsersChangeStats());

        $this->putDataIntoFile('trophy_award_ownership', $this->getTrophyAwardOwnership());

        $this->putDataIntoFile('trophy_awards', $this->getTrophyAwards());

        $this->putDataIntoFile('rg_limits', $this->getRgLimits());

        $this->dropUsersTempTable();

    }

    protected function getUsersLimitPerShard(): int
    {
        return $this->users_limit_per_shard;
    }

    protected function getUsersTableTempName(): string
    {
        return 'temp_users';
    }

    protected function createUsersTableTemp(): void
    {
        $start_day = substr($this->start_date, 0, 10);
        $end_day = substr($this->end_date, 0, 10);

        $users_registered_between = $this->filter_users ?
            "
            AND u.register_date <= '{$end_day}'
            AND u.register_date >= '{$start_day}'
        " : "";

        $users_sql = "
            SELECT id, concat('user', id) AS username, register_date FROM users AS u
            WHERE country = '{$this->getCountry()}'
                AND u.id NOT IN (
                    SELECT user_id FROM users_settings AS u_s
                                   WHERE u_s.setting = 'registration_in_progress' AND u_s.value >= 1
                )
                {$users_registered_between}
            ORDER BY id
        ";

        if ($this->getUsersLimitPerShard()) {
            $users_sql .= "
                LIMIT {$this->getUsersLimitPerShard()}
            ";
        }

        try {
            $this->connection->statement(
                $this->connection->raw("
                    CREATE TEMPORARY TABLE {$this->getUsersTableTempName()}
                        AS ({$users_sql});
                ")
            );

            $this->has_users_temp_table = true;
        } catch (Exception $e) {
            $this->has_users_temp_table = false;
        }
    }

    protected function dropUsersTempTable(): void
    {
        if (
            $this->has_users_temp_table &&
            $this->connection->getSchemaBuilder()->hasTable($this->getUsersTableTempName())
        ) {
            $this->connection->statement(
                $this->connection->raw("DROP TEMPORARY TABLE {$this->getUsersTableTempName()};")
            );
        }
    }

    protected function getUsersQuery(): Builder
    {
        $start_day = substr($this->start_date, 0, 10);
        $end_day = substr($this->end_date, 0, 10);

        $users_registered_between = $this->filter_users ?
            "
            AND users.register_date <= '{$end_day}'
            AND users.register_date >= '{$start_day}'
        " : "";

        return $this->connection->table('users')
            ->whereRaw("
                users.country = '{$this->getCountry()}'
                AND users.id NOT IN (
                    SELECT user_id FROM users_settings AS u_s
                    WHERE u_s.setting = 'registration_in_progress' AND u_s.value >= 1
                )
                {$users_registered_between}"
            );
    }

    protected function getTrophyAwardOwnershipQuery(): Builder
    {
        return $this->connection->table('trophy_award_ownership')
            ->whereIn('user_id', $this->getUsersIdsBuilder())
            ->where(function (Builder $query) {
                $query
                    ->whereBetween('created_at', [$this->start_date, $this->end_date])
                    ->orWhereBetween('activated_at', [$this->start_date, $this->end_date])
                    ->orWhereBetween('expire_at', [$this->start_date, $this->end_date]);
            });
    }


    protected function getTrophyAwardOwnership(): Collection
    {
        return $this->getTrophyAwardOwnershipQuery()->get();
    }

    protected function getTrophyAwards(): Collection
    {
        return $this->connection->table('trophy_awards')
            ->whereIn('id', $this->getTrophyAwardOwnershipQuery()->select('award_id'))
            ->get();
    }

    protected function getRgLimits(): Collection
    {
        return $this->connection->table('rg_limits')
            ->whereIn('user_id', $this->getUsersIdsBuilder())
            ->where('type', 'deposit')
            ->get();
    }

    protected function generateShardFiles(int $shard): void
    {
        $shard_connection = DB::getConnectionsList()[DB::getNodesList()[$shard]];
        $this->processNode($shard_connection);

        foreach ($this->files as &$file) {
            fclose($file['handler']);
            unset($file['handler']);

            if ($this->target_dir) {
                $new_filename = $this->target_dir . '/' . basename($file['filename']);
                rename($file['filename'], $new_filename);
                $file['filename'] = $new_filename;
            }

        }
    }

    protected function setCmdLine(InputInterface $input): array
    {
        $executableFinder = new PhpExecutableFinder();
        if (false === $php = $executableFinder->find(false)) {
            $cmd = ['/bin/env php'];
        } else {
            $cmd = array_merge([$php], $executableFinder->findArguments());
        }

        $cmd = array_merge($cmd, [$_SERVER['SCRIPT_NAME']], array_filter(array_values($input->getArguments())));

        $options = array_filter($input->getOptions());
        $options['generate-for-shard'] = -1;

        foreach ($options as $k => $v) {
            $cmd[] = '--' . $k;
            $cmd[] = $v;
        }

        return $cmd;
    }

    protected function callShard(int $shard): Process
    {
        $cmd = $this->cmd_options;
        $cmd[count($cmd) - 1] = $shard;
        return new Process($cmd);
    }

    protected function removeDuplicatedArchiveData(array $data): array
    {
        return array_values(array_unique($data, SORT_REGULAR));
    }

}
