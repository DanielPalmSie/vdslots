<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;


class UpdateAdmin2JurisdictionString extends Migration
{
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
                    'config_value' => '{"all":"","gb":"AND country = \'GB\'","mt":"AND country = \'MT\'","se":"AND country = \'SE\'","dk":"AND country = \'DK\'","it":"AND country = \'IT\'","ca-on":"AND country = \'CA\' AND province = \'ON\'","mga":"AND (country NOT IN (\'GB\',\'SE\', \'DK\', \'IT\', \'ES\', \'CA\') OR (country = \'CA\' AND province != \'ON\'))","mga sportsbook":"AND country NOT IN (\'GB\',\'SE\', \'DK\', \'IT\', \'ES\')","mt sportsbook":"AND country = \'MT\'","se sportsbook":"AND country = \'SE\'","gb sportsbook":"AND country = \'GB\'"}'
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
                    'config_value' => '{"all":"","gb":"AND country = \'GB\'","mt":"AND country = \'MT\'","se":"AND country = \'SE\'","dk":"AND country = \'DK\'","it":"AND country = \'IT\'","ca-on":"AND country = \'CA\' AND province = \'ON\'","mga":"AND country NOT IN (\'GB\',\'SE\', \'DK\', \'IT\', \'ES\', \'CA\') OR (country = \'CA\' AND province != \'ON\')","mga sportsbook":"AND country NOT IN (\'GB\',\'SE\', \'DK\', \'IT\', \'ES\')","mt sportsbook":"AND country = \'MT\'","se sportsbook":"AND country = \'SE\'","gb sportsbook":"AND country = \'GB\'"}'
                ]
            );
    }
}
