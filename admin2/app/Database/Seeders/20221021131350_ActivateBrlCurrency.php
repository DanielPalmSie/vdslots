<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class ActivateBrlCurrency extends Seeder
{
    private Connection $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $this->connection->table('currencies')->where('code', '=', 'BRL')->update(['legacy' => 0]);
    }

    public function down()
    {
        $this->connection->table('currencies')->where('code', '=', 'BRL')->update(['legacy' => 1]);
    }
}
