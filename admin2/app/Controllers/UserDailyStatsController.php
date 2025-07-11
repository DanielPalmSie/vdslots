<?php

/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.17.
 * Time: 9:29
 */

namespace App\Controllers;

use App\Models\User;
use App\Repositories\UserDailyStatsRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;

class UserDailyStatsController implements ControllerProviderInterface
{
    protected $userDailyStatsRepository;

    public function __construct()
    {
        $this->userDailyStatsRepository = new UserDailyStatsRepository();
    }

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];
        $factory->get('/{username}/gross/', 'App\Controllers\UserDailyStatsController::getGross');
        return $factory;
    }

    public function getGross(Application $app, $username)
    {
        $user = User::where('username', $username)->first();
        return $this->userDailyStatsRepository->getGrossByMonth($user, 4);
    }

}
