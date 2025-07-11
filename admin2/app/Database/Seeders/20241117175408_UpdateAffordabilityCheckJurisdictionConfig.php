<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Models\Config;

class UpdateAffordabilityCheckJurisdictionConfig extends Seeder
{
    private array $country_jurisdiction_map;
    private string $config_tag;

    public function init()
    {
        $this->country_jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');
        $this->config_tag = "net-deposit-limit";
    }

    public function up()
    {
        foreach ($this->country_jurisdiction_map as $jurisdictions) {
            if ($jurisdictions === 'UKGC') {
                continue;
            }
            $config_name = "affordability-check-$jurisdictions";
            $config_value = Config::getValue(
                $config_name,
                $this->config_tag,
                [],
                false,
                true,
                true
            );

            if (!$config_value) {
                continue;
            }

            // Set all range values to 0
            $config_value = array_map(function () {
                return 0;
            }, $config_value);

            Config::where('config_name', $config_name)
                ->where('config_tag', $this->config_tag)
                ->update(['config_value' => $this->stringifyConfigValue($config_value)]);
        }
    }

    private function stringifyConfigValue(array $value): string
    {
        $config_value = "";
        foreach ($value as $range => $ndl) {
            if (empty($range)) {
                continue;
            }
            $config_value .= implode("::", [$range, $ndl]) . "\n";
        }

        return $config_value;
    }
}