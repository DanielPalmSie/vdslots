<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Trigger;

class AddRG65Trigger extends Seeder
{
    private string $table;

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
                'name' => 'RG65',
                'indicator_name' => 'Intensive Gambler',
                'description' => 'Customer has exceeded NET loss limit set by regulator for 3 consecutive weeks',
                'color' => '#ffffff',
                'score' => 0,
                'ngr_threshold' => 0,
            ]
        ];
        $bulkInsertInMasterAndShards($this->table, $triggers);
    }

    public function down()
    {
        Trigger::where('name', 'RG65')->delete();
    }
}
