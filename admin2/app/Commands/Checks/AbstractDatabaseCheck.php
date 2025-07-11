<?php

namespace App\Commands\Checks;

abstract class AbstractDatabaseCheck
{
    public function canRun(string $checkName): bool
    {
        return $this->name === $checkName;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

}
