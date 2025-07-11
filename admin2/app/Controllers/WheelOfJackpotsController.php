<?php
namespace App\Controllers;

use App\Classes\DateRange;
use App\Models\User;
use App\Repositories\TrophyAwardsRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Models\JackpotWheel;
use App\Models\JackpotWheelSlice;
use App\Models\TrophyAwards;
use App\Repositories\WheelOfJackpotsRepository;
use App\Models\Config;
use App\Helpers\PaginationHelper;
use App\Helpers\Common;

class WheelOfJackpotsController implements ControllerProviderInterface
{
    /** @var WheelOfJackpotsRepository $repo */
    public $repo;

    public function __construct()
    {
        $this->repo = new WheelOfJackpotsRepository();
    }

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $routes = [['url'  => '/',                              'm' => 'index',                        'bind' => 'wheelofjackpots',                    'p' => 'wheelofjackpots.list.awards'],
                   ['url'  => '/createwheel/',                  'm' => 'createWheel',                  'bind' => 'wheelofjackpots-create-wheel',       'p' => 'wheelofjackpots.create.wheel'],
                   ['url'  => '/updatewheel/',                  'm' => 'updateWheel',                  'bind' => 'wheelofjackpots-update-wheel'],
                   ['url'  => '/updatexeditable/',              'm' => 'updateSliceXeditable',         'bind' => 'wheelofjackpots-updateslice-xeditable'],
                   ['url'  => '/addslice/',                     'm' => 'addSlice',                     'bind' => 'wheelofjackpots-add-slice'],
                   ['url'  => '/deleteslice/',                  'm' => 'deleteSlice',                  'bind' => 'wheelofjackpots-delete-slice'],
                   ['url'  => '/gettotalprobability/',          'm' => 'getTotalProbability',          'bind' => 'wheelofjackpots-gettotalprobability'],
                   ['url'  => '/getawardsforselect/',           'm' => 'getAwardsForSelect',           'bind' => 'wheelofjackpots-getawardsforselect'],
                   ['url'  => '/getawardsforselectwithfilter/', 'm' => 'getAwardsForSelectWithFilter', 'bind' => 'wheelofjackpots-getawardsforselectwithfilter'],
                   ['url'  => '/canactivatewheel/',             'm' => 'canActivateWheel',             'bind' => 'wheelofjackpots-canactivatewheel'],
                   ['url'  => '/activatewheel/',                'm' => 'activateWheel',                'bind' => 'wheelofjackpots-activatewheel'],
                   ['url'  => '/deletewheel/',                  'm' => 'deleteWheel',                  'bind' => 'wheelofjackpots-deletewheel'],
                   ['url'  => '/updatejackpot-xeditable/',      'm' => 'updateJackpotXeditable',       'bind' => 'wheelofjackpots-updatejackpot-xeditable'],
                   ['url'  => '/gettotalcontribution/',         'm' => 'getTotalContribution',         'bind' => 'wheelofjackpots-gettotalcontribution'],
                   ['url'  => '/orderslices/',                  'm' => 'orderSlices',                  'bind' => 'wheelofjackpots-orderslices'],
                   ['url'  => '/wheellog/',                     'm' => 'wheelLog',                     'bind' => 'wheelofjackpots-wheellog',           'p' => 'wheelofjackpots.wheellog'],
                   ['url'  => '/updatespinningtime/',           'm' => 'updateWheelSpinningTime',      'bind' => 'wheelofjackpots-updatespinningtime', 'p' => 'wheelofjackpots.updatespinningtime'],
                   ['url'  => '/clone/',                        'm' => 'cloneWheel',                    'bind' => 'wheelofjackpots-clone']];

        Common::doRoutes($app, $factory, $routes, 'wheelofjackpots.update.wheel', 'WheelOfJackpotsController');

        return $factory;
    }

    // TODO remove? check if is this still used...  wheel_list blade doesn't seem to exist
    public function listWheels(Application $app, Request $request)
    {
        return Common::view($app, 'admin.gamification.wheelofjackpots.wheel_list', ['wheels' => JackpotWheel::all(), 'app' => $app]);
    }

    public function index(Application $app, Request $request)
    {
        $config = Config::where("config_tag","spin-time")->get()[0];

        if ($request->getMethod() == 'POST' && !empty($request->get('wheel-spin-time'))) {
            $config->config_value = $request->get('wheel-spin-time');
            $config->save();
        }

        return Common::view($app, 'admin.gamification.wheelofjackpots.index', [
            'wheels'     => JackpotWheel::all(),
            'app'        => $app,
            'breadcrumb' => 'List',
            'configVal'  => $config->config_value
        ]);
    }


    public function createWheel(Application $app, Request $request)
    {
        if ($request->getMethod() == 'POST' && $request->get('name')) {

            // create wheel object
            $wheel = new JackpotWheel();
            $wheel->name = $request->get('name');
            // $wheel->number_of_slices = $request->get('number_of_slices');
            $wheel->number_of_slices = 1; // set a default value of 1 segment
            $wheel->cost_per_spin    = $request->get('cost_per_spin');
            $wheel->active           = 0;
            $wheel->deleted          = 0;
            $wheel->country          = $request->get('country');
            $wheel->style            = $request->get('style');
            $wheel->excluded_countries = implode(" ", $request->get('excluded_countries', []));

            if ($wheel->save()) {
                // create slice objects for each slice
                $slice = new JackpotWheelSlice();
                $slice->wheel_id = $wheel->id;
                $slice->sort_order = 0;
                $slice->save();

                $app['flash']->add('success', 'Wheel successfully created');
                return new RedirectResponse($app['url_generator']->generate('wheelofjackpots-update-wheel', [
                    'wheel_id' => $wheel->id
                ]));
            } else {
                $app['flash']->add('danger', 'Unable to create new wheel');
            }
        }

        $breadcrumb = 'Create';
        $styles =phive('DBUserHandler/JpWheel')->getAllWheelStyles();

        return Common::view($app, 'admin.gamification.wheelofjackpots.create_wheel', [
            'app'        => $app,
            'breadcrumb' => 'Create',
            'styles' => $styles,
        ]);
    }

    public function cloneWheel(Application $app, Request $request)
    {
        $redirectWithErrors = function($errors, $exception = false, $redirect = true) use ($request, $app) {
            foreach ($errors as $error) {
                if (is_array($error)) {
                    $error = $error[0];
                }

                $app['flash']->add('danger', $error);

                if ($exception) {
                    $app['monolog']->addError("Clone wheel: $error");
                }
            }

            if ($redirect) {
                return new RedirectResponse($request->headers->get('referer'));
            }
        };

        $old_wheel = JackpotWheel::find($old_id = $request->get('wheel_id'));

        if (empty($old_wheel)) {
            return $redirectWithErrors(["Wheel with id $old_id not found."]);
        }

        $old_wheel = $old_wheel->toArray();

        $wheel = new JackpotWheel();
        $wheel->fill(array_except($old_wheel, ['id', 'active']));
        $wheel->name .= " | Cloned " . str_random(5);

        try {
            if (!$wheel->save()) {
                return $redirectWithErrors(array_values($wheel->getErrors()));
            }
        } catch (\Exception $e) {
            return $redirectWithErrors([$e->getMessage()], true);
        }

        JackpotWheelSlice::query()
            ->where('wheel_id', '=', $old_wheel['id'])
            ->get()
            ->each(function ($old_slice) use ($wheel, $redirectWithErrors) {
                $old_slice = $old_slice->toArray();

                $slice = new JackpotWheelSlice();
                $slice->fill(array_except($old_slice, ['id']));
                $slice->wheel_id = $wheel->id;
                $slice->save();

                try {
                    if (!$slice->save()) {
                        $redirectWithErrors(array_values($wheel->getErrors()), false, false);
                    }
                } catch (\Exception $e) {
                    $redirectWithErrors([$e->getMessage()],true, false);
                }
            });

        return new RedirectResponse($app['url_generator']->generate('wheelofjackpots-update-wheel',['wheel_id' => $wheel->id]));
    }


    public function updateWheel(Application $app, Request $request)
    {
        $wheel = JackpotWheel::find($request->get('wheel_id'));

        if ($request->getMethod() == 'POST' && !empty($request->get('name'))) {

            // update wheel object
            $wheel->name = $request->get('name');
            // $wheel->number_of_slices = $request->get('number_of_slices');
            $wheel->cost_per_spin = $request->get('cost_per_spin');
            $wheel->country = $request->get('country');
            $wheel->style = $request->get('style');
            $wheel->excluded_countries = implode(" ", $request->get('excluded_countries', []));

            if (!$wheel->active) {
                if ($wheel->isDirty()) {
                    if ($wheel->save()) {
                        $app['flash']->add('success', 'Wheel updated');
                    } else {
                        $app['flash']->add('danger', 'Unable to update wheel');
                    }
                }
            } else {
                $app['flash']->add('danger', 'Unable to update wheel as it is ACTIVE');
            }
        }

        $orderedSlices = $this->repo->getOrderedSlices($wheel);
        $filenames = $this->getWheelSliceImages($orderedSlices);
        $styles = phive('DBUserHandler/JpWheel')->getAllWheelStyles();
        $wheel_style = phive('DBUserHandler/JpWheel')->getWheelStyle($wheel->style);

        return Common::view($app, 'admin.gamification.wheelofjackpots.update_wheel', [
            'wheel' => $wheel,
            'slices' => $orderedSlices,
            'styles' => $styles,
            'wheel_style' => $wheel_style,
            'filenames' => $filenames,
            'app' => $app,
            'breadcrumb' => 'Update'
        ]);
    }

    /**
     * Created: Jonathan Aber
     * Date: 5/6/2017
     *
     * @param Application $app
     * @param Request $request
     * @return mixed
     */
    public function orderSlices(Application $app, Request $request)
    {
        $wheel = JackpotWheel::find($request->get('wheel_id'));

        // TODO remove, is this used? commented for now
//        $slices = $wheel->slices()
//            ->orderBy('sort_order', 'asc')
//            ->get();
        // Array of ordered slices + unique cause it's passing back all the awards that have the same duplicate for each award_id of a slice
        $sortArray = array_values(array_unique($request->get('sortedArray')));

        //if (! $wheel->active) {
            // Go through the array of slices and saved the slices in the database according to the sorted array
            for ($cnt = 0; $cnt < count($sortArray); $cnt ++) {
                $slice = JackpotWheelSlice::find($sortArray[$cnt]);
                $slice->sort_order = $cnt;
                $slice->save();
            }
        //}

        $orderedSlices = $this->repo->getOrderedSlices($wheel);
        $filenames = $this->getWheelSliceImages($orderedSlices);

        return json_encode([
            'success' => true,
            'updatedSliceOrder' => $orderedSlices,
            'imageFilename' => $filenames
        ]);
    }

    /**
     * Activates or deactivates a wheel
     * With the new requested logic CH12243 we can have multiple wheels active
     * However we still need to check the 10M probability and if at least 1 award is set for each slice.
     *
     * @param Application $app
     * @param Request $request
     * @return type
     */
    public function activateWheel(Application $app, Request $request)
    {
        $wheel = JackpotWheel::find($request->get('wheel_id'));

        if ($wheel->active == 0) {

            if ($this->repo->canActivateWheel($request->get('wheel_id'))) {
                $wheel->active = 1;
                if ($wheel->save()) {
                    $app['flash']->add('success', 'Wheel activated');
                    return json_encode([
                        'success' => true,
                        'active' => 1
                    ]);
                } else {
                    $app['flash']->add('danger', 'Unable to activate wheel');
                    return json_encode([
                        'success' => false,
                        'active' => 0
                    ]);
                }
            } else {

                $app['flash']->add('danger', 'Unable to activate wheel. Check if Total Probability is 10,000,000 or if any slice is empty.');
                return json_encode([
                    'success' => false,
                    'active' => 0
                ]);
            }

        } else {
            $wheel->active = 0;
            if ($wheel->save()) {
                $app['flash']->add('success', 'Wheel dactivated');
                return json_encode([
                    'success' => true,
                    'active' => 0
                ]);
            } else {
                $app['flash']->add('danger', 'Unable to dactivate wheel');
                return json_encode([
                    'success' => false,
                    'active' => 1
                ]);
            }
        }

        return json_encode([
            'success' => false,
            'active' => 1
        ]);
    }

    public function updateSliceXeditable(Application $app, Request $request)
    {
        // Make sure wheel is inactive before allowing people to edit it.
        $wheel    = JackpotWheel::find($request->get('wheel_id'));
        $property = $request->get('name');
        $slice_id = $request->get('pk');
        $slice    = JackpotWheelSlice::find($slice_id);
        $value    = $request->get('value');
        $awards   = $slice->awards();

        if($property == 'award_id'){

            if(!empty($awards)){
                $award_alias = $request->get("award_alias");
                $arr         = [];
                // We assume we're trying to switch a reward.
                if(!empty($award_alias)){
                    foreach($awards as $a){
                        if($a->alias == $award_alias){
                            if(empty($value)){
                                // We want to delete this award from the list.
                                continue;
                            } else {
                                // We want to replace this award with a different one.
                                $arr[] = $value;
                            }
                        } else {
                            // We do nothing about this award.
                            $arr[] = $a->id;
                        }
                    }
                }else{
                    // A new award added to an existing list.
                    $arr = array_merge(array_column($awards->toArray(), 'id'), [$value]);
                }
                $value = implode(',', $arr);
                $award = TrophyAwards::find($arr[0]);
            }else{
                // The first award.
                $award = TrophyAwards::find($value);
            }
        }

        $slice->$property = $value;

        if ($slice->save()) {
            $orderedSlices = $this->repo->getOrderedSlices($wheel);
            $filenames = $this->getWheelSliceImages($orderedSlices);

            return json_encode([
                'status'        => 1,
                'newValue'      => $request->get('value'),
                'newText'       => $award->alias ?? 'Award ids were not updated',
                'updatedSlices' => $orderedSlices,
                'imageFilename' => $filenames
            ]);
        }

        return json_encode([
            'status' => 0
        ]);

    }


    public function deleteWheel(Application $app, Request $request)
    {
        $wheel = JackpotWheel::find($request->get('wheel_id'));

        if (! $wheel->active) {
            $wheel->deleted = 1;
            $wheel ->save();
        }else {
            $app['flash']->add('danger', 'Unable to delete wheel');
            return json_encode([
                'success' => false,
                'message' => 'Unable to delete wheel'
            ]);
        }

        //$actor = UserRepository::getCurrentUser();
        //$description = "delete wheel {$wheel->name}";
        //ActionRepository::logAction('', $description, 'delete_wheel', true, $actor->id);

        $app['flash']->add('success', 'Wheel successfully deleted');
        return json_encode([
            'success' => true,
            'message' => 'Wheel successfully deleted'
        ]);

    }



    /**
     * Removes a slice from a wheel, and updates the wheel record to set the number of slices
     *
     * @param Application $app
     * @param Request $request
     * @return type
     */
    public function deleteSlice(Application $app, Request $request)
    {
        try {
            $slice = JackpotWheelSlice::find($request->get('slice_id'));
            $wheel_id = $slice->wheel_id;

            if ($slice->delete()) {

                // update wheel object
                $wheel = JackpotWheel::find($wheel_id);
                $wheel->number_of_slices --;
                $wheel->save();

                $orderedSlices = $this->repo->getOrderedSlices($wheel);
                $filenames = $this->getWheelSliceImages($orderedSlices);

                $app['flash']->add('success', 'Slice successfully removed');
                return json_encode([
                    'success' => true,
                    'message' => 'Slice successfully deleted',
                    'updatedSlices' => $orderedSlices,
                    'imageFilename' => $filenames
                ]);
                // return new RedirectResponse($app['url_generator']->generate('wheelofjackpot-update-wheel', ['wheel_id' => $wheel_id]));
            } else {
                $app['flash']->add('danger', 'Unable to remove slice');
                return json_encode([
                    'success' => false,
                    'message' => 'Unable to remove slice'
                ]);
                // return new RedirectResponse($app['url_generator']->generate('wheelofjackpot-update-wheel', ['wheel_id' => $wheel_id]));
            }
        } catch (\Exception $e) {
            $app['flash']->add('danger', 'Error removing slice');
            return json_encode([
                'success' => false,
                'message' => 'Error removing slice'
            ]);
        }
    }

    /**
     * Adds an extra slice to a wheel
     *
     * @param Application $app
     * @param Request $request
     */
    public function addSlice(Application $app, Request $request)
    {
        $wheel_id = $request->get('wheel_id');

        // get wheel object to get total number of slices
        $wheel = JackpotWheel::find($wheel_id);
        $sliceNumbers = $wheel->number_of_slices;

        $slice = new JackpotWheelSlice();
        $slice->wheel_id = $wheel_id;
        $slice->sort_order = $sliceNumbers ++; // this is to order the slice with the next slice
        if ($slice->save()) {

            // update wheel object
            $wheel = JackpotWheel::find($wheel_id);
            $wheel->number_of_slices ++;
            $wheel->save();

            $orderedSlices = $this->repo->getOrderedSlices($wheel);
            $filenames = $this->getWheelSliceImages($orderedSlices);

            $app['flash']->add('success', 'New slice added');

            return json_encode([
                success => true,
                'wheel' => $wheel,
                'slice' => $slice,
                'updatedSlices' => $orderedSlices,
                'imageFilename' => $filenames
            ]);
        }

        $breadcrumb = 'Update Wheel';

        $app['flash']->add('danger', 'Unable to add a new slice');
        return new RedirectResponse($app['url_generator']->generate('wheelofjackpots-update-wheel', [
            'wheel_id' => $wheel_id
        ]));
    }

    public function getTotalProbability(Application $app, Request $request)
    {
        $total_probability = $this->repo->getTotalProbability($request->get('wheel_id'));

        return json_encode([
            'newText' => $total_probability
        ]);
    }

    // TODO is this used??
    public function getAwardsForSelect(Application $app, Request $request)
    {
        $awards = $this->repo->getAvailableAwardsForSelectWithFilter($request->get('wheel_id')); // filter null

        return json_encode($awards);
    }

    public function getAwardsForSelectWithFilter(Application $app, Request $request)
    {
        $awards = $this->repo->getAvailableAwardsForSelectWithFilter($request->get('wheel_id'), $request->get('filter'));

        return json_encode($awards);
    }

    /**
     * Checks if a wheel can be activated
     * Returns true when total probability is exactly 1.0000 and all slices have an award
     *
     * @param Application $app
     * @param Request $request
     */
    public function canActivateWheel(Application $app, Request $request)
    {
        if ($this->repo->canActivateWheel($request->get('wheel_id'))) {
            return true;
        }
        return false;
    }

    public function getTotalContribution(Application $app, Request $request)
    {
        $total_contribution = $this->repo->getTotalContribution();

        return json_encode([
            'newText' => $total_contribution
        ]);
    }

    /**
     * Get Wheel Slice images from the first award associated to the slice.
     * In case of multiple awards we cannot show all of them so we choose to display the first one
     *
     * @param Collection of \App\Models\JackpotWheelSlice $orderedSlices
     * @param boolean $isReplay - this is a special scenario where we provide just a list of awards (we don't have the full slices collection available from the logs)
     * @return array - $filenames
     */
    public function getWheelSliceImages($orderedSlices, $isReplay = false)
    {
        $user = cu();

        if(!$isReplay) {
            $award_ids = [];
            foreach ($orderedSlices as $slice){
                $award_ids[$slice->award_id] = $slice->award_id ? explode(',',$slice->award_id)[0] : null; // get first award
            }
            // get all existing "first TrophyAward" for the slices (bulk query)
            $awards = TrophyAwards::whereIn('id', $award_ids)->get();

            // we need to keep the same order in the array while returning the data, so we need to fill slice without value with empty TrophyAward obj...
            $orderedAwards = [];
            // ... for that we do an extra foreach in PHP, cause it's faster than doing multiple queries on the previous cycle for each TrophyAward id.
            foreach ($orderedSlices as $slice){
                $alreadyFetchedFromDB = $awards->where('id', $award_ids[$slice->award_id])->first(); // Collection, no db involved
                $orderedAwards[] = $alreadyFetchedFromDB ?: new TrophyAwards(); // if doesn't exist i'll create an empty object
            }
        } else {
            $orderedAwards = $orderedSlices;
        }

        $filenames = [];

        // Cycle all the awards and check if an image is available or use the default one.
        foreach ($orderedAwards as $award){

            if ($award->alias == null  || empty(TrophyAwards::getImage($award, $user)) ){
                array_push($filenames, "/file_uploads/events/reward_placeholder.png");
            } else {
                // the following is used to use another image on the wheel which is
                // different from the award which are 2 completely different images
                if ($award->type == "jackpot"){
                    $image = "/file_uploads/wheel/".$award->alias.".png";
                    array_push($filenames,$image);
                } else {
                    array_push($filenames, TrophyAwards::getImage($award, $user));
                }
            }
        }
        return $filenames;
    }

    /**
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws \Exception
     */
    public function wheelLog(Application $app, Request $request)
    {
        $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_TODAY);

        $wheels[] = JackpotWheel::find($request->get('wheel_id'));
        $wheel_log = $this->repo->getWheelLog($request->get('wheel_id'), $date_range);

        if ($request->isXmlHttpRequest()) {
            foreach ($request->get('form') as $form_elem) {
                $request->request->set($form_elem['name'], $form_elem['value']);
            };
        }

        $breadcrumb = 'Log';

        $paginator = new PaginationHelper(
            $wheel_log,
            $request,
            ['length' => 25, 'order' => ['column' => 'created_at', 'order' => 'DESC']]
        );

        if ($request->isXmlHttpRequest()) {
            return $app->json($paginator->getPage(false));
        } else {
            $page = $paginator->getPage();
            return $app['blade']->view()
                ->make(
                    'admin.gamification.wheelofjackpots.wheellog',
                    compact('page', 'wheels', 'wheel_log', 'app', 'breadcrumb', 'date_range', 'request')
                )
                ->render();
        }
    }


    public function replayWheel(Application $app, Request $request, User $user)
    {
        $referer =  $request->headers->get('referer', '');
        $wheellog = $this->repo->getWheelLogByAction($request->get('wheel_log_id'), $user->id);

        $win_slice = $wheellog[0] -> win_segment;
        $spin_time = $wheellog[0] -> created_at;
        $wheel_slices = $wheellog[0]-> slices; // This is an array of arrays [ ['award_id', 'probability', 'sort_order'], [..]]

        $wheel_slices = json_decode($wheel_slices);

        $wheel = JackpotWheel::find($wheellog[0]->wheel_id);

        $wheel_style = phive('DBUserHandler/JpWheel')->getWheelStyle($wheel->style);

        //order wheel_slices with slice order
        usort($wheel_slices, function($a, $b) {
            return $a['2'] <=> $b['2']; // 2 means sort_order
        });

        $legacy_user = cu( $wheellog[0] -> user_id);
        $awards = [];
        foreach ($wheel_slices as $key => $slice){
            $award = TrophyAwards::find($slice[0]);
            $awards[] =  $award;
            $award_alias[] = [
                'alias' => $award['attributes']['alias'],
                'description'=> TrophyAwardsRepository::getDescriptionWithImage($award->id, $legacy_user),
            ];
        }

        // this is a special scenario where i don't have the full slice object, but only a list of the awards
        $filenames = $this->getWheelSliceImages($awards, true);
        $breadcrumb = 'Replay';

        return $app['blade']->view()
            ->make('admin.gamification.wheelofjackpots.replaywheel', compact('award_alias', 'win_slice', 'wheel_slices', 'filenames', 'spin_time', 'app', 'breadcrumb', 'wheel', 'wheel_style', 'referer', 'user'))
            ->render();
    }


    /**
     * Shows history from wheels of jackpot for the user
     *
     * @param Application $app
     * @param Request $request
     * @param User $user
     * @return mixed
     * @throws \Exception
     */
    public function wheelHistory(Application $app, Request $request, User $user)
    {
        if ($request->get('wheel_log_id')) {
            return $this->replayWheel($app, $request, $user);
        }

        if ($request->isXmlHttpRequest()) {
            foreach ($request->get('form') as $form_elem) {
                $request->request->set($form_elem['name'], $form_elem['value']);
            }
        }

        $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_LAST_30_DAYS, 'date-range-start');
        $jackpot_wheels = JackpotWheel::on(replicaDatabaseSwitcher(true))->get();
        $selected_wheel = $request->get('wheel', 'all');
        $legacy_user = cu($user->id);

        $data = $this->repo->getWheelHistory($date_range, $user, $selected_wheel);

        $page = (new PaginationHelper(
            $data,
            $request,
            [
                'length' => 1000,
                'order' => ['column' => 'created_at', 'dir' => 'DESC']
            ]
        ))->getPage(!$request->isXmlHttpRequest());

        $page_count = count($page['data']);
        for ($i = 0; $i < $page_count; $i++) {
            $wheel = JackpotWheel::on(replicaDatabaseSwitcher(true))->find($page['data'][$i]->wheel_id);
            $page['data'][$i]->name = $wheel->name;
            $page['data'][$i]->description = TrophyAwardsRepository::getDescriptionWithImage($page['data'][$i]->win_award_id, $legacy_user);
            $page['data'][$i]->link = $app['blade']->view()->make(
                'admin.gamification.wheelofjackpots.partials.replay_wheel_link', [
                    'app' => $app,
                    'wheel_id' => $page['data'][$i]->wheel_id,
                    'wheel_log_id' => $page['data'][$i]->id ,
                    'user_id' => $user->id
                ]
            )->render();
        }

        return $request->isXmlHttpRequest()
            ? $app->json($page)
            : $app['blade']->view()->make(
                'admin.gamification.wheelofjackpots.history', compact('user', 'page', 'app', 'date_range', 'jackpot_wheels', 'selected_wheel', 'legacy_user')
            )->render();
    }


}

