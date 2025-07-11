<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Trigger;

class AddNewAML62Trigger extends Seeder
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
                'name' => 'AML62',
                'indicator_name' => 'High-Risk Behaviour with Prepaid Cards',
                'description' => 'An user deposits using prepaid cards and withdraws without wagering in any product',
                'color' => '#ffffff',
                'score' => 21,
            ]
        ];
        $bulkInsertInMasterAndShards($this->table, $triggers);
    }

    public function down()
    {
        Trigger::where('name', 'AML62')->delete();
    }
}