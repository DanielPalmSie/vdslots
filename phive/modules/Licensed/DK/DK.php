<?php

use DBUserHandler\DBUserRestriction;
use Videoslots\RgLimits\Builders\Locks\LockInterface;

require_once 'Rofus.php';
require_once 'Safe/SAFE.php';
require_once 'LoginTrait.php';
require_once __DIR__ . '/../Traits/ResponsibleGamblingTrait.php';

class DK extends Licensed
{
    use LoginTrait;
    use Rofus;
    use ResponsibleGamblingTrait;

    // Used by Rofus
    const FULL_SO_DATE_FORMAT = 'Y-m-d\TH:i:s.vP';

    /**
     * @var SAFE
     */
    private SAFE $safe;

    public const FORCED_LANGUAGE = 'da';

    public string $ext_exclusion_name = 'rofus';

    public function __construct()
    {
        parent::__construct();

        $this->safe = new SAFE(
            $this->getIso(),
            $this->getAllLicSettings()
        );
    }

    public $personal_number_length = 10;

    /**
     * @var array
     */
    protected array $fields_to_save_into_users_settings = [
        'nationality'
    ];

    public function getRgIndefiniteLockDays(){
        return 365;
    }

    /**
     * We don't support custom affiliate bonus codes
     *
     * @return bool
     */
    public function blockAffiliateBonusCode()
    {
        return true;
    }

    /**
     * If set to true all the categories on "gamebreak 24" will be checked by default
     *
     * @return bool
     */
    public function preselectGamebreak24()
    {
        return true;
    }

    /**
     * One year is considered "permanent".
     *
     * @return array
     */
    public function getSelfExclusionTimeOptions()
    {
        return [1, 30, 183, 365, 730, 1095, 1825];
    }

    public function getSelfExclusionConfirmMessage(bool $translate = true)
    {
        $alias = 'exclude.rofus.end.info.html';

        return $translate ? t($alias) : $alias;
    }

    public function getSelfExclusionExtraInfo()
    {
        return 'exclude.rofus.account.info.html';
    }

    /**
     * @return string
     */
    public function getSelfExclusionRecommendation()
    {
        return t('gamble.too.much.description.selfexclude.dk');
    }

    /**
     * @param null $user
     * @param string $gamstop_res
     * @return bool
     */
    public function hasExternalSelfExclusion($user = null, $gamstop_res = '')
    {
        return $this->hasExternalSelfExclusionCommon($user, $gamstop_res);
    }

    public function regGetDataFromNationalId()
    {
        return true;
    }

    public function disableBoPwdChange()
    {
        return true;
    }

    public function handleGameVersion($session_id, $ins)
    {
        parent::handleGameVersionCommon($session_id, $ins);
    }

    public function hideJackpots()
    {
        return true;
    }

    public function getFirstDepositBonus()
    {
        return phive('Bonuses')->getBonus(phive('Config')->getValue('license-dk', 'first-deposit-bonus-id', 0));
    }

    /**
     * Needed to display password field during registration step1, it needs to be filled it by the user to allow him to login in the future without MitID.
     * For other countries (SE) if we have an external verification we don't display the password field.
     * @return bool
     */
    public function forceRegStep1Password()
    {
        return true;
    }

    /**
     * @param bool $translate
     *
     * @return mixed
     */
    public function getRegistrationMessage(bool $translate = true)
    {
        $alias = 'registration.with.verification.method.dk';

        return $translate ? t($alias) : $alias;
    }

    public function validateRegFields()
    {
        return ['email', 'country', 'mobile', 'personal_number'];
    }

    public function personalNumberMessage($translate = true)
    {
        $alias = 'register.personal.number.description.dk';

        return $translate ? t($alias) : $alias;
    }

    /**
     * Check user provided data similarity with external data:
     * - NOT MATCH: we mark the customer as "temporal_account"
     *
     * @param DBUser $u_obj
     * @see Licensed::onRegistrationEnd()
     */
    public function onRegistrationEnd(DBUser $u_obj)
    {
        $res = $this->checkSimilarityWithExternalData($u_obj, false);

        if ($res === false) {
            $u_obj->setSetting('temporal_account', 1);
        }

        parent::onRegistrationEnd($u_obj);
    }

    /**
     * Check if a temporal account should be restricted after 30 days
     *
     * @param DBUser $u_obj
     * @return mixed
     */
    public function shouldBeRestricted($u_obj)
    {
        if ($u_obj->isTemporal() && $u_obj->registerSince() > 30) {
            return DBUserRestriction::TEMPORAL_ACCOUNT;
        }
        return false;
    }

    /**
     * @param DBUser $u_obj
     *
     * @return mixed
     */
    public function hasSpecialLimit($u_obj, $limit, $amount)
    {
        if ($limit == 'deposit' && $u_obj->isTemporal() && !$u_obj->isVerified()) {
            $sums = phive('Cashier')->getDeposits('', '', $u_obj->getId(), '', 'total');
            if (($sums['amount_sum'] + $amount) > $this->getLicSetting('temporal_account_deposit_limit')) {
                return true;
            }
        }
        return false;
    }

    public function customLoginTop($context){
        $mit_id_disabled = $this->getLicSetting('mit_id_disabled') === true;
        ?>
            <div id="lic-mbox-btn-show-custom" class="lic-mbox-btn lic-mbox-btn-inactive <?= $mit_id_disabled ? 'lic-mbox-btn-mit-id--disabled' : ''; ?>" style="float: right; width: 100%; margin-top: 0;" data-mit-id-active="<?= $mit_id_disabled ? 'false' : 'true'; ?>">
                <span><?php et('verification.method2.dk') ?></span>
                <?php if($mit_id_disabled): ?>
                    <span class="lic-mbox-label-info-mit-id--unavailable" style="line-height: 8px;"><?php et('mitid.currently.unavailable') ?></span>
                <?php endif ?>
            </div>
        <br />
<!--        --><?// endif;?>

        <div id="lic-mbox-btn-show-default" class="lic-mbox-btn lic-mbox-btn-inactive " style="margin-top: <?= $mit_id_disabled ? '60px' : '50px'; ?>; width: 100%">
            <span><?php et('email.and.password') ?></span>
            </div>
            <div class="clearfix"></div>

    <?php }

    public function customLoginBottom($context) {
        loadCss("/diamondbet/css/" . brandedCss() . "new-registration.css");
        ?>

        <div id="nid_field_msg" class="info-message" style="display:none">
            <?= empty($msg = lic('personalNumberMessage')) ? t('register.personal_number.error.message') : $msg; ?>
        </div>

        <?php $this->customMitIdLoginBottom($context); ?>

        <script>
            $(".nid-email").val(localStorage.getItem('nid'));

            if ((localStorage.getItem('nid') || '').toString().length > 0) {
                $("#remember_nid").attr('checked', 'checked');
            }

            $("#remember_nid").click(function () {
                if (!$(this).is(":checked")) {
                    localStorage.removeItem('nid');
                    return;
                }

                var curNid = $(".nid-email").val().toString();
                if(licFuncs.validateNid(curNid)){
                    localStorage.setItem('nid', curNid);
                }
            });

            if (!empty(httpGet('nid'))) {
                $(".nid-email").val(httpGet('nid'));
                if ($("#remember_nid").is(":checked")) {
                    $("#remember_nid").click();
                }
            }

            if ($("#nid-field").length > 0) {
                $("#nid-field").click(function() {
                    $("#nid_field_msg").hide();
                })
            }
        </script>
        <?php
    }

    public function customMitIdLoginBottom($context) {
        loadCss("/diamondbet/css/". brandedCss() ."new-registration.css");
        if ($context === 'login'): ?>
            <div class="registration-container">
                <label for="email">
                    <input id="email" class="mid-email input-normal required email" name="email" type="email" autocapitalize="off" autocorrect="off" placeholder="Your Email" autocomplete="email" value="">
                </label>
            </div>
        <? else: ?>
            <input id="nid-field" type="number" placeholder="<?php echo $this->getNidPlaceholder() ?>" class="nid-email input-normal lic-mbox-input" />
            <div id="nid_field_msg" class="info-message" style="display:none">
                <?= empty($msg = lic('personalNumberMessage')) ? t('register.personal_number.error.message') : $msg; ?>
            </div>
        <? endif;?>

        <p class="remember_midID"><input type="checkbox" id="remember_midID"><?php et('bankid.login.remember') ?></p>
        <div id="mid_field_msg" class="error" style="display: none;"><? et('mitid.login.username.field.required.error');?></div>
        <div id="second-register-button">
            <br clear="all"/>
            <div class="register-button" style="width: auto; border-radius: 5px;">
                <div id="mitid-verify-btn" class="register-big-btn-txt register-button-second_denmark" <?= licSetting('mit_id_disabled') === true ? '' : 'onclick="licFuncs.verificationByMitID()"' ?>>
                    <span style="flex-grow: 1"> <?php et('mitid.login.button') ?> </span>
                    <img class="register-button-second_denmark-img" src="<?php echo lic('imgUri', ['dk-mitid.png']) ?>"/>
                </div>
                <?php if(licSetting('mit_id_disabled') === true): ?>
                    <span style="color: red"><?php et('mitid.currently.unavailable') ?></span>
                <?php endif ?>
            </div>
        </div>
        <script>
            $(".mid-email").val(localStorage.getItem('mid'));

            if ((localStorage.getItem('mid') || '').toString().length > 0) {
                $("#remember_midID").attr('checked', 'checked');
            }

            $("#remember_midID").click(function () {
                if (!$(this).is(":checked")) {
                    localStorage.removeItem('mid');
                    return;
                }

                var curNid = $(".mid-email").val().toString();
                if(licFuncs.validateNid(curNid)){
                    localStorage.setItem('mid', curNid);
                }
            });

            if (!empty(httpGet('mid'))) {
                $(".mid-email").val(httpGet('nid'));
                if ($("#remember_midID").is(":checked")) {
                    $("#remember_midID").click();
                }
            }

            if ($("#nid-field").length > 0) {
                $("#nid-field").click(function() {
                    $("#nid_field_msg").hide();
                })
            }
        </script>
        <?php
    }

    /**
     * @return \Videoslots\RgLimits\Builders\Locks\LockInterface
     */
    public function createLockBuilder(): LockInterface
    {
        return new \Videoslots\RgLimits\Builders\Locks\DK();
    }

    /**
     * @deprecated
     */
    function rgLockSection($box){ ?>
        <?php dbInput('lock-hours', '', 'text', 'input-normal') ?>
        <br clear="all" />
        <br clear="all" />
        <strong>
            <?php et("or") ?>
        </strong>
        <br clear="all" />
        <br clear="all" />
        <input id="dk-indefinite" type="checkbox" name="indefinitely" value="" />
        <?php et("permanently.self-excluded") ?>
        <?php
        return true;
    }

    /**
     * @param DBUser $user
     * @return mixed
     */
    function lookupNid($user) {
        return $this->getDataFromNationalId($user->getCountry(), $user->getNid());
    }

    /**
     * Checks if we should show the intended gambling popup or not.
     * @param DBUser $user User, default null, if null we try and fetch from the session.
     *
     * @return bool True if we show, false otherwise.
     */
    function doIntendedGambling($user = null): bool
    {
        $user = $user ?? cu();
        if(empty($user)){
            return false;
        }
        return empty($user->getSetting('intended_gambling'));
    }

    /**
     * Used by "user-service-laravel" and "new game mode"
     * return all the data needed to display the strip - NO HTML
     */
    public function getBaseGameParams($user = null) {
        $licensing_strip_settings = $this->getLicSetting('licensing_strip');

        $rofusImg = $this->imgUri("rofus.svg");
        $stopSpilletImg = isMobileSite()? $this->imgUri("stopspillet40.svg") : $this->imgUri("stopspillet60.svg");

        $res = [
            'authority' => [
                'url' => $licensing_strip_settings['authority_link'],
                'img' => $this->imgUri("DGA-Icon.png")
            ],
            'self_exclusion' => [
                'url' => $licensing_strip_settings['self_exclusion_link'], // rofus
                'img' => $rofusImg
            ],
            'self_assessment' => [
//                'url' => $this->getGamTestUrl($user), // This needs to be open in an iframe
                'url' => $licensing_strip_settings['self_assessment_link'], // stopspillet
                'img' => $stopSpilletImg,
                'overlay_header' => t('gamtest.box.headline'),
            ],
            'account_limits' => [
                'url' => $this->getRespGamingUrl($user),
                'img' => $this->imgUri("RG-Icon.png")
            ]
        ];
        if (!empty($user)) {
            $res['elapsed_session_time'] = $this->getSessionLength($user);
        } elseif(phive()->isMobile()) {
            // only for mobile devices, when the user is logged out, we need to display the clock with the current time
            $res['show_clock'] = true;
        }
        if($this->getLicSetting('rg-over_age')) {
            $res['over_age'] = [
                'url' => '',
                'img' => $this->imgUri("18+W.png")
            ];
        }

        return $res;
    }

    public function doDisplayLoggedTime(){
        return $this->getLicSetting('rg-buttons') &&  $this->getLicSetting('rg-timer')
            && (isLogged() || (!isLogged() && phive()->isMobile()));
    }

    /**
     * The time the RC should use as starting point when launching a game
     * For DK we are using the time since the user logged in to the casino
     * If player is logged out we use this to show the clock on mobile.
     *
     * @param DBUser $user
     * @return bool|int
     */
    public function rcElapsedTime($user = null)
    {
        $user = cu($user);

        if (!empty($user)){
            return $this->getSessionLength($user);
        }
        return phive()->isMobile() ? 'simple_clock' : false;
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
                $this->rgLogo('rg-mobile-top', $lang);
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
            <a href="<?= $licensing_strip_settings['authority']['url'] ?>" target="_blank" rel="noopener noreferrer">
                <img src="<?= $licensing_strip_settings['authority']['img'] ?>" id="vs-sticky-bar-image__authority" class="vs-sticky-bar__image">
            </a>
            <a href="<?= $licensing_strip_settings['self_exclusion']['url'] ?>" target="_blank" rel="noopener noreferrer">
                <img src="<?= $licensing_strip_settings['self_exclusion']['img'] ?>" id="vs-sticky-bar-image__self-exclusion" class="vs-sticky-bar__image">
            </a>
            <a href="<?= $licensing_strip_settings['self_assessment']['url'] ?>" target="_blank" rel="noopener noreferrer">
                <img src="<?= $licensing_strip_settings['self_assessment']['img'] ?>" id="vs-sticky-bar-image__self-assessment" class="vs-sticky-bar__image">
            </a>
        </div>
        <?php
    }

    /*****************************
     * SAFE RELATED FUNCTIONS    *
     *****************************/

    /**
     * Creates kasinospil reports. Should be run on a cron every 5 min.
     * @throws Exception
     */
    public function onEvery5MinReporting()
    {
        if (phive('Distributed')->getSetting('safe_main_brand') !== true) {
            return;
        }
        $is_regeneration_running = $this->safe->isRegenerationRunning();
        if (! $is_regeneration_running) {
            $this->safe->cronCloseTamperToken();
            $this->safe->exportData(SafeConstants::KASINO_SPIL, $this->getIso());
        }

        if(!$this->isYesterdayEoDGenerated()){
            $yesterday = phive()->yesterday();
            $this->generateEoDReport($yesterday);
        }
    }

    /**
     * Custom reports from third-parties (Game Providers) for kasinospil reports.
     *
     * @param $game_session
     * @param $report_type
     * @return bool
     * @throws Exception
     */
    public function customEvery5Min($game_session, $report_type)
    {
        return $this->safe->generateCustomKasinoSpilReport($game_session, $report_type);
    }

    /**
     * Custom end of day reports from third-parties (Game Providers).
     *
     * @param $end_of_day_data
     * @param $report_key
     * @return bool
     */
    public function customEndOfDayReports($end_of_day_data, $report_key)
    {
        return $this->safe->customEndOfDayReports($end_of_day_data, $report_key);
    }

    /**
     * Try to generate the pending custom SAFE reports.
     */
    public function generatePendingReportsForCustomReports()
    {
        $kasino_spil_reports_keys = SafeConstants::generateProvidersReportType(
            SafeConstants::KASINO_SPIL,
            $this->getLicSetting('excluded_providers')
        );

        $end_of_day_reports_keys = SafeConstants::generateProvidersReportType(
            SafeConstants::END_OF_DAY,
            $this->getLicSetting('excluded_providers')
        );

        foreach (array_merge($kasino_spil_reports_keys, $end_of_day_reports_keys) as $key) {
            $this->safe->generatePendingReports($key);
        }
    }

    /**
     * Generates everyday report. Should be called once per day via cron.
     *
     * @param string $date
     */
    public function generateEoDReport($date = '')
    {
        if (phive('Distributed')->getSetting('safe_main_brand') !== true) {
            return;
        }
        $this->safe->exportData(SafeConstants::END_OF_DAY, $this->getIso(), $date);
    }

    /**
     * Check if previous day EoD has been generated
     *
     * @return boolean
     */
    public function isYesterdayEoDGenerated()
    {
        $yesterday = phive()->yesterday();

        $result = phive('SQL')->loadArray("
            SELECT
            1
            FROM
                external_regulatory_report_logs errl
            WHERE
                regulation = 'SAFE'
                AND report_type = 'EndOfDay'
                AND report_data_from = '{$yesterday}'");
        return !empty($result);
    }

    /**
     * Triggered by cron command
     *
     * @param string $date
     *
     * @return void
     * @throws Exception
     */
    public function onEveryday($date = ''): void
    {
        $this->onEveryDayGameVersion('DK');
        $this->runExternalSelfExclusionChecks();
    }

    /**
     * When we have problems in data this method will regenerate the data for a specific token.
     *
     * @param $token_id
     * @param string $run
     * @param string $spilHjemmeside
     * @param array $regenerate_requested_info
     * @return string|false
     */
    public function regenerateData($token_id, $run = 'all', $spilHjemmeside = 'all', $regenerate_requested_info = [])
    {
        $this->safe->brand_SpilHjemmeside = $spilHjemmeside;

        return $this->safe->regenerateData($token_id, $run, 'all', $regenerate_requested_info);
    }

    /**
     * Use to regenerate SAFE data that are generated from the old logic.
     *
     * @param array $regenerate_requests
     *
     * @option string $regenerate_requests[]['from']
     * @option string $regenerate_requests[]['to']
     * @option string $regenerate_requests[]['type']
     * @option string $regenerate_requests[]['end_of_day_id']
     * @option string $regenerate_requests[]['bet_count']
     * @option string $regenerate_requests[]['bet_amount']
     * @option string $regenerate_requests[]['win_amount']
     *
     * @param string $calculate_rollbacks
     *
     * @return array|false
     * @throws Exception
     */
    public function regenerateDataByDateRange(
        array $regenerate_requests,
        string $calculate_rollbacks = 'all'
    ) {
        $regeneration_started = $this->safe->regenerateRunning();
        if (! $regeneration_started) {
            echo "Regeneration failed.\n";
            return false;
        }

        $return = [];

        foreach ($regenerate_requests as $regenerate_request_info) {
            if (empty($regenerate_request_info['from']) || empty($regenerate_request_info['to'])) {
                echo "Please select date ranges";
                return false;
            }

            $type = empty($regenerate_request_info['type']) ? 'all' : $regenerate_request_info['type'];
            $end_of_day_id = empty($regenerate_request_info['end_of_day_id']) ? '' : $regenerate_request_info['end_of_day_id'];

            $return[$regenerate_request_info['from']] = $this->safe->regenerateDataByDateRange(
                $regenerate_request_info['from'],
                $regenerate_request_info['to'],
                $type,
                $end_of_day_id,
                $calculate_rollbacks,
                $regenerate_request_info
            );
        }

        $this->safe->closeRegenerate();

        return $return;
    }

    /**
     *
     * Regenerate specific files of a token using sequence number
     *
     * @param array $sequences The sequence numbers of the files to be regenerated
     * @param string $token_id The token of the files
     * @param string $calculate_rollbacks The report types for which rollback should be calculated
     *
     * @return string|false The new token, or false on error
     * @throws Exception
     */
    public function regenerateDataBySequence(
        array $sequences,
        string $token_id,
        string $calculate_rollbacks = 'all'
    ) {
        $regeneration_started = $this->safe->regenerateRunning();
        if (! $regeneration_started) {
            echo "Regeneration failed.\n";
            return false;
        }

        sort($sequences);

        $result = $this->safe->regenerateDataBySequence(
            $sequences,
            $token_id,
            $calculate_rollbacks
        );

        $this->safe->closeRegenerate();

        return $result;
    }

    /**
     * This function is intended for use when the process is locked because of some server issue.
     * It will create the missing reports and leave the cursor ready to continue
     *
     * @param string|null $new_timestamp new timestamp for the cursor, default=now
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function generateReportsAndUnlock(?string $new_timestamp = null, bool $delete_pending_rollbacks = true): void
    {
        if ($new_timestamp) {
            $end_date = new DateTimeImmutable($new_timestamp);
        } else {
            $end_date = new DateTimeImmutable();
        }
        $end_stamp = $end_date->format('Y-m-d H:i:s');

        $this->safe->extractParams();
        $current = new DateTime($this->safe->getCursor());

        if($end_date <= $current){
            throw new InvalidArgumentException('The new timestamp should be after the current cursor');
        }

        echo "Moving cursor into the future, current cursor was: {$current->format('Y-m-d H:i:s')}".PHP_EOL;

        // set the cursor on the future, so it doesn't run for now,
        $future_stamp = $end_date->modify('+1 year')->format('Y-m-d H:i:s');
        $this->safe->setCursor($future_stamp);

        // update secondary cursor
        $secondary_params = phive()->getMiscCache(SAFE::SAFE_CURSOR_SECONDARY);
        $secondary_params = json_decode($secondary_params, true, 512, JSON_THROW_ON_ERROR);
        $secondary_params[0]['mrvegas'] = $future_stamp;
        phive()->miscCache(SAFE::SAFE_CURSOR_SECONDARY, json_encode($secondary_params, JSON_THROW_ON_ERROR), true);


        $calculate_rollbacks = 'none';
        if($delete_pending_rollbacks){
            //Delete pending rollbacks, they will be included in the generation
            phive('SQL')->delete('misc_cache', ['id_str' => 'SAFE_pending_cancel']);
            $calculate_rollbacks = 'all';
        }

        //remove flags so we can work
        $this->safe->removeReportRunning();
        $this->safe->closeRegenerate();

        $ranges = [];
        while ($current < $end_date) {
            $ranges[] = [
                'from' => $current->format('Y-m-d H:i:s'),
                'to' => $current->modify('23:59:59')->format('Y-m-d H:i:s')
            ];
            $current->modify('+1 second');
        }

        $ranges[count($ranges) - 1]['to'] = $end_stamp;
        $ranges[count($ranges) - 1]['type'] = SafeConstants::KASINO_SPIL; //For the last day, we don't run EndOfDay


        echo 'Starting generation'.PHP_EOL;

        $this->regenerateDataByDateRange($ranges, $calculate_rollbacks);

        echo 'Generation finished'.PHP_EOL;


        //set cursor to new date, so it continues normally
        $this->safe->setCursor($end_stamp);

        // update secondary cursor
        $secondary_params = phive()->getMiscCache(SAFE::SAFE_CURSOR_SECONDARY);
        $secondary_params = json_decode($secondary_params, true, 512, JSON_THROW_ON_ERROR);
        $secondary_params[0]['mrvegas'] = $end_stamp;
        phive()->miscCache(SAFE::SAFE_CURSOR_SECONDARY, json_encode($secondary_params, JSON_THROW_ON_ERROR), true);

        echo 'OK'.PHP_EOL;
    }

    /**
     * This method created a Cancel Report for SAFE.
     *
     * @param $user_game_session_id
     * @param $user_id
     * @param $table
     * @param $amount
     * @param array $session : session from secondary brand, just using when a rollback in the secondary brand and se cancell all session and regenerate a new one
     */
    public function cancelReportSession($user_game_session_id, $user_id, $table, $amount, $session = [])
    {
        $this->safe->cancelReportSession($user_game_session_id, $user_id, $table, $amount, $session);
    }

    /**
     * @param $country
     * @param $date
     * @param $type
     * @param $cursor_regenerate
     * @param $secondary_cursor
     * @param $forced_values
     * @return array|mixed
     */
    public function getDataReportFromSecondaryBrand($country, $date, $type, $cursor_regenerate, $secondary_cursor, $forced_values)
    {
        return $this->safe->getDataReportFromSecondaryBrand($country, $date, $type, $cursor_regenerate, $secondary_cursor, $forced_values);
    }



    /**
     * Closes the token and opens new one.
     *
     * @param bool $generate_new_token
     * @param string $token_id
     */
    public function closeToken(bool $generate_new_token = true, $token_id = "")
    {
        $this->safe->extractParams();
        if ($token_id) {
            if ($this->safe->doesTokenHaveReports($token_id)) {
                $this->safe->closeUsedTokenByID($token_id);
                return;
            }

            $this->safe->closedUnusedTokenByID($token_id);
        } else {
            $this->safe->closeTamperToken($generate_new_token);
        }
    }

    /**
     * @param DBUser $user
     * @return bool
     */
    public function needsNid($user): bool
    {
        if ($user->isTestAccount()) {
            return false;
        }
        return !$user->hasAttr('nid');
    }

    /**
     * Start the verification process for users who don't have nid stored in DB
     *
     * @param DBUser $user
     * @param bool $is_api
     */
    public function startMissingNidVerification($user, bool $is_api = false)
    {
        if (empty($user)) {
            return;
        }
        if(!$is_api) {
            $_SESSION['verify_username'] = $user->getUsername();
        }
    }

    /**
     * Detect if external verification was already completed
     *
     * @param array $data
     * @return bool
     */
    public function passedExtVerification($data = [])
    {
        return !empty($data['response']);
    }

    /**
     * Return the correct suffix in new registration
     *
     * @param $string
     * @param bool $translate
     *
     * @return mixed
     */
    public function getSuffixContext($string, bool $translate = true)
    {
        $alias = "$string.dk";

        return $translate ? t($alias) : $alias;
    }

    /**
     * For DK we are only interested on NID matching customers so we filter as well by country.
     *
     * @param DBUser $user
     * @param string $remote
     *
     * @return
     */
    public function matchInBrand($user, $remote)
    {
        return toRemote($remote, 'matchUser', [
            $user->getId(),
            'ByAttribute',
            ['attribute' => 'nid', 'user_data' => ud($user), 'jurisdiction' => $user->getJurisdiction()]
        ], 3);
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
     * This action is triggered when a DK player is logged in
     * In this case it verifies if the player has a temporal account, not verified and less than 30 days to upload
     * his documents to proof his identity, if those conditions are met, it shows up a pop up reminder
     *
     * @param DBUser $u_obj
     * @param bool $is_api
     */
    public function onLogin(DBUser $u_obj, bool $is_api = false)
    {
        parent::onLogin($u_obj);
        if ($is_api) {
            return;
        }

        $date_diff = $this->getTimeSinceRegistration($u_obj);

        if (!$_SESSION['account_verification_reminder']
            && $date_diff['days'] < $this->getDaysToProvideDocuments()
            && $u_obj->isTemporal()
            && !$u_obj->isVerified()) {
            $_SESSION['account_verification_reminder'] = true;
        }
    }

    /**
     * If there is no registration in progress, and we checked for the user documents are not verified we restrict the user
     *
     * @param DBUser $u_obj
     * @return bool
     */
    public function onSuccessfulLogin($u_obj)
    {
        $uh    = phive('UserHandler');
        if ($uh->doCheckRestrict($u_obj, function(){ return true; }) && $this->shouldBeRestricted($u_obj)){
            $u_obj->block();
            return false;
        }
        return true;
    }

    /**
     * Retrieves the information to be displayed in the reminder popup located in
     * modules/Licensed/html/account_verification_reminder.php
     * Where:
     * days_left => are the days the user has to upload his verification documents
     * paragraphs => an array with the paragraphs to be displayed in the popup
     *
     * @return array
     */
    public function accountVerificationData()
    {
        return [
            'days_left' => $this->getDaysToProvideDocuments(),
            'paragraphs' => [
                'acc.verification.documents.reminder.p1.dk',
                'acc.verification.documents.reminder.p2.dk'
            ]
        ];
    }

    /**
     * Get user_ids with nid keys
     *
     * @param array<string> $user_nids
     *
     * @return array<string, string>
     */

    public function getUserIdsByNids(array $user_nids): array
    {
        $in = phive('SQL')->makeIn($user_nids);
        $query = "
            SELECT
                u.id, u.nid
            FROM
                users AS u
            WHERE
                u.nid IN ($in)
        ";
        $users = phive('SQL')->shs()->load1DArr($query, 'id', 'nid');

        return $users === false ? [] : $users;
    }

    /**
     * @param User $user
     * @return void
     */
    public function onPasswordLogin(User $user): void
    {
        phive('UserHandler')->logAction($user->getId(), 'Standard login: email/password', 'logged_in');
    }

    /**
     * Return the correct value for reset stamp as specified by DGA
     *
     * @param string $time_span
     * @param string $type
     *
     * @return string|bool
     */
    public function getResetStamp($time_span, $type)
    {
        if ($type !== $this->rgLimits()::TYPE_DEPOSIT){
            return false;
        }

        $dateFormat = "Y-m-d 23:59:59";
        switch ($time_span) {
            case 'day':
                return date($dateFormat);
            case 'week':
                return date($dateFormat, strtotime('sunday this week'));
            case 'month':
                return date($dateFormat, strtotime('last day of this month'));
            default:
                return false;
        }
    }

    /**
     * We hide deposit limit
     *
     * @param $type
     * @return bool
     */
    public function hideRgRemoveLimit($type)
    {
        return $type == 'deposit';
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
     * Check for external self exclusions
     *
     * @return void
     */
    public function runExternalSelfExclusionChecks(): void
    {
        if ($this->getLicSetting('gamstop')['is_active'] !== true) {
            return;
        }

        $query = "
            SELECT
                u.*,
                us.user_id,
                IFNULL(TIMESTAMPDIFF(HOUR, us.value, NOW()),0) AS last_rofus_diff
            FROM
                users u
                LEFT JOIN users_settings us ON us.user_id = u.id AND setting = 'last-rofus-check'
            WHERE
                u.country = 'DK'
            GROUP BY us.user_id
            HAVING last_rofus_diff >= 23
        ";
        $users = phive('SQL')->shs()->loadArray($query);

        foreach (array_chunk($users, $max_number_of_users_per_list = 1000) as $list) {
            try {
                $blocked_user_ids = $this->getMarketingBlockedUsers($list);
                $this->updateGamstopStatusFromBulkRequest($list, $blocked_user_ids);
            } catch (\Exception $e) {
                error_log($e->getCode() . ':' . $e->getMessage());
            }
        }
    }

    /**
     * Update users setting with the result of bulkGamstop
     * - last-rofus-check: for daily check
     * - cur-rofus: for self-exclusion status
     *
     * @param array $list - list of users
     * @param array $res - bulk gamstop result contains an array of blocked user ids
     * @return void
     */
    public function updateGamstopStatusFromBulkRequest(array $list, array $res): void
    {
        foreach ($list as $elem) {
            $user = cu($elem['user_id']);

            if (in_array($elem['user_id'], $res)) {
                $gamstop_res = 'Y';
                $ext_excluded = $this->hasExternalSelfExclusion($user, $gamstop_res);
                if ($ext_excluded !== false) {
                    phive('UserHandler')->logoutUser($elem['user_id'], 'external self exclusion');
                }
            }
        }
    }
}
