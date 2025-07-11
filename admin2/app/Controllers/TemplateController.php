<?php
namespace App\Controllers;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

use Carbon\Carbon;
use App\Helpers\DataFormatHelper;
use App\Helpers\PaginationHelper;
use App\Helpers\DateHelper;
use App\Extensions\Database\FManager as DB;


define('LABEL_TOT','totLabel');
class TemplateController 
{
     /**
     * Map - key value between methodName -> url of the request
     * useful for datatable dynamic url call
     * @var type 
     */
    //TODO: probably move from here
    protected static $map;
    
    /**
     *
     * @var type 
     */
    protected static $pagLength = 25;
    
    /**
     * Contains informations about routes / ... 
     * Definitions in menu.php
     * @var type 
     */
    protected static $subMenu;
    /**
     *
     * @var type 
     */
    protected $params   = [];
    
    /**
     *
     * @var type 
     */
    protected $bindings = [];
    
    /**
     * Generic method for sending variables to template
     * @param Application $app
     * @param Request $request
     * @param mixed $sort
     * @param mixed $columns
     * @param mixed $url
     * @param PaginationHelper $paginator
     * @param mixed $params
     * @param mixed $user
     * @param mixed $length
     * @param mixed $user_section
     * @return mixed
     */
    public function sendToTemplate(Application $app, Request $request, $sort, $columns, $url, $paginator, $params, $user = '', $length = 25, $user_section = '')
    {
        $params = $this->mergeParams($params);
        if ($request->isXmlHttpRequest()) {
            return $app->json($paginator->getPage(false));
        } else {
            $sort = array_search($sort,array_keys($columns));
            $page = $paginator->getPage();
            return $app['blade']->view()->make("admin.$url", compact('app', 'page', 'sort', 'columns', 'a', 'url', 'params', 'user', 'length', 'user_section'))->render();
        }
    }
    
    /**
     *
     * Overriding params from method.
     * eg. Default label for a filter is defined into getParams
     * if you need to override that label, just create the corresponding 
     * variable into method $params['label'] = 'something';
     * @param type $params
     * @return type
     */
    public function mergeParams($params) {
        if (!empty($params)) {
            $params = array_merge($this->params, $params);
        } else {
            $params = $this->params;
        }
        return $params;
    }
    
    /**
     * Return Effective time between periods (overlapping time)
     * @param type $periods
     * @return type
     */
    public static function total_hours_per_day($periods)
    {
        /* TODO: NEEDS TO BE MOVED TO SOME UTILS CLASS*/
        ksort($periods);
        do {
            $count = count($periods);
            foreach ($periods as $key1 => $period1) {
                foreach ($periods as $key2 => $period2) {
                    if ($key2 > $key1 and $period1[0] <= $period2[1] and $period1[1] >= $period2[0]) {
                        $periods[] = [min($period1[0], $period2[0]), max($period2[1], $period1[1])];
                        unset($periods[$key1], $periods[$key2]);
                    }
                }
            }
            $countPeriods = count($periods);
        } while ($count > $countPeriods);
        return array_reduce($periods, function ($total, $period) {
            return $total + $period[0]->diff($period[1])->h;
        });
    }
}