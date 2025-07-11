<?php
require_once('Queued.php');

use PhpAmqpLib\Exception\AMQPConnectionBlockedException;

class Publisher extends Queued
{
    /** const string PUBLISHER_SINGLE_RETRY_REDIS_KEY */
    const PUBLISHER_SINGLE_RETRY_REDIS_KEY = 'publisher:single:retry';
    /** const string PUBLISHER_CLUSTER */
    const PUBLISHER_CLUSTER = 'qcache';
    /**
     * Publish a single message into the queue
     *
     * @param $channel_name
     * @param $module
     * @param $method
     * @param $args
     * @param bool $persist
     * @param string|bool $override_brand
     * @param bool $add_msg_cnt True if we want to add the message count to the args, false otherwise.
     *
     * @return false|mixed
     */
    public function single($channel_name, $module, $method, $args, $persist = true, $override_brand = false, $add_msg_cnt = false)
    {
        if (!empty($override_brand)) {
            $this->config['brand'] = $override_brand;
        }

        if (empty($channel_name)) {
            return false;
        }

        try {

            $this->connect();

            list($q_name, $msg_cnt, $consumer_cnt) = $this->declare($channel_name, $persist);

            if($add_msg_cnt){
                $args[] = $msg_cnt;
            }

            $this->channel->basic_publish(
                $this->generateMessage([$module, $method, $args], $persist),
                '',
                $this->config['brand'] . '-' . $channel_name
            );

        } catch (\Exception $e) {
            $args_string = implode(",",$args);
            $data = "{$channel_name},{$module},{$method},{$args_string}";

            // We make a note in Redis
            $redis = mCluster(self::PUBLISHER_CLUSTER);
            $redis->lpush(self::PUBLISHER_SINGLE_RETRY_REDIS_KEY, $data);

            phive('Logger')->getLogger('bos_logs')->error("tournament_log", [
                'brand' => $this->config['brand'],
                'channel_name' => $channel_name,
                'module' => $module,
                'method' => $method,
                'data' => $data,
                'routing_key' => $this->config['brand'] . '-' . $channel_name,
                'error' => $e->getMessage(),
                'host' => gethostname(),
                'queued' => phive()->isQueued(),
                'cron' => phive()->isCron(),
                'forked' => phive()->isForked(),
                'backtrace' => $e->getTraceAsString()
            ]);

            return false;
        } finally {
            $this->close();
        }

        return $msg_cnt;
    }

    /**
     * To publish messages that should stick to a single server
     * p.ex: Battle of slots Queues should only have one instance or players
     *
     * @param $channel_name
     * @param $module
     * @param $method
     * @param $args
     * @param $default_server
     * @return false|mixed
     */
    public function singleNoLB($channel_name, $module, $method, $args, $default_server = "rabbit1")
    {
        $server_name = $this->proxy_config['forced_publishers'][$method] ?? $default_server;

        $this->forceConnection($server_name);
        $result = $this->single($channel_name, $module, $method, $args, true, false, false);

        $this->reloadConfig();

        return $result;
    }


    /**
     * To publish bulk messages in batches.
     *
     * @param $channel_name
     * @param $module
     * @param $method
     * @param $multi_args
     * @param bool $persist
     * @param false $override_brand
     * @return bool
     */
    public function bulk($channel_name, $module, $method, $multi_args, $persist = true, $override_brand = false)
    {
        if (!empty($override_brand)) {
            $this->config['brand'] = $override_brand;
        }

        if (empty($channel_name)) {
            return false;
        }

        $this->connect();

        $this->declare($channel_name, $persist);

        foreach (array_chunk($multi_args, 100) as $args_chunk) {
            foreach ($args_chunk as $args) {
                $this->channel->batch_basic_publish(
                    $this->generateMessage([$module, $method, $args], $persist),
                    '',
                    $this->config['brand'] . '-' . $channel_name
                );
            }

            try {
                $this->channel->publish_batch();
            } catch (AMQPConnectionBlockedException $exception) {
                do {
                    sleep(10);
                } while ($this->connection->isBlocked());
                $this->channel->publish_batch();
            }
        }

        $this->close();

        return true;
    }


    /**
     * @param $id
     * @param $base_channel_name
     * @param bool $nowait
     * @return mixed|null
     */
    public function purgeLoadBalanced($id, $base_channel_name, $nowait = true)
    {
        $instance = $this->getInstanceNum($id, $base_channel_name);
        return $this->purge($base_channel_name . $instance, $nowait);
    }

    /**
     * @param $id
     * @param $base_channel_name
     * @return int
     */
    public function getInstanceNum($id, $base_channel_name)
    {
        $channel_count = phive('Distributed')->getSetting('load_balanced_queues')[$base_channel_name] ?? 1;
        return $id % $channel_count;
    }

    /**
     * @param $id
     * @param $base_channel_name
     * @param $module
     * @param $method
     * @param $args
     * @param bool $persist
     * @param false $override_brand
     * @param false $add_msg_cnt
     * @return array
     */
    public function singleLoadBalanced(
        $id,
        $base_channel_name,
        $module,
        $method,
        $args,
        $persist = true,
        $override_brand = false,
        $add_msg_cnt = false
    ) {
        $instance = $this->getInstanceNum($id, $base_channel_name);
        return [
            $instance,
            $this->single($base_channel_name . $instance, $module, $method, $args, $persist, $override_brand, $add_msg_cnt)
        ];
    }

    /**
     * Cronjob: Reading from Redis, queue -> 'publisher:single:retry' messages and retry single() Publisher
     *
     * @return void
     */
    public function cronJobSinglePublisherRetry(): void
    {
        $redis = mCluster(self::PUBLISHER_CLUSTER);
        $queue_length = $redis->llen(self::PUBLISHER_SINGLE_RETRY_REDIS_KEY);

        // Limit the processing to a maximum of N messages to make sure cron doesn't get stuck long time
        $max_process_limit = phive('Distributed')->getSetting('retry_cron_limit');
        $process_count = min($queue_length, $max_process_limit);

        for ($i = 0; $i < $process_count; $i++) {
            // Fetch and remove the last message from the queue
            $queue_message = $redis->rpop(self::PUBLISHER_SINGLE_RETRY_REDIS_KEY);

            if ($queue_message) {
                $queue_message_items = explode(",", $queue_message);

                $channel_name = $queue_message_items[0];
                $module = $queue_message_items[1];
                $method = $queue_message_items[2];
                $slice_for_args = -1 * abs(count($queue_message_items) - 3);
                $args = array_slice($queue_message_items, $slice_for_args);

                $is_published = $this->singleNoLB($channel_name, $module, $method, $args);
                if (!$is_published) {
                    phive('Logger')->getLogger('bos_logs')->info("publisher_single_retry", [
                        "message" => "Interrupted retry queue due to failed publish."
                    ]);
                    break;
                }

                phive('Logger')->getLogger('bos_logs')->info("publisher_single_retry", [
                    'channel_name' => $channel_name,
                    'module' => $module,
                    'method' => $method,
                    'args' => $args
                ]);
            }
        }
    }
}
