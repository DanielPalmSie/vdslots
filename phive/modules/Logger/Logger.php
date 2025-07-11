<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api/PhModule.php';
require_once __DIR__ . '/DataCleaner.php';

use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;
use Monolog\Processor\IntrospectionProcessor;
use Psr\Log\LoggerInterface;

class Logger extends PhModule
{
    protected array $process_context = [];
    protected static array $loggers = [];

    protected LoggerInterface $logger;

    /**
     * @var int|string PSR int log level, or Monolog constant name
     */
    protected $default_level;

    public function __construct($logger = 'default')
    {
        $modules = $this->getSetting('additional_loggers', []);
        $log_file = $modules[$logger]['log_file'] ?? $this->getSetting('log_file');
        $minimum_level = $modules[$logger]['minimum_level'] ?? $this->getSetting('minimum_level');
        $default_level = $modules[$logger]['default_level'] ?? $this->getSetting('default_level');

        $this->logger = $this->createLogger(
            $logger,
            $log_file,
            $minimum_level
        );
        $this->default_level = MonologLogger::toMonologLevel($default_level);

        if ($logger === 'default') {
            self::$loggers[$logger] = $this;
        }
    }

    public function addIntrospectionProcessor(): void
    {
        $this->addProcessor(
            new IntrospectionProcessor(MonologLogger::DEBUG, [static::class], 1)
        );
    }

    /**
     * Add processors to the Logger
     *
     * @param callable Monolog processor
     */
    public function addProcessor(callable $processor): void
    {
        $this->logger->pushProcessor($processor);
    }


    /**
     * Ensure that the log file exists with correct permissions
     *
     * @param string $file
     * @param string $owner
     * @return void
     * @throws \Exception
     */
    public function setupLogFile($file, $owner): void
    {
        $process_owner = posix_getpwuid(posix_geteuid())['name'];

        $file_name = pathinfo($file, PATHINFO_FILENAME);
        if (empty($file_name) || empty($owner)) {
            throw new \Exception("Monolog[{$process_owner}]: Provided invalid configuration. File:{$file}, owner:{$owner}.");
        }

        $folder = pathinfo($file, PATHINFO_DIRNAME);

        if (!empty($folder) && !is_dir($folder)) {
            if (!mkdir($folder, 0777, true) && !is_dir($folder)) {
                throw new \Exception("Monolog[{$process_owner}]: Directory {$folder} was not created.");
            }

            chown($folder, $owner);
        }

        if (file_exists($file)) {
            if (!is_writable($file)) {
                throw new \Exception("Monolog[{$process_owner}]: Can't write to file {$file}. The file must be owned by {$owner}");
            }
            return;
        }

        $created = touch($file);
        if (!$created) {
            throw new \Exception("Monolog[{$process_owner}]: Can't create file {$file}. The folder must exist be owned by {$owner}.");
        }

        $changed_permission = chown($file, $owner);
        if (!$changed_permission) {
            throw new \Exception("Monolog[{$process_owner}]: File created {$file} but can't change the owner to ${owner}.");
        }

    }

    /**
     * @param String $name
     * @param String $log_file
     * @param String $minimum_level
     * @return MonologLogger
     */
    protected function createLogger($name, $log_file, $minimum_level): MonologLogger
    {
        try {
            $this->setupLogFile($log_file, $this->getSetting('log_file_owner'));

            // StreamHandler could throw exceptions
            $handler = new StreamHandler($log_file, $minimum_level);
        } catch (\Exception $e) {
            if (!empty($this->getSetting('log_on_handler_fallback'))) {
                error_log("Monolog error: " . $e->getMessage());
            }

            $handler = new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, $minimum_level);
        }

        return new MonologLogger($name, [$handler]);
    }

    public function channel(string $channel = 'default'): LoggerInterface
    {
        return $this->getLogger($channel)->logger;
    }

    public function getLogger($logger = 'default')
    {
        if (!isset(self::$loggers[$logger])) {
            self::$loggers[$logger] = new static($logger);
        }
        return self::$loggers[$logger];
    }

    public function debug($message, $context = []): void
    {
        $this->log($message, $context, MonologLogger::DEBUG);
    }

    /**
     * @param $message
     * @param array|null $context
     * @param int|string $level PSR int log level, or Monolog constant name. 0 will use the default from config
     */
    public function log($message, $context = [], $level = 0): void
    {
        if (!$level) {
            $level = $this->default_level;
        }

        $context = $context ?? [];
        if ($level > MonologLogger::DEBUG) {
            $context = $this->clearSensitiveFields($context);
        }

        if (!is_array($context)) {
            $context = [$context];
        }

        if (!empty($this->process_context)) {
            $context['process_context'] = $this->process_context;
        }

        if (!is_string($message)) {
            $message = var_export($message, true);
        }

        $this->logger->log($level, $message, $context);
    }

    public function info($message, $context = []): void
    {
        $this->log($message, $context, MonologLogger::INFO);
    }

    public function notice($message, $context = []): void
    {
        $this->log($message, $context, MonologLogger::NOTICE);
    }

    public function warning($message, $context = []): void
    {
        $this->log($message, $context, MonologLogger::WARNING);
    }

    public function error($message, $context = []): void
    {
        $this->log($message, $context, MonologLogger::ERROR);
    }

    public function critical($message, $context = []): void
    {
        $this->log($message, $context, MonologLogger::CRITICAL);
    }

    public function alert($message, $context = []): void
    {
        $this->log($message, $context, MonologLogger::ALERT);
    }

    public function emergency($message, $context = []): void
    {
        $this->log($message, $context, MonologLogger::EMERGENCY);
    }

    /**
     * Log the promotion so we can keep track of each step and notify people if needed
     * @param string $type
     * @param string $descr
     * @param bool $notify
     */
    public function logPromo($type = '', $descr = '', $notify = false)
    {
        error_log("promotion_log: " . $type . " - " . $descr);
        phive()->dumpTbl('promotion_log', ['promo_type' => $type, 'description' => $descr]);

        if ($notify) {
            $title = phive()->getSetting('domain') . ' - Marketing send out notification';
            $email = phive('MailHandler2')->getSetting('crm_emails');
            phive('MailHandler2')->mailLocal($title, "The campaign {$type} has status: {$descr}", '', $email);
        }
    }

    /**
     * Log a trace, with optional message
     *
     * @param string $message
     * @param int|string $level
     * @return void
     */
    public function logTrace(string $message = '', $level = 0)
    {
        if ($message) {
            $this->log($message, ['logged_with' => 'logTrace'], $level);
        }
        $e = new Exception();
        $this->log($e->getTraceAsString(), ['logged_with' => 'logTrace'], $level);
    }

    /**
     * Deleted old info from log tables
     *
     * @param string $tbl
     * @param string $col
     * @param string $tstr
     * @param string $primary_key
     * @deprecated Table deletion is not a great idea. trans_log should be replaced with file rotation. Look replacement for mailer_log use_case
     */
    public function deleteOlderThan(string $tbl, string $col = 'created_at', string $tstr = '-1 week', string $primary_key = 'id'): void
    {
        if (!in_array($tbl, ['trans_log', 'mailer_log'])) {
            throw new InvalidArgumentException('Arbitrary table delete not allowed');
        }

        /** @var SQL $generator */
        $generator = phive('SQL');
        /** @var SQL $executor */
        $executor = phive('SQL');
        $date = phive()->hisMod($tstr);

        $generator->deleteBatched("SELECT $primary_key FROM $tbl WHERE $col < '$date'",
            function ($r) use ($executor, $tbl, $primary_key) {
                $executor->delete($tbl, [$primary_key => $r[$primary_key]]);
            });
    }

    /**
     * Add information to help track individual processes
     *
     * @param mixed $extra
     * @return void
     */
    public function trackProcess($extra = null): void
    {
        $this->process_context = [
            'PID' => getmypid()
        ];

        if (!empty($extra)) {
            $this->process_context = array_merge(
                $this->process_context,
                is_array($extra) ? $extra : [$extra]
            );
        }
    }

    public function clearSensitiveFields($data)
    {
        $cleaner = new DataCleaner();
        return $cleaner->clearSensitiveFields($data);
    }

}
