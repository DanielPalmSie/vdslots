<?php
/**
 * Created by PhpStorm.
 * User: ricardo
 * Date: 16/03/16
 * Time: 16:48
 */

namespace App\Repositories;

use App\Classes\BlockType;
use App\Classes\SimpleResponse;
use App\Extensions\Database\FManager as DB;
use App\Helpers\Common;
use App\Helpers\IPHelper;
use App\Models\RgLimits;
use App\Models\User;
use App\Models\UserBlocked;
use Carbon\Carbon;
use Silex\Application;
use Videoslots\HistoryMessages\CashTransactionHistoryMessage;
use Videoslots\HistoryMessages\InterventionHistoryMessage;

/**
 * Block types:
 * @see BlockType class
 * 0 Failed 3 deposits in a row
 * 1 Failed 3 SMS validations in a row
 * 2 Wrong country
 * 3 Admin locked
 * 4 User locked himself
 * 5 Tried to login too many times with the wrong password
 * 6 Failed SMS authentication
 * 7 Wrong code from email link.
 * 8 Failed login attempts.
 * 9 Payment provider chargeback
 * 10 Too similar to existing user
 * 11 Temporary account block
 * 12 Failed PEP/SL check
 * 13 External self exclusion
 * 14 Underage user
 * 15 Deceased user
 * 16 External precautionary suspension
 *
 * Class BlockRepository
 * @package App\Repositories
 */
class BlockRepository
{
    /** @var User $user */
    protected $user;

    public $settings;

    /** @var  array $cached_data */
    public $cached_data;

    public static $auto_reasons = [0, 1, 2, 5, 6, 7, 8, 9, 10, 11, 12, 13, 15, 16];

    /**
     * Block constructor.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    //TODO shards
    public function populateSettings()
    {
        $this->user->settings_repo->populateSettings();
        $this->settings = $this->user->settings_repo->getSettings();
    }

    public function showActivateBtn()
    {
        $block_reason = $this->getBlockReason()->reason;
        if ($this->user->active || $this->user->repo->getSetting('super-blocked')) {
            return false;
        } elseif ($this->user->country == 'GB' && $this->wasSelfExcluded() && $this->user->repo->getSetting('unlock-date') && !isset($block_reason)) {
            return false;
        }elseif ($this->user->country == 'GB' && $this->wasSelfExcluded()){
            return true;
        } elseif ($this->isAutomaticBlock($block_reason) && p('normal.activate.permission')) {
            $this->activate_btn_reason = $this->getBlockReasonStr($block_reason);
            return true;
        } else {
            return false;
        }
    }

    public function showBlockBtn()
    {
        if ($this->user->active == 0 && !p('user.unlock')) {
            return false;
        } elseif ((!empty($this->user->repo->getSetting('lock-hours')) || $this->user->repo->getSetting('super-blocked')) && !p('user.super.unlock')) {
            return false;
        } elseif ($this->user->country == 'GB' && $this->wasSelfExcluded() && $this->user->repo->getSetting('unlock-date')) {
            return false;
        } elseif (p('user.block') || p('user.super.block')) {
            return true;
        } else {
            return false;
        }
    }

    public function showSelfExclusionBtn()
    {
        return $this->isSelfExcluded() || $this->isExternalSelfExcluded();
    }

    public function isAutomaticBlock($reason = null)
    {
        if (empty($reason)) {
            return in_array($this->getBlockReason()->reason, self::$auto_reasons);
        } else {
            return in_array($reason, self::$auto_reasons);
        }
    }

    public function isSuperBlocked()
    {
        return $this->settings->{'super-blocked'} == 1 && !$this->settings->{'excluded-date'} && !$this->settings->{'unlock-date'} && !$this->settings->{'external-excluded'} ? true : false;
    }

    public function isSelfLocked()
    {
        return $this->settings->{'unlock-date'} && $this->settings->{'lock-hours'};
    }

    public function isUKSelfLock()
    {
        return $this->isSelfLocked() && $this->user->country == 'GB';
    }

    public function isSelfExcluded()
    {
        return phive('DBUserHandler')->isSelfExcluded(cu($this->user));
    }

    public function isExternalSelfExcluded()
    {
        return phive('DBUserHandler')->isExternalSelfExcluded(cu($this->user));
    }

    public function wasSelfExcluded()
    {
        return (!$this->user->active && !$this->settings->{'super-blocked'} && $this->settings->{'excluded-date'} && $this->settings->{'unexcluded-date'}) ? true : false;
    }

    public function canBeExtendedSevenDays()
    {
        return p('user.unlock.in.7.days') && !$this->isSuperBlocked() && $this->user->country != 'GB' && !$this->wasSelfExcluded() && $this->settings->{'unlock-date'};
    }

    public function isDepositBlocked()
    {
        return (int)$this->settings->deposit_block === 1 || (int)$this->settings->restrict === 1;
    }

    public function isWithdrawalBlocked(): bool
    {
        return (int)$this->settings->cdd_withdrawal_block === 1 || (int)$this->settings->withdrawal_block === 1;
    }

    public function hasConsentFor($key = 'promo', $type = 'email', $category = 'main')
    {
        return (int)$this->settings->{"privacy-{$category}-{$key}-{$type}"} === 1;
    }

    public function isPlayBlocked()
    {
        return (int)$this->settings->play_block === 1 || (int)$this->settings->restrict === 1;
    }

    public function isBonusFraudFlagged() {

        return (int)$this->settings->{'bonus-fraud-flag'} === 1;
    }

    public function isBonusBlocked() {

        return (int)$this->settings->bonus_block === 1;
    }

    public function isUserBlocked() {

        return (int)$this->user->active === 0;
    }

    /**
     * We check if at least one the provider we use for "checkDob" during registration returned a verified value.
     * Any other value that is not "1" is a fail
     *
     * @return bool
     */
    public function underAgeVerificationPassed() {
        return (int)$this->settings->id3global_res === 1
            || (int)$this->settings->experian_res === 1;
    }

    /**
     * We check if the results returned by the providers we use for "checkPEPSanctions" during registration / recurrent
     * check are not valid, this mean we have a PEP/SL user.
     *
     * Valid values are:
     * - for id3global: "PASS"
     * - for acuris: "PASS" or "NO MATCH"
     *
     * Fail scenario are if:
     * - BOTH failed
     * - 1 failed but the other users_settings doesn't exist
     *
     * IMPORTANT: if one fail but the other one is passing the user will not be blocked.
     *
     * @return bool
     */
    public function isPepOrSanction()
    {
        $id3_is_pep = isset($this->settings->id3global_pep_res) && $this->settings->id3global_pep_res !== 'PASS';
        $acuris_is_pep = isset($this->settings->acuris_pep_res) && !in_array($this->settings->acuris_pep_res, ['PASS', 'NO MATCH']);
        return ($id3_is_pep && $acuris_is_pep)
            || ($id3_is_pep && !isset($this->settings->acuris_pep_res))
            || ($acuris_is_pep && !isset($this->settings->id3global_pep_res));
    }


    //TODO need to continue refactoring and try to unify all the is.... functions
    public function getBlockReasonName()
    {
        if ($this->settings->{'super-blocked'}) {
            return 'SUPER BLOCKED';
        } elseif ($this->settings->{'unexclude-date'} == "1970-01-01") {
            return 'INDEFINITELY SELF EXCLUDED';
        } elseif ($this->settings->{'indefinitely-self-excluded'}) {
            return 'PERMANENT SELF EXCLUDED';
        } elseif ($this->user->active == '0' && ($this->settings->{'unexclude-date'} || $this->settings->{'external-excluded'})) {
            return 'SELF EXCLUDED';
        } elseif ($this->isSelfLocked()) {
            return 'SELF LOCKED';
        } elseif ($this->settings->{'closed_account'}) {
            return 'ACCOUNT TERMINATED';
        } elseif (!$this->user->active) {
            return 'BLOCKED';
        } else {
            return false;
        }
    }

    //TODO need to continue refactoring and try to unify all the is.... functions
    public function getBlockReasonDescription()
    {
        $msg = '';

        if ($this->settings->{'closed_account'}) {
            return "Player requested account to be terminated on {$this->settings->{'closed_account_date'}}.";
        }

        if ($this->settings->{'super-blocked'}) {
            return $msg;
        }

        if ($this->isSelfExcluded() && !$this->isExternalSelfExcluded()) {
            if ($this->settings->{'unexclude-date'} == "1970-01-01") {
                return "Player is indefinitely self-excluded since {$this->settings->{'excluded-date'}}";
            }
            return "Player self-excluded on {$this->settings->{'excluded-date'}} until {$this->settings->{'unexclude-date'}}.";

        } elseif ($this->isExternalSelfExcluded()) {
            if ($this->user->country == 'GB') {
                $msg .= " on GamStop. Info received on {$this->settings->{'external-excluded'}}.";
            }
            return "Player externally self-excluded $msg";
        }

        $reason_obj = $this->getBlockReason();
        if ($this->settings->{'unlock-date'} && $this->settings->{'lock-date'}) {
            $msg .= "Player was self-locked on {$this->settings->{'lock-date'}}, will be unlocked on {$this->settings->{'unlock-date'}}.";
        } elseif ($this->settings->{'external-unexcluded-date'} && ($reason_obj->reason == 13)) {
            $msg .= "Player was externally self-excluded and we received on {$this->settings->{'external-unexcluded-date'}} confirmation that the self-exclusion ended.";
        } elseif ($this->settings->{'unlock-date'} && !$this->settings->{'lock-date'}) {
            if (empty($reason_obj) && $this->wasSelfExcluded()) {
                if ($this->user->country == 'GB' && $this->settings->{'unlock-date'}) {
                    if(empty($msg))
                        $msg = " Will be automatically unlocked on {$this->settings->{'unlock-date'}}.";
                }
                return "Player self-exclusion ended on {$this->user->repo->getSetting('unexcluded-date')}. $msg";
            }
            $msg .= "<b>{$this->getLastBlockReasonStr($reason_obj)}</b>. Player was blocked on {$reason_obj->date}, will be unlocked on {$this->settings->{'unlock-date'}}.";
        } elseif (!$this->settings->{'unlock-date'} && !$this->settings->{'lock-date'} && !$this->user->active) {
            $msg .= "<b>{$this->getLastBlockReasonStr(null, true)}.</b>";
        }

        if ($this->settings->{'experian_block'}) {
            if (!$this->underAgeVerificationPassed()) {
                $msg .= "\nBlocked due to automatic age verification failure.";
            } else {
                $msg .= "\nBlocked due to automatic verification failure.";
            }
        }

        if ($this->settings->{'tmp_deposit_block'}) {
            $msg .= "\nTemporary deposit blocked due to automatic verification still pending. It will be removed on account verification either automatically or manually.";
        }

        if ($this->settings->{'tac_block'}) {
            $msg .= "\nBlocked due to not accepting T&Cs.";
        } elseif ($this->wasSelfExcluded()) {
            if ($this->user->country == 'GB' && $this->settings->{'unlock-date'} && empty($msg)) {
                $msg .= " Will be automatically unlocked on {$this->settings->{'unlock-date'}}.";
            }
            return "Player self-exclusion ended on {$this->user->repo->getSetting('unexcluded-date')}. $msg";
        }

        return $msg;
    }

    public function getBlockReason()
    {
        $res = DB::shSelect(
            $this->user->getKey(),
            'users_blocked',
            " SELECT * FROM users_blocked WHERE user_id = :user_id ORDER BY id DESC LIMIT 0,1",
            ['user_id' => $this->user->getKey()]
        )[0];

        if (!empty($res->actor_id)) {
            $actor = User::find($res->actor_id);
            $res->actor_username = $actor->username;
            $res->actor_fullname = $actor->firstname . ' ' . $actor->lastname;
        }

        return $res;
    }

    public function getBlockReasonStr($code, $actor_username = false)
    {
        $failed_login_attempts = phive('DBUserHandler')->getSetting('login_attempts');
        $map = [
            BlockType::FAILED_3_DEPOSITS => 'Failed 3 deposits in a row',
            BlockType::FAILED_3_SMS_VALIDATIONS => 'Failed 3 SMS validations in a row',
            BlockType::WRONG_COUNTRY => 'Wrong country',
            BlockType::ADMIN_LOCKED => (!$actor_username) ? "Locked by admin" : "Locked by {$actor_username}",
            BlockType::USER_LOCKED_HIMSELF => 'User locked himself',
            BlockType::FAILED_LOGIN => "Failed {$failed_login_attempts} login attempts",
            BlockType::WRONG_CODE_FROM_EMAIL => 'Wrong code from email link',
            BlockType::FAILED_LOGIN_DUPLICATE_TYPE => "Failed {$failed_login_attempts} login attempts",
            BlockType::PSP_CHARGEBACK => 'Payment provider chargeback',
            BlockType::TOO_SIMILAR_ACCOUNT => 'Too similar to existing user',
            BlockType::TEMPORARY_ACCOUNT_BLOCK => 'Unverified account one month since registration',
            BlockType::FAILED_PEP_SL_CHECK => 'Failed PEP/SL check',
            BlockType::EXTERNAL_SELF_EXCLUSION => 'Externally self-excluded',
            BlockType::EXTERNAL_VERIFIED_AS_DECEASED => 'Externally verified as deceased',
            BlockType::EXTERNAL_PRECAUTIONARY_SUSPENSION => 'Externally precautionary suspension',
            BlockType::IGNORING_INTENSIVE_GAMBLING_CHECK => 'Ignoring Intensive Gambling check',

        ];
        return isset($map[$code]) ? $map[$code] : null;
    }

    public function getLastBlockReasonStr($reason_obj = null, $full_actor = null)
    {
        if (empty($reason_obj)) {
            $reason_obj = $this->getBlockReason();
        }

        if ($reason_obj->reason == BlockType::ADMIN_LOCKED && $reason_obj->actor_id > 0) {
            $actor = empty($full_actor) || empty($reason_obj->actor_fullname) || $reason_obj->actor_fullname == ' ' ? $reason_obj->actor_username : $reason_obj->actor_fullname;
            return "Locked by $actor";
        } else {
            return $this->getBlockReasonStr($reason_obj->reason);
        }
    }

    public function getDialogMessageBody($message_id)
    {
        switch ($message_id) {
            case 'extend-self-lock':
                $new_date = Carbon::now()->addDays(lic('getSelfLockCoolOffDays', [], $this->user->getKey()))->format('Y-m-d H:i:s');
                return "Are you sure you want to change the unlock date to <b>{$this->user->username}</b>?
                        <br><ul><li>Current: {$this->settings->{'unlock-date'}}</li><li>New date: $new_date</li></ul>";
                break;
            default:
                return "Are you sure you want to perform this action?";
        }
    }

    /**
     * @param Application $app
     * @param $reason
     * @param bool $unlock
     * @param null $unlock_date
     * @param bool $log_out
     * @return string|bool
     */
    public function addBlock(Application $app, $reason, $unlock = false, $unlock_date = null, $log_out = true)
    {
        try {
            $res = DB::transaction(function () use ($reason, $unlock, $unlock_date) {
                if (empty($this->user->active)) {
                    return false;
                }
                $this->user->update(['active' => 0]);
                if ($unlock) {
                    $unlock_date = empty($unlock_date) ? Carbon::now()->addDay()->format('Y-m-d H:i:s') : $unlock_date;
                    $this->user->repo->setSetting('unlock-date', $unlock_date);
                }
                $reason_msg = $this->getBlockReasonStr($reason, UserRepository::getCurrentUsername());
                ActionRepository::logAction($this->user, "User blocked. Reason: $reason_msg", 'block', false);

                // Keep this switch conditions and the one in phive DBUserHandler in sync.
                switch ($reason) {
                    case BlockType::USER_LOCKED_HIMSELF:
                        $user_status = phive('DBUserHandler/UserStatus')::STATUS_SELF_EXCLUDED;
                        break;
                    case BlockType::EXTERNAL_SELF_EXCLUSION:
                        $user_status = phive('DBUserHandler/UserStatus')::STATUS_EXTERNALLY_SELF_EXCLUDED;
                        break;
                    case BlockType::EXTERNAL_VERIFIED_AS_DECEASED:
                        $user_status = phive('DBUserHandler/UserStatus')::STATUS_DECEASED;
                        break;
                    default:
                        $user_status = phive('DBUserHandler/UserStatus')::STATUS_BLOCKED;
                        break;
                }
                $this->user->repo->trackUserStatusChanges($user_status);

                return UserBlocked::sh($this->user->getKey())->create([
                    'username' => $this->user->username,
                    'user_id' => $this->user->getKey(),
                    'reason' => $reason,
                    'ip' => IPHelper::remIp(),
                    'actor_id' => UserRepository::getCurrentId()
                ]);
            });
            if ($log_out) {
                phive('UserHandler')->logoutUser($this->user->id);
            }

            if (in_array($reason, [
                BlockType::USER_LOCKED_HIMSELF,
                BlockType::EXTERNAL_SELF_EXCLUSION,
                BlockType::EXTERNAL_VERIFIED_AS_DECEASED
            ])) {
                $current_status = $this->user->repo->getSetting('current_status');

                switch ($reason) {
                    case BlockType::USER_LOCKED_HIMSELF:
                        $user_status = phive('DBUserHandler/UserStatus')::STATUS_SELF_EXCLUDED;
                        break;
                    case BlockType::EXTERNAL_SELF_EXCLUSION:
                        $user_status = phive('DBUserHandler/UserStatus')::STATUS_EXTERNALLY_SELF_EXCLUDED;
                        break;
                    case BlockType::EXTERNAL_VERIFIED_AS_DECEASED:
                        $user_status = phive('DBUserHandler/UserStatus')::STATUS_DECEASED;
                        break;
                    default:
                        $user_status = phive('DBUserHandler/UserStatus')::STATUS_BLOCKED;
                        break;
                }

                if ($current_status !== $user_status) {
                    $app['monolog']->addWarning("User status inconsistent with the block reason.",
                        [
                            'user_id' => $this->user->id,
                            'current_status' => $current_status,
                            'expected_status' => $user_status
                        ]
                    );
                }
            }
            return $res;
        } catch (\Exception $e) {
            Common::dumpTbl('add-block-failed', $e->getMessage());
            return false;
        }
    }

    /**
     * @param Application $app
     * @return bool|string
     */
    public function removeBlock(Application $app)
    {
        $rg_limits_phive = phive('DBUserHandler/RgLimits')->setAdmin2Context();
        try {
            $reason = $this->getLastBlockReasonStr();
            list($block, $result) = lic('hasInternalSelfExclusion', [$this->user->id], $this->user->id);
            if ($block && $result === 'Y') {
                if(phive('UserHandler')->checkRemoveRemoteSelfExclusion($this->user->id)){
                    ActionRepository::logAction($this->user, "Remote user unlocked, original block reason: {$reason}", 'block');
                }
            } else {
                    ActionRepository::logAction($this->user, "Remote user is not self-excluded, no actions required", 'block');
            }
        } catch (\Exception $e) {
            $app['monolog']->addError("removeBlock message:{$e->getMessage()} user:{$this->user->id}");
        }

        try {
            return DB::transaction(function () use($rg_limits_phive) {
                $u_obj = cu($this->user->id);
                if ($u_obj->hasSetting('closed_account')) {
                    return "Closed accounts can't be unblocked.";
                }
                if ($u_obj->isSelfLocked()) {
                    $rg_limits_phive->removeLimit($u_obj, 'lock');
                }
                if ($u_obj->getSetting('excluded-date') && $u_obj->getSetting('unexclude-date')) {
                    if ($u_obj->getSetting('indefinitely-self-excluded')) {
                        $rg_limits_phive->removeLimit($u_obj, 'exclude_indefinite');
                    } else {
                        $rg_limits_phive->removeLimit($u_obj, 'exclude');
                    }
                }

                $users_settings = ['excluded-date', 'unexclude-date', 'unlock-date', 'lock-date', 'lock-hours', 'indefinitely-self-excluded', 'aml52-payout-details-requested'];
                $this->user->repo->deleteSetting($users_settings);
                $reason = $this->getLastBlockReasonStr();

                ActionRepository::logAction($this->user, "User unlocked, original block reason: $reason", 'block', false);

                if ($this->user->repo->isEnabledStatusTracking()) {
                    $status = $this->user->repo->getAllowedUserStatus($users_settings);

                    $this->user->repo->trackUserStatusChanges($status);

                    if (!lic('isActiveStatus', [$status], $this->user->getKey())) {
                        return 'For this jurisdiction only players with an Active status can be unlocked';
                    }
                }

                $this->user->update(['active' => 1]);

                return true;
            });
        } catch (\Exception $e) {
            $app['monolog']->addError("removeBlock message:{$e->getMessage()} user:{$this->user->id}");
            return false;
        }
    }

    /**
     * @param Application $app
     * @param int $duration In months
     * @param bool $extend
     * @param bool $permanent
     * @return bool
     */
    public function selfExclude(Application $app, $duration, bool $extend = false, bool $permanent = false, $indefinite = false)
    {
        if ($this->user->block_repo->isSuperBlocked()) {
            return false;
        }

        try {
            DB::transaction(function () use ($duration, $extend, $permanent, $app, $indefinite) {
                // We need to call addBlock before doing superBlock or the action will not be logged.
                $this->addBlock($app, BlockType::USER_LOCKED_HIMSELF, false, null, false);

                $block_string = $permanent ? 'Permanent' : '';
                if ($indefinite) {
                    $block_string = 'Indefinite';
                }
                if ($extend == false) {
                    ActionRepository::logAction($this->user, "Self Excluded {$block_string}", "profile-lock", true);
                    $this->user->repo->setSetting('excluded-date', Carbon::now());
                } else {
                    ActionRepository::logAction($this->user, "Extend Self Excluded {$block_string}", "profile-lock", true);
                }
                if ($permanent || $indefinite) {
                    $this->user->repo->setSetting('indefinitely-self-excluded', 1);
                    if ($this->user->repo->hasSetting('current_status')) {
                        $this->user->repo->setSetting('current_status', phive("DBUserHandler/UserStatus")::STATUS_SELF_EXCLUDED);
                    }
                }
                if ($permanent) {
                    $duration_carbon = lic('calculateSelfExclusionDurationDate', [$duration], $this->user->id);
                    $duration_carbon = $duration_carbon ? $duration_carbon : Carbon::now()->addDays($duration);
                    $this->user->repo->setSetting('unexclude-date', $duration_carbon->format('Y-m-d H:i:s'));
                }

                if ($indefinite) {
                    $this->user->repo->setSetting('unexclude-date', '1970-01-01');
                }

                if(!$permanent && !$indefinite){
                    $duration_carbon = lic('calculateSelfExclusionDurationDate', [$duration], $this->user->id);
                    $duration_carbon = $duration_carbon ? $duration_carbon : Carbon::now()->addDays($duration);

                    $this->user->repo->setSetting('unexclude-date', $duration_carbon->format('Y-m-d H:i:s'));
                }

                if ($permanent) {
                    lic('selfExclusionPermanent', [$this->user->id], $this->user->id);
                } else {
                    lic('selfExclusionTemporary', [$this->user->id, $duration], $this->user->id);
                }

            });
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function revokeLock()
    {
        try {
            DB::transaction(function () {
                $previous_date = $this->user->repo->getSetting('unlock-date');
                $this->extendBlock(7, 'Y-m-d', false);
                ActionRepository::logAction($this->user, "Early revoke on locked account. Previous unlock date: $previous_date.", "profile-lock", true);
            });
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @param Application $app
     * @param int $duration Type defined in type variable
     * @param string $type
     * @return bool
     */
    public function lockAccount(Application $app, $duration, $type = 'day')
    {
        if ($type == 'hour') {
            $hours = $duration;
        } elseif ($type == 'day') {
            $hours = 24 * $duration;
        } else {
            return false;
        }

        if ($this->user->country == 'GB') {
            $hours = $hours > 1008 ? 1008 : $hours;
        } else {
            $hours = $hours > (10000 * 24) ? (10000 * 24) : $hours;
        }
        try {
            $res = DB::transaction(function () use ($hours, $app) {
                $res = $this->addBlock($app, 3, false, null, false);
                if (!empty($this->user->active)) {
                    Common::dumpTbl('lock-account-failed', $res);
                    return false;
                }
                ActionRepository::logAction($this->user, "Locked Account", "profile-lock", true);
                $rg_limit_lock = new RgLimits([
                    "user_id" => $this->user->id,
                    "cur_lim" => 0,
                    "new_lim" => $hours / 24, // phive common logic is expecting "day"
                    "time_span" => 'na',
                    "type" => 'lock',
                ]);
                $limit_repo = new LimitsRepository();
                // "addLimit" will populate all the other "lock/unlock" settings
                $limit_repo->commonRgSetLimit($rg_limit_lock);

                $intervention = ActionRepository::logAction($this->user->id, "set-exclusion| User account locked", 'intervention');
                /** @uses Licensed::addRecordToHistory() */
                lic('addRecordToHistory', [
                    'intervention_done',
                    new InterventionHistoryMessage([
                        'id'             => $intervention->id,
                        'user_id'        => $this->user->id,
                        'begin_datetime' => Carbon::now()->format('Y-m-d H:i:s'),
                        'end_datetime'   => Carbon::now()->addHours($hours)->format('Y-m-d H:i:s'),
                        'type'           => 'set-exclusion',
                        'cause'          => 'profile-lock',
                        'event_timestamp'  => Carbon::now()->timestamp
                    ])
                ], $this->user->id);

                return true;
            });
            phive('UserHandler')->logoutUser($this->user->id);
            return $res;
        } catch (\Exception $e) {
            Common::dumpTbl('lock-account-failed', $e->getMessage());
            return false;
        }
    }

    /**
     * TODO due to multiples changes on the source cashtransaction stuff will use for the moment the original
     * @param bool $zero_out - if true user balance is zeroed
     * @param bool $log_out - if true we forcefully logout the player
     * @param bool $update_status - if false prevent logging status_change on actions table
     *        (Ex. when self excluding a customer we are superblocking them too, but we want to log only self exclude action)
     * @return SimpleResponse
     */
    public function superBlock($zero_out = true, $log_out = true, $update_status = true)
    {
        if ($this->user->repo->hasSetting('super-blocked')) {
            return SimpleResponse::fail('This user is super blocked already.');
        }
        try {
            DB::transaction(function () use ($update_status, $log_out, $zero_out) {
                $user = cu($this->user->id);
                $user->superBlock($zero_out, $update_status, $log_out);
                $user->superBlockRemote($zero_out, $update_status, $log_out);
                $this->user->repo->deleteSetting(['unexclude-date', 'excluded-date']);
            });

            return SimpleResponse::success("User successfully super blocked.");
        } catch (\Exception $e) {
            return SimpleResponse::fail("There was an error and this user has not been super blocked.", $e);
        }
    }

    /**
     * Remove "super-blocked" from the player, and make it active again after a cool off of 7 days.
     *
     * @return bool
     */
    public function liftSuperBlock()
    {
        try {
            DB::transaction(function () {
                $this->user->repo->deleteSetting(['super-blocked']);
                $this->extendBlock(7, 'Y-m-d', false);
                ActionRepository::logAction($this->user, "Super block lifted.", "profile-lock", true);
            });
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Extend lock period for the player for $days days.
     * By default we apply the lock till midnight of the selected day.
     * If $format is "Y-m-d H:i:s" instead it will be X days from now at that specific time.
     *
     * @param int $days
     * @param string $format
     * @param bool $log_out
     * @return false
     */
    public function extendBlock($days = 7, $format = 'Y-m-d', $log_out = true)
    {
        try {
            $res = DB::transaction(function () use ($days, $format) {
                if ($this->user->active != 1 && !$this->user->repo->hasSetting('super-blocked')) {
                    $date = Carbon::now()->addDays($days);
                    $this->user->repo->setSetting('unlock-date', $date->format($format));
                    $hours = $date->diffInHours(Carbon::now());
                    if ($this->user->repo->hasSetting('lock-hours')) {
                        $this->user->repo->setSetting('lock-hours', $hours);
                    }
                    $limit_repo = new LimitsRepository();
                    $limit_repo->revokeLockLimit($this->user->getKey());
                    // When extending Block, we mark the account as dormant, it will become active after $days days has passed
                    $this->user->repo->trackUserStatusChanges(phive('DBUserHandler/UserStatus')::STATUS_DORMANT);
                    return $date;
                } else {
                    return false;
                }
            });
            if ($log_out) {
                phive('UserHandler')->logoutUser($this->user->id);
            }
            return $res;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function hasIncomeDocs()
    {
        return !empty($this->settings->{'source_of_funds_activated'}) || !empty($this->settings->{'proof_of_wealth_activated'});
    }

    /**
     * @return string
     */
    public function getPlayDocumentStatus()
    {
        $player_status_data = lic('accountVerificationData', [$this->user->id], $this->user->id);
        $display = '';
        if ($player_status_data) {
            $display = $player_status_data['description'];
        }

        return $display;
    }

    public function getIncomeDocsStatus()
    {
        try {
            $documents = phive('Dmapi')->getUserDocumentsv2($this->user->id);

            $doc_res = [];
            foreach ($documents as $document) {
                if (($document['tag'] == 'proofofwealthpic' || $document['tag'] == 'sourceoffundspic') && $document['status'] != 'approved') {
                    $doc_res[] = $document['tag'];
                }
            }

            $map = [
                'sourceoffundspic' => 'Source of Wealth Declaration',
                'proofofwealthpic' => 'Proof of Wealth',
            ];

            // Special setting for users with 30+ days without completing the SOWD, this will be removed when the user submit the documents.
            // When this setting is present the User is restricted too.
            if($this->user->getSetting('sowd-enforce-verification')) {
                return "Account restricted until {$map['sourceoffundspic']} has been verified";
            }

            $msg = "Account restricted until {doc} has been verified";

            if (count($doc_res) == 1) {
                return str_replace("{doc}", $map[$doc_res[0]], $msg);
            } elseif (count($doc_res) == 2) {
                $partial = array_values($map)[0] ." and ". array_values($map)[1];
                return str_replace("{doc}", $partial, $msg);
            }
        } catch (\Exception $e) {

        }
        return '';
    }

    public function getWithdrawalBlockedReason():? string
    {
        if ($this->settings->cdd_withdrawal_block) {
            return 'CDD Restriction';
        }

        if ($this->settings->withdrawal_block) {
            return 'Transactions Restriction';
        }

        return null;
    }
}
