<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class FixUsersGameSessionsStartTime extends Migration
{
    /** @var string */
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'users_game_sessions';
        $this->schema = $this->get('schema');
    }


    /**
     * Do the migration
     */
    public function up()
    {
        DB::loopNodes(function ($connection) {
            $connection->statement("ALTER TABLE {$this->table} MODIFY COLUMN start_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
        }, false);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        DB::loopNodes(function ($connection) {
            $connection->statement("ALTER TABLE {$this->table} MODIFY COLUMN start_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        }, false);
    }
}
