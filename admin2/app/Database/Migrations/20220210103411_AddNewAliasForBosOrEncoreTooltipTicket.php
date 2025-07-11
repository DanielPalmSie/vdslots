<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;
use App\Extensions\Database\Connection\Connection;

class AddNewAliasForBosOrEncoreTooltipTicket extends Migration
{
    private string $table = 'localized_strings';
    private Connection $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $exists = $this->connection->table($this->table)
            ->where('alias', '=','rewardtype.mp-ticket-tooltip')
            ->where('language', '=','en')
            ->first();

            if (!$exists) {
                $this->connection->table($this->table)
                    ->insert([
                        'alias' => 'rewardtype.mp-ticket-tooltip',
                        'language' => 'en',
                        'value' => 'EUR {{phDiv|{{amount}}|100}} Battle ticket. Cannot be used as a re-buy.'
                    ]);
            }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection->table($this->table)
            ->where('alias', '=', 'rewardtype.mp-ticket-tooltip')
            ->where('language', '=', 'en')
            ->delete();
    }
}
