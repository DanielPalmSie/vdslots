<?php

use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;
use App\Models\Trigger;

class AddRG67Trigger extends Seeder
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
                'name' => 'RG67',
                'indicator_name' => 'RG67 User is top loser',
                'description' => 'Customer is top loser',
                'color' => '#ffffff',
                'score' => 0,
            ]
        ];
        $bulkInsertInMasterAndShards($this->table, $triggers);
    }

    public function down()
    {
        Trigger::where('name', 'RG67')->delete();
    }
}
