<?php

namespace App\Controllers;

use Illuminate\View\View;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class SportsbookController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/', 'App\Controllers\SportsbookController::index')
            ->bind('sportsbook.index')
            ->before(function () use ($app) {
                if (!p('admin.sportsbook.index')) {
                    $app->abort(403);
                }
            });

        $factory->get('/clean-events/', 'App\Controllers\SportsbookController::cleanEvents')
            ->bind('sportsbook.clean-events')
            ->before(function () use ($app) {
                if (!p('admin.sportsbook.index')) {
                    $app->abort(403);
                }
            });


        $factory->post('/remove-events/', 'App\Controllers\SportsbookController::removeEvents')
            ->bind('remove-events')
            ->before(function () use ($app) {
                if (!p('admin.sportsbook.index')) {
                    $app->abort(403);
                }
            });

        $factory->get('/download-not-settled-tickets/', 'App\Controllers\SportsbookController::downloadNotSettledTickets')
            ->bind('sportsbook.download-not-settled-tickets')
            ->before(function () use ($app) {
                if (!p('admin.sportsbook.download-not-settled-tickets')) {
                    $app->abort(403);
                }
            });

        return $factory;
    }

    /**
     * @param Application $app
     * @return View
     */
    public function index(Application $app, Request $request)
    {
        return $app['blade']->view()->make('admin.sportsbook.index', compact('app'))->render();
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return View
     */
    public function cleanEvents(Application $app, Request $request)
    {
        $event = NULL;
        $event_id = $request->get('event_id');
        $event_found_success = false;
        $event_found_error = false;

        if (isset($event_id)) {
            $event = $this->searchForEventsWithId($app, $event_id);
            if (isset($event)) {
               $event_found_success = true;
            } else {
                $event_found_error = true;
            }
        }

        return $app['blade']->view()->make('admin.sportsbook.clean-events', compact('app','event','event_id','event_found_success','event_found_error'))->render();
    }



    /**
     * @param Application $app
     * @param Request $request
     * @return View
     */
    public function removeEvents(Application $app, Request $request)
    {
        $remove_event_id = $request->get('remove_event');
        $event_removed_success = false;
        $event_removed_error = false;

        if (isset($remove_event_id)) {
            $event_removed = $this->removeEventWithId($app, $remove_event_id);

            if ($event_removed) {
                $event_removed_success = true;
             } else {
                 $event_removed_error = true;
             }
        }

        return $app['blade']->view()->make('admin.sportsbook.clean-events', compact('app','remove_event_id','event_removed_success', 'event_removed_error'))->render();
    }

    /**
     * @param Application $app
     * @return View
     */
    public function downloadNotSettledTickets(Application $app, Request $request)
    {
        return $app['blade']->view()->make('admin.sportsbook.download-not-settled-tickets', compact('app'))->render();
    }

    /**
     * Search for an event via event ID
     *
     * @param Application $app
     * @param string $event_id
     * @return NULL|array
     */
    public function searchForEventsWithId(Application $app, string $event_id): ?array
    {
        $event = $app['sportsbook_clean_event_service']->getSportEventViaId($event_id);

        return $event;
    }

    /**
     * Remove an event via event ID
     *
     * @param Application $app
     * @param string $event_id
     * @return bool
     */
    public function removeEventWithId(Application $app, string $event_id): bool
    {
        $event = $app['sportsbook_clean_event_service']->removeSportEventViaId($event_id);

        return $event;
    }
}
