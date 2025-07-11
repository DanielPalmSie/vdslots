<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 22/03/16
 * Time: 11:29
 */

namespace App\Controllers;

use App\Helpers\DataFormatHelper;
use App\Helpers\SportsbookHelper;
use App\Models\RgLimits;
use App\Models\User;
use App\Repositories\ActionRepository;
use App\Repositories\LimitsRepository;
use App\Repositories\TransactionsRepository;
use Carbon\Carbon;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Videoslots\HistoryMessages\InterventionHistoryMessage;

class LimitsController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $factory = $app['controllers_factory'];
        return $factory;
    }

    /**
     * Show and edit user gaming limits
     *
     * @param Application $app
     * @param User $user
     * @return mixed
     */
    public function gamingLimits(Application $app, User $user)
    {
        $is_sportsbook_enabled = SportsbookHelper::hasSportsbookEnabled($user);
        $common_limits = DataFormatHelper::getLimitsNames(null, $is_sportsbook_enabled);
        $time_span_list = DataFormatHelper::getLimitsTimeSpanList();
        $jurisdiction = (new \App\Repositories\UserRepository($user))->getJurisdiction();
        $net_deposit_month_jurisdictions = array_filter(
            explode(
                ",",
                phive('Config')->getValue('net-deposit-limit', 'net-deposit-limit-month-jurisdictions', '')
            )
        );

        return $app['blade']
            ->view()
            ->make(
                'admin.user.limits.gaming-limits',
                compact(
                    'app',
                    'user',
                    'common_limits',
                    'time_span_list',
                    'jurisdiction',
                    'net_deposit_month_jurisdictions'
                )
            )
            ->render();
    }

    public function inOutLimits(Application $app, User $user, Request $request)
    {
        if ($request->request->get('list') == 1) {
            return $app->json(TransactionsRepository::getMethods($app, $request));
        }
        $user->settings_repo->populateSettings();

        return $app['blade']->view()->make('admin.user.limits.inout-limits', compact('app', 'user', 'request'))->render();
    }

    public function blockManagement(Application $app, User $user, Request $request)
    {
        $user->settings_repo->populateSettings();
        $self_exclusion_options = LimitsRepository::getUserSelfExclusionTimeOptions($user);

        return $app['blade']->view()->make(
            'admin.user.limits.block-management',
            compact('app', 'user', 'self_exclusion_options')
        )->render();
    }

    /**
     * Set user gaming limits in the users_settings table
     *
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse|RedirectResponse
     */
    public function setInOutLimits(Application $app, User $user, Request $request)
    {
        if ($request->isMethod('POST')) {
            $type = $request->request->get('type');
            $method = $request->request->get('method');
            $limit = $request->request->get('limit');
            if (empty($type) || empty($method) || empty($limit)) {
                return $app->json([
                    'success' => false,
                    'message' => "One field is empty. Please fill all of them. $type $method $limit"
                ]);
            } elseif (!is_numeric($limit)) {
                return $app->json(['success' => false, 'message' => 'Limit must be a numeric field.']);
            } else {
                if ($user->repo->setSetting("$method-$type-limit", $limit)) {
                    return $app->json([
                        'success' => true,
                        'message' => 'Limit added successfully. Reloading the page...'
                    ]);
                } else {
                    return $app->json(['success' => false, 'message' => 'Limit not updated due to an unknown error.']);
                }
            }
        } else {
            return new RedirectResponse($request->headers->get('referer'));
        }
    }

    /**
     * Remove user gaming limits
     *
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse|RedirectResponse
     */
    public function removeInOutLimits(Application $app, User $user, Request $request)
    {
        if ($request->isMethod('POST')) {
            $key_element = $request->request->all();
            if ($user->repo->deleteSetting($key_element['key'])) {
                return $app->json(['success' => true, 'message' => 'Limit removed successfully.']);
            } else {
                return $app->json(['success' => false, 'message' => 'Limit not removed due to an unknown error.']);
            }
        } else {
            return new RedirectResponse($request->headers->get('referer'));
        }
    }

    /**
     * @param Application $app
     * @param User $user
     * @param Request $request
     * @return false|string|RedirectResponse
     * @throws \Exception
     */
    public function editGamingLimit(Application $app, User $user, Request $request)
    {
        $limits_repo = new LimitsRepository($app);
        $type = $request->get('type');

        $actions = ['update-limits', 'remove', 'remove-no-cooling', 'history', 'force-limit', 'remove-force-limit'];
        if (in_array($action = $request->get('action'), $actions)) {
            /** @var RgLimits[] $limits */
            $limits = collect($request->get('limits'))
                ->map(function ($details, $type_span) use ($request, $user) {
                    return new RgLimits([
                        "user_id" => $user->id,
                        "cur_lim" => $details['cur_lim'],
                        "new_lim" => $details['new_lim'],
                        "time_span" => $type_span,
                        "type" => $request->get('type'),
                    ]);
                });

            if ($action =='update-limits') {
                foreach ($limits as $limit) {
                    if (!empty($limit->new_lim) && $limit->new_lim > 0) {
                        $res = $limits_repo->commonRgSetLimit($limit, $user->currency);
                    }
                }
            } elseif ($action == 'remove') {
                $res =$limits_repo->commonRgRemoveLimit($user->id, $type);
            } elseif ($action == 'remove-no-cooling') {
                $res =$limits_repo->commonRgRemoveLimitNoCooling($user->id, $type);
            } elseif ($action == 'force-limit') {
                $res = $limits_repo->commonRgForceLimit($user->id, $type, $request->get('number_days'));
            } elseif ($action == 'remove-force-limit') {
                $res = $limits_repo->commonRgRemoveForcedLimit($user->id, $type);
            } elseif ($action == 'history') {
                return RedirectResponse::create($app['url_generator']->generate('admin.user-actions', [
                    'user' => $user->id,
                    'tag-like' => implode(',', [$type, DataFormatHelper::getOldLimitName($type)])
                ]));
            }

            if (is_array($res) && !empty($res['msg'])) {
                $app['flash']->add('danger', $res['msg']);
            }

            if (in_array($action, ['update-limits', 'force-limit'])) {
                $intervention = ActionRepository::logAction($user->id, "set-limit| {$type} Limit added", 'intervention');
                /** @uses Licensed::addRecordToHistory() */
                lic('addRecordToHistory', [
                    'intervention_done',
                    new InterventionHistoryMessage([
                        'id'             => $intervention->id,
                        'user_id'        => $user->id,
                        'begin_datetime' => Carbon::now()->format('Y-m-d H:i:s'),
                        'end_datetime'   => '',
                        'type'           => 'set-limit',
                        'cause'          => 'force-limit',
                        'event_timestamp'  => Carbon::now()->timestamp
                    ])
                ], $user->id);
            }
            $limits_repo->logCurrentLimit($user->id, $type);

            return new RedirectResponse($request->headers->get('referer'));
        }

        if ($type == 'set-lock') {
            return $limits_repo->setLockAccount($user, $request);
        } elseif ($type == 'revoke-lock') {
            return $limits_repo->revokeLockAccount($user, $request);
        } elseif ($type == 'set-self') {
            $this->logIntervention($user, $request);
            return $limits_repo->setExclusion($user, $request, false);
        } elseif ($type == 'extend-self') {
            return $limits_repo->setExclusion($user, $request, true);
        } else {
            return json_encode(['success' => false, 'message' => "Unknown action."]);
        }
    }

    /**
     * Logs intervention type and cause in actions table
     *
     * @param User $user
     * @param Request $request
     *
     * return void
     */
    private function logIntervention(User $user, Request $request)
    {
        $intervention_type = $request->get('intervention_type');
        $intervention_cause = $request->get('intervention_cause');
        if (
            lic('showInterventionTypes', [], $user->id) &&
            $intervention_type &&
            $intervention_cause
        ) {
            $log_data = implode("|", [
                $intervention_type,
                $intervention_cause
            ]);
            ActionRepository::logAction($user->id, $log_data, 'intervention');
        }
    }
}
