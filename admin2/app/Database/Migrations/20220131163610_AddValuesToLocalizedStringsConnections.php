<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddValuesToLocalizedStringsConnections extends Migration
{
    /** @var string */
    protected $table;

    /** @var Connection */
    protected $connection;

    public function init()
    {
        $this->table = 'localized_strings_connections';
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->connection
            ->table($this->table)->insert([
                'target_alias' => 'err.lowbalance',
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
            ->table($this->table)
            ->where('target_alias', '=', 'err.lowbalance')
            ->where('bonus_code', '=', 0)
            ->where('tag', '=', 'sb.betslip')
            ->delete();
    }
}


