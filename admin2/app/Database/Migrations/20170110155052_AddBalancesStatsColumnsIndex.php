<?php

use Phpmig\Migration\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddBalancesStatsColumnsIndex extends Migration
{
    protected $table;

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
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->index(['currency', 'date'], 'currency_date_idx');
            $table->index('user_id', 'user_id_idx');
        });

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropIndex('user_id_idx');
            $table->dropIndex('currency_date_idx');
        });
    }
}
