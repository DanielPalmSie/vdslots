<?php

require_once __DIR__ . '/../../api/PhModule.php';
require_once __DIR__ . '/Archive/BackgroundProcessor.php';
require_once __DIR__ . '/Archive/ArchiveBase.php';

class Archive extends ArchiveBase
{
    /** @var string $migrations_table */
    protected string $migrations_table = 'migrations_backoffice';
    protected array $shards;

    /** @var array|string[] $tables */
    protected array $tables = [
        'wins' => 'created_at',
        'bets' => 'created_at',
        'bets_mp' => 'created_at',
        'wins_mp' => 'created_at',
        'tournaments' => 'created_at',
        'tournament_entries' => 'updated_at',
        'actions' => 'created_at',
        'external_audit_log' => 'created_at',
        'cash_transactions' => 'timestamp',
    ];

    /** @var array|string[] $table_index */
    protected array $table_index = [
        'external_audit_log' => 'id',
    ];

    public function getArchiveTables() {
        return $this->tables;
    }

    /** @var array|string[] $restricted Do not archive these tables if a custom date was not provided */
    protected array $restricted = [
        'actions',
        'external_audit_log',
        //'cash_transactions'
    ];

    /**
     * Main entry point for archiving
     *
     * @param array $args
     * @return void
     * @throws Exception
     */
    public function execute(array $args): void
    {
        $this->logger->trackProcess(['arguments' => $args]);

        [$end_date, $tables_filter, $tables_end_date, $skip_master] = $this->extractArguments($args);

        $this->shards = range($skip_master == true ? 0 : self::MASTER_DATABASE, count($this->sql->getShards()) - 1);

        $this->logger->info("Archiving started.", [
            'tables' => $this->tables,
            'end_date' => $end_date,
            'tables_filter' => $tables_filter,
            'tables_end_date' => $tables_end_date,
        ]);

        $this->logger->info("Databases to be affected (-1 = master, 0-9 shards): " . print_r($this->shards, true));


        if (in_array($this->migrations_table, $tables_filter)) {
            $this->createArchiveTable(self::MASTER_DATABASE, $this->migrations_table);

            $this->logger->info("Table {$this->migrations_table} was created on master database.");

            return;
        }

        $tables = $this->filterTables($tables_filter, $tables_end_date);

        foreach ($tables as $table) {
            $processes = array_map(static function ($node) use ($table) {
                return ['SQL/Archive', 'createArchiveTable', [$node, $table]];
            }, $this->shards);

            $this->executeInParallel($processes, function ($progress) use ($table) {
                $this->logger->info("Create table: {$table} on archive {$progress}%.");
            });
        }

        $processes = [];
        foreach ($tables as $table) {
            $processes = array_merge($processes, array_map(static function ($node) use ($table) {
                return ['SQL/Archive', 'detectSchemaChanges', [$node, $table]];
            }, $this->shards));
            $this->executeInParallel($processes, function ($progress) use ($table) {
                $this->logger->info("Detect schema changes table: {$table} {$progress}%.");
            });
        }


        $processes = array_map(static function ($node) {
            return ['SQL/Archive', 'syncTableWithSchema', [$node, 'micro_games']];
        }, $this->shards);
        $this->executeInParallel($processes, function ($progress) {
            $this->logger->info("Sync micro_games {$progress}%.");
        });

        foreach ($tables as $table) {
            $processes = [];
            foreach ($this->shards as $node) {
                if ($this->shouldSkipNode($table, $node)) {
                    continue;
                }
                $replica = $this->selectDB(self::DB_TYPE_REPLICA, $node);
                $table_end_date = array_key_exists($table, $tables_end_date) ? $tables_end_date[$table] : $end_date;
                $date_interval = $this->getInterval($replica, $table_end_date, $table, $this->tables[$table], $this->table_index[$table] ?? null);

                foreach ($date_interval as $day) {
                    $processes[] = ['SQL/Archive', 'executeArchiving', [$node, $table, $day]];
                    Phive()->miscCache("node-archive-end-date-{$table}", $day, true);
                }
            }
            $this->executeInParallel($processes, function ($progress) use ($table) {
                $this->logger->info("Archiving progress {$table} {$progress}%.");
            });
        }

        $this->logger->info("Archiving completed.");
    }

    /**
     * Stop archiving if schema changes are detected
     *
     * @param int $node
     * @param string $table
     * @return void
     * @throws Exception
     */
    public function detectSchemaChanges(int $node, string $table): void
    {
        if ($this->shouldSkipNode($table, $node)) {
            return;
        }
        $slave_node = $this->selectDB(self::DB_TYPE_REPLICA, $node);
        $archive_node = $this->selectDB(self::DB_TYPE_ARCHIVE, $node);
        $primary_shard = $this->selectDB(self::DB_TYPE_PRIMARY, $node);
        $archive_node->keep_last_error_message = true;
        $primary_shard->keep_last_error_message = true;

        if ($this->getSchemaForTable($archive_node, $table) !== $this->getSchemaForTable($slave_node, $table)) {
            $this->throwError("Schema changes detected on table {$table}.");
        }
    }

    /**
     * Copy data from replica to archive then delete on primary
     *
     * @param int $node
     * @param string $table
     * @param string $day
     * @return void
     * @throws Exception
     */
    public function executeArchiving(int $node, string $table, string $day): void
    {
        if ($this->shouldSkipNode($table, $node)) {
            return;
        }
        $slave_node = $this->selectDB(self::DB_TYPE_REPLICA, $node);
        $archive_node = $this->selectDB(self::DB_TYPE_ARCHIVE, $node);
        $primary_shard = $this->selectDB(self::DB_TYPE_PRIMARY, $node);
        $archive_node->keep_last_error_message = true;
        $primary_shard->keep_last_error_message = true;

        // stop archiving if schema changes are detected
        $this->detectSchemaChanges($node, $table);

        $day_column = $this->tables[$table];
        $table_index = $this->table_index[$table] ?? null;

        if ($table_index) {
            $this->archiveBasedOnDifferentIndex($slave_node, $archive_node, $primary_shard, $table, $day, $day_column, $table_index);
        } else {
            // $select_query = "SELECT * FROM {$table} WHERE {$day_column} BETWEEN '{$day} 00:00:00' and '{$day} 23:59:59'";
            $select_query = "SELECT * FROM `{$table}` WHERE `{$day_column}` BETWEEN '{$day} 00:00:00' AND '{$day} 23:59:59'";
            $primary_shard->deleteBatched($select_query, function ($row) use ($table, $primary_shard, $archive_node) {
                $this->archiveRow($row, $table, $primary_shard, $archive_node);
            });
        }
    }

    /**
     * @param SQL $slave_node
     * @param SQL $archive_node
     * @param SQL $primary_shard
     * @param string $id_column
     * @param string $date_column
     * @param string $table
     * @param string $target_date
     * @return void
     * @throws Exception
     */
    public function archiveBasedOnDifferentIndex(SQL $slave_node, SQL $archive_node, SQL $primary_shard, string $table, string $target_date, string $date_column, string $id_column)
    {
        // normalize target date to YYYY-MM-DD
        $target_date = phive()->toDate($target_date);

        if (!phive()->validateDate($target_date)) {
            $this->throwError("Invalid target date {$date_column}=[{$target_date}] provided to archive {$table},{$id_column}.", [
                $this->getIdentifier($slave_node)
            ]);
        }

        // go from min_id 1 by 1 and if the date is not the same as the day we are archiving, stop
        // $first_row = $slave_node->loadAssoc("SELECT {$id_column} FROM {$table} ORDER BY {$id_column} ASC LIMIT 1");
        $first_row = $slave_node->loadAssoc("SELECT `{$id_column}` FROM `{$table}` ORDER BY `{$id_column}` ASC LIMIT 1");
        if (empty($first_row) || empty($first_row[$id_column])) {
            $this->logger->info("No rows found for table {$table}.", [
                $first_row, $this->getIdentifier($slave_node)
            ]);
            return;
        }

        $current_id = $first_row[$id_column];
        // $max_id = $slave_node->loadAssoc("SELECT MAX({$id_column}) AS id FROM {$table}");
        $max_id = $slave_node->loadAssoc("SELECT MAX(`{$id_column}`) AS id FROM `{$table}`");
        if (empty($max_id) || empty($max_id['id'])) {
            $this->logger->info("No max_id found for table {$table}.", [
                $max_id, $this->getIdentifier($slave_node)
            ]);
            return;
        }

        do {
            // $row = $slave_node->loadAssoc("SELECT * FROM {$table} WHERE {$id_column} = '{$current_id}'");
            $row = $slave_node->loadAssoc("SELECT * FROM `{$table}` WHERE `{$id_column}` = '{$current_id}'");
            if (empty($row) || empty($row[$id_column])) {
                $current_id++;
                continue;
            }
            $current_date = $row[$date_column];
            $current_id = $row[$id_column];

            # stop the entire archiving when invalid date is found
            if (empty($current_date)) {
                $this->throwError("Empty date {$date_column} found for row with {$id_column}=[{$current_id}] in table {$table}.", [
                    $row, $this->getIdentifier($slave_node)
                ]);
            } else {
                $current_date = phive()->toDate($current_date);
            }
            # stop the entire archiving when invalid date is found
            if (!phive()->validateDate($current_date)) {
                $this->throwError("Invalid date {$date_column}=[{$current_date}] found for row with {$id_column}=[{$current_id}] in table {$table}.", [
                    $row, $this->getIdentifier($slave_node)
                ]);
            }

            $current_date = phive()->toDate($current_date);

            # ignore data which is not for the current day
            if (strtotime($current_date) < strtotime($target_date)) {
                continue;
            }

            # if $current_date is after $day, stop archiving because we are done with this day
            # convert dates to unix timestamps to compare them
            if (strtotime($current_date) > strtotime($target_date)) {
                break;
            }

            $this->archiveRow($row, $table, $primary_shard, $archive_node);

            $current_id++;
        } while ($current_id <= $max_id['id']);
    }

    /**
     * Ensure that the table exists on the archive node
     *
     * @param int $node
     * @param string $table
     * @return void
     * @throws Exception
     */
    public function createArchiveTable(int $node, string $table): void
    {
        if ($this->shouldSkipNode($table, $node)) {
            return;
        }
        $slave_node = $this->selectDB(self::DB_TYPE_REPLICA, $node);
        $archive_node = $this->selectDB(self::DB_TYPE_ARCHIVE, $node);

        if (!$slave_node->hasTable($table)) {
            $this->throwError("Table {$table} does not exist on slave.", [$this->getIdentifier($slave_node)]);
        }

        $this->copyTableSchema($slave_node, $archive_node, $table);
    }

    /**
     * Copy schema and data from slave to archive
     *
     * @param int $node
     * @param string $table
     * @return void
     * @throws Exception
     */
    public function syncTableWithSchema(int $node, string $table): void
    {
        if ($this->shouldSkipNode($table, $node)) {
            return;
        }
        $slave_node = $this->selectDB(self::DB_TYPE_REPLICA, $node);
        $archive_node = $this->selectDB(self::DB_TYPE_ARCHIVE, $node);

        $this->syncTableSchemaBetween($slave_node, $archive_node, $table);

        $this->safelyCopyRows($slave_node, $archive_node, $table, null, true);
    }

}
