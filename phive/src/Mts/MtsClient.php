<?php

declare(strict_types=1);

namespace Videoslots\Mts;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * @deprecated Use \Videoslots\MtsSdkPhp\MtsClient instead
 */
class MtsClient
{
    private Client $client;

    public function __construct(array $settings, LoggerInterface $logger)
    {
        $key = $settings['key'];
        $consumerCustomId = $settings['consumer_custom_id'];
        $base = str_replace('/api/0.1', '/api/1.0', $settings['base_url']);

        $stack = HandlerStack::create(new CurlHandler());
        $stack->push(Middleware::mapRequest(function (RequestInterface $request) use ($key, $consumerCustomId) {
            return $request->withHeader('X-API-KEY', $key)
                ->withHeader('X-CONSUMER-CUSTOM-ID', $consumerCustomId);
        }));
        $stack->push(Middleware::log(
            $logger,
            new MessageFormatter(
                \GuzzleHttp\MessageFormatter::SHORT,
                \GuzzleHttp\MessageFormatter::DEBUG,
                ...[422, 424]
            ),
            LogLevel::WARNING
        ));

        $this->client = new Client([
            'base_uri' => $base,
            'handler' => $stack,
            'verify' => false,
        ]);
    }

    /**
     * @throws \JsonException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function deposit(string $supplier, array $payload): array
    {
        return $this->post('deposit/' . $supplier, $payload, [
            'Device-Type' => phive()->deviceType(),
        ]);
    }

    public function quickDeposit(string $supplier, array $payload): array
    {
        return $this->post('quick-deposit/' . $supplier, $payload, [
            'Device-Type' => phive()->deviceType(),
        ]);
    }

    /**
     * @throws \JsonException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function withdraw(string $supplier, array $payload, array $headers = []): array
    {
        return $this->post('withdraw/' . $supplier, $payload, $headers);
    }

    /**
     * @throws \JsonException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function withdrawCancel(string $id, array $payload): array
    {
        return $this->post('withdraw/' . $id . '/cancel', $payload);
    }

    public function prepaidDepositsDetail(array $payload): array
    {
        return $this->get('deposit/prepaid-detail', $payload);
    }

    public function selectAccount(string $supplier, array $payload): array
    {
        return $this->post("accounts/{$supplier}/async", $payload, [
            'Device-Type' => phive()->deviceType(),
        ]);
    }

    /**
     * @throws \JsonException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function post(string $url, array $params, array $headers = []): array
    {
        $res = $this->client->request('POST', $url, [
            'json' => $params,
            'headers' => array_merge($headers, [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]),
            'http_errors' => true,
        ]);

        return json_decode($res->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    private function get(string $url, array $params): array
    {
        $res = $this->client->request('GET', $url, [
            'query' => $params,
            'headers' => [
                'Accept' => 'application/json',
            ],
            'http_errors' => true,
        ]);

        return json_decode($res->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }

    public function getTransactionDetails(array $payload): array
    {
        return $this->post('transactions/details', $payload);
    }
}
