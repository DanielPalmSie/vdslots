<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Schema\MysqlBuilder;
use Phpmig\Migration\Migration;

class ICSBalanceHistoryCurrency extends Migration
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
        if ($this->schema->hasTable($this->table)) {
            $this->schema->table($this->table, static function (Blueprint $table) {
                //need space for bonus names
                $table->string('currency', 20)->change();
                $table->index(['user_id', 'currency', 'balance_date'], 'erub_user_currency_date');
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasTable($this->table)) {
            $this->schema->table($this->table, static function (Blueprint $table) {
                $table->string('currency', 3)->change();
                $table->dropIndex('erub_user_currency_date');
            });
        }
    }
}
