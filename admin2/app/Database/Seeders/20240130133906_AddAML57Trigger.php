<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Trigger;

class AddAML57Trigger extends Seeder
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
                'name' => 'AML57',
                'indicator_name' => '1k Single/Accumulated transaction using Paysafe/Flexepin/Neosurf/CashToCode',
                'description' => 'Singular or accumulated deposits of €1,000 or more within 30 days using Paysafecard, Flexipin, Neosurf or CashToCode',
                'color' => '#ffffff',
                'score' => 21,
            ]
        ];
        $bulkInsertInMasterAndShards($this->table, $triggers);
    }

    public function down()
    {
        Trigger::where('name', 'AML57')->delete();
    }
}