<?php

namespace App\Controllers;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repositories\TriggersRepository;
use Carbon\Carbon;
use App\Helpers\DataFormatHelper;
use App\Helpers\PaginationHelper;
use App\Helpers\DateHelper;
use App\Extensions\Database\FManager as DB;
use App\Models\Trigger;

class TriggersController extends TemplateController implements ControllerProviderInterface
{
    
    /**
     *
     * @var type 
     */
    private $query    = [];

    /**
     * Get and set all the parameters useful for all templates. 
     * @param Application $app
     * @param Request $request
     */
    public function getParams(Application $app, Request $request, $params = []) {
        $query = [];
        $params['current_year_month'] = date("Y-m");
        $query['date_range']    = "";
        $query['date_range2']   = " BETWEEN :start_date2 AND :end_date2 ";
        $query['country']       = " AND users.country = :country";
        $query['username']      = " AND users.username = :username";
        
        if ($request->isMethod('POST')) {
            foreach ($request->get('form') as $form_elem) {
                $params[$form_elem['name']] = $form_elem['value'];
                if (empty($params['date-range'])) {
                    $params['start_date'] = Carbon::now()->firstOfMonth()->format('Y-m-d') . ' 00:00:00';
                    $params['end_date'] = Carbon::now()->endOfMonth()->format('Y-m-d') . ' 23:59:59';
                } else {
                    $params['start_date'] = explode(' - ', $params['date-range'])[0] . ' 00:00:00';
                    $params['end_date'] = explode(' - ', $params['date-range'])[1] . ' 23:59:59';
                }
            }
        } else {
            if (empty($request->get('date-range'))) {
                $params['start_date'] = Carbon::now()->firstOfMonth()->format('Y-m-d') . ' 00:00:00';
                $params['end_date'] = Carbon::now()->endOfMonth()->format('Y-m-d') . ' 23:59:59';
            } else {
                $params['start_date'] = explode(' - ', $request->get('date-range'))[0] . ' 00:00:00';
                $params['end_date'] = explode(' - ', $request->get('date-range'))[1] . ' 23:59:59';
            }
            $params['country'] = $request->get('country');
            $params['having_count'] = $request->get('having_count');
            $params['username'] = $request->get('username');
            $params['date-range'] = $request->get('date-range') ? $request->get('date-range') : date("Y-m-d") . ' - ' . date("Y-m-d") ;
            
        }
        $this->params    = $params;
        $this->query     = $query;
    }
    
    /**
     * Routes for all methods. 
     * It use informations coming from the menu
     * @param Application $app
     * @return Application
     */
    public function connect(Application $app)
    {
        $section = 'triggers';
        $controller = ucwords($section). "Controller";
        $factory = $app['controllers_factory'];
        $this->subMenu = $app['vs.menu']['triggers']['submenu'];
//        echo "connect";
//        echo "<pre>";
//        root.url
//        $this->subMenu[] = 
//        print_r($app['vs.menu']['triggers']);die;
        foreach ($this->subMenu as $a) {
            $url        = $a['url'];
            $method     = $a['method'];
            $methodName = $a['methodName'];
            self::$map[$methodName] = $url;

            if ($method == 'GET') {
                $factory->get($url . '/', "App\Controllers\TriggersController::$methodName")
                    ->bind($url)
                    ->before(function () use ($app, $section) {
                        if (!p("$section.section")) {
                            $app->abort(403);
                        }
                    });
            } elseif ($method == 'GET|POST') {
                $factory->match($url . '/', "App\Controllers\TriggersController::$methodName")
                    ->bind($url)
                    ->before(function () use ($app, $section) {
                        if (!p("$section.section")) {
                            $app->abort(403);
                        }
                    })->method('GET|POST');
            }
        }
        return $factory;
    }
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
     * Update triggers color
     * @param Application $app
     * @param Request $request
     * @return type
     */
    public function triggerUpdate(Application $app, Request $request) {
        $trigger = new Trigger();
        $repo = new TriggersRepository($app);
        $colors = array();
        if(!empty($request->get('colors'))) {
            parse_str($request->get('colors'), $colors);
            foreach($colors as $trigger_name => $color) {
                if(substr($trigger_name,0,6) == 'color-') {
                    $trigger_name = str_replace('color-','',$trigger_name);
                    $obj = $trigger->find($trigger_name);
                    $obj->color = $color;
                    if(!$obj->save()) {
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
