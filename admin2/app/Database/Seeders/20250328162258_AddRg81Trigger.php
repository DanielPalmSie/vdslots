<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Trigger;

class AddRg81Trigger extends Seeder
{

    public function init()
    {
        $this->table = 'triggers';
        $this->trigger_name = 'RG81';
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
                'indicator_name' => 'Highest unique bets last Y days',
                'description' => 'Customer is top X customers who registered at any time with the highest number of unique bets in the last Y days',
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
