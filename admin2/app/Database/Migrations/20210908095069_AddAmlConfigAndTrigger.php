<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddAmlConfigAndTrigger extends Migration
{
    private const CONFIG_TABLE = 'config';
    private const TRIGGERS_TABLE = 'triggers';

    /** @var Connection */
    private $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $bulkInsertInMasterAndShards = function ($table, $data) {
            DB::bulkInsert($table, null, $data, DB::getMasterConnection());
            DB::bulkInsert($table, null, $data);
        };

        $triggers = [
            [
                "name" => "AML55",
                "indicator_name" => "36k Deposit",
                "description" => "Deposit is > or = to €36,000 in singular transaction or accumulated ",
                "color" => "#ff8000",
                "score" => 21,
                "ngr_threshold" => 0
            ]
        ];
        $bulkInsertInMasterAndShards(self::TRIGGERS_TABLE, $triggers);

        $configs = [
            [
                "config_name" => "AML55-value",
                "config_tag" => "AML",
                "config_value" => "3600000",
                "config_type" => "{\"type\":\"number\"}"
            ],
            [
                "config_name" => "AML55-timeframe",
                "config_tag" => "AML",
                "config_value" => 8760,//year in hours
                "config_type" => "{\"type\":\"number\"}"
            ]
        ];
        $bulkInsertInMasterAndShards(self::CONFIG_TABLE, $configs);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table(self::TRIGGERS_TABLE)
            ->where('name', '=', 'AML55')
            ->delete();

        $this->connection
            ->table(self::CONFIG_TABLE)
            ->where('config_name', '=', 'AML55-value')
            ->delete();

        $this->connection
            ->table(self::CONFIG_TABLE)
            ->where('config_name', '=', 'AML55-timeframe')
            ->delete();
    }
}
