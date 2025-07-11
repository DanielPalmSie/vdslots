<?php

namespace App\Controllers\Api;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Models\User;

use App\Controllers\UserProfileController;

class BeBettorController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->post(
            '/affordability-check',
            'App\Controllers\Api\BeBettorController::affordabilityCheck'
        );


        $factory->post(
            '/vulnerability-check',
            'App\Controllers\Api\BeBettorController::vulnerabilityCheck'
        );

        return $factory;
    }

    /**
     * Perform the affordability check to BeBettor
     *
     * @param Application $app
     * @param User $user
     * @return false|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function affordabilityCheck(Application $app, Request $request)
    {
        loadPhive();
        $result = UserProfileController::affordabilityCheck(
            $app,
            User::find($request->get('user_id'))
        );

        return $result;
    }

    /**
     * Perform the vulnerability check to BeBettor
     *
     * @param Application $app
     * @param User $user
     * @return false|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function vulnerabilityCheck(Application $app, Request $request)
    {
        $result = UserProfileController::vulnerabilityCheck(
            $app,
            User::find($request->get('user_id'))
        );

        return $result;
    }
}
