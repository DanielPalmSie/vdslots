<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;

class AddGermanyInBlockedCountryForBonusTermsMenu extends Migration
{
    private const TABLE = 'menus';
    private const ALIAS = 'bonus-terms-footer';
    private const COUNTRY = 'DE';
    private $connection;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $menu_item = $this->connection->table(self::TABLE)
            ->where('alias', '=', self::ALIAS)
            ->first();

        if(!empty($menu_item) && stripos($menu_item->excluded_countries, self::COUNTRY) === false) {

            $this->connection->table(self::TABLE)
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
        $menu_item = $this->connection->table(self::TABLE)
            ->where('alias', '=', self::ALIAS)
            ->first();

        if(!empty($menu_item) && stripos($menu_item->excluded_countries, self::COUNTRY) !== false) {

            $this->connection->table(self::TABLE)
                ->where('menu_id', '=', $menu_item->menu_id)
                ->update([
                    'excluded_countries' => $this->buildExcludedCountriesValue($menu_item->excluded_countries, 'remove')
                ]);
        }
    }

    /**
     * This logic has been taken from 20211029122350_add_es_in_blocked_country_for_bonus_terms_menu.php
     *
     * Action can be add or remove
     *
     * With 'add', self::COUNTRY will be added to string
     * With 'remove' self::COUNTRY will be removed from string
     */
    private function buildExcludedCountriesValue($countries, $action): string
    {
        $countries_arr = explode(' ', $countries);

        if($action === 'add' && !in_array(self::COUNTRY, $countries_arr)) {
            $countries_arr[] = self::COUNTRY;
        }

        if($action === 'remove') {
            $countries_arr = array_filter($countries_arr, fn($country) => !in_array($country, [self::COUNTRY]));
        }

        return implode(' ', $countries_arr);
    }
}