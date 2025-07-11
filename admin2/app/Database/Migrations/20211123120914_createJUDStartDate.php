<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class CreateJUDStartDate extends Migration
{
    /** @var string */
    protected $table;

    /** @var Connection */
    protected $connection;

    public function init()
    {
        $this->table = 'external_regulatory_report_logs';
        $this->connection = DB::getMasterConnection();
    }


    /**
     * Do the migration
     */
    public function up()
    {
        $exists = $this->connection->table($this->table)->where('report_type', 'JUD')->exists();
        //we only need to create this line if there's no previous JUD report in the system
        if (!$exists) {
            $this->connection->table($this->table)->insert([
                'unique_id' => 'jud_start',
                'regulation' => 'startICS', //not ICS so it doesn't appear in verification reports
                'report_type' => 'JUD',
                'report_data_from' => '2021-11-23 12:00:00', //date of launch
                'report_data_to' => '2021-11-23 12:00:00',
                'sequence' => 0,
            ]);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection->table($this->table)->where('unique_id', 'jud_start')->delete();
    }
}
