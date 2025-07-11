<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;
use App\Models\Config;

class AddLoginFrequencyRgFlag extends Migration
{
    private const CONFIG_TABLE = 'config';
    private const TRIGGERS_TABLE = 'triggers';

    protected $connection;

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
                "name" => "RG62",
                "indicator_name" => "Frequency of logins per week",
                "description" => "Frequency of logins per week > or equal to threshold for NL Players",
                "color" => "#00ff00",
                "score" => 0,
                "ngr_threshold" => 0
            ],
            [
                "name" => "RG63",
                "indicator_name" => "Frequency of logins per month",
                "description" => "Frequency of logins per month > or equal to threshold for NL Players",
                "color" => "#00ff00",
                "score" => 0,
                "ngr_threshold" => 0
            ],
        ];

        $bulkInsertInMasterAndShards(self::TRIGGERS_TABLE, $triggers);

        $configs = [
            [
                "config_name" => "RG62",
                "config_tag" => "RG",
                "config_value" => 0,
                "config_type" => "{\"type\":\"number\"}"
            ],
            [
                "config_name" => "RG63",
                "config_tag" => "RG",
                "config_value" => 0,
                "config_type" => "{\"type\":\"number\"}"
            ]
        ];

        foreach ($configs as $config) {
            Config::shs()->insert($config);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table(self::TRIGGERS_TABLE)
            ->where('name', '=', 'RG62')
            ->delete();

        $this->connection
            ->table(self::TRIGGERS_TABLE)
            ->where('name', '=', 'RG63')
            ->delete();

        $this->connection
            ->table(self::CONFIG_TABLE)
            ->where('config_name', '=', 'RG62')
            ->delete();

        $this->connection
            ->table(self::CONFIG_TABLE)
            ->where('config_name', '=', 'RG63')
            ->delete();
    }
}
