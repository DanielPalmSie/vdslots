<?php

use App\Extensions\Database\FManager as DB;
use Illuminate\Support\Collection;
use Phpmig\Migration\Migration;

class AddGameplayTimeIntervalToRiskProfileRatingTable extends Migration
{
    protected $table;
    protected $schema;
    protected $rpr_name = 'gameplay_time_interval';

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
            try {
                DB::bulkInsert($this->table, null, $data->toArray(), DB::getMasterConnection());
                DB::bulkInsert($this->table, null, $data->toArray());
            } catch (Exception $e) {
                echo "\n {$e->getMessage()}";
            }

            return $data;
        };

        $main = collect([
            [
                "name" => $this->rpr_name,
                "title" => "Gameplay time interval",
                "type" => "option",
                "section" => "RG",
                "data" => ""
            ]
        ]);
        $bulkInsertInMasterAndShards($main);

        collect([
            ["05:00 - 00:59", "05:00:00,00:59:59", 0],
            ["01:00 - 04:59", "01:00:00,04:59:59", 0]
        ])
            ->map(function ($el) {
                list($title, $name, $score) = $el;
                return [
                    "name" => $name,
                    "title" => $title,
                    "score" => $score,
                    "category" => $this->rpr_name,
                    "section" => "RG"
                ];
            })
            ->tap($bulkInsertInMasterAndShards);

    }

    /**
     * @throws Exception
     */
    public function down()
    {
        DB::loopNodes(function ($connection) {
            $connection->table($this->table)
                ->where('name', '=', $this->rpr_name)
                ->orWhere('category', '=', $this->rpr_name)
                ->delete();
        }, true);
    }
}
