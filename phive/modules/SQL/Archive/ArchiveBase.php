<?php

require_once __DIR__ . '/../../../api/PhModule.php';
require_once __DIR__ . '/BackgroundProcessor.php';
require_once __DIR__ . '/Exception/ArchiveException.php';

class ArchiveBase extends PhModule
{
    // node number to select master DB
    public const MASTER_DATABASE = -1;

    public const DB_TYPE_PRIMARY = 'primary';
    public const DB_TYPE_REPLICA = 'replica';
    public const DB_TYPE_ARCHIVE = 'archive';

    protected SQL $sql;
    protected Logger $logger;
    protected BackgroundProcessor $processor;


    public function __construct()
    {
        $this->sql = phive('SQL');
        $this->logger = phive('Logger')->getLogger('archive');
        $this->processor = new BackgroundProcessor();
    }

    /**
     * Allow the override of the logger instance required to add background process extra context
     *
     * @param Logger $logger
     * @return void
     */
    public function setLogger(Logger $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Helper to log error and throw exception
     *
     * @param string|Exception $msg
     * @param array $context
     * @return void
     * @throws Exception
     */
    public function throwError($msg, array $context = []): void
    {
        $context_string = "\nContext: " . json_encode([$context]);

        if ($msg instanceof Exception) {
            $this->logger->error($msg->getMessage(), [$context_string, $msg->getTraceAsString()]);
            throw $msg;
        }

        $this->logger->error($msg, $context);
        throw new ArchiveException($msg . $context_string);
    }

    /**
     * Get the interval between the oldest win and the provided end_date
     *
     * @param SQL $node
     * @param string $end_date
     * @param string $table
     * @param string $column
     * @param string|null $id_column
     * @return array
     * @throws Exception
     */
    public function getInterval(SQL $node, string $end_date, string $table, string $column, string $id_column = null): array
    {
        if (!phive()->validateDate($end_date)) {
            $this->throwError("Invalid end_date: {$end_date}");
        }

        $node_identifier = $this->getIdentifier($node);

        if ($id_column) {
            // $start_date_query = "SELECT {$column} FROM {$table} ORDER BY {$id_column} LIMIT 1";
            $start_date_query = "SELECT `{$column}` FROM `{$table}` ORDER BY `{$id_column}` LIMIT 1";
        } else {
            // $start_date_query = "SELECT {$column} FROM {$table} ORDER BY {$column} LIMIT 1";
            $start_date_query = "SELECT `{$column}` FROM `{$table}` ORDER BY `{$column}` LIMIT 1";
        }

        $this->logger->info("start_date_query");
        $this->logger->info($start_date_query);

        $start_date = $node->getValue($start_date_query);

        $this->logger->info("start_date 1:");
        $this->logger->info($start_date);

        if (empty($start_date)) {
            $this->logger->info("Start date is empty, nothing to do.", compact('start_date', 'end_date', 'node_identifier', 'table', 'column'));
            return [];
        }
        [$start_date] = explode(' ', $start_date);

        $this->logger->info("start_date 2:");
        $this->logger->info($start_date);

        if (!phive()->validateDate($start_date)) {
            $this->throwError("Invalid start_date: {$start_date}");
        }

        [$start_date, $end_date] = [phive()->fDate($start_date), phive()->fDate($end_date)];

        if (!phive()->validateDate($start_date) || !phive()->validateDate($end_date)) {
            $this->throwError("Invalid date.", compact('start_date', 'end_date'));
        }

        if ($start_date > $end_date) {
            $this->logger->info("Start date is larger than end date, nothing to do.", compact('start_date', 'end_date', 'node_identifier', 'table', 'column'));
            return [];
        }

        $interval_items = phive()->getDateInterval($start_date, $end_date);

        foreach ($interval_items as $index => $date) {
            if (empty($date) || $date < $start_date || $date > $end_date) {
                $this->logger->warning("The getDateInterval returned wrong dates.", compact('start_date', 'end_date', 'interval_items', 'node_identifier', 'table', 'column'));
                return [];
            }
            $interval_items[$index] = phive()->fDate($date);
        }

        return $interval_items;
    }

    /**
     * Execute method on all shards in parallel
     *
     * @param array $processes
     * @param Closure|null $progress_callback
     * @return void
     * @throws Exception
     */
    public function executeInParallel(array $processes = [], Closure $progress_callback = null): void
    {
        foreach ($processes as $p) {
            [$module, $method, $args] = $p;

            $this->processor->add($module, $method, $args);
        }

        try {
            $this->processor->runAllProcesses($this->sql->getSetting('archiving_max_parallel_processes'), $progress_callback);
        } catch (Exception $e) {
            $this->throwError($e->getMessage());
        }
    }

    /**
     * Synchronize schema between source_db and target_db
     *
     * @param SQL $source_db
     * @param SQL $target_db
     * @param string $table
     * @return void
     * @throws Exception
     */
    public function syncTableSchemaBetween(SQL $source_db, SQL $target_db, string $table): bool
    {
        $this->logger->info("Synchronizing schema for {$table}.");

        // required to get latest error message
        $target_db->keep_last_error_message = true;

        $suffix = '_' . date("YmdHis");
        $temp_table = $table . $suffix;
        $identifier = $this->getIdentifier($source_db, $target_db);

        if (!$source_db->hasTable($table)) {
            $this->throwError("Sync schema failed because {$table} does not exist on source db.", [$this->getIdentifier($source_db)]);
        }

        if ($this->copyTableSchema($source_db, $target_db, $table)) {
            $this->logger->debug($identifier . "New table was created so other checks are useless.");
            return true;
        }

        if ($this->getSchemaForTable($source_db, $table) === $this->getSchemaForTable($target_db, $table)) {
            $this->logger->debug($identifier . "Same schema on both databases.");
            return true;
        }

        // temp_table should be unique
        if ($target_db->hasTable($temp_table)) {
            $this->throwError("Temporary table {$temp_table} already exists.", compact('identifier'));
        }

        // create temporary $temp_table
        $this->copyTableSchema($source_db, $target_db, $table, $temp_table);

        // copy data from $table to $temp_table
        try {
            // $this->safelyCopyRows($target_db, $target_db, $temp_table, "SELECT * FROM {$table}");
            $this->safelyCopyRows($target_db, $target_db, $temp_table, "SELECT * FROM `{$table}`");
        } catch (Exception $e) {
            // $this->safeQuery($target_db, "DROP TABLE {$temp_table}");
            $this->safeQuery($target_db, "DROP TABLE `{$temp_table}`");
            $this->throwError("Temporary table {$temp_table} dropped.", compact('identifier'));
        }

        // copy data from $source to $table
        try {
            // $this->safelyCopyRows($source_db, $target_db, $temp_table, "SELECT * FROM {$table}", true);
            $this->safelyCopyRows($source_db, $target_db, $temp_table, "SELECT * FROM `{$table}`", true);
        } catch (Exception $e) {
            // if ($this->safeQuery($target_db, "DROP TABLE {$temp_table}")) {
            if ($this->safeQuery($target_db, "DROP TABLE `{$temp_table}`")) {
                $this->throwError("Temporary table {$temp_table} dropped.", compact('identifier'));
            } else {
                $this->throwError("Unable to drop temporary table {$temp_table}.", compact('identifier'));
            }
        }

        $backup_name = "{$table}_backup_{$suffix}";

        $this->logger->info("Start replacing {$table} with {$temp_table}. The backup name is {$backup_name}.", compact('identifier'));

        // if (!$this->safeQuery($target_db, "RENAME TABLE {$table} TO {$backup_name}")) {
        if (!$this->safeQuery($target_db, "RENAME TABLE `{$table}` TO `{$backup_name}`")) {
            $this->throwError("Renaming {$table} to {$backup_name} failed. This issue must be fixed before running the script again.", [
                'identifier' => $identifier,
                'error' => $target_db->last_error_message
            ]);
        }

        // if ($this->safeQuery($target_db, "RENAME TABLE {$temp_table} TO {$table}")) {
        if ($this->safeQuery($target_db, "RENAME TABLE `{$temp_table}` TO `{$table}`")) {
            $this->logger->info("Replaced {$table} with {$temp_table}. The backup name is {$backup_name}.", compact('identifier'));
            return true;
        }

        // rollback first rename
        // if (!$this->safeQuery($target_db, "RENAME TABLE {$backup_name} TO {$table}")) {
        if (!$this->safeQuery($target_db, "RENAME TABLE `{$backup_name}` TO `{$table}`")) {
            $this->throwError("Rollback failed to rename {$backup_name} to {$table} failed. Manual intervention is required.", [
                'identifier' => $identifier,
                'error' => $target_db->last_error_message
            ]);
        }

        $this->throwError("Renaming {$temp_table} to {$table} failed. Previous rename successfully rolled back.", [
            'identifier' => $identifier,
            'error' => $target_db->last_error_message
        ]);
    }

    public function safeQuery(SQL $db, string $q): bool
    {
        $db->keep_last_error_message = true;

        $success = $db->query($q);

        if (!empty($db->getHandle()->error)) {
            $db->last_error_message = $db->getHandle()->error;
        }

        if ($db->last_error_message) {
            $s = $success ? 'success' : 'error';
            $this->logger->error("[{$s}] {$q}", [
                'id' => $this->getIdentifier($db),
                'last_err' => $db->last_error_message
            ]);
        }

        if (!empty($db->last_error_message)) {
            $this->logger->error($db->last_error_message, [$this->getIdentifier($db), $q]);
            return false;
        }

        return $success;
    }

    /**
     * Get schema of the table in string format
     *
     * @param SQL $source
     * @param string $table
     * @return string
     */
    public function getSchemaForTable(SQL $source, string $table): string
    {
        $replacers = [
            'current_timestamp()' => 'current_timestamp'
        ];

        // $schema = array_map(static function ($field) {
        //     // Having index differences can be ignored because the raw data is still complete
        //     unset($field['Key']);
        //
        //     return $field;
        // }, $source->loadArray("SHOW COLUMNS FROM {$table}"));
        $schema = array_map(static function ($field) {
            // Having index differences can be ignored because the raw data is still complete
            unset($field['Key']);

            return $field;
        }, $source->loadArray("SHOW COLUMNS FROM `{$table}`"));

        $fields = array_column($schema, 'Field');

        array_multisort($fields, SORT_ASC, $schema);

        return str_replace(array_keys($replacers), array_values($replacers), strtolower(json_encode($schema)));
    }

    /**
     * Create empty table on target_db from source_db
     *
     * @param SQL $source_db
     * @param SQL $target_db
     * @param string $table
     * @param string|null $new_table
     * @return bool Indicates if new table was created
     * @throws Exception
     */
    public function copyTableSchema(SQL $source_db, SQL $target_db, string $table, string $new_table = null): bool
    {
        $target_db->keep_last_error_message = true;

        // $table already exists
        if (empty($new_table) && $target_db->hasTable($table)) {
            return false;
        }

        $engine = $this->sql->getSetting('archiving_storage_engine');
        // if (empty($target_db->loadAssoc("select * from information_schema.ENGINES where engine = '{$engine}'"))) {
        if (empty($target_db->loadAssoc("select * from information_schema.ENGINES where engine = '{$engine}'"))) {
            $this->throwError("Engine: {$engine} is not enabled on this database.", [$this->getIdentifier($target_db)]);
        }

        $create_table_query = $source_db->getCreate($table);
        if (empty($create_table_query)) {
            $this->throwError("The create table query is missing.", [
                $this->getIdentifier($source_db, $target_db), $target_db->last_error_message
            ]);
        }

        $create_table_query = str_replace("ENGINE=InnoDB", "ENGINE={$engine}", $create_table_query);

        if (!empty($new_table)) {
            $create_table_query = str_replace("CREATE TABLE `{$table}`", "CREATE TABLE `{$new_table}`", $create_table_query);
        }

        if (strtolower($engine) === 'rocksdb') {
            $create_table_query = 'SET STATEMENT rocksdb_bulk_load=1, sql_log_bin=0 FOR ' . $create_table_query;
        }

        $created = $this->safeQuery($target_db, $create_table_query);
        if (!$created && !$target_db->hasTable($table)) {
            $this->throwError("Error while creating table.", [
                $this->getIdentifier($target_db), $target_db->last_error_message, $create_table_query
            ]);
        }

        if (!empty($target_db->last_error_message)) {
            $this->throwError("Unknown error while creating table {$table}.", [
                $this->getIdentifier($source_db, $target_db),
                "error" => $target_db->last_error_message
            ]);
        }

        return true;
    }

    /**
     * Copy rows from source to target with complete error handling
     *
     * @param SQL $source_db
     * @param SQL $target_db
     * @param string $table
     * @param string|null $query
     * @param bool $replace
     * @return void
     * @throws Exception
     */
    public function safelyCopyRows(SQL $source_db, SQL $target_db, string $table, string $query = null, bool $replace = false): void
    {
        // required to get latest error message
        $target_db->keep_last_error_message = true;
        if (empty($query)) {
            // $query = "SELECT * FROM {$table}";
            $query = "SELECT * FROM `{$table}`";
        }

        $source_db->applyToRows($query, function ($row) use ($target_db, $table, $replace, $source_db, $query) {
            $success = $this->safeQuery($target_db, $target_db->getInsertSql($table, $row, null, $replace));
            $new_row_id = $target_db->getHandle()->insert_id;

            if (!$success || empty($new_row_id) || (int)$new_row_id !== (int)$row['id']) {
                $this->throwError("Unexpected behaviour while safely copying rows.", [
                    $this->getIdentifier($source_db, $target_db), $target_db->last_error_message, $new_row_id, $row, $query
                ]);
            }

            if (!empty($target_db->last_error_message)) {
                $this->throwError("Unable to insert row into {$table}.", [
                    $this->getIdentifier($source_db, $target_db), $target_db->last_error_message, $row, $query
                ]);
            }
        });
    }

    /**
     * Select the list of tables which should be archived
     *
     * @param $tables_filter
     * @param $tables_end_date
     * @return string[]
     */
    public function filterTables($tables_filter, $tables_end_date): array
    {
        return array_filter(array_keys($this->tables), function ($table) use ($tables_filter, $tables_end_date) {
            // intent to filter tables AND table must be in the provided list
            if (!empty($tables_filter) && !in_array($table, $tables_filter, true)) {
                return false;
            }

            $is_restricted_table = in_array($table, $this->restricted, true);
            $has_date_override = array_key_exists($table, $tables_end_date);

            if ($is_restricted_table && !$has_date_override) {
                $this->logger->warning("Skip table {$table} because it requires custom date to be provided.");
                return false;
            }

            return true;
        });
    }

    /**
     * Helper method to retrieve connection details
     *
     * @param SQL $source_db
     * @param SQL|null $target_db
     * @return string
     */
    public function getIdentifier(SQL $source_db, SQL $target_db = null): string
    {
        if (!$target_db) {
            return "Database: {$source_db->con_details['hostname']}:{$source_db->con_details['database']} ";
        }

        return "Source: {$source_db->con_details['hostname']}:{$source_db->con_details['database']}"
            . ", Target: {$target_db->con_details['hostname']}:{$target_db->con_details['database']}. ";
    }

    /**
     * Extract data from provided arguments list
     *
     * @param $args
     * @return array
     * @throws Exception
     */
    public function extractArguments($args): array
    {
        $end_date = $args[0];
        $tables_filter = [];
        $tables_end_date = [];
        $skip_master = false;

        array_shift($args);

        if (!phive()->isDate($end_date)) {
            $this->throwError("First argument must be valid end date.", compact('end_date'));
        }


        foreach ($args as $arg) {
            [$key, $provided_value] = explode('=', $arg);

            if (empty($key) || empty($provided_value)) {
                continue;
            }

            if ($key === 'only') {
                $tables_filter = explode(',', $provided_value);
            }

            if ($key === 'skipmaster') {
                $skip_master = true;
            }

            if (!empty($this->tables[$key])) {
                // detect if valid date was provided
                if (!phive()->isDate($provided_value)) {
                    $this->throwError("Invalid date provided for table {$key}.", compact('provided_value'));
                }

                $tables_end_date[$key] = $provided_value;
            }
        }

        return [$end_date, $tables_filter, $tables_end_date, $skip_master];
    }

    public function selectDB($key, $node = self::MASTER_DATABASE): SQL
    {
        $do_master = ($node === null || $node === self::MASTER_DATABASE);

        if ($key === self::DB_TYPE_PRIMARY) {
            $key = $do_master ? 'masterdb' : 'shards';
        } else if ($key === self::DB_TYPE_REPLICA) {
            $key = $do_master ? 'replica' : 'slave_shards';
        } else if ($key === self::DB_TYPE_ARCHIVE) {
            $key = $do_master ? 'archive' : 'shard_archives';
        }

        if ($key === 'masterdb') {
            $sql = new SQL([
                'username' => $this->sql->getSetting('username'),
                'password' => $this->sql->getSetting('password'),
                'hostname' => $this->sql->getSetting('hostname'),
                'database' => $this->sql->getSetting('database'),
            ]);
        } elseif ($do_master) {
            $sql = $this->sql->doDb($key);
        } else {
            $sql = $this->sql->doDb($key, $node);
        }

        $sql->setSetting('global_tables', []);
        $sql->setSetting('sharding_status', false);
        return $sql;
    }

    /**
     * Helper method detect if node should be skipped
     *
     * @param string $table
     * @param int $node
     * @return bool
     */
    public function shouldSkipNode(string $table, int $node): bool
    {
        $is_master_only_table = !phive('SQL')->isSharded($table) && !phive('SQL')->isGlobal($table);

        return $is_master_only_table && $node !== self::MASTER_DATABASE;
    }

    /**
     * @param $row
     * @param string $table
     * @param SQL $primary_shard
     * @param SQL $archive_node
     * @return void
     * @throws Exception
     */
    public function archiveRow($row, string $table, SQL $primary_shard, SQL $archive_node): void
    {
        if (!is_array($row) || empty($row) || empty($row['id'])) {
            $this->throwError("Unable to fetch row from {$table}.", [
                $primary_shard->last_error_message, $this->getIdentifier($primary_shard), $row
            ]);
        }

        $saved = $archive_node->save($table, $row);
        if (!$saved) {
            $this->throwError("Unable to save row with id {$row['id']} to archive {$table}.", [
                $archive_node->last_error_message, $this->getIdentifier($archive_node), $row
            ]);
        }

        // $item = $archive_node->loadAssoc("SELECT id FROM {$table} where id = {$row['id']}");
        $item = $archive_node->loadAssoc("SELECT id FROM `{$table}` WHERE id = {$row['id']}");
        if (!empty($archive_node->last_error_message)) {
            $this->throwError("Unable to fetch row from archive {$table}.", [
                $archive_node->last_error_message, $this->getIdentifier($archive_node), $row
            ]);
        }

        if (empty($item) || empty($item['id'])) {
            $this->throwError("Abort because this row did not exist in the archive.", [
                $this->getIdentifier($primary_shard, $archive_node), $row
            ]);
        }

        // We delete on the primary if the row exists in the archive
        // $deleted = $this->safeQuery($primary_shard, "DELETE FROM {$table} WHERE id = {$item['id']}");
        $deleted = $this->safeQuery($primary_shard, "DELETE FROM `{$table}` WHERE id = {$item['id']}");
        if (!$deleted || !empty($primary_shard->last_error_message)) {
            $this->throwError("Unable to delete row from live {$table}.", [
                $this->getIdentifier($primary_shard), $primary_shard->last_error_message, $row
            ]);
        }
    }
}
