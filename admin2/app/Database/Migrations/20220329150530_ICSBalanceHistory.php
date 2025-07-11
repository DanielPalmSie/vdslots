<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Schema\MysqlBuilder;
use Phpmig\Migration\Migration;

class ICSBalanceHistory extends Migration
{
    private string $table = 'external_regulatory_user_balances';
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
        if (!$this->schema->hasTable($this->table)) {
            $this->schema->create($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->bigIncrements('id');
                $table->bigInteger('user_id');
                $table->date('balance_date');
                $table->integer('cash_balance');
                $table->integer('bonus_balance');
                $table->integer('extra_balance');
                $table->string('currency', 3);

                $table->index(['user_id', 'balance_date']);
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->dropIfExists($this->table);
    }
}
