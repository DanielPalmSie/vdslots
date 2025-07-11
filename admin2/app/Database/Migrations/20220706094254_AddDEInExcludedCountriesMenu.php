<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Traits\WorksWithCountryListTrait;
use Illuminate\Support\Collection;
use Phpmig\Migration\Migration;

class AddDEInExcludedCountriesMenu extends Migration
{
    use WorksWithCountryListTrait;

    private string $menu_table = 'menus';
    private string $country_code = 'DE';
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
        $this->getMenuItems('video-poker')
            ->each(function ($menu_item) {
                $countries = $this->getCountriesArray($menu_item, 'excluded_countries');

                if (in_array($this->country_code, $countries)) {
                    return;
                }

                $this->connection->table($this->menu_table)
                    ->where('menu_id', '=', $menu_item->menu_id)
                    ->update([
                        'excluded_countries' => $this->buildCountriesValue($countries, 'add', $this->country_code)
                    ]);
            });
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $this->getMenuItems('videopoker')
            ->each(function ($menu_item) {
                $countries = $this->getCountriesArray($menu_item, 'excluded_countries');

                if (!in_array($this->country_code, $countries)) {
                    return;
                }

                $this->connection->table($this->menu_table)
                    ->where('menu_id', '=', $menu_item->menu_id)
                    ->update([
                        'excluded_countries' => $this->buildCountriesValue($countries, 'remove', $this->country_code)
                    ]);
            });
    }

    /**
     * @param string $alias
     * @return Collection
     */
    private function getMenuItems(string $alias): Collection
    {
        $pages_ids = $this->connection->table('pages')->where('alias', $alias)->pluck('page_id');

        return $this->connection->table('menus')
            ->whereIn('link_page_id', $pages_ids)
            ->get();
    }
}
