<?php

class BackgroundProcess
{
//    public const STATUS_PROGRESS = 'started';
    public const STATUS_SUCCESS = 'success';
//    public const STATUS_FAILED = 'error';
    
    protected string $tracker;
    protected string $command;
    protected int $pid;

    public function __construct($module, $func, $args)
    {
        $arguments = implode(' ', $args);
        $this->command = __DIR__ . "/exec.php {$module} {$func} {$arguments}";
        $this->tracker = 'parallel-process-' . phive()->uuid();
    }

    public function start(): void
    {
        $cmd = "PROCESS_TRACKER_ID={$this->tracker} php {$this->command} > /dev/null 2>&1 & echo $!";
        $op = shell_exec($cmd);
        $this->pid = (int)$op;
    }

    public function isRunning(): bool
    {
        exec('ps -p ' . $this->pid, $op);
        return isset($op[1]);
    }

    public function hasStatus(string $status): bool
    {
        return phive()->getMiscCache($this->tracker) === $status;
    }

    public function stop(): bool
    {
        exec('kill ' . $this->pid);
        return !$this->isRunning();
    }

    public function getTracker(): string
    {
        return $this->tracker;
    }

}
