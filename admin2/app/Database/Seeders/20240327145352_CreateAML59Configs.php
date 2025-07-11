<?php 
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Config;

class CreateAML59Configs extends Seeder
{

    /**
     * @var array|array[]
     */
    private array $configs;

    public function init()
    {
        $this->configs = [
            [
                "config_name" => 'AML59-bonus-payout-thold',
                "config_tag" => 'AML',
                "config_value" => 100000,
                "config_type" => '{"type":"number"}',
            ],
            [
                "config_name" => 'AML59-bonus-payout-percent',
                "config_tag" => 'AML',
                "config_value" => 9,
                "config_type" => '{"type":"number"}',
            ],
            [
                "config_name" => 'AML59-duration-days',
                "config_tag" => 'AML',
                "config_value" => 90,
                "config_type" => '{"type":"number"}',
            ],
        ];
    }

    public function up()
    {
        foreach ($this->configs as $insert) {
            Config::create($insert);
        }
    }

    public function down()
    {
        foreach ($this->configs as $config) {
            Config::where('config_name', $config['config_name'])->delete();
        }
    }
}