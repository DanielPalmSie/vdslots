<?php

namespace App\Controllers\Altenar;

use App\Models\User;
use App\Services\Altenar\BetService as AltenarBetService;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BetsController implements ControllerProviderInterface
{
    private Application $app;
    private AltenarBetService $altenarBetService;

    public function __construct(Application $app, AltenarBetService $altenarBetService)
    {
        $this->app = $app;
        $this->altenarBetService = $altenarBetService;
    }

    public function connect(Application $app): ControllerCollection
    {
        $factory = $app['controllers_factory'];

        $factory->get('/userprofile/{user}/bets-wins/altenar-details/{bet_id}/',
            [$this, 'getAltenarBetDetails'])
            ->convert('user', $app['userProvider'])
            ->bind('admin.altenar-betswins.details')
            ->before(function () use ($app) {
                if (!p('view.account.betswins')) {
                    $app->abort(403);
                }
            });

        return $factory;
    }

    public function getAltenarBets(Request $request, User $user): JsonResponse
    {
        $result = $this->altenarBetService->getAltenarBets($request, $user);

        return $this->app->json($result);
    }

    public function getAltenarBetDetails(User $user, Request $request, int $bet_id): JsonResponse
    {
        $result = $this->altenarBetService->getAltenarBetDetails($user, $request, $bet_id);

        return $this->app->json($result);
    }
}
