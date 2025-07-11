<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddHalloweenLinksToMobileMenus extends Seeder
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
        $mobile_halloween_page = $this->getMobileHalloweenPage();
        $mobile_menu = $this->getMobileMenu();
        $mobile_profile_menu = $this->getMobileProfileMenu();

        if (empty($mobile_halloween_page) || empty($mobile_menu) || empty($mobile_profile_menu)) {
            return;
        }

        $halloween_menu_name = '#mobile.menu.halloween';

        $this->connection
            ->table($this->menus_table)
            ->insert([
                [
                    'parent_id' => $mobile_menu->menu_id,
                    'alias' => 'halloween',
                    'name' => $halloween_menu_name,
                    'priority' => 204,
                    'link_page_id' => $mobile_halloween_page->page_id,
                    'link' => '',
                    'getvariables' => '',
                    'new_window' => 0,
                    'check_permission' => 0,
                    'logged_in' => 0,
                    'logged_out' => 1,
                    'included_countries' => '',
                    'excluded_countries' => '',
                    'excluded_provinces' => null,
                    'icon' => ''
                ]
            ]);

        $this->connection
            ->table($this->menus_table)
            ->where('name', $halloween_menu_name)
            ->where('parent_id', $mobile_profile_menu->menu_id)
            ->update([
                'logged_out' => 0,
            ]);
    }

    public function down()
    {
        $mobile_halloween_page = $this->getMobileHalloweenPage();
        $mobile_menu = $this->getMobileMenu();
        $mobile_profile_menu = $this->getMobileProfileMenu();

        if (empty($mobile_halloween_page) || empty($mobile_menu) || empty($mobile_profile_menu)) {
            return;
        }

        $halloween_menu_name = '#mobile.menu.halloween';

        $this->connection
            ->table($this->menus_table)
            ->where('name', $halloween_menu_name)
            ->where('parent_id', $mobile_menu->menu_id)
            ->delete();

        $this->connection
            ->table($this->menus_table)
            ->where('name', $halloween_menu_name)
            ->where('parent_id', $mobile_profile_menu->menu_id)
            ->update([
                'logged_out' => 1,
            ]);
    }

    private function getMobileHalloweenPage()
    {
        $mobile_halloween_page_path = '/mobile/halloween';

        return $this->connection
            ->table($this->pages_table)
            ->where('cached_path', $mobile_halloween_page_path)
            ->first();
    }

    private function getMobileMenu()
    {
        $mobile_menu_name = 'Mobile Menu';

        return $this->connection
            ->table($this->menus_table)
            ->where('name', $mobile_menu_name)
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
