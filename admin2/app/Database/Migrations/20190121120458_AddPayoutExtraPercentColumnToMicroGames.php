<?php

use App\Extensions\Database\Schema\Blueprint;
use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class AddPayoutExtraPercentColumnToMicroGames extends Migration
{
    protected $table;
    protected $schema;

    public function init()
    {
        $this->table = 'micro_games';
        $this->schema = $this->get('schema');
    }

    /**
     * Do the migration
     */
    public function up()
    {
        DB::loopNodes(function (\App\Extensions\Database\Connection\Connection $connection) {
            $connection->statement("ALTER TABLE `micro_games` ADD `payout_extra_percent` FLOAT NOT NULL DEFAULT '0' AFTER `blocked_logged_out`;");
        }, true);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->schema->table($this->table, function (Blueprint $table) {
            $table->asGlobal();
            $table->dropColumn('payout_extra_percent');
        });
    }
}
