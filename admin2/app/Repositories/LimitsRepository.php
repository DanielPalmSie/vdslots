<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 16/03/16
 * Time: 16:48
 */

namespace App\Repositories;

use App\Helpers\DataFormatHelper;
use App\Helpers\ValidationHelper;
use App\Models\RgLimits;
use App\Models\User;
use Carbon\Carbon;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Videoslots\HistoryMessages\InterventionHistoryMessage;
use Exception;

class LimitsRepository
{
    /** @var  Application $app */
    protected $app;

    private $ph_module = null;

    const PERMANENT = 'permanent';

    const INDEFINITE = 'indefinite';

    /**
     * Limits repository constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app = null)
    {
        $this->app = $app;
        $this->ph_module = phive('DBUserHandler/RgLimits')->setAdmin2Context();
    }

    /**
     * @param RgLimits $limit
     * @param String $user_currency
     * @return mixed
     * @throws \Exception
     */
    public function commonRgSetLimit($limit, $user_currency = null)
    {
        $highest_allowed_limit = licSetting('deposit_limit', $limit->user_id)['highest_allowed_limit'] ?? [];

        if (
            (!empty($highest_allowed_limit[$limit->getAttribute("time_span")])) &&
            $limit->getAttribute("type") === 'deposit' &&
            $limit->getAttribute("new_lim") > $highest_allowed_limit[$limit->getAttribute("time_span")]
        ) {
            return false;
        }

        if (empty($limit->getAttribute('cur_lim'))) {
            $res = $this->ph_module->addLimit(
                cu($limit->getAttribute("user_id")),
                $limit->getAttribute("type"),
                $limit->getAttribute("time_span"),
                $limit->getAttribute("new_lim")
            );
            if(phive('DBUserHandler/RgLimits')->getUnit($limit->type) === 'money')
            {
                $description = "{$limit->time_span} limit added, new limit is " . DataFormatHelper::nf($limit->new_lim) . " " . $user_currency;
            } else {
                $description = "{$limit->time_span} limit added, new limit is {$limit->new_lim}";
            }
            //log that we have added applied deposit limit
            $this->ph_module->logAppliedLimit(cu($limit->getAttribute("user_id")));
        } else {
            $res = $this->ph_module->changeLimit(
                cu($limit->getAttribute("user_id")),
                $limit->getAttribute("type"),
                $limit->getAttribute("new_lim"),
                $limit->getAttribute("time_span"),
                RgLimits::where('user_id', $limit->getAttribute("user_id"))
                    ->where('type', $limit->getAttribute("type"))
                    ->get()
                    ->keyBy('time_span')
                    ->toArray()
            );
            if(phive('DBUserHandler/RgLimits')->getUnit($limit->type) === 'money')
            {
                $description = "{$limit->time_span} limit updated, new limit is " . DataFormatHelper::nf($limit->new_lim) . " " . $user_currency;
            } else {
                $description = "{$limit->time_span} limit updated, new limit is {$limit->new_lim}";

            }
        }

        if ($res === true || is_string($res)) {
            ActionRepository::logAction($limit->user_id, $description, $limit->type, false, UserRepository::getCurrentId());
        }
        return $res;
    }

    /**
     * @param $user_id
     * @param $type
     * @return mixed
     * @throws \Exception
     */
    public function commonRgRemoveLimit($user_id, $type)
    {
        $res = $this->ph_module->removeLimit(cu($user_id), $type);
        if (is_string($res)) {
            ActionRepository::logAction($user_id, "Limit removed with cool down", $type, false, UserRepository::getCurrentId());
        }
        return $res;
    }

    /**
     * @param $user_id
     * @param $type
     * @return mixed
     * @throws Exception
     */
    public function commonRgRemoveLimitNoCooling($user_id, $type)
    {
        try {
            $u_obj = cu($user_id);
            $limits = $this->ph_module->getByTypeUser($u_obj, $type);
        } catch (Exception $exception) {
            $this->app['monolog']->addError(__CLASS__ . "Error: {$exception->getMessage()}");
        }

        $res = RgLimits::where('user_id', $user_id)->where('type', $type)->delete();
        if ($res) {
            ActionRepository::logAction($user_id, "Limit deleted with no cool down", $type, false, UserRepository::getCurrentId());

            foreach ($limits as $limit) {
                $this->ph_module->addRgLimitsLog(
                    $u_obj,
                    $type,
                    $limit['time_span'],
                    $limit['cur_lim'],
                    0,
                    $this->ph_module::REQUEST_TYPE_REMOVE_NO_COOL_OFF,
                );
            }
        }

        return $res;
    }

    /**
     * @param $user_id
     * @param $type
     * @param $days
     * @return mixed
     * @throws \Exception
     */
    public function commonRgForceLimit($user_id, $type, $days)
    {
        $res = $this->ph_module->forceLimit(cu($user_id), $type, Carbon::now()->addDays($days)->toDateTimeString());
        if ($res === true) {
            ActionRepository::logAction($user_id, "Limit forced for {$days} days", $type, false, UserRepository::getCurrentId());
        }
        return $res;
    }

    /**
     * @param $user_id
     * @param $type
     * @return mixed
     * @throws \Exception
     */
    public function commonRgRemoveForcedLimit($user_id, $type)
    {
        $res = $this->ph_module->removeForcedLimit(cu($user_id), $type);
        if ($res === true) {
            ActionRepository::logAction($user_id, "Forced status lifted this limit", $type, false, UserRepository::getCurrentId());
        }
        return $res;
    }

    public function setLockAccount(User $user, Request $request)
    {
        if ($res = ValidationHelper::validateEmptyFields($request, ['key', 'time'])) {
            return json_encode(['success' => false, 'message' => "Validation error: field '$res' is empty."]);
        } elseif (!is_numeric($request->get('time'))) {
            return json_encode(['success' => false, 'message' => "Validation error: the duration field is not numeric."]);
        }

        if (p('user.block')) {
            $block_repo = new BlockRepository($user);
            $hours = (int)$request->get('time') * 24;
            if (empty($user->active)) {
                return json_encode(['success' => false, 'message' => "User is blocked already."]);
            }
            if ($user->country == 'GB' && $hours > 1008) {
                return json_encode(['success' => false, 'message' => "UK players accounts cannot be locked more than 6 weeks."]);
            }
            $res = $block_repo->lockAccount($this->app, $hours, 'hour');
            if ($res === true) {
                return json_encode(['success' => true, 'message' => "User account locked successfully."]);
            } else {
                return json_encode(['success' => false, 'message' => "Error, operation not completed and changes rolled back."]);
            }
        } else {
            return json_encode(['success' => false, 'message' => "Not enough privileges."]);
        }
    }

    public function revokeLockAccount(User $user, Request $request)
    {
        if (p('user.block')) {
            $block_repo = new BlockRepository($user);

            if ($user->country == 'GB') {
                return json_encode(['success' => false, 'message' => "No early revoke to UK players."]);
            } elseif ($block_repo->revokeLock()) {
                return json_encode(['success' => true, 'message' => "Account early revoked done successfully."]);
            } else {
                return json_encode(['success' => false, 'message' => "Error, operation not completed and changes rolled back."]);
            }
        } else {
            return json_encode(['success' => false, 'message' => "Not enough privileges."]);
        }
    }

    /**
     * Wrapper for phive RG action to properly update rg_limits table
     * when "early revoke" action is triggered by and admin user.
     * It will move the reset date 1 week in the future, regardless of what it was previously set
     *
     * @param int $user_id
     * @return mixed
     */
    public function revokeLockLimit(int $user_id)
    {
        $this->ph_module->revokeLockLimit($user_id);
    }

    public function setExclusion(User $user, Request $request, $extend = false)
    {
        $duration = $request->get('time');
        if ($res = ValidationHelper::validateEmptyFields($request, ['key', 'time'])) {
            return json_encode(['success' => false, 'message' => "Validation error: field '$res' is empty."]);
        } elseif (
            $duration != self::INDEFINITE &&
            $duration != self::PERMANENT &&
            !is_numeric($duration) &&
            !$this->isSelfExclusionOptionValid($user, $duration)
        ) {
            return json_encode(['success' => false, 'message' => "Validation error: the duration field is not numeric."]);
        }

        if (p('user.exclude')) {
            $block_repo = new BlockRepository($user);
            $permanent = $duration == self::PERMANENT;
            $indefinite = $duration == self::INDEFINITE;
            $duration = ($permanent) ? $indefinite : $duration;

            if (!empty($user->repo->getSetting('indefinitely-self-excluded'))) {
                return json_encode(['success' => false, 'message' => "Validation error: the user was permanently self-excluded."]);
            }

            if ($extend && p('user.extend.exclude')) {
                if ($block_repo->selfExclude($this->app, $duration, true, $permanent, $indefinite)) {
                    return json_encode(['success' => true, 'message' => "User account extend self-excluded successfully."]);
                } else {
                    return json_encode(['success' => false, 'message' => "Error, operation not completed and changes rolled back."]);
                }
            } elseif (!$extend) {
                if ($block_repo->selfExclude($this->app, $duration, false, $permanent, $indefinite)) {

                    $duration_carbon = lic('calculateSelfExclusionDurationDate', [$duration], $this->user->id);
                    $end_datetime = $duration_carbon ?: Carbon::now()->addDays($duration);

                    $this->logIntervention($user, $request->get('intervention_type', ''), $request->get('intervention_cause', ''), $end_datetime->format('Y-m-d H:i:s'));

                    return json_encode(['success' => true, 'message' => "User account self-excluded successfully."]);
                } else {
                    return json_encode(['success' => false, 'message' => "Error, operation not completed and changes rolled back."]);
                }
            } else {
                return json_encode(['success' => false, 'message' => "Not enough privileges."]);
            }
        } else {
            return json_encode(['success' => false, 'message' => "Not enough privileges."]);
        }
    }

    public function getDepositLimits(User $user)
    {
        $deposit_limits = RgLimits::sh($user->id)
            ->where('user_id', $user->id)
            ->where('type', 'deposit')
            ->orderByRaw('FIELD(time_span, "day", "week", "month")')
            ->get();

        return $deposit_limits;
    }

    /**
     * Check if the selected self exclusion option is valid
     * @param User $user
     * @param string $option
     * @return bool
     */
    private function isSelfExclusionOptionValid(User $user, $option)
    {
        $valid_options = self::getUserSelfExclusionTimeOptions($user);
        foreach ($valid_options as $valid_option) {
            if ($option == $valid_option['duration']) {
                return true;
            }
        }

        return false;
    }


    /**
     * Return self-exclusion duration from the user if exists
     * @param User $user
     * @return array
     */
    public static function getUserSelfExclusionTimeOptions(User $user):array
    {
        $db_user = cu($user->getKey());
        $durations = lic('getSelfExclusionTimeOptions', [$db_user],  $db_user);
        $str_suffix = 'days';
        $durations_return = [];
        foreach ($durations as $duration) {
            $durations_return[] = [
                'duration' => $duration,
                'label' =>  t("exclude.$duration.$str_suffix")
            ];
        }

        if (lic('indefiniteSelfExclusion', [], $db_user)) {
            $durations_return[] = [
                'duration' => 'indefinite',
                'label' =>  t("exclude.indefinite.$str_suffix")
            ];
        }

        if (lic('permanentSelfExclusion', [], $db_user)) {
            $durations_return[] = [
                'duration' => 'permanent',
                'label' =>  t("exclude.permanent.$str_suffix")
            ];
        }

        return $durations_return;
    }

    /**
     * Log current Rg limits (for now deposit limits only) into `actions` table
     *
     * @param int $user_id
     * @param string $type
     * @throws \Exception
     */
    public function logCurrentLimit(int $user_id, string $type): void
    {
        if ($type === $this->ph_module::TYPE_DEPOSIT) {
            $this->ph_module->logCurrentLimit($user_id);
        }
    }

    /**
     * Logs intervention type and cause in actions table
     *
     * @param User $user
     * @param string $intervention_type
     * @param string $intervention_cause
     * @param string $end_datetime
     *
     * return void
     * @throws \Exception
     */
    public function logIntervention(User $user, string $intervention_type, string $intervention_cause, string $end_datetime): void
    {
        if (
            lic('showInterventionTypes', [], $user->id) &&
            !empty($intervention_type) &&
            !empty($intervention_cause)
        ) {
            $log_data = implode("|", [
                $intervention_type,
                $intervention_cause
            ]);
            $action = ActionRepository::logAction($user->id, $log_data, 'intervention');

            /** @uses Licensed::addRecordToHistory() */
            lic('addRecordToHistory', [
                'intervention_done',
                new InterventionHistoryMessage([
                    'id'             => $action->id,
                    'user_id'        => $user->id,
                    'begin_datetime' => Carbon::now()->format('Y-m-d H:i:s'),
                    'end_datetime'   => $end_datetime,
                    'type'           => $intervention_type,
                    'cause'          => $intervention_cause,
                    'event_timestamp'  => Carbon::now()->timestamp
                ])
            ], $user->id);
        }
    }
}
