<?php
/**
 * Created by PhpStorm.
 * User: iondum
 * Date: 08/11/18
 * Time: 20:12
 */

namespace App\Controllers;

use App\Classes\DateRange;
use App\Helpers\GrsHelper;
use App\Helpers\PaginationHelper;
use App\Models\RiskProfileRating;
use App\Models\User;
use App\Repositories\UserRepository;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use App\Extensions\Database\FManager as DB;

class RiskProfileRatingController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->match('/aml-profile-settings/', 'App\Controllers\RiskProfileRatingController::amlProfileSettings')
            ->bind('settings.aml-profile.index')
            ->before(function () use ($app) {
                if (!p('settings.aml-profile.section')) {
                    $app->abort(403);
                }
            })->method('GET|POST');


        $factory->match('/rg-profile-settings/', 'App\Controllers\RiskProfileRatingController::rgProfileSettings')
            ->bind('settings.rg-profile.index')
            ->before(function () use ($app) {
                if (!p('settings.rg-profile.section')) {
                    $app->abort(403);
                }
            })->method('GET|POST');

        $factory->match('/aml-rg-profile-settings/crud/', 'App\Controllers\RiskProfileRatingController::crudAmlRg')
            ->bind('settings.aml-profile.crud')
            ->before(function () use ($app) {
                if (!p('settings.aml-profile.section')) {
                    $app->abort(403);
                }
            })->method('GET|POST');


        return $factory;
    }


    public function crudAmlRg(Application $app, Request $request) {
        $error = [
            "status" => "error",
            "message" => "There was an internal error. Please reload the page and try again."
        ];
        $country_jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');
        $jurisdiction = $request->get('jurisdiction', $country_jurisdiction_map['default']);

        if ($request->get('action') === 'create') {
            $value = $request->get('value');

            if ($request->get('is_interval')) {
                // interval
                $interval = [$start = $request->get('start', 0)];
                if (!empty($end = $request->get('end'))) {
                    array_push($interval, $end);
                }

                if (empty($start) && empty($end)) {
                    $error['message'] = "Start input can not be empty.";
                    return $app->json($error);
                }

                $value = implode(",", $interval);
            }

            $rpr = new RiskProfileRating([
                "category" => $request->get('parent'),
                "section" => $request->get('section'),
                "title" => $request->get('title'),
                "score" => $request->get('score'),
                "jurisdiction" => $jurisdiction,
                "name" => $value,
                "type" => ""
            ]);

            DB::shBeginTransaction(true);
            try {
                if (DB::getShardingStatus()) {
                    DB::bulkInsert('risk_profile_rating', null, [$rpr->getAttributes()]);
                }
                DB::bulkInsert('risk_profile_rating', null, [$rpr->getAttributes()], DB::getMasterConnection());
                DB::shCommit(true);
            } catch (\Exception $e) {
                DB::shRollback(true);
                if($e->getCode() == 23000) {
                    $error['message'] = "Value $value for '{$rpr->parent()->getReplacedTitle()}' was already taken.";
                } else {
                    $app['monolog']->addError('crudAmlRg', [$e->getMessage()]);
                }

                return $app->json($error);
            }

        } elseif ($request->get('action') === 'delete') {
            DB::shBeginTransaction(true);
            try {
                DB::table('risk_profile_rating')
                    ->where('category','=', $request->get('category'))
                    ->where('name','=', $request->get('name'))
                    ->where('jurisdiction','=', $jurisdiction)
                    ->where('section','=', $request->get('section'))
                    ->delete();
                DB::shCommit(true);
            } catch (\Exception $e) {
                DB::shRollback(true);
                $app['monolog']->addError('crudAmlRg', [$e->getMessage()]);
                return $app->json($error);
            }
        }


            return $app->json(['status' => "success"]);
    }

    public function amlProfileSettings(Application $app, Request $request)
    {
        $main_section = RiskProfileRating::AML_SECTION;
        $country_jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');
        $jurisdiction = $request->get('jurisdiction', $country_jurisdiction_map['default']);

        if ($request->isXmlHttpRequest()) {
            return $this->xmlHttpRequestHandler($app, $request);
        }

        $data = RiskProfileRating::parents($main_section, $jurisdiction)->get();

        return $app['blade']->view()->make(
            'admin.settings.risk-profile-rating.index',
            compact('app', 'main_section', 'data', 'jurisdiction', 'country_jurisdiction_map')
        )->render();
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    private function xmlHttpRequestHandler(Application $app, $request)
    {
        foreach ($request->get('score', []) as $id => $score) {
            RiskProfileRating::getElementById($id)->update(['score' => $score]);
        }

        foreach ($request->get('data') as $id => $values) {
            $parent = RiskProfileRating::getElementById($id)->first();
            $parent_data = $parent->data;
            $parent_data['replacers'] = $values;
            RiskProfileRating::getElementById($id)->update(['data' => $parent_data]);
        }
        return $app->json([
            'status' => 'success',
            'message' => 'Your changes were saved. <br>Please refresh the page to reflect the changes.'
        ]);
    }

    public function rgProfileSettings(Application $app, Request $request)
    {
        $main_section = RiskProfileRating::RG_SECTION;
        $country_jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');
        $jurisdiction = $request->get('jurisdiction', $country_jurisdiction_map['default']);

        if ($request->isXmlHttpRequest()) {
            return $this->xmlHttpRequestHandler($app, $request);
        }

        $data = RiskProfileRating::parents($main_section, $jurisdiction)->whereNotIn('name', ['wagered_last_12_months', 'countries', 'deposited_last_12_months'])->get();

        return $app['blade']->view()->make(
            'admin.settings.risk-profile-rating.index',
            compact('app', 'main_section', 'data', 'jurisdiction', 'country_jurisdiction_map')
        )->render();
    }

    /**
     * Show all data from Risk_profile_rating_rog table
     *
     * @param Application $app
     * @param User|null $user
     * @param Request $request
     * @param string $menu
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public static function grsScoreReport(Application $app, User $user = null, Request $request = null, $menu = '')
    {
        if ($request->isXmlHttpRequest()) {
            foreach ($request->get('form') as $form_elem) {
                $request->request->set($form_elem['name'], $form_elem['value']);
            };
        }
        if ($user) {
            $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_CUR_MONTH);
            $columns =
                [
                    'created_at' => 'Created at',
                    'rating_type' => 'Type',
                    'rating' => 'Rating',
                    'rating_tag' => 'Rating Tag',
                    'influenced_by' => 'influenced by'
                ];
        } else {
            $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_TODAY);

            $columns =
                [
                    'user_id' => 'User Id',
                    'rating' => 'Rating',
                    'rating_tag' => 'Rating Tag',
                    'country' => 'Country',
                    'created_at' => 'Created at',
                    'influenced_by' => 'Influenced by'
                ];
        }
        $cols = array_diff(array_keys($columns), ['rating_type', 'created_at']);
        $type = $request->get('type', '');
        if (!empty($menu)) {
            $type = $menu;
        }
        $exclude_columns = ['rating', 'influenced_by'];
        $risk_profile_repo = $app['risk_profile_rating.repository'];
        $rating_tags = GrsHelper::getRatingScoreFilterRange($app, $request->get('section_profile_rating_start'), $request->get('section_profile_rating_end'));
        $data = $risk_profile_repo::getGRSSCoreReport($request, $date_range, $rating_tags, $cols, $type, $user);
        $page = (new PaginationHelper(
            $data,
            $request,
            [
                'length' => 25,
                'order' => ['column' => 'score', 'dir' => 'DESC']
            ]
        ))->getPage(!$request->isXmlHttpRequest());
        return $request->isXmlHttpRequest()
            ? $app->json($page) :
            $app['blade']->view()->make('admin.settings.risk-profile-rating.grsReport',
                compact('app', 'page', 'columns', 'exclude_columns', 'date_range', 'menu', 'user'))->render();
    }
}
