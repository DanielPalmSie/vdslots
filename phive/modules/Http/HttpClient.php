<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\TransferStats;

/**
 * Guzzle Client for HTTP Request.
 */
class HttpClient extends PhModule
{
    /**
     * @var GuzzleCLient
     */
    private GuzzleClient $client;


    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     *
     */
    public function __construct()
    {
        $this->client = new GuzzleClient(['defaults' => ['exceptions' => false]]);
        $this->logger = phive('Logger')->getLogger('game_providers');
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $params
     * @param array $headers
     * @param array $options
     * @param bool $debug
     * @param bool $log
     * @param float $version
     * @return PromiseInterface
     */
    public function requestAsync(
        string $method,
        string $url,
        array $params = [],
        array $headers = [],
        array $options = [],
        bool $debug = false,
        bool $log = true,
        float $version = 1.1
    ): PromiseInterface {
        $options = $this->buildOptions($params, $headers, $options, $debug, $log, $version);
        return $this->client->requestAsync($method, $url, $options);
    }

    /**
     * @param array $params
     * @param array $headers
     * @param array $options
     * @param bool $debug
     * @param bool $log
     * @param float $version
     * @return array
     */
    private function buildOptions(
        array $params = [],
        array $headers = [],
        array $options = [],
        bool $debug = false,
        bool $log = true,
        float $version = 1.1
    ): array {
        $options['timeout'] = $options['timeout'] ?: 3;
        $options['connect_timeout'] = $options['connect_timeout'] ?: 3;
        $options['debug'] = $debug;
        $options['version'] = $version;
        if ($log) {
            $options['on_stats'] = function (TransferStats $stats) use ($params, $headers) {
                $report['url'] = (string)$stats->getEffectiveUri();
                $report['transfer_time'] = $stats->getTransferTime();
                $report['request'] = $params;
                $report['headers'] = $headers;

                if ($stats->hasResponse()) {
                    $report['status_code'] = $stats->getResponse()->getStatusCode();
                    $report['headers'] = $stats->getResponse()->getHeaders();
                    $report['protocol'] = $stats->getResponse()->getProtocolVersion();
                    $this->logger->debug("Http Async log.", [$report]);
                } else {
                    $report['error_data'] = $stats->getHandlerErrorData();
                    $report['status_code'] = "error";
                    $this->logger->debug("Http Async Error log.", [$report]);
                }
            };
        }
        return array_merge($options, ['headers' => $headers, 'body' => json_encode($params)]);
    }


}
