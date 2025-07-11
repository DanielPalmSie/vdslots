<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;
use App\Traits\WorksWithCountryListTrait;

class EnableSportsbookForUkgcOnMrVegas extends Seeder
{
    use WorksWithCountryListTrait;

    private string $tablePages;
    private string $tableMenus;
    private string $tableBoxes;
    private string $tablePageSettings;
    private Connection $connection;
    private array $menuAlias;
    private const COUNTRY = 'GB';

    public function init()
    {
        $this->tableMenus = 'menus';
        $this->connection = DB::getMasterConnection();
        $this->menuAlias = ['sportsbook-live','sportsbook-prematch','mobile-secondary-top-menu-sports', 'mobile-secondary-top-menu-sports', 'mobile-sports-betting-history', 'sports-betting-history'];
    }

    /**
     * Do the migration
     */
    public function up()
    {

        if (getenv('APP_SHORT_NAME') !== 'MV') {
            return;
        }
        
        $menus = $this->connection
            ->table($this->tableMenus)
            ->whereIn('alias', $this->menuAlias)
            ->get();

        foreach ($menus as $menu) {
            $countries = $this->getCountriesArray($menu, 'excluded_countries');

            if (!in_array(self::COUNTRY, $countries)) {
                continue;
            }

            $this->connection
                ->table('menus')
                ->where('menu_id', '=', $menu->menu_id)
                ->update(['excluded_countries' => $this->buildCountriesValue($countries,'remove', self::COUNTRY)]);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        if (getenv('APP_SHORT_NAME') !== 'MV') {
            return;
        }

        $menus = $this->connection
            ->table($this->tableMenus)
            ->whereIn('alias', $this->menuAlias)
            ->get();

        foreach ($menus as $menu) {
            $countries = $this->getCountriesArray($menu, 'excluded_countries');

            if (in_array(self::COUNTRY, $countries)) {
                continue;
            }

            $this->connection
                ->table('menus')
                ->where('menu_id', '=', $menu->menu_id)
                ->update(['excluded_countries' => $this->buildCountriesValue($countries,'add', self::COUNTRY)]);
        }
    }
}
