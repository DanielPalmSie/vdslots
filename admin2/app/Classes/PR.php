<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 9/15/16
 * Time: 5:46 PM
 */

namespace App\Classes;

use GuzzleHttp\Client;
use Silex\Application;

class PR
{
    /** @var Client $client */
    protected $client;
    
    /** @var  Application $app */
    protected $app;

    /** @var  array $auth_data*/
    protected $auth_data;

    /**
     * Mts constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->auth_data['pwd'] = getenv('PARTNERROOM_RPC_KEY');
        if ($app['pr.config']['basic.auth']) {
            $b_auth = explode('|', getenv('ENVIRONMENT_BASIC_AUTH'));
            $this->auth_data['basic'] = [$b_auth[0] => $b_auth[1]];
        }

        $this->client = new Client([
            'base_uri' => $app['pr.config']['base.uri'],
            'timeout'  => 2.0,
        ]);
        $this->app = $app;
    }

    /**
     * @param $query
     * @param $uri
     * @param string $method
     * @return mixed
     */
    protected function processRequest($query, $uri = 'phive/modules/Site/json/exec.php', $method = 'PUT')
    {
        $map_name = [
            'GET' => 'query',
            'POST' => 'form_params',
            'PUT' => 'json'
        ];
        $query['pwd'] = $this->auth_data['pwd'];
        $request_params = [@$map_name[$method] => $query];

        if ($this->app['pr.config']['basic.auth']) {
            $request_params['auth'] = $this->auth_data['basic'];
        }

        try {
            $res = $this->client->request('PUT', $uri, $request_params);
        } catch (\Exception $e) {
            return $this->app->abort('408', "Partnerroom PRC service error");
        }
        return json_decode($res->getBody(), true);
    }


    public function execCommand($class, $method, $params)
    {
        return $this->processRequest(compact('class', 'method', 'params'));
    }

    public function execFetch($raw_mysql_query)
    {
        $res = $this->processRequest([
            'class' => 'SQL',
            'method' => 'loadArray',
            'params' => [$raw_mysql_query]
        ]);
        if (is_null($res)) {
            $this->app['monolog']->addError("Possible Partnerroom PRC service error executing a database fetch.");
        }
        return $res;
    }
}