<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddProfileToMobileMenu extends Migration
{
    private Connection $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        // Unique in the table.
        $parent_id = $this->connection->table('menus')->select('menu_id')
            ->where('alias', '=', 'mobile-account-menu')
            ->where('priority', '=', 197)
            ->value('menu_id');

        $mobile_as_parent_id = $this->connection->table('pages')->select('page_id')
            ->where('alias', '=', 'mobile')
            ->value('page_id');

        $link_page_id = $this->connection->table('pages')->select('page_id')
            ->where('alias', '=', 'account')
            ->where('parent_id', '=', $mobile_as_parent_id)
            ->value('page_id');

        $this->connection->table('menus')->insert([
            'parent_id' => $parent_id,
            'alias' => 'mobile-profile',
            'name' => '#profile',
            'priority' => 257,
            'link' => '',
            'link_page_id' => $link_page_id,
            'getvariables' => '[user/]profile',
            'new_window' => 0,
            'check_permission' => 0,
            'logged_in' => 1,
            'logged_out' => 0,
            'included_countries' => '',
            'excluded_countries' => '',
            'icon' => '',
        ]);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection->table('menus')
            ->where('alias', '=', 'mobile-profile')
            ->where('priority', '=', 257)
            ->delete();
    }
}