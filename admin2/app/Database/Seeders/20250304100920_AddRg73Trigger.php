<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Trigger;

class AddRg73Trigger extends Seeder
{

    public function init()
    {
        $this->table = 'triggers';
        $this->trigger_name = 'RG73';
    }

    public function up()
    {
        $bulkInsertInMasterAndShards = function ($table, $data) {
            DB::bulkInsert($table, null, $data, DB::getMasterConnection());
            DB::bulkInsert($table, null, $data);
        };

        $triggers = [
            [
                'name' => $this->trigger_name,
                'indicator_name' => 'X hours played in last Y hours',
                'description' => 'Customer played X number of hours during last y hours',
                'color' => '#ffffff',
                'score' => 0,
            ]
        ];

        $bulkInsertInMasterAndShards($this->table, $triggers);
    }

    public function down()
    {
        Trigger::where('name', $this->trigger_name)->delete();
    }
}
