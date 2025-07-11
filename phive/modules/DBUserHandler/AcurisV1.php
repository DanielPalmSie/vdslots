<?php
require_once __DIR__ .'/Acuris.php';


class AcurisV1 extends Acuris {


    public function __construct()
    {
        parent::__construct();
        $this->kyc_settings = phive('Licensed')->getSetting('kyc_suppliers')['acurisv1'];
        $this->bank_countries = phive('Localizer')->getAllBankCountries('iso');
        $this->pep_threshold_block = phive('Config')->getValue('acuris', 'pep_threshold_block', 90);
        $this->pep_threshold_refer = phive('Config')->getValue('acuris', 'pep_threshold_refer', 70);
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
    protected function getRequestPayload($user, $pep_request = true): array
    {
        $ud = $user->getData();
        $request = ['normal'];
        $firstname = $middlename = '';
        if ($this->kyc_settings['split_name_extra_call'] && $pep_request) {
            list($firstname, $middlename) = $user->getSplitName();
            if (!empty($middlename)) {
                array_push($request, 'split');
            }
        }
        foreach ($request as $attempt) {
            $payload = $pep_request ? $this->getPEPdata($ud, $attempt, $firstname, $middlename) : $this->getMonitoringData($ud);
            $content[$attempt] = json_encode($payload);
        }
        return $content;
    }

    /**
     * Get the data for search API (PEP/SL)
     *
     * @param $ud
     * @return array
     */
    protected function getPEPdata($ud, $attempt, $firstname = '', $middlename = '') :array
    {
        $payload = [
            "Threshold" => $this->pep_threshold_refer,
            "PEP" => true,
            "PreviousSanctions" => true,
            "CurrentSanctions" => true,
            "LawEnforcement" => true,
            "FinancialRegulator" => true,
            "Insolvency" => true,
            "DisqualifiedDirector" => true,
            "AdverseMedia" => true,
            "DateOfBirth" => $ud['dob'],
            "Forename" => $attempt == 'normal' ? $ud['firstname'] : $firstname,
            "Middlename" => $attempt == 'normal' ? null : $middlename,
            "Surname" => $ud['lastname'],
            "YearOfBirth" => substr($ud['dob'], 0, 4),
            "Country" => isset($this->bank_countries[$ud['country']]) ? $this->bank_countries[$ud['country']]['printable_name'] : null
        ];
        if (in_array($ud['country'], $this->kyc_settings['check_address_countries'])) {
            $address = [
                "Address" => $ud['address'],
                "City" => $ud['city'],
                "County" => null,
                "Postcode" => $ud['zipcode'],
            ];
            $payload = array_merge($payload, $address);
        }
        // we override the default "pep_check_types" with the one form the config
        foreach ($this->pep_check_types as $check_type) {
            if (isset($this->kyc_settings['pep_check_types'][$check_type])) {
                $payload[$check_type] = $this->kyc_settings['pep_check_types'][$check_type];
            }
        }
        return $payload;
    }

    /**
     * Get data for monitoring API
     *
     * @param $ud
     * @return array
     */
    protected function getMonitoringData($ud) :array
    {
        return
            [
                "forename" => $ud['firstname'],
                "surname" => $ud['lastname'],
                "yob" => substr($ud['dob'], 0, 4),
                "country" => isset($this->bank_countries[$ud['country']]) ? $this->bank_countries[$ud['country']]['printable_name'] : null,
                'uniqueId' => $ud['id'],
                'sourceName' => $this->kyc_settings['source_name'],
                "dob" => $ud['dob'],
                "address1" => $ud['address'],
                'nationality' => isset($this->bank_countries[$ud['country']]) ? $this->bank_countries[$ud['country']]['printable_name'] : null
            ];
    }

    /**
     * Adding the user in Acuris monitoring
     *
     * @param $user
     * @param bool $edit
     */
    public function addToMonitoring($user, $edit = false)
    {
        $url = $this->getApiUrl('monitor_add');
        $content = $this->getRequestPayload($user, false);
        list($method, $url) = $edit != true ? ['POST', $url] : ['PUT', $url . "?uniqueId={$user->getId()}"];
        $res = $this->requestToAcuris($url, $content['normal'], 'monitor_add', $method, $user, 'monitor');
        if (json_decode($res) != $user->getId() && !$edit || $edit && !empty($this->failed_acuris_monitor_result)) {
            $error = !empty($this->failed_acuris_monitor_result) ? $this->failed_acuris_monitor_result : $res;
            $user->setSetting('acuris_error_monitor', $error);
        } else {
            $user->setSetting('acuris-monitor', 1);
        }
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
    public function checkPEPSanctions($user)
    {
        $user = cu($user);
        $is_pep = false;
        $is_sanctions_current = false;

        if (in_array($user->getCountry(), $this->config_settings['pep_excluded_countries'])) {
            return true;
        }

        if (phive()->isLocal() && !empty($this->kyc_settings['mock_result_pep'])) {
            $setting_value = $this->kyc_settings['mock_result_pep'];
        } else {
            $qr_code = $user->getSetting('acuris-qrcode');
            if (!empty($qr_code)) {
                $result = $this->searchViaQRCode($user, $qr_code);
                $setting_value = $this->mapPEPResultToSetting($result);
            } else {
                $result = $this->searchViaUserInfo($user);

                if ($result['recordsFound'] == 0 || empty($result['matches'])) {
                    $setting_value = self::PEP_RESULTS['NO MATCH'];
                } else {
                    // We get the user with the highest score and process his info
                    $scores = array_column($result['matches'], 'score');
                    $key = array_keys($scores, max($scores))[0];
                    $setting_value = $this->mapPEPResultToSetting($result['matches'][$key]);

                    // Only for risky customer that have high matching criteria we store the Acuris unique identifier
                    if ($setting_value === self::PEP_RESULTS['ALERT']) {
                        $user->setSetting('acuris-qrcode', $result['matches'][$key]['person']['id']);
                    }
                }
            }
            $is_pep = $this->isPep($result);
            $is_sanctions_current = $this->isSanction($result);
        }

        if (empty($setting_value)) {
            $setting_value = empty($this->failed_acuris_pep_result) ? "Acuris failed, unknown reason" : $this->failed_acuris_pep_result;
        }

        $user->refreshSetting('acuris_pep_res', $setting_value);

        if (in_array($setting_value, [self::PEP_RESULTS['PASS'], self::PEP_RESULTS['NO MATCH']])) {
            return true;
        } elseif ($setting_value == self::PEP_RESULTS['ALERT']) {
            $user->refreshSetting('pep_failure', (int) $is_pep);
            $user->refreshSetting('sanction_list_failure', (int) $is_sanctions_current);
        }

        return $setting_value;
    }

    /**
     * Return the header with the API key and Host
     *
     * @param $service "search|monitor"
     * @return array
     */
    protected function getApiHeaders($service) {
        $host = $this->kyc_settings['api']['host'];
        $api_key = $this->kyc_settings['api'][$service];

        $extra_headers = [
            "apiKey: $api_key",
            "Host: $host"
        ];
        return $extra_headers;
    }

    protected function getScore($score){
        return $score['score'] ?? 100;
    }

    /**
     * Getting the alerts from Acuris API
     *
     * @return array
     */
    public function getPepAlerts(): array
    {
        $url = $this->getApiUrl('monitor_alert');
        $size = $this->kyc_settings['api']['alert_request_limit'] ?? 10;
        $source = $this->kyc_settings['source_name'];
        $limit = 0;
        $res_total = [];
        while (true) {
            $url_total = "$url/$limit/$size?source=$source";
            $res = $this->requestToAcuris($url_total, '', 'monitor_add', 'GET', null, 'monitor', '-alert');
            $result = json_decode($res, true);
            $res_total = array_merge($res_total, $result["monitorRecords"]);
            if (count($result["monitorRecords"]) < $size) {
                break;
            }
            $limit += $size;
        }
        return $res_total;
    }

    protected function searchRequest($user, $service, $method = 'POST', $qr_code = null)
    {
        $url = $this->getApiUrl("search_via_$service");
        $content = $service === 'info' ? $this->getRequestPayload($user) : [''];
        if ($service === 'qr') {
            $url .= '/' . $qr_code;
        }
        foreach ($content as $key => $con) {
            $split_key = $key != 'normal' ? "-$key" : '';
            $result = $this->requestToAcuris($url, $con, $service, $method, $user, 'search', $split_key);
            $result_decode = json_decode($result, true);
            $res['request_' . $key] = $result;
            if ($result_decode['recordsFound'] != 0) {
                break;
            }
        }
        $setting = count($res) > 1 ? json_encode($res) : $result;
        $user->setSetting('acuris_full_res', $setting);
        return $result_decode;
    }


}
