<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;

class AddDeadHeatFactorColumnToSportTransactions extends Migration
{
    /** @var string */
    protected $table;

    /** @var \App\Extensions\Database\Schema\MysqlBuilder */
    protected $schema;

    public function init()
    {
        $this->table = 'sport_transaction_details';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        if (!$this->schema->hasColumn($this->table, 'dead_heat_factor')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->double('dead_heat_factor')->after('void_factor')->nullable();
            });
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {

        if ($this->schema->hasColumn($this->table, 'dead_heat_factor')) {
            $this->schema->table($this->table, function (Blueprint $table) {
                $table->asSharded();
                $table->dropColumn('dead_heat_factor');
            });
        }
    }
}
