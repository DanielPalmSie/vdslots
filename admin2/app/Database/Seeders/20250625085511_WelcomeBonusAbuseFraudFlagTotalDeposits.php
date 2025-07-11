<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class WelcomeBonusAbuseFraudFlagTotalDeposits extends Seeder
{
    private array $config = [
        [
            'config_name' => 'welcome-bonus-abuse-fraud-flag-total-deposits',
            'config_tag' => 'withdrawal-flags',
            "config_type" => '{"type":"number"}',
            'config_value' => 2,
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
