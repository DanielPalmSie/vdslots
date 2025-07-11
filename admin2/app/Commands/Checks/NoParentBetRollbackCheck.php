<?php

namespace App\Commands\Checks;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;

class NoParentBetRollbackCheck extends AbstractDatabaseCheck implements DatabaseCheckInterface
{
    protected string $name = 'no-parent-bet-rollback';
    protected string $description = 'Get bet rollbacks that dont have / have a wrong parent id in cash transactions table';

    public function getBuilderForAll(
        Connection $connection,
        string $start_time,
        string $end_time
    ): Builder {

        return $this->getBaseBuilder($connection, $start_time, $end_time)
            ->select(['ct.id', 'ct.user_id', 'ct.timestamp', 'us.value as jurisdiction']);
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
    ): Builder {

        return $connection
            ->table('cash_transactions AS ct')
            ->join('users AS u', 'u.id', '=', 'ct.user_id')
            ->join('users_settings as us', function($join) {
                $join->on('us.user_id', '=', 'u.id')
                    ->where('us.setting', '=', 'jurisdiction');
            })
            ->leftJoin('bets AS b', 'b.id', '=', 'ct.parent_id')
            ->whereBetween('ct.timestamp', [$start_time, $end_time])
            ->where('ct.transactiontype', 7)
            ->whereNull('b.id');
    }

    public function getHeaders(): array
    {
        return [
            'cash_transaction_id',
            'user_id',
            'timestamp',
            'jurisdiction'
        ];
    }

    public function requiresUserData(): bool
    {
        return true;
    }
}
