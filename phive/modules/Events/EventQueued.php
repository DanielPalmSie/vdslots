<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api/PhModule.php';
require_once __DIR__ . '/../../traits/RabbitMQTrait.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Wire\AMQPTable;
use Videoslots\Events\RabbitMQ\RabbitMQConnectionFactory;

class EventQueued extends PhModule
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
     * Simulate mode flag
     *
     * @var bool
     */
    protected bool $simulate_mode = false;


    /**
     * All the queue configuration
     *
     * @var array
     */
    private $config;
    private $proxy_config;
    private $delay_exchange;
    private $topic_exchange;
    private $queueSettings;
    private $events;

    /**
     * Array of consumer names.
     * Usually there is only one item in array, meaning that consumer listens to a single queue.
     * However, it is possible for a consumer to listen to multiple queues (multiple items in array).
     *
     * Equals null in publisher context.
     *
     * @var array|null
     */
    protected $consumer_names;

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
    public function __construct($consumer_names = null)
    {
        $this->consumer_names = $consumer_names;
        $this->queueSettings = phive('Distributed')->getSetting('queue-consumers');
        $this->events = $this->getEvents();
        $this->debug_mode = phive('Distributed')->getSetting('queue_debug');
        $this->config = phive('Distributed')->getSetting('queue_server');
        $this->proxy_config = phive('Distributed')->getSetting('queue_proxy');
        $this->forced_host = getenv('FORCE_HOST');
        $this->forced_port = getenv('FORCE_PORT');
        $this->simulate_mode = phive('Distributed')->getSetting('queue-simulated', false);
    }

    /**
     * Check if the event is supported.
     *
     * @param $event
     * @return bool
     */
    public function isEventSupported($event): bool
    {
        $events = $this->getEvents();
        return in_array(ucfirst($event), $events);
    }

    /**
     * Returns a list of the supported events by this consumer
     *
     * @return array|mixed|string[]
     */
    protected function getEvents()
    {
        if (!empty($this->events)) {
            return $this->events;
        }

        $this->events = [];
        foreach ($this->queueSettings as $consumer) {
            if (empty($consumer['topics'])){
                continue;
            }
            foreach ($consumer['topics'] as $topic) {
                if (!empty($topic['name']) && !empty($topic['events'])) {
                    $topic_name = ucfirst($topic['name']);
                    $topic_events = array_map(function($event) use ($topic_name) {
                        return $topic_name. ucfirst($event);
                    }, $topic['events']);
                    $this->events = array_merge($this->events, $topic_events);
                }
            }
        }
        return $this->events;
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
        try {
            // If we are already connected return
            if ($this->connection && $this->connection->isConnected()) {
                return;
            }

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

            $this->channel = $this->connection->channel();
        } catch (\Exception $e) {
            $this->logError("na", $e, "connect::generic");
        }
    }

    /**
     * Declare the exchanges if they don't exist
     *
     * @param string $topic_name The origin of the event
     * @param $delay
     * @param $routing_key
     */
    protected function declareExchanges($topic_name, $delay, $routing_key)
    {
        $this->topic_exchange = $this->declareTopicExchange($topic_name);
        if ($delay) {
            $this->delay_exchange = $this->declareDelayExchange($topic_name, $delay, $routing_key);
        }
    }

    /**
     * After a certain delay the msg will expire and will be sent to the topic exchange
     *
     * @param $topic_name
     * @param $delay
     * @param $routing_key
     * @return string
     */
    private function declareDelayExchange($topic_name, $delay, $routing_key)
    {
        $delay_queue_name = "$topic_name-$delay-delay-queue";
        $delay_exchange_name = "$topic_name-$delay-delay-exchange";
        $right_now_exchange = "topic_$topic_name";
        // now create the delayed queue and the exchange
        $this->channel->queue_declare(
            $delay_queue_name,
            false,
            false,
            false,
            true,
            true,
            new AMQPTable([
                'x-message-ttl' => (int)$delay,
                //"x-expires" => $delay + 1000, // queue expiration
                'x-dead-letter-exchange' => $right_now_exchange,
                //'x-dead-letter-routing-key' => $routing_key // allows to change original message routing key, interesting if we need to implement some error handler in the future
            ])
        );
        $this->channel->exchange_declare($delay_exchange_name, 'topic');
        $this->channel->queue_bind($delay_queue_name, $delay_exchange_name, $routing_key);

        return $delay_exchange_name;
    }

    /**
     * Routes messages to queues subscribed to certain topic
     *
     * @param $topic_name
     * @return string
     */
    protected function declareTopicExchange($topic_name)
    {
        $right_now_exchange = "topic_$topic_name";
        $this->channel->exchange_declare(
            $right_now_exchange,
            'topic',
            false,
            true,
            true
        );
        return $right_now_exchange;
    }

    /**
     * Publishes the message with delay
     *
     * @param $routing_key
     * @param $body
     * @param int $delay milliseconds
     */
    protected function publish($routing_key, $body, $delay)
    {
        $msg = $this->generateMessage($body, true);
        $exchange = $delay ? $this->delay_exchange : $this->topic_exchange;
        $this->channel->basic_publish($msg, $exchange, $routing_key);
    }

    /**
     * @param $body
     * @param bool $persist This will indicate if we want to persist the message in disk
     * @return AMQPMessage
     */
    protected function generateMessage($body, $persist = true)
    {
        $properties = [];
        if ($persist) {
            $properties['delivery_mode'] = AMQPMessage::DELIVERY_MODE_PERSISTENT;
        }

        return new AMQPMessage(json_encode($body), $properties);
    }


    /**
     * Close queue connection, used when we stop/restart the process
     */
    protected function close()
    {
        try {
            if (!is_null($this->channel)) {
                $this->channel->close();
            }
            if (!is_null($this->connection)) {
                $this->connection->close();
            }
            $this->debug("Channel and connection closed");
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * Disables the proxy to allow for direct connection to rabbit server
     */
    protected function disableProxy()
    {
        $this->proxy_config['enabled'] = false;
    }


    /**
     * @param string $setting_name
     * @param string|null $consumer_name
     * @return mixed
     */
    protected function getQueueSetting($setting_name, $consumer_name = null)
    {
        if (empty($consumer_name)) {
            $consumer_name = $this->consumer_names[0];
        }

        $setting = $this->queueSettings[$consumer_name][$setting_name] ?? $this->queueSettings['DEFAULT'][$setting_name];
        if (is_null($setting)) {
            dd2("You need to add the setting $setting_name to queue-consumers on Distributed.config.php");
        }
        return $setting;
    }


    /**
     * Used only when the debug flag is set, output in supervisor will out to the standard output and not the error one
     *
     * @param mixed $dump
     */
    protected function debug($dump)
    {
        if ($this->isDebug()) {
            phive('Logger')->getLogger('queue_messages')->info("EventQueued::debug", [$dump]);
        }
    }

    /**
     * Handles logging error on exceptions caught during execution
     *
     * @param string $queue_name
     * @param Exception $exception
     */
    protected function logError($queue_name, $exception, $tag = "na")
    {
        $msg = get_called_class() . " error: ";
        $msg .= json_encode([
            'tag' => $tag,
            'queue_name' => $queue_name,
            'message' => $exception->getMessage(),
            'type' => get_class($exception),
            'trace' => $exception->getTraceAsString()
        ]);

        error_log($msg);
        $this->debug($msg);
    }

    /**
     * Handles logging info on certain cases during execution
     *
     * @param string $dump
     * @param string|null $consumer_name
     *
     * @return void
     */
    protected function info($dump, $consumer_name = null)
    {
        if ($this->getQueueSetting('log_info', $consumer_name)) {
            dumpTbl("rabbitmq_" . get_called_class() . " info:", $dump);
        }
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

    protected function isSimulate()
    {
        return $this->simulate_mode;
    }

    /**
     * @param $origin
     * @param $event
     * @param $data
     * @return void
     * @throws Exception
     */
    public function simulate($origin, $event, $data)
    {
        $method = 'on' . $event;
        $files = glob(__DIR__ . '/*Handler.php');

        foreach ($files as $file) {
            $className = basename($file, '.php');
            if (! @include_once( $file )) // @ - to suppress warnings,
                throw new \Exception ("$className.php does not exist");

            $class = phive("Events/$className");
            if (empty($class)) {
                continue;
            }
            if (method_exists($class, $method)) {
                call_user_func_array([$class, $method], $data);
                return;
            }
        }
        throw new \Exception ("No handler found for $event on $origin");
    }

}
