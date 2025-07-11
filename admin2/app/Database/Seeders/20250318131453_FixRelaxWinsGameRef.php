<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class FixRelaxWinsGameRef extends Seeder
{


    public function init()
    {

    }

    public function up()
    {
        $operations = [
            ['user_id' => 1966992979, 'update_id' => 5227311930],
            ['user_id' => 1967022675, 'update_id' => 5277349906],
        ];

        $gameRef = 'qspinrlx.hacksaw.hacksaw.1725';

        foreach ($operations as $op) {
            $db = phive('SQL')->sh($op['user_id']);
            $db->query("UPDATE wins SET `game_ref` = '$gameRef' WHERE id = {$op['update_id']}");
        }
    }
}
