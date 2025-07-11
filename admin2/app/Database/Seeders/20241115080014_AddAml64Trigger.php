<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddAml64Trigger extends Seeder
{
    private const TRIGGERS_TABLE = 'triggers';

    private Connection $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $bulkInsertInMasterAndShards = function ($table, $data) {
            DB::bulkInsert($table, null, $data, $this->connection);
            DB::bulkInsert($table, null, $data);
        };

        $triggers = [
            [
                "name" => "AML64",
                "indicator_name" => "NID Mismatch",
                "description" => "Player used an account linked to a different NID than the one used during registration.",
            ]
        ];
        $bulkInsertInMasterAndShards(self::TRIGGERS_TABLE, $triggers);
    }

    public function down()
    {
        $this->connection
            ->table(self::TRIGGERS_TABLE)
            ->where('name', '=', 'AML64')
            ->delete();
    }
}
