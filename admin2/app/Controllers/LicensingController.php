<?php

namespace App\Controllers;

use App\Helpers\DateHelper;
use App\Models\JpLog;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class LicensingController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/', 'App\Controllers\LicensingController::index')
            ->bind('licensing-index')
            ->before(function () use ($app) {
                if (!p('licensing.section')) {
                    $app->abort(403);
                }
            });
        return $factory;
    }

}
