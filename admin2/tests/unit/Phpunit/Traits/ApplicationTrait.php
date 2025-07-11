<?php

namespace Tests\Unit\Phpunit\Traits;

trait ApplicationTrait
{
    protected static function loadApplication(): void
    {
        require_once __DIR__ . '/../../../../bootstrap.php';
        require_once __DIR__ . '/../../../../global_functions.php';

        self::$app = $app;
    }
}