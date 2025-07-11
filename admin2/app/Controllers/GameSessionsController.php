<?php

/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.17.
 * Time: 9:29
 */

namespace App\Controllers;

use App\Helpers\DateHelper;
use App\Models\User;
use App\Models\UserGameSession;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use App\Extensions\Database\FManager as DB;
use Symfony\Component\HttpFoundation\Request;

class GameSessionsController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];
        return $factory;
    }

    public function listHistorical(Application $app, Request $request, User $user)
    {
        $date_range = DateHelper::validateDateRange($request, 3);

        $do_query = function (Builder $base_query, bool $do_archive = false) use ($date_range, $user) {
            if ($do_archive) {
                $base_query->selectRaw('ugs.*, micro.game_name');
            } else {
                $base_query->selectRaw('ugs.*, micro.game_name, ugss.game_version')
                    ->leftJoin('users_game_sessions_stats as ugss', 'ugss.game_session_id', '=', 'ugs.id');
            }
            $base_query->leftJoin('micro_games as micro', 'micro.ext_game_name', '=', 'ugs.game_ref')
                ->where('user_id', $user->getKey());
            if (is_null($date_range['end_date'])) {
                $base_query->where('start_time', '>=', $date_range['start_date']);
            } else {
                $base_query->whereBetween('start_time', [$date_range['start_date'], $date_range['end_date']]);
            }

            return $base_query->groupBy('ugs.id')->orderBy('start_time', 'DESC')->get();
        };

        /** @var Collection $archive TODO remove this temp fix as it fails when archive config is not present*/
        $archive = 0; //$do_query(DB::doArchiveDb()->table('users_game_sessions as ugs'), true);
        /** @var Collection $non_archive */
        $non_archive = $do_query(DB::shTable($user->getKey(), 'users_game_sessions as ugs'));
        if (count($archive) > 0 && count($non_archive) > 0) {
            $game_sessions = $non_archive->merge($archive)->sortByDesc('start_time');
        } elseif (count($archive) > 0) {
            $game_sessions = $archive;
        } else {
            $game_sessions = $non_archive;
        }

        $sort = ['column' => 6, 'type' => "desc", 'start_date' => $date_range['start_date'], 'end_date' => $date_range['end_date']];
        return $app['blade']->view()->make('admin.user.gamesessions.historical', compact('app', 'user', 'sort', 'game_sessions'))->render();
    }

    public function listInProgress(Application $app, User $user)
    {
        $game_sessions = UserGameSession::query()
            ->leftJoin('users_game_sessions_stats as ugss', 'ugss.game_session_id', '=', 'users_game_sessions.id')
            ->where('user_id', $user->getKey())->whereRaw("end_time = '0000-00-00 00:00:00'")->get();
        $sort = ['column' => 6, 'type' => "desc"];
        return $app['blade']->view()->make('admin.user.gamesessions.inprogress', compact('app', 'user', 'sort', 'game_sessions'))->render();
    }

    public function listLogged(Application $app, User $user, Request $request)
    {
        $date_range = DateHelper::validateDateRange($request, 3);

        $do_query = function (Builder $base_query) use ($date_range, $user) {
            $base_query->where('user_id', $user->getKey());
            if (is_null($date_range['end_date'])) {
                $base_query->where('created_at', '>=', $date_range['start_date']);
            } else {
                $base_query->whereBetween('created_at', [$date_range['start_date'], $date_range['end_date']]);
            }
            return $base_query->orderBy('created_at', 'DESC')->get();
        };

        try {
            /** @var Collection $archive */
            $archive = $do_query(DB::doArchiveDb()->table('users_sessions'));
        } catch (\Exception $e) {
            $archive = [];
        }

        /** @var Collection $non_archive */
        $non_archive = $do_query(DB::shTable($user->getKey(), 'users_sessions'));
        if (count($archive) > 0 && count($non_archive) > 0) {
            $in_outs = $non_archive->merge($archive)->sortByDesc('created_at');
        } elseif (count($archive) > 0) {
            $in_outs = $archive;
        } else {
            $in_outs = $non_archive;
        }

        $sort = ['column' => 1, 'type' => "desc", 'start_date' => $date_range['start_date'], 'end_date' => $date_range['end_date']];
        return $app['blade']->view()->make('admin.user.gamesessions.logged', compact('app', 'user', 'sort', 'in_outs'))->render();
    }
}
