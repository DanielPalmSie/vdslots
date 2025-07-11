<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class SportsbookMobileRedirects extends Seeder
{
    private Connection $connection;

    private string $table;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'start_go';
    }

    public function up()
    {
        $this->connection
            ->table($this->table)
            ->insert([
                [
                    'from' => '/sports/prematch/',
                    'to' => '/mobile/sports/prematch/',
                ],
                [
                    'from' => '/sports/live/',
                    'to' => '/mobile/sports/live/',
                ],
            ]);
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('from', '/sports/prematch/')
            ->delete();

        $this->connection
            ->table($this->table)
            ->where('from', '/sports/live/')
            ->delete();
    }
}
