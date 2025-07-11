<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Traits\WorksWithCountryListTrait;
use Illuminate\Support\Collection;
use Phpmig\Migration\Migration;

class AddITInBlockedCountryForChristmasCalendar extends Migration
{
    use WorksWithCountryListTrait;

    private string $menu_table = 'menus';
    private string $country_code = 'IT';
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
        $this->getMenuItems()
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
        $this->getMenuItems()
            ->each(function ($menu_item) {
                $countries = $this->getCountriesArray($menu_item, 'excluded_countries');

                if (!in_array($this->country_code, $countries)) {
                    return;
                }

                $this->connection->table($this->menu_table)
                    ->where('menu_id', $menu_item->menu_id)
                    ->update([
                        'excluded_countries' => $this->buildCountriesValue($countries, 'remove', $this->country_code)
                    ]);
            });
    }

    /**
     * @return Collection
     */
    private function getMenuItems(): Collection
    {
        $pages_ids = $this->connection->table('pages')->where('alias', 'christmas-calendar')->pluck('page_id');

        return $this->connection->table('menus')
            ->whereIn('link_page_id', $pages_ids)
            ->get();
    }
}
