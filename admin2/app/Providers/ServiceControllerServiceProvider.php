<?php

namespace App\Providers;

use Silex\Application;
use Silex\Provider\ServiceControllerServiceProvider as BaseServiceControllerServiceProvider;
use Silex\Api\BootableProviderInterface;

class ServiceControllerServiceProvider extends BaseServiceControllerServiceProvider implements BootableProviderInterface
{
    public function boot(Application $app)
    {
        // implementation with dependency injection
        // TODO: move routes to separate files api.php and web.php
        /**
         * App\Controllers\Api\RiskProfileRatingController
         */
        $app->post(
            '/api/risk-profile-rating/calculate-score/',
            'risk_profile_rating.controller:getScore'
        );
        $app->post(
            '/api/risk-profile-rating/calculate-score/all',
            'risk_profile_rating.controller:calculateAll'
        );
        $app->post(
            '/api/risk-profile-rating/get-last-score/',
            'risk_profile_rating.controller:getLastScore'
        );
    }
}