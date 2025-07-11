<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class BlockPlayngoGamesCAQC extends Seeder
{
    public function init()
    {
    }

    public function up()
    {
        DB::loopNodes(function ($connection) {
            $connection->statement("UPDATE micro_games SET blocked_provinces = CONCAT(`blocked_provinces` ,' CA-QC')
                   WHERE operator = 'Play N Go' AND network = 'playngo'");
        }, true);
    }
}
