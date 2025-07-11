<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 03/02/2016
 * Time: 12:31
 */

namespace App\Repositories;

use App\Classes\DateRange;
use App\Helpers\DownloadHelper;
use Carbon\Carbon;
use App\Extensions\Database\FManager as DB;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class TriggersRepository
{
    /**
     * 
     * @param Request $request
     * @param type $date_range
     * @return type
     */
    public function getTriggersLog(Request $request, $date_range)
    {
        
        $username      = $request->get('username');
        $trigger_type  = $request->get('trigger_type');
        $country       = $request->get('country');
        
        if ($request->isMethod('POST')) {
            print_r($request->get('form'));die;
            foreach ($request->get('form') as $form_elem) {
                print_r($form_elem);die;
                $params[$form_elem['name']] = $form_elem['value'];
                if (empty($params['date-range'])) {
                    $params['start_date'] = Carbon::now()->firstOfMonth()->format('Y-m-d') . ' 00:00:00';
                    $params['end_date'] = Carbon::now()->endOfMonth()->format('Y-m-d') . ' 23:59:59';
                } else {
                    $params['start_date'] = explode(' - ', $params['date-range'])[0] . ' 00:00:00';
                    $params['end_date'] = explode(' - ', $params['date-range'])[1] . ' 23:59:59';
                }
                if (empty($params['date-range2'])) {
                    $params['start_date2'] = Carbon::now()->firstOfMonth()->format('Y-m-d') . ' 00:00:00';
                    $params['end_date2'] = Carbon::now()->endOfMonth()->format('Y-m-d') . ' 23:59:59';
                } else {
                    $params['start_date2'] = explode(' - ', $params['date-range2'])[0] . ' 00:00:00';
                    $params['end_date2'] = explode(' - ', $params['date-range2'])[1] . ' 23:59:59';
                }
            }
        } 
        
        $param_bindings = [
            'start_date' => $date_range->getStart('date') . ' 00:00:00',
            'end_date' => $date_range->getEnd('date') . ' 23:59:59',
        ];
        $where_country = '';
        $where_username = '';
        $where_trigger_name = '';
        
        if (!empty($user_id)) {
            $where_username = " AND username = :username ";
            $param_bindings['username'] = $username;
        }
        if (!empty($country)) {
            $where_country = " AND users.country = :country ";
            $param_bindings['country'] = $country;
        }
        if (!empty($trigger_type)) {
            $where_trigger_name = " AND trigger_name LIKE '$trigger_type%'";
        }
        $where_date = " AND triggers_log.created_at BETWEEN :start_date AND :end_date ";
        
        return DB::shsSelect('triggers_log', "
            SELECT 
                 users.username,
                 concat(users.firstname,' ',users.lastname)as fullname,
                 users.country,
                 created_at,
                 users.register_date,
                 indicator_name,
                 trigger_name,
                 color  
             FROM
                 triggers_log
             JOIN
                 triggers
             ON 
                 trigger_name = triggers.name
             JOIN 
                 users
             ON 
                 users.id = triggers_log.user_id    
             WHERE 1 
             $where_date
             $where_country
             $where_username
             $where_trigger_name ",$param_bindings);
    }
    public function getTriggers(Request $request,$date_range) {
        
        $param_bindings = [];
        $where_name = '';
        $trigger_name      = $request->get('name');
        if (!empty($trigger_name)) {
            $where_name = " AND name = :name ";
            $param_bindings['name'] = $trigger_name;
        }
        $sql = "SELECT *
                FROM triggers
                WHERE 1
                $where_name 
                ORDER BY LENGTH(name), name";
        
        return DB::shsSelect('triggers', $sql, $param_bindings);
    }
        


}