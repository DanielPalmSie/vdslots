<?php

namespace App\Repositories;

use App\Extensions\Database\FManager as DB;
use App\Models\User;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Request;

class ResponsibilityCheckRepository
{
    const LIMIT_ENTRIES = 25;

    /* Identity check provider */
    const GBG_PROVIDER = 'GBG';

    /**
     * Get Identity check data
     *
     * @param User|null $user
     *
     * @return array
     */
    public function getIdentityCheck(User $user = null): array
    {
        $bindings['limit'] = self::LIMIT_ENTRIES;
        $bindings['provider'] = self::GBG_PROVIDER;

        $sql = "
            SELECT
                rc.user_id,
                rc.fullname,
                rc.country,
                rc.requested_at,
                rc.status,
                rc.solution_provider
            FROM responsibility_check rc            
            JOIN users ON users.id = rc.user_id
            WHERE solution_provider = :provider";

        if ($user) {
            $sql .= "
            AND rc.user_id = :user_id
            ORDER BY requested_at DESC
            LIMIT :limit";

            $bindings['user_id'] = $user->getKey();
            return DB::shSelect($user->getKey(), "responsibility_check", $sql, $bindings);
        }

        $sql .= "
            ORDER BY requested_at DESC
            LIMIT :limit";

        return DB::shsSelect("responsibility_check", $sql, $bindings);
    }


    /**
     * Get deposit limit test data
     *
     * @param User|null $user
     *
     * @return array
     */
    public function getDepositLimitIncreaseTest(User $user = null): array
    {

        $sql = " SELECT actions.id, CONCAT(users.firstname, ' ', users.lastname) AS Fullname , DATE_FORMAT(actions.created_at, '%e/%m/%Y' ) , actions.descr
                   FROM users JOIN actions ON actions.actor = users.id
                   WHERE actions.tag = 'rg_test' AND actions.descr in ('fail', 'pass') AND users.id = :user_id ORDER BY actions.created_at desc ";


        $bindings['user_id'] = $user->getKey();
        return DB::shSelect($user->getKey(), "users", $sql, $bindings);
    }

    /**
     * administrator RG test confirmation, log actions with result.
     * on pass add new setting to the user and update rg_limits table dates.
     *
     * @param Request $request
     * @return false|string
     */
    public function rgTestConfirmation(Request $request)
    {
        $actionId = $request->get('action_id');
        $userId = $request->get('user_id');
        $result = $request->get('result');

        $insert = [
            'id' => $actionId,
            'result' => $result,
        ];

        if (!$result) {
            return json_encode(['success' => false, 'msg' => "Confirmation failed, please try again"]);
        }

        cu($userId)->deleteSetting('rg_review_state');

        if ($result == 'pass') {
            cu($userId)->setSetting('deposit_limit_change_approval_date', Carbon::now());
            phive('SQL')->sh($userId)->updateArray('rg_limits', array('changes_at' => Carbon::now()->addDays(3)), ['type' => 'deposit', 'user_id' => $userId]);
        }else{
            cu($userId)->deleteSetting('deposit_removal_check');
            phive('SQL')->sh($userId)->updateArray('rg_limits', array('new_lim' => 0), ['type' => 'deposit', 'user_id' => $userId]);
        }

        phive('DBUserHandler')->logAction($userId, json_encode($insert), 'rg_test_confirmation', '', cu()->userId);
        return json_encode(['success' => true, 'result' => $result]);

    }

    /**
     * Check the actions table for confirmation by the administrator
     *
     * @param User $user
     * @return mixed
     */
    public function onLoadRgMonitoring(User $user)
    {
        $user_id = $user->getKey();
        $rg_admin_data = phive('UserHandler')->getUserActions($user_id, ['rg_test_confirmation'], [], []);

        return $rg_admin_data;
    }
}
