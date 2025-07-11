<?php

namespace App\Controllers;

use Carbon\Carbon;
use App\Repositories\TransactionsRepository;
use Illuminate\Support\Collection;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;


class PaymentsController implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->match('/', 'App\Controllers\PaymentsController::index')
            ->bind('index')
            ->before(function () use ($app) {
                if (!p('admin.payments')) {
                    $app->abort(403);
                }
            })
            ->method('GET|POST');

        $factory->match('/pending-deposits/', 'App\Controllers\PaymentsController::pendingDeposits')
                ->bind('pending-deposits')
                ->before(function () use ($app) {
                    if (!p('admin.payments.pending-deposits')) {
                        $app->abort(403);
                    }
                })
                ->method('GET|POST');

        $factory->match('/pending-deposits/approve/', 'App\Controllers\PaymentsController::approveDeposit')
               // ->bind('pending-deposits/approve')
                ->before(function () use ($app) {
                    if (!p('admin.payments.approve.pending-deposits')) {
                        $app->abort(403);
                    }
                })
                ->method('POST');

        
        return $factory;
    }


    function approveDeposit(Application $app, Request $req){
        $repo    = new TransactionsRepository($app);
        return $repo->approveDeposit($req) ? 'Approved' : 'Not Approved';
    }
    
    function pendingDeposits(Application $app, Request $req){
        if(empty($req->get('start_date')))
            $req->attributes->set('start_date', Carbon::create()->subMonth());
        $req->attributes->set('status', 'pending');
        $repo    = new TransactionsRepository($app);
        $deps    = $repo->getDeposits($req);
        $initial = ['data' => $deps['paginator']['data'], 'defer_option' => $deps['paginator']['recordsTotal'], 'initial_length' => 25, 'order' => ['column' => 1, 'type' => "desc"]];
        return $app['blade']->view()->make('admin.payments.pending-deposits', compact('app', 'deps', 'initial'))->render();        
    }
    
    /**
     * Deletes a single image
     *
     * @param Application $app
     * @param Request $request
     */
    public function index(Application $app, Request $request)
    {
        return $app['blade']->view()->make('admin.payments.index', compact('app'))->render();
    }

}
