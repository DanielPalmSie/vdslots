<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2016.02.03.
 * Time: 14:02
 */

namespace App\Controllers;

use App\Classes\DateRange;
use App\Helpers\DateHelper;
use App\Models\User;
use App\Models\BonusType;
use App\Repositories\BonusRepository;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class BonusController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];

        $factory->match('/filterbonustype/', 'App\Controllers\BonusController::filterbonustype')
            ->bind('bonustype.ajaxfilter');

        return $factory;
    }

    /**
     * Returns response for ajax requests
     *
     * @param Application $app
     * @param Request $request
     * @return mixed
     */
    public function filterbonustype(Application $app, Request $request)
    {
        return BonusType::where('bonus_name', 'LIKE', '%'.$request->query->get('q').'%')
            ->orWhere('bonus_type', 'LIKE', '%'.$request->query->get('q').'%')
            ->orWhere('bonus_code', 'LIKE', '%'.$request->query->get('q').'%')
            ->get();
    }

    public function listRewards(Application $app, User $user, Request $request)
    {
        $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_EMPTY);
        $repo = new BonusRepository($app);

        $rewards = $repo->getRewardsList($user, $date_range, $request);
        $can_forfeit = p('forfeit.bonus');
        $can_reactivate = p('reactivate.bonus');

        $sort = ['column' => 5, 'type' => "desc"];
        return $app['blade']->view()->make('admin.user.bonus.rewards', compact('app', 'user', 'sort', 'date_range', 'rewards', 'can_forfeit', 'can_reactivate'))->render();
    }

    public function listRewardsTransactions(Application $app, User $user, Request $request)
    {
        $date_range = DateRange::rangeFromRequest($request, DateRange::DEFAULT_EMPTY);
        $date_range->validate($app);
        $repo = new BonusRepository($app);

        $rewards_transactions = $repo->getRewardsTransactions($user, $date_range, $request);

        $sort = ['column' => 4, 'type' => "desc"];
        return $app['blade']->view()->make('admin.user.bonus.rewards-transactions', compact('app', 'user', 'sort', 'date_range', 'rewards_transactions'))->render();
    }

    /**
     * Add bonus to User
     * We have two types of bonuses, non-deposit and deposit/reload bonuses
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return mixed
     */
    public function addBonus(Application $app, User $user, Request $request)
    {
        $repo = new BonusRepository($app);

        if ($request->isMethod('POST')) {
            $res = $repo->addBonus($request, $user);
            if ($res) {
                return $app->json(['success' => true, 'message' => $res]);
            } else {
                return $app->json(['success' => false, 'message' => 'Unexpected error adding the bonus']);
            }
        } elseif ($request->isMethod('GET')) {
            if ($request->get('list') == 1) {
                $res = $repo->getBonusList($user, $request->get('limit'), $request->get('type'));
                return $res;
            }
            $bonus_types = $repo->getBonusTypes();
            return $app['blade']->view()->make('admin.user.bonus.add-bonus', compact('app', 'user', 'bonus_types'))->render();

        } else {
            return new RedirectResponse($request->headers->get('referer'));
        }
    }

    /**
     * Delete Bonus Entry
     * todo improve the phive exception
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return RedirectResponse
     */
    public function deleteBonusEntry(Application $app, User $user, Request $request)
    {
        try {
            phive('Bonuses')->fail($request->get('reward_id'), "Bonus with id {$request->get('bonus_id')} failed by ".cu()->getUsername());
        } catch (\Exception $e) {
            $app->abort(500, "Phive error");
        }

        return new RedirectResponse($request->headers->get('referer'));
    }

    /**
     * todo check the behaviour
     * todo improve the phive exception
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return string
     */
    public function reActivateBonusEntry(Application $app, User $user, Request $request)
    {
        try {
            phive('Bonuses')->reactivateBonusEntry((int)$request->get('reward_id'));
        } catch (\Exception $e) {
            $app->abort(500, "Phive error");
        }

        return new RedirectResponse($request->headers->get('referer'));
    }

    /**
     * todo port legacy redeem function
     *
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse|RedirectResponse
     */
    public function vouchers(Application $app, User $user, Request $request)
    {
        if ($request->isMethod('GET')) {
            if (phive('Bonuses')->hasActiveExclusives($user->getKey())) {
                $error = 'Error: Note that the user have an active bonus. If you want to activate a new bonus through this
                 voucher it is mandatory to forfeit the current bonus in the bonus section of the account.';
            }

            $repo = new BonusRepository($app);
            $date_range = DateHelper::validateDateRange($request);
            $sort = ['column' => 0, 'type' => "desc", 'start_date' => $date_range['start_date'], 'end_date' => $date_range['end_date']];

            if ($request->get('list') == 'pending') {
                $app->abort(404);
            } else {
                $vouchers_list = $repo->getVouchersData($date_range, $user);
                return $app['blade']->view()->make('admin.user.bonus.vouchers', compact('app', 'user', 'error', 'sort', 'vouchers_list'))->render();
            }

        } elseif ($request->isMethod('POST')) {
            $form_elements = $request->request->all();
            if (empty($form_elements['vcode'])) {
                return $app->json(['success' => false, 'message' => 'Error: Voucher code field empty.']);
            }

            $redeem_result = phive('Vouchers')->redeem($user->getKey(), $form_elements['vcode'], $form_elements['vcode']);
            if ($redeem_result !== true) {
                $msg = t($redeem_result);
                $success = false;
            } elseif ($GLOBALS['bonus_activation'] === false) {
                $msg = 'The bonus could not be activated, please remove or complete another current bonuses before trying
                to activate this bonus again. Activating and removing bonuses can be done in the bonus section.';
                $success = false;
            } else {
                $msg = 'The voucher was successfully redeemed.';
                $success = true;
            }

            if ($msg) {
                return $app->json(['success' => $success, 'message' => $msg]);
            } else {
                return $app->json(['success' => false, 'message' => 'Unexpected error adding the reward']);
            }

        } else {
            return new RedirectResponse($request->headers->get('referer'));
        }

    }
}
