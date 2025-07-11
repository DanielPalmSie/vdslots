<?php

namespace App\Classes;

use App\Extensions\Database\FManager as DB;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Collection;
use Silex\Application;

class Dmapi
{
    /** @var Client $client */
    protected $client;

    /** @var  Application $app */
    public $app;

    /** @var  array $cache_storage */
    public $cache_storage;

    /**
     * Mts constructor.
     * @param Application $app
     * @param float $timeout
     */
    public function __construct(Application $app, $timeout = 2.0)
    {
        $this->client = new Client([
            'base_uri' => $app['dmapi.config']['base.uri'],
            'timeout' => $timeout,
        ]);
        $this->app = $app;
    }

    /**
     * @param $query
     * @param $uri
     * @param string $method
     * @param bool $fail
     * @return mixed
     * @throws
     */
    protected function processRequest($query, $uri, $method = 'GET', $fail = false)
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
                'headers' => [
                    'X-API-KEY' => getenv('DMAPI_API_KEY')
                ]
            ]);
        } catch (\Exception $e) {
            if ($fail) {
                throw new \Exception($e->getMessage(), $e->getCode());
            }
            $this->app['monolog']->addError("Dmapi error {$e->getMessage()}");
            return $this->app->abort('408', "DMAPI service error");
        }
        return json_decode($res->getBody(), true);
    }

    public function test()
    {
        $res = $this->processRequest(['param1' => 'thisisparam1'], 'api/v1/stats/test');

        dd($res);
    }

    public function getDocumentsStats(array $params, $limit, $skip, $order_column, $order_direction, $extra)
    {
        $params['limit'] = $limit;
        $params['skip'] = $skip;
        $params['order-col'] = $order_column;
        $params['order-dir'] = $order_direction;

        $res = $this->processRequest($params, 'api/v1/stats/management-history-report');

        if (isset($res['error'])) {
            $this->app['monolog']->addError("Dmapi error {$res['error']}");
        }

        $result = [];
        foreach ($res['result'] as $elem) {
            $elem['tag'] = Dmapi::getDocumentType($elem['tag']);
            $elem['status'] = $this->getDocumentStatusFromMap($elem['status']);
            $elem['executed_by'] =  DB::shTable($elem['executed_by'], 'users')->where('id', $elem['executed_by'])->first()->username;
            $result[] = $elem;
        }

        $agents = [];
        if (!empty($res['agents'])) {
            foreach (DB::table('users')->selectRaw("id, username, CONCAT(firstname,' ',lastname)as fullname, firstname")->whereIn('id', $res['agents'])->get() as $agent) {
                $agents[$agent->id] = empty($agent->firstname) ? $agent->username : $agent->fullname . "  [{$agent->username}]";
            }
        }

        return [
            "draw" => intval($extra['draw']),
            "recordsTotal" => intval($res['count']),
            "recordsFiltered" => intval($res['count']),
            "data" => $result,
            "agents" => $agents
        ];
    }

    public static function getDocumentType($type = null)
    {
        $map = [
            'addresspic' => 'Proof of Address',
            'bankpic' => 'Bank',
            'citadelpic' => 'Citadel',
            'creditcardpic' => 'Credit Card',
            'ecopayzpic' => 'Ecopayz',
            'idcard-pic' => 'Proof of Identity',
            'instadebitpic' => 'Instadebit',
            'skrillpic' => 'Skrill',
            'netellerpic' => 'Neteller',
            'trustlypic' => 'Trustly',
            'cubitspic' => 'Cubits',
            'sourceoffundspic' => 'Source of Wealth Declaration',
            'bankaccountpic' => 'Bank Account',
            'internaldocumentpic' => 'Internal Document',
            'proofofwealthpic' => 'Proof of Wealth',
            'proofofsourceoffundspic' => 'Proof of Source of Funds',
        ];

        if (is_null($type)) {
            return $map;
        } else {
            return isset($map[$type]) ? $map[$type] : $type;
        }
    }

    public function getDocumentStatusFromMap($status, $type = 'doc')
    {
        $map = [
            'doc' => [
                0 => 'Requested',
                1 => 'Processing',
                2 => 'Approved',
                3 => 'Rejected',
                4 => 'CPNN',
                5 => 'Deactivated'
            ],
            'file' => [
                1 => 'Processing',
                2 => 'Approved',
                3 => 'Rejected',
                4 => 'CPNN'
            ]
        ];

        return isset($map[$type][$status]) ? $map[$type][$status] : 'na';
    }

    /**
     * @param $user_id
     *
     * @return mixed
     * @throws
     */
    public function dataManagementDownload($user_id) {
        return $this->processRequest([], "api/v1/data-management/{$user_id}", 'GET', true);
    }

    /**
     * @param $user_id
     *
     * @return mixed
     * @throws
     */
    public function dataManagementRemove($user_id) {
        return $this->processRequest([], "api/v1/data-management/{$user_id}", 'DELETE');
    }

    /**
     * @param $file
     * @param $attributes
     *
     * @return mixed
     * @throws
     */
    public function uploadDocument($file, $attributes) {
        $data = [
            'data' => [
                'file'=> [
                    'attributes' => [
                        'original_name' => $file['name'],
                        'mime_type' => $file['type'],
                        'encoded_data' => base64_encode(file_get_contents($file['tmp_name'])),
                    ]
                ],
                'attributes' => $attributes
            ]
        ];

        return $this->processRequest($data, "api/v1/certificates/upload", 'POST');
    }


}
