<?php

namespace Tests\Unit\Phive\Modules\Logger\Wrappers;

require_once __DIR__ . '/../../../../../../../phive/modules/Logger/Logger.php';

use Psr\Log\LoggerInterface;
use Mockery\MockInterface;

class LoggerWrapper extends \Logger
{
    public function __construct()
    {
        echo "LoggerWrapper";
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getMock(): MockInterface
    {
        return $this->logger;
    }
}