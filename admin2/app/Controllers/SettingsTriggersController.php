<?php

/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.17.
 * Time: 9:29
 */

namespace App\Controllers;

use App\Repositories\TriggersRepository;
use Illuminate\Support\Facades\App;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Extensions\Database\FManager as DB;
use Valitron\Validator;
use App\Models\Trigger;

class SettingsTriggersController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->match('/', 'App\Controllers\SettingsTriggersController::triggers')
            ->bind('settings.triggers.index')
            ->before(function () use ($app) {
                if (!p('settings.triggers.section')) {
                    $app->abort(403);
                }
            })->method('GET|POST');

        return $factory;
    }

    /**
     * @param Application $app
     */
//    public function index(Application $app, Request $request)
//    {
//        $breadcrumb = 'List and Search';
//
//        return $app['blade']->view()->make('admin.settings.games.index', compact('app', 'breadcrumb'))->render();
//    }
      /**
     * Get list of triggers
     * @param Application $app
     * @param Request $request
     * @return type
     */
    public function triggers(Application $app, Request $request) {
        
        if ($request->isXmlHttpRequest()) {
            return $this->triggerUpdate($app, $request);
        }
        
        $repo = new TriggersRepository($app);
        $data = $repo->getTriggers($request, $date_range);
        /* save */
        $sort = ['column' => 1, 'order' => 'asc'];
        return $app['blade']->view()->make("admin.triggers.triggers", compact('data', 'app', 'sort', 'date_range'))->render();
    }


    /**
     * Update triggers settings
     * @param Application $app
     * @param Request $request
     * @return type
     */

    public function triggerUpdate(Application $app, Request $request)
    {
        $trigger = new Trigger();
        $repo = new TriggersRepository($app);
        $colors = [];
        $scores = [];
        $ngr_threshold = [];

        parse_str($request->get('params'), $params);

        // Parse parameters 
        foreach ($params as $key => $value) {
            if (explode("-", $key)[0] == 'color') {
                $colors[$key] = $value;
            } else if (explode("-", $key)[0] == 'score') {
                $scores[$key] = $value;
            } else if (explode("-", $key)[0] == 'ngr_threshold') {
                $ngr_threshold[$key] = $value;
            }

        }

        // Collors
        if (!empty($colors)) {

            foreach ($colors as $trigger_name => $color) {
                if (substr($trigger_name, 0, 6) == 'color-') {
                    $trigger_name = str_replace('color-', '', $trigger_name);
                    $obj = $trigger->find($trigger_name);
                    $obj->color = $color;
                    if (!$obj->save()) {
                        $msg = ['status' => 'error', 'message' => 'Data not saved!'];
                        return $app->json($msg);
                    }
                }
            }
        }

        // Scores
        if (!empty($scores)) {

            foreach ($scores as $trigger_name => $score) {
                if (substr($trigger_name, 0, 6) == 'score-') {
                    $trigger_name = str_replace('score-', '', $trigger_name);
                    $obj = $trigger->find($trigger_name);
                    $obj->score = $score;
                    if (!$obj->save()) {
                        $msg = ['status' => 'error', 'message' => 'Data not saved!'];
                        return $app->json($msg);
                    }

                }
            }
        }

        if (!empty($ngr_threshold)) {

            foreach ($ngr_threshold as $trigger_name => $ngr) {
                if (substr($trigger_name, 0, 14) == 'ngr_threshold-') {
                    $trigger_name = str_replace('ngr_threshold-', '', $trigger_name);
                    $obj = $trigger->find($trigger_name);
                    $obj->ngr_threshold = $ngr;
                    if (!$obj->save()) {
                        $msg = ['status' => 'error', 'message' => 'Data not saved!'];
                        return $app->json($msg);
                    }

                }
            }
        }

        $msg = ['status' => 'success', 'message' => 'Saved!'];
        return $app->json($msg);
    }
    

}
