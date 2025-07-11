<?php
require_once __DIR__ . '/../../vendor/autoload.php';

require_once('EventQueued.php');

class EventPublisher extends EventQueued
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Caching for the use queue flag
     *
     * @param $event
     * @param null $user
     * @return bool
     */
    public function useEventQueue($event, $user = null): bool
    {
        try {
            $use_queue = json_decode(phMget("fire-event-enabled"), true);
            if (empty($use_queue)) {
                $use_queue = phive('Config')->getByTagValues('event-queues');
                if (empty($use_queue)) {
                    return true; // Default to true if config cannot be consulted. See TA-567.
                }
                phMset("fire-event-enabled", json_encode($use_queue), 86400);
            }
            $event_supported = $this->isEventSupported($event);
            return $use_queue['enabled'] == "yes"
                &&
                !in_array($event, explode(',', $use_queue['muted-events'])) && $event_supported
                &&
                (empty($user) || in_array(phive('SQL')->getNodeByUserId(uid($user)), explode(',', $use_queue['nodes'])));
        } catch (\Exception $e) {
            phive('Logger')->getLogger('queue_messages')->info("queue_messages:useEventQueue", [$e]);
            return true; // Default to true if config cannot be consulted. See TA-567.
        }
    }


    public function single($origin, $event, $data, $delay, $u = null)
    {
        $origin = strtolower($origin);
        $event = ucfirst($event);
        $data = array_map(function ($arg) {
            return $arg === 'na' ? '' : $arg;
        }, $data); // this is for making it compatible with pexec
        $brand = phive('Distributed')->getSetting('local_brand');
        $routing_key = implode(".", [$brand, $origin, $event, 'mt']);

        try {
            if ($this->isSimulate()) {
                $this->simulate($origin, $event, $data);
                return true;
            }
            $this->connect();

            $this->declareExchanges($origin, $delay, $routing_key);
            //echo "publish on $routing_key with delay: $delay".PHP_EOL;
            $this->publish($routing_key, $data, $delay);

        } catch (\Exception $e) {

            phive('Logger')->getLogger('bos_logs')->error("event_publisher_log", [
                'brand' => $brand,
                'origin' => $origin,
                'event' => $event,
                'data' => $data,
                'routing_key' => $routing_key,
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

        return true;
    }

    public function __destruct()
    {
        $this->close();
    }
}
