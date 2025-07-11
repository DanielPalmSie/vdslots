<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddRG66DaysConfig extends Seeder
{
    /**
     * @var array
     */
    private array $config;

    public function init()
    {
        $this->config = [
            "config_name" => 'RG66-net-deposit-days',
            "config_tag" => 'RG',
            "config_value" => "30",
            "config_type" => json_encode([
                "type" => "number",
            ], JSON_THROW_ON_ERROR)
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
