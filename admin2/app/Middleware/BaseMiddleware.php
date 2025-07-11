<?php

namespace App\Middleware;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class BaseMiddleware
{
    public Request $request;
    public Application $app;

    public function __construct($app, Request $request)
    {
        $this->request = $request;
        $this->app = $app;
    }
}
