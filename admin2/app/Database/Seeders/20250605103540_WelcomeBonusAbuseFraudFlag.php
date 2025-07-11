<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class WelcomeBonusAbuseFraudFlag extends Seeder
{
    private const FLAG = 'welcome-bonus-abuse-fraud-flag';

    private array $config = [
        [
            'config_name' => 'enabled-' . self::FLAG,
            'config_tag' => 'withdrawal-flags',
            'config_type' => '{"type":"choice","values":["on","off"]}',
            'config_value' => 'off',
        ]
    ];

    public function up()
    {
        foreach ($this->config as $config) {
            Config::updateOrCreate($config);
        }
    }

    public function down()
    {
        foreach ($this->config as $config) {
            Config::where('config_name', $config['config_name'])->delete();
        }
    }
}
