<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 5/10/17
 * Time: 2:29 PM
 */

namespace App\Extensions\Database\Schema;

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\ArchiveFManager as ArchiveDB;
use Daursu\ZeroDowntimeMigration\Connections\BaseConnection;
use DomainException;
use Illuminate\Database\Schema\Blueprint as BaseBlueprint;
use Illuminate\Database\Schema\Grammars\Grammar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Process\Exception\RuntimeException;

class Blueprint extends BaseBlueprint
{
    /** @var  string $type Table type on the shard config */
    protected $type;

    /** @var bool $reuse_commands Avoids commands duplication */
    protected $reuse_commands = false;

    /**
     * @var bool
     */
    private bool $use_non_block_alter = false;

    /**
     *  Set the table as Master on creation
     */
    public function asMaster()
    {
        $this->type = 'master';
    }

    /**
     *  Set the table as Global on creation
     */
    public function asGlobal()
    {
        $this->type = 'global';
    }

    /**
     *  Set the table as Sharded on creation
     */
    public function asSharded()
    {
        $this->type = 'sharded';
    }

    /**
     *  This is to be used on migrations which have to be run on nodes+master but are not targeting a global table
     */
    public function migrateEverywhere()
    {
        $this->type = 'migrate-everywhere';
    }

    /**
     * Use percona-toolkit or similar to make a non-blocking ALTER TABLE
     * ignored if the config is empty
     *
     * @param bool $enable
     * @return void
     */
    public function useNonBlockAlter(bool $enable = true): void
    {
        $this->use_non_block_alter = $enable && DB::isAlterConnectionSet();
    }

    /**
     * Execute the blueprint against the database.
     *
     * @param Connection $connection
     * @param Grammar $grammar
     * @return void
     * @throws \Exception
     */
    public function build($connection, $grammar)
    {
        $this->validate();
        $this->checkAlterToolAvailability($connection);

        if ($this->use_non_block_alter) {
            $required_space_result = [];
            $required_space = function (Connection $conn) use (&$required_space_result) {
                $output = new ConsoleOutput();
                $output->writeln(" ==  <info>Retrieving required disk space for {$conn->getName()}:{$conn->getDatabaseName()}:{$this->table}</info>");
                $required_space_result[] = (array)$conn->select("
                SELECT table_schema AS db,
                       table_name AS t,
                       CONCAT(ROUND(((data_length+index_length) / 1024 / 1024), 2), 'MB') sizeMB,
                       :host AS `host`
                FROM information_schema.tables
                WHERE table_name = :table AND table_schema = :schema;
            ",
                    [
                        ':host'   => $conn->getName(),
                        ':table'  => $this->table,
                        ':schema' => $conn->getDatabaseName(),
                    ]
                )[0];
            };

            $this->runCommands($required_space, $connection);

            $this->confirmMigration($required_space_result);
        }

        $migration = function (Connection $conn) use ($grammar) {
            if ($this->use_non_block_alter) {
                $conn = DB::getAlterConnection($conn);
            }
            $output = new ConsoleOutput();
            $start_time = microtime(true);

            $output->writeln(" ==  <info>Migrating {$conn->getConfig('host')}:{$conn->getConfig('database')}</info>");
            foreach ($this->toSql($conn, $grammar) as $statement) {
                if (is_subclass_of($conn, BaseConnection::class)) {
                    //if an affected column has a default value that we are not changing,
                    //the quotes will get doubled, and it will fail when we pass it via the tool
                    $statement = str_replace("''", "'", $statement);
                }

                if ($_SERVER['only_archive_databases']) {
                    $statement = 'set STATEMENT rocksdb_bulk_load=1, sql_log_bin=0 FOR ' . $statement;

                    $output->writeln(" ==  <info>{$conn->getName()} migrating {$statement}</info>");
                }

                $conn->statement($statement);
            }
            $time = round((microtime(true) - $start_time), 4);
            $output->writeln(" ==  <info>{$conn->getName()} migrated {$time}s</info>");
            $this->reuse_commands = true;
        };
        $this->runCommands($migration, $connection);
    }

    /**
     * Asks for confirmation, since the table must be copied
     *
     * @param array $items
     * @return void
     */
    protected function confirmMigration(array $items): void
    {
        $output = new ConsoleOutput();
        $q = "\n<info>This migration will be done using a schema change tool. \nThis requires to create a copy of the table.</info>\nMinimum disk space required: \n";
        foreach ($items as $item) {
            $q .= "{$item['host']}:{$item['db']}:{$item['t']} - {$item['sizeMB']} \n";
        }

        $output->write($q . "<question>Are you sure there is enough disk space available to perform this action?</question>\nType 'yes' to continue: \n");
        $line = fgets(STDIN);
        if (trim($line) !== 'yes') {
            $output->writeln("<error>ABORTING!</error>");
            die();
        }
        $output->writeln("\nThank you, continuing...");
    }


    protected function runCommands(callable $callback, $connection): void
    {
        if (!DB::getShardingStatus()) {
            // Shards are disabled, we do only master
            $callback($connection);
        } elseif ($this->type === 'migrate-everywhere') {
            DB::loopNodes($callback, true);
        } elseif (DB::isSharded($this->getTable())) {
            // Here we need to loop archive nodes when conn is archive, not defualt only
            if ($connection->getConfig('is_archive') === true) {
                ArchiveDB::loopNodes($callback);
            } else {
                DB::loopNodes($callback);
            }
        } elseif (DB::isGlobal($this->getTable())) {
            // Here we need to loop archive nodes when conn is archive, not defualt only
            if ($connection->getConfig('is_archive') === true) {
                ArchiveDB::loopNodes($callback, true);
            } else {
                DB::loopNodes($callback, true);
            }
        } else {
            //only on master
            $callback($connection);
        }
    }

    /**
     * @param Connection $connection
     * @return void
     */
    protected function checkAlterToolAvailability(Connection $connection): void
    {
        if ($this->use_non_block_alter) {

            DB::getAlterConnection($connection)->pretend(function (\Illuminate\Database\Connection $c) {
                try {
                    $c->statement('ALTER TABLE faketable ADD COLUMN none int');
                } catch (RuntimeException $e) {
                    //we are currently assuming that the external tools are called with symfony/process, so we use this \Symfony\Component\Process\Process::$exitCodes
                    if (str_contains($e->getMessage(), 'Exit Code: 127(')) {
                        if (env('APP_ENV') === 'dev') {
                            //on dev environments, not having the tool installed is just a warning, on prod it will break
                            $output = new ConsoleOutput();
                            $output->writeln('<comment>WARNING: configured driver could not be found</comment> Continuing with default migration');
                            $this->use_non_block_alter = false;
                        } else {
                            throw $e;
                        }
                    }
                }
            });
        }
    }


    protected function addImpliedCommands(Grammar $grammar)
    {
        if ($this->reuse_commands === true) { //this is to avoid commands piling up from previous connection when inside the loop
            return;
        }

        parent::addImpliedCommands($grammar);
    }

    private function validate()
    {
        if ($this->creating() && DB::getShardingStatus()) {
            $help_msg = "Hint: as you are creating a new table you must specify whether that table will be sharded, global or only at the master. See: " . __NAMESPACE__ . "/" . get_class($this);
            if (empty($this->type)) {
                throw new DomainException("Blueprint config error: table '{$this->getTable()}' type is not defined. {$help_msg}");
            }

            if (!in_array($this->type, ['master', 'global', 'sharded', 'migrate-everywhere'])) {
                throw new DomainException("Blueprint config error: table '{$this->getTable()}' type is not supported. {$help_msg}");
            }

            $msg = "Blueprint config error: table '{$this->getTable()}' type  is set to {$this->type} but not configured like that on the local config file. Hint: table type must be configured at config/local.php";
            if (
                ($this->type === 'global' && !DB::isGlobal($this->getTable())) ||
                ($this->type === 'sharded' && !DB::isSharded($this->getTable())) ||
                ($this->type === 'master' && (DB::isGlobal($this->getTable()) || DB::isSharded($this->getTable())))
            ) {
                throw new DomainException($msg);
            }

        }
    }
}
