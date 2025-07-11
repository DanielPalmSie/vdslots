<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\FManager as DB;

class AddCorruptedStatusToBonusEntriesStatusEnum extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'bonus_entries';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->schema->hasTable($this->table)) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                DB::loopNodes(function (\App\Extensions\Database\Connection\Connection $connection) {
                    $connection->statement("ALTER TABLE `{$this->table}` MODIFY COLUMN `status` ENUM('pending', 'active', 'approved', 'completed', 'failed', 'corrupted') DEFAULT 'pending' NOT NULL");
                }, true);
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasTable($this->table)) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                DB::loopNodes(function (\App\Extensions\Database\Connection\Connection $connection) {
                    $connection->statement("ALTER TABLE `{$this->table}` MODIFY COLUMN `status` ENUM('pending', 'active', 'approved', 'completed', 'failed') DEFAULT 'pending' NOT NULL");
                }, true);
            });
        }
    }
}
