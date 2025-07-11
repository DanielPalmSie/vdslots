<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;

class AddEsInBlockedCountryForBonusTermsMenu extends Migration
{
    private $menu_table;
    private $connection;
    private $alias;
    private $country_code;

    public function init()
    {
        $this->menu_table = 'menus';
        $this->alias = 'bonus-terms-footer';
        $this->country_code = 'ES';
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $menu_item = $this->connection->table($this->menu_table)
                                        ->where('alias', '=', $this->alias)
                                        ->first();

        if(!empty($menu_item) && stripos($menu_item->excluded_countries, $this->country_code) === false) {

            $this->connection->table($this->menu_table)
                ->where('menu_id', '=', $menu_item->menu_id)
                ->update([
                    'excluded_countries' => $this->buildExcludedCountriesValue($menu_item->excluded_countries, 'add')
                ]);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $menu_item = $this->connection->table($this->menu_table)
            ->where('alias', '=', $this->alias)
            ->first();

        if(!empty($menu_item) && stripos($menu_item->excluded_countries, $this->country_code) !== false) {

            $this->connection->table($this->menu_table)
                ->where('menu_id', '=', $menu_item->menu_id)
                ->update([
                    'excluded_countries' => $this->buildExcludedCountriesValue($menu_item->excluded_countries, 'remove')
                ]);
        }
    }

    /**
     * Will build value for excluded_countries field
     *
     * Action can be add or remove
     *
     * With 'add', ES will be added to string
     * With 'remove' ES will be removed from string
     */
    private function buildExcludedCountriesValue($countries, $action): string
    {
        $countries_arr = explode(' ', $countries);

        if($action === 'add' && !in_array($this->country_code, $countries_arr)) {
            $countries_arr[] = $this->country_code;
        }

        if($action === 'remove') {
            $countries_arr = array_filter($countries_arr, fn($country) => !in_array($country, [$this->country_code]));
        }

        return implode(' ', $countries_arr);
    }
}
