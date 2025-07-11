<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Extensions\Database\Connection\Connection;
use App\Models\Config;

class AddMissedWelcomeBonusRemindersConfigsForOtherBrands extends Seeder
{
    private Connection $connection;
    private string $table;
    private string $brand;
    private array $configurations;

    public function init()
    {
        $this->connection = DB::getMasterConnection();
        $this->table = 'config';
        $this->brand = phive('BrandedConfig')->getBrand();
        $this->configurations = [
            [
                "config_name" => 'welcome.bonus_reminder',
                "config_tag" => 'mails',
                "config_value" => '',
                "config_type" => '{"type":"template", "next_data_delimiter":",", "format":"<:Number><delimiter>"}'
            ],
            [
                "config_name" => 'welcome.bonus_reminder',
                "config_tag" => 'bonus_reminder_email',
                "config_value" => 'off',
                "config_type" => '{"type":"choice","values":["on","off"]}"}'
            ],
            [
                "config_name" => 'welcome.bonus_reminder',
                "config_tag" => 'bonus_reminder_sms',
                "config_value" => 'off',
                "config_type" => '{"type":"choice","values":["on","off"]}"}'
            ]
        ];
    }

    public function up()
    {
        if ($this->brand != phive('BrandedConfig')::BRAND_KUNGASLOTTET) {
            foreach ($this->configurations as $config) {
                $exists = $this->connection
                    ->table($this->table)
                    ->where('config_name', $config['config_name'])
                    ->where('config_tag', $config['config_tag'])
                    ->exists();

                if (!$exists) {
                    $this->connection->table($this->table)->insert($config);
                }
            }
        }
    }

    public function down()
    {
        if ($this->brand != phive('BrandedConfig')::BRAND_KUNGASLOTTET) {
            foreach ($this->configurations as $config) {
                $this->connection
                    ->table($this->table)
                    ->where('config_name', $config['config_name'])
                    ->where('config_tag', $config['config_tag'])
                    ->delete();
            }
        }
    }
}