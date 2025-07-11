<?php
namespace App\Controllers;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Models\Jackpot;
use App\Repositories\JackpotRepository;

class JackpotController implements ControllerProviderInterface
{
    public function __construct()
    {
        $this->repo = new JackpotRepository();
    }

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->match('/', 'App\Controllers\JackpotController::index')
        ->bind('jackpot.index')
        ->before(function () use ($app) {
            if (!p('jackpot.section')) {
                $app->abort(403);
            }
        })->method('GET|POST');
        
        $factory->match('/jackpots/updatejackpot-xeditable/', 'App\Controllers\JackpotController::updateJackpotXeditable')
            ->bind('jackpots.updatejackpots')
            ->before(function () use ($app) {
            if (! p('jackpot.section')) {
                $app->abort(403);
            }
        })
            ->method('GET|POST');
             
        $factory->match('/jackpots/gettotalcontribution/', 'App\Controllers\JackpotController::getTotalContribution')
            ->bind('jackpots.gettotalcontribution')
            ->before(function () use ($app) {
            if (! p('jackpot.section')) {
                $app->abort(403);
            }
        })
            ->method('GET|POST');
                  
        $factory->match('/getwinhistory/', 'App\Controllers\JackpotController::getWinHistory')
        ->bind('jackpots.getwinhistory')
        ->before(function () use ($app) {
            if (! p('jackpot.section')) {
                $app->abort(403);
            }
        })
        ->method('GET|POST');
        
        return $factory;
    }

    /**
     * @param Application $app
     */
    public function index(Application $app, Request $request)
    {
        $jackpots = Jackpot::all();
        
        $breadcrumb = "List";
        
        return $app['blade']->view()
        ->make('admin.gamification.jackpot.index', compact('jackpots', 'app', 'breadcrumb'))
        ->render();
    }
    
    public function updateJackpotXeditable(Application $app, Request $request)
    {  
        $property = $request->get('name');
        $jackpot = Jackpot::find($request->get('pk'));
        $jackpot->$property = $request->get('value');

        if ($jackpot->save()) {
            return json_encode([
                'status' => 1,
                'newValue' => $request->get('value')
            ]);
        } else {
            return json_encode([
                'status' => 0
            ]);
        }
    }
    
    
    public function getWinHistory(Application $app, Request $request)
    {
        $breadcrumb = "Jackpot History";
        
        $winhistlog = $this->repo->getWinLog();
            
            return $app['blade']->view()
            ->make('admin.gamification.jackpot.jackpotwinlog', compact('winhistlog','app', 'breadcrumb'))
            ->render();
    }
}

