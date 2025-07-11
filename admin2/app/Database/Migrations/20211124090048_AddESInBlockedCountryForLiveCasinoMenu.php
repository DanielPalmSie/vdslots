<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class AddESInBlockedCountryForLiveCasinoMenu extends Migration
{
    /** @var string */
    private string $menu_table;

    /** @var Connection */
    private Connection $connection;

    /** @var string */
    private string $country_code;

    public function init()
    {
        $this->menu_table = 'menus';
        $this->country_code = 'ES';
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        foreach($this->getMenuItems() as $menu_item) {

            $countries = $this->getCountriesArray($menu_item);

            if (in_array($this->country_code, $countries)) {
                continue;
            }

            $this->connection->table($this->menu_table)
                ->where('menu_id', '=', $menu_item->menu_id)
                ->update([
                    'excluded_countries' => $this->buildExcludedCountriesValue($countries, 'add')
                ]);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        foreach($this->getMenuItems() as $menu_item) {

            $countries = $this->getCountriesArray($menu_item);

            if (!in_array($this->country_code, $countries)) {
                continue;
            }

            $this->connection->table($this->menu_table)
                ->where('menu_id', '=', $menu_item->menu_id)
                ->update([
                    'excluded_countries' => $this->buildExcludedCountriesValue($countries, 'remove')
                ]);
        }
    }

    /**
     * will return all rows with alias live-casino. As we are supposed to hide all Live Casino menu for ES
     *
     * @return mixed
     */
    private function getMenuItems()
    {
        $pages = $this->connection->table('pages')->where('cached_path', 'like', '%live-casino')->pluck('page_id');

        return $this->connection->table($this->menu_table)
            ->whereIn('link_page_id', $pages)
            ->get();
    }

    /**
     * Will build value for excluded_countries field
     *
     * Action can be add or remove
     *
     * With 'add', ES will be added to string
     * With 'remove' ES will be removed from string
     */
    private function buildExcludedCountriesValue(array $countries, $action): string
    {
        if($action === 'add') {
            $countries[] = $this->country_code;
        }

        if($action === 'remove') {
            unset($countries[array_search($this->country_code, $countries)]);
        }

        return join(' ', $countries);
    }

    /**
     * @param $item
     * @return array
     */
    private function getCountriesArray($item): array
    {
        return array_filter(explode(' ', $item->excluded_countries));
    }
}
