<?php

namespace App\Commands\Checks;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;

class WrongGameRefGameSessionCheck extends AbstractDatabaseCheck implements DatabaseCheckInterface
{
    protected string $name = 'wrong-game-ref-game-session';
    protected string $description = 'Get users game sessions with wrong game ref in DB';

    public function getBuilderForAll(
        Connection $connection,
        string $start_time,
        string $end_time
    ): Builder {

        return $this->getBaseBuilder($connection, $start_time, $end_time)
            ->select([
                'ugs.id',
                'ugs.start_time',
                'ugs.end_time',
                'ugs.game_ref',
                'ugs.user_id',
                'us.value as jurisdiction'
            ]);
    }

    public function getBuilderForAny(
        Connection $connection,
        string $start_time,
        string $end_time
    ): Builder {

        return $this->getBaseBuilder($connection, $start_time, $end_time)
            ->limit(1);
    }

    protected function getBaseBuilder(
        Connection $connection,
        string $start_time,
        string $end_time
    ): Builder{

        return $connection
            ->table('users_game_sessions AS ugs')
            ->join('users AS u', 'u.id', '=', 'ugs.user_id')
            ->join('users_settings as us', function($join) {
                $join->on('us.user_id', '=', 'u.id')
                    ->where('us.setting', '=', 'jurisdiction');
            })
            ->leftJoin('micro_games AS mg', function ($join) {
                $join->on('mg.ext_game_name', '=', 'ugs.game_ref')
                    ->on('mg.device_type_num', '=', 'ugs.device_type_num');
            })
            ->whereBetween('ugs.end_time', [$start_time, $end_time])
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
