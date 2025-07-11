<?php

namespace App\Controllers\Api;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class EmailController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/email/template/', 'App\Controllers\Api\EmailController::test');

        return $factory;
    }

    public function test(Application $app, Request $request)
    {
        return $app->json(['msg' => 'HELLO']);
    }

}
