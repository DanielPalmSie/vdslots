<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Trigger;

class AddAML59Trigger extends Seeder
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
                'name' => 'AML59',
                'indicator_name' => '1k or more in bonus payouts',
                'description' => 'Got a bonus payout >= €1000(configurable), accounting for >= 9%(configurable) of their total wager amount in the last 90 days.',
                'color' => '#ffffff',
                'score' => 59
            ]
        ];
        $bulkInsertInMasterAndShards($this->table, $triggers);
    }

    public function down()
    {
        Trigger::where('name', 'AML59')->delete();
    }
}