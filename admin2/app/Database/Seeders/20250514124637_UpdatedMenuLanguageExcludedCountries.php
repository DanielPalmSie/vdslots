<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Traits\WorksWithCountryListTrait;

class UpdatedMenuLanguageExcludedCountries extends Seeder
{
    use WorksWithCountryListTrait;

    private Connection $connection;
    private const COUNTRY = 'SE';

    private string $table = 'localized_strings';


    protected array $data = [
        'en' => [
            'paynplay.error.login-limit-reached.description' => 'Your account is temporarily locked because your login limit has been reached. You are welcome back when the limit has been reset.',
            'paynplay.error.login-limit-reached.title' => 'Login Limit Reached',
        ],
        'sv' => [
            'paynplay.error.login-limit-reached.description' => 'Ditt konto är tillfälligt låst eftersom din inloggningsgräns har uppnåtts. Du är välkommen tillbaka när gränsen har återställts.',
            'paynplay.error.login-limit-reached.title' => 'Gränsen för inloggning har nåtts',
        ]
    ];

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->brand = phive('BrandedConfig')->getBrand();
    }

    public function up()
    {
        if ($this->brand !== 'megariches') {
            return;
        }

        // update localized_string
        foreach ($this->data as $language => $translations) {
            foreach ($translations as $alias => $value) {
                $this->connection->table($this->table)->upsert([
                    'alias' => $alias,
                    'value' => $value,
                    'language' => $language,
                ], ['alias', 'language']);
            }
        }

        // update menus
        $menu_names = ['#change-languages', '#forgot-password', '#edit-password'];

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
        if ($this->brand !== 'megariches') {
            return;
        }

        foreach ($this->data as $language => $translations) {
            foreach (array_keys($translations) as $alias) {
                $this->connection->table($this->table)->where('alias', $alias)->where('language', $language)->delete();
            }
        }

        // update menus
        $menu_names = ['#change-languages', '#forgot-password'];

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
}
