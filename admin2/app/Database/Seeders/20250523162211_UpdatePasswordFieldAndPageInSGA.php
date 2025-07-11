<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Traits\WorksWithCountryListTrait;

class UpdatePasswordFieldAndPageInSGA extends Seeder
{
    use WorksWithCountryListTrait;
    private Connection $connection;

    private const COUNTRY = 'SE';
    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    public function up()
    {

        if (!$this->isTargetBrand()) {
            return;
        }

        // update menus
        $menu_names = ['#forgot-password', '#edit-password'];

        $menu_items = $this->connection
            ->table('menus')
            ->whereIn('name', $menu_names)
            ->get();

        foreach ($menu_items as $item) {
            $countries = $this->getCountriesArray($item, 'excluded_countries');

            if (in_array(self::COUNTRY, $countries)) {
                continue;
            }

            $this->connection
                ->table('menus')
                ->where('menu_id', '=', $item->menu_id)
                ->update(['excluded_countries' => $this->buildCountriesValue($countries,'add', self::COUNTRY)]);
        }
    }
    public function down() {
        if (!$this->isTargetBrand()) {
            return;
        }

        // update menus
        $menu_names = ['#forgot-password', '#edit-password'];

        $menu_items = $this->connection
            ->table('menus')
            ->whereIn('name', $menu_names)
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

    private function isTargetBrand(): bool
    {
        return in_array($this->brand, [phive('BrandedConfig')::BRAND_DBET, phive('BrandedConfig')::BRAND_KUNGASLOTTET, phive('BrandedConfig')::BRAND_MEGARICHES], true);
    }

}
