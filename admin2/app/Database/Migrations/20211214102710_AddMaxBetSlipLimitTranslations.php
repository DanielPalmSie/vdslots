<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddMaxBetSlipLimitTranslations extends Migration
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
                'alias' => 'sb.maxbetslip.limit',
                'language' => 'en',
                'value' => 'Betslip Full: Maximum {{maxVal}} selections already made'
            ]);

        $this->connection
            ->table($this->tableConnections)->insert([
                'target_alias' => 'sb.maxbetslip.limit',
                'bonus_code' => 0,
                'tag' => 'sb'
            ]);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->tableConnections)
            ->where('target_alias', '=', 'sb.maxbetslip.limit')
            ->where('bonus_code', '=', 0)
            ->where('tag', '=', 'sb')
            ->delete();

        $this->connection
            ->table($this->table)
            ->where('alias', '=', 'sb.maxbetslip.limit')
            ->where('language', '=', 'en')
            ->delete();
    }
}