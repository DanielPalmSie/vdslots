<?php

require_once __DIR__ . '/../Libraries/Bluem.php';
require_once __DIR__ . '/../../../modules/DBUserHandler/Registration/RegistrationHtml.php';
require_once __DIR__ .'/../Traits/PreventMultipleGameSessionsTrait.php';

class NL extends Licensed
{
    use PreventMultipleGameSessionsTrait;

    public const RG_YOUNG_ADULT = 'RG61';
    private const MIN_YOUNG_ADULT_AGE = 18;
    private const MAX_YOUNG_ADULT_AGE = 24;

    public const FORCED_LANGUAGE = 'nl';

    protected array $fields_to_save_into_users_settings = [
        'firstname_initials',
        'citizen_service_number',
        'iban',
        'birth_place',
        'doc_type',
        'doc_number',
        'doc_issued_by',
        'doc_issue_date',
        'honest_player'
    ];

    /** @var Bluem $bluem */
    protected Bluem $bluem;
    public string $ext_exclusion_name = 'cruks';

    /**
     * NL constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $settings = $this->getLicSetting('bluem');
        $this->bluem = new Bluem(
            $settings['base_url'],
            $settings['token'],
            $settings['sender_id'],
            $settings['sender_brand']
        );
        $this->bluem->toggleCruksCheck(!empty($settings['enable_cruks']));
    }

    /**
     * Check if player is registered in CRUKS
     *
     * @param DBUser|string|null $user
     * @param array $overwrite_fields
     * @param bool $on_login
     * @return array
     */
    public function isUserRegisteredInCruks($user = null, $overwrite_fields = [], $on_login = false): array
    {
        // no user provided
        if (empty($user)) {
            return $this->bluem->isUserRegisteredInCruks($overwrite_fields);
        }

        $user = cu($user);
        // user_id provided but can't find the user in DB
        if (empty($user)) {
            return $this->bluem->isUserRegisteredInCruks($overwrite_fields);
        }

        $cruks_code = $user->getSetting('cruks_code');
        $data = array_merge([
            'user_id' => $user->getId(),
            'lastname' => $user->getData('lastname'),
            'dob' => $user->getData('dob'),
            'bsn' => $user->getSetting('citizen_service_number'),
            'firstname' => $user->getData('firstname'),
            'last_name_prefix' => $user->getSetting('last_name_prefix'),
            'birth_location' => $user->getSetting('birth_location'),
        ], $overwrite_fields);

        if($cruks_code) {
            $data['cruks_code'] = $cruks_code;
        }

        return $this->bluem->isUserRegisteredInCruks($data, $user, $on_login);
    }

    /**
     * Check CRUKS service after external service had issues
     * If external service is still unavailable do only 1 check
     *
     * @return void
     */
    public function checkCruksAfterExternalServiceWasDown(): void
    {
        $setting = $this->bluem::CRUKS_SERVICE_WAS_DOWN_FLAG;

        // external service was never down so do nothing or another cron is already handling this
        // cruks service available flag is missing, cover for redis cleared
        if (empty(phMget($setting)) && empty(phMget($this->bluem::CRUKS_SERVICE_IS_UP_FLAG))) {
            return;
        }

        // in case this takes too long, prevent another cron from running on the same users
        phMdel($setting);

        $users = phive('SQL')->shs()->loadArray("SELECT user_id FROM users_settings WHERE setting = '{$setting}'");

        foreach ($users as $user_setting) {
            // one request already attempted but found the service down
            if (!empty(phMget($setting))) {
                return;
            }

            $user = cu($user_setting['user_id']);
            if (empty($user)) {
                phive('Logger')->debug('crucks-check-on-missing-user', $user_setting);
                continue;
            }

            $check = $this->isUserRegisteredInCruks($user);

            if (empty($check['success'])) {
                continue;
            }

            $user->deleteSetting($setting);

            if (!empty($check['result'])) {
                $uh = phive('DBUserHandler');
                $uh->currentUser = $user;
                $uh->logout();
            }
        }
    }

    /**
     * Cron runs every 1 minute
     *
     * @return void
     */
    public function onEveryMin() {
        $this->checkCruksAfterExternalServiceWasDown();
    }

    /**
     * Return external verification link
     *
     * @param $return_url
     * @param null $user
     * @return array|false
     */
    public function verifyRedirectStart($return_url, $user = null)
    {
        $user = cu($user);
        if (empty($user)) {
            return false;
        }

        $ext_login_id = explode('-', $user->getSetting('ext-login-id')) ?? [];
        list($url, $trans_id, $entrance_code) = $this->bluem->createIdentityTransaction($return_url, $user, $ext_login_id[0]);
        if (empty($url)) {
            return false;
        }
        // We need this when the user returns.
        $_SESSION['bluem_current_trans_id'] = "$trans_id-$entrance_code";

        return ['url' => $url];
    }

    /**
     * Prepare to redirect the main page to external verification link
     *
     * @param $user
     * @param string $context
     * @param bool $user_logged_in
     * @return array|string
     */
    public function verifyRedirectStartWrapper($user, $context = RegistrationHtml::CONTEXT_REGISTRATION, $user_logged_in = false)
    {
        $user = cu($user);
        if (empty($user)) {
            return 'blocked.login_fail.html';
        }

        if ($user->getCountry() !== $this->getIso()) {
            // ex: SE player trying to login via iDIN
            return 'blocked.login_fail.html';
        }

        if (empty($user->getSetting('ext-login-id')) && !$user_logged_in && $context !== RegistrationHtml::CONTEXT_REGISTRATION) {
            // NL player who registered using normal registration
            return RegistrationHtml::actionResponse('licFuncs.showPassword', []);
        }

        $ret_url = "phive/modules/Licensed/html/verify_redirect_return.php?action={$context}&lang=" . cLang();
        $res = $this->verifyRedirectStart(phive()->getSiteUrl('', false, $ret_url), $user);
        if (!empty($res)) {
            // This session variable is needed for the redirect from verify end to registration step 2.
            $_SESSION['reg_uid'] = $user->getId();

            return RegistrationHtml::actionResponse('goTo', [$res['url'], null, true, true]);
        }

        return 'external.service.error.html';
    }

    /**
     * Method called when external verification redirects back
     *
     * @param string $context
     * @return bool|DBUser|string
     */
    public function verifyRedirectEnd($context = RegistrationHtml::CONTEXT_REGISTRATION)
    {
        $idin_link_user_id = $_SESSION['idin_link_user_id'];
        unset($_SESSION['idin_link_user_id']);

        list($trans_id, $entrance_code) = explode('-', $_SESSION['bluem_current_trans_id']);
        if (empty($trans_id)) {
            return false;
        }

        if ($this->getLicSetting('mock_ext_verify_return')) {
            $res = $this->getLicSetting('mock_ext_verify');
        } else {
            $res = $this->bluem->getIdentityTransactionStatus($trans_id, $entrance_code);
        }

        if (!$res['success']) {
            return false;
        }

        $result = $res['result'];
        $user = cu($result['IdentityStatusUpdate']['DebtorReference']);
        $bank_id = $result['IdentityStatusUpdate']['IdentityReport']['CustomerIDResponse'];
        $bank = $result['IdentityStatusUpdate']['AuthenticationAuthorityID'];
        $ext_login_id = $bank . '-' . $bank_id;

        if ($context !== RegistrationHtml::CONTEXT_REGISTRATION) {
            // detect if we try to login with wrong account
            if ($idin_link_user_id !== $user->getId() && $user->getSetting('ext-login-id') !== $ext_login_id) {
                return false;
            }
        }

        $_SESSION['rstep1']['pr'] = 'no';
        $res['result']['user'] = $result;
        $_SESSION['lookup_res'] = $res;
        $lookup_res = $_SESSION['lookup_res'];

        $lookup_data = $this->mapLookupData($lookup_res['result']['user']);

        if (!empty($user)) {
            // We only do the DOB attribute save and possible block one time to avoid repeat blocks
            // in case the iDIN result for some reason is not correct and the customer needs to be
            // verified by P&F agent. So this will happen only on reg and on first time login with iDIN.
            if (!$user->hasSetting('ext-login-id')) {
                $error = null;
                $verified_source = !empty($lookup_data['dob']);
                if ($verified_source) {
                    $user->setAttr('dob', $lookup_data['dob']);
                    if ($this->isUnderAge($user)) {
                        $this->uh->addBlock($user, 14);
                        $error = 'underage.block';
                    } else {
                        $user->setSetting('verified-nid', 1);
                    }
                } else {
                    /** @var ExternalKyc $ext_kyc_module */
                    $ext_kyc_module = phive("DBUserHandler/ExternalKyc");
                    $ext_kyc_module->resetFailureCheck();
                    $ext_kyc_module->logAndBlockUserOnFailedKyc($user, 'age');
                    $error = 'underage.block';
                }

                // To be used for subsequent logins using the chosen bank.
                $user->setSetting('ext-login-id', $ext_login_id);
                $user->setSetting('nid_data', json_encode($lookup_res['result']));
                if (!empty($error)) {
                    return $error;
                }
            }
        }

        // always set this information in session to have it available on redirect to reg_step_2 for example
        if (lic('hasPrepopulatedStep2')) {
            $_SESSION['ext_normal_user'] = true;
            $_SESSION['tmp_rstep2'] = $lookup_data;
            $_SESSION['rstep2_disabled'] = $_SESSION['tmp_rstep2'];
            $_SESSION['rstep2'] = $_SESSION['rstep2_disabled'];
        }

        return $user;
    }

    /**
     * Return correctly mapped user information from external verification data
     *
     * @param array $data
     * @return array
     */
    public function mapLookupData($data): array
    {
        $data = $data['IdentityStatusUpdate'] ?: [];
        $data = $data['IdentityReport'] ?: [];
        $address = $data['AddressResponse'] ?: [];
        $name = $data['NameResponse'] ?: [];

        [$year, $month, $day] = explode('-', $data['BirthDateResponse']);

        return [
            'firstname_initials' => $name['Initials'],
            'firstname' => $name['LegalFirstName'],
            'lastname' => $name['LegalLastName'],
            'address' => $address['Street'] . ' ' . $address['HouseNumber'],
            'zipcode' => $address['PostalCode'],
            'city' => $address['City'],
            'birthdate' => $day,
            'birthmonth' => $month,
            'birthyear' => $year,
            'dob' => $data['BirthDateResponse'],
            'sex' => ucfirst(strtolower($data['GenderResponse'])),
        ];
    }

    /**
     * Add iDIN as login option
     *
     * @return mixed
     */
    public function customLoginTop()
    {
        return licHtml('custom_login_top');
    }

    /**
     * Add necessary form elements for iDIN
     *
     * @return mixed
     */
    public function customLoginBottom()
    {
        return licHtml('login_bottom');
    }

    /**
     * Make verify redirect start available to ajax requests
     *
     * @param $post
     * @return array|string
     */
    public function ajaxVerifyRedirectStartWrapper($post)
    {
        if ($post['context'] !== RegistrationHtml::CONTEXT_REGISTRATION && !empty($post['password_required']) && $post['password_required'] !== 'false') {
            list($user, $action) = $this->uh->setAjaxContext()->login($post['username'], $post['password']);

            $result = $this->uh->getLoginAjaxContextRes($user, $action);
            if (empty($result["success"])) {
                return RegistrationHtml::actionResponse('loginCallback', [$result]);
            } else {
                $this->uh->logout();
                $_SESSION['idin_link_user_id'] = $user->getId();
                $user_logged_in = true;
            }
        }

        $res = $this->verifyRedirectStartWrapper($post['username'], $post['context'], $user_logged_in ?? false);
        if (is_array($res)) {
            return $res;
        }
        return $this->fail(is_string($res) ? t($res) : $res);
    }

    /**
     * On Login Responsible Gaming Check
     *
     * @param DBUser $u_obj
     * @return void
     */
    public function onLoginRGCheck(DBUser $u_obj): void
    {
        $this->checkLoginFrequencies($u_obj);
    }

    /**
     * Check Login Frequencies
     *
     * @param DBUser $user
     */
    private function checkLoginFrequencies(DBUser $user): void
    {
        $config = phive('Config')->getByTagValues('RG');
        //weekly
        $flag_name = 'RG62';
        $frequency = (int) $config[$flag_name];
        $start_date = date('Y-m-d 00:00:00', strtotime("monday -1 week"));
        $end_date = date('Y-m-d 23:59:59', strtotime("sunday"));
        $this->checkLoginFrequencyPeriod($user, $flag_name, $frequency, $start_date, $end_date);

        //monthly
        $flag_name = 'RG63';
        $frequency = (int) $config[$flag_name];
        $start_date = date('Y-m-01 00:00:00');
        $end_date = date('Y-m-t 23:59:59');
        $this->checkLoginFrequencyPeriod($user, $flag_name, $frequency, $start_date, $end_date);
    }

    /**
     * Check Login Frequency Period
     *
     * @param DBUser $user
     * @param string $flag_name
     * @param int    $frequency
     * @param string $start_date
     * @param string $end_date
     */
    private function checkLoginFrequencyPeriod(DBUser $user, string $flag_name, int $frequency, string $start_date, string $end_date): void
    {
        $user_handler = phive('UserHandler');
        $logins = $user_handler->countLoginsByPeriod($user->getId(), $start_date, $end_date);

        if ($logins >= $frequency) {
            $user_handler->logTrigger($user, $flag_name, 'User reached logins frequency', true, [$start_date, $end_date]);
        }
    }

    /**
     * Checks if balance type limit is applicable for this jurisdiction
     *
     * @return bool
     */
    public function hasBalanceTypeLimit(): bool
    {
        $additional_rg_limits = $this->getLicSetting('additional_rg_limits') ?? [];

        return (in_array('balance', $additional_rg_limits));
    }

    /**
     * If intervention types should be shown
     *
     * @return bool
     */
    public function showInterventionTypes(): bool
    {
        return true;
    }

    /**
     * @param DBUser $user
     * @param array $overwrite_fields
     *
     * @return mixed
     */
    public function checkIBAN(DBUser $user, array $overwrite_fields = [])
    {
        $iban_settings = $this->getLicSetting('bluem')['iban_check'] ?? [];
        $iban_check_enabled = $iban_settings['enabled'] ?? false;
        $allow_organisations = $iban_settings['allow_organisations'] ?? false;

        if (!$iban_check_enabled) {
            return [
                'success' => true,
                'result' => true,
                'message' => t('bluem.iban.check.not_enabled')
            ];
        }

        $data = array_merge([
            'user_id' => $user->getId(),
            'full_name' => $user->getFullName(),
            'iban' => $user->getSetting('iban')
        ], $overwrite_fields);

        $response = $this->bluem->checkIBAN($data, $user);

        if (!$response['success']) {
            return [
                'success' => false,
                'result' => $response['result'],
                'message' => is_string($response['result']) ? t("bluem.iban.check.{$response['result']}") : ''
            ];
        }

        $result = $response['result'];
        if ($result) {
            $matched = $result['known'] && ($result['matched'] || $result['mistyped']) && $result['active'];
            $valid_account_type = $allow_organisations || $result['account_type'] === Bluem::ACCOUNT_TYPE_PERSON;

            if($matched && $valid_account_type) {
                $user->setSetting('bluem_iban_check_passed', true);

                if($result['mistyped'] && $result['suggested_name']) {
                    $user->setSetting('bluem_iban_suggested_name', $result['suggested_name']);
                }

                return [
                    'success' => true,
                    'result' => true,
                    'message' => t('bluem.iban.check.passed')
                ];
            }
        }

        return [
            'success' => true,
            'result' => false,
            'message' => t("bluem.iban.check.failed")
        ];
    }

    /**
     * Handle all logic to be executed when registration has finished.
     *
     * @param DBUser $u_obj
     */
    public function onRegistrationEnd(DBUser $u_obj)
    {
        parent::onRegistrationEnd($u_obj);
        $this->checkUserDOB($u_obj);
        $this->clearBSN($u_obj);
    }

    /**
     * Make an external verification
     * For NL: check if user is external self excluded with Bluem Cruks
     *
     * @param array $user_data
     * @param array $errors
     *
     * @return array
     */
    public function extraValidationForStep2(array $user_data, array $errors): array
    {
        return $this->validateUserCruksOnRegistration($user_data);
    }

    /**
     * Returns 3 options:
     * 'Y' - user is self excluded
     * 'N' - user is not self excluded
     * 'ERROR' - couldn't check the user
     *
     * @param DBUser|bool|string|null $user
     *
     * @return string
     * @throws Exception
     */
    public function checkGamStop($user = null): string
    {
        $user = cu($user);

        if (empty($user)) {
            return self::SELF_EXCLUSION_ERROR;
        }

        $cruks_code = $user->getSetting('cruks_code');
        $user_data = [
            'user_id' => $user->getId(),
            'lastname' => $user->getData('lastname'),
            'dob' => $user->getData('dob'),
            'bsn' => $user->getSetting('citizen_service_number'),
            'firstname' => $user->getData('firstname'),
            'last_name_prefix' => $user->getSetting('last_name_prefix'),
            'birth_location' => $user->getSetting('birth_location'),
        ];

        if($cruks_code) {
            $user_data['cruks_code'] = $cruks_code;
        }

        $response = $this->isUserRegisteredInCruks($user, $user_data);
        /** @var array|bool|string $result */
        $response_result = $response['result'];
        $is_success = (bool) ($response['success'] ?? false);

        if ($is_success && $response_result === true) {
            // if user is registered in Cruks => user is external self excluded
            $result = self::SELF_EXCLUSION_POSITIVE;
        } elseif ($is_success && $response_result === false) {
            $result = self::SELF_EXCLUSION_NEGATIVE;
        } else {
            phive('Logger')->error(
                "NL. checkGamStop. Couldn't check user",
                ['user_data' => $user_data, 'response' => $response]
            );

            $result = self::SELF_EXCLUSION_ERROR;
        }

        return $result;
    }

    /**
     * @param DBUser $u_obj
     */
    private function checkUserDOB(DBUser $u_obj): void
    {
        $dob = $u_obj->getData('dob');

        if (empty($dob)) {
            phive('Logger')->error('Error. checkUserDOB. Empty dob of user: #' . $u_obj->getId());

            return;
        }

        $min_young_years = self::MIN_YOUNG_ADULT_AGE;
        $max_young_years = self::MAX_YOUNG_ADULT_AGE;
        $min_young_years_stamp = strtotime("-{$min_young_years} year");
        $max_young_years_stamp = strtotime("-{$max_young_years} year");
        $dob_stamp = strtotime($dob);

        // If user is 18-23 (include 23) years old => add trigger `young adult`
        if ($dob_stamp > $max_young_years_stamp && $dob_stamp <= $min_young_years_stamp) {
            $this->uh->logTrigger($u_obj, self::RG_YOUNG_ADULT, 'User is young adult');
        }
    }

    /**
     * Initiates rg popup in game page
     *
     * @return bool
     */
    public function handleRgPopupInGamePage(): bool
    {
        $user = cu();

        if (empty($user)) {
            return false;
        }
        ?>
        window.rgLimitPopupHandler = lic('rgLimitPopupHandler', []);
        window.rgLimitPopupHandler.showGamingExperiencePopup({on_game_page: 'yes'});
        <?php
        return true;
    }

    /**
     * Show login limit rg page
     *
     * @return bool
     */
    public function showLoginLimit(): bool
    {
        return true;
    }

    /**
     * To check if rg limits should be shown after registration.
     * If any one of limits defined in config is not set return true
     *
     * @return bool
     */
    public function showRgLimitsOnRegistrationComplete(): bool
    {
        $user = cu();
        $post_registration_popups = $this->getLicSetting('post_registration_popup');

        $result = false;
        foreach ($post_registration_popups as $post_registration_popup) {
            $limit = rgLimits()->getSingleLimit($user, $post_registration_popup);

            if(empty($limit)) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * Registration step 2 fields
     *
     * @return string[][]
     */
    public function registrationStep2Fields(): array
    {
        if (phive()->isMobile()) {
            return [
                'left' => ['firstname_initials', 'firstname', 'lastname', 'citizen_service_number', 'iban', 'birthdate', 'birth_place', 'address', 'zipcode', 'city', 'doc_type', 'doc_number', 'doc_issued_by', 'doc_issue_date', 'preferred_lang', 'currency'],
                'right' => ['sex', 'email_code', 'eighteen', 'honest_player']
            ];
        }
        return [
            'left' => ['firstname_initials', 'firstname', 'lastname', 'citizen_service_number', 'iban', 'birthdate', 'birth_place', 'address', 'zipcode', 'city', 'doc_type', 'doc_number'],
            'right' => ['doc_issued_by', 'doc_issue_date', 'preferred_lang', 'currency', 'sex', 'email_code', 'eighteen', 'honest_player']
        ];
    }

    /**
     * Get options for field: document type
     *
     * @return string[]
     */
    public function getDocumentTypeList(): array
    {
        $document_list = phive('Config')->valAsArray('license-nl', 'registration-document-list', ' ', ':');

        return array_map(static function ($document_name) {
            return t($document_name);
        }, $document_list ?? []);
    }

    /**
     * Get options for field: document issued by
     *
     * @return array|null
     */
    public function getDocumentIssuedByList(): ?array
    {
        $document_list = phive('Config')->valAsArray('license-nl', 'registration-document-issuer-list', ' ', ':');

        return array_map(static function ($document_name) {
            return t($document_name);
        }, $document_list ?? []);
    }

    /**
     * Get extra field keys required only for this license
     *
     * @param string $field
     * @param string $field_key
     * @return string|null
     */
    public function getRegistrationFieldsExtra(string $field, string $field_key): ?string
    {
        $configs = [
            'doc_type' => [
                'input_placeholder' => 'registration.doc_type.input-placeholder',
            ],
            'doc_number' => [
                'input_placeholder' => 'registration.doc_number.input-placeholder'
            ]
        ];
        if (!$configs[$field]) {
            return null;
        }
        return $configs[$field][$field_key];
    }

    /**
     * Reload the prefill data for registration step 2
     *
     * @param DBUser $user
     * @return void
     */
    public function rehydrateRegistrationSessionParameters($user): void
    {
        if (!lic('hasPrepopulatedStep2')) {
            return;
        }

        $nid_data = $user->getSetting('nid_data');
        if (empty($nid_data)) {
            return;
        }

        $nid_data = json_decode($nid_data, true);
        if (empty($nid_data)) {
            return;
        }

        $_SESSION['ext_normal_user'] = true;
        $_SESSION['rstep2'] = $this->mapLookupData($nid_data);
        $_SESSION['rstep2_disabled'] = $_SESSION['rstep2'];
    }

    /**
     * Save extra information on the user
     *
     * @param DBUser $user
     * @param array $fields
     */
    public function saveExtraInformation(DBUser $user, array $fields): void
    {
        $fields['doc_issue_date'] = implode('-', [$fields['doc_year'], $fields['doc_month'], $fields['doc_date']]);
        parent::saveExtraInformation($user, $fields);
    }

    public function onLogin(DBUser $u_obj) {
        parent::onLogin($u_obj);
    }

    /**
     * Used by "user-service-laravel" and "new game mode"
     * return all the data needed to display the strip - NO HTML
     */
    public function getBaseGameParams($user = null) {
        $licensing_strip_settings = $this->getLicSetting('licensing_strip');
        $self_assesment_logo = 'Loke_' . (isMobileSite() ? 'Mobile_' : 'Desktop_') . 'Icon.png';

        $res = [
            'self_assessment' => [
                'url' => $licensing_strip_settings['self_assessment_link'],
                'img' => $this->imgUri($self_assesment_logo)
            ],
            'self_exclusion' => [
                'url' => $licensing_strip_settings['self_exclusion_link'],
                'img' => $this->imgUri('Kansspelautoriteit_Icon.png')
            ]
        ];
        if (!empty($user)) {
            $res['elapsed_session_time'] = $this->getSessionLength($user);
            $res['bet_slip'] = [
                'url' => phive('UserHandler')->getUserAccountUrl('my-profile'),
                'img' => $this->imgUri('bet_slip.png')
            ];
            $res['account_limits'] = [
                'url' => $this->getRespGamingUrl($user),
                'img' => $this->imgUri('RG-Icon.png')
            ];
        }
        if($this->getLicSetting('rg-over_age')) {
            $res['over_age'] = [
                'url' => '',
                'img' => $this->imgUri('18+W.png')
            ];
        }

        return $res;
    }

    /**
     * Jurisdiction specific validations before a Withdrawal.
     *
     * @param DBUser $user The user making the withdrawal.
     * @param array $request The withdrawal request parameters.
     * @return string|null The localized error message if validation failed, or null if the validation was successful.
     */
    public function validateWithdraw(DBUser $user, array $request): ?string
    {

        $iban = (string)($request['iban'] ?? '');

        if ($user->getSetting('bluem_iban_check_passed') && ($user->getSetting('iban') == $iban)) {
            phive('Logger')->debug('bluem-iban-check-ignoring', [
                'url' => $_SERVER['REQUEST_URI'] ?? null,
                'file' => __METHOD__ . '::' . __LINE__,
                'message' => 'Bluem IBAN check was already successful for this IBAN.',
                'user_id' => $user->getId(),
            ]);
            return null;
        }
        return t('ibanincorrect');
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


    public function rgLogo($type = 'white', $extra_classes = '', $lang = null)
    {
        $user = cu();
        $licensing_strip_settings = $this->getBaseGameParams($user);
        $add_margin_bottom = isMobileSite() ? '' : 'margin-five-bottom';

        ?>
        <div class="rg-top__item rg-logo vs-sticky-bar__images <?php echo $extra_classes ?>" id="vs-sticky-bar__images">
            <a href="<?= $licensing_strip_settings['bet_slip']['url'] ?>">
                <img src="<?= $licensing_strip_settings['bet_slip']['img'] ?>" id="vs-sticky-bar-image__account-slip" class="vs-sticky-bar__image">
            </a>
            <a href="<?= $licensing_strip_settings['account_limits']['url'] ?>">
                <img src="<?= $licensing_strip_settings['account_limits']['img'] ?>" id="vs-sticky-bar-image__account-limits" class="vs-sticky-bar__image">
            </a>
            <a href="<?= $licensing_strip_settings['self_assessment']['url'] ?>" target="_blank" rel="noopener noreferrer">
                <img src="<?= $licensing_strip_settings['self_assessment']['img'] ?>" id="vs-sticky-bar-image__self-assesment" class="vs-sticky-bar__image <?php echo $add_margin_bottom ?>">
            </a>
            <a href="<?= $licensing_strip_settings['self_exclusion']['url'] ?>" target="_blank" rel="noopener noreferrer">
                <img src="<?= $licensing_strip_settings['self_exclusion']['img'] ?>" id="vs-sticky-bar-image__self-exclusion" class="vs-sticky-bar__image">
            </a>
        </div>
        <?php
    }

    /**
     * Determine if we need to show licensing strip on current site (phive/diamondbet):
     * everywhere on both desktop and mobile
     *
     * @return bool
     */
    public function showTopLogos()
    {
        return !p('admin_top') && $this->getLicSetting('rg-buttons');
    }

    public function topLogos($type = 'white', $extra_classes = '')
    {
        if ($this->showTopLogos()) {
            return phive()->ob(function () use ($extra_classes) {
                $this->rgLogo('margin-five-top ' . $extra_classes);
            });
        }
        return false;
    }

    public function topMobileLogos($lang = null)
    {
        if ($this->getLicSetting('rg-buttons')) {
            return phive()->ob(function () use ($lang) {
                $this->rgLogo('rg-mobile-top', 'flex-center', $lang);
            });
        }
        return false;
    }

    public function rgClockTime($user)
    {
        return $this->getSessionLength($user);
    }

    /**
     * Jurisdiction specific validations before a deposit.
     *
     * @param DBUser $user The user making the deposit.
     * @param string $deposit_type e.g. 'bank', 'ccard' etc.
     * @param array $request The deposit request parameters.
     * @return string|null The localized error message if validation failed, or null if the validation was successful.
     */
    public function validateDeposit(DBUser $user, string $deposit_type, array $request): ?string
    {
        if ($deposit_type != 'bank') {
            return null;
        }

        $iban = (string)($request['iban'] ?? '');
        $err = PhiveValidator::start($iban)->iban()->error;
        if ($err) {
            phive('Logger')->info('bluem-iban-check-aborted', [
                'url' => $_SERVER['REQUEST_URI'] ?? null,
                'file' => __METHOD__ . '::' . __LINE__,
                'message' => 'Invalid IBAN format. Not sending to Bluem.',
                'IBAN' => $iban,
                'user_id' => $user->getId(),
            ]);
            return $err;
        }

        if ($user->getSetting('bluem_iban_check_passed') && ($user->getSetting('iban') == $iban)) {
            phive('Logger')->debug('bluem-iban-check-ignoring', [
                'url' => $_SERVER['REQUEST_URI'] ?? null,
                'file' => __METHOD__ . '::' . __LINE__,
                'message' => 'Bluem IBAN check was already successful for this IBAN.',
                'user_id' => $user->getId(),
            ]);
            return null;
        }

        $response = $this->checkIBAN($user, ['iban' => $iban]);
        if (($response['success'] ?? null) && ($response['result'] ?? null)) {
            $this->onIbanCheckSucceeded($user, $iban);
            return null;
        }

        $this->onIbanCheckFailed($user, $iban);
        phive('Logger')->debug('bluem-iban-check-failed', [
            'url' => $_SERVER['REQUEST_URI'] ?? null,
            'file' => __METHOD__ . '::' . __LINE__,
            'response' => $response,
            'IBAN' => $iban,
            'user_id' => $user->getId(),
        ]);
        return $response['message'] ?? t('ibanincorrect');
    }

    /**
     * Removes the play_block, deposit_block and withdrawal_block settings, if they were set due to a previous IBAN check failure.
     *
     * @param DBUser $user
     * @param string $iban
     */
    private function onIbanCheckSucceeded(DBUser $user, string $iban)
    {
        $user->setSetting('iban', $iban);
        $user->setSetting('bluem_iban_check_passed', true);

        if ((int)$user->getSetting('count_failed_iban_checks') > 0) {
            $user->deleteSetting('play_block');
            $user->deleteSetting('deposit_block');
            $user->deleteSetting('withdrawal_block');
        }
        $user->setSetting('count_failed_iban_checks', 0);
    }

    /**
     * @param DBUser $user
     * @param string $iban
     */
    private function onIbanCheckFailed(DBUser $user, string $iban)
    {
        $user->setSetting('bluem_iban_check_passed', false);

        $count = (int)$user->getSetting('count_failed_iban_checks') + 1;
        $user->setSetting('count_failed_iban_checks', $count);
        $iban_settings = $this->getLicSetting('bluem')['iban_check'];
        $max_failed_checks = $iban_settings['max_failed_checks'] ?? 0;
        if ($count >= $max_failed_checks) {
            $user->playBlock();
            $user->depositBlock();
            $user->setSetting('withdrawal_block', 1);
        }
    }

    /**
     * Invoked after a deposit was created.
     *
     * @param $user
     * @param array|null $args Keys: 'transaction_id', 'iban' (used on psp page), 'user_full_name' (used on psp page)
     * @return array
     * @example onDepositNotification($user, [])
     */
    public function postValidateDeposit($user, ?array $args): array
    {
        if (empty($user = cu($user))) {
            return ['transaction_rejected' => false, 'message' => "Missing user."];
        }

        $iban_settings = $this->getLicSetting('bluem')['iban_check'];
        $iban_check_enabled = $iban_settings['enabled'];
        if (!$iban_check_enabled) {
            return ['transaction_rejected' => false, 'message' => t('bluem.iban.check.not_enabled')];
        }

        $tr = phive('CasinoCashier')->getDeposit($args['transaction_id'] ?? 0, $user->getId());
        if (!$tr) {
            return ['transaction_rejected' => false, 'message' => "Transaction not found."];
        }

        if (($tr['dep_type'] != 'ideal') || ($tr['status'] != 'approved')) {
            return ['transaction_rejected' => false, 'message' => "Ignoring this transaction."];
        }

        $reportData = [
            'user_id' => $tr['user_id'],
            'mts_id' => $tr['mts_id'],
            'transaction_id' => $tr['mts_id'],
            'amount' => $tr['amount'],
            'supplier' => $tr['dep_type'],
        ];

        if (!($args['iban'] ?? null)) {
            $this->handleReportingEvent('failed_deposit', $reportData);
            return ['transaction_rejected' => true, 'message' => "Missing IBAN."];
        }

        if ($user->getSetting('bluem_iban_check_passed') && ($user->getSetting('iban') === $args['iban'])) {
            return ['transaction_rejected' => false, 'message' => "Matching IBAN for deposit."];
        }

        if ($iban_settings['__debug_accept_all_iban__'] ?? null) {
            return ['transaction_rejected' => false, 'message' => "Debug setting accepts all IBANs."];
        }

        $this->handleReportingEvent('failed_deposit', $reportData);
        $req = [
            'transaction_id' => $tr['mts_id'],
            'amount' => $tr['amount'],
            'supplier' => $tr['dep_type'],
            'reference_id' => $tr['ext_id'],
            'extra' => [
                'user_block_status' => DBUserHandler::USER_BLOCK_STATUS_NO_BLOCK,
            ],
        ];
        phive('CasinoCashier')->revertDeposit($user, $req, true, $tr['dep_type']);

        return ['transaction_rejected' => true, 'message' => "IBAN mismatch."];
    }

    /**
     * Invoked after a deposit is cancelled.
     *
     * @param $user
     * @param array|null $args
     */
    public function onCancelledDeposit($user, ?array $args)
    {
        if (($args['supplier'] ?? null) !== 'ideal') {
            return;
        }

        $reportData = [
            'user_id' => $args['user_id'],
            'mts_id' => $args['transaction_id'],
            'transaction_id' => $args['transaction_id'],
            'amount' => $args['amount'],
            'supplier' => $args['supplier'],
        ];

        $this->handleReportingEvent('failed_deposit', $reportData);
    }

    /**
     * Dummy method to be removed when merging with NL.
     */
    public function handleReportingEvent(string $event, array $data): void
    {
        phive('Logger')->debug('_dummy_ ' . __METHOD__ . '::' . __LINE__, [
            'url' => $_SERVER['REQUEST_URI'] ?? null,
            'event' => $event,
            'data' => $data,
            'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)
        ]);
    }

    /**
     * Validate user with Cruks
     * If user is self excluded - blocks user and returns a validation error
     * If user's data is not correct - returns a validation error
     *
     * @param array $user_data
     *
     * @return array
     */
    private function validateUserCruksOnRegistration(array $user_data): array
    {
        $user_id = $user_data['user_id'] ?? '';
        $user = cu($user_id);

        if (empty($user)) {
            phive('Logger')->error(
                'NL. extraValidationForStep2. empty `user`',
                ['user_data' => $user_data]
            );

            return [];
        }

        // If Cruks is disabled
        // Or if user is a test account
        if ($this->getLicSetting('gamstop')['is_active'] !== true || $user->isTestAccount()) {
            return [];
        }

        $data = [
            'user_id' => $user_id,
            'bsn' => $user_data['citizen_service_number'] ?? '',
            'firstname' => $user_data['firstname'],
            'lastname' => $user_data['lastname'],
            'last_name_prefix' => $user_data['last_name_prefix'] ?? '',
            'dob' => $user_data['dob'],
            'birth_location' => $user_data['birth_place'] ?? '',
        ];

        $response = $this->isUserRegisteredInCruks($user, $data);
        /** @var array|bool|string $response_result */
        $response_result = $response['result'];
        $is_success = (bool) ($response['success'] ?? false);
        $key = !empty($user_data['citizen_service_number']) ? 'citizen_service_number' : 'lastname';
        $validation_errors = [];

        if (!$is_success && is_array($response_result) && !empty($response_result['ErrorCode']) && !empty($response_result['ErrorMessage'])) {
            // if user's data wrong, we could have ErrorMessage from Cruks. Example: 'Person is not known with the BSN'
            $validation_errors[$key] = 'errors.user.external_validation';
        } elseif (!$is_success && is_string($response_result) && $response_result !== 'connection_error') {
            // If we have internal validation error
            $validation_errors[$key] = 'errors.user.internal_validation';
        } elseif ($is_success && $response_result === true) {
            // if user is registered in Cruks => user is external self excluded
            $validation_errors[$key] = 'errors.user.external_self_excluded';
            phive('DBUserHandler')->updateUser($user, $user_data);
            $this->hasExternalSelfExclusionCommon($user, self::SELF_EXCLUSION_POSITIVE);
        } elseif ($response_result == 'connection_error') {
            lics('onCRUKSServiceDown');
        }

        return $validation_errors;
    }

    /**
     * Hide Battles - encore button
     *
     * @return bool
     */
    public function hideBattles()
    {
        return true;
    }

    /**
     * @param mixed $user_id The user id or the (already initialized) user object
     * @return bool
     */
    public function isUserAccountClosed($user): bool
    {
        $user = cu($user);
        return $user->getSetting('closed_account') == 1;
    }

    /**
     * Show login redirects
     *
     * @param $lang
     * @return bool
     */
    public function loginRedirects($lang): bool
    {
        if (!isLogged()) {
            return false;
        }

        $rg_configs = $this->getLicSetting('rg_info');

        $required_limit_types = $this->getLicSetting('post_registration_popup');

        $limits_set = true;
        $user = cu();
        foreach($required_limit_types as $limit_type) {
            $rg_limit = $this->rgLimits()->getByTypeUser($user, $limit_type);

            if(empty($rg_limit)) {
                $limits_set = false;
                break;
            }
        }

        if(!empty($rg_configs['popup_active']) && empty($_SESSION['lic_login_redirects']) && $limits_set){
            $_SESSION['lic_login_redirects'] = true;
            $url = phive()->isMobile() ? 'rg-activity' : '?rg_login_info=true';

            phive('Redirect')->to($url, $lang, true, "302 Found");
        }

        return true;
    }

    /**
     * Will listen to WS with tag `updated_user_balance`
     * And if the balance exceeds the current limit,
     * balance limit popup will be triggered.
     *
     * Function will be called in Game play page for balance change during game play.
     *
     * @param DBUser $user
     * @return void
     */
    public function doBalanceCheckInGamePlay(DBUser $user): void
    {
        if(!$this->hasBalanceTypeLimit()) {
            return;
        }

        $limit = RgLimits()->getSingleLimit($user, 'balance');

        if(!$limit) {
            return;
        }

        $ws_url = $this->getExceededBalanceLimitWsUrl($user);
        ?>
        doWs('<?php echo $ws_url ?>', function (e) {
            GameCommunicator.waitRoundFinished(function() {
                var res = JSON.parse(e.data),
                balance = res.new_balance;
                licFuncs.showBalanceLimitPopup({action: 'game_play', amount: balance});
            });
        });
        <?php
    }

    /**
     * @param DBUser $user
     *
     * @return string
     */
    public function getExceededBalanceLimitWsUrl(DBUser $user): string
    {
        $tag = "updated_user_balance";
        $channel = phive('UserHandler')->wsChannel($tag, $user->getId());

        return phive('UserHandler')->wsUrl("exceeded_balance_limit", true, [], $channel);
    }

    /**
     * Hide deposit and login limit remove button.
     *
     * @param $type
     * @return bool
     */
    public function hideRgRemoveLimit($type)
    {
        return $type === 'login' || $type === 'deposit';
    }

    /**
     * @param null $user
     * @param string $gamstop_res
     * @return bool
     */
    public function hasExternalSelfExclusion($user = null, $gamstop_res = ''): bool
    {
        return $this->hasExternalSelfExclusionCommon($user, $gamstop_res);
    }

    /**
     * @param DBUser $user
     */
    public function clearBSN(DBUser $user)
    {
        if($user->hasSetting('citizen_service_number') && $user->hasSetting('cruks_code')) {
            $user->deleteSetting('citizen_service_number');
        }
    }
}
