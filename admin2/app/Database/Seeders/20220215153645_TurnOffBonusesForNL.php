<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Config;
use App\Traits\WorksWithCountryListTrait;

class TurnOffBonusesForNL extends Seeder
{
    use WorksWithCountryListTrait;

    private Connection $connection;
    private const COUNTRY = 'NL';

    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $bonuses = [
            'netent-reg-bonus-countries',
            'normal-reg-bonus-countries',
            'reg-bonus-countries',
            'reg-award-countries'
        ];

        $configs = $this->connection
            ->table('config')
            ->whereIn('config_name', $bonuses)
            ->get();

        foreach ($configs as $config) {
            $countries = $this->getCountriesArray($config, 'config_value');

            if (!in_array(self::COUNTRY, $countries)) {
                continue;
            }

            Config::shs()
                ->where('id', '=', $config->id)
                ->update(['config_value' => $this->buildCountriesValue($countries,'remove', self::COUNTRY)]);
        }
    }
}