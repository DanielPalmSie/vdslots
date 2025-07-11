<?php
namespace App\Classes;

use App\Models\User;
use App\Repositories\ActionRepository;
use App\Repositories\UserRepository;
use Exception;
use GuzzleHttp\Client;
use Silex\Application;


class BeBettor
{
    /** @var Client $client */
    protected $client;

    /** @var UserRepository $actor */
    protected $actor;

    /** @var Application $app */
    public $app;

    /** @var int $retries */
    private $retries;

    /** @var float $initial_wait */
    private $initial_wait;

    /**
     * BeBettor constructor.
     * @param Application $app
     * @param float $timeout
     */
    public function __construct(Application $app, $actor = null, float $timeout = 2.0)
    {
        $this->client = new Client([
            'base_uri' => $app['bebettor']['base.uri'],
            'timeout' => $timeout,
        ]);
        $this->app = $app;
        $this->retries = 0;
        $this->initial_wait = 0.2;

        if ($actor) {
            $this->actor = $actor;
        } else {
            $this->actor = UserRepository::getCurrentUser();
        }

    }

    /**
     * Perform a request to beBettor API by sending the method and the type
     * There are two types that we can get data for 'AFFORDABILITY' and 'VULNERABILITY'
     *
     * @param array $query
     * @param String $uri
     * @param User $customer
     * @param string $method
     * @param string $type
     * @return mixed
     */
    protected function processRequest(array $query, String $uri, User $customer, $method = 'GET', $type = 'AFFORDABILITY')
    {
        $map_name = [
            'GET' => 'query',
            'POST' => 'json',
        ];
        $start_time = microtime(true);
        try {
            $res = $this->client->request($method, $uri, [
                @$map_name[$method] => $query,
                'headers' => [
                    'X-API-KEY' => $this->app['bebettor']['X-API-KEY'],
                    'X-BB-Check-Type' => $type,
                    'Content-Type' => 'application/json'
                ]
            ]);
        } catch (\Exception $e) {
            $result = $this->processException($e, $query, $customer, $start_time, $type);

            if(!$result) {
                return json_decode($e->getResponse()->getBody(true), true);
            }
        }
        $response_body = json_decode($res->getBody(), true);

        phive()->externalAuditTbl("bebettor_".strtolower($type)."_check", $query, $response_body, (microtime(true) - $start_time), 200, 0, $response_body['requestId'], $this->actor->id);

        return $response_body;
    }

    /**
     * Handle the exception if there was a problem
     * NOTICE : in case of a 429 error we have to retry because it could be we just exceeded
     * the limit of tries for this moment(5 tries a second) and we add a bit of time in between the retries
     *
     * @param \Exception $e
     * @param array $query
     * @param User $customer
     * @param float $start_time
     * @param string $type
     * @throws \Exception
     */
    private function processException(\Exception $e, array $query, User $customer, float $start_time, $type)
    {
        switch ($e->getCode()) {
            case 429:
                return $this->retryRequest($customer, $type);
            default:
                phive()->externalAuditTbl("bebettor_".strtolower($type)."_check", $query, $e->getMessage(), (microtime(true) - $start_time), $e->getCode(), 0, 0, $this->actor->id);
                break;
        }
        return false;
    }

    /**
     * Retry the request to beBettor, we have a max tries of 3 and we have some sleep in
     * between calls
     *
     * @param User $customer
     * @param string $type
     * @param int $max_tries
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function retryRequest(User $customer, $type, int $max_tries = 3)
    {
        if ($this->retries < $max_tries) {
            sleep($this->initial_wait);
            $this->initial_wait *= 1.5;
            $this->retries += 1;
            return $this->performScreeningCheck($customer, $type);
        }
        return false;
    }

    /**
     * Perform the Affordability check by collecting the data we need
     * and making the call to the api
     *
     * @param User $customer
     * @param string $type
     * @return mixed
     */
    public function performScreeningCheck(User $customer, string $type)
    {
        $request_query = [
            "customerId" => $customer['id'],
            "firstName" => $customer['firstname'],
            "lastName" => $customer['lastname'],
            "gender" => strtoupper($customer['sex']),
            "dateOfBirth" => $customer['dob'],
            "address" => [
                "addressLine1" => $customer['address'],
                "town" => $customer['city'],
                "postcode" => $customer['zipcode']
            ]
        ];

        $response = $this->processRequest($request_query, $this->app['bebettor']['affordability'], $customer, 'POST', $type);
        phive('UserHandler')->logAction($customer['id'], "{$this->actor->username} performed a {$type} check on {$customer['username']}", strtolower($type).'_check', false, $this->actor->id);

        return $response;
    }
}
