<?php

namespace App\Controllers;

use App\Repositories\ResponsibilityCheckRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repositories\MonitoringRepository;
use App\Repositories\ActionRepository;
use App\Repositories\UserRepository;
use Carbon\Carbon;
use App\Helpers\DataFormatHelper;
use App\Helpers\PaginationHelper;
use App\Helpers\DateHelper;
use App\Models\User;
use App\Extensions\Database\FManager as DB;


class MonitoringController extends TemplateController implements ControllerProviderInterface
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

    private $title;


    public function getParams(Application $app, Request $request, $params = []) {
        $query = [];
        $params['current_year_month'] = date("Y-m");
        $query['date_range']    = "";
        $query['date_range2']   = " BETWEEN :start_date2 AND :end_date2 ";
        $query['country']       = " AND users.country = :country";
        $query['username']      = " AND users.username = :username";
        $query['trigger_name']      = " AND triggers_log.trigger_name = :trigger_name";

        if ($request->isMethod('POST')) {
            foreach ($request->get('form') as $form_elem) {
                $params[$form_elem['name']] = $form_elem['value'];
                if (empty($params['date-range'])) {
                    $params['start_date'] = Carbon::now()->startOfDay()->format('Y-m-d') . ' 00:00:00';
                    $params['end_date'] = Carbon::now()->endOfDay()->format('Y-m-d') . ' 23:59:59';
                } else {
                    $params['start_date'] = explode(' - ', $params['date-range'])[0] . ' 00:00:00';
                    $params['end_date'] = explode(' - ', $params['date-range'])[1] . ' 23:59:59';
                }
            }
        } else {
            if (empty($request->get('date-range'))) {
                $params['start_date'] = Carbon::now()->startOfDay()->format('Y-m-d') . ' 00:00:00';
                $params['end_date'] = Carbon::now()->endOfDay()->format('Y-m-d') . ' 23:59:59';
            } else {
                $params['start_date'] = explode(' - ', $request->get('date-range'))[0] . ' 00:00:00';
                $params['end_date'] = explode(' - ', $request->get('date-range'))[1] . ' 23:59:59';
            }
            $params['country'] = $request->get('country');
            $params['having_count'] = $request->get('having_count');
            $params['username'] = $request->get('username');
            $params['trigger_name'] = $request->get('trigger_name');
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
        $section = 'monitoring';
        $factory = $app['controllers_factory'];

        $factory->match('/responsible-gaming-monitoring/', "App\Controllers\MonitoringController::rgMonitoring")
        ->bind('responsible-gaming-monitoring')
        ->before(function () use ($app) {
            if (!p("rg.section.monitoring")) {
                $app->abort(403);
            }
        })->method('GET|POST');

        $factory->match('/fraud-monitoring/', "App\Controllers\MonitoringController::fraudMonitoring")
        ->bind('fraud-monitoring')
        ->before(function () use ($app) {
            if (!p("fraud.section.fraud-monitoring")) {
                $app->abort(403);
            }
        })->method('GET|POST');

        $factory->match('/fraud-aml-monitoring/', "App\Controllers\MonitoringController::fraudAmlMonitoring")
        ->bind('fraud-aml-monitoring')
        ->before(function () use ($app) {
            if (!p("fraud.section.aml-monitoring")) {
                $app->abort(403);
            }
        })->method('GET|POST');

        return $factory;
    }


    /**
     * Triggers related to AML (anti money laundering)
     * @param Application $app
     * @param Request $request
     * @return type
     */
    public function fraudAmlMonitoring(Application $app, User $user = null,  Request $request)
    {
        $request->request->set('trigger_type', 'AML');
        $request->request->set('trigger_type', 'AML');
        $this->title = 'Aml monitoring';
        return $this->Monitoring($app, $request, $user);
    }
    /**
     * Triggers related to RG (responsible gaming)
     * @param Application $app
     * @param Request $request
     * @return type
     */
    public function rgMonitoring(Application $app, User $user = null, Request $request)
    {
        $this->title = 'RG monitoring';
        $request->request->set('trigger_type', 'RG');
        $request->request->set('trigger_type', 'RG');
        return $this->Monitoring($app, $request, $user);
    }

    /**
     * Triggers related to FR (fraud)
     * @param Application $app
     * @param Request $request
     * @return type
     */
    public function fraudMonitoring(Application $app, User $user = null,  Request $request)
    {
        $this->title = 'Fraud monitoring';
        $request->request->set('trigger_type', 'FR');
        $request->request->set('trigger_type', 'FR');
        return $this->Monitoring($app, $request, $user);
    }


    private function Monitoring(Application $app, Request $request, User $user = null) {

        $url = 'fraud.monitoring';

        $columns = [
            'id'=>'Id',
            'fullname'=>'Full name',
            'country'=>'country',
            'created_at'=>'Created At',
            'register_date'=>'Register Date',
            'indicator_name'=>'Indicator Name',
            'trigger_description'=>'Description',
            'trigger_name'=>'Trigger Name',
            'descr'=>'Descr.',
            'data'=>'Extra',
            'color'=>'Color',
            'rg_profile_score' => 'RG Profile Score'
        ];
        if (!$user) {
            $columns = ['declaration_proof' => ''] + $columns;
        }
            /*if (!empty($user)) {
               $columns['reports'] = 'Reports';
            }*/
        /*
        if ($user) {
            $actor = UserRepository::getCurrentUser();
            $description = "Username: {$user->username} reviewed by {$actor->username}";
            ActionRepository::logAction($user->id, $description, 'monitoring-check', true, $actor->id);
        }*/
        /*********************/
        $this->getParams($app, $request);
        $params   = $this->params;
        $query    = $this->query;
        /*********************/
        $trigger_type = $request->get('trigger_type');
        $params['trigger_type'] = $trigger_type;
        if ($trigger_type == 'AML') {
            $columns['rg_profile_score'] = 'AML Profile Score';
        }
        $params['report_title'] = $this->title;
        $sort = ['column' => !$user ? 4 : 3, 'type' => "desc"];
        $repo = new MonitoringRepository();
        if ($request->isMethod('GET') && !empty($user) && empty($request->get('date-range'))) {
            $params['start_date'] = Carbon::now()->subDays(30)->startOfDay()->toDateTimeString();
            $params['end_date'] = Carbon::now()->endOfDay()->toDateTimeString();
        }
        $res = $repo->getMonitoring($app, $request, $params, $query, $user);
        foreach ($res as $r) {
            if (empty($r->data)) {
                $r->data = implode(' ', json_decode($r->data));
            }
            $r->username = $r->id;
        }
        $paginator = new PaginationHelper($res, $request, ['length' => self::$pagLength, 'order' => ['column' => 'created_at', 'dir' => 'DESC']]);

        /* Preparing data for Identity check section */
        $params['identity_check_section'] = $this->identityCheckSectionPreparing($user);
        if (licSetting('show_deposit_limit_test', $user->id)){
            $params['deposit_limit_test'] = $this->depositLimitTest($user);
        }

        return $this->sendToTemplate($app, $request, $sort, $columns, $url, $paginator, $params, $user);
    }

    /**
     * @param User|null $user
     * @return array
     */
    private function identityCheckSectionPreparing(User $user = null)
    {
        $section_data['report_title'] = 'Identity check';
        $section_data['columns'] = [
            'user_id'           => 'Id',
            'fullname'          => 'Full name',
            'country'           => 'Country',
            'requested_at'      => 'Requested at',
            'status'            => 'Status',
            'solution_provider' => 'Solution Provider',
        ];
        $section_data['data'] = (new ResponsibilityCheckRepository())->getIdentityCheck($user);
        $section_data['limit'] = ResponsibilityCheckRepository::LIMIT_ENTRIES;

        return $section_data;
    }


    /**
     * @param User|null $user
     * @return array
     */
    private function depositLimitTest(User $user = null)
    {
        $data = (new ResponsibilityCheckRepository())->getDepositLimitIncreaseTest($user);
        $result_data = (new ResponsibilityCheckRepository())->onLoadRgMonitoring($user);

        if (empty($data)) {
            return false;
        }

        $section_data['report_title'] = 'RG test - Deposit limit increase/decrease';
        $section_data['columns'] = [
            'fullname'          => 'Full name',
            'completed_at'      => 'Completed at',
            'result'            => 'Result',
            'rg_evaluation'     => 'RG Evaluation',
        ];
        $section_data['data'] = $data;
        $section_data['result_data'] = $result_data;
        return $section_data;
    }

}
