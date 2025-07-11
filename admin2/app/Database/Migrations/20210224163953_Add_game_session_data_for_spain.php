<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddGameSessionDataForSpain extends Migration
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
        $this->table = 'ext_game_participations';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'reminder')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->string('reminder', 10)->default('');
            });
        }

        if (!$this->schema->hasColumn($this->table, 'time_limit')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->integer('time_limit')->default(0);
            });
        }

        if (!$this->schema->hasColumn($this->table, 'limit_future_session_for')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->integer('limit_future_session_for')->default(0);
            });
        }

        if (!$this->schema->hasColumn($this->table, 'restrict_future_session')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->tinyInteger('restrict_future_session')->default(0);
            });
        }

        if (!$this->schema->hasColumn($this->table, 'participation_id')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->string('participation_id')->default('')->change();
            });
        }

        if (!$this->schema->hasColumn($this->table, 'ext_game_id')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->string('ext_game_id')->default('')->change();
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'reminder')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('reminder');
            });
        }

        if ($this->schema->hasColumn($this->table, 'time_limit')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('time_limit');
            });
        }

        if ($this->schema->hasColumn($this->table, 'limit_future_session_for')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('limit_future_session_for');
            });
        }

        if ($this->schema->hasColumn($this->table, 'restrict_future_session')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('restrict_future_session');
            });
        }
    }
}
