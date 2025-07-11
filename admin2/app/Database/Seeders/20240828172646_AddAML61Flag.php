<?php

use App\Extensions\Database\Seeder\Seeder;

class AddAML61Flag extends Seeder
{
    public function init()
    {
        $this->table = 'triggers';
        $this->isShardedDB = $this->getContainer()['capsule.vs.db']['sharding_status'];
    }

    public function up()
    {
        $trigger = [
            'name' => 'AML61',
            'indicator_name' => 'Enhanced Monitoring Alert',
            'description' => 'MLRO monitoring',
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
        $query = "DELETE FROM {$this->table} WHERE name = 'AML61'";
        phive('SQL')->query($query);

        if ($this->isShardedDB) {
            phive('SQL')->shs()->query($query);
        }
    }
}