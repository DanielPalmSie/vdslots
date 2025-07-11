<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Trigger;

class AddRG78Trigger extends Seeder
{
    public function init()
    {
        $this->table = 'triggers';
    }

    public function up()
    {
        $bulkInsertInMasterAndShards = function ($table, $data) {
            DB::bulkInsert($table, null, $data, DB::getMasterConnection());
            DB::bulkInsert($table, null, $data, null, true);
        };

        $triggers = [
            [
                'name' => 'RG78',
                'indicator_name' => 'Top loss registered last Y month',
                'description' => 'Top X highest losing customers that have registered in the last Y months',
                'color' => '#ffffff',
                'score' => 0,
            ]
        ];
        $bulkInsertInMasterAndShards($this->table, $triggers);
    }

    public function down()
    {
        Trigger::where('name', 'RG78')->delete();
    }
}
