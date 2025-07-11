<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddKungaslottetToExcludedBrandsForSbMenu extends Seeder
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
            ->where('alias', 'LIKE', 'sportsbook%')
            ->orWhere('alias', 'mobile-secondary-top-menu-sports')
            ->update([
                'excluded_brands' => 'kungaslottet'
            ]);
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('alias', 'LIKE', 'sportsbook%')
            ->orWhere('alias', 'mobile-secondary-top-menu-sports')
            ->update([
                'excluded_brands' => null
            ]);
    }
}
