<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Trigger;

class AddRG58Trigger extends Seeder
{
    private string $table;
    private string $trigger_name;
    private array $trigger;

    public function init()
    {
        $this->table = 'triggers';
        $this->trigger_name = 'RG58';
        $this->trigger = [
            'name' => $this->trigger_name,
            'indicator_name' => 'Game Session Duration',
            'description' => 'Game session durations on consecutive days',
            'color' => '#00ff00',
            'score' => 8,
            'ngr_threshold' => 0,
        ];
    }

    public function up()
    {
        $insert = $this->trigger;
        DB::loopNodes(function ($connection) use ($insert) {
            $connection->table($this->table)
                ->upsert(
                    $insert,
                    ['name'],
                    ['indicator_name', 'description', 'color', 'score', 'ngr_threshold']
                );
        }, true);
    }

    public function down()
    {
        Trigger::where('name', $this->trigger_name)->delete();
    }
}
