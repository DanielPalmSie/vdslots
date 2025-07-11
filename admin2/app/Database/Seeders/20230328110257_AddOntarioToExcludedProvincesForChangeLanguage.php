<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddOntarioToExcludedProvincesForChangeLanguage extends Seeder
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
            ->where('alias', 'LIKE', 'change-languages')
            ->update([
                'excluded_provinces' => 'CA-ON'
            ]);
    }

    public function down()
    {
        $this->connection
            ->table($this->table)
            ->where('alias', 'LIKE', 'change-languages')
            ->where('excluded_provinces', 'LIKE', 'CA-ON')
            ->update([
                'excluded_provinces' => null
            ]);
    }
}