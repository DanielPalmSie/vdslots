<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Traits\WorksWithCountryListTrait;

class EnableSportsbookForUkgc extends Seeder
{
    use WorksWithCountryListTrait;

    private Connection $connection;
    private const COUNTRY = 'GB';

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        if (getenv('APP_SHORT_NAME') === 'MV') {
            return;
        }

        $menu_aliases = [
            'sportsbook-live',
            'sportsbook-prematch',
            'sports-betting-history',
            'mobile-sports-betting-history',
            'mobile-secondary-top-menu-sports'
        ];

        $menu_items = $this->connection
            ->table('menus')
            ->whereIn('alias', $menu_aliases)
            ->get();

        foreach ($menu_items as $item) {
            $countries = $this->getCountriesArray($item, 'excluded_countries');

            if (in_array(self::COUNTRY, $countries)) {
                $this->connection
                    ->table('menus')
                    ->where('menu_id', '=', $item->menu_id)
                    ->update(['excluded_countries' => $this->buildCountriesValue($countries,'remove', self::COUNTRY)]);
            }
        }
    }
}