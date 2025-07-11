<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddGermanyToExcludedCountriesForChangeLanguageMenu extends Seeder
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
            ->where('alias', 'change-languages')
            ->update([
                'excluded_countries' => 'DE'
            ]);
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('alias', 'change-languages')
            ->where('excluded_countries', 'LIKE', 'DE')
            ->update([
                'excluded_countries' => null
            ]);
    }
}