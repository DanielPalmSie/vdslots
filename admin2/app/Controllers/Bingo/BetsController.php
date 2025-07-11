<?php

namespace App\Controllers\Bingo;

use App\Models\User;
use App\Services\Bingo\BetService;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BetsController implements ControllerProviderInterface
{
    private Application $app;
    private BetService $betService;

    public function __construct(Application $app, BetService $betService)
    {
        $this->app = $app;
        $this->betService = $betService;
    }

    public function connect(Application $app): ControllerCollection
    {
        $factory = $app['controllers_factory'];

        $factory->get('/userprofile/{user}/bets-wins/bingo-details/{bet_id}/',
            [$this, 'getBetDetails'])
            ->convert('user', $app['userProvider'])
            ->bind('admin.bingo.betswins.details')
            ->before(function () use ($app) {
                if (!p('view.account.betswins')) {
                    $app->abort(403);
                }
            });

        return $factory;
    }

    public function getBetDetails(User $user, Request $request, int $bet_id): JsonResponse
    {
        $result = $this->betService->getBetDetails($user, $request, $bet_id);

        return $this->app->json($result);
    }
}
