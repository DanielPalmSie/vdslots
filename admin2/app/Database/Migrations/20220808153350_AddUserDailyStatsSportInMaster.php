<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddUserDailyStatsSportInMaster extends Migration
{
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    /**
     * init
     *
     * @return void
     */
    public function init()
    {
        $this->table = 'users_daily_stats_sports';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasTable($this->table)) {
            $this->schema->create($this->table, function (Blueprint $table) {
                $table->asMaster();
                $table->bigIncrements('id');
                $table->bigInteger('user_id');
                $table->bigInteger('bets')->default(0);
                $table->bigInteger('wins')->default(0);
                $table->bigInteger('void')->default(0);
                $table->date('date');
                $table->string('currency', 3);
                $table->string('country', 3);
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
