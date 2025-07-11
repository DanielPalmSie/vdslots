<?php

/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2015.11.17.
 * Time: 9:29
 */

namespace App\Controllers;

use App\Classes\DateRange;
use App\Models\User;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Extensions\Database\FManager as DB;

class UserSessionsController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];
        return $factory;
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param User $user
     * @return mixed
     */
    public function index(Application $app, Request $request, User $user)
    {
        return $app['blade']->view()->make('admin.user.usersessions.index', compact('app', 'user'))->render();
    }

    /**
     * @param Application $app
     * @param Request $request
     * @param User $user
     * @return mixed
     */
    public function listHistorical(Application $app, Request $request, User $user)
    {
        $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_LAST_30_DAYS);

        $user_sessions = DB::shsSelect('users_sessions', "SELECT us.*, deposits.amount AS deposit_sum, withdrawals.amount AS withdrawal_sum, ugs.bet_amount AS bet_sum, ugs.win_amount AS win_sum
                FROM users_sessions us
                LEFT JOIN (
                    SELECT   sum( amount ) AS amount, `timestamp` , user_id, session_id
                    FROM     cash_transactions
                    WHERE    user_id = :user_idd
                    AND      transactiontype = 3
                    GROUP BY session_id
                ) AS deposits ON deposits.session_id = us.id 
                LEFT JOIN (
                    SELECT   sum( amount ) AS amount, `timestamp` , user_id, session_id
                    FROM     cash_transactions
                    WHERE    user_id = :user_idw
                    AND      transactiontype = 8
                    GROUP BY session_id
                ) AS withdrawals ON withdrawals.session_id = us.id 
                LEFT JOIN (
                    SELECT   sum( bet_amount ) AS bet_amount, sum( win_amount ) AS win_amount, end_time, user_id, session_id
                    FROM     users_game_sessions
                    WHERE    user_id = :user_idbw
                    GROUP BY session_id
                ) AS ugs ON ugs.session_id = us.id
                WHERE us.user_id = :user_id 
                AND us.ended_at >= :start_date 
                AND us.ended_at <= :end_date", [
            'user_id' => $user->getKey(),
            'user_idd' => $user->getKey(),
            'user_idw' => $user->getKey(),
            'user_idbw' => $user->getKey(),
            'start_date' => $date_range->getStart('timestamp'),
            'end_date' => $date_range->getEnd('timestamp')
        ]);

        return $app['blade']->view()->make('admin.user.usersessions.historical', compact('app', 'user', 'user_sessions', 'date_range'))->render();
    }

}
