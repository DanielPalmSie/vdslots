<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class CreateUsersGameSessionsStats extends Migration
{
    /**
     * Do the migration
     */
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    /**
     * Do the migration
     */
    public function init()
    {
        $this->table = 'users_game_sessions_stats';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasTable($this->table)) {
            $this->schema->create($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->bigIncrements('id');
                $table->bigInteger('game_session_id')->index();
                $table->string('country', 5)->index();
                $table->string('game_version', 255);
                $table->integer('max_multiplier')->index();
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasTable($this->table)) {
            $this->schema->drop($this->table);
        }
    }
}

