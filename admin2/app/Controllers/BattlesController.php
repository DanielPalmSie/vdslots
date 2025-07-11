<?php

/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.17.
 * Time: 9:29
 */

namespace App\Controllers;

use App\Helpers\DateHelper;
use App\Models\TournamentEntry;
use App\Models\User;
use App\Repositories\BattlesRepository;
use App\Repositories\BetsAndWinsRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class BattlesController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];
        return $factory;
    }

    /**
     * Filtering users and paginate the results
     *
     * @param Application $app
     * @param Request $request
     * @param User $user
     * @return mixed
     */
    public function listUserBattles(Application $app, Request $request, User $user)
    {
        $date_range = DateHelper::validateDateRange($request);
        $repo = new BattlesRepository();

        $tournament_entries = $repo->getUserBattlesList($date_range, $user->getKey());

        $sort = ['column' => 5, 'type' => "desc", 'start_date' => $date_range['start_date'], 'end_date' => $date_range['end_date']];
        return $app['blade']->view()->make('admin.user.battles.list', compact('tournament_entries', 'app', 'user', 'sort'))->render();
    }

    public function showBattleResults(Application $app, Request $request, User $user)
    {
        $date_range = DateHelper::validateDateRange($request);

        $tournament_id = $request->get('t_id');

        $tournament_entries = TournamentEntry::with('user')->where('t_id', $tournament_id)->get();

        $sort = ['column' => 4, 'type' => "asc", 'start_date' => $date_range['start_date'], 'end_date' => $date_range['end_date']];
        return $app['blade']->view()->make('admin.user.battles.result', compact('tournament_entries', 'app', 'user', 'sort', 'tournament_id'))->render();
    }

    public function showBattleBetsAndWins(Application $app, Request $request, User $user)
    {
        $date_range = DateHelper::validateDateRange($request);
        $bw_repo = new BetsAndWinsRepository($app, $user, $request);

        $bets_and_wins = $bw_repo->getBetWinListQuery()->where('t_id', $request->get('t_id'))->get();

        $sort = ['column' => 1, 'type' => "desc", 'start_date' => $date_range['start_date'], 'end_date' => $date_range['end_date']];
        return $app['blade']->view()->make('admin.user.battles.betsandwins', compact('app', 'user', 'sort', 'bets_and_wins'))->render();
    }
}