<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Trigger;

class AddRg77Trigger extends Seeder
{

    public function init()
    {
        $this->table = 'triggers';
        $this->trigger_name = 'RG77';
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
                'indicator_name' => 'Top X highest depositing customers that have registered in the last Y months',
                'description' => 'Customer is top X customers who registered in the last Y months',
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
