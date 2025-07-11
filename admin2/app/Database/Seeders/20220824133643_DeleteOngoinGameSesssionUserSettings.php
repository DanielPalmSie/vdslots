<?php

use App\Extensions\Database\Schema\Blueprint;
use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\Connection\Connection;
use App\Extensions\Database\FManager as DB;

class DeleteOngoinGameSesssionUserSettings extends Seeder
{

    public function init()
    {
        $this->table = 'users_settings';
    }

    public function up()
    {
        DB::loopNodes(function ($connection) {
            $connection->table($this->table)
                ->where('setting', '=', 'ongoing_game_sessions')
                ->delete();
        }, true);
    }
}