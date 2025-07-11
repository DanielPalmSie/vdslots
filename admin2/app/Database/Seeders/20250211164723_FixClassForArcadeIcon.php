<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class FixClassForArcadeIcon extends Seeder
{

    private Connection $connection;
    private string $table;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'menus';
    }

    public function up()
    {
        $this->connection
            ->table($this->table)
            ->where('icon', 'icon-vs-arcade')
            ->update(['icon' => 'icon-arcade']);
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('icon', 'icon-arcade')
            ->update(['icon' => 'icon-vs-arcade']);
    }
}
