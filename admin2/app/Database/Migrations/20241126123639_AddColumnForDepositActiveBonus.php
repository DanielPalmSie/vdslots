<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddColumnForDepositActiveBonus extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'bonus_types';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table
                ->tinyInteger('deposit_active_bonus')
                ->default(0)
                ->after('forfeit_bonus')
                ->comment('0 - Deposit Active Bonus button not visible, 1 - Deposit Active Bonus button visible');
        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('deposit_active_bonus');
        });
    }
}
