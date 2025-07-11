<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\SessionListener;
use Pimple\Container;
use Silex\Provider\SessionServiceProvider as BaseSessionServiceProvider;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;

final class SessionServiceProvider extends BaseSessionServiceProvider
{
    public function register(Container $app)
    {
        parent::register($app);

        $app['session.listener'] = function ($app) {
            return new SessionListener($app);
        };

        $app['session.storage'] = function () {
            return new PhpBridgeSessionStorage();
        };
    }
}