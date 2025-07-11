<?php

namespace App\Classes;

use App\Extensions\Database\ReplicaFManager as ReplicaDB;
use App\Helpers\DataFormatHelper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Database\Eloquent\Collection;
use Silex\Application;

class Mts
{
    /** @var Client $client */
    protected $client;

    /** @var  Application $app */
    public $app;

    /** @var  array $cache_storage */
    public $cache_storage;

    private $apiKey;

    /**
     * Mts constructor.
     * @param Application $app
     * @param float $timeout
     */
    public function __construct(Application $app, $timeout = 2.0)
    {
        $this->client = new Client([
            'base_uri' => $app['mts.config']['base.uri'],
            'timeout' => $timeout,
        ]);

        $this->apiKey = getenv('MTS_API_KEY');
        $this->app = $app;
    }

    protected function processRequest(
        array $query,
        string $uri,
        string $method = 'GET',
        bool $fail = false,
        array $headers = []
    )
    {
        $map_name = [
            'GET' => 'query',
            'DELETE' => 'query',
            'POST' => 'form_params',
            'PUT' => 'json'
        ];

        try {
            $res = $this->client->request($method, $uri, [
                @$map_name[$method] => $query,
                'headers' => array_merge([
                    'X-API-KEY' => $this->apiKey
                ], $headers)
            ]);

            // TODO: move to client logging middleware together with error logging
            $this->app['monolog']->addInfo('backoffice to MTS call', [
                'base_url' => $this->app['mts.config']['base.uri'],
                'method' => $method,
                'call' => $uri,
                'code' => $res->getStatusCode(),
                'api_key' => $this->obfuscateValue($this->apiKey),
                'params' => $this->obfuscateArray($query),
                'response' => json_decode($res->getBody(), true),
            ]);

        } catch (ClientException $e) {
            return $this->processException($e, $fail);
        }
        return json_decode($res->getBody(), true);
    }

    /**
     * On mts => action: query, class: supplier, method: list, args: {type: deposit}
     * @param string $method
     * @param array $args
     * @param string $class
     * @return mixed
     * @throws \Exception
     */
    public function doRpcQuery($method, $args, $class = 'stats')
    {

        $query = [
            'action' => 'query',
            'class' => $class,
            'method' => $method,
            'args' => $args,
        ];

        try {
            $res = $this->client->post('api/0.1/rpc/execute', [
                \GuzzleHttp\RequestOptions::JSON => $query,
                'headers' => [
                    'X-API-KEY' => $this->apiKey,
                ]
            ]);

            $this->app['monolog']->addInfo('backoffice rpc to MTS call', [
                'base_url' => $this->app['mts.config']['base.uri'],
                'method' => $method,
                'call' => $class . '::' . $method,
                'code' => $res->getStatusCode(),
                'api_key' => $this->obfuscateValue($this->apiKey),
                'params' => $this->obfuscateArray($query),
                'response' => json_decode($res->getBody(), true),
            ]);

        } catch (ClientException $e) {
            return $this->processException($e, false);
        }
        return json_decode($res->getBody(), true);
    }

    private function processException(ClientException $e, bool $fail)
    {
        if ($fail) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }
        if ($this->app['debug']) {
            return $this->app->abort('408', $e->getMessage());
        } else {
            $this->app['monolog']->addError('backoffice to MTS error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]);

            $errorMessage = 'MTS Service Error';

            // Only display validation errors from MTS
            if ($e->getCode() === 422) {
                $errorMessage .= ': '. $e->getResponse()->getBody()->getContents();
            }

            return $this->app->abort('408', $errorMessage);
        }
    }


    /**
     * TODO this function has to be refactored and merge with getFailedDeposits
     *
     * @param $user_id
     * @param $start_date
     * @param $end_date
     * @return mixed
     * @throws \Exception
     */
    public function getFailedDepositsLite($user_id, $start_date, $end_date)
    {
        $response = $this->doRpcQuery('getFailedDeposits', compact('user_id', 'start_date', 'end_date'));
        return array_key_exists('errors', $response) ? [] : $response;
    }

    /**
     * @param int|string $user_id
     * @param string $start_date
     * @param string $end_date
     * @param int $limit
     * @param int $offset
     * @param array $attributes
     * @param boolean $count_only
     *
     * @param int $page
     * @param int $length
     * @param bool $paginate
     *
     * @return array|bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getFailedDeposits($user_id, $start_date, $end_date, $limit = 1000, $offset = null, $attributes = null, $count_only = false, $paginate = false, $page = 0, $length = 10)
    {
        $query = [
            'date_from' => $start_date,
            'date_to' => $end_date,
            'limit' => $limit,
            'user_id' => $user_id,
            'count_only' => $count_only
        ];

        if ($paginate) {
            $query['paginate'] = $paginate;
            $query['page'] = $page + 1;
            $query['length'] = $length;
        }

        if (!empty($offset)) {
            $query['from'] = $offset;
        }

        if (!empty($attributes)) {
            $query['order_column'] = $attributes['column'];
            $query['order_direction'] = $attributes['dir'];
        }

        $res = $this->processRequest($query, 'api/1.0/deposits/failed');

        if ($count_only) {
            return $res;
        }

        if (!empty($attributes['user_info'])) {
            $tx_list = [];
            foreach ($res['result'] as $elem) {
                $user_info = ReplicaDB::shSelect($elem['user_id'], 'users', "SELECT u.id, u.username, u.country, us.value AS verified FROM users u
                                            LEFT JOIN users_settings us ON u.id = us.user_id AND us.setting = 'verified'
                                            WHERE u.id = :user_id", ['user_id' => $elem['user_id']])[0];
                $tx_list[] = array_merge($elem, [
                    'username' => $user_info->username,
                    'country' => $user_info->country,
                    'verified' => $user_info->verified == 1 ? 'Yes' : 'No',
                ]);
            }
        } else {
            $tx_list = $res['result'];
        }

        return [
            "draw" => intval($attributes['draw']),
            "recordsTotal" => intval($res['count']),
            "recordsFiltered" => intval($res['count']),
            "data" => $tx_list
        ];
    }

    private function getTransactionsDetails(array $transactionIds): array
    {
        $transactionIds = array_filter($transactionIds, function ($id) {
            return $id !== 0;
        });

        if ($transactionIds) {
            $query['ids'] = $transactionIds;

            return $this->processRequest($query, 'api/1.0/transactions/details', 'POST');
        }

        return [];
    }

    public function addTransactionDetails(object $collection, string $transactionIdKey = 'mts_id'): object
    {
        $mtsIds = $collection->pluck($transactionIdKey)->unique()->all();
        $transactionsDetails = $this->getTransactionsDetails($mtsIds);

        foreach ($collection as $item) {
            $transactionId = $item->{$transactionIdKey};

            if (!$transactionId) {
                continue;
            }

            $transactionsDetail = $transactionsDetails['data'][$transactionId] ?? null;

            if ($transactionsDetail && $transactionsDetail['credit_card']) {
                $transactionsDetail['credit_card']['details'] = ' | ' . DataFormatHelper::getCardDetails(
                        $transactionsDetail['credit_card'],
                        $transactionsDetail['sub_supplier']
                    );
            }

            $item->transaction_details = $transactionsDetail;
        }

        return $collection;
    }

    /**
     * @param $user_id
     * @param array $query_params If null it will retrieve all the cards
     * @return mixed
     * @throws
     */
    public function getCardsList($user_id, array $query_params = [])
    {
        if (empty($query_params)) {
            $query_params = [
                'user_id' => $user_id,
                'can_withdraw' => 1,
                'card_id' => 0,
                'suppliers' => 'wirecard,adyen,worldpay,credorax',
                //'verified' => 1, for now this is not in use as this method is only used on the manual withdrawal creation
                //'active' => 1
            ];
        }

        return $this->processRequest($query_params, 'api/0.1/user/cards/list');
    }

    /**
     * @param $user_id
     *
     * @return mixed
     * @throws
     */
    public function dataManagementDownload($user_id) {
        return $this->processRequest([], "api/0.1/data-management/{$user_id}",'GET', true);
    }

    /**
     * @param $user_id
     * @return mixed
     * @throws
     */
    public function dataManagementRemove($user_id) {
        return $this->processRequest([], "api/0.1/data-management/{$user_id}", 'DELETE');
    }

    /**
     * @param $user_id
     * @param $number
     * @return mixed|void
     * @throws \Exception
     */
    public function updateMuchBetterNumber($user_id, $number)
    {
        $data = [
            'supplier' => 'muchbetter',
            'user_id' => $user_id,
            'ext_id' => $number,
        ];

        return $this->processRequest($data, 'api/0.1/update-ext-id', 'PUT');
    }

    /**
     * Get the account information's for the user
     *
     * @param $user_id
     * @param string $supplier
     *
     * @return mixed
     * @throws \Exception
     */
    public function getUserAccounts($user_id)
    {
        return $this->doRpcQuery('getUserAccounts', compact('user_id'));
    }

    public function getBlacklistedBinById(int $id): array
    {
        return $this->processRequest([], "api/0.1/bin-blacklist/{$id}");
    }

    public function getBlacklistedBins(array $params = []): array
    {
        return $this->processRequest($params, 'api/0.1/bin-blacklist');
    }

    public function createBlacklistedBin(array $params): array
    {
        return $this->processRequest(
            $params,
            "api/0.1/bin-blacklist",
            'POST',
            false,
            ['Accept' => 'application/json']
        );
    }

    public function updateBlacklistedBin(int $id, array $params): bool
    {
        return $this->processRequest(
            $params,
            "api/0.1/bin-blacklist/{$id}",
            'PUT',
            false,
            ['Accept' => 'application/json']
        );
    }

    public function approveAccount(int $id): array
    {
        return $this->processRequest(
            [],
            "api/1.0/account/{$id}/approve",
            "POST",
            false,
            ['Accept' => 'application/json']
        );
    }

    public function registerAccount(string $supplier, array $data): array
    {
        return $this->processRequest(
            $data,
            "api/1.0/account/register/{$supplier}",
            "POST",
            false,
            ['Accept' => 'application/json']
        );
    }

    private function obfuscateArray(array $array = null): array
    {
        if (!$array) {
            return $array;
        }

        $obfuscated = $array;

        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $obfuscated[$k] = $this->obfuscateArray($v);
            } elseif (is_scalar($v)) {
                $obfuscated[$k] = $this->obfuscateValue($v);
            }
        }

        return $obfuscated;
    }

    private function obfuscateValue($s)
    {
        if (!is_scalar($s) || is_bool($s)) {
            return $s;
        }

        $s = (string)$s;
        if (($len = strlen($s)) < 6) {
            return substr($s, 0, 2) . str_repeat('*', max(0, $len - 2));
        }
        return substr($s, 0, 2) . str_repeat('*', $len - 4) . substr($s,-2);
    }
}
