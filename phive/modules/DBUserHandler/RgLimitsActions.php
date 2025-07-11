<?php

use Laraphive\Domain\User\DataTransferObjects\RemoveRgLimitResponseData;
use Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsBetMaxData;
use Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsBetMaxResponseData;
use Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsExcludeIndefiniteResponseData;
use Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsExcludePermanentlyResponseData;
use Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsBlockAccountData;
use Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsBlockAccountResponseData;
use Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsResettableData;
use Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsResettableResponseData;
use Laraphive\Domain\User\Factories\UpdateRgLimitsBlockAccountFactory;
use Laraphive\Domain\User\Factories\UpdateRgLimitsResettableFactory;
use Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsSingleData;
use Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsSingleResponseData;
use Laraphive\Domain\User\Factories\RemoveRgLimitsFactory;
use Laraphive\Domain\User\Factories\UpdateRgLimitsBetMaxFactory;
use Laraphive\Domain\User\Factories\UpdateRgLimitsExcludeIndefiniteFactory;
use Laraphive\Domain\User\Factories\UpdateRgLimitsExcludePermanentlyFactory;
use Laraphive\Domain\User\Factories\UpdateRgLimitsSingleFactory;
use Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsExcludeResponseData;
use Laraphive\Domain\User\Factories\UpdateRgLimitsExcludeFactory;
use Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsGameBreak24ResponseData;
use Laraphive\Domain\User\Factories\UpdateRgLimitsGameBreak24Factory;

require_once __DIR__ . '/RgLimitsResettableFormatter.php';

/**
 * Class the handle response on RgLimits Actions.
 *
 * We need to move the actions to this class, so that the same logic
 * can be used by both Phive and User service.
 *
 * See ch53868-rg (initial POC), for a more extensive example on this matter.
 * that branch cannot be merged as it's binded to work done on new Mr.Vegas site.
 */
class RgLimitsActions
{
    /** @var DBUser $u_obj */
    public $u_obj;

    /** @var RgLimits $rg */
    public $rg;

    /** @var bool default value for response */
    public $success = true;

    /** @var string default result msg */
    public $msg = 'rglimits.added.successfully';

    /** @var bool set to true if we need to force an error message without proceeding with limit logic Ex. user logged out */
    private $forced_fail = false;

    /**
     * RgLimitsAction constructor.
     */
    public function __construct()
    {
        $this->u_obj = cu();
        if (empty($this->u_obj)) {
            $this->forced_fail = true;
            $this->success = false;
            $this->msg = 'user.not.logged.in';
        }
        $this->rg = rgLimits();
    }

    /**
     * Update status and message to be returned
     * based on passed result and perfomed action
     *
     * @param $res
     * @param null $action_type
     * @return array
     */
    public function setChangeMsg($res, $action_type = null)
    {
        if (is_string($res) || $res === true) {
            $this->success = true;
            if ($action_type === 'lower') {
                $this->msg = 'rglimits.change.success.immediate';
            } else {
                $this->msg = 'rglimits.change.success.cooloff';
            }
        } else {
            if ($res === false) {
                $this->msg = $action_type === 'deposit.limit.increase.error' ? $action_type : 'rglimits.change.fail';
                $this->success = false;
            }
        }
    }

    /**
     * Return the final result [success, msg]
     * If params are passed we update the variables first.
     *
     * @param null $res
     * @param null $action_type
     * @return array
     */
    public function getReturnMsg($res = null, $action_type = null)
    {
        if (is_null($res) && is_null($action_type)) {
            return [$this->success, $this->msg];
        }
        $this->setChangeMsg($res, $action_type);
        return [$this->success, $this->msg];
    }

    /**
     * Lock the player for X days
     *
     * @param int $days number of days
     */
    private function lockStandard($days)
    {
        $this->rg->addLimit($this->u_obj, 'lock', 'na', $days);
    }

    /**
     * Lock the player for XXX days, as defined in getRgIndefiniteLockDays
     * otherwise set error message.
     */
    private function lockIndefinitely()
    {
        $indefinite_lock_days = (int)lic('getRgIndefiniteLockDays');
        if (!empty($indefinite_lock_days)) {
            $this->rg->addLimit($this->u_obj, 'exclude', 'na', $indefinite_lock_days);
        } else {
            $this->setChangeMsg(false);
        }
    }

    /**
     * Handle lock logic, both for custom number of days and indefinitely.
     *
     * @param $amount
     * @param false $indefinitely
     */
    public function lock($amount, $indefinitely = false)
    {
        // We're looking at for instance a Swede that want's to lock indefinitely.
        if (!empty($indefinitely)) {
            $this->lockIndefinitely();
        } else {
            $this->lockStandard($amount);
            $this->sendLockToRemoteBrand($amount);
        }
    }

    /**
     * Sends profile lock to remote brand
     *
     * @param $amount
     * @return void
     */
    public function sendLockToRemoteBrand($amount)
    {
        if (!lic('getLicSetting', ['cross_brand'], $this->u_obj)['sync_self_lock']) {
            return;
        }

        $remote_brand = getRemote();
        $remote_user_id = $this->u_obj->getRemoteId();

        if(!empty($remote_user_id)) {
            $response = toRemote(
                $remote_brand,
                'lock',
                [$remote_user_id, $amount]
            );

            if ($response['success']) {
                phive('UserHandler')->logAction(
                    $this->u_obj,
                    "Locked user on {$remote_brand}",
                    "profile-lock-remote",
                    true
                );
            }
        }
    }

    /**
     * Adds and existing self lock on remote brand to the user on local brand.
     * This is typically needed on registration.
     *
     * @param DBUser $user
     * @param string $lock_hours
     * @param string $lock_date
     * @param string $unlock_date
     * @return void
     */
    public function addSelfLockFromRemoteBrand(DBUser $user, string $lock_hours, string $lock_date, string $unlock_date)
    {
        phive("UserHandler")->logAction($user, "Locked Account", "profile-lock", true);
        $user->setSetting('lock-hours', $lock_hours);
        $user->setSetting('lock-date', $lock_date);
        $user->setSetting('unlock-date', $unlock_date);

        $this->rg->uh->addBlock($user, 4, true, $unlock_date);

        $user_id = $user->getId();
        $type = "lock";
        $time_span = "na";
        $insert = [
            'user_id'    => $user_id,
            'cur_lim'    => $lock_hours,
            'new_lim'    => 0,
            'time_span'  => $time_span,
            'progress'   => 0,
            'updated_at' => phive()->hisNow(),
            'created_at' => $lock_date,
            'type'       => $type,
            'extra'      => "",
            'changes_at' => $unlock_date,
        ];

        $this->rg->addRgLimitToHistory($user_id, $type, $lock_hours, $time_span, $lock_date, $insert['changes_at']);

        $this->rg->uh->logAction(
            $user,
            "Synced remote profile lock. lock-hours: {$lock_hours} lock-date: {$lock_date} unlock-date: {$unlock_date}",
            "brand-self-lock"
        );

        $this->rg->setRemoteRequestContext();
        $this->rg->addRgLimitsLog(
            $user,
            $insert['type'],
            $insert['time_span'],
            0,
            $insert['cur_lim'],
            $this->rg::REQUEST_TYPE_ADD,
        );

        $this->rg->db->sh($insert)->insertArray('rg_limits', $insert);
    }

    /**
     * TODO atm it's called directly from actions.php, need to be reworked there to follow standard "execute(action,...)" approach /Paolo
     *
     * @param DBUser $u_obj
     * @param $type
     * @param $limits
     * @param bool $skip_remote_brand
     * @return array|mixed
     */
    function addResettable($u_obj, $type, $limits, $skip_remote_brand = false)
    {
        // We have to check that all limits have been filled in, or none.

        if ($this->rg->isResettable($type)) {
            $tmp = phive()->remEmpty(array_column($limits, 'limit'));
            if (empty($tmp)) {
                return [true, true];
            }

            if (count($tmp) != count($this->rg->time_spans)) {
                return ['nok', t('limit.missing')];
            }
        }

        $highest_allowed_deposit_limit_result = lic('checkHighestAllowedDepositLimit', [[$limits], $u_obj]);

        if (is_array($highest_allowed_deposit_limit_result)) {
            return [
                $highest_allowed_deposit_limit_result['success'],
                $highest_allowed_deposit_limit_result['msg']
            ];
        }

        $highest_allowed_customer_net_deposit_limit_result = lic('checkHighestAllowedCustomerNetDepositLimit', [$limits, $u_obj], $u_obj);

        if (is_array($highest_allowed_customer_net_deposit_limit_result)) {
            return [
                $highest_allowed_customer_net_deposit_limit_result['success'],
                $highest_allowed_customer_net_deposit_limit_result['msg']
            ];
        }

        foreach ($limits as $i => $rgl) {
            $clean_limit = $this->rg->cleanInput($rgl['type'], $rgl['limit']);

            if (empty($clean_limit) || $rgl['limit'] < 1) {
                return $this->getChangeMsg(false);
            }

            // convert euros to cents
            $limits[$i]['limit'] = $clean_limit;
        }

        // If no default settings exist for the user jurisdiction, no extra checks will be done.
        $lic_defaults = lic('getDefaultLimitsByType', [$u_obj, $type], $u_obj);
        $previous_limits = $this->rg->getByTypeUser($u_obj, $type);
        $res_msg = true;

        foreach ($limits as $rgl) {
            $local_clean_limit = $rgl['limit'];

            list($local_clean_limit, $new_msg) = lic('overrideRgLimit',
                [$u_obj, $rgl['type'], $rgl['time_span'], $local_clean_limit, $lic_defaults, $previous_limits], $u_obj);

            $this->rg->addLimit($u_obj, $type, $rgl['time_span'], $local_clean_limit);

            if (!empty($lic_defaults)) {
                $lic_defaults[$rgl['time_span']] -= $local_clean_limit;
            }

            if ($res_msg === true && !empty($new_msg)) {
                $res_msg = $new_msg;
            }

            phive('Cashier/Arf')->invoke('onRgLimitAdd', $u_obj, $local_clean_limit, $rgl);
        }

        // if all limits were left as default $lic_defaults should contain all 0s
        if (!empty($lic_defaults)) {
            $default_counter = 0;
            foreach ($lic_defaults as $value) {
                if ($value == 0) {
                    $default_counter++;
                }
            }
            $using_defaults = $default_counter === count($lic_defaults);
            lic('onAddRgLimitWithDefault', [$u_obj, $type, $using_defaults], $u_obj);
        }

        $this->rg->logAction($u_obj, $limits[0]['type'], $limits, 'add');

        // If all limit are applied correctly we fire cross-brand request if checkbox was selected.
        if (!$skip_remote_brand && $this->isCrossBrandLimit($u_obj, $limits[0]['type'])) {
            toRemote(getRemote(), 'addLimit', [$u_obj->getRemoteId(), $limits[0]['type'], $limits]);
        }

        return [true, $res_msg];
    }


    /**
     * TODO atm it's called directly from actions.php, need to be reworked there to follow standard "execute(action,...)" approach /Paolo
     *
     * @param DBUser $u_obj
     * @param $limits
     * @param $resettable_limits
     * @param bool $skip_remote_brand
     * @return array
     */
    function changeResettable($u_obj, $limits, $resettable_limits, $skip_remote_brand = false)
    {

        $highest_allowed_deposit_limit_result = lic('checkHighestAllowedDepositLimit', [$limits, $u_obj], $u_obj);

        if (is_array($highest_allowed_deposit_limit_result)) {
            return [
                $highest_allowed_deposit_limit_result['success'],
                $highest_allowed_deposit_limit_result['msg']
            ];
        }

        $highest_allowed_customer_net_deposit_limit_result = lic('checkHighestAllowedCustomerNetDepositLimit', [$limits, $u_obj], $u_obj);

        if (is_array($highest_allowed_customer_net_deposit_limit_result)) {
            return [
                $highest_allowed_customer_net_deposit_limit_result['success'],
                $highest_allowed_customer_net_deposit_limit_result['msg']
            ];
        }

        $res = true;
        $action_type = null;
        $empty = 0;
        $original_limit = 0;
        $loss_limit_exceed_max_value = false;
        $max_loss_limit = lic('getHighestAllowedLossLimit', [$u_obj], $u_obj) ?? PHP_INT_MAX;

        // this loop is for validation limit values & converting EUR to cents
        foreach ($limits as $i => $rgl) {
            if (empty($rgl['limit'])) {
                continue;
            }

            $clean_limit = $this->rg->cleanInput($rgl['type'], $rgl['limit']);

            if (empty($clean_limit) || $rgl['limit'] < 1) {
                return $this->getChangeMsg(false);
            }

            if ($rgl['type'] === 'loss' && $clean_limit > $max_loss_limit) {
                $loss_limit_exceed_max_value = true;
                $original_limit = (int)$rgl['limit'];
                $clean_limit = $max_loss_limit;
            }

            // convert EUR to cents
            $limits[$i]['limit'] = $clean_limit;
        }

        foreach ($limits as $rgl) {
            if (empty($rgl['limit'])) {
                continue;
            }

            $clean_limit = $rgl['limit'];

            $ret = lic('changeAccountLimit', [$u_obj, $clean_limit, $resettable_limits[$rgl['time_span']]], $u_obj);

            if (($ret['success'] ?? null) === false) {
                phive('UserHandler')->logAction($u_obj,
                    "Action: change, Type: {$rgl['type']}, Limits: {$clean_limit}, Extra: {$ret['code']} - {$ret['message']}",
                    'pacg-change-account-limit-error', true);
                return $this->getChangeMsg(false);
            }

            phive('Cashier/Arf')->invoke('onRgLimitChange', $u_obj, $clean_limit, $resettable_limits[$rgl['time_span']],
                'change');
            $res = $this->rg->changeLimit($u_obj, $rgl['type'], $clean_limit, $rgl['time_span'], $resettable_limits,
                $action_type, $increased, $rgl['autorevert']);
            $empty++;


            // $increase will be boolean only if the limit changed
            if (!is_null($increased)) {
                $this->rg->logAction($u_obj, $rgl['type'], $limits, $increased ? 'raise' : 'lower');
            }
        }

        phive('Cashier/Arf')->invoke('onSetLimit', $u_obj);

        if ($empty > 0) {
            $this->rg->logAction($u_obj, $limits[0]['type'], $limits, 'change');
            // If all limit are applied correctly we fire cross-brand request if checkbox was selected.
            if (!$skip_remote_brand && $this->isCrossBrandLimit($u_obj, $limits[0]['type'])) {
                toRemote(getRemote(), 'changeLimit', [$u_obj->getRemoteId(), $limits[0]['type'], $limits]);
            }
        } else {
            $res = false;
        }

        if ($loss_limit_exceed_max_value) {
            $this->commentBreachLossLimit($u_obj, $original_limit, $max_loss_limit / 100);
            return ['ok', t2('loss-limit.set.over.maximum.html', ['loss_limit' => $max_loss_limit / 100 . cs()])];
        }

        return $this->getChangeMsg($res, $action_type);
    }

    /**
     * TODO temporary duplicate of actions.php "getChangeMsg" to keep compatibility with the returned messages from inside this class.
     *  This need to be removed once proper logic via "execute" is applied to "add/changeResettable" actions. /Paolo
     *
     * @param $res
     * @param null $action_type
     * @return array
     */
    function getChangeMsg($res, $action_type = null)
    {
        list($res, $msg) = $this->getReturnMsg($res, $action_type);
        $res = !empty($res) ? 'ok' : 'nok';
        $msg = $msg === 'user.not.logged.in' ? 'no user' : t2($msg,
            ['cooloff_period' => lic('getCooloffPeriod', [getCountry()])]);
        return [$res, $msg];
    }

    /**
     * Execute the rg limit action
     *
     * @param $action
     * @param array $pdata - contains resettable limits data
     * @param array $extra_data - contains other kind of limits data
     * @return array|void
     */
    public function execute($action, $pdata, $extra_data = [])
    {
        if ($this->forced_fail) {
            return $this->getReturnMsg();
        }
        switch ($action) {
            case 'lock':
                $this->lockAction($extra_data['indefinitely'], $extra_data['num_days'], $extra_data['num_hours']);
                break;
            // TODO port the other actions here
        }

        return $this->getReturnMsg();
    }

    /**
     * @param string $type
     * @param array $limits
     * @param array $resettableLimits
     *
     * @return array
     */
    public function changeResettableAction(string $type, array $limits, array $resettableLimits): array
    {
        list($res, $msg) = $this->changeResettable($this->u_obj, $limits, $resettableLimits);

        if ($type === RgLimits::TYPE_DEPOSIT) {
            $this->rg->logCurrentLimit($this->u_obj);
        }

        return [$msg, $res];
    }

    /**
     * @param string $type
     * @param array $limits
     *
     * @return array
     */
    public function addResettableAction(string $type, array $limits): array
    {
        $uh = phive('UserHandler');
        $msg = t2('rglimits.added.successfully', ['cooloff_period' => lic('getCooloffPeriod', [getCountry()])]);

        if ($this->rg->isResettable($type)) {
            foreach ($limits as $rgl) {
                if (empty($rgl['limit'])) {
                    return [t('limit.missing'), 'nok'];
                }

                $cleanLimit = $this->rg->cleanInput($rgl['type'], $rgl['limit']);
                if (empty($cleanLimit) || $rgl['limit'] < 1) {
                    return [t('rglimits.change.fail'), 'nok'];
                }
            }
        }

        $highestAllowedDepositLimitResult = lic('checkHighestAllowedDepositLimit', [$limits, $this->u_obj],
            $this->u_obj);

        if (is_array($highestAllowedDepositLimitResult) && $highestAllowedDepositLimitResult['success'] === 'nok') {
            return [$highestAllowedDepositLimitResult['msg'], 'nok'];
        }

        $highest_allowed_customer_net_deposit_limit_result = lic('checkHighestAllowedCustomerNetDepositLimit', [$limits, $this->u_obj], $this->u_obj);

        if (is_array($highest_allowed_customer_net_deposit_limit_result)) {
            return [
                $highest_allowed_customer_net_deposit_limit_result['msg'],
                $highest_allowed_customer_net_deposit_limit_result['success']
            ];
        }

        $limitsInCents = [];
        $loss_limit_exceed_max_value = false;
        $original_limit = 0;
        $max_loss_limit = lic('getHighestAllowedLossLimit', [$this->u_obj], $this->u_obj) ?? PHP_INT_MAX;

        foreach ($limits as $rgl) {
            $clean_limit = $this->rg->cleanInput($rgl['type'], $rgl['limit']);

            if ($rgl['type'] === 'loss' && $clean_limit > $max_loss_limit) {
                $loss_limit_exceed_max_value = true;
                $original_limit = (int)$rgl['limit'];
                $clean_limit = $max_loss_limit;
            }

            $rgl['limit'] = $clean_limit;
            $this->rg->addLimit($this->u_obj, $type, $rgl['time_span'], $rgl['limit']);
            $limitsInCents[] = $rgl;
        }

        if ($type == 'exclude') {
            if ($uh->getSetting('has_gamstop_content') === true && $this->u_obj->getCountry() == 'GB') {
                $msg = t('exclude.gamstop.end.info.html');
            }
        }

        $this->rg->logAction($this->u_obj, $type, $limitsInCents, 'add');

        if ($this->isCrossBrandLimit($this->u_obj, $type)) {
            toRemote(getRemote(), 'addLimit', [$this->u_obj->getRemoteId(), $type, $limits]);
        }

        if ($type === RgLimits::TYPE_DEPOSIT) {
            $this->rg->logCurrentLimit($this->u_obj);
        }

        if ($loss_limit_exceed_max_value) {
            $this->commentBreachLossLimit($this->u_obj, $original_limit, $max_loss_limit / 100);
            return [t2('loss-limit.set.over.maximum.html', ['loss_limit' => $max_loss_limit / 100 . cs()]), 'ok'];
        }

        return [$msg, 'ok'];
    }

    /**
     * Update resettable RG Limits
     *
     * @param \Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsResettableData $data
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsResettableResponseData
     * @api
     *
     */
    public function updateRgLimitsResettable(UpdateRgLimitsResettableData $data): UpdateRgLimitsResettableResponseData
    {
        $resettableLimits = $this->rg->getByTypeUser($this->u_obj, $data->getType());
        $rgLimitsResettableFormatter = new RgLimitsResettableFormatter($data);
        $limits = $rgLimitsResettableFormatter->getLimitsRequest();

        if (empty($resettableLimits)) {
            list($message, $status) = $this->addResettableAction($data->getType(), $limits);
        } else {
            list($message, $status) = $this->changeResettableAction($data->getType(), $limits, $resettableLimits);
        }

        if ($status === 'nok') {
            return UpdateRgLimitsResettableFactory::createError($message);
        }

        lic('onLoggedPageLoad', [$this->u_obj], $this->u_obj);

        $extraWhere = sprintf("AND type ='%s'", $data->getType());
        $limits = $this->rg->getResettAble($this->u_obj, $extraWhere);
        $changesAt = $limits[0]['changes_at'];
        $limits = $this->formatRgLimitsResettable($limits);

        return UpdateRgLimitsResettableFactory::createSuccess($limits, $changesAt);
    }

    /**
     * @param array $limits
     *
     * @return array
     */
    private function formatRgLimitsResettable(array $limits): array
    {
        $result = [];

        foreach ($limits as $limit) {
            unset($limit['country'], $limit['changes_at']);

            $type = $limit['type'];
            $newLimit = $this->rg->prettyLimit($type, $limit['new_lim']);
            $curLimit = $this->rg->prettyLimit($type, $limit['cur_lim']);
            $progress = $this->rg->prettyLimit($type, $limit['progress']);

            $values = [
                'values' => [
                    'rem' => $this->rg->prettyLimit($type, $this->rg->getRemaining($limit)),
                    'new' => $newLimit,
                    'cur' => $curLimit,
                ],
            ];

            $limit['cur_lim'] = $curLimit;
            $limit['new_lim'] = $newLimit;
            $limit['progress'] = $progress;

            $result[] = array_merge($limit, $values);
        }

        return $result;
    }

    /**
     * Check if current jurisdiction allow cross brand limit actions and if the checkbox was selected.
     *
     * @param DBUser $user
     * @param string $type
     *
     * @return bool
     */
    public function isCrossBrandLimit(DBUser $user, string $type): bool
    {
        return !empty($_POST["cross-brand-limit-{$type}"]) && lic('showCrossBrandLimitExtra', [$type, $user], $user);
    }

    /**
     * @param string $type
     * @param string $limit
     * @param bool $translate
     *
     * @return array
     */
    public function updateSingleAction(string $type, string $limit, bool $translate = false): array
    {
        $failAlias = 'rglimits.change.fail';
        $successAlias = 'rglimits.added.successfully';
        $cleanLimit = $this->rg->cleanInput($type, $limit);
        $isValidateLimit = lic('isValidRealityCheckDuration', [$cleanLimit, !$translate], $this->u_obj);

        if ($translate) {
            $hasError = $isValidateLimit['status'] == 'error';
            $successMessage = t2($successAlias, ['cooloff_period' => lic('getCooloffPeriod', [getCountry()])]);
            $failMessage = t($failAlias);
        } else {
            $hasError = !$isValidateLimit['valid'];
            $successMessage = $successAlias;
            $failMessage = $failAlias;
        }

        if ($hasError) {
            if ($translate) {
                return [[$isValidateLimit['message']], $isValidateLimit['status']];
            } else {
                return [$isValidateLimit['errors'], 'nok'];
            }
        }

        if (empty($cleanLimit) || $limit < 1) {
            return [[$failMessage], 'nok'];
        }

        $this->rg->saveLimit($this->u_obj, $type, 'na', $this->rg->cleanInput($type, $limit));
        phive('Cashier/Arf')->invoke('onSetLimit', $this->u_obj);

        return [[$successMessage], 'ok'];
    }

    /**
     * Update single RG Limits
     *
     * @param \Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsSingleData $data
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsSingleResponseData
     * @api
     *
     */
    public function updateRgLimitsSingle(UpdateRgLimitsSingleData $data): UpdateRgLimitsSingleResponseData
    {
        if (($data->getType() == RGLimits::TYPE_BALANCE && !lic('hasBalanceTypeLimit')) ||
            ($data->getType() == RGLimits::TYPE_RC && empty(phive('Casino')->startAndGetRealityInterval()))) {
            return UpdateRgLimitsSingleFactory::createError(['Forbidden'], 403);
        }

        list($messages, $status) = $this->updateSingleAction($data->getType(), $data->getLimit());

        if ($status !== 'ok') {
            return UpdateRgLimitsSingleFactory::createError($messages);
        }

        $limit = $this->rg->getSingleLimit($this->u_obj, $data->getType());
        $changesAt = $limit['changes_at'];
        $limit = $this->formatRgLimitsResettable([$limit]);

        return UpdateRgLimitsSingleFactory::createSuccess($limit, $changesAt);
    }

    /**
     * @param string $timeSpan
     * @param string $limit
     * @param bool $translate
     *
     * @return array
     */
    public function updateBetMaxAction(string $timeSpan, string $limit, bool $translate = false): array
    {
        $failAlias = 'rglimits.change.fail';
        $successAlias = 'rglimits.change.success.betmax';

        if ($translate) {
            $successMessage = t2($successAlias, ['cooloff_period' => lic('getCooloffPeriod', [getCountry()])]);
            $failMessage = t($failAlias);
        } else {
            $successMessage = $successAlias;
            $failMessage = $failAlias;
        }

        $cleanLimit = $this->rg->cleanInput(RgLimits::TYPE_BETMAX, $limit);
        $result = $this->rg->saveVariableCoolOffLimit($this->u_obj, RgLimits::TYPE_BETMAX, $timeSpan, $cleanLimit);

        if (!$result) {
            return [$failMessage, 'nok'];
        }

        phive('Cashier/Arf')->invoke('onSetLimit', $this->u_obj);

        return [$successMessage, 'ok'];
    }

    /**
     * Update betmax RG Limits
     *
     * @param \Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsBetMaxData $data
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsBetMaxResponseData
     * @api
     *
     */
    public function updateRgLimitsBetMax(UpdateRgLimitsBetMaxData $data): UpdateRgLimitsBetMaxResponseData
    {
        list($messages, $status) = $this->updateBetMaxAction($data->getTimeSpan(), $data->getLimit());

        if ($status !== 'ok') {
            return UpdateRgLimitsBetMaxFactory::createError($messages);
        }

        $limit = $this->rg->getSingleLimit($this->u_obj, RgLimits::TYPE_BETMAX);
        $changesAt = $limit['changes_at'];
        $limit = $this->formatRgLimitsResettable([$limit]);

        return UpdateRgLimitsBetMaxFactory::createSuccess($limit, $changesAt);
    }

    /**
     * @param bool $translate
     *
     * @return string
     */
    public function excludePermanentlyAction(bool $translate = false): string
    {
        $this->rg->addLimit($this->u_obj, 'exclude', 'na', 365, ['permanent' => true]);
        $message = lic('getPermanentSelfExclusionConfirmMessage', [$translate], $this->u_obj);
        lic('selfExcludeEmail', [$this->u_obj], $this->u_obj);
        lic('selfExclusionPermanent', [$this->u_obj], $this->u_obj);

        phive('Cashier/Arf')->invoke('onSetLimit', $this->u_obj);

        return $message;
    }

    /**
     * Update exclude permanently RG Limits
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsExcludePermanentlyResponseData
     * @api
     *
     */
    public function updateRgLimitsExcludePermanently(): UpdateRgLimitsExcludePermanentlyResponseData
    {
        if (!lic('permanentSelfExclusion', [], $this->u_obj)) {
            return UpdateRgLimitsExcludePermanentlyFactory::createError('Forbidden', 403);
        }

        $userId = $this->u_obj->getId();
        $message = $this->excludePermanentlyAction();
        phive('SQL')->sh($userId)->delete('personal_access_tokens', ['tokenable_id' => $userId]);

        return UpdateRgLimitsExcludePermanentlyFactory::createSuccess($message);
    }

    /**
     * @param string $type
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\RemoveRgLimitResponseData
     *
     * @api
     */
    public function remove(string $type): RemoveRgLimitResponseData
    {
        [$message, $status] = $this->removeAction($type, false);

        if ($status !== 'ok') {
            if (is_array($message) && isset($message['action'])) {
                return RemoveRgLimitsFactory::createErrorWithAction($message['action']);
            } else {
                return RemoveRgLimitsFactory::createError($message);
            }
        }

        return RemoveRgLimitsFactory::createSuccess();
    }

    /**
     * @param string $type
     * @param bool $translate
     *
     * @return array
     */
    public function removeAction(string $type, bool $translate = true): array
    {
        if (phive('DBUserHandler/RgLimitsActions')->isCrossBrandLimit($this->u_obj, $type)) {
            toRemote(getRemote(), 'removeRemoteLimit', [$this->u_obj->getRemoteId(), $type]);
        }

        phive('Cashier/Arf')->invoke('onRgLimitChange', $this->u_obj, null, $type, 'remove');
        $this->rg->logAction($this->u_obj, $type, ['limit' => 'removed'], 'remove');

        if ($type === $this->rg::TYPE_DEPOSIT) {
            $check_for_remove_deposit_limit = lic('depositLimitRemovalTest', [$this->u_obj, $translate], $this->u_obj);
            $this->rg->logCurrentLimit($this->u_obj);
            if (is_array($check_for_remove_deposit_limit) && $check_for_remove_deposit_limit['success'] === 'nok') {
                return [$check_for_remove_deposit_limit['msg'], "nok"];
            }
        }

        [$res, $msg] = $this->getChangeMsg($this->rg->removeLimit($this->u_obj, $type));

        if ($res === "ok") {
            phive('Cashier/Arf')->invoke('onSetLimit', $this->u_obj);
        }

        return [$msg, $res];
    }

    /**
     * @param bool $translate
     *
     * @return string
     */
    public function excludeIndefiniteAction(bool $translate = false): string
    {
        $this->rg->addLimit($this->u_obj, 'exclude_indefinite', 'na', 1, ['indefinite' => true]);
        $message = lic('getPermanentSelfExclusionConfirmMessage', [$translate], $this->u_obj);
        lic('selfExcludeEmail', [$this->u_obj], $this->u_obj);
        lic('selfExclusionPermanent', [$this->u_obj], $this->u_obj);

        phive('Cashier/Arf')->invoke('onSetLimit', $this->u_obj);

        return $message;
    }

    /**
     * Update exclude indefinite RG Limits
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsExcludeIndefiniteResponseData
     * @api
     *
     */
    public function updateRgLimitsExcludeIndefinite(): UpdateRgLimitsExcludeIndefiniteResponseData
    {
        if (!lic('indefiniteSelfExclusion', [], $this->u_obj)) {
            return UpdateRgLimitsExcludeIndefiniteFactory::createError('Forbidden', 403);
        }

        $userId = $this->u_obj->getId();
        $message = $this->excludeIndefiniteAction();
        phive('SQL')->sh($userId)->delete('personal_access_tokens', ['tokenable_id' => $userId]);

        return UpdateRgLimitsExcludeIndefiniteFactory::createSuccess($message);
    }

    /**
     * @param string $duration
     * @param bool $translate
     *
     * @return string
     */
    public function excludeAction(string $duration, bool $translate = false): string
    {
        $this->rg->addLimit($this->u_obj, 'exclude', 'na', $duration);
        $message = lic('getSelfExclusionConfirmMessage', [$translate], $this->u_obj);
        lic('selfExcludeEmail', [$this->u_obj], $this->u_obj);
        lic('selfExclusionTemporary', [$this->u_obj, $duration], $this->u_obj);

        phive('Cashier/Arf')->invoke('onSetLimit', $this->u_obj);

        return $message;
    }

    /**
     * Update exclude RG Limits
     *
     * @param string $duration
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsExcludeResponseData
     * @api
     *
     */
    public function updateRgLimitsExclude(string $duration): UpdateRgLimitsExcludeResponseData
    {
        $timeOptions = lic('getSelfExclusionTimeOptions', [], $this->u_obj);
        if (!in_array($duration, $timeOptions)) {
            return UpdateRgLimitsExcludeFactory::createError('api.user.error.rg.exclude.duration.invalid');
        }

        $userId = $this->u_obj->getId();
        $message = $this->excludeAction($duration);
        phive('SQL')->sh($userId)->delete('personal_access_tokens', ['tokenable_id' => $userId]);

        return UpdateRgLimitsExcludeFactory::createSuccess($message);
    }

    /**
     * @param array $categories
     * @param bool $translate
     *
     * @return array
     */
    public function gameBreak24Action(array $categories, bool $translate = false): array
    {
        $numDays = 1;
        $failAlias = 'rglimits.change.fail';
        $failMessage = $translate ? t($failAlias) : $failAlias;
        $extendedCategories = $this->u_obj->expandLockedGamesCategories($categories ?? []);

        if (empty($extendedCategories)) {
            return [$failMessage, 'nok'];
        }

        $categoriesStr = implode("|", $extendedCategories);
        $result = $this->rg->addLimit($this->u_obj, 'lockgamescat', 'na', $numDays, $categoriesStr, true);

        if ($result === false) {
            return [$failMessage, 'nok'];
        }

        phive('Cashier/Arf')->invoke('onSetLimit', $this->u_obj);

        return ['', 'ok'];
    }

    /**
     * Update gameBreak24 RG Limits
     *
     * @param array $categories
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsGameBreak24ResponseData
     * @api
     *
     */
    public function updateRgLimitsGameBreak24(array $categories): UpdateRgLimitsGameBreak24ResponseData
    {
        if (empty(lic('getLicSetting', ['gamebreak_24']))) {
            return UpdateRgLimitsGameBreak24Factory::createError('Forbidden', 403);
        }

        if (!$this->validateGameBreak24($categories)) {
            return UpdateRgLimitsGameBreak24Factory::createError('api.user.error.rg.gamebreak24.categories.invalid');
        }

        list($message, $status) = $this->gameBreak24Action($categories);

        if ($status !== 'ok') {
            return UpdateRgLimitsGameBreak24Factory::createError($message);
        }

        return UpdateRgLimitsGameBreak24Factory::createSuccess();
    }

    /**
     * @param array $categories
     *
     * @return bool
     */
    private function validateGameBreak24(array $categories): bool
    {
        $availableCategories = array_column(lic('getGamebreak24Categories'), 'alias');

        return !array_diff($categories, $availableCategories);
    }

    /**
     * @param bool $indefinitely
     * @param string|null $numDays
     * @param string|null $numHours
     *
     * @return void
     */
    public function lockAction(bool $indefinitely, ?string $numDays, ?string $numHours): void
    {
        $limit = empty($numDays) ? $numHours / 24 : $numDays;

        $this->lock($limit, $indefinitely);

        phive('Cashier/Arf')->invoke('onSetLimit', $this->u_obj);
    }

    /**
     * @param \Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsBlockAccountData $data
     *
     * @return \Laraphive\Domain\User\DataTransferObjects\UpdateRgLimitsBlockAccountResponseData
     *
     * @api
     */
    public function updateRgLimitsBlockAccount(
        UpdateRgLimitsBlockAccountData $data
    ): UpdateRgLimitsBlockAccountResponseData {
        if (!lic('hasRgSection', ['lock'], $this->u_obj) ||
            $this->isLockForbidden($data->isIndefinitely())
        ) {
            return UpdateRgLimitsBlockAccountFactory::createError('Forbidden', 403);
        }

        $this->lockAction($data->isIndefinitely(), $data->getNumDays(), $data->getNumHours());

        return UpdateRgLimitsBlockAccountFactory::createSuccess();
    }

    /**
     * @param bool $indefinitely
     *
     * @return bool
     */
    private function isLockForbidden(bool $indefinitely): bool
    {
        return !lic('supportIndefiniteLock') && $indefinitely === true;
    }

    /**
     * @param DBUser $user
     * @return $this
     */
    public function setUserObject(DBUser $user): self
    {
        $this->u_obj = $user;

        return $this;
    }

    /**
     * @param DBUser $user
     * @param int    $loss_limit
     * @param int    $max_loss_limit
     *
     * @return void
     */
    private function commentBreachLossLimit(DBUser $user, int $loss_limit, int $max_loss_limit) : void
    {
        $comment = "The customer changed their loss limit to $loss_limit. When reviewing their affordability,
        we notice this is a high amount. We have informed customer that we have lowered amount to $max_loss_limit.
        Customer can request higher limit by submitting evidence that they can afford it.";
        $user->addComment($comment, 0, 'automatic-flags');
        $comment = "We stopped customer from setting a loss limit of $loss_limit and we set the limit to $max_loss_limit
        due to the affordability information we have on customer.";
        $user->addComment($comment, 0, 'rg-action');
    }
}
