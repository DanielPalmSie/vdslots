<?php

use Laraphive\Contracts\EventPublisher\EventPublisherInterface;
use Laraphive\Domain\User\Actions\Step1FieldsFactory;
use Laraphive\Domain\User\Factories\CountryServiceFactory;

/**
 * Trait Step1FieldsTrait
 */
trait Step2FieldsTrait
{
    public static $birth_country_list;
    public static $residence_country_list;
    public static $residence_province_list;
    public static $province_of_birthday_list;
    public static $industry_list;

    /**
     * @return array
     * @throws Exception
     */
    private static function getBirthCountryList(): array
    {
        if (empty(self::$birth_country_list)) {
            self::$birth_country_list = lic('getBirthCountryList');
        }
        return empty(self::$birth_country_list) ? [] : self::$birth_country_list;
    }

    /**
     * @return array
     */
    private static function getNationalities(): array
    {
        $countries = phive('SQL')->readOnly()->loadArray("SELECT iso FROM bank_countries");
        $nationalities = [];
        $topNationalities = lic('getLicSetting', ['top_nationalities']);
        foreach ($countries as $country) {
            $iso = $country['iso'];
            $nationalities[$iso] = t("country.name.{$iso}");
        }

        $topItem = [];
        if (!empty($topNationalities)) {
            // Extract keys in the correct order
            foreach ($topNationalities as $key) {
                if (isset($nationalities[$key])) {
                    $topItem[$key] = $nationalities[$key];
                    unset($nationalities[$key]);
                }
            }
        }

        asort($nationalities);

        if (!empty($topItem)) {
            return array_merge(
                ['common.countries' => ['type' => 'optgroup']],
                $topItem,
                ['uncommon.countries' => ['type' => 'optgroup']],
                $nationalities);
        }

        return $topItem + $nationalities;
    }

    /**
     * @param $user
     *
     * @return string
     */
    private static function prefillBirthCountryValue($user): string {
        $value = lic('getBirthCountryValue');

        if($value !== false){
            return $value;
        }

        return cuRegistration($user)->getCountry();
    }


    /**
     * @return false|mixed
     */
    private static function prefillNationalityValue(){
        $value = lic('getNationalityValue');

        if($value !== false){
            return $value;
        }

        return lic('getIso');
    }



    /**
     * @return array
     * @throws Exception
     */
    private static function getProvinces(): array
    {
        if (empty(self::$residence_province_list)) {
            self::$residence_province_list = lic('getProvinces');
            asort(self::$residence_province_list);
            if(($key = array_search(licSetting('removed_province'), self::$residence_province_list)) !== false){
                unset(self::$residence_province_list[$key]);
            }
        }

        return empty(self::$residence_province_list) ? [] : self::$residence_province_list;
    }

    /**
     * @return array
     */
    private static function getIndustries(): array
    {
        if (empty(self::$industry_list)) {
            self::$industry_list = lic('getIndustries');
            asort(self::$industry_list);
        }

        return empty(self::$industry_list) ? [] : self::$industry_list;
    }

    private function checkForStreet()
    {
        if (licSetting('show_street_in_registration')){
            return 'register.street.nostar';
        }
        return 'register.address.nostar';
    }

    /**
     * @return array
     * @throws Exception
     */
    private static function getAllProvinces(): array
    {
        if (empty(self::$province_of_birthday_list)) {
            self::$province_of_birthday_list = lic('getAllProvinces');
            asort(self::$province_of_birthday_list);
        }
        return empty(self::$province_of_birthday_list) ? [] : self::$province_of_birthday_list;
    }

    /**
     * @return array
     */
    private static function getResidenceCountryList(): array
    {
        if (empty(self::$residence_country_list)) {
            self::$residence_country_list = array_diff_key(
                phive('Cashier')->displayBankCountries(phive('Cashier')->getBankCountries('', true)),
                phive('Config')->valAsArray('countries', 'block')
            );
        }
        return empty(self::$residence_country_list) ? [] : self::$residence_country_list;
    }

    /**
     * Main configuration for all registration step 2 fields
     * Returns only the fields which are required based on $country
     *
     * refactor without the use of GLOBAL variables
     *
     * @param $user
     * @para $data data from globals refactor
     * @return array[]
     * @throws Exception
     */

    public static function step2Fields($user = null, $data = [])
    {
        $user = cu($user) ?: cuRegistration();
        $bonus_code = phive('Bonuses')->getBonusCode();
        if (!empty($data['rstep2']['bonus_code'])) {
            $bonus_code = $data['rstep2']['bonus_code'];
        }

        $sex = 'Male';
        if (!empty($data['sex'])) {
            $sex = $data['sex'];
        } elseif (!empty($data['rstep2']['sex'])) {
            $sex = $data['rstep2']['sex'];
        }

        $currency = $forced_currency = lic('getForcedCurrency', [], $user);
        if (empty($currency)) {
            $currency = $data['rstep2']['currency'] ?: getCur();
        }
        if (is_array($currency)) {
            $currency = $currency['code'];
        }

        $language = $forced_language = lic('getForcedLanguage', [], $user);
        if (empty($language)) {
            if (empty($data['preferred_lang'])) {
                $language = phive('Localizer')->getLanguage();
            } elseif (!empty($data['rstep2']['preferred_lang'])) {
                $language = $data['rstep2']['preferred_lang'];
            } else {
                $language = $data['preferred_lang'];
            }
        }

        $fc = new FormerCommon();
        $required_age = phive('SQL')->getValue("SELECT reg_age FROM bank_countries WHERE iso = '{$data['rstep1']['country']}'");

        $currencies = [];
        foreach (cisos(false, false, false) as $r) {
            $currencies[$r['code']] = $r['code'];
        }

        $province = $forced_province = lic('getForcedProvince', [], $user);
        if(empty($province)){
            $province = $data['rstep2']['main_province'] ?: self::getProvinces();
        }
        if(is_array($province)){
            $province = $province['iso_code'];
        }

        /**
         * key is the id of the input field
         * value is an array which contains input specific configuration
         * Closures explanation:
         *  show: returns if the field is required or not
         *  validate: returns null when validation succeeded and error message when validation failed
         */
        $force_birthyear = !empty($data['rstep2']['birthyear']) ? $data['rstep2']['birthyear'] : $data['birthyear'];
        $lic_fields = phive()->flatten(array_values(lic('registrationStep2Fields')));
        $country = $user->getCountry();

        trackRegistration($data['rstep2'], "step2Fields_sessionData");
        $fields = [
            'firstname' => [
                'name' => lic('shouldGetInputName', ['firstname']),
                'value' => $data['rstep2']['firstname'],
                'disabled' => lic('shouldDisableInput', ['firstname']),
                'placeholder' => 'register.firstname.nostar',
                'show' => function() use ($lic_fields) {
                    return in_array('firstname', $lic_fields);
                },
                'error_message' => 'register.custom.error.message'
            ],
            'lastname' => [
                'name' => lic('shouldGetInputName', ['lastname']),
                'value' => $data['rstep2']['lastname'],
                'disabled' => lic('shouldDisableInput', ['lastname']),
                'placeholder' => 'register.lastname.nostar',
                'show' => function() use ($lic_fields) {
                    return in_array('lastname', $lic_fields);
                },
                'error_message' => 'register.custom.error.message'
            ],
            'lastname_second' => [
                'name' => lic('shouldGetInputName', ['lastname_second']),
                'value' => $data['rstep2']['lastname_second'],
                'disabled' => lic('shouldDisableInput', ['lastname_second']),
                'placeholder' => 'register.lastname_second.nostar',
                'skip_frontend_validation' => true,
                'label' => 'register.lastname_second.label',
                'validate' => function ($value, $form = []) {
                    if (lic('isValidNif', [$form['personal_number']]) && empty($value)) {
                        return 'register.lastname_second.error.message';
                    }

                    return null;
                },
                'show' => function() use ($lic_fields) {
                    return in_array('lastname_second', $lic_fields);
                }
            ],
            'address' => [
                'name' => lic('shouldGetInputName', ['address']),
                'value' => $data['rstep2']['address'],
                'disabled' => lic('shouldDisableInput', ['address']),
                'placeholder' => self::checkForStreet(),
                'show' => function() use ($lic_fields) {
                    return in_array('address', $lic_fields);
                },
                'error_message' => 'register.address.error.message'
            ],
            'place_of_birth' => [
                'name' => lic('shouldGetInputName', ['place_of_birth']),
                'value' => $data['rstep2']['place_of_birth'],
                'disabled' => lic('shouldDisableInput', ['place_of_birth']),
                'placeholder' =>'register.place.of.birth',
                'validate' => function ($value) {
                    if (empty($value)) {
                        return 'place.of.birth.error.required';
                    }
                    return null;
                },
                'show' => function() use ($lic_fields) {
                    return in_array('place_of_birth', $lic_fields);
                },
                'error_message' => 'register.custom.error.message'
            ],
            'building' => [
                'name' => lic('shouldGetInputName', ['building']),
                'value' => $data['rstep2']['building'],
                'disabled' => lic('shouldDisableInput', ['building']),
                'placeholder' => 'register.building.nostar'
            ],
            'zipcode' => [
                'name' => lic('shouldGetInputName', ['zipcode']),
                'value' => $data['rstep2']['zipcode'],
                'disabled' => lic('shouldDisableInput', ['zipcode']),
                'placeholder' => 'register.zipcode.nostar',
                'show' => function() use ($lic_fields) {
                    return in_array('zipcode', $lic_fields);
                },
                'error_message' => ($country === 'CA' && $province === 'ON') ?
                        'zipcode.unvalid.CA-ON' : 'register.zipcode.error.message'
            ],
            'city' => [
                'name' => lic('shouldGetInputName', ['city']),
                'value' => $data['rstep2']['city'],
                'disabled' => lic('shouldDisableInput', ['city']),
                'placeholder' => 'register.city.nostar',
                'show' => function() use ($lic_fields) {
                    return in_array('city', $lic_fields);
                },
                'error_message' => 'register.custom.error.message'
            ],
            'preferred_lang' => [
                'value' => $language,
                'placeholder' => 'register.chooselang.nostar',
                'disabled' => lic('shouldDisableInput', ['preferred_lang']) || !empty($forced_language),
                'options' => phive("Localizer")->filterLanguageOptions($user)->getLangSelect("WHERE selectable = 1", lic('getExcludedRegistrationLanguages', [], $user)),
                'show' => function() use ($lic_fields) {
                    return in_array('preferred_lang', $lic_fields);
                }
            ],
            'bonus_code_text' => [
                'show' => function () {
                    return false; //  It may be re-enabled in the future
                }
            ],
            'bonus_code' => [
                'value' => $bonus_code,
                'disabled' => true,
                'placeholder' => 'register.bonus_code.nostar',
                'show' => function () use ($bonus_code) {
                    if (empty($bonus_code)) {
                        return false;
                    }
                    return true;
                },
            ],
            'birthdate' => [
                'placeholder' => 'register.birthdate.nostar',
                'disabled' => lic('shouldDisableInput', ['birthdate']),
                'fields' => [
                    'birthyear' => [
                        'value' => $force_birthyear ?? 1970,
                        'disabled' => lic('shouldDisableInput', ['birthyear']),
                        'placeholder' => 'year',
                        'options' => $fc->getYears($required_age)
                    ],
                    'birthmonth' => [
                        'value' => !empty($data['rstep2']['birthmonth']) ? $data['rstep2']['birthmonth'] : $data['birthmonth'],
                        'disabled' => lic('shouldDisableInput', ['birthmonth']),
                        'placeholder' => 'month',
                        'options' => $fc->getFullMonths()
                    ],
                    'birthdate' => [
                        'value' => !empty($data['rstep2']['birthdate']) ? $data['rstep2']['birthdate'] : $data['birthdate'],
                        'disabled' => lic('shouldDisableInput', ['birthdate']),
                        'placeholder' => 'day',
                        'options' => $fc->getDays()
                    ],
                ],
                'show' => function() use ($lic_fields) {
                    return in_array('birthdate', $lic_fields);
                }
            ],
            'currency' => [
                'value' => $currency,
                'disabled' => !empty($forced_currency) ? true : null,
                'show' => function () {
                    if (empty(phive("Currencer")->getSetting('multi_currency'))) {
                        return false;
                    }
                    return true;
                },
                'placeholder' => 'register.currency.nostar',
                'options' => $currencies,
                'show' => function() use ($lic_fields) {
                    return in_array('currency', $lic_fields);
                }
            ],
            'sex' => [
                'placeholder' => 'register.gender.nostar',
                'data' => [
                    'female' => 'Female',
                    'male' => 'Male'
                ],
                'disabled' => lic('shouldDisableInput', ['sex']),
                'fields' => [
                    'female' => [
                        'value' => $sex == 'Female',
                        'disabled' => lic('shouldDisableInput', ['sex']),
                        'placeholder' => 'register.female',
                    ],
                    'male' => [
                        'value' => $sex == 'Male',
                        'disabled' => lic('shouldDisableInput', ['sex']),
                        'placeholder' => 'register.male',
                    ],
                ],
                'show' => function() use ($lic_fields) {
                    return in_array('sex', $lic_fields);
                }
            ],
            'email_code' => [
                'value' => !empty($data['email_code']) ? $data['email_code'] : '',
                'disabled' => lic('shouldDisableInput', ['email_code']),
                'placeholder' => 'validation.code.nostar',
                'data' => [
                    'email' => $user->getAttribute('email'),
                    'mobile' => $user->getAttribute('mobile'),
                ],
                'show' => function () use ($user) {
                    return lic('verifyCommunicationChannel', null, $user) && !lic('oneStepRegistrationEnabled');
                }
            ],
            'eighteen' => [
                'checkbox' => true,
                'name' => 'check',
                'disabled' => lic('shouldDisableInput', ['eighteen']),
                'placeholder' => $required_age > 18 ? 'register.iamabove21' : 'register.iamabove18',
                'checked' => lic('shouldCheckCheckbox', ['eighteen']),
                'show' => function() use ($lic_fields) {
                    return in_array('eighteen', $lic_fields);
                }
            ],
            'fiscal_code' => [
                'value' => $data['rstep2']['fiscal_code'],
                'disabled' => lic('shouldDisableInput', ['fiscal_code']),
                'placeholder' => 'registration.fiscal_code.nostar',
                'show' => function() use ($lic_fields) {
                    return in_array('fiscal_code', $lic_fields);
                }
            ],
            'birth_country' => [
                'value' => $data['rstep2']['birth_country'] ?? self::prefillBirthCountryValue($user),
                'placeholder' => 'registration.birth_country.nostar',
                'disablelabel' => lic('shouldDisableLabel', ['birth_country']),
                'disabled' => lic('shouldDisableInput', ['birth_country']),
                'options' => lic('getBirthCountryList') ?? self::getBirthCountryList(),
                'validate' => function ($value, $form = []) {
                    $is_valid_value = !empty((self::getBirthCountryList() ?? [])[$value]);
                    if (empty($value) || !$is_valid_value) {
                        return 'register.birth_country.error.message';
                    }

                    return null;
                },
                'show' => function() use($user) {
                    return lic('showRegistrationExtraFields', ['step2', 'birth_country'], cuRegistration($user));
                }
            ],
            'birth_province' => [
                'value' => $data['rstep2']['birth_province'],
                'placeholder' => 'registration.birth_province.nostar',
                'disabled' => lic('shouldDisableInput', ['birth_province']),
                'options' => self::getAllProvinces()
            ],
            'birth_city' => [
                'value' => $data['rstep2']['birth_city'],
                'placeholder' => 'registration.birth_city.nostar',
                'disabled' => lic('shouldDisableInput', ['birth_city']),
                'options' => [],
                'show' => function() use ($lic_fields) {
                    return in_array('birth_city', $lic_fields);
                }
            ],
            'main_address' => [
                'value' => $data['rstep2']['main_address'],
                'placeholder' => 'registration.main_address.nostar',
                'disabled' => lic('shouldDisableInput', ['main_address']),
                'show' => function() use ($lic_fields) {
                    return in_array('main_address', $lic_fields);
                }
            ],
            'main_country' => [
                'value' => $data['rstep2']['main_country'],
                'placeholder' => 'registration.main_country.nostar',
                'disabled' => lic('shouldDisableInput', ['main_country']),
                'options' => self::getResidenceCountryList(),
                'show' => function() use ($lic_fields) {
                    return in_array('main_country', $lic_fields);
                }
            ],
            'main_city' => [
                'value' => $data['rstep2']['main_city'],
                'placeholder' => 'registration.main_city.nostar',
                'disabled' => lic('shouldDisableInput', ['main_city']),
                'options' => [],
                'show' => function() use ($lic_fields) {
                    return in_array('main_city', $lic_fields);
                }
            ],
            'main_province' => [
                'value' => $province,
                'placeholder' => 'registration.main_province.nostar',
                'disabled' => !empty($forced_province) ? true : null,
                'options' => self::getProvinces(),
                'show' => function() use ($lic_fields) {
                    return in_array('main_province', $lic_fields);
                }
            ],
            'cap' => [
                'value' => $data['rstep2']['cap'],
                'placeholder' => 'registration.cap.nostar',
                'disabled' => lic('shouldDisableInput', ['cap']),
                'show' => function() use ($lic_fields) {
                    return in_array('cap', $lic_fields);
                }
            ],
            'doc_type' => [
                'value' => $data['rstep2']['doc_type'],
                'placeholder' => 'registration.doc_type.nostar',
                'disabled' => lic('shouldDisableInput', ['doc_type']),
                'options' => lic('getDocumentTypeList') ?: [],
                'input_placeholder' => lic('getRegistrationFieldsExtra', ['doc_type', 'input_placeholder']),
                'show' => function() use ($lic_fields) {
                    return in_array('doc_type', $lic_fields);
                }
            ],
            'doc_number' => [
                'value' => $data['rstep2']['doc_number'],
                'placeholder' => 'registration.doc_number.nostar',
                'disabled' => lic('shouldDisableInput', ['doc_number']),
                'input_placeholder' => lic('getRegistrationFieldsExtra', ['doc_number', 'input_placeholder']),
                'show' => function() use ($lic_fields) {
                    return in_array('doc_number', $lic_fields);
                }
            ],
            'doc_issue_date' => [
                'placeholder' => 'registration.doc_issue_date.nostar',
                'fields' => [
                    'doc_year' => [
                        'value' => !empty($data['rstep2']['doc_year']) ? $data['rstep2']['doc_year'] : $data['doc_year'],
                        'disabled' => lic('shouldDisableInput', ['doc_year']),
                        'placeholder' => 'year',
                        'options' => $fc->getYears(0, true)
                    ],
                    'doc_month' => [
                        'value' => !empty($data['rstep2']['doc_month']) ? $data['rstep2']['doc_month'] : $data['doc_month'],
                        'disabled' => lic('shouldDisableInput', ['doc_month']),
                        'placeholder' => 'month',
                        'options' => $fc->getFullMonths()
                    ],
                    'doc_date' => [
                        'value' => !empty($data['rstep2']['doc_date']) ? $data['rstep2']['doc_date'] : $data['doc_date'],
                        'disabled' => lic('shouldDisableInput', ['doc_date']),
                        'placeholder' => 'day',
                        'options' => $fc->getDays()
                    ],
                ],
                'show' => function() use ($lic_fields) {
                    return in_array('doc_issue_date', $lic_fields);
                }
            ],
            'doc_issued_by' => [
                'value' => $data['rstep2']['doc_issued_by'],
                'placeholder' => 'registration.doc_issued_by.nostar',
                'disabled' => lic('shouldDisableInput', ['doc_issued_by']),
                'options' => lic('getDocumentIssuedByList'),
                'show' => function() use ($lic_fields) {
                    return in_array('doc_issued_by', $lic_fields);
                }
            ],
            'doc_place' => [
                'value' => $data['rstep2']['doc_place'],
                'placeholder' => 'registration.doc_place.nostar',
                'disabled' => lic('shouldDisableInput', ['doc_place']),
                'show' => function() use ($lic_fields) {
                    return in_array('doc_place', $lic_fields);
                }
            ],
            'fiscal_region' => [
                'value' => $data['rstep2']['fiscal_region'],
                'placeholder' => 'registration.fiscal_region.nostar',
                'disabled' => lic('shouldDisableInput', ['fiscal_region']),
                'options' => lic('getFiscalRegions') ?? [],
                'validate' => function ($value, $form = []) {
                    $is_valid_value = !empty((lic('getFiscalRegions') ?? [])[$value]);
                    if (empty($value) || !$is_valid_value) {
                        return 'register.fiscal_region.error.message';
                    }

                    return null;
                },
                'show' => function() use ($lic_fields) {
                    return in_array('fiscal_region', $lic_fields);
                }
            ],
            'nationality' => [
                'value' => $data['rstep2']['nationality'] ?? self::prefillNationalityValue(),
                'placeholder' => 'registration.nationality.nostar',
                'disabled' => lic('shouldDisableInput', ['nationality']),
                'options' => self::getNationalities() ?? [],
                'validate' => function ($value, $form = []) {
                    $is_valid_value = !empty((self::getNationalities() ?? [])[$value]);
                    if (empty($value) || !$is_valid_value) {
                        return 'register.nationality.error.message';
                    }

                    return null;
                },
                'show' => function() use ($lic_fields) {
                    return in_array('nationality', $lic_fields);
                }
            ],
            'residence_country' => [
                'value' => $data['rstep2']['residence_country'] ?? lic('getIso'),
                'placeholder' => 'registration.residence_country.nostar',
                'disabled' => true,
                'options' => lic('getResidenceCountryList') ?? [],
                'validate' => function ($value, $form = []) {
                    $is_valid_value = !empty((lic('getResidenceCountryList') ?? [])[$value]);
                    if (empty($value) || !$is_valid_value) {
                        return 'register.residence_country.error.message';
                    }

                    return null;
                },
                'show' => function() use ($lic_fields) {
                    return in_array('residence_country', $lic_fields);
                }
            ],
            'personal_number' => [
                'value' => $data['rstep2']['personal_number'],
                'disabled' => lic('shouldDisableInput', ['personal_number']),
                'placeholder' => 'register.personal_number.nostar',
                'error_message' => lic('personalNumberMessage', [false]) ?: 'register.personal_number.error.message',
                'show' => function () use ($user) {
                    $fields = lic('registrationStep2Fields', [], $user) ?? [];
                    foreach ($fields as $list) {
                        if (in_array('personal_number', $list)) {
                            return true;
                        }
                    }
                    return false;
                },
                'validate' => function ($value, array $form = []) use ($user) {
                    if (empty($value)){
                        return lic('personalNumberEmptyMessage',[false]) ?: 'register.err.invalid.personal.number';
                    }
                    if (phive('UserHandler')->doubleNid($value, $user->getCountry())) {
                        return lic('personalNumberTakenMessage', [false]) ?: 'register.err.invalid.personal.number';
                    }

                    return lic('validatePersonalNumber', [$value]);
                },
                'show' => function() use ($user) {
                    $fields = phive()->flatten(array_values(lic('registrationStep2Fields')));
                    return in_array('personal_number', $fields);
                }
            ],
            'firstname_initials' => [
                'value' => $data['rstep2']['firstname_initials'],
                'disabled' => lic('shouldDisableInput', ['firstname_initials']),
                'placeholder' => 'register.firstname_initials',
                'show' => function() use ($lic_fields) {
                    return in_array('firstname_initials', $lic_fields);
                }
            ],
            'citizen_service_number' => [
                'value' => $data['rstep2']['citizen_service_number'],
                'disabled' => lic('shouldDisableInput', ['citizen_service_number']),
                'placeholder' => 'register.citizen_service_number',
                'show' => function() use ($lic_fields) {
                    return in_array('citizen_service_number', $lic_fields);
                }
            ],
            'birth_place' => [
                'value' => $data['rstep2']['birth_place'],
                'disabled' => lic('shouldDisableInput', ['birth_place']),
                'placeholder' => 'register.birth_place',
                'show' => function() use ($lic_fields) {
                    return in_array('birth_place', $lic_fields);
                }
            ],
            'iban' => [
                'value' => $data['rstep2']['iban'],
                'disabled' => lic('shouldDisableInput', ['iban']),
                'placeholder' => 'register.iban',
                'description_top' => 'register.iban.description-top',
                'validate' => function ($value, array $form = []) use ($user) {
                    if (empty($form['iban'])) {
                        return 'errors.user.not_valid_iban';
                    }

                    $result = lic('checkIBAN', [$user, [
                        'user_id' => $user->getId(),
                        'full_name' => ucfirst($form['firstname']) . ' ' . ucfirst($form['lastname']),
                        'iban' => $form['iban']
                    ]], $user);

                    if (isset($result['success']) && ($result['success'] === false || $result['result'] === false)) {
                        return 'errors.user.not_valid_iban';
                    }

                    return null;
                },
                'show' => function() use ($lic_fields) {
                    return in_array('iban', $lic_fields);
                }
            ],
            'honest_player' => [
                'checkbox' => true,
                'name' => 'honest_player',
                'disabled' => lic('shouldDisableInput', ['honest_player']),
                'placeholder' => 'register.honest_player',
                'validate' => function ($value) {
                    if (empty($value)) {
                        return 'cashier.error.required';
                    }
                    return null;
                },
                'show' => function() use ($lic_fields) {
                    return in_array('honest_player', $lic_fields);
                }
            ],
            'aml' => [
                'checkbox' => true,
                'disabled' => lic('shouldDisableInput', ['aml']),
                'name' => 'aml_check',
                'placeholder' => 'register.aml.nostar',
            ],
            'occupation' => [
                'name' => lic('shouldGetInputName', ['occupation']),
                'value' => $data['rstep2']['occupation'],
                'disabled' => lic('shouldDisableInput', ['occupation']),
                'placeholder' => 'register.occupation.nostar'
            ],

            'industry' => [
                'name' => lic('shouldGetInputName', ['industry']),
                'value' => $data['rstep2']['industry'],
                'disabled' => lic('shouldDisableInput', ['industry']),
                'placeholder' => 'register.industry.nostar',
                'options' => self::getIndustries()
            ],
            'legal_age' => [
                'checkbox' => true,
                'name' => 'legal_age_check',
                'disabled' => lic('shouldDisableInput', ['legal_age']),
                'placeholder' => 'legal.age.nostar',
            ],
            'pep_check' => [
                'checkbox' => true,
                'name' => 'pep_check',
                'disabled' => lic('shouldDisableInput', ['pep_check']),
                'placeholder' => 'pep.check.nostar',
            ],
        ];

        if (phive()->isMobile()) {
            $birthdate = $fields['birthdate']['fields'];
            $fields['birthdate']['fields'] = [
                'birthdate' => $birthdate['birthdate'],
                'birthmonth' => $birthdate['birthmonth'],
                'birthyear' => $birthdate['birthyear'],
            ];
        }

        $exceptions = ['bonus_code'];
        // filter out the fields which are not required
        foreach ($fields as $field => $config) {
            $visible = $config['show'];
            if (empty($visible)) {
                continue;
            }
            $visible = $visible();
            if (in_array($field, $exceptions)) {
                $fields[$field]['visible'] = $visible;
                continue;
            }
            if (!$visible) {
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
     * @param bool $translate
     * @param $user
     * @return array
     * @throws Exception
     */
    public static function getStep2Fields($translate = true, $user = null)
    {
        $data =  [
            'rstep2' => $_SESSION['rstep2'],
            'rstep1' => $_SESSION['rstep1'],
            'sex' => $_POST['sex'],
            'preferred_lang' => $_POST['preferred_lang'],
            'birthyear' => $_POST['birthyear'],
            'birthmonth' => $_POST['birthmonth'],
            'birthdate' => $_POST['birthdate'],
            'email_code' => $_GET['email_code'],
            'doc_year' => $_POST['doc_year'],
            'doc_month' => $_POST['doc_month'],
            'doc_date' => $_POST['doc_date'],
        ];
        return self::getStep2FieldsV2($translate, $user, $data);
    }

    /**
     * Used to return required fields for $country to the FE
     * Applies common logic like translation
     * Removes all closures
     *
     * REFACTOR of getStep2Fields removing GLOBALS variable dependency
     *
     * @param bool $translate
     * @param $user
     * @param array $data data refactor from global variables
     * @return array
     * @throws Exception
     */
    public static function getStep2FieldsV2($translate = true, $user = null, $data = [])
    {
        $result = [];

        foreach (self::step2Fields($user, $data) as $field => $config) {
            if ($translate) {
                if (!empty($config['placeholder'])) {
                    $config['placeholder'] = t($config['placeholder']);
                }
                if (!empty($config['input_placeholder'])) {
                    $config['input_placeholder'] = t($config['input_placeholder']);
                }
                if (!empty($config['description_top'])) {
                    $config['description_top'] = t($config['description_top']);
                }
                if (!empty($config['fields'])) {
                    foreach ($config['fields'] as $f => $c) {
                        $config['fields'][$f]['placeholder'] = t($config['fields'][$f]['placeholder']);
                    }
                }
            }

            unset($config['show']);
            $result[$field] = $config;
        }

        trackRegistration(null, "getStep2Fields_sessionDataAfterProcessing");
        return $result;
    }

    /**
     * This will apply validation as defined in self::step2Fields
     * only on fields which are not validated already by other logic
     * The purpose is to move all field validations in self::step2Fields
     *
     * @param array|null $already_validated List of already validated fields
     * @param array $fields
     * @param array $form
     * @return array
     * @throws
     */
    public static function applyFieldValidation(array $already_validated, array $fields, array $form): array
    {
        $errors = [];

        $fields_not_validated = array_diff($fields, $already_validated);
        $fields_to_validate = [];
        $source = self::getStep2Fields(true, $form['user_id']);

        foreach ($fields_not_validated as $field) {
            $field_config = $source[$field];

            if (empty($field_config) || empty($field_config['validate'])) {
                continue;
            }
            $fields_to_validate[$field] = $field_config;
        }

        // validate input fields
        foreach ($fields_to_validate as $field => $config) {
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


        if(!isPNP()){
            $validation_errors = lic('extraValidationForStep2', [$form, $errors]);
        }

        if (!empty($validation_errors)) {
            $errors = array_merge($errors, $validation_errors);
        }

        return $errors;
    }

    /**
     * Validate all $form data received from FE
     *
     * @param bool $translate
     * @param array $extra array containing data used for check (refactor from globals use)
     * @return array
     *
     * @deprecated
     */
    public static function validateStep2Fields($translate = true, $extra = [])
    {
        /** @var UserHandler $UserHandler */
        $UserHandler = phive('DBUserHandler');

        $user = cuRegistration();

        if (empty($user)) {
            return ['general_error' => 'no user'];
        }

        $fields = $UserHandler->getReqFields();
        $lic_fields = lic('adjustField', [$fields]);
        $fields =  $lic_fields ? $lic_fields : $fields;

        // fields from step 1 are already saved, no need to validate them again
        foreach (array_keys(self::step1Fields($user->getData('country'), true)) as $field) {
            unset($fields[$field]);
        }

        $errors = $UserHandler->validateStep2($fields, !in_array('age_check', self::getStep1Checkboxes()), $user);

        // validate fields which are present on step2 but are not validated by previous logic
        $fields_errors = self::applyFieldValidation(
            array_keys($fields),
            phive()->flatten(array_values(lic('registrationStep2Fields'))),
            $_POST + ['user_id' => $user->getId()]
        );

        if (!empty($fields_errors)) {
            $errors = array_merge($errors, $fields_errors);
        }

        $UserHandler->checkDob($errors, $user);

        $finished_step2 = $UserHandler->hasFinishedRegistrationStep2($user);
        if (
            empty($errors) &&
            !$finished_step2 &&
            lic('verifyCommunicationChannel', null, $user) &&
            !lic('oneStepRegistrationEnabled', null, $user) &&
            !isBankIdMode()
        ) {
            if ($extra['email_code'] == $user->getSetting('email_code') && !empty($user->getSetting('email_code'))) {
                $user->setSetting('email_code_verified', 'yes');
                //$user->setAttribute('verified_email', '1');
                unset($_SESSION['email_code_shown']);
                // Remove AML flag if it has been set
                phiveApp(EventPublisherInterface::class)->fire('authentication', 'AuthenticationRemoveEmailAndPhoneCheckFlagEvent', [$user->getId()], 0);
            } elseif ($extra['email_code'] == $user->getSetting('sms_code') && !empty($user->getSetting('sms_code'))) {
                // HenrikSMS: validate SMS code here, seems like we don't need to send an SMS here, just show a message on the site with mosms.verify.success
                $user->setSetting('sms_code_verified', 'yes');
                $user->setSetting('email_code_verified', 'yes');
                $user->setAttr('verified_phone', 1);
                unset($_SESSION['sms_code_shown']);
                // Remove AML flag if it has been set
                phiveApp(EventPublisherInterface::class)->fire('authentication', 'AuthenticationRemoveEmailAndPhoneCheckFlagEvent', [$user->getId()], 0);

            } else {
                $errors = ['email_code' => 'wrong.email.code'];
                // ARF check specific for verification code
                phiveApp(EventPublisherInterface::class)->fire('authentication', 'AuthenticationEmailAndPhoneCheckEvent', [$user->getId()], 0);
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
     * @param array $request
     * @param \DBUser $user
     * @param bool $translate
     * @return array
     */
    public static function validateStep2FieldsV2(array $request, DBUser $user, $translate = true)
    {
        /** @var UserHandler $UserHandler */
        $UserHandler = phive('DBUserHandler');

        if (empty($user)) {
            return ['general_error' => 'no user'];
        }

        $fields = self::getRequiredRegisterStep2Fields($user->getCountry());
        $provinces = self::getProvinces();
        $requiredStep2Fields = phive()->flatten(array_values(lic('registrationStep2Fields')));

        $errors = $UserHandler->validateStep2(
            $fields,
            !in_array('age_check', self::getStep1Checkboxes()),
            $user,
            $request,
            $requiredStep2Fields,
            $provinces,
        );

        // validate fields which are present on step2 but are not validated by previous logic
        $fields_errors = self::applyFieldValidation(
            array_keys($fields),
            $requiredStep2Fields,
            $request + ['user_id' => $user->getId()],
        );

        if (!empty($fields_errors)) {
            $errors = array_merge($errors, $fields_errors);
        }

        $finished_step2 = $UserHandler->hasFinishedRegistrationStep2($user);
        if (
            empty($errors) &&
            !$finished_step2 &&
            lic('verifyCommunicationChannel', null, $user) &&
            !isPNP() &&
            !isBankIdMode() &&
            !lic('oneStepRegistrationEnabled', null, $user)
        ) {
            if ($request['email_code'] == $user->getSetting('email_code') && !empty($user->getSetting('email_code'))) {
                $user->setSetting('email_code_verified', 'yes');
                //$user->setAttribute('verified_email', '1');
                unset($_SESSION['email_code_shown']);
                // Remove AML flag if it has been set
                phiveApp(EventPublisherInterface::class)->fire('authentication', 'AuthenticationRemoveEmailAndPhoneCheckFlagEvent', [$user->getId()], 0);
            } elseif ($request['email_code'] == $user->getSetting('sms_code') && !empty($user->getSetting('sms_code'))) {
                // HenrikSMS: validate SMS code here, seems like we don't need to send an SMS here, just show a message on the site with mosms.verify.success
                $user->setSetting('sms_code_verified', 'yes');
                $user->setSetting('email_code_verified', 'yes');
                $user->setAttr('verified_phone', 1);
                unset($_SESSION['sms_code_shown']);
                // Remove AML flag if it has been set
                phiveApp(EventPublisherInterface::class)->fire('authentication', 'AuthenticationRemoveEmailAndPhoneCheckFlagEvent', [$user->getId()], 0);
            } else {
                $errors = ['email_code' => 'wrong.email.code'];
                // ARF check specific for verification code
                phiveApp(EventPublisherInterface::class)->fire('authentication', 'AuthenticationEmailAndPhoneCheckEvent', [$user->getId()], 0);

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
     * @param string $country
     *
     * @return array
     */
    public static function getRequiredRegisterStep2Fields(string $country): array
    {
        $requiredFields = self::getRegistrationFields();

        $step1FieldsKeys = self::getStep1FieldsKeys($country);
        foreach ($step1FieldsKeys as $key) {
            unset($requiredFields[$key]);
        }

        return $requiredFields;
    }

    /**
     * @return array
     */
    private static function getRegistrationFields(): array
    {
        $reqFields = phive('DBUserHandler')->getReqFields();
        $licFields = lic('adjustField', [$reqFields]);

        return $licFields ?: $reqFields;
    }

    /**
     * @param string $country
     *
     * @return array
     */
    private static function getStep1FieldsKeys(string $country): array
    {
        $countryService = CountryServiceFactory::create();
        $fields = self::getStep1FieldsFactory()->make($country, $countryService);

        $result = [];
        foreach ($fields as $field) {
            $result[] = $field->getName();
        }

        return $result;
    }

    /**
     * @return \Laraphive\Domain\User\Actions\Step1FieldsFactory
     */
    private static function getStep1FieldsFactory(): Step1FieldsFactory
    {
        return phiveApp(Step1FieldsFactory::class);
    }

    /**
     * @return array
     */
    public static function getResidenceCountriesList(): array
    {
        return self::getResidenceCountryList();
    }



}
