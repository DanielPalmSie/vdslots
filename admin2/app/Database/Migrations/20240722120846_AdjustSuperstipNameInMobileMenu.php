<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AdjustSuperstipNameInMobileMenu extends Migration
{
    /** @var Connection */
    protected $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $this->connection->table('menus')->where('alias', 'mobile-secondary-top-menu-poolx')->update(['name' => 'menu.secondary.poolx']);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->connection->table('menus')->where('alias', 'mobile-secondary-top-menu-poolx')->update(['name' => '#menu.secondary.poolx']);
    }
}
