<?php

namespace App\Controllers;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;

class SettingsController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/', 'App\Controllers\SettingsController::dashboard')
            ->bind('settings-dashboard')
            ->before(function () use ($app) {
                if (!p('settings.section')) {
                    $app->abort(403);
                }
            });

        return $factory;
    }

    /**
     * Renders the Settings dashboard
     *
     * @param Application $app
     * @return mixed
     */
    public function dashboard(Application $app)
    {
        return $app['blade']->view()->make('admin.settings.index', compact('app'))->render();
    }
}
