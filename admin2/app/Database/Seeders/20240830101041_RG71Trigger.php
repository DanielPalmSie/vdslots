<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Trigger;

class RG71Trigger extends Seeder
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
                'name' => 'RG71',
                'indicator_name' => 'Financial Vulnerability Check',
                'description' => 'Players who are found VULNERABLE in external registry - JUDGMENTS_ORDERS_FINES_REGISTER_MATCH',
                'color' => '#ffffff',
                'score' => 0,
                'ngr_threshold' => 0,
            ]
        ];
        $bulkInsertInMasterAndShards($this->table, $triggers);
    }

    public function down()
    {
        Trigger::where('name', 'RG71')->delete();
    }
}
