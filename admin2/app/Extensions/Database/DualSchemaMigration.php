<?php

namespace App\Extensions\Database;

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\FManager as DB;

/**
 * Base migration to apply schema changes to both main and archive databases.
 */
abstract class DualSchemaMigration extends Migration
{
    protected $table;
    protected $schema;
    protected $archived_tables = [];

    public function init()
    {
        $this->table = $this->defineTable();
        $this->archived_tables = array_keys(phive('SQL/Archive')->getArchiveTables());
        //$this->schema = $this->get('schema');
    }

    /**
     * Define the table this migration affects
     */
    abstract protected function defineTable();

    /**
     * Determine if this table should be archived
     */
    protected function shouldArchive()
    {
        return in_array($this->table, $this->archived_tables);
    }

    /**
     * Run the migration
     */
    public function up()
    {
        echo "Applying migration to main master and shards for table {$this->table}\n";
        $archive_master = DB::getMasterConnection();
        $archive_master->getSchemaBuilder()->table($this->table, function (Blueprint $table) {
            $this->upChanges($table);
        });

        // If this table should be archived, apply to archive databases
        if ($this->shouldArchive()) {
            // We're in archive mode - apply to archive master
            echo "Applying migration to archive master and shards for table {$this->table}\n";
            $archive_master = ArchiveManager::getMasterConnection();
            $archive_master->getSchemaBuilder()->table($this->table, function (Blueprint $table) {
               $this->upChanges($table);
            });
        } else {
            echo "Table not in the archive list: {$this->table}\n";
        }
    }

    /**
     * Roll back the migration
     */
    public function down()
    {
        echo "Applying migration to main master and shards for table {$this->table}\n";
        $archive_master = DB::getMasterConnection();
        $archive_master->getSchemaBuilder()->table($this->table, function (Blueprint $table) {
            $this->downChanges($table);
        });

        // If this table should be archived, apply to archive databases
        if ($this->shouldArchive()) {
            // We're in archive mode - apply to archive master
            echo "Applying migration to archive master and shards for table {$this->table}\n";
            $archive_master = ArchiveManager::getMasterConnection();
            $archive_master->getSchemaBuilder()->table($this->table, function (Blueprint $table) {
                $this->downChanges($table);
            });
        } else {
            echo "Table not in the archive list: {$this->table}\n";
        }
    }

    /**
     * Define the changes to apply in the up() migration
     */
    abstract protected function upChanges(Blueprint $table);

    /**
     * Define the changes to apply in the down() migration
     */
    abstract protected function downChanges(Blueprint $table);
}
