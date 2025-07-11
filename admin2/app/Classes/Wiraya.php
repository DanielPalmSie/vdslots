<?php
/**
 * Created by PhpStorm.
 * User: iondum
 * Date: 12/06/18
 * Time: 11:36
 */

namespace App\Classes;

use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Silex\Application;

class Wiraya
{
    /** @var Application $app */
    private $app;

    /** @var Client $client */
    private $client;

    /** @var bool $authenticated */
    private $authenticated = false;

    /** @var string $token */
    private $token;

    public function __construct($app, $timeout = 2.0)
    {
        $this->app = $app;
        $this->client = new Client([
            'base_uri' => $app['wiraya']['url'],
            'timeout' => $timeout,
        ]);
    }

    /**
     * @param bool $enable_exception
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException|\Exception
     */
    public function auth($enable_exception = true)
    {
        $res = $this->processRequest('/auth/token/apikey', 'POST', [
            'key' => getenv('WIRAYA_API_KEY')
        ]);

        if (!$res['authenticated'] && $enable_exception) {
            throw new \Exception('Wiraya authentication failed.');
        }

        $this->authenticated = $res['authenticated'];
        $this->token = $res['token'];

        return (bool)$this->authenticated;
    }

    /**
     * @param $uri
     * @param $query
     * @param array $headers
     * @param string $method
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function processRequest($uri, $method, $query, $headers = [])
    {
        $map_name = [
            'DELETE' => 'query',
            'POST' => 'json',
            'PUT' => 'json',
            'GET' => 'query',
        ];
        $headers_map = [
            'POST' => [
                'Content-type' => 'application/json'
            ],
            'PUT' => [
                'Content-type' => 'application/json'
            ],
            'DELETE' => [],
            'GET' => []
        ];

        if (getenv('WIRAYA_TEST')) {
            $this->app['monolog']->addError('data', [$uri, $method, $query, $headers]);
        }

        try {
            $res = $this->client->request($method, $uri, [
                @$map_name[$method] => $query,
                'headers' => array_merge($headers_map[$method], $headers)
            ]);
        } catch (\Exception $e) {
            if ($this->app['debug']) {
                return $this->app->abort('408', $e->getMessage());
            } else {
                $this->app['monolog']->addError($e->getMessage());
                return $this->app->abort('408', "Wiraya service error");
            }
        }
        return json_decode($res->getBody(), true);
    }

    /**
     * @param $users
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function registerUsers($users)
    {
        $res = $this->processRequest('/api/contact/_bulk', 'PUT', $users, [
            'Authorization' => "Bearer {$this->token}"
        ]);

        return is_array($res) && array_has($res, ['id']);
    }

    /**
     * @param Collection $users
     * @param $campaign
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setUsersOnCampaign($users, $campaign)
    {
        $users_campaign = $users
            ->mapWithKeys(function ($user_id) use ($campaign) {
                return [
                    $user_id => [
                        "campaign" => $campaign
                    ]
                ];
            })
            ->all();

        $res = $this->processRequest('/api/contact/campaigns/_bulk', 'POST', $users_campaign, [
            'Authorization' => "Bearer {$this->token}"
        ]);

        return is_array($res) && array_has($res, ['id']);
    }
}