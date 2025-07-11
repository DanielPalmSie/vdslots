<?php

/**
 * ./console mig:up 20241009140437
 *
 * ./console mig:down 20241009140437
 */

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class AddNewColumnsRegardingAffiliatesOnUserDailyStatsSportsTable extends Migration
{

    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

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
        if ($this->schema->hasTable($this->table)) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->migrateEverywhere();
                $table->float('op_fee');
                $table->float('tax');
                $table->float('before_deal');
                $table->bigInteger('gross');
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
                $table->migrateEverywhere();
                $table->dropColumn('op_fee');
                $table->dropColumn('tax');
                $table->dropColumn('before_deal');
                $table->dropColumn('gross');
            });
        }
    }
}
