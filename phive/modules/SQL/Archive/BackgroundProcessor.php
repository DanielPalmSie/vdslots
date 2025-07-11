<?php

require_once __DIR__ . '/BackgroundProcess.php';
require_once __DIR__ . '/BackgroundFakeProcess.php';

class BackgroundProcessor
{
    public static bool $fake_background = false;

    /** @var BackgroundProcess[] $queued_processes */
    private array $queued_processes = [];

    /** @var BackgroundProcess[] $active_processes */
    private array $active_processes = [];

    /** @var BackgroundProcess[] $finished_processes */
    private array $finished_processes = [];

    /** @var BackgroundProcess[] $failed_processes */
    private array $failed_processes = [];

    private int $progress = 0;
    
    /**
     * Add function to the parallel queue
     *
     * @param string $module
     * @param string $func
     * @param array $args
     * @return void
     */
    public function add(string $module, string $func, array $args = []): void
    {
        if (self::$fake_background) {
            $this->queued_processes[] = new BackgroundFakeProcess($module, $func, $args);
        } else {
            $this->queued_processes[] = new BackgroundProcess($module, $func, $args);
        }
    }

    /**
     * Run functions form the queue in parallel by starting child processes and tracking the progress
     *
     * @param null $max_processes
     * @param Closure|null $progress_callback
     * @return void
     * @throws Exception
     */
    public function runAllProcesses($max_processes = null, Closure $progress_callback = null): void
    {
        while (count($this->queued_processes) > 0 || count($this->active_processes) > 0) {

            // add room for the next batch of processes
            $this->active_processes = array_filter($this->active_processes, function (BackgroundProcess $process) {
                if ($process->isRunning()) {
                    return true;
                }

                if ($process->hasStatus($process::STATUS_SUCCESS)) {
                    $this->finished_processes[] = $process;
                } else {
                    $this->failed_processes[] = $process;
                    // something failed, stop archiving process
                    $this->queued_processes = [];
                }

                return false;
            });

            // start the next batch of queued processes
            while (count($this->queued_processes) > 0 && count($this->active_processes) < $max_processes) {
                $process = array_shift($this->queued_processes);
                $process->start();

                $this->active_processes[] = $process;
            }
            
            if ($progress_callback) {
                $this->handleProgressCallback($progress_callback);
            }
            
            sleep(1);
        }

        $failed = array_map(static function (BackgroundProcess $process) {
            return $process->getTracker();
        }, $this->failed_processes);

        if (!empty($failed)) {
            throw new Exception("Exit with error. These processes failed: " . implode(',', $failed));
        }

        $this->finished_processes = [];
        $this->active_processes = [];
        $this->failed_processes = [];
        $this->queued_processes = [];
    }

    /**
     * Progress of the current processes
     *
     * @param Closure $progress_callback
     */
    public function handleProgressCallback(Closure $progress_callback): void
    {
        $queued = count($this->queued_processes);
        $active = count($this->active_processes);
        $failed = count($this->failed_processes);
        $succeeded = count($this->finished_processes);

        $finished = $failed + $succeeded;
        $all = $finished + $queued + $active;
        $progress = (int)($finished * 100 / $all);

        if ($progress === $this->progress) {
            return;
        }
        
        $this->progress = $progress;
        $progress_callback($progress);
    }

    /**
     * Child process handler
     *
     * @param string $tracker
     * @param array $args
     * @return void
     */
    public static function exec(string $tracker, array $args): void
    {
        // Create new instance to prevent the override of process tracker from singleton
        $logger = new Logger('archive');
        $logger->trackProcess([
            'PROCESS_TRACKER_ID' => $tracker,
            'arguments' => $args
        ]);

        $logger->debug('Start');
        try {
            phive()->miscCache($tracker, 'started', true);

            array_shift($args); // script

            $module = array_shift($args);
            $func = array_shift($args);

            if (empty($module) || empty($func)) {
                throw new Exception('Invalid arguments provided');
            }

            if (method_exists(phive($module), 'setLogger')) {
                phive($module)->setLogger($logger);
            }

            phive()->apply($module, $func, $args);

            phive()->miscCache($tracker, 'success', true);
        } catch (Exception $e) {
            phive()->miscCache($tracker, 'error', true);

            $logger->error('Parallel execution exception: ' . $e->getMessage());
        }
        $logger->debug('Finish');
    }
}
