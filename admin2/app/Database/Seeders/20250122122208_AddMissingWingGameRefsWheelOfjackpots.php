<?php

use App\Extensions\Database\Seeder\Seeder;
use App\Extensions\Database\FManager as DB;

class AddMissingWingGameRefsWheelOfjackpots extends Seeder
{
    private string $table;

    public function init()
    {
        $this->table = 'wins';
    }

    public function up()
    {
        $this->init();

        DB::loopNodes(function ($connection) {
            $connection
                ->table($this->table)
                ->where(function ($q) {
                    return $q->whereNull('game_ref')
                        ->orWhere('game_ref', '=', '');
                })
                ->where('mg_id', 'LIKE', 'wheelofjackpots%')
                ->where('created_at', '>=', '2024-07-01 00:00:00')
                ->update(
                    ['game_ref' => 'wheel_of_jackpots']
                );
        });
    }
}
