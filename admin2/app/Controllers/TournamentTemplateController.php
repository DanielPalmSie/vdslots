<?php

/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.17.
 * Time: 9:29
 */

namespace App\Controllers;

use App\Helpers\DataFormatHelper;
use App\Models\TournamentTemplate;
use App\Models\TournamentLadder;
use App\Models\TournamentAwardLadder;
use App\Repositories\ActionRepository;
use App\Repositories\TournamentTemplateRepository;
use App\Repositories\TrophyAwardsRepository;
use Silex\Application;
use App\Repositories\GameRepository;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Extensions\Database\FManager as DB;

class TournamentTemplateController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/', 'App\Controllers\TournamentTemplateController::index')
            ->bind('tournamenttemplates.index')
            ->before(function () use ($app) {
                if (!p('tournamenttemplates.section')) {
                    $app->abort(403);
                }
            });

        $factory->match('/new/', 'App\Controllers\TournamentTemplateController::newTournamentTemplate')
            ->bind('tournamenttemplates.new')
            ->before(function () use ($app) {
                if (!p('tournamenttemplates.new')) {
                    $app->abort(403);
                }
            });

        $factory->match('/edit/{tournament_template}/', 'App\Controllers\TournamentTemplateController::editTournamentTemplate')
            ->convert('tournament_template', $app['tournamentTemplateProvider'])
            ->bind('tournamenttemplates.edit')
            ->before(function () use ($app) {
                if (!p('tournamenttemplates.edit')) {
                    $app->abort(403);
                }
            });

        $factory->match('/delete/{tournament_template}/', 'App\Controllers\TournamentTemplateController::deleteTournamentTemplate')
            ->convert('tournament_template', $app['tournamentTemplateProvider'])
            ->bind('tournamenttemplates.delete')
            ->before(function () use ($app) {
                if (!p('tournamenttemplates.delete')) {
                    $app->abort(403);
                }
            });

        $factory->match('/search/', 'App\Controllers\TournamentTemplateController::searchTournamentTemplate')
            ->bind('tournamenttemplates.search')
            ->before(function () use ($app) {
                if (!p('tournamenttemplates.section')) {
                    $app->abort(403);
                }
            });

        $factory->match('/file-upload/', 'App\Controllers\TournamentTemplateController::fileUpload')
            ->bind('tournamenttemplates.fileupload')
            ->before(function () use ($app) {
                if (!p('tournamenttemplates.fileupload')) {
                    $app->abort(403);
                }
            });


        $factory->match('/tournamenttemplate-example/', 'App\Controllers\TournamentTemplateController::tournamentTemplateExample')
            ->bind('tournamenttemplates.getexample')
            ->before(function () use ($app) {
                if (!p('tournamenttemplates.section')) {
                    $app->abort(403);
                }
            });


        $factory->get('/filterAwardLadderTag/', 'App\Controllers\TournamentTemplateController::filterAwardLadderTag')
            ->bind('tournamenttemplates.filterAwardLadderTag')
            ->before(function () use ($app) {
                if (!p('tournamenttemplates.section')) {
                    $app->abort(403);
                }
            });


        $factory->get('/ajaxAwardLadder/', 'App\Controllers\TournamentTemplateController::ajaxAwardladder')
            ->bind('tournamenttemplates.ajaxAwardLadder')
            ->before(function () use ($app) {
                if (!p('tournamenttemplates.section')) {
                    $app->abort(403);
                }
            });

        $factory->match('/clear-queue/', 'App\Controllers\TournamentTemplateController::clearQueue')
                ->bind('tournamenttemplates.clearQueue')
                ->before(function () use ($app) {
                    if (!p('tournamenttemplates.section')) {
                        $app->abort(403);
                    }
                });


        return $factory;
    }

    public function clearQueue(Application $app, Request $request){
        $tpl = TournamentTemplate::find($request->request->get('tpl_id'));
        if(empty($tpl)){
            return 'Template could not be found.';
        }
        $tpl = $tpl->toArray();
        if(empty($tpl['queue'])){
            return 'The template is not queued so did nothing.';
        }
        phive('Tournament')->purgeTemplateQueue($tpl);
        return "Template queue {$tpl['queue']} was cleared.";
    }
    
    /**
     * Response for AJAX filtering requests. On the client side it needs to be polished a little bit because
     * select2 accepts this format: ["results": {"id": 32, "text": "sometext"}, .....]
     *
     * @param Application $app
     * @param Request $request
     * @return mixed
     */
    public function filterAwardLadderTag(Application $app, Request $request)
    {
        return TournamentAwardLadder::distinct()
            ->select(['id', 'tag'])
            ->where('tag', 'LIKE', '%'.$request->query->get('q').'%')
            ->groupBy('tag')
            ->get();
    }


    public function ajaxAwardLadder(Application $app, Request $request)
    {
        // TODO: Use the models for this?
        $ladders = DB::table('tournament_award_ladder')->
            where('tournament_award_ladder.tag', $request->query->get('tag'))->
            join('trophy_awards', 'tournament_award_ladder.award_id', '=', 'trophy_awards.id')->
            orderby('tournament_award_ladder.end_spot', 'asc')->
            get();

        $ordinal = function($number) {
            $ends = array('th','st','nd','rd','th','th','th','th','th','th');
            if ((($number % 100) >= 11) && (($number%100) <= 13)) {
                return $number . 'th';
            }
            else {
                return $number . $ends[$number % 10];
            }
        };

        return $app['blade']->view()->make('admin.gamification.tournamenttemplates.partials.awardladder', compact('app', 'ladders', 'ordinal'))->render();
    }

    /**
     * @param Application $app
     */
    public function index(Application $app, Request $request, $users_list = null)
    {
        $repo    = new TournamentTemplateRepository($app);
        $columns = $repo->getTournamentTemplateSearchColumnsList();

        if (!isset($_COOKIE['tournamenttemplates-search-no-visible'])) {
            foreach (array_keys($columns['list']) as $k) {
                if (!in_array($k, $columns['default_visibility'])) {
                    $columns['no_visible'][] = "col-$k";
                }
            }
            setcookie('tournamenttemplates-search-no-visible', json_encode($columns['no_visible']));
            $_COOKIE['tournamenttemplates-search-no-visible'] = json_encode($columns['no_visible']);
        } else {
            $columns['no_visible'] = json_decode($_COOKIE['tournamenttemplates-search-no-visible'], true);
        }

        $res = $this->getTournamentTemplateList($request, $app, [
            'ajax'         => false,
            'length'       => 25,
            'sendtobrowse' => $request->get('sendtobrowse', 0),
            'users_list'   => $users_list
        ]);

        $pagination = [
            'data'           => $res['data'],
            'defer_option'   => $res['recordsTotal'],
            'initial_length' => 25
        ];

        $breadcrumb = "List and Search";

        $view = ["new" => "Tournament Template", 'title' => 'Tournament Templates', 'variable' => 'tournamenttemplates', 'variable_param' => 'tournament_template'];

        return $app['blade']->view()->make('admin.gamification.tournamenttemplates.index', compact('app', 'columns', 'pagination', 'breadcrumb', 'view'))->render();
    }

    private function getAllDistinct()
    {
        $tournament_template            = new TournamentTemplate();
        $all_distinct['category']       = array_merge([""], $tournament_template->getDistinct('category'));
        $all_distinct['play_format']    = array_merge([""], $tournament_template->getDistinct('play_format'));
        $all_distinct['prize_type']     = array_unique(array_merge(["", "win-static"], $tournament_template->getDistinct('prize_type')));
        $all_distinct['start_format']   = array_merge([""], $tournament_template->getDistinct('start_format'));
        $all_distinct['win_format']     = array_merge([""], $tournament_template->getDistinct('win_format'));
        $all_distinct['mtt_recur_type'] = array_merge([""], $tournament_template->getDistinct('mtt_recur_type'));
        $all_distinct['reg_lim_excluded_countries'] = DataFormatHelper::getSelect2FormattedData(DataFormatHelper::getCountryList(), [
            "id" => 'iso',
            "text" => 'printable_name'
        ]);

        $tournament_ladder              = new TournamentLadder();
        $all_distinct['ladder_tag']     = array_merge([""], $tournament_ladder->getDistinct('tag'));

        return $all_distinct;
    }

    private function getColumnsOrder()
    {
        $columns_order = [
            ['column' => 'desktop_or_mobile', 'readonly' => true, 'type' => 'input', 'tooltip' => 'Whether this tournament will be listed on desktop or mobile devices.', 'placeholder' => 'Select a game_ref first'],
            ['column' => 'category', 'type' => 'select2', 'tooltip' => 'Currently only normal or guaranteed is possible'],
            ['column' => 'start_format', 'type' => 'select2', 'tooltip' => 'Currently mtt and sng is possible. mtt is just a scheduled tournament, sngs are not scheduled. They start when the min amount of players have registered.'],
            ['column' => 'win_format', 'type' => 'select2', 'tooltip' => 'tht (The Highest Total) or thw (The Highest Win). In case of tht the players who accumulates the highest total win sum wins, in case of thw the player who scores the highest single win wins.'],
            ['column' => 'play_format', 'type' => 'select2', 'tooltip' => 'Currently only balance and xspin. xspin is the same as balance but with a limited amount of spins. If it is an xspin, Xspin info needs to have a value.'],
            ['column' => 'total_cost', 'tooltip' => 'Total cost for the whole tournament.'],
            ['column' => 'cost', 'readonly' => true, 'tooltip' => "The amount of money in EUR cents that will be the player's play/MP balance."],
            ['column' => 'duration_minutes', 'tooltip' => 'The number of minutes an MP should last.'],
            ['column' => 'xspin_info', 'tooltip' => 'Currently the amount of spins the tournament should be limited to.'],
            'min_bet',
            'max_bet',
            ['column' => 'house_fee'],
            ['column' => 'min_players', 'tooltip' => "The minimum amount of players that need to sign up. If this target is not reached the MP will be cancelled automatically instead of started and all balances and pot money will be re-disbursed to registered players' cash balances."],
            ['column' => 'max_players', 'tooltip' => 'In case of mtt, registration will close when this number has been reached. In case of sng the MP starts right away when the number is reached, at the same time a new sng MG is created from the template.'],
            ['column' => 'rebuy_house_fee', 'tooltip' => 'In cents of the tournament currency. This value will be applied everytime someone makes a rebuy.'],
            'rebuy_cost',
            'rebuy_times',
            ['column' => 'mtt_show_hours_before', 'tooltip' => 'The number of hours before start an mtt should show at all (ie display with status upcoming).'],
            ['column' => 'mtt_start_time', 'tooltip' => 'In case of <b>mtt</b> recur type is day: 06,08,12,14,16,18,20,22. In case it is week or month: 20:30:00 for half past eight PM.'],
            ['column' => 'mtt_start_date', 'tooltip' => 'In case of a one-time MP, this is the date it starts (ex 2014-12-24 for a Christmas eve tournament), in case of a recurring MP it will be ignored.'],
            ['column' => 'mtt_reg_duration_minutes', 'tooltip' => 'The number of minutes before the start time it will be possible to register for an mtt MP.'],
            ['column' => 'mtt_late_reg_duration_minutes', 'tooltip' => 'The number of minutes after the start time it will be possible to register for an mtt MP.'],
            ['column' => 'mtt_recur_type', 'type' => 'select2', 'tooltip' => 'Can be day, week or month in case of mtt.'],
            ['column' => 'mtt_recur_days', 'help-block' => 'In case recur type is week: 1-7 (ex 5,6,7 if it is to show on Fri, Sat and Sun), in case it is month: 01-31 (ex 01,02,03 if it is to show on the first 3 days in every month), leave empty in case it is day.'],
            ['column' => 'recur', 'help-block' => "If the MP should recur, setting this to 0 will in effect pause creation of the MP in question, this field should be used to hide/remove an MP, deletion is forbidden.\n
                In case of SNG:
                0: Disables new creations of the SNG.
                1: Will cause immediate creation of a new tournament when the current one is filled.
                2: Causes the creation to happen when the current one is finished, ie the play time is up.
                3: Similar to #1 but player can't register in the same tournament until he is finished with the current one (tournament entry status is finished).
                4: Similar to #1 but player can't register in the same tournament until the current one is completely finished (tournament status is finished)."],
            ['column' => 'recur_end_date', 'tooltip' => 'When the MP should stop recurring, applies to both sng and mtt.'],
            ['column' => 'guaranteed_prize_amount', 'tooltip' => 'Amount in EUR cents, in case the total prize pool is less than this number the difference will be made up by way of transactions of type 41 to each winning player.'],
            ['column' => 'prize_type', 'type' => 'select2', 'help-block' => "Currently cash-balance, cash-fixed or win-prog.\n
                In case of win-fixed the cost will be used for the prize pool, cash balances will NOT be returned, note that a house fee needs to be applied to this type in order for the house to make any money (in case of a non-freeroll), <b>during game play the cash balance will not be increased with the winnings</b>.\n
                In case of cash-balance both the prize pot and all MP cash balances will be used to make up the total prize pool.\n
                In case of cash-fixed only the prize pot will be used and all MP balances will be returned to each player.\n
                In case of win-prog the prize pot, cash balances and win amounts will be used to make up the prize pool, <b>during game play the cash balance will not be increased with the winnings</b>."],
            ['column' => 'get_race', 'type' => 'boolean', 'tooltip' => 'Yes to get said thing, No to not get it.'],
            ['column' => 'get_loyalty', 'type' => 'boolean', 'tooltip' => 'Yes to get said thing, No to not get it.'],
            ['column' => 'get_trophy', 'type' => 'boolean', 'tooltip' => 'Yes to get said thing, No to not get it.'],
            ['column' => 'turnover_threshold', 'tooltip' => 'Is the amount of EUR cents that needs to be wagered in order to be able to receive a prize at all. '],
            ['column' => 'award_ladder_tag', 'type' => 'select2_filter', 'tooltip' => 'Is the tag for the award prize ladder to be used in case cash prizes are not used. Note that setting this value to something automatically overrides any normal ladder. '],
            'duration_rebuy_minutes',
            ['column' => 'ladder_tag', 'type' => 'select2', 'tooltip' => 'Is the tag for the cash prize ladder to be used. Note that this value is disregarded in case an Award ladder tag is set.'],
            ['column' => 'included_countries', 'tooltip' => "Are lists of ISO2 codes, ie PL BG RU. Players from non-allowed countries won't see the tournament in question in the lobby listing."],
            ['column' => 'excluded_countries', 'tooltip' => "Are lists of ISO2 codes, ie PL BG RU. Players from non-allowed countries won't see the tournament in question in the lobby listing."],
			['column' => 'blocked_provinces', 'tooltip' => "Are lists of ISO2 codes, ie PL BG RU. Players from non-allowed provinces won't see the tournament in question in the lobby listing."], // TODO check for tooltip text
			['column' => 'reg_wager_lim', 'tooltip' => 'Controls how much a player needs to have wager in EUR cents in the Reg lim period which is in days.'],
            ['column' => 'reg_dep_lim', 'tooltip' => 'Controls how much a player needs to have deposited in EUR cents in the Reg lim period which is in days.'],
            ['column' => 'reg_lim_period', 'tooltip' => 'Reg lim is the period in days used with Reg wager lim and Reg dep lim.'],
            ['column' => 'reg_lim_excluded_countries', 'type' => 'select2-multi', 'tooltip' => 'Reg lim excluded countries is used to select which countries can register without having to comply with the registration requirements.'],
            'pot_cost',
            ['column' => 'free_pot_cost', 'tooltip' => 'If there is a pot cost it will be paid for by the casino if this value is 1, if it is zero or empty it will be payed for by the player.'],
            ['column' => 'prize_calc_wait_minutes', 'tooltip' => 'Due to the possibility of players entering freespins etc just before the tournament time period is over this value can be used to delay prize calculations which enables lagging wins to arrive before prizes are handed out. Any lagging bets or rebuys will still be rejected.'],
            ['column' => 'spin_m', 'tooltip' => 'The spin multiplier, will be used to multiply the cash balance and the available spins on register and rebuy. Use only with win fixed, use the default value 1 for all other types.'],
            'pwd',
            ['column' => 'number_of_jokers', 'tooltip' => 'The number of Jokers for this tournament. Jokers will get doubled up with their winnings if any.'],
            ['column' => 'bounty_award_id', 'tooltip' => 'The award for the bounty prize for being better with exactly 1 place than the bounty guy who won the last tournament. If it is 0, bounty will be not applied to this tournament.'],
            ['column' => 'bet_levels', 'tooltip' => 'Ex: 20,40,60,100 NO spaces, just commas and cents. If empty min and max bet will be respected instead.'],
            ['column' => 'queue', 'type' => 'input', 'tooltip' => 'The queue channel name to enforce queueing, only works with SNGs, leave empty to disable.']
        ];

        $column_data = TournamentTemplate::getColumnsData();

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

    private function fixTournamentTemplateData(&$tournament_template_data)
    {
        foreach(['get_race', 'get_loyalty', 'get_trophy'] as $col){
            $tournament_template_data[$col] = isset($tournament_template_data[$col]) ? 1 : 0;
        }
        
        if (is_array($tournament_template_data['reg_lim_excluded_countries'])) {
            $tournament_template_data['reg_lim_excluded_countries'] = implode(" ", $tournament_template_data['reg_lim_excluded_countries']);
        }
    }

    /**
     * @param Application $app
     * @param Request $request
     */
    public function newTournamentTemplate(Application $app, Request $request)
    {
        if ($request->getMethod() == 'POST') {
            $new_tournament_template = null;

            $data = $request->request->all();
            $t = new TournamentTemplate($data);
            if (!$t->validate()) {
                return $app->json(['success' => false, 'attribute_errors' => $t->getErrors()]);
            }
            DB::shBeginTransaction(true);
            try {

                $this->fixTournamentTemplateData($data);

				$new_tournament_template = TournamentTemplate::create($data);

			} catch (\Exception $e) {
                DB::shRollback(true);
                return $app->json(['success' => false, 'error' => $e]);
            }
            DB::shCommit(true);
            return $app->json(['success' => true, 'tournament_template' => $new_tournament_template]);
        }

        $all_distinct = $this->getAllDistinct();

        $columns_order = $this->getColumnsOrder();

        $buttons['save'] = "Create New Tournament Template";

        $breadcrumb = 'New';

        return $app['blade']->view()->make('admin.gamification.tournamenttemplates.new', compact('app', 'buttons', 'columns_order', 'all_distinct', 'breadcrumb'))->render();
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param TournamentTemplate $tournament_template
     */
    public function editTournamentTemplate(Application $app, Request $request, TournamentTemplate $tournament_template)
    {
        if (!$tournament_template) {
            return $app->json(['success' => false, 'Tournament Template not found.']);
        }

        if ($request->getMethod() == 'POST') {

            $data = $request->request->all();
            $t = new TournamentTemplate($data);
            if (!$t->validate()) {
                return $app->json(['success' => false, 'attribute_errors' => $t->getErrors()]);
            }

            DB::shBeginTransaction(true);
            try {
                if (!isset($data['tournament_name']) || strlen($data['tournament_name']) < 3) {
                    return $app->json(['success' => false, 'error' => 'The Tournament Name is too short.']);
                }

                // TODO: Should it be allowed to clear the game_ref?
                // Special treatment for gameref, as if this is cleared in the form,
                // it's not showing up in the data, and thus not "clearing" it.
                // Doing that here then.
                if (!isset($data['game_ref'])) {
                    $data['game_ref'] = "";
                }

                $this->fixTournamentTemplateData($data);

                $tournament_template->update($data);
            } catch (\Exception $e) {
                DB::rollback();
                return $app->json(['success' => false, 'error' => $e]);
            }
            DB::shCommit(true);
            return $app->json(['success' => true]);
        }

        $all_distinct = $this->getAllDistinct();

        $columns_order = $this->getColumnsOrder();

        $game_repo = new GameRepository();
        $game      = $game_repo->getGameByExtGameName($tournament_template->game_ref);

        $buttons['save']        = "Save";
        $buttons['save-as-new'] = "Save As New...";
        //$buttons['delete']      = "Delete";

        $this->fakeTournament($tournament_template);
        $tournament_template_example = $this->getTournamentTemplateExample($tournament_template);

        $repo = new TournamentTemplateRepository($app);
        $repo->setTournamentTemplateImage($tournament_template);

        // TODO: Use the models for this?
        $ladders = DB::table('tournament_award_ladder')->
            where('tournament_award_ladder.tag', $tournament_template->award_ladder_tag)->
            join('trophy_awards', 'tournament_award_ladder.award_id', '=', 'trophy_awards.id')->
            orderby('tournament_award_ladder.end_spot', 'asc')->
            get();

        $ordinal = function($number) {
            $ends = array('th','st','nd','rd','th','th','th','th','th','th');
            if ((($number % 100) >= 11) && (($number%100) <= 13)) {
                return $number . 'th';
            }
            else {
                return $number . $ends[$number % 10];
            }
        };

        $breadcrumb = 'Edit';

        return $app['blade']->view()->make('admin.gamification.tournamenttemplates.edit', compact('app', 'buttons', 'columns_order', 'tournament_template', 'tournament_template_example', 'all_distinct', 'game', 'breadcrumb', 'ladders', 'ordinal'))->render();
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param TournamentTemplate $tournament_template
     */
    public function deleteTournamentTemplate(Application $app, Request $request, TournamentTemplate $tournament_template)
    {
        // TODO: Make sure user permision check works if you ever enable this.
        return $app->json(['success' => false, 'error' => 'Delete is disabled.']);

        /* // Disabled delete for now.
        DB::shBeginTransaction(true);
        try {
            $result = $tournament_template->delete();
            if (!$result) {
                DB::shRollback(true);
                return $app->json(['success' => false]);
            }
        } catch (\Exception $e) {
            DB::shRollback(true);
            return $app->json(['success' => false, 'error' => $e]);
        }

        DB::shCommit(true);
        return $app->json(['success' => true]);
        */
    }

    // Code here is borrowed from Tournament.php in phive.
    private function fakeTournament(&$tournament_template)
    {
        $schedule = phive('Tournament')->getMttSchedule($tournament_template, phive()->hisNow());
        $start_time = phive()->hisNow();
        if (count($schedule) > 0) {
            $start_time = $schedule[0]; // Just grab the first closest in time one.
        }

        // This is for getRegStartTime.
        if($tournament_template['start_format'] == 'mtt') {
            $tournament_template['mtt_start'] = $start_time;
            $tournament_template['status'] = 'upcoming';
        } else {
            $tournament_template['status'] = 'registration.open';
        }

        $tournament_template['start_time'] = $start_time; // This if for getStartOrStatus.
    }

    private function getTournamentTemplateExample($tournament_template)
    {
        $tournament_template_example = [];

        if (phive('Tournament')->getRegStartTime($tournament_template) !== false) {
            $tournament_template_example['registration_opens'] = phive('Tournament')->getRegStartTime($tournament_template, true);
        }
        $tournament_template_example['start']       = phive('Tournament')->getStartOrStatus($tournament_template);
        $tournament_template_example['min_players'] = $tournament_template->min_players;
        $tournament_template_example['max_players'] = $tournament_template->max_players;
        $tournament_template_example['buy_in']      = phive('Tournament')->getBuyIn($tournament_template);

        if (!empty($tournament_template->pot_cost)) {
            $tournament_template_example['pot_cost'] = '&euro; '.$tournament_template->pot_cost;
        }

        if (!empty($tournament_template->guaranteed_prize_amount)) {
            $tournament_template_example['guaranteed_prize_amount'] = '&euro; '.($tournament_template->guaranteed_prize_amount / 100);
        }

        $tournament_template_example['duration_minutes'] = $tournament_template->duration_minutes.' minutes';
        $tournament_template_example['spins'] = empty($tournament_template['xspin_info']) ? 'N/A' : phive('Tournament')->getXspinInfo($tournament_template, 'tot_spins');

        if(empty($tournament_template->bet_levels)) {
            $tournament_template_example['bet_levels'] = '&euro; '.sprintf("%.02f - &euro; %.02f", $tournament_template->min_bet/100, $tournament_template->max_bet/100);
        } else {
            $tournament_template_example['bet_levels'] = '&euro; '.implode(', ', array_map(function($num){ return $num / 100;  }, explode(',', $tournament_template->bet_levels)));
        }

        return $tournament_template_example;
    }

    /**
     * @param Application $app
     * @param Request $request
     */
    public function tournamentTemplateExample(Application $app, Request $request)
    {
        if ($request->getMethod() == 'POST') {

            $tournament_template = new TournamentTemplate($request->request->all());
            $this->fakeTournament($tournament_template);
            $tournament_template_example = $this->getTournamentTemplateExample($tournament_template);

            return $app->json(['success' => true, 'tournament_template' => $tournament_template, 'tt_example' => $tournament_template_example]);
        }

        return $app->json(['success' => false, 'error', 'Expecting a POST Template Tournament message.']);
    }

    /**
     * @param Application $app
     * @param Request $request
     */
    public function fileUpload(Application $app, Request $request)
    {
        $base_destination = phive('Filer')->getSetting('UPLOAD_PATH').'/tournaments';

        foreach ($_FILES as $file) {
            $color_file_destination = $base_destination.'/'.$file['name'];
            if(phive('UserHandler')->getSetting('send_public_files_to_dmapi')) {
                phive('Dmapi')->uploadPublicFile($file['tmp_name'], 'file_uploads', $file['name'], 'tournaments');
            } else {
                if (move_uploaded_file($file['tmp_name'], $color_file_destination)) { // This function does checks regarding uploaded file so we depend on that for the grey image.
                    chmod($color_file_destination, 0777);
                }
            }
        }

        return $app->json(['success' => true]);
    }

    /**
     * @param Request $request
     * @param Application $app
     * @param array $attributes
     * @return array
     */
    private function getTournamentTemplateList($request, $app, $attributes)
    {
        $repo           = new TournamentTemplateRepository($app);
        $search_query   = null;
        $archived_count = 0;
        $total_records  = 0;
        $length         = 25;
        $order_column   = "tournament_name";
        $start          = 0;
        $order_dir      = "ASC";

        if ($attributes['sendtobrowse'] != 1) {
            $search_query = $repo->getTournamentTemplateSearchQuery($request);
        } else {
            $search_query = $repo->getTournamentTemplateSearchQuery($request, false, $attributes['users_list']);
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
                $search_query->where('tournament_name', 'LIKE', "%$q%");
                $search_query->orWhere('game_ref', 'LIKE', "%$q%");
                $search_query->orWhere('category', 'LIKE', "%$q%");
            }
        }

        $non_archived_count = DB::table(DB::raw("({$search_query->toSql()}) as a"))
            ->mergeBindings($search_query)
            ->count();

        if ($attributes['sendtobrowse'] != 1 && $app['vs.config']['archive.db.support'] && $repo->not_use_archived == false) {
            $archived_search_query = $repo->getTournamentTemplateSearchQuery($request, true);
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
    public function searchTournamentTemplate(Application $app, Request $request)
    {
        return $app->json($this->getTournamentTemplateList($request, $app, ['ajax' => true]));
    }

    /**
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return mixed
     */
    public function listUserTournamentTemplate(Application $app, User $user, Request $request)
    {
        $repo = new TournamentTemplateRepository($app);
        $date_range = DateHelper::validateDateRange($request, 6);
        $sort = ['column' => 3, 'type' => "desc", 'start_date' => $date_range['start_date'], 'end_date' => $date_range['end_date']];

        $user_tournamenttemplate_events = $repo->getTournamentTemplateList($user, $date_range);

        //todo check this function
        //$categories = phive('TournamentTemplate')->getCategories(cu($user->getKey()), 'category', '', 'tournamenttemplate');
        $categories = $repo->getCategories();
        //$tournamenttemplates = TournamentTemplate::where('category', 'activity')->get();
        $tournamenttemplates = $repo->getPerCategory($user, 'activity');

        return $app['blade']->view()->make('admin.user.tournamenttemplates', compact('app', 'user', 'sort', 'categories', 'tournamenttemplates', 'user_tournamenttemplate_events'))->render();
    }

    public function listNotActivatedRewards(Application $app, User $user, Request $request)
    {
        $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_EMPTY);
        $repo = new TournamentTemplateRepository($app);
        $rewards_not_activated = $repo->getNotActivatedRewardsList($user, $date_range, $request);

        $sort = ['column' => 0, 'type' => "desc"];
        return $app['blade']->view()->make('admin.user.bonus.not-activated', compact('app', 'user', 'sort', 'rewards_not_activated', 'date_range'))->render();
    }

    public function listRewardHistory(Application $app, User $user, Request $request)
    {
        $date_range = DateHelper::validateDateRange($request, 8);
        $repo = new TournamentTemplateRepository($app);
        $trophyaward_repo = new TrophyAwardsRepository($app);

        $reward_history = $repo->getRewardHistoryList($user, $date_range);

        foreach ($reward_history as $index => &$reward) {
            $reward = (array)$reward; // Needs to be array for phive.
            $trophyaward_repo->setTrophyAwardImage($reward, $user);
            $reward_history[$index] = $reward;
        }

        $sort = ['column' => 2, 'type' => "desc", 'start_date' => $date_range['start_date'], 'end_date' => $date_range['end_date']];
        return $app['blade']->view()->make('admin.user.bonus.reward-history', compact('app', 'user', 'sort', 'reward_history'))->render();
    }

    /**
     * Add new tournamenttemplate to an User.
     * todo port phive functions
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return RedirectResponse
     */
    public function addTournamentTemplateToUser(Application $app, User $user, Request $request)
    {
        if (!$request->isMethod('POST')) {
            $app->abort(405);
        }

        $repo = new TournamentTemplateRepository($app);
        if (!is_numeric($request->get('tournamenttemplate-id'))) {
            $app['session']->set('flash', ['type' => 'warning', 'message' => "TournamentTemplate id is not valid."]);
            return new RedirectResponse($request->headers->get('referer'));
        }

        $legacy_user = phive('UserHandler')->getUserByUsername($user->username);
        //$ud = $legacy_user->data;
        $legacy_tournamenttemplate = phive('TournamentTemplate')->get($request->get('tournamenttemplate-id'));

        if (phive('TournamentTemplate')->awardTournamentTemplate($legacy_tournamenttemplate, $legacy_user->data) !== false) {
            ActionRepository::logAction($user, "TournamentTemplate {$legacy_tournamenttemplate['alias']} with ID {$legacy_tournamenttemplate['id']} manually added.", 'add_tournamenttemplate');
            $app['session']->set('flash', ['type' => 'success', 'message' => "TournamentTemplate successfully added to the customer."]);
        } else {
            $msg = $repo->getAddTournamentTemplateValidationMessage($user);
            $app['session']->set('flash', ['type' => 'danger', 'message' => "There was an error and the tournamenttemplate has not been added to the customer. Reason: $msg"]);
        }

        return new RedirectResponse($request->headers->get('referer'));
    }

    public function getTournamentTemplateForCategory(Application $app, User $user, Request $request, $category)
    {
        $repo = new TournamentTemplateRepository($app);
        return $repo->getPerCategory($user, $category);
    }

    public function addReward(Application $app, User $user, Request $request)
    {
        $repo = new TournamentTemplateRepository($app);

        if ($request->isMethod('POST')) {
            $res = $repo->addReward($request, $user);
            if ($res) {
                return $app->json(['success' => true, 'message' => $res]);
            } else {
                return $app->json(['success' => false, 'message' => 'Unexpected error adding the reward']);
            }
        } elseif ($request->isMethod('GET')) {
            if ($request->get('list') == 1) {
                return $repo->getRewardsByType($request->get('type'));
            }
            $rewards_types = $repo->getRewardsTypes();
            return $app['blade']->view()->make('admin.user.bonus.add-reward', compact('app', 'user', 'rewards_types'))->render();

        } else {
            return new RedirectResponse($request->headers->get('referer'));
        }
    }

    /**
     * Delete Award Entry
     * todo improve the phive exception
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return RedirectResponse
     */
    public function deleteAwardEntry(Application $app, User $user, Request $request)
    {
        try {
            phive('TournamentTemplate')->removeAward($request->get('award_id'), $user->getKey());
        } catch (\Exception $e) {
            $app->abort(500, "Phive error");
        }

        return new RedirectResponse($request->headers->get('referer'));
    }

}
