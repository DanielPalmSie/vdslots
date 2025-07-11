<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class ClearMicroJpsTable extends Seeder
{

    /** @var DB */
    private $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $this->connection->table('micro_jps')->truncate();
    }
}