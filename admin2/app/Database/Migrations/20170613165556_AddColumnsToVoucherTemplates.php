<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Schema\Blueprint;

class AddColumnsToVoucherTemplates extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'voucher_templates';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asMaster();
            $table->string('expire_time');

            $table->unsignedInteger('deposit_amount');
            $table->string('deposit_start');
            $table->string('deposit_end');
            $table->string('deposit_method');

            $table->unsignedInteger('wager_amount');
            $table->string('wager_start');
            $table->string('wager_end');

            $table->string('games');
            $table->string('game_operators');

        });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->dropColumn('expire_time');

            $table->dropColumn('deposit_amount');
            $table->dropColumn('deposit_start');
            $table->dropColumn('deposit_end');
            $table->dropColumn('deposit_method');

            $table->dropColumn('wager_amount');
            $table->dropColumn('wager_start');
            $table->dropColumn('wager_end');

            $table->dropColumn('games');
            $table->dropColumn('game_operators');

        });
    }
}