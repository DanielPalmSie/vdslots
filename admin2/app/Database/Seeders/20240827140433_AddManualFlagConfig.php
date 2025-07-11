<?php

use App\Extensions\Database\Seeder\Seeder;

class AddManualFlagConfig extends Seeder
{
    /**
     * @var array|array[]
     */
    private array $configs;
    private string $table = 'config';

    public function init()
    {
        $country_jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');

        foreach ($country_jurisdiction_map as $country => $jurisdiction) {
            $default_flags = $this->getDefaultTriggers();

            if ($jurisdiction === 'AGCO') {
                $default_flags[] = 'AML60';
            }
            $flags = implode(',', $default_flags);
            $this->configs[] = [
                "config_name" => "$jurisdiction-manual-flags",
                "config_tag" => 'jurisdictions',
                "config_value" => $flags,
                "config_type" => json_encode([
                    "type" => "template",
                    "next_data_delimiter" => ",",
                    "format" => "<:Trigger><delimiter>",
                ]),
            ];
        }
    }

    public function up()
    {
        foreach ($this->configs as $config) {
            phive('SQL')->insertArray($this->table, $config);
        }
    }

    public function down()
    {
        foreach ($this->configs as $config) {
            $config_name = $config['config_name'];
            $query = "DELETE FROM {$this->table} WHERE config_name = '{$config_name}' AND config_tag = 'jurisdictions'";
            phive('SQL')->query($query);
            phive('SQL')->shs()->query($query);
        }
    }

    private function getDefaultTriggers(): array
    {
        $default_flags = [];
        for ($i = 41; $i <= 57; $i++) {
            $default_flags[] = 'RG' . $i;
        }
        return $default_flags;
    }
}