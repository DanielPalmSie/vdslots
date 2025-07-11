<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class RemoveNorwayLangCountryAndCurrency extends Seeder
{
    private Connection $connection;
    private string $languagesTable;
    private string $currenciesTable;
    private string $configTable;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->languagesTable = 'languages';
        $this->currenciesTable = 'currencies';
        $this->configTable = 'config';
    }

    public function up()
    {
        $this->connection
            ->table($this->languagesTable)
            ->where('language', 'no')
            ->delete();

        $this->connection
            ->table($this->currenciesTable)
            ->where('code', 'NOK')
            ->update([
                'legacy' => 1
            ]);

        $countries = $this->connection
            ->table($this->configTable)
            ->where('config_tag', 'countries')
            ->where('config_name', 'block')
            ->first();

        if(!empty($countries) && stripos($countries->config_value, 'NO') === false) {
            $this->connection->table($this->configTable)
                ->where('id', '=', $countries->id)
                ->update([
                    'config_value' => $this->buildExcludedCountriesValue($countries->config_value, 'add')
                ]);
        }
    }

    public function down()
    {
        $this->connection
            ->table($this->languagesTable)
            ->where('language', 'no')
            ->insert([
                'language' => 'no',
                'light' => 1,
                'selectable' => 1
            ]);

        $this->connection
            ->table($this->currenciesTable)
            ->where('code', 'NOK')
            ->update([
                'legacy' => 0
            ]);

        $countries = $this->connection
            ->table($this->configTable)
            ->where('config_tag', 'countries')
            ->where('config_name', 'block')
            ->first();

        if(!empty($countries) && stripos($countries->config_value, 'NO') !== false) {
            $this->connection->table($this->configTable)
                ->where('id', '=', $countries->id)
                ->update([
                    'config_value' => $this->buildExcludedCountriesValue($countries->config_value, 'remove')
                ]);
        }
    }

    /**
     *
     * Action can be add or remove
     *
     * With 'add', self::COUNTRY will be added to string
     * With 'remove' self::COUNTRY will be removed from string
     */
    private function buildExcludedCountriesValue($countries, $action): string
    {
        $countries_arr = explode(' ', $countries);

        if($action === 'add' && !in_array('NO', $countries_arr)) {
            $countries_arr[] = 'NO';
        }

        if($action === 'remove') {
            $countries_arr = array_filter($countries_arr, fn($country) => !in_array($country, ['NO']));
        }

        return implode(' ', $countries_arr);
    }
}
