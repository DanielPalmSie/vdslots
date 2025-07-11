<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Trigger;

class RG82Trigger extends Seeder
{

    public function init()
    {
        $this->table = 'triggers';
        $this->trigger_name = 'RG82';
    }

    public function up()
    {
        $bulkInsertInMasterAndShards = function ($table, $data) {
            DB::bulkInsert($table, null, $data, DB::getMasterConnection());
            DB::bulkInsert($table, null, $data);
        };

        $description = "Customer is top X customers who registered at any time with the highest amount of time spent on site in the last Y days.";

        $triggers = [
            [
                'name' => $this->trigger_name,
                'indicator_name' => 'Top X time spent in last Y days',
                'description' => $description,
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
