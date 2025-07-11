<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddMTSportsbookToAdminJurisdiction extends Migration
{
    /** @var Connection */
    private $connection;

    private $table;

    public function init()
    {

        $this->table = 'config';
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->connection
            ->table($this->table)
            ->where(['config_name' => 'admin2.jurisdiction'])
            ->update(
                [
                    'config_value' => '{"all":"","mt":"AND country = \'MT\'","mga":"AND country NOT IN (\'GB\', \'SE\', \'DK\')","se":"AND country = \'SE\'","dk":"AND country = \'DK\'","gb":"AND country = \'GB\'", "mga":"AND country NOT IN (\'GB\', \'SE\', \'DK\')", "mga sportsbook":"AND country NOT IN (\'GB\', \'SE\', \'DK\')", "mt sportsbook":"AND country = \'MT\'"}'
                ]
            );
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where(['config_name' => 'admin2.jurisdiction'])
            ->update(
                [
                    'config_value' => '{"all":"","mt":"AND country = \'MT\'","mga":"AND country NOT IN (\'GB\', \'SE\', \'DK\')","se":"AND country = \'SE\'","dk":"AND country = \'DK\'","gb":"AND country = \'GB\'", "mga":"AND country NOT IN (\'GB\', \'SE\', \'DK\')", "mga sportsbook":"AND country NOT IN (\'GB\', \'SE\', \'DK\')"}'
                ]
            );
    }
}
