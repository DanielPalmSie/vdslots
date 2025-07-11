<?php
use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class AddAffordabilityCheckValueConfig extends Seeder
{
    private array $configs;

    private array $jurisdictions = ['SGA', 'DGA', 'DGOJ', 'CAON', 'MGA', 'ADM'];

    public function init()
    {
        foreach ($this->jurisdictions as $jurisdiction) {
            $this->configs[] = [
                "config_name" => "affordability-check-$jurisdiction-value",
                "config_tag" => 'net-deposit-limit',
                "config_value" => '',
                "config_type" => json_encode(["type"=>"number"])
            ];
        }

        $this->configs[] = [
            "config_name" => "affordability-check-UKGC-value",
            "config_tag" => 'net-deposit-limit',
            "config_value" => 500,
            "config_type" => json_encode(["type"=>"number"])
        ];
    }

    public function up()
    {
        foreach ($this->configs as $config) {
            Config::create($config);
        }
    }

    public function down()
    {
        foreach ($this->configs as $config) {
            Config::where('config_name', $config['config_name'])->delete();
        }
    }
}
