<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddBetslipInfoOnMultiBetTabSelection extends Migration
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
        $exist = $this->connection
            ->table($this->table)
            ->where('alias', '=', 'sb.betslip.info_min_multi_bet_selection')
            ->where('language', '=', 'en')
            ->first();

        if (!empty($exist)) {
            return;
        }

        $this->connection
            ->table($this->table)->insert([
                'alias' => 'sb.betslip.info_min_multi_bet_selection',
                'language' => 'en',
                'value' => 'Place a bet by clicking on 2 on more market outcomes.'
            ]);

        $this->connection
            ->table($this->tableConnections)->insert([
                'target_alias' => 'sb.betslip.info_min_multi_bet_selection',
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
            ->where('target_alias', '=', 'sb.betslip.info_min_multi_bet_selection')
            ->where('bonus_code', '=', 0)
            ->where('tag', '=', 'sb.betslip')
            ->delete();

        $this->connection
            ->table($this->table)
            ->where('alias', '=', 'sb.betslip.info_min_multi_bet_selection')
            ->where('language', '=', 'en')
            ->delete();
    }
}