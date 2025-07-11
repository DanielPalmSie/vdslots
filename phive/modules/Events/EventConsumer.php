<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpAmqpLib\Message\AMQPMessage;

require_once('EventQueued.php');

class EventConsumer extends EventQueued
{
    private object $event_handler;

    private array $consumer_tags = [];

    public function __construct(array $consumer_names)
    {
        parent::__construct($consumer_names);

        $this->defineSignals();

    }

    public function init(): void
    {
        $queue_names = [];
        try {
            $this->connect();

            foreach ($this->consumer_names as $consumer_name) {
                $queue_names[] = $this->setupQueueConsumer($consumer_name);
            }

            while ($this->channel->is_consuming()) {
                try {
                    $this->channel->wait(null, false, 60);
                } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                    try {
                        $this->connection->checkHeartBeat();
                        $this->debug("Wait timed out doing heart beat." . implode(', ', $this->consumer_names));
                    } catch (\Exception $e) {
                        $this->logError(implode(', ', $queue_names), $e, "init::timeout");
                        $this->close();
                    }
                    continue;
                } catch (\Exception $e) {
                    $this->logError(implode(', ', $queue_names), $e, "init::inner");
                    $this->close();
                }

            }
            $this->close();

        } catch (\Exception $e) {
            sleep(30);
            $this->logError(implode(', ', $queue_names), $e, "init::generic");
        }
    }

    /**
     * Setup a consumer for a given queue name.
     *
     * @param string $consumer_name
     *
     * @return string
     */
    private function setupQueueConsumer(string $consumer_name): string
    {
        $topics = $this->getQueueSetting('topics', $consumer_name);
        $license = $this->getQueueSetting('license', $consumer_name);
        $brand = phive('Distributed')->getSetting('local_brand');

        phive('Logger')->getLogger('queue_messages')->debug("EventConsumer::timeoutSessions init ", [$this->getQueueSetting("queue_name", $consumer_name)]);
        if ($this->getQueueSetting("queue_name", $consumer_name) === "trophy-events") {
            phive('Logger')->getLogger('queue_messages')->debug("EventConsumer::timeoutSessions topics ", [$topics]);
            phive('Logger')->getLogger('queue_messages')->debug("EventConsumer::timeoutSessions brand ", [$brand]);
        }

        $this->event_handler = $this->getEventHandler($consumer_name);

        $callback = $this->getCallbackFunction($consumer_name);

        list($queue_name, ,) = $this->channel->queue_declare($this->getQueueSetting("queue_name", $consumer_name), false, true, false, false);
        $this->channel->basic_qos(0, $this->getQueueSetting('prefetch-size', $consumer_name), null);

        foreach ($topics as $topic) {
            //declare the topic exchanges
            $topic_name = $topic['name'];
            $topic_exchange = $this->declareTopicExchange(strtolower($topic_name));
            foreach ($topic['events'] as $event) {
                $event_name = ucfirst($topic_name) . ucfirst($event);
                $this->channel->queue_bind($queue_name, $topic_exchange, "$brand.$topic_name.$event_name.$license");
            }
        }

        $this->consumer_tags[] = $this->channel->basic_consume($queue_name, '', false, false, false, false, $callback);

        return $queue_name;
    }

    /**
     * Get the callback function with event_handler passed as an extra parameter.
     *
     * @param string $consumer_name
     * @return Closure
     */
    private function getCallbackFunction(string $consumer_name)
    {
        return function (AMQPMessage $msg) use ($consumer_name) {
            list($brand, $origin, $event, $license) = explode('.', $msg->get('routing_key'));
            $params = json_decode($msg->body, true);

            $this->doEventHandling($event, $params, $consumer_name);
            $msg->get('channel')->basic_ack($msg->get('delivery_tag'));
        };
    }

    /**
     * @param string $event
     * @param array|null $params
     * @param string $consumer_name
     * @return false|mixed
     */
    private function doEventHandling(string $event, ?array $params, string $consumer_name)
    {
        $method = 'on' . $event;
        if (method_exists($this->event_handler, $method)) {
            if (empty($params)) {
                $params = [];
            }
            return call_user_func_array([$this->event_handler, $method], $params);
        }
        $this->info("Handler not declared for $method on $consumer_name");

        return false;
    }


    /**
     * @param string $consumer_name
     *
     * @return object|bool|Phive The global instance.
     */
    private function getEventHandler(string $consumer_name)
    {
        $module = $this->getQueueSetting('event_handler');
        try {
             if (! @include_once(  __DIR__ . "/../$module.php" )) // @ - to suppress warnings,
                throw new \Exception ("$module.php does not exist");

            $event_handler = phive($module);

            if (empty($event_handler)) {
                dd2("You need to declare the event handler for $consumer_name on $module.php");
            }
            return $event_handler;
        } catch (\Exception $e) {
            $this->logError("error", $e, "getEventHandler::general");
            $this->close();
            sleep(30);
        }
    }

    private function defineSignals()
    {
        if (extension_loaded('pcntl')) {
            define('AMQP_WITHOUT_SIGNALS', false);

            pcntl_signal(SIGTERM, [$this, 'signalHandlerEvents']);
            pcntl_signal(SIGHUP, [$this, 'signalHandlerEvents']);
            pcntl_signal(SIGINT, [$this, 'signalHandlerEvents']);
            pcntl_signal(SIGQUIT, [$this, 'signalHandlerEvents']);
            pcntl_signal(SIGUSR1, [$this, 'signalHandlerEvents']);
            pcntl_signal(SIGUSR2, [$this, 'signalHandlerEvents']);
            pcntl_signal(SIGALRM, [$this, 'signalHandlerEvents']);
        } else {
            echo 'Unable to process event signals.' . PHP_EOL;
            exit(1);
        }
    }

    /**
     * Signal handler
     *
     * @param  int $signal_number
     * @return void
     */
    public function signalHandlerEvents($signal_number)
    {
        $this->debug('Handling signal: #' . $signal_number);

        switch ($signal_number) {
            case SIGTERM:  // 15 : supervisor default stop
            case SIGQUIT:  // 3  : kill -s QUIT
            case SIGINT:   // 2  : ctrl+c
            case SIGHUP:   // 1  : kill -s HUP Restart will kill as well as Supervisord will handle it
                $this->stop();
                break;
            case SIGUSR1:  // 10 : kill -s USR1
                // send an alarm in 1 second
                pcntl_alarm(1);
                break;
            case SIGUSR2:  // 12 : kill -s USR2
                // send an alarm in 10 seconds
                pcntl_alarm(10);
                break;
            default:
                break;
        }
    }

    /**
     * Tell the server you are going to stop consuming
     * It will finish up the last message and not send you any more
     */
    public function stop()
    {
        try {
            $this->debug("Stopping event customer by either cancel or restart command");

            if ($this->channel !== null) {
                foreach ($this->consumer_tags as $consumer_tag) {
                    $this->channel->basic_cancel($consumer_tag, false, true);
                }
            }

            $this->close();

            // Gracefully close any SQL connection opened.
            phive("SQL")->close();

        } catch (\Exception $e) {
            $this->logError("generic", $e, "EventConsumer::stop");
        }

        exit();
    }
}
