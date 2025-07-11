<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../traits/RabbitMQTrait.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Connection\Heartbeat\PCNTLHeartbeatSender;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;
use Videoslots\Events\RabbitMQ\RabbitMQConnectionFactory;

class Queued
{
    use RabbitMQTrait;

    /**
     * The RabbitMQ connection instance.
     *
     * @var AMQPStreamConnection
     */
    protected $connection = null;

    /**
     * The RabbitMQ channel instance.
     *
     * @var AMQPChannel
     */
    protected $channel = null;


    /**
     * Debug mode flag
     *
     * @var bool
     */
    protected $debug_mode = false;


    /**
     * All the queue configuration
     *
     * @var array
     */
    protected $config;

    /**
     * To force the host on the consumers
     */
    private $forced_host = false;

    /**
     * To force the host on the consumers
     */
    private $forced_port = false;


    /**
     *
     * Queued constructor.
     */
    public function __construct()
    {
        $this->debug_mode = phive('Distributed')->getSetting('queue_debug');
        $this->config = phive('Distributed')->getSetting('queue_server');
        $this->proxy_config = phive('Distributed')->getSetting('queue_proxy');
        $this->forced_host = getenv('FORCE_HOST');
        $this->forced_port = getenv('FORCE_PORT');
    }

    /**
     * @return void
     */
    /**
     * @param null|float $connection_timeout
     * @param null|float $read_write_timeout
     * @param null|bool $keepalive
     * @param null|float|int $heartbeat
     */
    protected function connect($connection_timeout = 3.0, $read_write_timeout = 3.0, $keepalive = false, $heartbeat = 0)
    {
        $config = $this->getServerConfig();
        $this->connection = RabbitMQConnectionFactory::createConnection(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['pwd'],
            $config['vhost'],
            false,
            'AMQPLAIN',
            null,
            'en_US',
            $connection_timeout,
            $read_write_timeout,
            null,
            $keepalive,
            $heartbeat
        );

        if ($heartbeat > 0) {
            $sender = new PCNTLHeartbeatSender($this->connection); // fixes php problems with long processes heartbeat @see https://github.com/php-amqplib/RabbitMqBundle/issues/301
            $sender->register();
        }

        $this->channel = $this->connection->channel();
    }

    /**
     * Force connection to be established to one of the nodes declared in the configuration
     * @param $server_name
     * @return void
     */
    protected function forceConnection($server_name)
    {
        if ($this->proxy_config['enabled'] && !empty($this->proxy_config['nodes'][$server_name])) {
            $this->proxy_config['enabled'] = false;
            $this->config = $this->proxy_config['nodes'][$server_name];
        }
    }

    /**
     * Reload config from settings.
     * Could be useful for resetting after forcing connection ( @see forceConnection() )
     * @return void
     */
    protected function reloadConfig(): void
    {
        $this->config = phive('Distributed')->getSetting('queue_server');
        $this->proxy_config = phive('Distributed')->getSetting('queue_proxy');
    }

    /**
     * @param $name
     * @param bool $persist
     */
    protected function declare($name, $persist = true)
    {
        $res = $this->channel->queue_declare($this->config['brand'] . '-' . $name, false, $persist, false, false);

        $this->debug("Queue: {$res[0]} | Message count: {$res[1]} | Consumer count: {$res[2]}");

        return $res;
    }

    /**
     * Close queue connection, used when we stop/restart the process
     */
    protected function close()
    {
        try {
            if (!empty($this->channel)) {
                $this->channel->close();
                $this->connection->close();
                $this->debug("Channel and connection closed");
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    protected function disableProxy()
    {
        $this->proxy_config['enabled'] = false;
    }

    public function purge($name, $nowait = true){
        $this->connect();
        $res = $this->channel->queue_purge($this->config['brand'] . '-' . $name, $nowait);
        $this->close();
        return $res;
    }

    /**
     * @param $body
     * @param bool $persist This will indicate if we want to persist the message in disk
     * @return AMQPMessage
     */
    protected function generateMessage($body, $persist = true)
    {
        if ($persist) {
            $properties = ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT];
        }

        return new AMQPMessage(json_encode($body), $properties ?? []);
    }

    /**
     * Used only when the debug flag is set, output in supervisor will out to the standard output and not the error one
     *
     * @param mixed $dump
     */
    protected function debug($dump) {
        if ($this->isDebug()) {
            phive('Logger')->getLogger('queue_messages')->info("Queued::debug", [$dump]);
        }
    }

    /**
     * Handles logging error on exceptions caught during execution
     *
     * @param string $queue_name
     * @param Exception $exception
     */
    protected function logError($queue_name, $exception)
    {
        $msg = get_called_class() . " error: ";
        $msg .= json_encode([
            'queue_name' => $queue_name,
            'message' => $exception->getMessage(),
            'type' => get_class($exception),
            'trace' => $exception->getTraceAsString()
        ]);

        error_log($msg);
        $this->debug($msg);
    }

    /**
     * We check if we run the component in debug mode
     *
     * @return bool
     */
    protected function isDebug()
    {
        return $this->debug_mode;
    }

    /**
     * Function to test and debug queues publishing and consuming
     *
     * @param string $message Message to print and store in db, it is recommended to use an ID for the message like msg23
     * @param int $sleep In case we want to sleep the process to emulate long running and blocking operations
     */
    public function testQueue($message, $sleep = 0)
    {
        echo $message . "processed, sleeping for $sleep seconds\n";
        sleep($sleep);
        phive()->dumpTbl('queue-test', $message);
        echo $message . "finished\n";
    }

}
