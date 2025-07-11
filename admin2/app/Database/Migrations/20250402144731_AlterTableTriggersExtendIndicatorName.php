<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class AlterTableTriggersExtendIndicatorName extends Migration
{
    protected $table;

    public function init()
    {
        $this->table = 'triggers';
    }


    /**
     * Do the migration
     */
    public function up()
    {
        DB::loopNodes(function ($connection) {
            $connection->statement("ALTER TABLE {$this->table} MODIFY indicator_name varchar(64)");
        }, true);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        DB::loopNodes(function ($connection) {
            $connection->statement("ALTER TABLE {$this->table} MODIFY indicator_name varchar(32)");
        }, true);
    }
}
