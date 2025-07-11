<?php

namespace App\Commands\Checks;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;

interface DatabaseCheckInterface
{
    public function canRun(string $checkName): bool;

    public function name(): string;

    public function description(): string;

    public function getBuilderForAny(Connection $connection, string $start_time, string $end_time): Builder;

    public function getBuilderForAll(Connection $connection, string $start_time, string $end_time): Builder;

    public function getHeaders(): array;

    public function requiresUserData(): bool;
}
