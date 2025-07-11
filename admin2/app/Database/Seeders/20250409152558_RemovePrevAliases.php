<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class RemovePrevAliases extends Seeder
{
    private Connection $connection;

    private string $table;

    private string $connectionsTable;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'localized_strings';
        $this->connectionsTable = 'localized_strings_connections';
    }

    public function up()
    {
        $this->connection
            ->table($this->table)
            ->where('alias', 'like', '%.prev')
            ->delete();

        $this->connection
            ->table($this->connectionsTable)
            ->where('target_alias', 'like', '%.prev')
            ->delete();
    }
}
