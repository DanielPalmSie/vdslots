<?php

use App\Extensions\Database\FManager as DB;
use Illuminate\Support\Collection;
use Phpmig\Migration\Migration;

class AddAgeToRiskProfileRatingTable extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'risk_profile_rating';
        $this->schema = $this->get('schema');
    }

    /**
     * @throws Exception
     */
    public function up()
    {
        /**
         * @param Collection $data
         * @return mixed
         */
        $bulkInsertInMasterAndShards = function ($data) {
            DB::bulkInsert($this->table, null, $data->toArray(), DB::getMasterConnection());
            if (DB::getShardingStatus()) {
                DB::bulkInsert($this->table, null, $data->toArray());
            }
            return $data;
        };

        $main = collect([
            [
                "name" => $age = "age",
                "title" => "Age",
                "type" => "interval",
                "section" => "AML",
                "data" => ""
            ],
            [
                "name" => $age = "age",
                "title" => "Age",
                "type" => "interval",
                "section" => "RG",
                "data" => ""
            ],
        ]);
        $bulkInsertInMasterAndShards($main);
    }

    /**
     * @throws Exception
     */
    public function down()
    {
        DB::loopNodes(function ($connection) {
            $connection->table($this->table)
                ->where('name', '=', 'age')
                ->orWhere('category', '=', 'age')
                ->delete();
        }, true);
    }
}
