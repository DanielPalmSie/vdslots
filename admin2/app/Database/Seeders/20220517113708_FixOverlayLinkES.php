<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Traits\WorksWithCountryListTrait;

class FixOverlayLinkES extends Seeder
{
    use WorksWithCountryListTrait;

    private Connection $connection;
    private const COUNTRY = 'ES';

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $names = ['#menu.main.welcome-bonus', '#mobile.menu.bonus'];

        $menu_items = $this->connection
            ->table('menus')
            ->whereIn('name', $names)
            ->get();

        foreach ($menu_items as $item) {
            $countries = $this->getCountriesArray($item, 'excluded_countries');

            if (!in_array(self::COUNTRY, $countries)) {
                continue;
            }

            $this->connection
                ->table('menus')
                ->where('menu_id', '=', $item->menu_id)
                ->update(['excluded_countries' => $this->buildCountriesValue($countries,'remove', self::COUNTRY)]);
        }
    }
}