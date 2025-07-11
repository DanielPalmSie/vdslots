<?php

namespace App\Providers;

use App\Models\GameTagConnection;
use App\Observers\GameTagConnectionObserver;
use Pimple\Container;
use Silex\Application;
use App\Models\Trophy;
use App\Models\TrophyAwards;
use App\Observers\TrophyAwardsObserver;
use App\Observers\TrophyObserver;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;

class AppServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $pimple)
    {
        // TODO: Implement register() method.
    }

    public function boot(Application $app)
    {
        TrophyAwards::observe(TrophyAwardsObserver::class);
        Trophy::observe(TrophyObserver::class);
        GameTagConnection::observe(GameTagConnectionObserver::class);
    }
}
