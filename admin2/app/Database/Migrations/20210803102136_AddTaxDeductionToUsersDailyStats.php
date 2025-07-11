<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class AddTaxDeductionToUsersDailyStats extends Migration
{
    /** @var string */
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'users_daily_stats';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'tax_deduction')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->migrateEverywhere();
                $table->bigInteger('tax_deduction')->default(0);
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if ($this->schema->hasColumn($this->table, 'tax_deduction')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->migrateEverywhere();
                $table->dropColumn('tax_deduction');
            });
        }
    }
}
