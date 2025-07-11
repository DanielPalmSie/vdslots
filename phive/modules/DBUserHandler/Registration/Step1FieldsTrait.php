<?php

use Laraphive\Domain\User\Actions\Step1FieldsFactory;
use Laraphive\Domain\User\Actions\Steps\DataTransferObjects\Step1FieldsData;
use Laraphive\Domain\User\Factories\CountryServiceFactory;

/**
 * Trait Step1FieldsTrait
 */
trait Step1FieldsTrait
{
    /**
     * Main configuration for all registration step 1 fields
     * Returns only the fields which are required based on $country
     *
     * @deprecated use @link \Laraphive\Domain\User\Actions\GetStep1FieldsAction::class
     *
     * @param $country
     * @param bool $plain // used to send the full list of fields
     * @return array[]
     */
    public static function step1Fields($country, $plain = false)
    {
        /** @var DBUserHandler $DBUserHandler */
        $DBUserHandler = phive('DBUserHandler');

        /**
         * key is the id of the input field
         * value is an array which contains input specific configuration
         * Closures explanation:
         *  show: returns if the field is required or not
         *  validate: returns null when validation succeeded and error message when validation failed
         */
        $fields = [
            'personal_number' => [
                'value' => $_SESSION['rstep1']['personal_number'],
                'disabled' => !empty($_SESSION['rstep2']) ? true : null,
                'placeholder' => 'register.personal_number.nostar',
                'error_message' => !empty(lic('personalNumberMessage', [false])) ? lic('personalNumberMessage', [false]) : 'register.personal_number.error.message',
                'show' => function () use ($country) {
                    if (empty(phive('DBUserHandler')->showNationalId($country))) {
                        return false;
                    }

                    if (lic('hasExtVerification')) {
                        return false;
                    }

                    return true;
                },
                'validate' => function ($value) use ($DBUserHandler, $country) {
                    if ($DBUserHandler->doubleNid($value, $country)) {
                        if (lic('personalNumberMessage')) {
                            return lic('personalNumberMessage', [false]);
                        }
                        return 'register.err.invalid.personal.number';
                    }

                    return lic('validatePersonalNumber', [$value]);
                }
            ],
            'username' => [
                'value' => $_SESSION['rstep1']['username'],
                'disabled' => !empty($_SESSION['rstep2']) ? true : null,
                'placeholder' => 'register.username.nostar',
                'error_message' => 'register.username.error.message',
                'show' => function () use ($DBUserHandler) {
                    if (empty($DBUserHandler->getSetting('show_username'))) {
                        return false;
                    }
                    return true;
                },
                'validate' => function ($value, $form = []) use ($DBUserHandler) {
                    if (!empty($DBUserHandler->countUsersWhere('username', $value))) {
                        return 'register.err.username.taken';
                    }

                    if (is_numeric($value)) {
                        return 'register.err.numerical';
                    }

                    return null;
                }
            ],
            'email' => [
                'value' => $_SESSION['rstep1']['email'],
                'placeholder' => 'register.email.nostar',
                'error_message' => 'register.email.error.message',
                'validate' => function ($value, $form = []) use ($DBUserHandler) {
                    if ($DBUserHandler->getSetting('full_registration') && $value !== $form['secemail']) {
                        return 'register.err.email.not.same';
                    }
                    // Prevent checking against DB when we go back to step 1 from step 2 and data is equal to what we have in the session
                    if(!empty($_SESSION['rstep2'])) {
                        if($value === $_SESSION['rstep1']['email']) {
                            return null;
                        }
                    }
                    $not_used = [];
                    $error_message = $DBUserHandler->doubleEmail($not_used, '', true, $value);
                    if (!empty($error_message)) {
                        return 'register.err.' . $error_message;
                    }

                    return null;
                }
            ],
            'secemail' => [
                'placeholder' => 'register.email2.nostar',
                'value' => $_SESSION['rstep1']['secemail'],
                'error_message' => '',
                'show' => function () use ($DBUserHandler) {
                    if (empty($DBUserHandler->getSetting('full_registration')) || lic('hasExtVerification')) {
                        return false;
                    }
                    return true;
                }
            ],
            'password' => [
                'value' => $_SESSION['rstep1']['password'],
                'disabled' => !empty($_SESSION['rstep2']) ? true : null,
                'placeholder' => 'register.password.nostar',
                'error_message' => 'register.password.error.message',
                'show' => function () {
                    if (lic('hasExtVerification') && !lic('forceRegStep1Password')) {
                        return false;
                    }
                    return true;
                }
            ],
            'secpassword' => [
                'value' => $_SESSION['rstep1']['password'],
                'disabled' => !empty($_SESSION['rstep2']) ? true : null,
                'placeholder' => 'register.secpassword.nostar',
                'error_message' => '',
                'show' => function () use ($DBUserHandler) {
                    if (lic('hasExtVerification') && !lic('forceRegStep1Password')) {
                        return false;
                    }
                    if (empty($DBUserHandler->getSetting('full_registration'))) {
                        return false;
                    }
                    return true;
                }
            ],
            'country' => [
                'value' => $country ?? $_SESSION['rstep1']['country'] ?? phive('IpBlock')->getCountry(),
                'placeholder' => '',
                'disabled' => lic('shouldDisableInput', ['country']),
                'error_message' => '',
                'validate' => function ($value) {
                    $countries = array_diff_key(
                        phive('Cashier')->displayBankCountries(phive('Cashier')->getBankCountries('', true), [], false),
                        phive('Config')->valAsArray('countries', 'block')
                    );
                    if (!isset($countries[$value])) {
                        return 'register.err.country';
                    }
                    return null;
                }
            ],
            'mobile' => [
                'value' => $_SESSION['rstep1']['mobile'],
                'placeholder' => 'register.mobile.nostar',
                'error_message' => '',
                'validate' => function ($value, $form = []) use ($DBUserHandler) {
                    // Prevent checking against DB when we go back to step 1 from step 2 and data is equal to what we have in the session
                    if(!empty($_SESSION['rstep2'])) {
                        $mobile = $form['country_prefix'].$value;
                        if($mobile === $_SESSION['rstep1']['full_mobile']) {
                            return null;
                        }
                    }

                    $not_used = [];
                    $error_message = $DBUserHandler->isValidMobile($not_used, '', true, $form['country_prefix'], $value);
                    if (!empty($error_message)) {
                        return 'register.err.' . $error_message;
                    }
                    return null;
                }
            ],
            'country_prefix' => [
                'value' => empty($prefix = phive('Cashier')->phoneFromIso($country)) ? '' : $prefix,
                'disabled' => true,
                'placeholder' => '',
                'error_message' => ''
            ],
            'referring_friend' => [
                'value' => filter_input(INPUT_POST, 'referring_friend', FILTER_SANITIZE_STRING),
                'placeholder' => 'register.step1.refer.info.html',
                'show' => function () {
                    if (empty(phive('DBUserHandler')->getSetting('refer_in_reg'))) {
                        return false;
                    }
                    return true;
                }
            ],
            'security_question' => [
                'value' => $_SESSION['rstep1']['security_question'],
                'disabled' => !empty($_SESSION['rstep2']) ? true : null,
                'placeholder' => 'register.security.question',
                'error_message' => 'register.security.question.error.message',
                'show' => function () {
                    if (empty(lic('getLicSetting', ['security_question']))) {
                        return false;
                    }
                    return true;
                },
                'validate' => function ($value, $form = []) {
                    $security_questions = array_keys(self::getSecurityQuestions());

                    if (!in_array($value, $security_questions)) {
                        return 'register.security.question.error.message';
                    }

                    return null;
                }
            ],
            'security_answer' => [
                'value' => $_SESSION['rstep1']['security_answer'],
                'disabled' => !empty($_SESSION['rstep2']) ? true : null,
                'placeholder' => 'register.security.answer',
                'error_message' => 'register.security.answer.error.message',
                'show' => function () {
                    if (empty(lic('getLicSetting', ['security_question']))) {
                        return false;
                    }
                    return true;
                },
                'validate' => function ($value, $form = []) {
                    if (strlen($value) === 0) {
                        return 'register.security.answer.error.message';
                    }

                    return null;
                }
            ],
            'csrf_token' => [
                'show' => function() {
                    return true;
                }
            ]
        ];

        if (!empty($plain)) {
            return $fields;
        }

        // filter out the fields which are not required
        foreach ($fields as $field => $config) {
            $visible = $config['show'];
            if (empty($visible)) {
                continue;
            }
            if (!$visible()) {
                unset($fields[$field]);
            }
        }

        return $fields;
    }

    /**
     * Used to return required fields for $country to the FE
     * Applies common logic like translation
     * Removes all closures
     *
     * @deprecated use @link \Laraphive\Domain\User\Actions\GetStep1FieldsAction::class
     *
     * @param $country
     * @param bool $translate
     * @return array
     */
    public static function getStep1Fields($country, $translate = true)
    {
        $result = [];

        foreach (self::step1Fields($country) as $field => $config) {
            if ($translate) {
                $config['placeholder'] = t($config['placeholder']);
                $config['error_message'] = t($config['error_message']);
            }

            unset($config['show'], $config['validate']);
            $result[$field] = $config;
        }

        return $result;
    }

    /**
     * Validate all $form data received from FE
     *
     * @deprecated
     *
     * @param $form
     * @param $country
     * @param bool $translate
     * @return array
     */
    public static function validateStep1Fields($form, $country, $translate = true)
    {
        /** @var QuickFire $QuickFire */
        $QuickFire = phive('QuickFire');
        /** @var UserHandler $UserHandler */
        $UserHandler = phive('DBUserHandler');

        // get required fields so that we don't rely on data received from FE
        $fields = self::step1Fields($country);
        // get required checkboxes so that we don't rely on the ones received from FE
        $checkboxes = self::getStep1Checkboxes();

        foreach(array_merge(array_keys($fields), $checkboxes) as $f) {
            $_SESSION['rstep1'][$f] = $_POST[$f];
        }

        if (!empty($_SESSION['rstep2'])) {
            // prevent validating password because it can't be changed anyway
            unset($fields['password']);
        }

        // ported old validation
        $errors = $QuickFire->validate($UserHandler->getReqFields(array_keys($fields)));

        // validate input fields
        foreach ($fields as $field => $config) {
            $validate = $config['validate'];
            if (empty($validate)) {
                continue;
            }

            $error_message = $validate($form[$field], $form);
            if (empty($error_message)) {
                continue;
            }

            $errors[$field] = $error_message;
        }

        // validate required checkboxes
        foreach ($checkboxes as $checkbox) {
            if ($form[$checkbox] != 'on' && $form[$checkbox] != 1) {
                $errors[$checkbox] = 'register.err.not.checked';
            }
        }

        $ip = remIp();
        //if IP is not from a whitelist we are checking for a registration attempts
        if (!isWhitelistedIp($ip)){
            // this check will be moved to some extraValidations method if other similar checks will be applied in the future
            if (!phive()->isLocal() && $UserHandler->countUsersWhere('reg_ip', $ip) > 2) {
                $errors['ip'] = 'register.err.ip.toomany';
            }
        }


        // apply translations on error messages
        if ($translate) {
            foreach ($errors as $field => $message) {
                $errors[$field] = t($message);
            }
        }

        return $errors;
    }

    /**
     * Validate all $form data received from FE
     *
     * @param array $form
     * @param string $country
     * @param bool $translate
     *
     * @return array
     */
    public static function validateStep1FieldsV2(
        array $form,
        string $country,
        bool $translate = true,
        bool $isApi = true
    ) {
        $registerStep2Started = false;
        /** @var QuickFire $QuickFire */
        $QuickFire = phive('QuickFire');
        /** @var UserHandler $UserHandler */
        $UserHandler = phive('DBUserHandler');

        // get required fields so that we don't rely on data received from FE
//        $fields = self::step1Fields($country);

        $step1FieldsData = Step1FieldsData::fromArray($form);

        /** @var \Laraphive\Domain\User\Actions\Step1FieldsFactory $step1FieldsFactory */
        $step1FieldsFactory = phiveApp(Step1FieldsFactory::class);
        $countryService = CountryServiceFactory::create();

        $user = cuRegistration();

        $userId = $user ? $user->getId() : null;

        $fields = $step1FieldsFactory->make(
          $country, $countryService, null, $step1FieldsData, $userId
        );

        $fieldKeys = [];
        foreach ($fields as $field) {
            if ($field->show()) {
                $fieldKeys[] = $field->getName();
            }
        }

        // get required checkboxes so that we don't rely on the ones received from FE
        $checkboxes = self::getStep1Checkboxes();

        $postedFieldsKeys = array_merge($fieldKeys, $checkboxes);

        if ($registerStep2Started === true) {
            // prevent validating password because it can't be changed anyway
            unset($fieldKeys['password']);
            unset($fieldKeys['personal_number']);

        }

        // ported old validation
        $errors = $QuickFire->validateV2($UserHandler->getReqFields($fieldKeys), $form);

        if($isApi && isset($errors['email'])) {
            $errors['email'] = 'The email must be a valid email address.';
        }

        foreach ($fields as $field) {
            if ($isApi === true &&
                $field->getName() === 'password' &&
                $form[$field->getName()]  !== '' &&
                !in_array($field->getName(), $fieldKeys)
            ) {
                $errors[$field->getName()] = 'register.err.password.not.required';
            }

            if (!$field->show()) {
                continue;
            }

            $value = $form[$field->getName()] ?? "";
            $error_message = $field->validate($value);

            if (empty($error_message)) {
                continue;
            }

            $errors[$field->getName()] = $error_message;
        }

        // validate required checkboxes
        foreach ($checkboxes as $checkbox) {
            if ($form[$checkbox] != 'on' && $form[$checkbox] != 1) {
                $errors[$checkbox] = 'register.err.not.checked';
            }
        }

        if(isPNP($user, $country)){
            $ip = $_SESSION['rstep1']['pnp_ip'] ?? remIp();
        } else {
            $ip = remIp();
        }

        //if IP is not from a whitelist we are checking for a registration attempts
        if (!isWhitelistedIp($ip)){
            if (!phive('DBUserHandler')->isRegistrationAndLoginAllowed($ip)) {
                $errors['ip'] = 'register.err.blocked.country';
            }

            if (phive()->isMobileApp() && !phive('DBUserHandler')->isAppAllowedCountry($ip)) {
                $errors['ip'] = 'register.err.blocked.country';
            }

            // this check will be moved to some extraValidations method if other similar checks will be applied in the future
            if (!phive()->isLocal() && $UserHandler->countUsersWhere('reg_ip', $ip) > 2) {
                $errors['ip'] = 'register.err.ip.toomany';
            }
        }


        // apply translations on error messages
        if ($translate) {
            foreach ($errors as $field => $message) {
                $errors[$field] = t($message);
            }
        }

        return [$errors, $postedFieldsKeys];
    }

    /**
     * Return a list of required checkboxes
     *
     * @return string[]
     */
    public static function getStep1Checkboxes()
    {
        $checkboxes = ['privacy', 'conditions'];
        $extra_checkboxes_lic = lic('getLicSetting', ['extra_checkboxes']);

        $showExtraCheckboxes = lic('getLicSetting', ['registration_show_extra_checkboxes']);

        $extra_checkboxes = array_merge((array)$extra_checkboxes_lic, (array)$showExtraCheckboxes);

        if (!empty($extra_checkboxes)) {
            $checkboxes = array_merge($checkboxes, $extra_checkboxes);
        }

        return $checkboxes;
    }

    /**
     * @param bool $translated
     * @return array|string[]
     */
    public static function getSecurityQuestions($translated = true)
    {
        $questions = [
            "q1" => "registration.security.question1",
            "q2" => "registration.security.question2",
            "q3" => "registration.security.question3"
        ];
        if (empty($translated)) {
            return $questions;
        }
        return array_map(function ($q) {
            return t($q);
        }, $questions);
    }

    /**
     * @param DBUser $user
     * @param $request
     */
    public static function setupSecurityQuestion($user, $request)
    {
        if (empty(lic('getLicSetting', ['security_question']))) {
            return;
        }

        if (empty($user)) {
            return;
        }

        $user->setSetting('security_question', $request['security_question']);
        $user->setSetting('security_answer', $request['security_answer']);
    }
}
