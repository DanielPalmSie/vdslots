<?php

namespace Tests\Unit\Modules;

require_once __DIR__ . '/../../../../phive/phive.php';


class RedisClusterTest extends RedisTest
{
    public function __construct(?string $name = null, array $data = [], $dataName = ''){
        parent::__construct($name, $data, $dataName);
        if (!phive('Redis')->inClusterMode()) {
            $this->markTestSkipped('Redis cluster mode is disabled');
        }
    }
}
