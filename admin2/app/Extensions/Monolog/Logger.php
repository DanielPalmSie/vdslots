<?php

namespace App\Extensions\Monolog;

use Monolog\Logger as MonologLogger;

class Logger extends MonologLogger
{

    public function addInfo(string $message, array $context = [])
    {
        $this->addRecord(static::INFO, (string) $message, $context);
    }

    public function addError(string $message, array $context = [])
    {
        $this->addRecord(static::ERROR, (string) $message, $context);
    }

    public function addNotice(string $message, array $context = [])
    {
        $this->addRecord(static::NOTICE, (string) $message, $context);
    }

    public function addWarning(string $message, array $context = [])
    {
        $this->addRecord(static::WARNING, (string) $message, $context);
    }

}