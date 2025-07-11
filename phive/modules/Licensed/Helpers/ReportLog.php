<?php

class ReportLog
{
    private string $log_file_path;
    private bool $logs_enabled;
    private string $license_country;

    public function __construct(string $log_folder, string $license_country, bool $logs_enabled, string $log_prefix = 'log_')
    {
        if (! file_exists($log_folder)) {
            // create directory/folder uploads.
            if (!mkdir($concurrentDirectory = $log_folder, 0777, true) && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }

        $this->license_country = $license_country;
        $this->logs_enabled = $logs_enabled;
        $this->log_file_path = $log_folder . '/' . $log_prefix . date('d-m-Y') . '.log';
    }

    /**
     * Create a log with the data
     * @param string $log_msg
     */
    public function log(string $log_msg): void
    {
        if (!$this->logs_enabled) {
            return;
        }

        try {
            $debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $trace_datum = count($debug) > 1 ? $debug[1] : $debug[0];

            $data_to_log = date('Y-m-d H:i:s') . "-" .
                basename($trace_datum['file']) . "-" .
                $trace_datum['line'] . "-" . $log_msg . "\n";

            file_put_contents($this->log_file_path,
                $data_to_log,
                FILE_APPEND
            );
        } catch (\Exception $e) {
            phive()->dumpTbl('report_log_failed', $e->getMessage());
            phive()->dumpTbl('report_log_message', $this->license_country . ': ' . $log_msg);
        }
    }
}