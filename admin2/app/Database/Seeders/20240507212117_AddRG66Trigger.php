<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Trigger;

class AddRG66Trigger extends Seeder
{
    public function init()
    {
        $this->table = 'triggers';
    }

    public function up()
    {
        $bulkInsertInMasterAndShards = function ($table, $data) {
            DB::bulkInsert($table, null, $data, DB::getMasterConnection());
            DB::bulkInsert($table, null, $data);
        };

        $triggers = [
            [
                'name' => 'RG66',
                'indicator_name' => 'X Net deposit within last Y days',
                'description' => 'Customer has Net Deposit of €1500 within the last 30 days',
                'color' => '#ffffff',
                'score' => 0,
            ]
        ];
        $bulkInsertInMasterAndShards($this->table, $triggers);
    }

    public function down()
    {
        Trigger::where('name', 'RG66')->delete();
    }
}
