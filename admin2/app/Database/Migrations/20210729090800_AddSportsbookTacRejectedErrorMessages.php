<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddSportsbookTacRejectedErrorMessages extends Migration
{
    private $localizedStringsTable;
    private $localizedStringsConnectionTable;
    private $alias;

    /** @var Connection */
    private $connection;

    public function init()
    {
        $this->localizedStringsTable = 'localized_strings';
        $this->localizedStringsConnectionTable = 'localized_strings_connections';
        $this->alias = 'sb.tac_rejected_error';

        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->connection->table($this->localizedStringsConnectionTable)->insert(
            [
                'target_alias' => $this->alias,
                'bonus_code' => 0,
                'tag' => 'sb'
            ]
        );

        $this->connection->table($this->localizedStringsTable)->insert(
            [
                'alias' => $this->alias,
                'language' => 'en',
                'value' => 'You are prevented from betting and making any deposit until you confirm our sports book 
                 specific rules. You can do that in your profile, under the section \'Edit Profile\'.'
            ]
        );
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->localizedStringsConnectionTable)
            ->where('target_alias', '=', $this->alias)
            ->delete();

        $this->connection
            ->table($this->localizedStringsTable)
            ->where('alias', '=', $this->alias)
            ->delete();
    }
}
