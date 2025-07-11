<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddBonusTypeColumnToExtGameSessions extends Migration
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
        if (!$this->schema->hasColumn($this->table, 'bonus_type')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->char('bonus_type', 1)
                    ->nullable(true)
                    ->after('ext_game_id');

                $table->index('bonus_type');
                $table->index('status', 'ext_game_sessions_status_index');
                $table->index('created_at', 'ext_game_sessions_created_at_index');
                $table->index('ended_at', 'ext_game_sessions_ended_at_index');
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'bonus_type')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->dropColumn('bonus_type');
                $table->dropIndex('ext_game_sessions_status_index');
                $table->dropIndex('ext_game_sessions_created_at_index');
                $table->dropIndex('ext_game_sessions_ended_at_index');
            });
        }
    }
}
