<?php

use App\Extensions\Database\Seeder\Seeder;

class AddAML60Flag extends Seeder
{
    private string $table;
    private bool $isShardedDB;

    public function init()
    {
        $this->table = 'triggers';
        $this->isShardedDB = $this->getContainer()['capsule.vs.db']['sharding_status'];
    }

    public function up()
    {
        $trigger = [
            'name' => 'AML60',
            'indicator_name' => 'Extreme risk player',
            'description' => 'Manual flag',
            'color' => '#ffffff',
            'score' => 0,
        ];

        phive('SQL')->onlyMaster()->insertArray($this->table, $trigger);

        if ($this->isShardedDB) {
            phive('SQL')->shs()->insertArray($this->table, $trigger);
        }
    }

    public function down()
    {
        $query = "DELETE FROM {$this->table} WHERE name = 'AML60'";
        phive('SQL')->query($query);

        if ($this->isShardedDB) {
            phive('SQL')->shs()->query($query);
        }
    }
}