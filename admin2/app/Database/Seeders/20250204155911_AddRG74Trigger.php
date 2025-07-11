<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Trigger;

class AddRG74Trigger extends Seeder
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
                'name' => 'RG74',
                'indicator_name' => 'X spins within last Y hours',
                'description' => 'Customer played X spins last y hours',
                'color' => '#ffffff',
                'score' => 0,
            ]
        ];
        $bulkInsertInMasterAndShards($this->table, $triggers);
    }

    public function down()
    {
        Trigger::where('name', 'RG74')->delete();
    }

}
