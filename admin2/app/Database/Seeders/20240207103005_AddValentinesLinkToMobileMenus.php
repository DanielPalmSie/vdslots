<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class AddValentinesLinkToMobileMenus extends Seeder
{
    private Connection $connection;

    private string $menus_table = "menus";
    private string $pages_table = "pages";

    private string $mobile_page_path = '/mobile/valentines';
    private string $menu_name = '#mobile.menu.valentines';

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $mobile_valentines_page = $this->getMobileValentinesPage();
        $mobile_menu = $this->getMobileMenu();
        $mobile_profile_menu = $this->getMobileProfileMenu();

        if (empty($mobile_valentines_page) || empty($mobile_menu) || empty($mobile_profile_menu)) {
            return;
        }

        $this->connection
            ->table($this->menus_table)
            ->insert([
                [
                    'parent_id' => $mobile_menu->menu_id,
                    'alias' => 'valentines',
                    'name' => $this->menu_name,
                    'priority' => 204,
                    'link_page_id' => $mobile_valentines_page->page_id,
                    'link' => '',
                    'getvariables' => '',
                    'new_window' => 0,
                    'check_permission' => 0,
                    'logged_in' => 0,
                    'logged_out' => 1,
                    'included_countries' => '',
                    'excluded_countries' => 'SE',
                    'excluded_provinces' => null,
                    'icon' => ''
                ]
            ]);

        $this->connection
            ->table($this->menus_table)
            ->where('name', $this->menu_name)
            ->where('parent_id', $mobile_profile_menu->menu_id)
            ->update([
                'logged_out' => 0,
                'excluded_countries' => 'SE',
            ]);
    }

    public function down()
    {
        $mobile_valentines_page = $this->getMobileValentinesPage();
        $mobile_menu = $this->getMobileMenu();
        $mobile_profile_menu = $this->getMobileProfileMenu();

        if (empty($mobile_valentines_page) || empty($mobile_menu) || empty($mobile_profile_menu)) {
            return;
        }

        $this->connection
            ->table($this->menus_table)
            ->where('name', $this->menu_name)
            ->where('parent_id', $mobile_menu->menu_id)
            ->delete();

        $this->connection
            ->table($this->menus_table)
            ->where('name', $this->menu_name)
            ->where('parent_id', $mobile_profile_menu->menu_id)
            ->update([
                'logged_out' => 1,
                'excluded_countries' => '',
            ]);
    }

    private function getMobileValentinesPage()
    {
        return $this->connection
            ->table($this->pages_table)
            ->where('cached_path', $this->mobile_page_path)
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
