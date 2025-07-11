<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class BeBettorVulnerableNDL extends Seeder
{
    private array $config;

    public function init()
    {
        $this->config = [
            "config_name" => 'bebettor-vs-score-vulnerable',
            "config_tag" => 'RG',
            "config_value" => 50000,
            "config_type" => '{"type":"number"}',
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
