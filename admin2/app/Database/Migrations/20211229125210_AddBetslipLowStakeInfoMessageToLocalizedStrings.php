<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddBetslipLowStakeInfoMessageToLocalizedStrings extends Migration
{
    /** @var string */
    protected $table;
    protected $tableConnections;

    protected $connection;

    public function init()
    {
        $this->table = 'localized_strings';
        $this->tableConnections = 'localized_strings_connections';
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->connection
            ->table($this->table)->insert([
                'alias' => 'sb.betslip.stake_min_amount',
                'language' => 'en',
                'value' => 'Insert a minimum stake of at least {{globalCurrency}} {{minStake}}  to place your bet'
            ]);

        $this->connection
            ->table($this->tableConnections)->insert([
                'target_alias' => 'sb.betslip.stake_min_amount',
                'bonus_code' => 0,
                'tag' => 'sb.betslip'
            ]);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->tableConnections)
            ->where('target_alias', '=', 'sb.betslip.stake_min_amount')
            ->where('bonus_code', '=', 0)
            ->where('tag', '=', 'sb.betslip')
            ->delete();

        $this->connection
            ->table($this->table)
            ->where('alias', '=', 'sb.betslip.stake_min_amount')
            ->where('language', '=', 'en')
            ->where('value', '=', 'Insert a minimum stake of at least {{globalCurrency}} {{minStake}}  to place your bet')
            ->delete();
    }
}


