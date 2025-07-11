<?php
require_once __DIR__ .'/../Traits/RealityCheckTrait.php';
require_once __DIR__ .'/../Traits/PreventMultipleGameSessionsTrait.php';

class DE extends Licensed
{
    use RealityCheckTrait;
    use PreventMultipleGameSessionsTrait;

    public const FORCED_LANGUAGE = 'de';

    protected $extra_registration_fields = [
        'step1' => [],
        'step2' => [
            'birth_country' => true
        ],
    ];

    /**
     * @var array
     */
    protected array $fields_to_save_into_users_settings = [
        'birth_country',
    ];


    /**
     * Show RG related buttons only on gameplay
     *
     * @param string $extra_classes
     * @param string $img_class
     * @return bool|void
     */
    public function rgGameplayTopButton($extra_classes = '', $img_class = '')
    {
        if (!$this->getLicSetting('show_panic_button')) {
            return false;
        }
        loadJs('/phive/js/hammerjs/hammer.min.js');
        $panicButtonConfig = $this->getBaseGameParams();
        $findColumn = array_column($panicButtonConfig, 'swipe_threshold');
        $swipeThreshold = count($findColumn) > 0 ? $findColumn[0] : 10;

        return "<script type=\"text/javascript\">lic('panicButtonInit', [{swipe_threshold: " . $swipeThreshold . "}]);</script>" . licHtml('panicButton');
    }

    /**
     * Used by "user-service-laravel" and "new game mode"
     * return all the data needed to display the strip - NO HTML
     */
    public function getBaseGameParams($user = null) {
        if ($this->getLicSetting('show_panic_button')) {
            $res['panic_button'] = [
                'gesture_threshold' => 30,
                'icon' => 'icon-vs-panic-button-lock'
            ];
        }

        return $res;
    }

    /**
     * @see Licensed::showBosLicensingStripInLobby
     *
     * @return bool
     */
    public function showBosLicensingStripInLobby()
    {
        return false;
    }

    /**
     * @see Licensed::showBosLicensingStripInGame()
     *
     * @return bool
     */
    public function showBosLicensingStripInGame()
    {
        return $this->getLicSetting('show_panic_button');
    }

    /**
     * Custom jurisdictional logic that overrides the actual user input when setting up the limit
     *
     * @param DBUser $u_obj
     * @param string $type
     * @param string $time_span
     * @param mixed $requested_limit
     * @param array $lic_defaults
     * @param array $previous_limits
     * @return array
     */
    public function overrideRgLimit($u_obj, $type, $time_span, $requested_limit, $lic_defaults, $previous_limits)
    {
        $default = (int)$lic_defaults[$time_span];
        if ($type == 'deposit' && !empty($cutoff_date = $this->getLicSetting('deposit_limit')['override_cutoff_date'])) {
            if (empty($previous_limits) && $u_obj->getAttr('register_date') >= $cutoff_date && (int)($requested_limit) > $default) {
                return [$default, 'rglimits.added.overridden.de'];
            }
        }

        return [$requested_limit, false];
    }

    /**
     * @param null|DBUser $user
     * @return bool
     */
    public function hasDepositLimitOnCashier($user = null)
    {
        $user = cu($user);

        if (empty($user) || empty($this->getLicSetting('deposit_limit')['popup_active'])) {
            return false;
        }

        if (!empty($this->rgLimits()->getByTypeUser($user, 'deposit'))) {
            return false;
        }

        return true;
    }

    /**
     * Called by Licensed/ajax.php - will check if the user has some enforced action to do before play
     * Currently used on click on "Mobile BoS" links to enforce popup from the old site, before going into new site.
     *
     * @param array $post - $_POST is being passed here
     * @return array
     */
    public function ajaxBeforePlay($post = [])
    {
        return ['url' => $this->beforePlay(null, 'mobile', null)];
    }

    /**
     * We only hide deposit limit
     *
     * @param $type
     * @return bool
     */
    public function hideRgRemoveLimit($type){
        return parent::hideRgRemoveLimit($type) || $type == 'deposit';
    }

    /**
     * Do not show Jackpot feeds on lobby
     *
     * @return bool
     */
    public function hideJackpots()
    {
        return true;
    }

    /**
     * Registration step 2 setup
     *
     * @return string[][]
     */
    public function registrationStep2Fields()
    {
        if (phive()->isMobile()) {
            return [
                'left' => ['firstname', 'lastname', 'address', 'zipcode', 'city', 'bonus_code', 'currency', 'preferred_lang'],
                'right' => ['birthdate', 'birth_country', 'sex', 'email_code', 'eighteen']
            ];
        }
        return [
            'left' => ['firstname', 'lastname', 'address', 'zipcode', 'city', 'preferred_lang', 'bonus_code_text', 'bonus_code'],
            'right' => ['birthdate', 'birth_country', 'currency', 'sex', 'email_code', 'eighteen']
        ];
    }

    /**
     * Get list of countries for "Place of birth" field.
     *
     * @return array
     */
    public function getBirthCountryList(): array
    {
        return phive('Cashier')->displayBankCountries(phive('Cashier')->getBankCountries('', true));
    }

    /**
     * Create empty documents
     * Set default Reality Check limit
     * @param DBUser $u_obj
     * @see Licensed::onRegistrationEnd()
     */
    public function onRegistrationEnd(DBUser $u_obj)
    {
        parent::onRegistrationEnd($u_obj);

        if (empty(rgLimits()->getByTypeUser($u_obj, 'rc'))) {
            $rc_configs = $this->getRcConfigs();

            rgLimits()->saveLimit($u_obj, 'rc', 'na', $rc_configs['rc_default_interval']);
        }

        $this->checkKycDob($u_obj);
    }

    /**
     * @see Licensed::beforePlay() + "rg-activity": this check if user accepted rg-popup-activity in valid period
     *
     * @param DBUser $u_obj
     * @param string $type
     * @param null $game
     * @return string
     */
    public function beforePlay($u_obj, $type = 'flash', $game = null): string
    {
        $url = parent::beforePlay($u_obj, $type, $game);

        if (! empty($url)) {
            return $url;
        }

        return $this->handleRgLimitPopupRedirection($u_obj, $type, 'activity');
    }

    /**
     * The time the RC should use as starting point when launching a game
     * Here we are using the time since the user logged in to the casino
     *
     * @param DBUser $user
     * @return int - seconds
     */
    public function rcElapsedTime($user = null)
    {
        $user = cu($user);

        if (!empty($user)){
            // +1 second to initialize RC on login.
            // on login it returns 0 so rc is not initialized.
            return $this->getSessionLength($user) + 1;
        }
        return false;
    }

    /**
     * Return all the data needed to populate the RC dialog.
     * @param $user
     * @param string $lang
     * @param string $ext_game_name
     * @param bool $translate
     * @return array
     */
    public function getRealityCheck($user, $lang = 'en', string $ext_game_name, bool $translate = true)
    {
        $string = 'reality-check.msg.elapsedtime.with-pause';
        $rc_data = $this->getRcPopupMessageData($user, $ext_game_name);
        $rc_data['pause_minutes'] = $this->getGamingPauseTime(true);
        $header_alias = 'reality-check.header.title';

        return [
            'header' => $translate ? t($header_alias, $lang) : $header_alias,
            'message' => tAssoc($string, $rc_data, $lang), // already translated for easier use on the old site
            'messageString' => $string, // needed on user-service
            'messageData' => $rc_data, // needed on user-service
            'buttons' => $this->getRcPopupButtons($user, $lang),
            'closeButton' => false
        ];
    }

    /**
     * Amount of time in seconds/minutes for which the gaming is paused
     * @param false $minutes
     * @return float|int|mixed
     */
    public function getGamingPauseTime($minutes = false)
    {
        $config = $this->getLicSetting('reality_check');
        if (empty($config)) {
            return 0;
        }
        if (empty($config['game_pause_time_in_seconds'])) {
            return 0;
        }
        if ($minutes) {
            return $config['game_pause_time_in_seconds'] / 60;
        }
        return $config['game_pause_time_in_seconds'];
    }

    /**
     * Return the buttons that need to displayed in the popup
     * will check if a license define different buttons
     * @param $user
     * @param $lang
     * @return array
     */
    public function getRcPopupButtons($user, $lang = 'en')
    {
        return [
            [
                'string' => 'reality-check.label.accept',
                'action' => 'accept',
                'url' => phive('Casino')->getLobbyUrl(false, $lang)
            ]
        ];
    }

    /**
     * When user clicks accept pause the game play for configured seconds
     * @param $user
     * @return bool
     * @throws Exception
     */
    public function pauseGamePlay($user)
    {
        $user = cu($user);
        if (empty($user)) {
            return false;
        }

        $seconds = $this->getGamingPauseTime();
        $until = new DateTime('+' . $seconds . ' seconds');

        phMsetShard('reality_check_game_paused_until', $until->getTimestamp(), $user->getId(), $seconds);
        return true;
    }

    /**
     * Calculate how many minutes passed since session opened
     *
     * @param $user
     * @return false|float
     */
    public function getMinutesReached($user) {
        return floor($this->rcElapsedTime() / 60);
    }

    /**
     * Enable detecting if game play is paused via ajax request
     * @return bool
     */
    public function ajaxGamePlayPaused() {
        return $this->gamePlayPaused(cu());
    }

    /**
     * Check if game play is paused or return remaining seconds or return timestamp
     * @param $user
     * @param bool $return_remaining
     * @param bool $return_timestamp
     * @return bool
     */
    public function gamePlayPaused($user, $return_remaining = false, $return_timestamp = false)
    {
        $user = cu($user);
        if (empty($user)) {
            return false;
        }
        $timestamp = phMgetShard('reality_check_game_paused_until', $user->getId());

        if (empty($timestamp)) {
            return false;
        }
        if ($return_timestamp) {
            return $timestamp;
        }

        $now = new DateTime();
        $until = new DateTime();
        $until->setTimestamp($timestamp);

        $remaining = phive()->subtractTimes($until->getTimestamp(), $now->getTimestamp(), 's');
        if ($return_remaining) {
            return $remaining < 0 ? 0 : $remaining;
        }
        return $now < $until;
    }
    public function getCustomRcStyle() {
        if (phive()->isMobile()) {
            ?>
            <style>
                #reality-check-popup {
                    display: flex;
                    flex-direction: column;
                }
                #reality-check-popup > .rc__body {
                    display: flex;
                    flex-grow: 1;
                }
            </style>
            <?php
        }
    }

    /**
     * Triggered when user is blocked because of KYC DoB reason
     *
     * @param DBUser $user
     *
     * @return void
     */
    public function blockKycDob(DBUser $user): void
    {
        $user->playBlock();
        $user->unVerify(); //block withdraw
    }

    /**
     * If there is no registration in progress and we find a customer without KYC checks we perform them
     *
     * @param DBUser $u_obj
     * @return bool|void
     */
    public function onSuccessfulLogin($u_obj)
    {
        $this->checkKycDob($u_obj);
    }

    /**
     * Checks user's kyc date of birth
     *
     * @throws Exception
     */
    private function checkKycDob(DBUser $u_obj): void
    {
        if (!$this->getLicSetting('check_dob')) {
            return;
        }

        if ($u_obj->isTestAccount()) {
            return;
        }

        $force_gbg_check = $u_obj->hasSettingExpired('verified', 365) && $u_obj->hasSettingExpired('id3global_requested_at', 365);
        if (!$force_gbg_check) {
            return;
        }

        $u_obj->deleteSetting('experian_block');
        $ext_kyc_module = $this->getExternalKyc();
        $ext_kyc_module->setAgeAlert(false);
        $this->checkKycDobCommon($u_obj, ['ID3'], $force_gbg_check);


    }

    /**
     * @inheritDoc
     */
    public function getRedirectBackToLinkAfterRgPopup()
    {
        $u_obj = cu();

        if ($this->getLicSetting('check_dob') && $u_obj && $this->isGbgFailed($u_obj) && !$u_obj->isVerified()) {
            $url = $this->getVerificationModalUrl(phive()->isMobile(), true);
            $_SESSION['rg_login_info_callback']
                = phive('Redirect')->getToUrl($url,
                phive('Localizer')->getSiteLanguage($u_obj));
        }

        return parent::getRedirectBackToLinkAfterRgPopup();
    }

    /**
     * Use If you want check gbg result
     *
     * @param DBUser $user
     *
     * @return bool
     */
    public function isGbgFailed(DBUser $user): bool
    {
        if (!$user->hasDoneAgeVerification()) {
            return false;
        }

        $dob_results = $this->getExternalKyc()::DOB_RESULTS;

        return (int)$user->getSetting('id3global_res')
            !== $dob_results['PASS'];
    }

    /**
     * Check if should redirect to verification modal from topmenu
     *
     * @param DBUser|null $user
     *
     * @return bool
     */
    public function shouldRedirectToVerificationModal(DBUser $user = null): bool
    {
        if (!$this->getLicSetting('check_dob')) {
            return false;
        }

        if (!$user) {
            return false;
        }

        if ($user->isVerified()) {
            return false;
        }

        if ($user->isTestAccount()) {
            return false;
        }

        if (!$this->isGbgFailed($user)) {
            return false;
        }

        if ($user->hasDeposited()) {
            return false;
        }

        if (isset($_GET['rg_login_info'])) {
            return false;
        }

        if (isset($_GET['showtc'])) {
            return false;
        }

        $routes = [
            '/\/account\/\d+\/documents/',
            '/mobile\/rg-activity/',
            '/mobile\/rg-verify/',
            '/mobile\/cashier\/deposit/',
            '/cashier\/deposit/',
        ];

        foreach ($routes as $route) {
            if (preg_match($route, $_SERVER["REQUEST_URI"]) == 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if deposit limit is above highest allowed deposit limit
     *
     * @param array $new_limits
     * @param DBUser $user
     * @return array|null
     */
    public function checkHighestAllowedDepositLimit(array $new_limits, DBUser $user): ?array
    {
        $filtered_limits = array_filter($new_limits, function ($limit) {
            return $limit['type'] === 'deposit';
        });

        foreach ($filtered_limits as $new_limit) {
            $timespan = $new_limit['time_span'];
            $highest_allowed_limit = $this->getLicSetting('deposit_limit')['highest_allowed_limit'];
            $new_limit_value_in_cents = $new_limit['limit'] * 100;

            if ($new_limit_value_in_cents > $highest_allowed_limit[$timespan]) {
                return [
                    'success' => 'nok',
                    'msg' => tAssoc('rg.highest.allowed.deposit.limit.error', ['limit' => $highest_allowed_limit[$timespan] / 100])
                ];
            }
        }

        return null;
    }

    /**
     * On Verify
     *
     * @param DBUser $user
     * @param int|NULL $old_verified_value if player status was active previously or not
     * @return void
     */
    public function onVerify(DBUser $user, ?int $old_verified_value):void
    {
        if ($user->isPlayBlocked()) {
            $user->resetPlayBlock();
        }

        if ($user->isDepositBlocked()) {
            $user->resetDepositBlock();
        }
    }

    /**
     * @return array
     */
    public function getSelfExclusionTimeOptions()
    {
        $option = [];
        if ($this->getLicSetting('indefinite_self_exclusion')) {
            $option = ['indefinite'];
        }
        return array_merge([183, 365, 730, 1095, 1825], $option);
    }
}
