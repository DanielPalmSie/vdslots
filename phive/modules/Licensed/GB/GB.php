<?php

use Carbon\Carbon;
use DBUserHandler\DBUserRestriction;
use Laraphive\Contracts\EventPublisher\EventPublisherInterface;
use Rakit\Validation\Rules\Traits\DateUtilsTrait;
use Videoslots\RgLimits\Builders\Locks\LockInterface;

require_once __DIR__ . '/../Traits/RealityCheckTrait.php';
require_once __DIR__ . '/../Libraries/GamStop/Single/GamStopSingle.php';
require_once __DIR__ . '/../Libraries/GamStop/Batch/GamStopBatch.php';
require_once __DIR__ . '/../Traits/ResponsibleGamblingTrait.php';
require_once __DIR__ . '/../Traits/ServicesTrait.php';

class GB extends Licensed
{
    use DateUtilsTrait;
    use RealityCheckTrait;
    use ResponsibleGamblingTrait;
    use \Licensed\Traits\ServicesTrait;

    private const TIME_OUT_FOR_THE_REQUEST = 3;
    public string $ext_exclusion_name = 'gamstop';

    public $prevent_self_exclusion_flow = [
        'Y' => 'N'
    ];

    protected bool $default_net_deposit_in_client_currency = true;

    /**
     * @return bool
     */
    public function hasMaximumBetAmountLimit(): bool {
        return true;
    }

    /**
     * To show the deposit limit on cashier context
     *
     * @param null|DBUser $user
     * @return bool
     */
    public function hasDepositLimitOnCashier($user = null)
    {
        $user = cu($user);

        if (empty($user) || $user->hasSetting('reg-dep-lim-prompt')) {
            return false;
        }

        $cutoff_date = $this->getLicSetting('deposit_limit_cashier_cutoff');
        if (empty($cutoff_date) || $user->getAttr('register_date') < $cutoff_date) {
            return false;
        }

        return true;
    }

    /**
     * Notify net deposit limit reset by email
     *
     * @param $rgl
     * @return void|bool
     */
    public function notifyNetDepositLimitReset($rgl)
    {
        $user = cu($rgl['user_id']);
        if (empty($user)) {
            return false;
        }

        $remote = getRemote();
        $local_total_loss_response = phive('Distributed')->getLossesForUser($user->getId());
        $local_total_loss = $local_total_loss_response['success'] ? (int)$local_total_loss_response['result'] : 0;
        $remote_user_id = linker()->getUserRemoteId($user);
        $remote_total_loss_response = toRemote($remote, 'getLossesForUser', [$remote_user_id]);
        $remote_total_loss = 0;

        if (!empty($remote_total_loss_response['success']) && $remote_total_loss_response['result'] < 0) {
            $remote_user_current_limit = toRemote($remote, 'getCurrentLimit', [$remote_user_id, 'net_deposit', 'month']);
            if ($remote_user_current_limit['success']) {
                $remote_total_loss = chg($remote_user_current_limit['result']['currency'], $user->getCurrency(), (int)$remote_total_loss_response['result'], 1);
            }
        }

        if ($local_total_loss > $remote_total_loss || empty($remote_total_loss) && empty($local_total_loss)) {
            parent::notifyNetDepositLimitReset($rgl);
        } elseif ($local_total_loss === $remote_total_loss) {
            $local_last_loss = phive('Distributed')->getLastLossForUser($user->getId());
            $remote_last_loss = (string)toRemote($remote, 'getLastLossForUser', [$remote_user_id]);
            $is_valid_date = $this->isValidDate($remote_last_loss);

            if (! $is_valid_date) {
                phive('Logger')->error(
                    'The remote last loss timestamp is not valid date.',
                    [$remote_last_loss]
                );
            }

            if ($is_valid_date && Carbon::parse($local_last_loss)->gt(Carbon::parse($remote_last_loss))) {
                parent::notifyNetDepositLimitReset($rgl);
            }
        }
    }

    /**
     * To show the account policy popup after the deposit limit is shown
     * TODO to check if we still need this
     *
     * @param null $user
     * @return bool
     */
    public function hasMessageOnCashier($user = null)
    {
        $user = cu($user);

        if (empty($user) || $user->hasSetting('viewed-account-policy')) {
            return false;
        } else {
            return true;
        }
    }

    public function rgOnChangeCron($rgl){
        $rgl['old_lim'] = $rgl['cur_lim'];
        cu($rgl['user_id'])->setSetting('has_old_limits', 1);
        return $rgl;
    }

    /**
     * In UK we do age checks and pep/sl checks.
     * - Age check is done only on non recurrent check for the player
     *
     * When the check is recurrent the process is running on a fork so we just run the 2 functions directly, instead
     * when the process is not recurrent we want to run the DOB check right away and the PEP/SL check on a fork.
     *
     * @param DBUser $user
     * @param bool $recurrent Ongoing monitoring
     */
    public function checkKyc($user, bool $recurrent = false)
    {
        phive('Logger')->getLogger('registration')->debug("GB::checkKyc", [uid($user), $recurrent]);
        if(empty($recurrent)) {
            phiveApp(EventPublisherInterface::class)
                ->fire('authentication', 'AuthenticationCheckKycGeneralEvent', [uid($user), $recurrent], 0);
        } else {
            parent::checkPEPSanctionsCommon($user, $recurrent);
        }
    }

    /**
     * If there is no registration in progress and we find a customer without KYC checks we perform them
     *
     * @param DBUser $u_obj
     * @return bool|void
     */
    public function onSuccessfulLogin($u_obj)
    {
        if (!$u_obj->isVerified() && !$u_obj->isAgeVerified() && !$u_obj->hasSetting('registration_in_progress')) {
            $this->checkKycDobCommon(uid($u_obj));
        }
        if (empty($this->getLicSetting('id3_onlogin_launch_date'))) {
            return true;
        }
        $uid = uid($u_obj);
        phive()->pexec('na', 'lic', ['kycOnLogin', [$uid], $uid], 0, true);
    }

    /**
     * KYC on login
     *
     * Here we check the following:
     * 1. That the appropriate KYC docs have the correct statuses, if not then we:
     * 2. Call ID3 again on older players which have not been excluded from this check.
     * 3. Finally we restrict the player if it failed the ID3 check too.
     *
     * @param int $uid The user id.
     *
     * @return bool True if no restriction happened, false otherwise.
     */
    public function kycOnLogin($uid){
        $u_obj = cu($uid);

        if($u_obj->isRestricted() || $u_obj->hasSetting('dont_verify_on_login') || $u_obj->hasSetting('registration_in_progress')){
            return true;
        }

        // We disable further checks as we only want this to happen once.
        $u_obj->setSetting('dont_verify_on_login', 1);

        $uh    = phive('UserHandler');
        $ud    = ud($u_obj);

        if(!$uh->doCheckRestrict($u_obj, function(){ return true; })){
            // We have OK docs uploaded so nothing to do.
            return true;
        }

        $is_age_verification_active = in_array('ID3', phive('Licensed')->getSetting('kyc_suppliers')['config']['dob_order']);

        // If we have a registration older than the "cutoff date" we need to do the call again, otherwise we just fetch the setting.
        $is_age_verified = $ud['register_date'] < $this->getLicSetting('id3_onlogin_launch_date')
            ? $this->checkKycDobCommon($u_obj, null, true)
            : $u_obj->isAgeVerified();

        if ($is_age_verified || !$is_age_verification_active) {
            return true;
        }

        $u_obj->restrict(DBUserRestriction::KYC_CHECK);

        return false;
    }


    public function getSelfExclusionConfirmMessage(bool $translate = true)
    {
        $alias = 'exclude.gamstop.end.info.html';

        return $translate ? t($alias) : $alias;
    }

    public function getSelfExclusionExtraInfo()
    {
        return 'exclude.gamstop.account.info.html';
    }

    public function getSelfExclusionRecommendation()
    {
        return t('gamble.too.much.description.selfexclude.gb');
    }

    /**
     * Detect if user has external self exclusion.
     *
     * @param DBUser|null $user
     * @param string $gamstop_res - value in ['Y', 'N', 'P', ''], used to skip the request to gamstop
     * @return mixed
     */
    public function hasExternalSelfExclusion($user = null, $gamstop_res = '')
    {
        return $this->hasExternalSelfExclusionCommon($user, $gamstop_res);
    }

    /**
     * Check if the supplier is credorax and country is gb.
     *
     * @param string $supplier|psp
     * @param array $user
     * @return bool
     */
    public function checkSupplierAndCountry($supplier) : bool
    {
        return ($supplier == 'credorax');
    }

    /**
     * Cron job that comply with:
     * Operator's open sessions are longer than 24 hours, so the operator re-checks the GAMSTOP Matching Service
     * at least every 24hours and act on any Y responses by stopping the open session
     *
     * @throws
     */
    private function externalSelfExclusion(): void
    {
        if ($this->getLicSetting('gamstop')['is_active'] !== true) {
            return;
        }
        $query = "
            SELECT
                u.*,
                us.user_id,
                IFNULL(TIMESTAMPDIFF(HOUR, us.value, NOW()),0) AS last_gamestop_diff
            FROM
                users u
                LEFT JOIN users_settings us ON us.user_id = u.id AND setting = 'last-gamstop-check'
            WHERE
                u.country = 'GB'
            GROUP BY us.user_id
            HAVING last_gamestop_diff >= 23
        ";
        $users = phive('SQL')->shs()->loadArray($query);

        foreach (array_chunk($users, $docs_max_number_of_allowed_items = 1000) as $list) {
            try {
                // $res contains key 'excluded' with the list of ids
                $res = $this->bulkGamstopRequest($list);
                $this->updateGamstopStatusFromBulkRequest($list, $res);
            } catch (\Exception $e) {
                error_log($e->getCode() . ':' . $e->getMessage());
            }
        }
    }


    /**
     * Update users setting with the result of bulkGamstop
     * - last-gamstop-check: for daily check
     * - cur-gamstop: for self-exclusion status
     *
     * @param $list - list of users
     * @param $res - bulk gamstop results contains ['excluded'=>[], 'previouslyExcluded'=>[], 'usersWithErrors'=>[]]
     */
    public function updateGamstopStatusFromBulkRequest($list, $res)
    {
        /**
         * If we receive a malformed res array having only 'errors' elements
         * due to Validation issues we treat them separately.
         */
        if (array_key_exists('error', phive()->flatten($res['usersWithErrors'], true))) {
            foreach ($res['usersWithErrors'] as $elem) {
                $gamstop_res = 'E';
                $user = cu($elem['user']['correlationId']);
                $user->setSetting('last-gamstop-check', phive()->hisNow());
                $ext_excluded = $this->hasExternalSelfExclusion($user, $gamstop_res);
                if ($ext_excluded !== false) {
                    phive('UserHandler')->logoutUser($elem['user_id'], 'external self exclusion');
                }
            }
        } else {
            foreach ($list as $elem) {
                /**
                 * If the User is in the list of customer with invalid data (Ex. mobile too long) we set "res = E"
                 * that will log on user actions that the check failed, but we still update the last-gamstop-check
                 * to avoid the same failing message to be processed every hour, if customer get his data fixed by CS
                 * he will be allowed to login again in max 24h.
                 */
                $user = cu($elem['user_id']);
                if (in_array($elem['user_id'], $res['usersWithErrors'])) {
                    $gamstop_res = 'E';
                    $user->setSetting('last-gamstop-check', phive()->hisNow());
                } else {
                    if (in_array($elem['user_id'], $res['excluded'])) {
                        $gamstop_res = 'Y';
                    } else {
                        $gamstop_res = in_array($elem['user_id'], $res['previouslyExcluded']) ? 'P' : 'N';
                    }
                }
                $ext_excluded = $this->hasExternalSelfExclusion($user, $gamstop_res);
                if ($ext_excluded !== false) {
                    phive('UserHandler')->logoutUser($elem['user_id'], 'external self exclusion');
                }
            }
        }
    }

    /**
     * For GB both "N" and "P" allow marketing material to be sent
     *
     * @param $user
     * @return bool
     * @throws Exception
     */
    public function userIsMarketingBlocked($user)
    {
        $user = cu($user);

        if (empty($user)) {
            return true;
        }

        $res = $this->checkGamStop($user);

        return !in_array($res, ["N", "P"]);
    }



    /**
     * @param array $users
     * @return mixed
     */
    public function getMarketingBlockedUsers($users)
    {
        foreach ($users as $index => $user) {
            if ($user['country'] !== $this->getIso()) {
                unset($users[$index]);
                continue;
            }
        }

        $blocked = [];

        foreach (array_chunk($users, $docs_max_number_of_allowed_items = 1000) as $users_chunk) {

            try {
                $res = $this->bulkGamstopRequest($users_chunk);
                // push only blocked users
                $blocked[] = array_filter($users_chunk, function ($user) use ($res) {
                    return in_array($user['id'], $res['excluded']);
                });
                $this->updateGamstopStatusFromBulkRequest($users_chunk, $res);
            } catch (Exception $exception) {
                $blocked[] = $users_chunk;
            }
        }

        // convert array of arrays to array of users
        $blocked = phive()->flatten($blocked, true);


        // return array of user ids
        return array_map(function ($user) {
            return $user['id'];
        }, $blocked);
    }


    /**
     * Get the gamstop status for a list of users
     *
     * @param $users
     * @param string $req_id
     * @return array|string
     * @throws Exception
     */
    public function bulkGamstopRequest($users, $req_id = '')
    {
        $settings = $this->getLicSetting('gamstop');
        $version = $settings['version'] ?? 'v2';
        $hostUrl = $settings['urls']['batch'][$version];
        $timeout = $settings['timeout'];
        $apiKey = $settings['key'];
        $req_id = empty($req_id) ? phive()->uuid() : $req_id;
        $start_time = microtime(true);

        try {
            $gamStop = new GamStopBatch($version, $hostUrl, $apiKey, $timeout);
            $gamStop->setUsers($users);
            $response = $gamStop->execute();
            $status_code = $response['statusCode'];
            $res_id = $response['X-Unique-Id'];
            return $response;
        } catch (Exception $e) {
            $response = $e->getMessage();
            $status_code = 400;
            $res_id = null;
            throw $e;
        } finally {
            // Add a log for the request
            $this->logExternal('gamstop-bulk', $gamStop->getUsers(), $response, (microtime(true) - $start_time), $status_code, $req_id, $res_id);

            // Add a new log only for the users that have validation problems
            if (is_array($response) && count($response["usersWithErrors"]) > 0) {
                $this->logExternal('gamstop-bulk', $gamStop->getUsers(), $response["usersWithErrors"], (microtime(true) - $start_time), 422, $req_id, $res_id);
            }
        }
    }


    /**
     * @param null $user
     * @return string
     * @throws Exception
     */
    public function checkGamStop($user = null)
    {
        $ud = ud($user);
        $settings = $this->getLicSetting('gamstop');

        //Recommended Circuit Breaker
        if ($settings['disable_calls'] === true || phive('Config')->getValue('lga', 'gamstop-active') != 'on') {
            return 'D';
        }

        $start_time = microtime(true);

        $version = $settings['version'] ?? 'v1';
        $timeout = $settings['timeout'];
        $hostUrl = $settings['urls']['single'][$version];
        $apiKey = $settings['key'];


        try {
            $gamStop = new GamStopSingle($version, $hostUrl, $apiKey, $timeout);
            $gamStop->setParams($ud, phive()->uuid());
            $params = $gamStop->getParams();
            $response = $gamStop->execute();
            $res = $response['X-Exclusion'];
        } catch (Exception $e) {
            $response = [
                'error' => "{$e->getMessage()}",
                'statusCode' => 400
            ];
            $res = $e->getMessage();
        }

        $this->logExternal('gamstop', $params, $response, (microtime(true) - $start_time), $response['statusCode'], $params['x_trace_id'], $response['X-Unique-Id'], $ud);

        return $res;
    }

    /**
     * The time the RC should use as starting point when launching a game
     * @param DBUser $user
     * @return int - seconds
     */
    public function rcElapsedTime($user = null)
    {
        return 0;
    }

    /**
     * @var array
     */
    protected array $fields_to_save_into_users_settings = [
        'nationality'
    ];

    /**
     * Used by "user-service-laravel" and "new game mode"
     * return all the data needed to display the strip - NO HTML
     */
    public function getBaseGameParams($user = null) {
        $licensing_strip_settings = $this->getLicSetting('licensing_strip');
        $res = [
            'authority' => [
                'url' => $licensing_strip_settings['authority_link'],
                'img' => $this->imgUri("gambling_commission.png")
            ],
            'self_exclusion' => [
                'url' => $licensing_strip_settings['self_exclusion_link'],
                'img' => $this->imgUri("gamstop.png")
            ],
            'account_limits' => [
                'url' => $this->getRespGamingUrl($user),
                'img' => $this->imgUri("RG-Icon.png")
            ],
            'game_play_session' => lic('getLicSetting', ['game_play_session'])
        ];
        if (!empty($user)) {
            $res['elapsed_session_time'] = $this->getSessionLength($user);
        }
        if($this->getLicSetting('rg-over_age')) {
            $res['over_age'] = [
                'url' => '',
                'img' => $this->imgUri("18+W.png")
            ];
        }

        return $res;
    }

    public function topLogos($type = 'white', $extra_classes = '')
    {
        if ($this->showTopLogos()) {
            return phive()->ob(function () use ($extra_classes) {
                $this->rgLogo('margin-four-top ' . $extra_classes);
            });
        }
        return false;
    }

    public function topMobileLogos($lang = null)
    {
        if ($this->getLicSetting('rg-buttons')) {
            return phive()->ob(function () use ($lang) {
                $this->rgLogo('rg-mobile-top rg-mobile-top-gb', $lang);
            });
        }
        return false;
    }

    public function rgLogo($extra_classes = '', $lang = null)
    {
        $user = cu();
        $licensing_strip_settings = $this->getBaseGameParams($user);
        ?>
        <div class="rg-top__item rg-logo vs-sticky-bar__images <?php echo $extra_classes ?>" id="vs-sticky-bar__images">
            <a href="<?= $licensing_strip_settings['account_limits']['url'] ?>">
                <img src="<?= $licensing_strip_settings['account_limits']['img'] ?>" id="vs-sticky-bar-image__account-limits" class="vs-sticky-bar__image">
            </a>
            <a href="<?= $licensing_strip_settings['self_exclusion']['url'] ?>" target="_blank" rel="noopener noreferrer">
                <img src="<?= $licensing_strip_settings['self_exclusion']['img'] ?>" id="vs-sticky-bar-image__self-exclusion" class="vs-sticky-bar__image">
            </a>
            <a href="<?= $licensing_strip_settings['authority']['url'] ?>" target="_blank" rel="noopener noreferrer">
                <img src="<?= $licensing_strip_settings['authority']['img'] ?>" id="vs-sticky-bar-image__authority" class="vs-sticky-bar__image">
            </a>
        </div>
        <?php
    }

    /**
     * @see Licensed::rgClockTime()
     * @return bool|int
     */
    public function rgClockTime($user)
    {
        return $this->getSessionLength($user);
    }

    /**
    *   Gets the common parameters used for reality checks
    *   @param $user ->  User
    *   @return array
    */
    public function getRcParams($user = null)
    {
      return [
        'jurisdiction' => 'GB',
        'rcElapsedTime' => 0,
        'rcTotalBet' => 0,
        'rcTotalWin' => 0,
      ];
    }

    // ---- Display related stuff ---- //

    /**
     * @deprecated
     */
    public function rgLockSection($box)
    { ?>
        <?php $box->printRgRadios(array(24, 168, 744), 'lock_duration', 'hours', function () { ?>
        <input id="uk-other" class="left" type="radio" name="other" value=""/>
        <div class="left" style="margin-top: 2px;">
            <?php et("other") ?>
        </div>
    <?php }) ?>
        <div id="uk-lock-txt-holder" style="display: none;">
            <div><?php et("lock.accountgb.other") ?></div>
            <?php dbInput('lock-hours', '', 'text', 'input-normal') ?>
        </div>
        <?php
        return true;
    }

    /**
     * @return \Videoslots\RgLimits\Builders\Locks\LockInterface
     */
    public function createLockBuilder(): LockInterface
    {
        return new \Videoslots\RgLimits\Builders\Locks\GB();
    }

    /**
     * From a previous note on RgLimits:
     * - UKGC specific requirement that a player should not be able to lock account for more than 42 days, if longer
     * they have to self exclude
     * - if longer it indicates a gambling problem (or so they think) and then SelfExclusion is more strict
     *
     * @return int
     */
    public function getLockAccountMaxDays() {
        return 42;
    }

    /**
     * Alias used for the RG Lock Account section
     * - Specific for GB
     *
     * @return array
     */
    public function getLockAccountMessages(): array
    {
        $iso = $this->getIso();

        return [
            'headline' => "lock.account.days$iso",
            'description' => "lock.account$iso.info.html",
            'submenu' => "lock.account$iso.x.days",
        ];
    }

    /**
     * Check the length of the mobile number(gameStop doesn't allow a mobile number longer than 14 characters)
     *
     * @param $prefix
     * @param $mobile
     * @return bool
     */
    public function isMobileLengthCorrect($mobile, $prefix)
    {
        return strlen(phive("Mosms")->cleanUpNumber($prefix . $mobile)) <= 14;
    }

    /**
     * For UK we need to do a full match following the gamstop matrix @see Linker::matchFullDetails() for implementation
     * details.
     *
     * @param DBUser $user
     * @param string $remote
     *
     * @return
     */
    public function matchInBrand($user, $remote)
    {
        $full_details_match = toRemote(
            $remote,
            'matchUser',
            [
                $user->getId(),
                'FullDetails',
                [
                    'user_data' => ud($user),
                    'jurisdiction' => $user->getJurisdiction(),
                ]
            ],
            self::TIME_OUT_FOR_THE_REQUEST
        );

        if ($full_details_match['result'] ) {
            return $full_details_match;
        }

        return toRemote(
            $remote,
            'matchUser',
            [
                $user->getId(),
                'ByAttribute',
                [
                    'user_data' => ud($user),
                    'jurisdiction' => $user->getJurisdiction(),
                ]
            ],
            self::TIME_OUT_FOR_THE_REQUEST
        );
    }

    /**
     * In case we want to log the action, in some jurisdictions we don't want to have this logged in actions.
     *
     * @return bool
     */
    public function logActionOnMatch()
    {
        return true;
    }

    /**
     * Actions to be executed after an account has been updated and we for instance need to check the cross brand link
     * logic
     *
     * @param DBUser|int|string $user
     */
    public function onAccountUpdate($user)
    {
        if (!empty(linker()->getUserRemoteId($user))) {
            phive()->pexec('Site/Linker', 'brandLink', [uid($user), 'yes']);
        }
    }

    /**
     * Triggered by cron command
     *
     * @return void
     */
    public function onEveryHour(): void
    {
        $this->externalSelfExclusion();
    }

    /**
     * If affordability check features should be shown in back office
     * @return bool
     */
    public function showAffordabilityCheck(): bool
    {
        return true;
    }

    /**
     * If vulnerability check features should be shown in back office
     * @return bool
     */
    public function showVulnerabilityCheck(): bool
    {
        return true;
    }

    /**
     * Return Affordability data for a user. To be used by a remote brand
     *
     * @param int $user_id
     * @return mixed|string
     */
    public function getAffordabilityChecks(int $user_id)
    {
        $pr = new PRUserHandler();
        return $pr->getAffordabilityChecks($user_id);
    }

    /**
     * Return latest Affordability status for a user.
     *
     * @param int $user_id
     * @return string
     */
    public function getLatestAffordabilityStatusOfUser(int $user_id): string
    {
        $sql = "SELECT
                    status
                FROM responsibility_check
                WHERE
                    user_id = {$user_id}
                    AND type = 'affordability'
                ORDER BY id DESC";
        $affordability = phive('SQL')->sh($user_id)->loadAssoc($sql);

        return $affordability !== null ? $affordability['status'] : '';
    }

    /**
     * check if the source of funds is in process
     *
     * @param array $user
     * @return bool
     */
    public function checkSourceFundsStatus($user) : bool
    {
        return $user->getSetting('source_of_funds_status') === 'processing';
    }
    /**
     * Return vulnerability data for a user. To be used by a remote brand
     *
     * @param int $user_id
     * @return mixed|string
     */
    public function getVulnerabilityChecks(int $user_id)
    {
        $pr = new PRUserHandler();
        return $pr->getVulnerabilityChecks($user_id);
    }

    /**
     * Perform an affordability check in admin2 and update the net_deposit accordingly
     *
     * @param int $user_id
     * @return mixed|string
     */
    public function affordabilityCheck(int $user_id)
    {
        $user = cu($user_id);
        phive("UserHandler")->logAction(
                $user,
                "Perform an affordability check for user $user_id", "affordability-check-request"
        );

        $response = phive()->postToBoApi("/affordability-check", ['user_id' => $user_id]);
        $response = json_decode($response, true);
        $success = $response['success'];
        $score = $response['score'];
        $score_category = $response['score_category'];
        $limits = rgLimits()->getByTypeUser($user, 'net_deposit');
        $affordability_score_ndl_map = $this->getLicSetting('affordability_score_ndl_map');
        $users_currency = $user->getCurrency();
        $change_limit_result = false;

        if ($success && !empty($score_category)) {
            $ndl = $affordability_score_ndl_map[$score_category];
            $response_converted = chg('GBP', $users_currency, $ndl, 1);
            $aligned_ndl = $this->alignNetDepositLimit($user, $response_converted);
            $whole_amount = nf2($aligned_ndl, true, 100);
            $net_deposit_limit = lic('getNetDepositMonthLimit', [$user, $aligned_ndl], $user);
            $user->setSetting('original_net_deposit_limit', $aligned_ndl);
            if(!rgLimits()->hasLimits($user, 'net_deposit')) {
                $change_limit_result = rgLimits()->addLimit($user,'net_deposit',  'month', $net_deposit_limit);
            } else {
                foreach ($limits as $limit) {
                    if($limit['cur_lim'] > $ndl && $limit['time_span'] == 'month') {
                        $change_limit_result = rgLimits()->changeLimit($user, 'net_deposit', $net_deposit_limit, $limit['time_span']);
                    }
                }
            }
            $action_description = "Successful response from BeBettor ($score_category) - " .
                (
                    $change_limit_result ?
                    "Monthly net-deposit limit set to $whole_amount $users_currency" :
                    "Could not update monthly net-deposit limit"
                );
            phive("UserHandler")->logAction($user, $action_description, "affordability-check-success");
        } else {
            $net_deposit_limit = lic('getNetDepositMonthLimit', [$user], $user);
            $user->setSetting('original_net_deposit_limit', licSetting('net_deposit_limit', $user)['month']);
            $whole_amount = nf2($net_deposit_limit, true, 100);
            if(!rgLimits()->hasLimits($user, 'net_deposit')) {
                $change_limit_result = rgLimits()->addLimit($user,'net_deposit', 'month', $net_deposit_limit);
            } else {
                foreach ($limits as $limit) {
                    if ($limit['time_span'] === 'month') {
                        $aligned_ndl = $this->alignNetDepositLimit($user, $net_deposit_limit);
                        $change_limit_result = rgLimits()->changeLimit($user, 'net_deposit', $aligned_ndl, $limit['time_span']);
                    }
                }
            }

            $action_description = "Unsuccessful response from BeBettor ({$response['error']}) - " .
                ($change_limit_result ? "Monthly net-deposit limit set to $whole_amount $users_currency" : "Could not update monthly net-deposit limit");
            phive("UserHandler")->logAction($user, $action_description, "affordability-check-fail");

        }
    }


    /**
     * Perform a vulnerability check in admin2 and update the net_deposit limit accordingly
     *
     * @param int $user_id
     * @return array{bool, array{success: bool, score: string, flags: array|null, flags_string: string, checkId:
     *                     string}}
     */
    public function vulnerabilityCheck(int $user_id)
    {
        if (!$this->shouldCheckVulnerability($user_id)) {
            return [false, []];
        }

        $user = cu($user_id);

        phive("UserHandler")->logAction(
            $user,
            "Perform a vulnerability check for user $user_id",
            "vulnerability-check-request"
        );

        try {
            $vulnerability_check_response = $this->getVulnerabilityCheckDataFromBO($user_id);
        } catch (Exception $e) {
            phive('Logger')->error(
                'Vulnerability check error',
                [$e, $user_id],
            );
            return [false, []];
        }

        $success = $vulnerability_check_response['success'];

        if (!$success) {
            phive("UserHandler")->logAction(
                $user,
                "Unsuccessful response from BeBettor ({$vulnerability_check_response['error']})",
                "vulnerability-check-fail"
            );
            return [false, []];
        }

        $score = $vulnerability_check_response['score'];
        $vulnerable = $score === 'VULNERABLE';
        $check_id = $vulnerability_check_response['check_id'];
        $this->uh->logAction(
            $user,
            "Successful response from BeBettor ($score) checkId: {$check_id}",
            "vulnerability-check-success"
        );

        $flags = $vulnerability_check_response['flags'];
        $flags_string = implode(" | ", $flags);

        $vulnerability_check_response['flags_string'] = $flags_string;

        return $vulnerable ? [true, $vulnerability_check_response] : [false, []];
    }

    /**
     * Makes a request to BO to have the vulnerability check performed and returns the result.
     *
     * @param int $user_id
     * @throw Exception
     * @return mixed
     * @throws JsonException
     */
    public function getVulnerabilityCheckDataFromBO(int $user_id)
    {
        $response = phive()->postToBoApi("/vulnerability-check", ['user_id' => $user_id]);
        return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Checks if the user has a vulnerability check performed during the past 12 months.
     *
     * @param $user_id
     * @return bool
     */
    public function shouldCheckVulnerability(int $user_id): bool
    {
        $start_date = phive()->hisMod('-12 month');
        $result = phive('SQL')->sh($user_id)->loadArray("
            SELECT id
            FROM responsibility_check
            WHERE solution_provider = 'BeBettor'
              AND type = 'vulnerability'
              AND user_id = {$user_id}
              AND requested_at >= '{$start_date}'
        ");

        return empty($result);
    }

    /**
     * Handles vulnerability check result and updates NDL.
     *
     * @param DBUser $user
     * @return void
     */
    public function handleVulnerabilityCheckResult(DBUser $user)
    {
        $users_currency = $user->getCurrency();
        $base_ndl = phive('Config')->getValue('RG', 'bebettor-vs-score-vulnerable-highrisk', 50000);
        $base_ndl_converted = mc($base_ndl, $users_currency);
        $new_ndl = lic('getNetDepositMonthLimit', [$user, $base_ndl_converted], $user);
        $whole_amount_new_ndl = nf2($new_ndl, true, 100);

        $current_ndl = rgLimits()->getLimit($user, 'net_deposit', 'month')['cur_lim'];
        $whole_amount_current_ndl = nf2($current_ndl, true, 100);

        if ($current_ndl <= $new_ndl) {
            $this->uh->logAction(
                $user,
                "beBettor Vulnerability Check score is VULNERABLE HIGH RISK. Current NDL is " .
                "$whole_amount_current_ndl $users_currency",
                "rg-actions"
            );

            $user->addComment(
                "This account was flagged by beBettor as a FVC and the NDL remained unchanged",
                0,
                'automatic-flags'
            );
            return;
        }

        if(!rgLimits()->hasLimits($user, 'net_deposit')) {
            $change_limit_result = rgLimits()->addLimit(
                $user,
                'net_deposit',
                'month',
                $new_ndl
            );
        } else {
            $change_limit_result = rgLimits()->changeLimit(
                $user,
                'net_deposit',
                $new_ndl,
                'month'
            );
        }

        $change_limit_result_string = $change_limit_result ?
            "This account was flagged by beBettor as a FVC and the NDL have been reduced from " .
            "{$whole_amount_current_ndl} to {$whole_amount_new_ndl} {$users_currency}" :
            "This account was flagged by beBettor as a FVC and could not update monthly net-deposit limit";
        $change_limit_result_string = "beBettor Vulnerability Check score is VULNERABLE HIGH RISK. " .
            $change_limit_result_string;

        $this->uh->logAction(
            $user,
            $change_limit_result_string,
            "rg-actions"
        );

        $comment = "This account was flagged by beBettor as a FVC and the NDL have been reduced from " .
            "{$whole_amount_current_ndl} to {$whole_amount_new_ndl} {$users_currency}";
        $user->addComment(
            $comment,
            0,
            'automatic-flags'
        );
    }

    /**
     * On Document Status Updated
     *
     * @param DBUser $user
     * @param array $document
     */
    public function onDocumentStatusUpdated(DBUser $user, $document)
    {
        if (empty($user)) {
            return;
        }

        $doc_status = $document["attributes"]["status"];
        $doc_tag = $document["attributes"]["tag"];

        if ($doc_tag === 'sourceofincomepic'
            && $doc_status === 'approved'
        ) {
            $amount = chg('GBP', $user->getCurrency(), 3000 * 100, 1);
            rgLimits()->changeLimit($user, 'net_deposit', $amount, 'month');
        }


        $remote_user_id = $user->getRemoteId();
        $local_brand = getLocalBrand();
        if (!$remote_user_id) {
            return;
        }
        $doc_type_setting_name = phive('Dmapi')->getSettingNameForDocumentType($doc_tag);
        linker()->updateDocumentSetting($user, $doc_type_setting_name, $doc_status, $local_brand);

        if ($user->shouldSyncCDDStatus()) {
            $user->updateCDDFlagOnDocumentStatusChange();
            $user->logCDDActions("Document {$doc_tag} was updated");
        }
    }

    /**
     * This function will hide the default Account field for this Jurisdiction
     *
     * @return true. Always return true
     */
    public function hideAccountField()
    {
        return true;
    }

    /**
     * We hide loss limit
     *
     * @param $type
     * @return bool
     */
    public function hideRgRemoveLimit($type)
    {
        return $type == 'loss';
    }

    public function setNetDepositMonthLimit(DBUser $user, int  $net_deposit_limit_month)
    {
        $remote_brand = getRemote();
        $remote_user_id = $user->getRemoteId();
        $local_user_limit = rgLimits()->getLimit($user, 'net_deposit', 'month');
        $remote_user_limit = toRemote($remote_brand, 'getRemoteUserLimit', [$remote_user_id, 'net_deposit', 'month']);

        if (!empty($local_user_limit)) {
            $net_deposit_limit_month = $local_user_limit['cur_lim'];
        }

        if ($remote_user_id !== 0) {
            $action = 'change';
            $increased = null;
            $progress = $remote_user_limit['result']['progress'] ?? null;

            if (!is_null($progress)) {
                $remote_user_current_limit = toRemote(getRemote(), 'getCurrentLimit', [$remote_user_id, 'net_deposit', 'month']);
                $remote_user_currency = $remote_user_current_limit['result']['currency'] ?? null;

                if ($remote_user_current_limit['success'] && $remote_user_currency !== $user->getCurrency()) {
                    $progress = chg($remote_user_current_limit['result']['currency'], $user->getCurrency(), $progress, 1);
                }
            }

            rgLimits()->changeLimit($user, 'net_deposit', $net_deposit_limit_month, 'month', [], $action, $increased, false, $progress);
        }
    }

    /**
     * Handle document status change for a user.
     *
     * @param $user_id
     * @param $document_type
     * @param $status
     * @return void
     */
    public function onDocumentStatusChange($user_id, $document_type, $status): void
    {
        $user = cu($user_id);
        $remote_user_id = $user->getRemoteId();
        $local_brand = getLocalBrand();
        if (!$remote_user_id) {
            return;
        }

        $doc_type_setting_name = Phive('Dmapi')->getSettingNameForDocumentType($document_type);

        // Check if the document type should be synced, CDD is checked, and a setting name exists
        if ($doc_type_setting_name) {
            linker()->updateDocumentSetting($user, $doc_type_setting_name, $status, $local_brand);
            if ($user->shouldSyncCDDStatus()) {
                $user->updateCDDFlagOnDocumentStatusChange();
                $user->logCDDActions("Document {$document_type} was changed");
            }
        }
    }

    /**
     * Block user If one of required documents is deleted
     * Synchronize document statuses across brands if 2k threshold triggered
     *
     * @param        $user
     * @param string $document_type
     *
     * @return void
     */
    public function onDeleteDocument($user, string $document_type): void
    {
        $remote_user_id = $user->getRemoteId();
        $local_brand = getLocalBrand();
        if (!$remote_user_id) {
            return;
        }

        $doc_type_setting_name = Phive('Dmapi')->getSettingNameForDocumentType($document_type);
        if ($doc_type_setting_name) {
            linker()->updateDocumentSetting($user, $doc_type_setting_name, Dmapi::STATUS_REQUESTED, $local_brand);
            if ($user->shouldSyncCDDStatus()) {
                $user->updateCDDFlagOnDocumentStatusChange();
                $user->logCDDActions("Document {$document_type} was deleted");
            }

        }
    }

    /**
     * Synchronize document statuses on document expiry across brands if 2k threshold triggered
     *
     * @param        $user
     * @param array $document
     * @return void
     */
    public function onDocumentExpired($user, array $document): void
    {
        $remote_user_id = $user->getRemoteId();
        $local_brand = getLocalBrand();
        if (!$remote_user_id) {
            return;
        }

        $tag = $document["attributes"]['tag'];
        $status = $document["attributes"]["status"];

        $doc_type_setting_name = Phive('Dmapi')->getSettingNameForDocumentType($tag);
        if ($doc_type_setting_name) {
            linker()->updateDocumentSetting($user, $doc_type_setting_name, $status, $local_brand);
            if ($user->shouldSyncCDDStatus()) {
                $user->updateCDDFlagOnDocumentStatusChange();
                $user->logCDDActions("Document {$tag} was expired");
            }
        }

    }

    /**
     * Synchronize document statuses on document processing across brands if 2k threshold triggered
     *
     * @param        $user
     * @param array $document
     *
     * @return void
     */
    public function onDocumentProcessing($user, array $document): void
    {
        $remote_user_id = $user->getRemoteId();
        $local_brand = getLocalBrand();
        if (!$remote_user_id) {
            return;
        }

        $tag = $document["attributes"]['tag'];
        $status = $document["attributes"]["status"];

        $doc_type_setting_name = Phive('Dmapi')->getSettingNameForDocumentType($tag);
        if ($doc_type_setting_name) {
            linker()->updateDocumentSetting($user, $doc_type_setting_name, $status, $local_brand);
            if ($user->shouldSyncCDDStatus()) {
                $user->updateCDDFlagOnDocumentStatusChange();
                $user->logCDDActions("Document {$tag} processing");
            }
        }

    }

    /**
     * return default as empty string
     *
     * @param string|null $key
     * @return string
     */
    public function shouldGetInputName(?string $key): string
    {
        /*
         * map value will be the name attr value of input field
         * and for the same we have a validation in registration_new.js
         */
        $field_name_mapping = [
            'zipcode'    => 'zipcode_min_max'
        ];
        return $field_name_mapping[$key] ?? parent::shouldGetInputName($key);
    }

    /**
     * @param DBUser $user
     * @return void
     */
    public function onRealityCheckRefused(DBUser $user): void
    {
        $setting = 'refused-reality-check';
        if ($user->hasSetting($setting)) {
            return;
        }

        $user->setSetting($setting, phive()->today());
    }

    /**
     * Get the Net Deposit Limit from affordability check status.
     * NDL returned in user's currency along with description
     *
     * @param DBUser $user
     * @return array
     */
    public function getUserNDLFromAffordabilityCheck(DBUser $user): array
    {
        $cndl = -1;
        $description = '';
        $beBettor_status = lic('getLatestAffordabilityStatusOfUser', [$user->getId()], $user);
        if (!empty($beBettor_status)) {
            $users_currency = $user->getCurrency();
            $affordability_score_ndl_map = $this->getLicSetting('affordability_score_ndl_map');
            $cndl = $affordability_score_ndl_map[$beBettor_status];
            $cndl = mc($cndl, $users_currency);
            $description = "BeBettor limit. BeBettor score:{$beBettor_status}, BeBettor NDL:{$cndl}.";
        }

        return [
            'cndl' => $cndl,
            'description' => $description,
        ];
    }


    /**
     * Registration step 2 setup
     *
     * @return array
     */
    public function registrationStep2Fields(): array
    {
        if (phive()->isMobile()) {
            return [
                'left' => ['firstname', 'lastname', 'address', 'zipcode', 'city', 'nationality', 'bonus_code', 'currency', 'preferred_lang'],
                'right' => ['birthdate', 'sex', 'email_code', 'eighteen']
            ];
        }
        return [
            'left' => ['firstname', 'lastname', 'address', 'zipcode', 'city', 'nationality', 'preferred_lang', 'bonus_code'],
            'right' => ['birthdate', 'currency', 'sex', 'email_code', 'eighteen']
        ];
    }

    /**
     * Makes possible to disable prefilling of a Nationality Value
     * @return string
     */
    public function getNationalityValue(): string
    {
        return '';
    }

    /**
     * Get an array of excluded language select options for registration step 2
     * @return array
     */
    public function getExcludedRegistrationLanguages()
    {
        $defaultExcludedLanguages = ['sv', 'da', 'it'];

        $additionalLanguages = $this->getLicSetting('excluded_languages');

        if (!empty($additionalLanguages)) {
            $excludedLanguages = array_merge($defaultExcludedLanguages, $additionalLanguages);
        } else {
            $excludedLanguages = $defaultExcludedLanguages;
        }

        return $excludedLanguages;
    }

    /**
     * Get the config value of the welcome deposit bonus for GB
     * @return array
     */
    public function getFirstDepositBonus()
    {
        return phive('Bonuses')->getBonus(phive('Config')->getValue('license-gb', 'first-deposit-bonus-id', 0));
    }

    /**
     * @return array
     */
    public function getIndustries($user = null): array
    {
        return ($this->getIndustryServiceInstance($user))->getIndustryList('gb');
    }

    /**
     * @return array
     */
    public function getOccupations(string $industry, $user): array
    {
        return ($this->getOccupationService($user))->getOccupationsInSelectedIndustry('gb', $industry);
    }


    public function hasViewedOccupationPopup($user): bool
    {
        return $user->getSetting('updated-occupation-data');
    }

    /**
     * When the NDL from a third-party check is greater than the current NDL,
     * we should NOT increase it automatically. If the result is less than the current NDL,
     * the NDL should be decreased automatically.
     *
     * @param DBUser $user
     * @param int    $ndl
     *
     * @return int
     */
    protected function alignNetDepositLimit(DBUser $user, int $ndl): int
    {
        $current_ndl = rgLimits()->getLimit($user, 'net_deposit', 'month');

        if (empty($current_ndl['cur_lim']) || $ndl < $current_ndl['cur_lim']) {
            return $ndl;
        }

        return (int)$current_ndl['cur_lim'];
    }

    /**
     * Adds comments to the customer's profile once per calendar month when they attempt to deposit
     * after reaching their CNDL.
     *
     * @param DBUser $user
     * @return void
     */
    public function addCommentWhenCNDLReached(DBUser $user): void
    {
        $ndl_reached_comment_setting = 'ndl_reached_comment_at';

        $comment_date = $user->getSetting($ndl_reached_comment_setting);

        if ($comment_date && Carbon::parse($comment_date)->month === Carbon::now()->month) {
            return;
        }

        $comment = "We have informed customer that we have restricted their deposits for the rest of their rolling period to protect from financial harm. Customer can request the limit to be increased by submitting supporting evidence for their affordability.";
        $user->addComment($comment, 0, 'automatic-flags');

        $comment = "We have restricted customers deposit for the rest of the current period. Customer will be able to deposit again after their rolling period is over. Customer can request higher limit by submitting evidence that they can afford it.";
        $user->addComment($comment, 0, 'rg-action');

        $user->refreshSetting($ndl_reached_comment_setting, Phive()->hisNow());
    }

    /**
     * When the user externally self excludes through Gamstop, lower their NDL
     *
     * @param DBUser $user
     * @return void
     */
    public function lowerNDLOnExternalSelfExclusion(DBUser $user): void
    {
        $current_ndl = lic('getUserMonthNetDepositLimit', [$user], $user);
        $external_exclusion_ndl = (int)$this->getLicSetting('external_exclusion_net_deposit_limit')['month'];
        $new_ndl = mc($external_exclusion_ndl, $user->getCurrency());
        $tag = 'external_self_exclusion';
        if ($current_ndl > $new_ndl) {
            lic('updateUserMonthNetDepositLimit', [$user, $new_ndl, $tag], $user);
            phive('UserHandler')->logAction(
                $user,
                "NDL lowered from {$current_ndl} to {$new_ndl} due to external self exclusion",
                $tag
            );
        }
    }

    /**
     * Adds comment when user NDL is updated based on SOWd NDL
     *
     * @param DBUser $user
     * @param string $new_ndl
     * @param string $old_ndl
     * @return void
     */
    public function addCommentOnSowdNdlUpdate(DBUser $user, string $new_ndl, string $old_ndl): void
    {
        $comment = "We interacted with the user to check their affordability via a source of wealth declaration. While comparing the SOWd with the current BeBettor limit, we noticed a mismatch and a new net deposit threshold has been set to {$new_ndl} from previous {$old_ndl}";
        $user->addComment($comment, 0, 'automatic-flags');
    }
}
