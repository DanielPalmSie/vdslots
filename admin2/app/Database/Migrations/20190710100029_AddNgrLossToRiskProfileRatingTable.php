<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddNgrLossToRiskProfileRatingTable extends Migration
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
         * @param \Illuminate\Support\Collection $data
         * @return mixed
         */
        $bulkInsertInMasterAndShards = function ($data) {
            try {
                DB::bulkInsert($this->table, null, $data->toArray(), DB::getMasterConnection());
                DB::bulkInsert($this->table, null, $data->toArray());
            } catch (\Exception $e) {
                echo "\n {$e->getMessage()}";
            }

            return $data;
        };

        $main = collect([
            [
                "name" => $ngr_loss = "ngr_loss",
                "title" => "NGR Loss",
                "type" => "option",
                "section" => "RG",
                "data" => ""
            ]
        ]);
        $bulkInsertInMasterAndShards($main);

        collect([
            ["€0 - €9,999", "0,9999", 0],
            ["€10,000 - €19,999", "10000,19999", 7],
            ["€20,000+", "20000", 10]
        ])
            ->map(function ($el) use ($ngr_loss) {
                list($title, $name, $score) = $el;
                return [
                    "name" => $name,
                    "title" => $title,
                    "score" => $score,
                    "category" => $ngr_loss,
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
                ->where('name', '=', 'ngr_loss')
                ->orWhere('category', '=', 'ngr_loss')
                ->delete();
        }, true);
    }
}
