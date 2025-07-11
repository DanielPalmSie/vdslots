<?php

require_once __DIR__ . '/BackgroundProcess.php';
require_once __DIR__ . '/BackgroundProcessor.php';

class BackgroundFakeProcess extends BackgroundProcess
{
    public function start(): void
    {
        BackgroundProcessor::exec($this->tracker, explode(' ', $this->command));
    }

    public function isRunning(): bool
    {
        return false;
    }

    public function hasStatus(string $status): bool
    {
        return phive()->getMiscCache($this->tracker) === 'success';
    }

    public function stop(): bool
    {
        return true;
    }

}
