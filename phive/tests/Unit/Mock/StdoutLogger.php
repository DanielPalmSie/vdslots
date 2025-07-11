<?php

namespace Tests\Unit\Mock;

use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;

class StdoutLogger implements LoggerInterface
{

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array|null $context
     *
     * @return void
     */
    public function log($level, $message, $context = null)
    {
        $color = $this->getColor($level);
        $contextString = '';
        if (is_array($context)) {
            $contextString = json_encode($context);
        }

        echo "\033[{$color}m[{$level}]  =>  {$message} {$contextString}\033[0m\n";
    }

    /**
     * Get ANSI color code for the log level
     *
     * @param string $level
     *
     * @return string
     */
    private function getColor(string $level): string
    {
        switch ($level) {
            case LogLevel::EMERGENCY:
            case LogLevel::ALERT:
            case LogLevel::CRITICAL:
                return '31';
            case LogLevel::ERROR:
                return '33';
            case LogLevel::WARNING:
                return '35';
            case LogLevel::NOTICE:
            case LogLevel::INFO:
                return '36';
            case LogLevel::DEBUG:
                return '32';
            default:
                return '0';
        }
    }

    /**
     * Emergency message
     *
     * @param string $message
     * @param array|null $context
     *
     * @return void
     */
    public function emergency($message, $context = null)
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Alert message
     *
     * @param string $message
     * @param array|null $context
     *
     * @return void
     */
    public function alert($message, $context = null)
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Critical message
     *
     * @param string $message
     * @param array|null $context
     *
     * @return void
     */
    public function critical($message, $context = null)
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Error message
     *
     * @param string $message
     * @param array|null $context
     *
     * @return void
     */
    public function error($message, $context = null)
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Warning message
     *
     * @param string $message
     * @param array|null $context
     *
     * @return void
     */
    public function warning($message, $context = null)
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Notice message
     *
     * @param string $message
     * @param array|null $context
     *
     * @return void
     */
    public function notice($message, $context = null)
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Info message
     *
     * @param string $message
     * @param array|null $context
     *
     * @return void
     */
    public function info($message, $context = null)
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Debug message
     *
     * @param string $message
     * @param array|null $context
     *
     * @return void
     */
    public function debug($message, $context = null)
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }
}
