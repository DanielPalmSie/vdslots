<?php
require_once __DIR__ . '/../Traits/RealityCheckTrait.php';

class MT extends Licensed
{
    use RealityCheckTrait;

    protected $extra_registration_fields = [
            'step1' => [],
            'step2' => [],
    ];

    protected array $fields_to_save_into_users_settings = [
            'place_of_birth',
            'nationality'
    ];

    /**
     * Just a temporary mock function for testing purposes, to be removed in a later stage
     * @return mixed
     */
    public function testIso()
    {
        return $this->getLicSetting('test');
    }

    /**
     * @return array[]
     */
    public function registrationStep2Fields()
    {
        if (phive()->isMobile()) {
            $fields = [
                'left' => ['firstname', 'lastname', 'address', 'zipcode', 'city', 'nationality', 'place_of_birth', 'bonus_code', 'currency', 'preferred_lang'],
                'right' => ['birthdate', 'sex', 'email_code', 'eighteen']
            ];
        } else {
            $fields = [
                'left' => ['firstname', 'lastname', 'address', 'zipcode', 'city', 'nationality', 'place_of_birth', 'preferred_lang', 'bonus_code'],
                'right' => ['birthdate', 'currency', 'sex', 'email_code', 'eighteen']
            ];
        }

        $hideFields = $this->getLicSetting('hide_user_registration_fields');
        $isMobile = phive()->isMobile();

        foreach ($hideFields as $fieldName) {
            if ($fieldName === 'preferred_lang') {
                $side = 'left';
            } else if ($fieldName ==='currency') {
                $side = $isMobile ? 'left' : 'right';
            } else {
                continue;
            }

            // Search and remove the field if it exists
            $index = array_search($fieldName, $fields[$side]);
            if ($index !== false) {
                array_splice($fields[$side], $index, 1);
            }
        }

        return $fields;
    }

    /**
     * @param DBUser $u_obj
     * @param bool   $is_api
     *
     * @return void
     */
    public function onLogin(DBUser $u_obj, bool $is_api = false)
    {
        parent::onLogin($u_obj);

        if ($is_api) {
            return;
        }

        if (licSetting('require_main_province', $u_obj) && !$u_obj->hasSetting('main_province')) {
            $_SESSION['show_add_province_popup'] = true;
        }

        if ($u_obj->hasSetting('nationality_birth_country_required')) {
            $_SESSION['show_add_nationalityandpob_popup'] = true;
        }
    }

    /**
     * Invoked after a deposit successful.
     *
     * @param $user
     * @param array|null $args
     */
    public function onSuccessfulDeposit($user, ?array $args)
    {
        $this->addNationalityBirthCountrySetting($user);
    }

    /**
     * @return array
     */
    public function getSelfExclusionTimeOptions()
    {
        return [183, 365, 730, 1095, 1825];
    }


    /**
     * Override default message for RC dialog
     *
     * @return string
     */
    public function getRcPopupMessageString()
    {
        return 'reality-check.msg.elapsedtime.www';
    }

    /**
     * Override default getRcPopupMessageData()
     * @param $user
     * @param string $ext_game_name
     * @return array
     */
    public function getRcPopupMessageData($user, string $ext_game_name)
    {
        $user = cu($user);
        if (empty($user)) {
            return [];
        }
        $rg = phive('Licensed')->rgLimits();
        $rg_limits = $rg->getRcLimit($user);
        $balance = $user->winLossBalance();

        return [
            'minutes' => $rg_limits['cur_lim'], // interval
            'minutes_reached' => $this->getMinutesReached($user, $ext_game_name),
            'currency' => $user->data['currency'],
            'winloss' => nfCents($balance->getTotal(), true),
            'loss' => nfCents($balance->getLoss(), true),
            'win' => nfCents($balance->getWin(), true),
        ];
    }

    /**
     * Makes possible to disable prefilling of a Country Value
     * @return string
     */
    public function getBirthCountryValue(): string
    {
        return '';
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
     * Skips creating a Label for a Dropdown Select
     * @param string|null $key
     *
     * @return bool
     */
    public function shouldDisableLabel(?string $key): bool
    {
        if($key == 'birth_country'){
            return true;
        }

        return false;
    }

    /**
     * Gets List of countries
     *
     * @return array
     */
    public function getBirthCountryList(): array
    {
        return lic('getNationalities') ?? [];
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
        
        if (privileged($user)) {
            return false;
        }
        
        return $this->getLicSetting('intended_gambling') &&
            empty($user->getSetting('intended_gambling'));
    }
}
