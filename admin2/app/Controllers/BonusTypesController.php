<?php

namespace App\Controllers;

use App\Models\Race;
use Illuminate\Support\Carbon;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use App\Extensions\Database\FManager as DB;
use Symfony\Component\HttpFoundation\Request;
use App\Classes\FormBuilder\FormBuilder;
use App\Classes\FormBuilder\Elements\ElementInterface;
use App\Models\Config;
use App\Models\BonusType;
use App\Models\BonusTypeTemplate;
use App\Repositories\BonusTypeRepository;

class BonusTypesController implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->match('/', 'App\Controllers\BonusTypesController::index')
        ->bind('bonustypes.index')
        ->before(function () use ($app) {
            if (!p('bonustypes.section')) {
                $app->abort(403);
            }
        })
        ->method('GET|POST');

        $factory->match('/new/', 'App\Controllers\BonusTypesController::newBonusType')
        ->bind('bonustypes.new')
        ->before(function () use ($app) {
            if (!p('bonustypes.new')) {
                $app->abort(403);
            }
        })
        ->method('GET|POST');

        $factory->match('/newcombo/', 'App\Controllers\BonusTypesController::newBonusTypeAndTrophyAward')
        ->bind('bonustypes.newcombo')
        ->before(function () use ($app) {
            if (!p('bonustypes.new') || !p('trophyawards.new')) {
                $app->abort(403);
            }
        })
        ->method('POST');

        $factory->match('/wizard/', 'App\Controllers\BonusTypesController::wizard')
        ->bind('bonustypes.wizard')
        ->before(function () use ($app) {
            if (!p('bonustypes.new')) {
                $app->abort(403);
            }
        })
        ->method('GET|POST');

        $factory->match('/wizardajax/', 'App\Controllers\BonusTypesController::wizardAjax')
        ->bind('bonustypes.wizardajax')
        ->before(function () use ($app) {
            if (!p('bonustypes.new')) {
                $app->abort(403);
            }
        })
        ->method('GET|POST');

        $factory->match('/search/', 'App\Controllers\BonusTypesController::searchBonusType')
        ->bind('bonustypes.search')
        ->before(function () use ($app) {
            if (!p('bonustypes.section')) {
                $app->abort(403);
            }
        });


        $factory->match('/edit/{bonustype}/', 'App\Controllers\BonusTypesController::editBonusType')
        ->convert('bonustype', $app['bonusTypeProvider'])
        ->bind('bonustypes.edit')
        ->before(function () use ($app) {
            if (!p('bonustypes.edit')) {
                $app->abort(403);
            }
        });

        $factory->match('/delete/{bonustype}/', 'App\Controllers\TournamentTemplateController::deleteBonusType')
        ->convert('bonustype', $app['bonusTypeProvider'])
        ->bind('bonustypes.delete')
        ->before(function () use ($app) {
            if (!p('tournamenttemplates.delete')) {
                $app->abort(403);
            }
        });

        return $factory;
    }

    public function index(Application $app, Request $request)
    {
        $repo    = new BonusTypeRepository($app);
        $columns = $repo->getBonusTypeSearchColumnsList();

        if (!isset($_COOKIE['bonustypes-search-no-visible'])) {
            foreach (array_keys($columns['list']) as $k) {
                if (!in_array($k, $columns['default_visibility'])) {
                    $columns['no_visible'][] = "col-$k";
                }
            }
            setcookie('bonustypes-search-no-visible', json_encode($columns['no_visible']));
            $_COOKIE['bonustypes-search-no-visible'] = json_encode($columns['no_visible']);
        } else {
            $columns['no_visible'] = json_decode($_COOKIE['bonustypes-search-no-visible'], true);
        }

        $res = $this->getBonusTypeList($request, $app, [
            'ajax'         => false,
            'length'       => 25,
            'sendtobrowse' => $request->get('sendtobrowse', 0),
        ]);

        $pagination = [
            'data'           => $res['data'],
            'defer_option'   => $res['recordsTotal'],
            'initial_length' => 25
        ];

        $breadcrumb = "List and Search";

        $view = ["new" => "Bonus Type", 'title' => 'Bonus Types', 'variable' => 'bonustypes', 'variable_param' => 'bonustype'];

        return $app['blade']->view()->make('admin.gamification.bonustypes.index', compact('app', 'columns', 'pagination', 'breadcrumb', 'view'))->render();

        /*
        //$promotionsController = new Messaging\PromotionsTemplateController();
        //return $promotionsController->createBonusTemplate($app, $request);

        $data = BonusType::orderBy('id', 'asc')->get();
        //return $app['blade']->view()->make('admin.messaging.bonus.create', compact('app', 'data'))->render();

        return $app['blade']->view()->make('admin.promotions.bonustypes.index', compact('app', 'data'))->render();
        */
    }
    public function newBonusType(Application $app, Request $request)
    {
        if ($request->getMethod() == 'POST') {
            $data = $request->request->all();
            $this->fixBonusTypeData($data);

            $new_bonustype = $this->createBonusTypes($app, $data);

            // NOTE: If multiples bonus types are created (because of multiple rewards), load the view of the last one.
            return $app->json(['success' => true, 'bonustype' => $new_bonustype]);
        }

        $all_distinct = $this->getAllDistinct();

        $columns_order = $this->getColumnsOrder();

        $buttons['save'] = "Create New Bonus Type";

        $breadcrumb = 'New';

        return $app['blade']->view()->make('admin.gamification.bonustypes.new', compact('app', 'buttons', 'columns_order', 'all_distinct', 'breadcrumb'))->render();
    }

    public function newBonusTypeAndTrophyAward(Application $app, Request $request)
    {
        if ($request->getMethod() == 'POST') {
            $data = $request->request->all();
            foreach ($data['bonustype_form'] as $object) {
                $data['bonustype'][$object['name']] = $object['value'];
            }
            foreach ($data['trophyaward_form'] as $object) {
                $data['trophyaward'][$object['name']] = $object['value'];
            }

            $this->fixBonusTypeData($data['bonustype']);

            $new_bonustype = new BonusType($data['bonustype']);
            if (!$new_bonustype->validate()) {
                return $app->json(['success' => false, 'attribute_errors' => $new_bonustype->getErrors(), 'bonustype' => $new_bonustype]);
            }

            DB::shBeginTransaction(true);
            if ($new_bonustype->save()) {
                $new_bonustype->storeChanges('add', $new_bonustype);

                $data['trophyaward']['bonus_id'] = $new_bonustype['id'];
                $trophy_award_controller = new \App\Controllers\TrophyAwardsController();
                $trophy_award_controller->fixTrophyAwardData($data['trophyaward']);

                $new_trophyaward = new \App\Models\TrophyAwards($data['trophyaward']);
                if (!$new_trophyaward->validate()) {
                    return $app->json(['success' => false, 'attribute_errors' => $new_trophyaward->getErrors(), 'bonustype' => $new_bonustype, 'trophyaward' => $new_trophyaward]);
                }

                if ($new_trophyaward->save()) {
                    DB::shCommit(true);
                    return $app->json(['success' => true, 'bonustype' => $new_bonustype, 'trophyaward' => $new_trophyaward]);
                }

                return $app->json(['success' => false, 'error' => 'Unable to create a Trophy Award.', 'trophyaward' => $new_trophyaward]);
            }
            if (!$new_bonustype) {
                DB::shRollback(true);
                return $app->json(['success' => false, 'error' => "Unable to create Bonus Type.", 'bonustype' => $new_bonustype]);
            }

            DB::shRollback(true);
            return $app->json(['success' => false, 'error' => 'Unable to create new Bonus Type and Trophy Award.']);
        }
    }


    /*
    private function getConfigWizardDataByConfigTag($config_tag, $parts) {
        $bonustypes_wizard = [];

        $config_bonustypes_wizard = Config::where("config_tag", $config_tag)->get();
        foreach ($config_bonustypes_wizard as $c) {
            $bonustypes_wizard[$c['config_name']] = explode(",", $c['config_value']);
            foreach ($bonustypes_wizard[$c['config_name']] as $index => $data) {
                $bonustypes_wizard[$c['config_name']][$index] = array_combine($parts, explode("::", $data));
            }
        }

        return $bonustypes_wizard;
    }
    */

    private function getConfigWizardDataByConfigName($config_name = "") {
        $bonustypes_wizard = [];
        $config_bonustypes_wizard = [];

        if (empty($config_name)) {
            $config_bonustypes_wizard = Config::where("config_tag", 'LIKE', "bonus-types-wizard%")->get();
        } else {
            $config_bonustypes_wizard = Config::where("config_name", $config_name)->get();
        }
        foreach ($config_bonustypes_wizard as $c) {
            $bonustypes_wizard[$c['config_name']][$c['config_tag']] = explode("|", $c['config_value']);
            foreach ($bonustypes_wizard[$c['config_name']][$c['config_tag']] as $index => $data) {
                $parts = explode("::", $data);
                if (count($parts) == 2) {
                    $bonustypes_wizard[$c['config_name']][$c['config_tag']][$index] = array_combine(['name', 'type'], $parts);
                } else if (count($parts) == 3) {
                    $bonustypes_wizard[$c['config_name']][$c['config_tag']][$index] = array_combine(['name', 'visibility', 'value'], $parts);
                } else if (count($parts) == 4) {
                    $bonustypes_wizard[$c['config_name']][$c['config_tag']][$index] = array_combine(['name', 'visibility', 'group', 'value'], $parts);
                }
            }
        }

        return $bonustypes_wizard;
    }

    public function wizardAjax(Application $app, Request $request)
    {
        $bonustype = $request->get('bonustype');

        $bonustype_wizard_data = $this->getConfigWizardDataByConfigName($bonustype);

        /*
        echo "<pre>";
        print_r($bonustype_wizard_data[$bonustype]);
        echo "</pre>";
        */

        foreach ($bonustype_wizard_data[$bonustype]['bonus-types-wizard-static-defaults'] as $data) {
            $bonustype_wizard_data[$bonustype]['bonus-types-wizard-defaults-unique'][] = $data['name'];
        }
        $bonustype_wizard_data[$bonustype]['bonus-types-wizard-defaults-unique'] = array_unique($bonustype_wizard_data[$bonustype]['bonus-types-wizard-defaults-unique']);

        foreach ($bonustype_wizard_data[$bonustype]['bonus-types-wizard-static-defaults'] as $data) {
            if (!empty($data['group'])) {
                $bonustype_wizard_data[$bonustype]['bonus-types-wizard-groups'][] = $data['group'];
            }
        }
        $bonustype_wizard_data[$bonustype]['bonus-types-wizard-groups'] = array_unique($bonustype_wizard_data[$bonustype]['bonus-types-wizard-groups']);


        return $app['blade']->view()->make('admin.gamification.bonustypes.wizard-form', compact('app', 'data', 'bonustype', 'bonustype_wizard_data'))->render();
    }

    public function wizard(Application $app, Request $request)
    {
        $config_bonustypes_types = Config::where("config_tag", 'bonus-types-wizard-types')->first();

        if (strlen($config_bonustypes_types['config_value']) > 0) {
            $bonustype_wizard_types = explode(",", $config_bonustypes_types['config_value']);
        }

        $wizard_data = $this->getConfigWizardDataByConfigName();

        return $app['blade']->view()->make('admin.gamification.bonustypes.wizard', compact('app', 'bonustype_wizard_types', 'wizard_data'))->render();
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param array $attributes
     * @return array
     */
    private function getBonusTypeList($request, $app, $attributes)
    {
        $repo           = new BonusTypeRepository($app);
        $search_query   = null;
        $archived_count = 0;
        $total_records  = 0;
        $length         = 25;
        $order_column   = "bonus_name";
        $start          = 0;
        $order_dir      = "ASC";

        if ($attributes['sendtobrowse'] != 1) {
            $search_query = $repo->getBonusTypeSearchQuery($request);
        } else {
            $search_query = $repo->getBonusTypeSearchQuery($request, false, $attributes['users_list']);
        }

        // Search column-wise too.
        foreach($request->get('columns') as $value) {
            if (strlen($value['search']['value']) > 0) {
                $words = explode(" ", $value['search']['value']);
                foreach($words as $word) {
                    $search_query->where($value['data'], 'LIKE', "%".$word."%");
                }
            }
        }

        $search = $request->get('search')['value'];
        if (strlen($search) > 0) {
            $s = explode(' ', $search);
            foreach ($s as $q) {
                $search_query->where('bonus_name', 'LIKE', "%$q%");
                $search_query->orWhere('bonus_type', 'LIKE', "%$q%");
            }
        }

        $non_archived_count = DB::table(DB::raw("({$search_query->toSql()}) as a"))
            ->mergeBindings($search_query)
            ->count();

        if ($attributes['sendtobrowse'] != 1 && $app['vs.config']['archive.db.support'] && $repo->not_use_archived == false) {
            $archived_search_query = $repo->getBonusTypeSearchQuery($request, true);
            try {
                $archived_count = DB::connection('videoslots_archived')->table(DB::raw("({$archived_search_query->toSql()}) as b"))
                    ->mergeBindings($search_query)
                    ->count();
            } catch (\Exception $e) {
            }
            $total_records = $non_archived_count + $archived_count;
        } else {
            $total_records = $non_archived_count;
        }

        if ($attributes['ajax'] == true) {
            $start        = $request->get('start');
            $length       = $request->get('length');
            $order        = $request->get('order')[0];
            $order_column = $request->get('columns')[$order['column']]['data'];
            $order_dir    = $order['dir'];
        } else {
            $length = $total_records < $attributes['length'] ? $total_records : $attributes['length'] ;
        }

        if ($attributes['sendtobrowse'] !== 1 && $app['vs.config']['archive.db.support'] && $archived_count > 0) {
            $non_archived_records     = $search_query->orderBy($order_column, $order_dir)->limit($length)->skip($start)->get();
            $non_archived_slice_count = count($non_archived_records);
            if ($non_archived_slice_count < $length) {
                $next_length = $length - $non_archived_slice_count;
                $next_start  = $start - $non_archived_count;
                if ($next_start < 0) {
                    $next_start = 0;
                }
                $archived_records = $archived_search_query->orderBy($order_column, $order_dir)->limit($next_length)->skip($next_start)->get();
                if ($non_archived_slice_count > 0) {
                    $data = array_merge($non_archived_records, $archived_records);
                } else {
                    $data = $archived_records;
                }
            } else {
                $data = $non_archived_records;
            }
        } else {
            $data = $search_query->orderBy($order_column, $order_dir)->limit($length)->skip($start)->get();
        }

        return [
            "draw"            => intval($request->get('draw')),
            "recordsTotal"    => intval($total_records),
            "recordsFiltered" => intval($total_records),
            "data"            => $data
        ];
    }


    /**
    * @param Application $app
    * @param Request $request
    */
    public function searchBonusType(Application $app, Request $request)
    {
        return $app->json($this->getBonusTypeList($request, $app, ['ajax' => true]));
    }


    private function fixBonusTypeData(&$bonustype_data)
    {
        if (isset($bonustype_data['ext_ids'])) {
            $bonustype_data['ext_ids'] = join("|", $bonustype_data['ext_ids']);
        } else {
            $bonustype_data['ext_ids'] = "";
        }
        if (!empty($bonustype_data['ext_ids_override']) && in_array($bonustype_data['bonus_tag'], ['microgaming', 'evolution'])) {
            $bonustype_data['ext_ids'] = $bonustype_data['ext_ids_override'];
        }

        if (isset($bonustype_data['game_id'])) {
            $bonustype_data['game_id'] = join("|", $bonustype_data['game_id']);
        } else {
            $bonustype_data['game_id'] = "";
        }

        if(isset($bonustype_data['reward']) ){
            $bonustype_data['reward'] = array_map('trim', explode(',', $bonustype_data['reward']));
        }

        $bonustype_data['forfeit_bonus'] = isset($bonustype_data['forfeit_bonus']) ? 1 : 0;
        $bonustype_data['allow_xp_calc'] = isset($bonustype_data['allow_xp_calc']) ? 1 : 0;
        $bonustype_data['deposit_active_bonus'] = (isset($bonustype_data['deposit_active_bonus'])) ? 1 : 0;
        $bonustype_data['auto_activate_bonus_id'] = $bonustype_data['auto_activate_bonus_id'] !== '' ? $bonustype_data['auto_activate_bonus_id'] : null;
        $bonustype_data['auto_activate_bonus_day'] = $bonustype_data['auto_activate_bonus_day'] !== '' ? $bonustype_data['auto_activate_bonus_day'] : null;
        $bonustype_data['auto_activate_bonus_period'] = $bonustype_data['auto_activate_bonus_period'] !== '' ? $bonustype_data['auto_activate_bonus_period'] : null;
        $bonustype_data['auto_activate_bonus_send_out_time'] = $bonustype_data['auto_activate_bonus_send_out_time'] !== '' ? $bonustype_data['auto_activate_bonus_send_out_time'] : null;
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param BonusType $bonustype
     */
    public function editBonusType(Application $app, Request $request, BonusType $bonustype)
    {

		if (!$bonustype) {
            return $app->json(['success' => false, 'Bonus Type not found.']);
        }

        if ($request->getMethod() == 'POST') {

            $data = $request->request->all();
            $t = new BonusType($data);
            if (!$t->validate()) {
                return $app->json(['success' => false, 'attribute_errors' => $t->getErrors()]);
            }

            DB::beginTransaction();
            try {
                // TODO: Should it be allowed to clear the game_ref?
                // Special treatment for game_ref, as if this is cleared in the form,
                // it's not showing up in the data, and thus not "clearing" it.
                // Doing that here then.
                /*
                if (!isset($data['game_ref'])) {
                    $data['game_ref'] = "";
                }
                */

                $this->fixBonusTypeData($data);

                $old_bonus_type = $bonustype->replicate();
                $old_bonus_type->id = $bonustype->id;

                // when creating bonus types we have an array, in edit we only have one value in an array
                if (!empty($data['reward']) && is_array($data['reward'])) {
                    $data['reward'] = $data['reward'][0];
                }

                if ($bonustype->update($data)) {
                    $bonustype->storeChanges('edit', $bonustype, $old_bonus_type);
                }
            } catch (\Exception $e) {
                DB::rollback();
                return $app->json(['success' => false, 'error' => $e]);
            }
            DB::commit();
            return $app->json(['success' => true]);
        }

        $all_distinct = $this->getAllDistinct();

        $columns_order = $this->getColumnsOrder();

        /*
        $game_repo = new GameRepository();
        $game      = $game_repo->getGameByExtGameName($bonustype->game_ref);
        */

        $buttons['save']        = "Save";
        $buttons['save-as-new'] = "Save As New...";
        //$buttons['delete']      = "Delete";

        $breadcrumb = 'Edit';

        return $app['blade']->view()->make('admin.gamification.bonustypes.edit', compact('app', 'buttons', 'columns_order', 'bonustype', 'all_distinct', 'breadcrumb'))->render();
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param BonusType $bonustype
     */
    public function deleteBonusType(Application $app, Request $request, BonusType $bonustype)
    {
        // TODO: Make sure user permision check works if you ever enable this.
        return $app->json(['success' => false, 'error' => 'Delete is disabled.']);

        /* // Disabled delete for now.
        DB::beginTransaction();
        try {
            $result = $bonustype->delete();
            if (!$result) {
                DB::rollBack();
                return $app->json(['success' => false]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $app->json(['success' => false, 'error' => $e]);
        }

        DB::commit();
        return $app->json(['success' => true]);
        */
    }

    private function getAllDistinct()
    {
        $bonustype                     = new BonusType();
		$all_distinct['bonus_type']    = $bonustype->getDistinct('bonus_type');
        $all_distinct['progress_type'] = $bonustype->getDistinct('progress_type');
        $all_distinct['type']          = $bonustype->getDistinct('type');
        $daysOfWeek = collect(range(0, 6))->map(fn($day) => [
            'key' => $day,
            'name' => Carbon::create(2025)->startOfWeek(Carbon::SUNDAY)->addDays($day)->format('l')
        ])->toArray();

        $all_distinct['auto_activate_bonus_day'] = array_merge([
            ['key' => '', 'name' => 'Not Selected']
        ], $daysOfWeek);

		return $all_distinct;
    }

    private function getColumnsOrder()
    {
        $columns_order = [
            ['column' => 'expire_time', 'type' => 'date', 'tooltip' => "The time at which point the bonus stops being considered. <b>Applies to bust bonuses and deposit bonuses.</b>"],
            ['column' => 'num_days', 'tooltip' => "Controls when a bonus entry should expire, it is the current date when the bonus is activated + num days."],
            ['column' => 'cost', 'tooltip' => "This is the amount that is to be turned over (in cents)."],
            ['column' => 'reward', 'type' => 'text', 'tooltip' => "The amount of money (in cents) to be payed out. Also the amount of money the bonus balance starts with. In case of non-bust bonuses the reward <b>will in the end be the bonus balance</b> which might be bigger or smaller than the actual reward value.<br><br> <b>NOTE: Multiple values will create one bonus type for each value</b>, updating the bonus type name for consistency"],
            ['column' => 'bonus_name', 'tooltip' => "The name of the bonus, is used in various places as a label."],
            ['column' => 'deposit_limit', 'tooltip' => 'In effect the <b>Max Payout</b>. Controls how much money (in cents) the up to value will be in "50% up to 100", if it is a <b>deposit bonus</b>.'],
            ['column' => 'rake_percent', 'tooltip' => "Controls the amount of money that needs to be turned over for <b>deposit bonuses</b>. For example: 300 will result in a cost value of 300 if the <b>reward</b> ends up being 100."],
            ['column' => 'bonus_code', 'tooltip' => "controls if the bonus should apply to a given player by way of which affiliate the user is tagged to, applies to deposit and bust bonuses."],
            ['column' => 'bonus_type', 'type' => 'select2', 'tooltip' => 'Controls if the bonus should apply to a given player by way of which affiliate the user is tagged to. <b>Applies to deposit and bust bonuses</b>.'],
            ['column' => 'exclusive', 'tooltip' => "A bonus can not be added if there already is one exlusive bonus active. <b>This value needs to be set to 0 or 2 to avoid exclusivity!</b> Avalue of 2 makes it possible to add and active the bonus again and again if prior entries have failed or has been approved. A <b>value of 3</b> will enable the bonus to exist at the same time as exclusives, but it can't be reactivated."],
            ['column' => 'bonus_tag', 'tooltip' => "If freespin bonus set the network name here, <b>bsg</b>, <b>microgaming</b> or <b>netent</b>."],
            ['column' => 'type', 'type' => 'select2', 'tooltip' => 'Defaults to Casino.'],
            ['column' => 'game_tags', 'tooltip' => "Controls which games (by way of the game tag) the bonus applies to, game play on other type will not affect the bonus <b>leave empty for all games</b>. Game reference names can also be mixed with game tags or used standalone of course, use for instance videoslots,mgs_americanroulette to allow the videoslots category and the American Roulette game."],
            ['column' => 'cash_percentage', 'tooltip' => "<b>Only makes sense when creating a bonus balance bonus</b>. Controls how much of the turnover needs to be through actual cash play and not with bonus money. Set to for instance 50 for 50%."],
            ['column' => 'max_payout', 'tooltip' => "Controls how much money should be rewarded (in cents) when the bonus is completed, if set to zero (default) the bonus balance will be rewarded, if non-zero then this value will be rewarded if the bonus balance is bigger than this value."],
            ['column' => 'reload_code', 'tooltip' => "This code needs to be entered properly by the player during the deposit process, if the entered code matches the reload code the reload bonus will be activated and work in the same way as a deposit bonus."],
            ['column' => 'excluded_countries', 'tooltip' => "Enter for instance se pl uk. That is the 2 letter iso code with a space between each country code to block people from those countries to <b>redeem vouchers connected to this bonus, does not work on deposit bonuses atm!</b>"],
            ['column' => 'deposit_amount', 'tooltip' => "If not empty it will be a requirement that the player should have deposited this amount of cents to be able to activate the bonus through a voucher."],
            ['column' => 'deposit_max_bet_percent', 'tooltip' => "If not empty is max bet as a percentage of deposit (if depositbonus). If set to 0.1 and the deposit is 100 and a bet of 11 is registered the bonus will fail."],
            ['column' => 'bonus_max_bet_percent', 'tooltip' => "If not empty is max bet as a percentage of the bonus reward. If set to 0.1 and the reward is 100 and a bet of 11 is registered the bonus will fail."],
            ['column' => 'max_bet_amount', 'tooltip' => "If not empty it is the absolute amount in cents that can happen when the bonus is active, a bet higher than that will result in a failed bonus."],
            ['column' => 'fail_limit'],
            ['column' => 'included_countries'],
            ['column' => 'game_percents', 'tooltip' => 'Has to correspond to Game tags, each tag needs a percent value, if Game tags is set to "videoslots,blackjack" then this one needs to be set to "1,0.1" for videoslots games to generate 100% turnover towards the Cost and blackjack games 10%.'],
            ['column' => 'loyalty_percent', 'tooltip' => "Set to for instance 0.5 if you want the bonus to generate 50% of the wager turnover towards the normal cashback, result: X wager * 0.5 * 0.01 = actual cashback."],
            ['column' => 'top_up', 'tooltip' => "Applies to reload bonuses, if set to bigger than 0 it will increase the player's cash balance with the amount in cents (generates a cash transaction type 14), it will not affect the bonus in any other way."],
            ['column' => 'stagger_percent', 'tooltip' => "Applies to casino wager bonuses. When set to for instance 0.1 then when 10% of the revenue (cost) goal has been reached 10% of the reward is paid out and so on. Set to 0 for a one time full payout of the reward when the turnover goal has been reached."],
            ['column' => 'ext_ids', 'type' => 'select2_multiselect', 'split_with' => "|"],
            ['column' => 'ext_ids_override', 'type' => 'modal', 'tooltip' => "If not empty, this value overrides the selected value in Ext Ids. To use in cases where ext_id doesn't match the ext_game_name (p.e: Microgaming)"],
            ['column' => 'progress_type', 'type' => 'select2', 'tooltip' => 'Default is both, if set to cash then the bonus will only progress when real money is turned over, if set to <b>bonus</b> then it will only progress when bonus money is being turned over.'],
            ['column' => 'deposit_threshold'],
            ['column' => 'game_id', 'type' => 'select2_multiselect', 'split_with' => "|"],
            ['column' => 'allow_race', 'tooltip' => "Set to 0 (default) to disallow or 1 to allow progress to happen in a casino race while this bonus is active, note that progress doesn't happen if 2 bonuses are active at the same time and one of them has allow set to 0. <b>This only works with the new realtime casino race logic!</b>"],
            ['column' => 'forfeit_bonus', 'type' => 'boolean', 'checked' => 1, 'tooltip' => 'Yes if forfeit button is enabled(default), No if disabled'],
            ['column' => 'deposit_active_bonus', 'type' => 'boolean', 'tooltip' => 'If active, user will be required to forfeit bonus before depositing', 'on_label' => 'On', 'off_label' => 'Off'],
            ['column' => 'frb_coins', 'tooltip' => "Default 1"],
            ['column' => 'frb_denomination', 'tooltip' => "Default 0.01"],
            ['column' => 'frb_lines', 'tooltip' => "Default 15. Are currently only applicable to PlaynGo freespin bonuses, they need to be there."],
            ['column' => 'frb_cost'],
            ['column' => 'award_id'],
            ['column' => 'keep_winnings'],
            ['column' => 'deposit_multiplier'],
			['column' => 'auto_activate_bonus_id', 'name' => 'On Activation of'],
			['column' => 'auto_activate_bonus_day', 'type' => 'select2', 'name' => 'Day rewarded'],
			['column' => 'auto_activate_bonus_period', 'name' => 'Period'],
            ['column' => 'allow_xp_calc', 'type' => 'boolean_on_off', 'tooltip' => 'Calculate XP points if user has this bonus as active - if button is enabled, No if disabled(default)']
        ];

        $column_data = BonusType::getColumnsData();

        foreach ($columns_order as $index => $c) {
            $data = null;
            if (is_array($c)) {
                $data = $c;
                if (!isset($data['type'])) {
                    $data['type'] = $column_data[$data['column']]['type_simple'];
                }
            } else {
                $data = ['column' => $c, 'type' => $column_data[$c]['type_simple']];
            }

            $columns_order[$index] = $data;
        }
        return $columns_order;
    }

    private function createBonusTypes(Application $app, array $data)
    {
        $reward_data = $data["reward"];
        $new_bonustype = null;

        foreach ($reward_data as $reward) {

            $data["reward"] = $reward;
            $data["bonus_name"] = $this->updateBonusTypeName($data["bonus_name"], $reward);

            $new_bonustype = new BonusType($data);

            if (!$new_bonustype->validate()) {
                return $app->json(['success' => false, 'attribute_errors' => $new_bonustype->getErrors()]);
            }

            if (!$new_bonustype->save()) {
                return $app->json(['success' => false, 'error' => 'Unable to save new Bonus Type.']);
            }

            $new_bonustype->storeChanges('add', $new_bonustype);
        }

        return $new_bonustype;
    }

    private function updateBonusTypeName(string $bonusTypeName, int $newValue)
    {
        return preg_replace_callback('/\b\d+\s+Free Spins\b/', function($matches) use ($newValue) {
            return $newValue . ' Free Spins';
        }, $bonusTypeName);
    }
}
