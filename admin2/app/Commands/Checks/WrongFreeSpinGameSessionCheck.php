<?php

namespace App\Commands\Checks;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;

class WrongFreeSpinGameSessionCheck extends AbstractDatabaseCheck implements DatabaseCheckInterface
{
    protected string $name = 'wrong-free-spin-game-session';
    protected string $description = 'Get game sessions that should have been recorded in bonus entries as a FS game session but haven\'t';

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
            ->join('users_settings as us', function($join){
                $join->on('us.user_id', '=', 'u.id')
                    ->where('us.setting', '=', 'jurisdiction');
            })
            ->leftJoin('bonus_entries AS be', 'be.game_session_id', '=', 'ugs.id')
            ->whereBetween('ugs.end_time', [$start_time, $end_time])
            ->where('ugs.bet_amount', 0)
            ->where('ugs.bet_cnt', 0)
            ->where('ugs.win_amount', '>', 0)
            ->where('ugs.win_cnt', '>', 0)
            ->whereNotNull('be.id');
    }

    public function getHeaders(): array
    {
        return [
            'users_game_sessions_id',
            'start_time',
            'end_time',
            'game_ref',
            'user_id',
            'jurisdiction'
        ];
    }

    public function requiresUserData(): bool
    {
        return true;
    }
}
