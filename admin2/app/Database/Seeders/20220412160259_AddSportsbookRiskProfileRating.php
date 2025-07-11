<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddSportsbookRiskProfileRating extends Seeder
{
    public function init()
    {
        $this->table = 'risk_profile_rating';
    }

    public function up()
    {
        $bulkInsertInMasterAndShards = function ($table, $data) {
            DB::bulkInsert($table, null, $data, DB::getMasterConnection());
            DB::bulkInsert($table, null, $data);
        };

        $risk_profile_rating_sportsbook = [
            [
                'name' => 'sportsbook',
                'title' => 'Sportsbook',
                'score' => 0,
                'category' => 'game_type',
                'section' => 'AML',
            ]
        ];

        $bulkInsertInMasterAndShards($this->table, $risk_profile_rating_sportsbook);
    }

    /**
     * @throws Exception
     */
    public function down()
    {
        DB::loopNodes(function ($connection) {
            $connection->table($this->table)
                ->where('name', '=', 'sportsbook')
                ->where('category', '=', 'game_type')
                ->delete();
        }, true);
    }
}