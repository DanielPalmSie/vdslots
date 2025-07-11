<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;
use App\Models\Trigger;

class AddRG64Trigger extends Seeder
{
    private string $table;
    private string $trigger_name;
    private array $trigger;

    public function init()
    {
        $this->table = 'triggers';
        $this->trigger_name = 'RG64';
        $this->trigger = [
            'name' => $this->trigger_name,
            'indicator_name' => 'High deposit frequency',
            'description' => 'High number of deposits within 24 hours',
            'color' => '#ff8000',
            'score' => 21,
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
