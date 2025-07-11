<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddOntarioToExcludedProvincesForMobileSbMenus extends Seeder
{
    private Connection $connection;
    private string $table;

    public function init()
    {
        $this->table = 'menus';
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $this->connection
            ->table($this->table)
            ->whereIn('alias', [
                'mobile-sports-betting-history',
                'mobile-secondary-top-menu-sports',
                'menu.secondary.sportsbook-live'
            ])
            ->update([
                'excluded_provinces' => 'CA-ON'
            ]);
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->whereIn('name', [
                'mobile-sports-betting-history',
                'mobile-secondary-top-menu-sports',
                'menu.secondary.sportsbook-live'
            ])
            ->where('excluded_provinces', 'LIKE', 'CA-ON')
            ->update([
                'excluded_provinces' => null
            ]);
    }
}