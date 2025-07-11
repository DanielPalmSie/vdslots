<?php

namespace App\Controllers\PoolX;

use App\Models\User;
use App\Services\PoolX\BetService as PoolXBetService;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BetsController implements ControllerProviderInterface
{
    private Application $app;
    private PoolXBetService $poolXBetService;

    public function __construct(Application $app, PoolXBetService $poolXBetService)
    {
        $this->app = $app;
        $this->poolXBetService = $poolXBetService;
    }

    public function connect(Application $app): ControllerCollection
    {
        $factory = $app['controllers_factory'];

        $factory->get('/userprofile/{user}/bets-wins/poolx-details/{bet_id}/',
            [$this, 'getPoolXBetDetails'])
            ->convert('user', $app['userProvider'])
            ->bind('admin.poolx-betswins.details')
            ->before(function () use ($app) {
                if (!p('view.account.betswins')) {
                    $app->abort(403);
                }
            });

        return $factory;
    }

    public function getPoolXBets(Request $request, User $user): JsonResponse
    {
        $result = $this->poolXBetService->getPoolXBets($request, $user);

        return $this->app->json($result);
    }

    public function getPoolXBetDetails(User $user, Request $request, int $bet_id): JsonResponse
    {
        $result = $this->poolXBetService->getPoolXBetDetails($user, $request, $bet_id);

        return $this->app->json($result);
    }
}
