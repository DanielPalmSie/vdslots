<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Seeder\Seeder;
use App\Traits\WorksWithCountryListTrait;

/**
 * One record was exist in MrVegas and I did to handle the missed exclusive country.
 */
class AddMissedNLANDSEInMobileSportsOnMrVegas extends Seeder
{
    use WorksWithCountryListTrait;

    private string $tablePages;
    private string $tableMenus;
    private string $tableBoxes;
    private string $tablePageSettings;
    private Connection $connection;
    private array $excludedCountries = ['NL', 'SE'];

    public function init()
    {
        $this->tableMenus = 'menus';
        $this->connection = DB::getMasterConnection();
    }

    /**
     * Do the migration
     */
    public function up()
    {

        if (getenv('APP_SHORT_NAME') !== 'MV') {
            return;
        }

        foreach ($this->excludedCountries as $country){
            $menus = $this->connection
                ->table($this->tableMenus)
                ->where('alias', 'mobile-secondary-top-menu-sports')
                ->get();

            foreach ($menus as $menu) {
                $countries = $this->getCountriesArray($menu, 'excluded_countries');
                if (!in_array($country, $countries)) {
                    $this->connection
                        ->table('menus')
                        ->where('menu_id', '=', $menu->menu_id)
                        ->update(['excluded_countries' => $this->buildCountriesValue($countries, 'add', $country)]);
                }
            }
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

        foreach ($this->excludedCountries as $country){
            $menus = $this->connection
                ->table($this->tableMenus)
                ->where('alias', 'mobile-secondary-top-menu-sports')
                ->get();

            foreach ($menus as $menu) {
                $countries = $this->getCountriesArray($menu, 'excluded_countries');
                if (in_array($country, $countries)) {
                    $this->connection
                        ->table('menus')
                        ->where('menu_id', '=', $menu->menu_id)
                        ->update(['excluded_countries' => $this->buildCountriesValue($countries, 'remove', $country)]);
                }
            }
        }
    }
}
