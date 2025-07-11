<?php
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Config;

class UpdateNetDepositMonthCountries extends Seeder
{
    private Connection $connection;


    public function init()
    {
        $this->connection = DB::getMasterConnection();
    }

    public function up()
    {
        $countries = ['GB,SE,DK,ES,IT'];
        $configName = 'net-deposit-limit-month-countries';

        $config = $this->connection
            ->table('config')
            ->where('config_name', $configName)
            ->first();

        $netDepositCountries = array_filter(explode(',', ($config->{'config_value'})));

        foreach ($countries as $country) {
            if(in_array($country, $netDepositCountries)) {
                continue;
            }

            Config::shs()
                ->where('config_name', $configName)
                ->update(['config_value' => $country]);

        }
    }
}