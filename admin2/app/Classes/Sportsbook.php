<?php

namespace App\Classes;

use GuzzleHttp\Client;
use Silex\Application;

abstract class Sportsbook
{
    public Application $app;
    public Client $client;
    public string $sportsbookBaseUri;


    public function __construct(Application $app, string $sportsbookBaseUri)
    {
        $this->client = new Client(['timeout' => 10, 'verify' => false]);
        $this->sportsbookBaseUri = $sportsbookBaseUri;
        $this->app = $app;
    }

    protected function patternResponse(bool $isSuccess, string $message): array
    {
        return [
            "success" => $isSuccess,
            "data" => $message
        ];
    }
}
