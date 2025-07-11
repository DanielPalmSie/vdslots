<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Schema\MysqlBuilder;
use Phpmig\Migration\Migration;

class UsersBalanceStats extends Migration
{
    private string $table = 'users_daily_balance_stats';
    protected MysqlBuilder $schema;

    public function init()
    {
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function(Blueprint $table){
            $table->migrateEverywhere();
            $table->index(['user_id', 'date', 'source']);
            $table->dropIndex('user_id_idx'); //dropping the one created by default
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function(Blueprint $table){
            $table->migrateEverywhere();
            $table->index(['user_id'], 'user_id_idx'); //restoring previous name
            $table->dropIndex(['user_id', 'date', 'source']);
        });
    }
}
