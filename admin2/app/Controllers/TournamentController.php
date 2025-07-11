<?php

namespace App\Controllers;

use App\Models\Tournament;
use App\Repositories\TournamentRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Extensions\Database\FManager as DB;
use App\Repositories\GameRepository;

class TournamentController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->get('/', 'App\Controllers\TournamentController::index')
            ->bind('tournaments.index')
            ->before(function () use ($app) {
                if (!p('tournaments.section')) {
                    $app->abort(403);
                }
            });

        $factory->match('/search/', 'App\Controllers\TournamentController::searchTournament')
            ->bind('tournaments.search')
            ->before(function () use ($app) {
                if (!p('tournaments.section')) {
                    $app->abort(403);
                }
            });

        $factory->match('/view/{tournament}/', 'App\Controllers\TournamentController::viewTournament')
            ->convert('tournament', $app['tournamentProvider'])
            ->bind('tournaments.edit')
            ->before(function () use ($app) {
                if (!p('tournaments.edit')) {
                    $app->abort(403);
                }
            });

        $factory->match('/cancel/', 'App\Controllers\TournamentController::cancelTournament')
            ->bind('tournaments.cancel')
            ->before(function () use ($app) {
                if (!p('tournaments.cancel')) {
                    $app->abort(403);
                }
            });

        $factory->match('/pause/', 'App\Controllers\TournamentController::pauseTournament')
            ->bind('tournaments.pause')
            ->before(function () use ($app) {
                if (!p('tournaments.pause')) {
                    $app->abort(403);
                }
            });

        $factory->match('/resume/', 'App\Controllers\TournamentController::resumeTournament')
            ->bind('tournaments.resume')
            ->before(function () use ($app) {
                if (!p('tournaments.resume')) {
                    $app->abort(403);
                }
            });

        $factory->match('/calc/', 'App\Controllers\TournamentController::calcPrizesOfTournament')
            ->bind('tournaments.calc')
            ->before(function () use ($app) {
                if (!p('tournaments.calc')) {
                    $app->abort(403);
                }
            });

        $factory->match('/close/', 'App\Controllers\TournamentController::closeBattle')
            ->bind('tournaments.close')
            ->before(function () use ($app) {
                if (!p('tournaments.close')) {
                    $app->abort(403);
                }
            });


        return $factory;
    }
    
    /**
     * @param Application $app
     * @param Request $request
     */
    public function index(Application $app, Request $request)
    {
        $repo    = new TournamentRepository($app);
        $columns = $repo->getTournamentSearchColumnsList();

        if (!isset($_COOKIE['tournaments-search-no-visible'])) {
            foreach (array_keys($columns['list']) as $k) {
                if (!in_array($k, $columns['default_visibility'])) {
                    $columns['no_visible'][] = "col-$k";
                }
            }
            setcookie('tournaments-search-no-visible', json_encode($columns['no_visible']));
            $_COOKIE['tournaments-search-no-visible'] = json_encode($columns['no_visible']);
        } else {
            $columns['no_visible'] = json_decode($_COOKIE['tournaments-search-no-visible'], true);
        }

        $res = $this->getTournamentList($request, $app, [
            'ajax'         => false,
            'length'       => 25,
            'sendtobrowse' => $request->get('sendtobrowse', 0)
        ]);

        $pagination = [
            'data'           => $res['data'],
            'defer_option'   => $res['recordsTotal'],
            'initial_length' => 25
        ];

        $breadcrumb = "List and Search";

        $view = ["new" => "Tournament", 'title' => 'Tournaments', 'variable' => 'tournaments', 'variable_param' => 'tournament'];

        return $app['blade']->view()->make('admin.gamification.tournaments.index', compact('app', 'columns', 'pagination', 'breadcrumb', 'view'))->render();


    }

    /**
     * Search the tournaments(tournaments.search)
     * 
     * @param Application $app
     * @param Request $request
     */
    public function searchTournament(Application $app, Request $request)
    {
        return $app->json($this->getTournamentList($request, $app, ['ajax' => true]));
    }

    /**
     * Function to get a list of available tournaments 
     * 
     * @param Request $request
     * @param Application $app
     * @param array $attributes
     * @return array
     */
    private function getTournamentList($request, $app, $attributes)
    {
        
        $repo           = new TournamentRepository($app);
        $search_query   = null;
        $archived_count = 0;
        $total_records  = 0;
        $length         = 25;
        $order_column   = "id";
        $start          = 0;
        $order_dir      = "ASC";

        $search_query = $repo->getTournamentSearchQuery($request);


        // search column-wise
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
            }
        }

        $non_archived_count = DB::table(DB::raw("({$search_query->toSql()}) as a"))
            ->mergeBindings($search_query)
            ->count();

        if ($attributes['sendtobrowse'] != 1 && $app['vs.config']['archive.db.support'] && $repo->not_use_archived == false) {
            $archived_search_query = $repo->getTournamentSearchQuery($request, true);
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
     * Function for view the tournament(tournaments.edit)
     * 
     * @param Application $app
     * @param Request $request
     * @param Tournament $tournament
     */
    public function viewTournament(Application $app, Request $request, Tournament $tournament)
    {
        if (!$tournament) {
            return $app->json(['success' => false, 'Tournament not found.']);
        }

        $columns_order = $this->getColumnsOrder();

        $game_repo = new GameRepository();
        $game      = $game_repo->getGameByExtGameName($tournament->game_ref);

        $buttons['cancel']          = "Cancel";
        $buttons['pause']           = "Pause";
        $buttons['resume']          = "Resume";
        $buttons['calc_prizes']     = "Calculate Prizes";
        $buttons['close-battle']    = "Close Battle";

        //get tournament data that are eligible for close battle requirements
        $unclosed_battles = phive('Tournament/TournamentCloseBattles')->getAllRelatedTournaments($tournament->id);

        $breadcrumb = 'View';

        return $app['blade']->view()->make('admin.gamification.tournaments.edit', compact('app', 'buttons', 'tournament', 'columns_order', 'game', 'unclosed_battles'))->render();

    }

    /**
     * Arrange the colums and order to display in the view page
     */
    function getColumnsOrder(){

        $columns_order = [
            ['column' => 'desktop_or_mobile', 'tooltip' => 'Whether this tournament will be listed on desktop or mobile devices.'],
            ['column' => 'tpl_id', 'tooltip' => 'Related tournaemnt template id',],
            ['column' => 'category', 'tooltip' => 'Currently only normal or guaranteed is possible'],
            ['column' => 'start_format', 'tooltip' => 'Currently mtt and sng is possible. mtt is just a scheduled tournament, sngs are not scheduled. They start when the min amount of players have registered.'],
            ['column' => 'win_format', 'tooltip' => 'tht (The Highest Total) or thw (The Highest Win). In case of tht the players who accumulates the highest total win sum wins, in case of thw the player who scores the highest single win wins.'],
            ['column' => 'play_format', 'tooltip' => 'Currently only balance and xspin. xspin is the same as balance but with a limited amount of spins. If it is an xspin, Xspin info needs to have a value.'],
            ['column' => 'total_cost', 'tooltip' => 'Total cost for the whole tournament.'],
            ['column' => 'cost', 'tooltip' => "The amount of money in EUR cents that will be the player's play/MP balance."],
            ['column' => 'duration_minutes','tooltip' => 'The number of minutes an MP should last.'],
            ['column' => 'xspin_info', 'tooltip' => 'Currently the amount of spins the tournament should be limited to.'],
            ['column' => 'min_bet'],
            ['column' => 'max_bet'],
            ['column' => 'house_fee'],
            ['column' => 'min_players', 'tooltip' => "The minimum amount of players that need to sign up. If this target is not reached the MP will be cancelled automatically instead of started and all balances and pot money will be re-disbursed to registered players' cash balances."],
            ['column' => 'max_players', 'tooltip' => 'In case of mtt, registration will close when this number has been reached. In case of sng the MP starts right away when the number is reached, at the same time a new sng MG is created from the template.'],
            ['column' => 'rebuy_house_fee', 'tooltip' => 'In cents of the tournament currency. This value will be applied everytime someone makes a rebuy.'],
            ['column' => 'rebuy_cost'],
            ['column' => 'rebuy_times'],
            ['column' => 'start_time', 'tooltip' => 'The actual stamp when the tournament started.'],
            ['column' => 'end_time', 'tooltip' => 'The Actual stamp when the tournament ended.'],
            ['column' => 'status', 'tooltip' => 'Current status of the tournament.'],
            ['column' => 'mtt_start', 'tooltip' => 'In case of <b>mtt</b> recur type is day: 06,08,12,14,16,18,20,22. In case it is week or month: 20:30:00 for half past eight PM.'],
            ['column' => 'mtt_reg_duration_minutes', 'tooltip' => 'The number of minutes before the start time it will be possible to register for an mtt MP.'],
            ['column' => 'mtt_late_reg_duration_minutes', 'tooltip' => 'The number of minutes after the start time it will be possible to register for an mtt MP.'],
            ['column' => 'prize_amount', 'tooltip' => 'Amount in EUR cents, the total prize amount to be distributed in accordance with the tournament_ladder in cents in the casino default currency.'],
            ['column' => 'guaranteed_prize_amount', 'tooltip' => 'Amount in EUR cents, in case the total prize pool is less than this number the difference will be made up by way of transactions of type 41 to each winning player.'],
            ['column' => 'registered_players', 'tooltip' => 'The amount of players that have registered for this tournament.'],
            ['column' => 'prize_type', 'help-block' => "Currently cash-balance, cash-fixed or win-prog.\n
                In case of win-fixed the cost will be used for the prize pool, cash balances will NOT be returned, note that a house fee needs to be applied to this type in order for the house to make any money (in case of a non-freeroll), <b>during game play the cash balance will not be increased with the winnings</b>.\n
                In case of cash-balance both the prize pot and all MP cash balances will be used to make up the total prize pool.\n
                In case of cash-fixed only the prize pot will be used and all MP balances will be returned to each player.\n
                In case of win-prog the prize pot, cash balances and win amounts will be used to make up the prize pool, <b>during game play the cash balance will not be increased with the winnings</b>."],
            ['column' => 'get_race', 'tooltip' => '1 or 0'],
            ['column' => 'get_loyalty'],
            ['column' => 'get_trophy'],
            ['column' => 'turnover_threshold', 'tooltip' => 'Is the amount of EUR cents that needs to be wagered in order to be able to receive a prize at all. '],
            ['column' => 'award_ladder_tag', 'tooltip' => 'Is the tag for the award prize ladder to be used in case cash prizes are not used. Note that setting this value to something automatically overrides any normal ladder. '],
            ['column' => 'duration_rebuy_minutes'],
            ['column' => 'ladder_tag', 'tooltip' => 'Is the tag for the cash prize ladder to be used. Note that this value is disregarded in case an Award ladder tag is set.'],
            ['column' => 'included_countries', 'tooltip' => "Are lists of ISO2 codes, ie PL BG RU. Players from non-allowed countries won't see the tournament in question in the lobby listing."],
            ['column' => 'excluded_countries', 'tooltip' => "Are lists of ISO2 codes, ie PL BG RU. Players from non-allowed countries won't see the tournament in question in the lobby listing."],
			['column' => 'blocked_provinces', 'tooltip' => "Are lists of ISO2 codes, ie PL BG RU. Players from non-allowed provinces won't see the tournament in question in the lobby listing."],
			['column' => 'reg_wager_lim', 'tooltip' => 'Controls how much a player needs to have wager in EUR cents in the Reg lim period which is in days.'],
            ['column' => 'reg_dep_lim', 'tooltip' => 'Controls how much a player needs to have deposited in EUR cents in the Reg lim period which is in days.'],
            ['column' => 'reg_lim_period', 'tooltip' => 'Reg lim is the period in days used with Reg wager lim and Reg dep lim.'],
            ['column' => 'reg_lim_excluded_countries','tooltip' => 'Reg lim excluded countries is used to select which countries can register without having to comply with the registration requirements.'],
            ['column' => 'pot_cost'],
            ['column' => 'free_pot_cost', 'tooltip' => 'If there is a pot cost it will be paid for by the casino if this value is 1, if it is zero or empty it will be payed for by the player.'],
            ['column' => 'spin_m', 'tooltip' => 'The spin multiplier, will be used to multiply the cash balance and the available spins on register and rebuy. Use only with win fixed, use the default value 1 for all other types.'],
            ['column' => 'pwd'],
            ['column' => 'number_of_jokers', 'tooltip' => 'The number of Jokers for this tournament. Jokers will get doubled up with their winnings if any.'],
            ['column' => 'bounty_award_id', 'tooltip' => 'The award for the bounty prize for being better with exactly 1 place than the bounty guy who won the last tournament. If it is 0, bounty will be not applied to this tournament.'],
            ['column' => 'bet_levels', 'tooltip' => 'Ex: 20,40,60,100 NO spaces, just commas and cents. If empty min and max bet will be respected instead.'],
            ['column' => 'calc_prize_stamp', 'tooltip' => 'The stamp when the battle prizes were calculated and handed out.'],
            ['column' => 'prizes_calculated', 'tooltip' => '0 / 1, if 1 then the battle prizes should not ever be calculated again, typically set before prize calculations have even started to prevent double cron invocations.'],
            ['column' => 'allow_bonus'],
            ['column' => 'pause_calc', 'tooltip' => '0 / 1, if 1 prizes are not calculated, can be set by an admin.'],
        ];

        array_walk($columns_order, function(&$item) {
            $item['readonly'] = true;
        });

        $column_data = Tournament::getColumnsData();

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

    /**
     * function use for cancel the tournaments(moved from old admin)
     * 
     * @param Application $app
     * @param Request $request
     * 
     */
    function cancelTournament(Application $app, Request $request){

        $t_id = $request->get('t_id');

        if(empty($t_id)){
            return 'Tournament could not be found.';
        }

        try{
            phive('Tournament')->cancel($t_id);

            return $app->json(['success' => true]);

        } catch (\Exception $e) {

            return $app->json(['success' => false, 'error' => $e]);
            
        }
    }

    /**
     * function use for pause the tournaments(moved from old admin)
     * 
     * @param Application $app
     * @param Request $request
     * 
     */
    function pauseTournament(Application $app, Request $request){

        $t_id = $request->get('t_id');

        if(empty($t_id)){
            return 'Tournament could not be found.';
        }

        try{
            phive('Tournament')->pause($t_id);

            return $app->json(['success' => true]);

        } catch (\Exception $e) {

            return $app->json(['success' => false, 'error' => $e]);
            
        }

    }

    /**
     * function use for resume the tournaments(moved from old admin)
     * 
     * @param Application $app
     * @param Request $request
     * 
     */
    function resumeTournament(Application $app, Request $request){

        $t_id = $request->get('t_id');

        if(empty($t_id)){
            return 'Tournament could not be found.';
        }

        try{
            phive('Tournament')->resume($t_id);

            return $app->json(['success' => true]);

        } catch (\Exception $e) {

            return $app->json(['success' => false, 'error' => $e]);
            
        }

    }

    /**
     * function use for calculate prizes of the tournaments(moved from old admin)
     * 
     * @param Application $app
     * @param Request $request
     * 
     */
    function calcPrizesOfTournament(Application $app, Request $request){

        $t_id = $request->get('t_id');

        if(empty($t_id)){
            return 'Tournament could not be found.';
        }

        try{
            phive('Tournament')->endTournament($t_id);

            return $app->json(['success' => true]);

        } catch (\Exception $e) {

            return $app->json(['success' => false, 'error' => $e]);
            
        }

    }

    /**
     * Function use for closing the battles
     * 
     * @param Application $app
     * @param Request $request
     * 
     */
    public function closeBattle(Application $app, Request $request)
    {

        try{
            $tournament_id = $request->get('t_id');

            //getting eligible tournament entries data for the tournament id
            $tournament_entries = phive('Tournament/TournamentCloseBattles')->getRelatedTournamentEntries(array(['id' => $tournament_id]));

            //close tournament and calculate prizes and pay out to users
            phive('Tournament/TournamentCloseBattles')->closeAndPayUnpaidBattles($tournament_entries);

            return $app->json(['success' => true]);

        } catch (\Exception $e) {

            return $app->json(['success' => false, 'error' => $e]);
            
        }
    }
}
