<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class RecreateDepositVsWager extends Migration
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

        DB::loopNodes(function ($connection) {
            $connection->table($this->table)
                ->where('name', '=', 'deposit_vs_wager')
                ->where('section', '=', 'AML')
                ->update(['type' => 'interval']);

            $connection->table($this->table)
                ->where('category', '=', 'deposit_vs_wager')
                ->where('section', '=', 'AML')
                ->delete();
        }, true);


        collect([
            ["0x - 4x", "0,4", 10],
            ["5x - 7x", "5,7", 6],
            ["8x - 11x", "8,11", 1],
            ["12x - 14x", "12,14", 6],
            ["15x - 18x", "15,18", 10],
            ["18+", "19", 10]
        ])->map(function ($el) {
                list($title, $name, $score) = $el;
                return [
                    "name" => $name,
                    "title" => $title,
                    "score" => $score,
                    "category" => 'deposit_vs_wager',
                    "section" => "AML"
                ];
            })
            ->tap(function ($data) {
                /** @var \Illuminate\Support\Collection $data */
                DB::bulkInsert($this->table, null, $data->toArray(), DB::getMasterConnection());
                DB::bulkInsert($this->table, null, $data->toArray());
                return $data;
            });

    }

    /**
     * @throws Exception
     */
    public function down()
    {

    }
}
