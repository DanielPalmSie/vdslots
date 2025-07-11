<?php
require_once('Queued.php');

use PhpAmqpLib\Message\AMQPMessage;

class Consumer extends Queued
{
    private array $consumer_tags;

    /**
     * Consumer constructor.
     */
    public function __construct()
    {
        parent::__construct();

        if (extension_loaded('pcntl')) {
            define('AMQP_WITHOUT_SIGNALS', false);

            pcntl_signal(SIGTERM, [$this, 'signalHandler']);
            pcntl_signal(SIGHUP, [$this, 'signalHandler']);
            pcntl_signal(SIGINT, [$this, 'signalHandler']);
            pcntl_signal(SIGQUIT, [$this, 'signalHandler']);
            pcntl_signal(SIGUSR1, [$this, 'signalHandler']);
            pcntl_signal(SIGUSR2, [$this, 'signalHandler']);
            pcntl_signal(SIGALRM, [$this, 'alarmHandler']);
        } else {
            echo 'Unable to process signals.' . PHP_EOL;
            exit(1);
        }
    }

    /**
     * Start the consumer
     *
     * @param array $queue_names
     * @param bool $persist
     * @throws ErrorException
     */
    public function start(array $queue_names, $persist = true)
    {
        $this->connect(3.0, 3.0, true, 30);

        foreach ($queue_names as $queue_name) {
            $this->declare($queue_name,  $persist);

            $this->channel->basic_qos(0,1,null);

            $callback = function(AMQPMessage $msg) {
                $array = json_decode($msg->body, true);
                phive()->apply($array[0], $array[1], $array[2]);
                $msg->get('channel')->basic_ack($msg->get('delivery_tag'));
            };

            $this->consumer_tags[] = $this->channel->basic_consume($this->config['brand'] . '-' . $queue_name, '', false, false, false, false, $callback);
        }

        while($this->channel->is_consuming()) {
            try {
                $this->channel->wait();
            } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
                try {
                    $this->connection->checkHeartBeat();
                    $this->debug("Wait timed out doing heart beat.");
                } catch (Exception $e) {
                    $this->logError(implode(', ', $queue_names), $e);
                    $this->close();
                }
                continue;
            } catch (Exception $e) {
                $this->logError(implode(', ', $queue_names), $e);
                $this->close();
            }

        }
    }

    /**
     * Signal handler
     *
     * @param  int $signal_number
     * @return void
     */
    public function signalHandler($signal_number)
    {
        $this->debug('Handling signal: #' . $signal_number);
        global $consumer;

        switch ($signal_number) {
            case SIGTERM:  // 15 : supervisor default stop
            case SIGQUIT:  // 3  : kill -s QUIT
            case SIGINT:   // 2  : ctrl+c
            case SIGHUP:   // 1  : kill -s HUP Restart will kill as well as Supervisord will handle it
                $consumer->stop();
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
            $this->debug("Stopping customer by either cancel or restart command");

            if ($this->channel !== null) {
                foreach ($this->consumer_tags as $consumer_tag) {
                    $this->channel->basic_cancel($consumer_tag, false, true);
                }
            }
            $this->close();

            // Gracefully close any SQL connection opened.
            phive("SQL")->close();
        } catch (\Exception $e) {
            $this->logError("generic", $e);
        }
        exit();
    }

}
