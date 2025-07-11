<?php

use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Config;
use App\Traits\WorksWithCountryListTrait;

class TurnOffEmailsForNL extends Seeder
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
        $found = [];
        $emails = [
            '1w-inactive-100',
            '1w-inactive-200',
            '1w-inactive-500',
            '1w-inactive-x',
            '2w-inactive-100',
            '2w-inactive-200',
            '2w-inactive-500',
            '2w-inactive-x',
            'welcome.mail',
            'welcome.mrvegas',
            'no-deposit-weekly',
            'weekly-bonuslovers',
            'nodeposit-newbonusoffers-mail-1',
            'nodeposit-newbonusoffers-mail-2',
            'nodeposit-newbonusoffers-mail-3',
            'nodeposit-newbonusoffers-mail-4',
            'nodeposit-newbonusoffers-mail-5',
            'nodeposit-newbonusoffers-mail-6',
            'nodeposit-newbonusoffers-mail-7',
            'nodeposit-newbonusoffers-mail-8',
            'nodeposit-newbonusoffers-mail-9',
            'nodeposit-newbonusoffers-mail-10',
            'nodeposit-newbonusoffers-mail-11',
            'nodeposit-newbonusoffers-mail-12',
            'nodeposit-newbonusoffers-mail-13',
            'nodeposit-newbonusoffers-mail-14',
            'nodeposit-newbonusoffers-mail-15',
            'deposit-newbonusoffers-mail-1',
            'deposit-newbonusoffers-mail-2',
            'deposit-newbonusoffers-mail-3',
            'deposit-newbonusoffers-mail-4',
            'deposit-newbonusoffers-mail-5',
            'deposit-newbonusoffers-mail-6',
            'deposit-newbonusoffers-mail-7',
            'deposit-newbonusoffers-mail-8',
            'deposit-newbonusoffers-mail-9',
            'deposit-newbonusoffers-mail-10',
            'deposit-newbonusoffers-mail-11',
            'deposit-newbonusoffers-mail-12',
            'deposit-newbonusoffers-mail-13',
            'deposit-newbonusoffers-mail-14',
            'deposit-newbonusoffers-mail-15',
            'monthly-week1',
        ];

        $configs = $this->connection
            ->table('config')
            ->whereIn('config_name', $emails)
            ->where('config_tag', '=', 'countries')
            ->get();

        foreach ($configs as $config) {
            $countries = $this->getCountriesArray($config, 'config_value');
            $found[] = $config->config_name;

            if (in_array(self::COUNTRY, $countries)) {
                continue;
            }

            Config::shs()
                ->where('id', '=', $config->id)
                ->update(['config_value' => $this->buildCountriesValue($countries,'add', self::COUNTRY)]);
        }

        $to_insert = array_map(function($email) {
            return [
                'config_name' => $email,
                'config_tag' => 'countries',
                'config_value' => self::COUNTRY,
                'config_type' => '{"type":"number"}'// We have this for other countries values.
            ];
        }, array_diff($emails, $found));

        foreach ($to_insert as $insert) {
            Config::shs()->insert($insert);
        }
    }
}