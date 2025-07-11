<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class UpdateWinBoosterLinkAlias extends Seeder
{

    private Connection $connection;
    private string $tableMenus;

    public function init()
    {
        $this->tableMenus = 'menus';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
         $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', 'my-rainbow-reasure')
            ->update([
                'alias' => 'my-rainbow-treasure'
            ]);
    }

    public function down()
    {
        $this->connection
            ->table($this->tableMenus)
            ->where('alias', '=', 'my-rainbow-treasure')
            ->update([
                'alias' => 'my-rainbow-reasure'
            ]);
    }
}
