<?php

namespace App\Controllers\Api;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class TestController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/test/', 'App\Controllers\Api\TestController::test');

        return $factory;
    }

    public function test(Application $app, Request $request)
    {
        return $app->json(['msg' => 'Api test completed successfully.']);
    }

}
