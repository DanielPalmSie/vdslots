<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddedFeatureFlagsForRgPopupEmails extends Seeder
{
    /**
     * @var array|array[]
     */
    private array $config;

    public function init()
    {
        $this->config = [
            "config_name" => "notify-customers-on-ignored-rg-popup",
            "config_tag" => 'mails',
            "config_value" => "",
            "config_type" => json_encode([
                "type" => "template",
                "delimiter" => ":",
                "next_data_delimiter" => ";",
                "format" => "<:Jurisdiction><delimiter><:NotificationTime24h>"
            ], JSON_THROW_ON_ERROR),
        ];
    }

    public function up()
    {
        Config::create($this->config);
    }

    public function down()
    {
        Config::where('config_name', $this->config['config_name'])->delete();
    }
}