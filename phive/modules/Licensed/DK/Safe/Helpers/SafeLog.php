<?php


class SafeLog
{
    /**
     * @var string
     */
    private $filename;

    /**
     * @var string
     */
    private $log_file_path;

    /**
     * @var bool
     */
    private $logs_enabled;

    public function __construct(string $filename, bool $logs_enabled = true)
    {
        $this->filename = $filename;
        $this->logs_enabled = $logs_enabled;

        if (! file_exists($this->filename)) {
            // create directory/folder uploads.
            mkdir($this->filename, 0777, true);
        }

        $this->log_file_path = $this->filename . '/log_safe_' . date('d-m-Y') . '.log';
    }

    /**
     * Create a log with the data sends to SAFE
     * @param $log_msg
     */
    public function log($log_msg)
    {
        if ($this->logs_enabled) {
            try {
                $debug = debug_backtrace();

                $data_to_log = date('Y-m-d H:i:s') . "-" .
                    basename($debug[0]['file']) . "-" .
                    $debug[0]['line'] . "-" . $log_msg . "\n";

                file_put_contents($this->log_file_path,
                    $data_to_log,
                    FILE_APPEND
                );
            } catch (Exception $e) {
                phive()->dumpTbl('safe_log_failed', $e->getMessage());
            }
        }
    }
}