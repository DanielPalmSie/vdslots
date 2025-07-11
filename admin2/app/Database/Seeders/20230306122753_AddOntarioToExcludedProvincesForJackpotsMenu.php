<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddOntarioToExcludedProvincesForJackpotsMenu extends Seeder
{
    private Connection $connection;
    private string $menuTable;
    private string $pagesTable;
    private string $link;

    public function init()
    {
        $this->menuTable = 'menus';
        $this->pagesTable = 'pages';
        $this->link = 'page_id';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $this->connection
            ->table('menus')
            ->whereIn('link_page_id', $this->getJackpotPages())
            ->update([
                'excluded_provinces' => 'CA-ON'
            ]);
    }

    private function getJackpotPages(): array
    {
        return $this->connection
            ->table($this->pagesTable)
            ->select($this->link)
            ->where('cached_path', 'like', '%jackpot%')
            ->pluck($this->link)
            ->toArray();
    }

    public function down()
    {
        $this->connection
            ->table($this->menuTable)
            ->whereIn('link_page_id', $this->getJackpotPages())
            ->where('excluded_provinces', 'LIKE', 'CA-ON')
            ->update([
                'excluded_provinces' => null
            ]);
    }
}