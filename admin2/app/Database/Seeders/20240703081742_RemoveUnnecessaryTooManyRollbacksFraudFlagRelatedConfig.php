<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class RemoveUnnecessaryTooManyRollbacksFraudFlagRelatedConfig extends Seeder
{
    private Connection $connection;
    private string $table;

    public function init()
    {
        $this->table = 'config';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        parent::up();

        $this->connection->table($this->table)
            ->where('config_name', '=', 'number-of-hours-for-rollbacks')
            ->where('config_tag', '=', 'withdrawal-flags')
            ->delete();
    }
}
