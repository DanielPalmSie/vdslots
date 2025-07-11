<?php

use Phpmig\Migration\Migration;
use App\Extensions\Database\FManager as DB;


class RemoveDEFromIPBlockCountries extends Migration
{
    private const TABLE = 'config';
    private const CONFIG_TAG = 'countries';
    private const CONFIG_NAME = 'ip-block';
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
        $countries = $this->connection->table(self::TABLE)
            ->where('config_tag', '=', self::CONFIG_TAG)
            ->where('config_name', '=', self::CONFIG_NAME)
            ->first();

        if(!empty($countries) && stripos($countries->config_value, self::COUNTRY) !== false) {

            $this->connection->table(self::TABLE)
                ->where('id', '=', $countries->id)
                ->update([
                    'config_value' => $this->buildExcludedCountriesValue($countries->config_value, 'remove')
                ]);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $countries = $this->connection->table(self::TABLE)
            ->where('config_tag', '=', self::CONFIG_TAG)
            ->where('config_name', '=', self::CONFIG_NAME)
            ->first();

        if(!empty($countries) && stripos($countries->config_value, self::COUNTRY) === false) {

            $this->connection->table(self::TABLE)
                ->where('id', '=', $countries->id)
                ->update([
                    'config_value' => $this->buildExcludedCountriesValue($countries->config_value, 'add')
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

        if($action === 'add' && !in_array(self::COUNTRY, $countries_arr)) {
            $countries_arr[] = self::COUNTRY;
        }

        if($action === 'remove') {
            $countries_arr = array_filter($countries_arr, fn($country) => !in_array($country, [self::COUNTRY]));
        }

        return implode(' ', $countries_arr);
    }
}
