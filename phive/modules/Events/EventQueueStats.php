<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api/PhModule.php';

class EventQueueStats extends PhModule
{
    /**
     * Debug mode flag
     *
     * @var bool
     */
    protected $debug_mode = false;

    /**
     * Queue / events configuration
     *
     * @var array
     */
    private $configs;
    private $queue_settings;
    private $queue_stats_url;

    /**
     *
     *  Constructor.
     */
    public function __construct()
    {
        $this->queue_settings = phive('Distributed')->getSetting('queue-consumers');
        $this->debug_mode = phive('Distributed')->getSetting('queue_debug');

    }

    /**
     * Using the rabbitmq connection credentials, polls its JSON API to get the no of messages in each events queue.
     *
     */
    public function logQueueStats()
    {
        $queue_stats_data = $this->buildQueueStatsData();

        foreach ($queue_stats_data as $queue_data) {
            $queue_stats_url = $queue_data['url'];
            $queue_stats_host = $queue_data['host'];

            // get list of all queues from rabbitmq api
            $all_queues_json = $this->getAPIStatsJson($queue_stats_url);
            $log_message_total = '';

            if (empty($all_queues_json)) {
                phive('Logger')->getLogger('queue_messages')->debug("queue_messages", ["event queues not found on host $queue_stats_host"]);
                continue;
            }

            foreach ($all_queues_json as $queue) {
                $queue_name = $queue["name"];

                // poll stats for individual queue by vhost '/' (%2f) and /queue name
                $json_array = $this->getAPIStatsJson($queue_stats_url . "/%2f/" . $queue_name);
                $messages_count = $json_array["messages"] ?? 0;

                if($messages_count > 0) {
                    $log_message = "Found queue $queue_name with $messages_count messages on host $queue_stats_host" . PHP_EOL;
                    $log_message_total .= $log_message;
                }

            }

            if(!empty($log_message_total)) {
                phive('Logger')->getLogger('queue_messages')->debug("queue_messages", [$log_message_total]);
            }

        }
    }

    /**
     * Polls the API with the given rabbitmq api endpoint and parses response into JSON
     *
     * Example inputs:
     *
     *   1)  Get stats for a queue: http://root:1234@10.5.30.129:15672/api/queues/%2f/notification-events"
     *   2)  Get stats for all queues: http://root:1234@10.5.30.129:15672/api/queues"
     *
     *  @return array|mixed|string[]
     */
    private function getAPIStatsJson($url)
    {
        $json_string = file_get_contents($url);
        if (!empty($json_string)) {
            return json_decode($json_string, true); // need an associative array
        }
        return [];
    }

    /**
     * Returns URLs and Hosts for the rabbitmq api endpoints depending on whether queue proxy is enabled or not.
     *
     * @return array $queue_stats_data
     */
    private function buildQueueStatsData()
    {
        $distributed = phive('Distributed');
        $queue_proxy_settings = $distributed->getSetting('queue_proxy');
        $proxy_config = $queue_proxy_settings['nodes'];
        $queue_proxy_enabled = $queue_proxy_settings['enabled'];
        $queue_stats_data = [];

        $server_config = $distributed->getSetting('queue_server');
        $server_auth = $server_config['user'] . ":" . $server_config['pwd'];
        $server_host = $server_config['host'];

        // if proxy is not enabled, we get logs from the queue server
        if (!$queue_proxy_enabled) {
            $queue_stats_data[] = [
                'url' => "http://$server_auth@$server_host:15672/api/queues",
                'host' => $server_host,
            ];
        } else {
            foreach ($proxy_config as $config) {
                $proxy_auth = $config['user'] . ":" . $config['pwd'];
                $proxy_host = $config['host'];
                $queue_stats_data[] = [
                    'url' => "http://$proxy_auth@$proxy_host:15672/api/queues",
                    'host' => $proxy_host,
                ];

            }
        }

        return $queue_stats_data;
    }

    /**
     * Returns a list of the supported event queue names from config only.
     *
     * @return array|mixed|string[]
     */
    protected function getAllEventQueueNames()
    {
        $event_queue_names = [];
        foreach ($this->queue_settings as $consumer) {
            if (empty($consumer['topics'])){
                continue;
            }
            $queue_name = $consumer['queue_name'];
            if (!empty($queue_name) && !in_array($queue_name, $event_queue_names)) {
                $event_queue_names[] = $queue_name;
            }
        }
        return $event_queue_names;
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
}
