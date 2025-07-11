<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class RemoveEasterHuntFromMenu extends Seeder
{

    private Connection $connection;

    private string $menus_table = 'menus';
    private string $pages_table = 'pages';

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $mobile_easter_page = $this->getMobileEasterPage();
        $mobile_profile_menu = $this->getMobileProfileMenu();

        if (empty($mobile_easter_page) || empty($mobile_profile_menu)) {
            return;
        }

        $easter_menu_name = '#mobile.menu.easter';

        $this->connection
            ->table($this->menus_table)
            ->where('name', $easter_menu_name)
            ->where('parent_id', $mobile_profile_menu->menu_id)
            ->delete();
    }

    private function getMobileEasterPage()
    {
        $mobile_easter_page_path = '/mobile/easter-hunt';

        return $this->connection
            ->table($this->pages_table)
            ->where('cached_path', $mobile_easter_page_path)
            ->first();
    }

    private function getMobileProfileMenu()
    {
        $mobile__profile_menu_name = 'Mobile Profile Menu';

        return $this->connection
            ->table($this->menus_table)
            ->where('name', $mobile__profile_menu_name)
            ->first();
    }
}
