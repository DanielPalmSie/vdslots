<?php

use App\Extensions\Database\FManager as DB;
use Phpmig\Migration\Migration;

class EnhanceRGProfileSettings extends Migration
{
    protected $table;

    protected $schema;

    public function init()
    {
        $this->table = 'risk_profile_rating';
        $this->schema = $this->get('schema');
    }

    /**
     * @param array $data
     */
    private function updateMasterAndShards($data) {
        $apply_updates = function($node) use ($data) {
            /** @var \App\Extensions\Database\Connection\Connection $node */
            foreach ($data as $name => $updates) {
                $node->table($this->table)->where('name', '=', $name)->update($updates);
            }
        };

        DB::loopNodes($apply_updates);
        $apply_updates(DB::getMasterConnection());
    }

    /**
     * @throws Exception
     */
    public function up()
    {
        $data = json_encode(["replacers" => ["_LAST_DAYS" => 45, "_PREVIOUS_DAYS" => 45]]);

        $settings = [
            // ready:
            'avg_dep_amount_x_days' => [
                'title' => 'Average deposited amount per logged in day from last _LAST_DAYS days have increased from previous _PREVIOUS_DAYS days',
                "data" => $data
            ],
            // ready:
            'avg_dep_count_x_days' => [
                'title' => 'Average deposit transactions per logged in day from last _LAST_DAYS days have increased from previous _PREVIOUS_DAYS days',
                "data" => $data
            ],
            // ready:
            'avg_time_per_session_x_days' => [
                'title' => 'Average time per session per logged in day from last _LAST_DAYS days have increased from previous _PREVIOUS_DAYS days',
                "data" => $data
            ],
            // ready:
            'avg_sessions_count_x_days' => [
                'title' => 'Average sessions per logged in day from last _LAST_DAYS days have increased from previous _PREVIOUS_DAYS days',
                "data" => $data
            ],
        ];

        $this->updateMasterAndShards($settings);
    }

    /**
     * @throws Exception
     */
    public function down()
    {
        $data = json_encode(["replacers" => ["_DAYS" => 45]]);

        $settings = [
            'avg_dep_amount_x_days' => [
                'title' => 'Average deposited amount per logged in day from last _DAYS days have increased from previous _DAYS days',
                "data" => $data
            ],
            'avg_dep_count_x_days' => [
                'title' => 'Average deposit transactions per logged in day from last _DAYS days have increased from previous _DAYS days',
                "data" => $data
            ],
            'avg_time_per_session_x_days' => [
                'title' => 'Average time per session per logged in day from last _DAYS days have increased from previous _DAYS days',
                "data" => $data
            ],
            'avg_sessions_count_x_days' => [
                'title' => 'Average sessions per logged in day from last _DAYS days have increased from previous _DAYS days',
                "data" => $data
            ],
        ];

        $this->updateMasterAndShards($settings);
    }
}
