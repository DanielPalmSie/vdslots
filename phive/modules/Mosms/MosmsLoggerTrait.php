<?php

namespace Mosms;

trait MosmsLoggerTrait
{
    /**
     * Logging all necessary actions
     * @param string $message
     * @param array $payload
     * @param string $level
     * @return void
     */
    private function log(string $message, array $payload, string $level = 'debug'): void
    {
        switch ($level) {
            case 'error':
                phive('Logger')->getLogger('mosms')->error($message, $payload);
                break;
            default:
                phive('Logger')->getLogger('mosms')->debug($message, $payload);
                break;
        }
    }
}
