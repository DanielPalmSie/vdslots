<?php

require_once('ExternalKyc.php');

/**
 * This class handle external PEP/SL checks with Acuris.
 * Currently implemented:
 * - "info": search with user information
 * - "qr": search via unique identifier
 * @TODO Monitoring API
 *
 * Class Acuris
 */
abstract class Acuris extends ExternalKyc
{
    /**
     * score to block a player
     * @var int
     */
    protected $pep_threshold_block;
    /**
     * score to set a setting to refer
     * @var int
     */
    protected $pep_threshold_refer;

    /**
     * List of all the possible check types
     * used to loop the config values to see if we are overriding the default settings
     *
     * @var array
     */
    protected $pep_check_types = [
        'PEP',
        'PreviousSanctions',
        'CurrentSanctions',
        'LawEnforcement',
        'FinancialRegulator',
        'Insolvency', // UK only
        'DisqualifiedDirector', // UK & Ireland only
        'AdverseMedia'
    ];

    /**
     * Acuris API require the country to be passed as a string, instead of the ISO.
     * So we get all the countries an use the "printable_name".
     *
     * TODO check if our own countries matches the one from Acuris, other we may need to amend our data or add an extra mapping. /Paolo
     *
     * @var array
     */
    protected $bank_countries = [];


    /**
     * Get API url
     *
     * @param $service
     * @return mixed
     */
    protected function getApiUrl($service)
    {
        return $this->kyc_settings['api'][$service . '_url'];
    }

    /**
     * Return the header with the API key and Host
     *
     * @param $service "search|monitor"
     * @return array
     */
    abstract protected function getApiHeaders($service);

    /**
     * Get API timeout
     *
     * @param $service
     * @return mixed
     */
    protected function getApiTimeout($service)
    {
        return $this->kyc_settings['api']['pep_search_'.$service] ?: self::DEFAULT_API_TIMEOUT;
    }

    /**
     * Prepare the data to be sent to Acuris API.
     *
     * Please note that we are setting a minimum threshold of 70 ("refer" value)
     * and requesting the check on ALL the available "pep_check_types".
     * All the above settings are editable via config (threshold) / setting (pep_check_types).
     *
     * If "split_name_extra_call" is true we may trigger a second call to Acuris API if the following conditions are met:
     * - firstname contains more than 1 word
     * - the first call to API returns 0 matches
     *
     * @param $user DBUser
     * @param $pep_request boolean, true: using the the PEP/SL API, false: adding to the monitor system
     * @return false|string
     */
    abstract protected function getRequestPayload($user, $pep_request = true) :array;

    /**
     * Get the data for search API (PEP/SL)
     *
     * @param $ud
     * @return array
     */
    abstract protected function getPEPdata($ud, $attempt, $firstname = '', $middlename = ''): array;

    /**
     * Get data for monitoring API
     *
     * @param $ud
     * @return array
     */
    abstract protected function getMonitoringData($ud): array;

    abstract protected function getScore($result);

    /**
     * We map the result from the API to our "standards" settings.
     * The following logic applies:
     * - 1) ("NOT PEP" && "NOT SL") OR Score < "refer threshold" --> PASS
     * - 2) (PEP OR SL) AND Score between "refer threshold" and "block threshold" --> REFER
     * - 3) (PEP OR SL) AND Score > "block threshold"  --> ALERT
     *
     * Extra - should never happen... most probably something wrong in the API call
     * - 4) if $is_pep and $is_sanctions_current are both "null" --> ERROR
     *
     * @param $result,the person who highest score
     * @return string
     */
    protected function mapPEPResultToSetting($result)
    {
        $score = $this->getScore($result);
        $is_pep = $this->isPep($result);
        $is_sanctions_current = $this->isSanction($result);
        if (is_null($is_pep) || is_null($is_sanctions_current)) {
            return self::PEP_RESULTS['ERROR']; // 4)
        }
        $setting_value = self::PEP_RESULTS['PASS'];
        if ($score < $this->pep_threshold_refer || !$this->isPepOrSanction($result)) {
            $setting_value = self::PEP_RESULTS['PASS']; // 1)
        } elseif($this->isPepOrSanction($result)) {
            $setting_value = $score < $this->pep_threshold_block ? self::PEP_RESULTS['REFER'] : self::PEP_RESULTS['ALERT']; // 2) : 3)
        }

        return $setting_value;
    }

    /**
     * Adding the user in Acuris monitoring
     *
     * @param $user
     * @param bool $edit
     */
     abstract protected function addToMonitoring($user, $edit = false);


    /**
     * Common wrapper for API call to Acuris
     *
     * @param string $url
     * @param string $content
     * @param string $service
     * @param string $method
     * @param DBUser $user
     * @param string $header
     * @param string $split_key
     * @param int $attempt
     * @return bool
     */
    protected function requestToAcuris($url, $content, $service, $method, $user, $header, $split_key = '', int $attempt = 1)
    {
        $start_time = microtime(true);
        $pep = $this->isPEPRequest($service);
        $tag = $pep == true ? "acuris_pep$split_key" : "acuris_monitor$split_key";
        try {
            $extra_headers = $this->getApiHeaders($header);
            $result = phive()->post(
                $url,
                $content,
                'application/json',
                $extra_headers,
                "acuris-$header$split_key",
                $method,
                $this->getApiTimeout($service),
                '',
                '',
                true
            );

            $status = $result[1];
            $is_server_error = $result === false || str_starts_with($status, '5');

            if ($is_server_error) {
                throw new Exception("Acuris API call failed. URL: $url, Content: $content");
            }

            $u_id = !empty($user) ? $user->getId() : 0;
            phive()->externalAuditTbl($tag, $content, $result, (microtime(true) - $start_time), 200, 0, 0, $u_id);
        } catch (Exception $e) {
            // To keep compatibility with error mapping
            $object = $pep == true ? 'failed_acuris_pep_result' : 'failed_acuris_monitor_result';
            $this->handleError($object , $e->getMessage());
            phive()->externalAuditTbl($tag, $content, "Acuris auth failed. Reason: {$e->getMessage()}", (microtime(true) - $start_time), 500, 0, 0, $u_id);

            $max_attempts_count = $this->kyc_settings['max_attempts_count'] ?? 2;
            if ($attempt < $max_attempts_count) {
                $wait_time = 5;
                sleep($wait_time);

                return $this->requestToAcuris($url, $content, $service, $method, $user, $header, $split_key, $attempt + 1);
            }

            return false;
        }
        return $result;
    }

    /**
     * To know if the request use the Search API
     *
     * @param $service
     * @return bool
     */
    protected function isPEPRequest($service): bool
    {
        return $service != 'monitor_add';
    }

    /**
     * Wrap the error according is in PEP or monitoring API
     *
     * @param $object
     * @param $message
     */
    protected function handleError($object, $message){
        $this->$object = $message;
    }

    /**
     * Simple wrapper to know if the user isPepOrSanction
     *
     * @param $result
     * @return bool
     */
    protected function isPepOrSanction($result)
    {
        return $this->isPep($result) || $this->isSanction($result);
    }

    /**
     * Simple wrapper around the result to handle "isPEP" on both search scenarios:
     * - "info" -> $result['person']['isPEP']
     * - "qr" -> $result['isPEP']
     *
     * @param $result
     * @return bool|null
     */
    protected function isPep($result): ?bool
    {
        if (isset($result['person']['isPEP'])) {
            return $result['person']['isPEP'];
        }

        if (isset($result['isPEP'])) {
            return $result['isPEP'];
        }

        return null;
    }

    /**
     * Simple wrapper around the result to handle "isSanction" on both search scenarios:
     * - "info" -> $result['person']['isSanctionsCurrent']
     * - "qr" -> $result['isSanctionsCurrent']
     *
     * @param $result
     * @return bool|null
     */
    protected function isSanction($result): ?bool
    {
        if (isset($result['person']['isSanctionsCurrent'])) {
            return $result['person']['isSanctionsCurrent'];
        }

        if (isset($result['isSanctionsCurrent'])) {
            return $result['isSanctionsCurrent'];
        }

        return null;
    }

    /**
     * PEP and Sanction Lists check with Acuris, we have 2 types of calls available:
     * - "info":
     *   - apply to new customers
     *   - return a list of all the possible matches above a certain score
     *   - we process only the user with the highest score
     * - "qr":
     *   - apply to existing customers that have "acuris-qrcode" ("qr" is Acuris unique person id)
     *   - "acuris-qrcode" is stored only for customer that were flagged with "ALERT" (high score and PEP or SL)
     *   - return the single customer
     *
     * To know the possible values that we store on the "users_settings" see mapPEPResultToSetting + "NO MATCH"
     * (NO MATCH is a special scenario if the API doesn't return any match)
     *
     * @param int|DBUser $user
     * @return bool
     */
    abstract protected function checkPEPSanctions($user);

    /**
     * Getting the alerts from Acuris API
     *
     * @return array
     */
    abstract protected function getPepAlerts(): array;

    /**
     * Creation for each user, the link to acuris and the link to vs BO
     *
     * @param $user
     * @param $pep
     * @return string
     */
    function getPEPAlertsEmailBody($user, $pep)
    {
        $acuris_version = phive('Licensed')->getSetting('kyc_suppliers')['config']['pep_order'];
        $acuris_id = array_column($pep['matches'], 'matchId');
        $link_to_acuris = '';
        foreach ($acuris_id as $id) {
            $link = in_array('AcurisV2', $acuris_version) ?
                $this->kyc_settings['api']['bo'].$user->getSetting('acuris-qrcode')
                : $this->kyc_settings['api']['bo']."$id/true";
            $link_to_acuris .= chr(10) . " Please , check the acuris Link  <a href='$link'></a> ";
        }
        return "New alert on the user with id <a href='{$user->accUrl('', true)}'>{$user->getId()}</a> $link_to_acuris";
    }


    /**
     * Wrapper for searchRequest with user info
     *
     * @param $user
     * @return bool|mixed
     */
    protected function searchViaUserInfo($user)
    {
        return $this->searchRequest($user, 'info', 'POST');
    }

    /**
     * Wrapper for searchRequest with QR code (acuris unique identifier)
     *
     * @param $user
     * @param $qr_code
     * @return bool|mixed
     */
    protected function searchViaQRCode($user, $qr_code)
    {
        return $this->searchRequest($user, 'qr', 'GET', $qr_code);
    }

    /**
     * Common wrapper for Search/QR request
     *
     * @param $user
     * @param $service
     * @param string $method
     * @param null $qr_code
     * @return bool|mixed
     */
    abstract protected function searchRequest($user, $service, $method = 'POST', $qr_code = null);
}

/*
$url = "https://api1.uat.c6-intelligence.com/api/v2_1/api/countries"; // countries
 [
  "name" => "Afghanistan",
  "name" => "Albania",
  "name" => "Algeria",
  "name" => "American Samoa",
  "name" => "Andorra",
  "name" => "Angola",
  "name" => "Anguilla",
  "name" => "Antigua and Barbuda",
  "name" => "Argentina",
  "name" => "Armenia",
  "name" => "Aruba",
  "name" => "Australia",
  "name" => "Austria",
  "name" => "Azerbaijan",
  "name" => "Bahamas, The",
  "name" => "Bahrain",
  "name" => "Bangladesh",
  "name" => "Barbados",
  "name" => "Belarus",
  "name" => "Belgium",
  "name" => "Belize",
  "name" => "Benin",
  "name" => "Bermuda",
  "name" => "Bhutan",
  "name" => "Bolivia",
  "name" => "Bosnia and Herzegovina",
  "name" => "Botswana",
  "name" => "Brazil",
  "name" => "British Virgin Islands",
  "name" => "Brunei",
  "name" => "Bulgaria",
  "name" => "Burkina Faso",
  "name" => "Burma",
  "name" => "Burundi",
  "name" => "Cambodia",
  "name" => "Cameroon",
  "name" => "Canada",
  "name" => "Cape Verde",
  "name" => "Cayman Islands, The",
  "name" => "Central African Republic",
  "name" => "Chad",
  "name" => "Chile",
  "name" => "China",
  "name" => "Colombia",
  "name" => "Comoros",
  "name" => "Congo, Democratic Republic of the",
  "name" => "Congo, Republic of the",
  "name" => "Cook Islands",
  "name" => "Corsica",
  "name" => "Costa Rica",
  "name" => "Côte d'Ivoire",
  "name" => "Croatia",
  "name" => "Cuba",
  "name" => "Cyprus",
  "name" => "Czech Republic",
  "name" => "Denmark",
  "name" => "Djibouti",
  "name" => "Dominica",
  "name" => "Dominican Republic",
  "name" => "East Timor",
  "name" => "Ecuador",
  "name" => "Egypt",
  "name" => "El Salvador",
  "name" => "Equatorial Guinea",
  "name" => "Eritrea",
  "name" => "Estonia",
  "name" => "Ethiopia",
  "name" => "Faroe Islands",
  "name" => "Fiji",
  "name" => "Finland",
  "name" => "France",
  "name" => "French Guiana",
  "name" => "French Polynesia",
  "name" => "Gabon",
  "name" => "Gambia, The",
  "name" => "Gaza Strip",
  "name" => "Georgia",
  "name" => "Germany",
  "name" => "Ghana",
  "name" => "Gibraltar",
  "name" => "Greece",
  "name" => "Greenland",
  "name" => "Grenada",
  "name" => "Guadeloupe",
  "name" => "Guam",
  "name" => "Guatemala",
  "name" => "Guernsey",
  "name" => "Guinea",
  "name" => "Guinea-Bissau",
  "name" => "Guyana",
  "name" => "Haiti",
  "name" => "Holy See",
  "name" => "Honduras",
  "name" => "Hong Kong",
  "name" => "Hungary",
  "name" => "Iceland",
  "name" => "India",
  "name" => "Indonesia",
  "name" => "Iran",
  "name" => "Iraq",
  "name" => "Ireland",
  "name" => "Isle of Man",
  "name" => "Israel",
  "name" => "Italy",
  "name" => "Jamaica",
  "name" => "Japan",
  "name" => "Jersey",
  "name" => "Jordan",
  "name" => "Kazakhstan",
  "name" => "Kenya",
  "name" => "Kiribati",
  "name" => "Korea, North",
  "name" => "Korea, South",
  "name" => "Kosovo",
  "name" => "Kuwait",
  "name" => "Kyrgyzstan",
  "name" => "Laos",
  "name" => "Latvia",
  "name" => "Lebanon",
  "name" => "Lesotho",
  "name" => "Liberia",
  "name" => "Libya",
  "name" => "Liechtenstein",
  "name" => "Lithuania",
  "name" => "Luxembourg",
  "name" => "Macau",
  "name" => "Macedonia, The Former Yugoslav Republic of",
  "name" => "Madagascar",
  "name" => "Malawi",
  "name" => "Malaysia",
  "name" => "Maldives",
  "name" => "Mali",
  "name" => "Malta",
  "name" => "Marshall Islands",
  "name" => "Martinique",
  "name" => "Mauritania",
  "name" => "Mauritius",
  "name" => "Mayotte",
  "name" => "Mexico",
  "name" => "Micronesia, Federated States of",
  "name" => "Moldova",
  "name" => "Monaco",
  "name" => "Mongolia",
  "name" => "Montenegro",
  "name" => "Montserrat",
  "name" => "Morocco",
  "name" => "Mozambique",
  "name" => "Myanmar",
  "name" => "Namibia",
  "name" => "Nauru",
  "name" => "Nepal",
  "name" => "Netherlands",
  "name" => "Netherlands Antilles",
  "name" => "New Caledonia",
  "name" => "New Zealand",
  "name" => "Nicaragua",
  "name" => "Niger",
  "name" => "Nigeria",
  "name" => "Niue",
  "name" => "None",
  "name" => "Norfolk Island",
  "name" => "Northern Mariana Islands",
  "name" => "Norway",
  "name" => "Oman",
  "name" => "Pakistan",
  "name" => "Palau",
  "name" => "Palestine",
  "name" => "Panama",
  "name" => "Papua New Guinea",
  "name" => "Paraguay",
  "name" => "Peru",
  "name" => "Philippines",
  "name" => "Poland",
  "name" => "Portugal",
  "name" => "Puerto Rico",
  "name" => "Qatar",
  "name" => "Reunion",
  "name" => "Romania",
  "name" => "Russia",
  "name" => "Rwanda",
  "name" => "Saint Kitts and Nevis",
  "name" => "Saint Lucia",
  "name" => "Saint Pierre and Miquelon",
  "name" => "Saint Vincent and the Grenadines",
  "name" => "Samoa",
  "name" => "San Marino",
  "name" => "Sao Tome and Principe",
  "name" => "Saudi Arabia",
  "name" => "Senegal",
  "name" => "Serbia",
  "name" => "Seychelles",
  "name" => "Sierra Leone",
  "name" => "Singapore",
  "name" => "Slovakia",
  "name" => "Slovenia",
  "name" => "Solomon Islands",
  "name" => "Somalia",
  "name" => "South Africa",
  "name" => "South Sudan",
  "name" => "Spain",
  "name" => "Sri Lanka",
  "name" => "St. Maarten",
  "name" => "Sudan",
  "name" => "Suriname",
  "name" => "Swaziland",
  "name" => "Sweden",
  "name" => "Switzerland",
  "name" => "Syria",
  "name" => "Taiwan",
  "name" => "Tajikistan",
  "name" => "Tanzania",
  "name" => "Thailand",
  "name" => "Togo",
  "name" => "Tonga",
  "name" => "Trinidad and Tobago",
  "name" => "Tunisia",
  "name" => "Turkey",
  "name" => "Turkmenistan",
  "name" => "Turks and Caicos Islands",
  "name" => "Tuvalu",
  "name" => "Uganda",
  "name" => "Ukraine",
  "name" => "United Arab Emirates",
  "name" => "United Kingdom",
  "name" => "United States of America",
  "name" => "United States Virgin Islands",
  "name" => "Uruguay",
  "name" => "Uzbekistan",
  "name" => "Vanuatu",
  "name" => "Vatican City",
  "name" => "Venezuela",
  "name" => "Vietnam",
  "name" => "Wallis and Futuna",
  "name" => "West Bank",
  "name" => "Western Sahara",
  "name" => "Yemen",
  "name" => "Yugoslavia",
  "name" => "Zambia",
  "name" => "Zimbabwe"
]*/
