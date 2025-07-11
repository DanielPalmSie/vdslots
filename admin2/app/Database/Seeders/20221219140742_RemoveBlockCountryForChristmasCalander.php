<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;

class RemoveBlockCountryForChristmasCalander extends Seeder
{

    private string $tableMenus;
    private Connection $connection;

    public function init()
    {

        $this->tableMenus = 'menus';
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {

        /*
        |--------------------------------------------------------------------------
        | Menu record process
        |--------------------------------------------------------------------------
        */
         $this->connection
            ->table($this->tableMenus)
            ->where('menu_id', 409)
            ->where('parent_id', 257)
            ->where('alias', 'xmas')
            ->where('name', '#mobile.menu.xmas')
            ->where('link_page_id', 914)
            ->where('check_permission', 0)
            ->update([
                'excluded_countries' => '',
            ]);

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection
            ->table($this->tableMenus)
            ->where('menu_id', 409)
            ->where('parent_id', 257)
            ->where('alias', 'xmas')
            ->where('name', '#mobile.menu.xmas')
            ->where('link_page_id', 914)
            ->where('check_permission', 0)
            ->update([
                'excluded_countries' => 'ES',
            ]);

    }

}