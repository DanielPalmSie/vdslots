<?php

namespace App\Commands\Checks;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;

class WrongGameRefBetCheck extends AbstractDatabaseCheck implements DatabaseCheckInterface
{
    protected string $name = 'wrong-game-ref-bet';
    protected string $description = 'Get bets with wrong game ref in DB';

    public function getBuilderForAll(
        Connection $connection,
        string $start_time,
        string $end_time
    ): Builder {

        return $this->getBaseBuilder($connection, $start_time, $end_time)
            ->select([
                'b.id',
                'b.user_id',
                'b.game_ref',
                'b.device_type',
                'b.created_at',
                'us.value as jurisdiction'
            ]);
    }


    public function getBuilderForAny(
        Connection $connection,
        string $start_time,
        string $end_time
    ): Builder{

        return $this->getBaseBuilder($connection, $start_time, $end_time)
            ->limit(1);
    }

    protected function getBaseBuilder(
        Connection $connection,
        string $start_time,
        string $end_time
    ): Builder{

        return $connection
            ->table('bets AS b')
            ->join('users AS u', 'u.id', '=', 'b.user_id')
            ->join('users_settings as us', function($join) {
                $join->on('us.user_id', '=', 'u.id')
                    ->where('us.setting', '=', 'jurisdiction');
            })
            ->leftJoin('micro_games AS mg', function ($join) {
                $join->on('mg.ext_game_name', '=', 'b.game_ref')
                    ->on('b.device_type', '=', 'mg.device_type_num');
            })
            ->whereBetween('b.created_at', [$start_time, $end_time])
            ->whereNull('mg.id');
    }

    public function getHeaders(): array
    {
        return [
            'bet_id',
            'user_id',
            'game_ref',
            'device_type',
            'created_at',
            'jurisdiction'
        ];
    }

    public function requiresUserData(): bool
    {
        return true;
    }
}
