<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddUsToRegistrationAndLoginBlockedCountries extends Seeder
{
    private const TABLE = 'config';
    private const CONFIG_TAG = 'exclude-countries';
    private const CONFIG_NAME = 'login-and-registration-blocked-countries';

    private const COUNTRY = 'US';

    private $connection;


    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $config = $this->connection->table(self::TABLE)
            ->where('config_tag', '=', self::CONFIG_TAG)
            ->where('config_name', '=', self::CONFIG_NAME)
            ->first();

        if (!empty($config) && stripos($config->config_value, self::COUNTRY) === false) {
            $this->connection->table(self::TABLE)
                ->where('id', '=', $config->id)
                ->update([
                    'config_value' => $this->buildExcludedCountriesValue($config->config_value, 'add')
                ]);
        }
    }

    public function down()
    {
        $config = $this->connection->table(self::TABLE)
            ->where('config_tag', '=', self::CONFIG_TAG)
            ->where('config_name', '=', self::CONFIG_NAME)
            ->first();

        if (!empty($config) && stripos($config->config_value, self::COUNTRY) !== false) {
            $this->connection->table(self::TABLE)
                ->where('id', '=', $config->id)
                ->update([
                    'config_value' => $this->buildExcludedCountriesValue($config->config_value, 'remove')
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

        if ($action === 'add' && !in_array(self::COUNTRY, $countries_arr)) {
            $countries_arr[] = self::COUNTRY;
        }

        if ($action === 'remove') {
            $countries_arr = array_filter($countries_arr, fn($country) => !in_array($country, [self::COUNTRY]));
        }

        return trim(implode(' ', $countries_arr));
    }
}
