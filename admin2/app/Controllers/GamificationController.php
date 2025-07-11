<?php

namespace App\Controllers;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;

class GamificationController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/', 'App\Controllers\GamificationController::dashboard')
            ->bind('gamification-dashboard')
            ->before(function () use ($app) {
                if (!p('gamification.section')) {
                    $app->abort(403);
                }
            });

        return $factory;
    }

    /**
     * Renders the Gamification dashboard
     *
     * @param Application $app
     * @return mixed
     */
    public function dashboard(Application $app)
    {
        return $app['blade']->view()->make('admin.gamification.index', compact('app'))->render();
    }

}
