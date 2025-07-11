<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class CreateRealTimeStats extends Migration
{
    /** @var string */
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'users_realtime_stats';
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
                $table->date('date');
                $table->bigInteger('user_id');
                $table->integer('bets')->default(0);
                $table->integer('wins')->default(0);
                $table->integer('bet_count')->default(0);
                $table->integer('win_count')->default(0);
                $table->integer('deposits')->default(0);
                $table->integer('withdrawals')->default(0);
                $table->integer('rewards')->default(0);
                $table->integer('fails')->default(0);
                $table->integer('jp_contrib')->default(0);
                $table->integer('frb_wins')->default(0);
                $table->integer('tournaments')->default(0);
                $table->integer('bets_rollback')->default(0);
                $table->integer('wins_rollback')->default(0);

                $table->unique(['date', 'user_id']);
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
                $this->schema->drop($this->table);
            });
        }
    }
}
