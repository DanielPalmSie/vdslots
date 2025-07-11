<?php

require_once 'CA.php';

use CA\UserMigrator;
use GeoComply\GeoComplyTrait;
use Licensed\Traits\ServicesTrait;
use Videoslots\RgLimits\Builders\Locks\LockInterface;
use Videoslots\Mts\MtsClient;

require_once __DIR__ . '/../Traits/ResponsibleGamblingTrait.php';
require_once __DIR__ . '/../Traits/ServicesTrait.php';

class CAON extends CA
{
    use GeoComplyTrait;

    public const FORCED_LANGUAGE = 'en';
    public const FORCED_PROVINCE = 'ON';
    use ResponsibleGamblingTrait;
    use ServicesTrait;

    public function __construct()
    {
    }

    /**
     * Load JS files and optionally only load the path without rendering.
     *
     * @param bool $only_path Set to true to only load the path without rendering.
     * @return array|void Returns an array of JS files if $only_path is false.
     */
    public function loadJs($only_path = false)
    {
        if ($only_path) {
            return parent::loadJs($only_path);
        } else {
            parent::loadJs();
            $this->loadGeoComplyCSS();
            $this->loadGeoComplyJs();
        }
    }


    /**
     * @var array[]
     */
    protected $extra_registration_fields = [
            'step1' => [],
            'step2' => [],
    ];

    /**
     * @var array
     */
    protected array $fields_to_save_into_users_settings = [
        'occupation', 'industry', 'building', 'nationality'
    ];

    /**
     * Used by "user-service-laravel" and "new game mode"
     * return all the data needed to display the strip - NO HTML
     */
    public function getBaseGameParams($user = null)
    {
        $licensing_strip_settings = $this->getLicSetting('licensing_strip');
        $res = [
                'self_exclusion' => [
                        'url' => $licensing_strip_settings['self_exclusion_link'],
                        'img' => $this->imgUri("igaming-ontario.png")
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
                    'img' => $this->imgUri("19+W.png")
            ];
        }
        if ($this->getLicSetting('rg-buttons')) {
            return $res;
        }
        return false;
    }

    public function rgLogo($type = 'white', $extra_classes = '', $lang = null)
    {
        $user = cu();
        $licensing_strip_settings = $this->getBaseGameParams($user);
        $add_margin_bottom = isMobileSite() ? '' : 'margin-five-bottom';

        ?>
        <div class="rg-top__item rg-logo vs-sticky-bar__images rg-top-bar-ca <?php echo $extra_classes ?>" id="vs-sticky-bar__images">
            <a href="<?= $licensing_strip_settings['account_limits']['url'] ?>">
                <img src="<?= $licensing_strip_settings['account_limits']['img'] ?>" id="vs-sticky-bar-image__account-limits" class="vs-sticky-bar__image">
            </a>
            <a href="<?= $licensing_strip_settings['self_exclusion']['url'] ?>" target="_blank" rel="noopener noreferrer">
                <img src="<?= $licensing_strip_settings['self_exclusion']['img'] ?>" id="vs-sticky-bar-image__self-exclusion" class="vs-sticky-bar__image">
            </a>
        </div>
        <?php
    }

    public function topLogos($type = 'white', $extra_classes = '')
    {
        if ($this->showTopLogos()) {
            return phive()->ob(function () use ($extra_classes) {
                $this->rgLogo('','margin-four-top ' . $extra_classes);
            });
        }
        return false;
    }

    public function topMobileLogos($lang = null)
    {
        if ($this->getLicSetting('rg-buttons')) {
            return phive()->ob(function () use ($lang) {
                $this->rgLogo('', 'rg-mobile-top rg-mobile-top-ca', $lang);
            });
        }
        return false;
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
     * @param string $user_status
     *
     * @return bool
     */
    public function isActiveStatus(string $user_status): bool
    {
        return $user_status === UserStatus::STATUS_ACTIVE || $user_status === UserStatus::STATUS_UNDER_INVESTIGATION;
    }

    /**
     * Registration step 2 setup
     *
     * @return array
     */
    public function registrationStep2Fields(): array
    {
        if (!licSetting('require_main_province')) {
            return parent::registrationStep2Fields();
        }

        if (phive()->isMobile()) {
            return [
                'left' => ['firstname', 'lastname', 'address', 'building', 'zipcode', 'city', 'bonus_code', 'nationality', 'main_province', 'currency', 'preferred_lang'],
                'right' => ['birthdate', 'sex', 'email_code', 'legal_age', 'aml', 'pep_check']
            ];
        }
        return [
            'left' => ['firstname', 'lastname', 'address', 'building', 'zipcode', 'city', 'nationality', 'main_province', 'preferred_lang', 'bonus_code'],
            'right' => ['birthdate', 'currency', 'sex', 'email_code', 'legal_age', 'aml', 'pep_check']
        ];
    }

  /**
     * Registration step 2 resubmit setup
     *
     * @return array
     */
    public function registrationStep2FieldsMigration(): array
    {

        if (phive()->isMobile()) {
            return [
                'left' => ['firstname', 'lastname', 'address', 'building', 'zipcode', 'city', 'nationality', 'main_province', 'currency', 'preferred_lang'],
                'right' => ['birthdate', 'sex', 'legal_age', 'aml', 'pep_check']
            ];
        }
        return [
            'left' => ['firstname', 'lastname', 'address', 'building', 'zipcode', 'city', 'nationality', 'main_province', 'preferred_lang'],
            'right' => ['birthdate', 'currency', 'sex', 'legal_age', 'aml', 'pep_check']
        ];
    }


    /**
     * Make an external verification
     * For Ontario registration 2 checkboxes
     *
     * @param array $user_data
     *
     * @return array
     */
    public function extraValidationForStep2(array $user_data): array
    {
        $validation_errors = [];

        if ($user_data['pep_check'] != 'on' && $user_data['pep_check'] != 1) {
            $validation_errors['pep_check'] = 'not.checked';
        }

        if ($user_data['aml'] != 'on' && $user_data['aml'] != 1) {
            $validation_errors['aml'] = 'not.checked';
        }

        if ($user_data['legal_age'] != 'on' && $user_data['legal_age'] != 1) {
            $validation_errors['legal_age'] = 'not.checked';
        }


        return $validation_errors;
    }

    /**
     * Extra fields that should be passed to HistoryMessages for migrated users
     *
     * @param DBUser $user
     *
     * @return array
     */
    public function getUserMigrationExtraFields(DBUser $user): array
    {
        return [
            'migrated_cash_balance' => (int) $user->getBalance(),
            'migrated_booster_vault' => (int) phive('DBUserHandler/Booster')->getVaultBalance($user)
        ];
    }
    /**
     * @return array
     */
    public function getIndustries($user=null): array
    {
        return ($this->getIndustryServiceInstance($user))->getIndustryList('ca', 'industries_list');
    }

    /**
     * @return array
     */
    public function getOccupations(string $industry, $user): array
    {
        return ($this->getOccupationService($user))->getOccupationsInSelectedIndustry('ca', $industry);
    }


    public function hasViewedOccupationPopup($user): bool
    {
        return $user->hasSetting('updated-occupation-data');
    }

    function showOccupationalPopupOnDeposit($u_obj) {
        if(!$this->hasViewedOccupationPopup($u_obj)) {
            $u_obj->setSetting('show_occupation_popup', 1);
        }
    }

    /**
     * Set user province on end step1
     *
     * @param $user
     * @return void
     */
    public function addProvince($user)
    {
        $user = cu($user);
        $user->setSetting('main_province', 'ON');
    }

    /**
     * Is used a a lic function to verify if idscan is enabled for current jurisdiction
     * @return bool
     */
    public function IdScanVerificationEnabled($user): bool {
        $enabled = phive('IdScan')->getSetting('enabled') ?? false;
        $is_user_poi_approved = (bool) $user->getSetting('poi_approved');

        if ($is_user_poi_approved) {
            return false;
        }

        if($enabled && in_array($user->getUsername(), phive('IdScan')->getSetting('skip_verification_for'))){
            return false;
        }

        return $enabled;
    }


    public function IdScanVerification(string $hashed_uuid): string {
        $status = '';

        $data = phive('IdScan')->getSavedUserData($hashed_uuid);
        $verificationStatus = $data['status'];

        if($verificationStatus){
            return $verificationStatus;
        }

        //if we have step2 data in Redis
        if (count($data['step2data'])){
            //if we have step2data and status is undefined - we need to proceed with IDScan verification
            return 'check';
        }

        return $status;
    }

    public function redirectToDocumentsPage(DBUser $u_obj)
    {
        $redirect_url = $u_obj->accUrl('documents');
        return $this->goToUrlBeforePlay($u_obj, 'flash', phive('Licensed')->getDocumentsUrl($u_obj), $redirect_url);
    }

    /**
     * Return IDScan verification page's URL if verification is necessary
     *
     * @param $user
     * @return string
     */
    public function IdScanVerificationRedirect($user):string {
        $routes = [
            '/idscan/',
            '/mobile/idscan/'
        ];

        //idscan was requested
        if($user->getSetting('idscan_block')){
            foreach ($routes as $route) {
                if (preg_match($route, $_SERVER["REQUEST_URI"]) == 1) {
                    return '';
                }
            }

            $is_mobile = phive()->isMobile();
            $hashed_uid = $user->getSetting('hashed_uuid');

            $params = "?uid=$hashed_uid&context=contact-info-change";
            $path = $is_mobile ? "/mobile/idscan/$params" : "/idscan/$params";

            return $path;
        }

        return '';
    }


    /**
     * Multi jurisdiction function
     * KYC verification. On failure returns IDScan error
     *
     * @param $user
     * @param  bool  $logout
     * @param  null  $request_data
     *
     * @return array|string|string[]
     */
    public function openAccountNaturalPerson($user, $logout = false, $request_data = null) {
        if (!is_null($request_data)) {
            phive('DBUserHandler')->updateUser($user, $request_data);
            $user->setSetting('registration_data_saved', 1);
        }
        $user = cu(uid($user));
        $document_status = phive('Dmapi')->getUserDocumentsGroupedByTagStatus($user->getId());

        if (phive('Dmapi')->documentsHaveStatus($document_status, ['idcard-pic'], 'approved')) {
            $user->setSetting('poi_approved', 1);
            return '';
        }

        return $this->verifyAccountNaturalPerson($user);
    }

    /**
     * Implementation of verification for CAON. Used separately and as a part of openAccountNaturalPerson
     *
     * @param $user
     * @return array|string|string[]
     */
    public function verifyAccountNaturalPerson($user){
        //If IDScan is not enabled then we don't need KYC verification on registration
        if (!$this->IdScanVerificationEnabled($user)) {
            return '';
        }

        $hashed_uuid = cu($user)->getSetting('hashed_uuid');
        $data = phive('IdScan')->getSavedUserData($hashed_uuid);

        //If IDScan verification is already done we can skip checks
        if(isset($data['status'])){
            return '';
        }

        // Before sending to ID3 we need to save user's data to database
        $this->getExternalKyc()->setAgeAlert(false);
        $this->checkKycDobCommon($user, ['ID3'], true);

        //If user has PASS, PASS CREDIT, PASS DUAL then we don't need IDScan verification
        if (in_array($user->getSetting('id3global_res'), array(1,4,5))){
            return [];
        } else {
            //Otherwise IDScan verification should be triggered
            $userObj = cu($user);
            phive('UserHandler')->logAction($userObj->getId(), 'IDScan verification is required', 'IDScan');
            phive('UserHandler')->logAction($userObj, 'createEmptyDocuments on IDScan flow', 'creating_documents');
            $this->createEmptyDocuments($userObj);
            return ['idscan'=>'check'];
        }
    }

    /**
     * @param string|null $key
     * @return string
     */
    public function shouldGetInputName(?string $key): string
    {
        $field_name_mapping = [
                'firstname'  => 'firstname',
                'lastname'   => 'lastname',
                'address'    => 'address',
                'building'   => 'building',
                'zipcode'    => 'zipcode',
                'city'       => 'city',
                'industry'   => 'industry',
                'occupation' => 'occupation'
        ];
        return $field_name_mapping[$key] ?? parent::shouldGetInputName($key);
    }

    /**
     * @param string|null $key
     * @return bool
     */
    public function shouldDisableInput(?string $key): bool
    {
        $disabled_fields = ['main_province'];
        return in_array($key, $disabled_fields) || parent::shouldDisableInput($key);
    }

    public function shouldCheckCheckbox(?string $key): bool
    {

       return false;
    }


    /*
     * For migration to Ontario, trigger validation for pre-filled fields
     */
    public function triggerValidation () : bool {
        if($_SESSION['rstep2']['migration']) {
            return true;
        };
        return false;
    }

    /**
     * @param DBUser $user
     */
    public function onLogin(DBUser $u_obj, bool $is_api = false)
    {
        parent::onLogin($u_obj, $is_api);

        if ($u_obj->getSetting('ask-rg-tools')){
            $_SESSION['show_responsible_gaming_message_popup'] = true;
        }

        if ((new UserMigrator($u_obj))->requiresMigration()) {
            $u_obj->setSetting('migrated', "0");
        }

        if ($u_obj->getSetting('idscan_expiry_status')) {
            $_SESSION['idscan_failed_expiry_date'] = true;
        }

        $geoComplyLicense = licSetting('geocomply');
        if($geoComplyLicense['ENABLED']){
            $geoComply = phive('GeoComply');
            $geoComplyCredentials = $geoComply->getSetting('auth');
            $geoComplyData = array_merge($geoComplyLicense, $geoComplyCredentials);
            $geoComply->init($geoComplyData);

            $_SESSION['gcusername'] = $u_obj->getUsername();

            if(isset($_SESSION['rstep1'])){
                $_SESSION['gcusername'] = $_SESSION['rstep1']['email'];
                $_SESSION['gcpassword'] = $_SESSION['rstep1']['password'];
                unset($_SESSION['rstep1']);
            }

            //If there was initial IP mismatch with GeoComply IP and remIp() - we store that for later analise
            $geo_comply_data = $geoComply->loadGeoComplyData($u_obj);
            if ($geo_comply_data['ip_initial_mismatch']) {
                $u_obj->setSetting('geocomply_initial_ip_mismatch', "1");

                $geoComply->log('Initial IP mismatch', [
                    'username' => $geoComply->getUsername(),
                    'whitelisted' => $geoComply->isWhitelisted(),
                    'gc_ip' => $geo_comply_data['ip'],
                    'rem_ip' => $geo_comply_data['remip'],
                    'true_client_ip' => $_SERVER['HTTP_TRUE_CLIENT_IP'],
                    'forwarded_for' => $_SERVER['HTTP_X_FORWARDED_FOR'],
                    'remote_addr' => $_SERVER['REMOTE_ADDR'],
                    'cf_connecting_ip' => $_SERVER['HTTP_CF_CONNECTING_IP'],
                    'cf_pseudo_ip4' => $_SERVER['HTTP_CF_PSEUDO_IPV4'],
                    'gc_data' => $geo_comply_data,
                    'host' => 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . '://' . "{$_SERVER['HTTP_HOST']}/{$_SERVER['REQUEST_URI']}"
                ]);

            } else {
                $u_obj->deleteSetting('geocomply_initial_ip_mismatch');
            }

            if(!$geoComply->hasVerifiedIp($u_obj)){
                return [
                    $u_obj, [
                        "method" => "goToIpVerification"
                    ]
                ];
            }
        }
    }

    public function ipVerificationOnLogin(){
        $geoComplySettings = licSetting('geocomply');
        if($geoComplySettings['ENABLED']){
            return true;
        }
    }

    /*
    public function shouldDisableInput(?string $key): bool
    {
        $disabled_fields = ['country', 'main_province'];
        return in_array($key, $disabled_fields) || parent::shouldDisableInput($key);
    }

    /**
     * @param DBUser $u_obj
     */
    public function onRegistrationEnd(DBUser $u_obj)
    {
        parent::onRegistrationEnd($u_obj);
        $_SESSION['show_add_limits_popup'] = true;
    }

    public function generate2faCode(int $user_id)
    { //TODO: to be moved to Licensed or CAON.php
        $user = phive('UserHandler')->newByAttr('id', $user_id);
        if (empty($user)) {
            die('EMPTY USER');
        }
        $code = random_int(1111, 9999);
        $user->setSetting('2fa_code', $code);
        $twoFactorAuthCode = $user->getSetting('2fa_code');
        phive('MailHandler2')->sendOtpMail($user, $twoFactorAuthCode);
        phive('Mosms')->putInQ($user, t('mosms.verification.msg') . ' ' . $twoFactorAuthCode, false);
    }

    /**
     * @param $user
     * @return void
     */
    public function showAccount2FaSecuritySettings($user = null)
    {
        $user = cu($user);
        ?>
        <div class="simple-box account-security-box">
            <form name="registerform" method="post" action="">
                <input type="hidden" name="token" value="<? echo $_SESSION['token']; ?>">
                <h3 class="section-title">
                    <?php et('security.headline') ?>
                </h3>
                <div class="registerform account-security-section">
                    <caption>2FA Security Activation</caption>
                    <div><?php echo t('two.factor.authentication.info') ?></div>
                    <div>
                        <?php et('enable.two.factor.authentication.check') ?>
                        <?php dbCheckSetting($user, '2-factor_authentication', 'not-checked') ?>
                    </div>
                    <div class="update-button">
                            <input type="submit" name="submit_2FA" value="<?php echo t("register.update") ?>"
                                   class="btn btn-l btn-default-l edit-profile-submit-btn authentication-btn"/>
                    </div>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * @return \Videoslots\RgLimits\Builders\Locks\LockInterface
     */
    public function createLockBuilder(): LockInterface
    {
        return new \Videoslots\RgLimits\Builders\Locks\CAON();
    }

    // ---- Display related stuff ---- //
    /**
     * @deprecated
     */
    public function rgLockSection($box)
    {
        $box->printRgRadios(array(24, 168, 744, 1460, 2190), 'lock_duration', 'hours', function () { ?>
            <input id="ca-other" class="left" type="radio" name="other" value=""/>
            <div class="left" style="margin-top: 2px;">
                <?php et("other") ?>
            </div>
        <?php }, 'rg-button-ontario') ?>
        <div id="ca-lock-txt-holder" style="display: none;">
            <?php dbInput('lock-hours', '', 'text', 'input-normal') ?>
        </div>
        <?php
        return true;
    }


    /**
     * Runs every minute
     *
     * @return void
     */
    public function onEveryMin()
    {
        $this->geoComplyCron();
    }

    /**
     * Get list of fields to display on user profile page
     *
     * @return string[]
     */
    public function getPrintDetailsFields(): array
    {
        return ['firstname', 'lastname', 'address', 'building', 'province', 'zipcode', 'city', 'country', 'dob', 'mobile', 'email', 'last_login', 'register_date'];
    }

    /**
     * Get needed data to display details on user profile page
     *
     * @param DBUser $user
     *
     * @return array
     * @throws Exception
     */
    public function getPrintDetailsData(DBUser $user): array
    {
        return array_merge(parent::getPrintDetailsData($user), [
            'province' => $this->getProvinces()[$user->getSetting('main_province')] ?? '',
            'building' =>$user->getSetting('building')
        ]);
    }

    /**
     * @param string|null $zipcode
     * @return string|null
     */
    public function formatZipcode(string $zipcode = null)
    {
        return strtoupper($zipcode);
    }

    /**
     * Makes possible to disable prefilling of a Nationality Value
     * @return string
     */
    public function getNationalityValue(): string
    {
        return '';
    }

    public function pepRegularCheckFrequency(): int
    {
        return 30;
    }

    /**
     * @return void
     */
    public function geocomplyNDBInfo() {
        ?>
        <script>
            if ($.cookie('geocomplyndbinfo_popup') === null) {
                addToPopupsQueue(function () {
                    lic('showNDBInfoPopup');
                    $.cookie('geocomplyndbinfo_popup', true);
                });
            }
        </script>
        <?php
    }

    public function hasPrepaidDepositLimit(string $supplier, string $bin, int $userId, int $amount): array
    {
        $prepaidLimitConfigs = licSetting('prepaid_deposits_limit');

        if (!in_array($supplier, $prepaidLimitConfigs['suppliers'])) {
            return [];
        }

        $mtsClient = new MtsClient(
            phive('Cashier')->getSetting('mts'),
            phive('Logger')->channel('payments')
        );

        $prepaidDeposits = $mtsClient->prepaidDepositsDetail([
            'card_bin' => $bin,
            'user_id' => $userId,
            'last_days' => $prepaidLimitConfigs['last_days'],
        ]);

        if (!$prepaidDeposits['is_prepaid']) {
            return [];
        }

        if ($prepaidDeposits['total_prepaid_deposits'] + $amount > $prepaidLimitConfigs['allowed_amounts']) {
            return [
                'limit' => 'prepaid_deposits',
                'params' => [
                    'total_prepaid_deposits' => $prepaidDeposits['total_prepaid_deposits'],
                ],
            ];
        }

        if ($prepaidDeposits['total_prepaid_cards'] >= $prepaidLimitConfigs['allowed_cards']) {
            return [
                'limit' => 'prepaid_cards'
            ];
        }

        return [];
    }

    public function shouldAskForCompanyDetails(): bool
    {
        $user = cu();

        if (!$user) {
            return false;
        }

        return
            $user->hasPrivacySettings()
            && !$user->hasSetting('company_details_popup_shown')
            && !$user->hasCompanyDetails();
    }

    /**
     * Cron job started from diamondbet/soap/midnight.php.
     *
     * @return void
     */
    public function onEveryMidnight(): void
    {
        $this->askRgToolsYearly();
    }

    /**
     * Collects all users whose registration anniversary is at the day of checking
     * and triggers the Ask Rg Tools popup for them.
     *
     * @return void
     */
    public function askRgToolsYearly(): void
    {
        $register_month_day = date('m-d');

        $users_to_ask = phive('SQL')->shs()->loadArray("
            SELECT us.user_id
            FROM users_settings us
                     LEFT JOIN users u ON u.id = us.user_id
                     LEFT JOIN users_settings us2 ON us.user_id = us2.user_id AND us2.setting = 'ask-rg-tools'
            WHERE us.setting = 'main_province'
              AND us.value = 'ON'
              AND u.country = 'CA'
              AND u.register_date LIKE '%-{$register_month_day}'
              AND u.register_date != CURDATE()
              AND us2.value IS NULL;
        ");

        foreach ($users_to_ask as $user) {
            $this->askRgTools((int)$user['user_id']);
        }
    }

    /**
     * Set the required setting for the user to trigger the 'Ask RG Tools' popup on the account.
     * Question: "Do you understand our responsible gambling tools available?"
     *
     * @param int $user_id
     * @return void
     */
    public function askRgTools(int $user_id): void
    {
        $user = cu($user_id);

        if (empty($user)) {
            return;
        }

        $user->setSetting('ask-rg-tools', 1);

        $action_descr = "{$user->getAttr('email')} was requested to answer, " .
            "Do you understand our responsible gambling tools available?";
        phive('UserHandler')->logAction($user, $action_descr, 'ask_rg_tools', true, $user);
    }

    /**
     * Handles the answer the user gave at the front end regarding question:
     * "Do you understand our responsible gambling tools available?".
     *
     * @param DBUser $user
     * @param string $answer
     * @return void
     */
    public function handleRgToolsAnswer(DBUser $user, string $answer): void
    {
        $valid_answers = ['yes', 'no'];

        if (!in_array($answer, $valid_answers, true)) {
            return;
        }

        $user->deleteSetting('ask-rg-tools');

        $answer = ucfirst($answer);
        $action_descr = "{$user->getAttr('email')} answered [{$answer}] to message, " .
            "Do you understand our responsible gambling tools available?";
        phive('UserHandler')->logAction($user, $action_descr, 'ask_rg_tools', true, $user);
    }
}
