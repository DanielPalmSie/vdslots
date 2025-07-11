<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class AddExtraBalanceToUsersDailyBalanceStatsTable extends Migration
{
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'users_daily_balance_stats';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'extra_balance')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asGlobal();
                $table->bigInteger('extra_balance')->default(0);
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'extra_balance')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asGlobal();
                $table->dropColumn('extra_balance');
            });
        }
    }
}
