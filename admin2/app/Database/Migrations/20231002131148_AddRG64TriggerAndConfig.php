<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;
use App\Models\Config;
use App\Models\Trigger;


class AddRG64TriggerAndConfig extends Migration
{
    private const TRIGGERS_TABLE = 'triggers';

    private array $configs;
    private string $trigger_name;
    private array $trigger;

    public function init()
    {
        $this->trigger_name = 'RG64';

        $this->configs = [
            [
                'config_name' => "{$this->trigger_name}-high-deposit-frequency",
                'config_tag' => 'RG',
                'config_value' => 'off',
                'config_type' => json_encode([
                    "type" => "choice",
                    "values" => ["on", "off"]
                ])
            ],
            [
                'config_name' => "{$this->trigger_name}-high-deposit-frequency-threshold",
                'config_tag' => 'RG',
                'config_value' => '0',
                'config_type' => json_encode([
                    "type" => "number"
                ])
            ]
        ];

        $this->trigger = [
            [
                'name' => $this->trigger_name,
                'indicator_name' => 'High deposit frequency',
                'description' => 'High number of deposits within 24 hours',
                'color' => '#ff0000',
                'score' => 21,
                'ngr_threshold' => 0
            ]
        ];
    }

    /**
     * Do the migration
     */
    public function up()
    {
        $bulkInsertInMasterAndShards = function ($table, $data) {
            DB::bulkInsert($table, null, $data, DB::getMasterConnection());
            DB::bulkInsert($table, null, $data);
        };

        $bulkInsertInMasterAndShards(self::TRIGGERS_TABLE, $this->trigger);

        foreach ($this->configs as $config) {
            Config::shs()->insert($config);
        }
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $names = [];
        foreach ($this->configs as $config) {
            $names[] = $config['config_name'];
        }
        Config::whereIn('config_name', $names)->delete();

        Trigger::where('name', '=', $this->trigger_name)->delete();
    }
}

