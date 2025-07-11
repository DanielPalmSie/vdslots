<?php
use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class RemovePrevFromLocalizedStringsConnectionsTable extends Migration
{
    private Connection $connection;
    private string $table;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'localized_strings_connections';
    }

    public function up()
    {
        $this->connection->table($this->table)
            ->where('target_alias', 'LIKE', '%.prev')
            ->delete();
    }
}
