<?php

namespace App\Controllers;

use App\Classes\DateRange;
use App\Helpers\PaginationHelper;
use App\Repositories\GamesRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Models\Game;
use App\Extensions\Database\FManager as DB;
use App\Repositories\GameRepository;

class GameController implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/', 'App\Controllers\GameController::dashboard')
            ->bind('game.dashboard')
            ->before(function () use ($app) {
                if (!p('game.link')) {
                    $app->abort(403);
                }
            });

        $factory->get('/filter/', 'App\Controllers\GameController::filter')
            ->bind('game.ajaxfilter')
            ->before(function () use ($app) {
                if (!p('game.link')) {
                    $app->abort(403);
                }
            });

        $factory->get('/getgame/', 'App\Controllers\GameController::getGameByID')
            ->bind('game.getbyid')
            ->before(function () use ($app) {
                if (!p('game.link')) {
                    $app->abort(403);
                }
            });

        $factory->get('/desktop_or_mobile/', 'App\Controllers\GameController::isGameForDesktopOrMobile')
            ->bind('game.desktop_or_mobile')
            ->before(function () use ($app) {
                if (!p('game.link')) {
                    $app->abort(403);
                }
            });

        $factory->get('/games/', 'App\Controllers\SettingsGamesController::index')
            ->bind('settings.games.index')
            ->before(function () use ($app) {
                if (!p('settings.games.section')) {
                    $app->abort(403);
                }
            });

        $factory->get('/games/edit/', 'App\Controllers\SettingsGamesController::edit')
            ->bind('settings.games.edit')
            ->before(function () use ($app) {
                if (!p('settings.games.section')) {
                    $app->abort(403);
                }
            });

        $factory->get('/games/bulkimport/', 'App\Controllers\GameController::bulkImportView')
            ->bind('games.bulk-import')
            ->before(function () use ($app) {
                if (!p('settings.games.section')) {
                    $app->abort(403);
                }
            });

        $factory->post('/games/bulk/', 'App\Controllers\GameController::handleBulkImportGames')
            ->bind('games.handle-bulk-import')
            ->before(function () use ($app) {
               if(!p('settings.games.section')) {
                   $app->abort(403);
               }
            });

        return $factory;
    }

    /**
     * Response for AJAX filtering requests. On the client side it needs to be polished a little bit because
     * select2 accepts this format: ["results": {"id": 32, "text": "sometext"}, .....]
     *
     * @param Application $app
     * @param Request $request
     * @return mixed
     */
    public function filter(Application $app, Request $request)
    {
        return Game::where('ext_game_name', 'LIKE', '%'.$request->query->get('q').'%')
            ->orWhere('game_name', 'LIKE', '%'.$request->query->get('q').'%')
            ->get();
    }

    public function getGameByID(Application $app, Request $request)
    {
        return Game::where('game_id', '=', $request->query->get('game_id'))->first();
    }

    /**
     * Determine if a game has a version for desktop or mobile or both
     *
     * @param Application $app
     * @param Request $request
     * @return string
     */
    public function isGameForDesktopOrMobile(Application $app, Request $request)
    {
        $repo   = new GameRepository();
        $result = $repo->isGameForDesktopOrMobile($request->query->get('game_ref'));

        return $result;
    }

    public function dashboard(Application $app)
    {
        return $app['blade']->view()->make('admin.game.index', compact('app'))->render();
    }

    public function bulkImportView(Application $app)
    {
        return $app['blade']->view()->make('admin.game.bulk-import', compact('app'))->render();
    }

    public function handleBulkImportGames(Application $app, Request $request)
    {
        $app['flash']->add('success', "CSV has been received.");
        return $app->json(['success' => true, 'files' => $_FILES]);
    }

}
