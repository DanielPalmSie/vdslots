<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class MakeExtSessionIdNullableToExtGameSessions extends Migration
{
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'ext_game_sessions';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if ($this->schema->hasColumn($this->table, 'ext_session_id')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->string('ext_session_id')->nullable()->change();
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'ext_session_id')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->string('ext_session_id')->nullable(false)->change();
            });
        }
    }
}
