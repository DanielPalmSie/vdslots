<?php
require_once __DIR__ .'/Acuris.php';

class AcurisV2 extends Acuris {

    public function __construct()
    {
        parent::__construct();
        $this->kyc_settings = phive('Licensed')->getSetting('kyc_suppliers')['acurisv2'];
        $this->bank_countries = phive('Localizer')->getAllBankCountries('iso');
        $this->pep_threshold_block = phive('Config')->getValue('acurisV2', 'pep_threshold_block', 90);
        $this->pep_threshold_refer = phive('Config')->getValue('acurisV2', 'pep_threshold_refer', 70);
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
     * @return array
     */
    protected function getRequestPayload($user, $pep_request = true): array
    {
        $ud = $user->getData();
        $request = ['normal'];


        foreach ($request as $attempt) {
            $payload = $pep_request ? $this->getPEPdata($ud, $attempt,  $user->getFullName()) : $this->getMonitoringData($ud);
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
            "dobMatching" => 'exact',
            "datasets" => ['SAN'],
            "dob" => $ud['dob'],
            "name" =>  $ud['firstname']. ' ' .$ud['lastname'],
        ];

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
                "name" => $ud['firstname']. ' ' .$ud['lastname'],
                "countries" => [
                    $ud['country']
                ],
                "dob" => $ud['dob'],
                "gender" => strtolower($ud['sex'])
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
        $source = $this->kyc_settings['source_name'];
        $url = $this->getApiUrl('monitor_add') . $source . '/individuals/' . $user->getId();
        $content = $this->getRequestPayload($user, false);

        $res = $this->requestToAcuris($url, $content['normal'], 'monitor_add', 'PUT', $user, 'monitor');
        if (!in_array($res['1'] , ['200', '201'])){
            $user->setSetting('acuris_error_monitor', $res[0]);
        }else{
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
            $resource_id = $user->getSetting('acuris-resourceId');
            if (!empty($resource_id)) {
                $result = $this->searchViaQRCode($user, $resource_id);
                $setting_value = $this->mapPEPResultToSetting($result);
            } else {
                $result = $this->searchViaUserInfo($user);
                if ($result->results->matchCount == 0 || empty($result->results->matches)) {
                    $setting_value = self::PEP_RESULTS['NO MATCH'];
                } else {
                    // We get the user with the highest score and process his info
                    $scores = array_column($result->matches, 'score');

                    $key = array_keys($scores, max($scores))[0];
                    $setting_value = $this->mapPEPResultToSetting($result->results->matches[0]);

                    // Only for risky customer that have high matching criteria we store the Acuris unique identifier
                    if ($setting_value === self::PEP_RESULTS['ALERT']) {
                        $user->setSetting('acuris-resourceId', $result->results->matches[0]->resourceId);
                        $user->setSetting('acuris-qrcode', $result->results->matches[0]->qrCode);
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
            "x-api-key: $api_key",
        ];
        return $extra_headers;
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
            $result_decode = json_decode($result[0]);
            $res['request_' . $key] = $result;
            if ($result_decode->results->matchCount != 0) {
                break;
            }
        }
        $setting = count($res) > 1 ? json_encode($res) : json_encode($result);
        $user->setSetting('acuris_full_res', $setting);
        return $result_decode;
    }

    /**
     * @param $result
     * @return null|bool
     */
    protected function isPep($result): ?bool
    {
        if (! isset($result->datasets)) {
            return null;
        }

        foreach ($result->datasets as $value) {
            if ($value == 'PEP-CURRENT') {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $result
     * @return null|bool
     */
    protected function isSanction($result): ?bool
    {
        if (! isset($result->datasets)) {
            return null;
        }

        foreach ($result->datasets as $value) {
            if ($value == 'SAN-CURRENT') {
                return true;
            }
        }
        return false;
    }

    protected function getScore($score){
        return $score->score ?? 100;
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
        $next_token = null;
        $res_total = [];
        while (true) {
            $url_total = "$url$source/individuals?pageSize=$size";
            if ($next_token){
                $url_total .= '&nextToken=' . $next_token;
            }
            $res = $this->requestToAcuris($url_total, '', 'monitor_add', 'GET', null, 'monitor', '-alert');
            $result = json_decode($res[0], true);
            $res_total = array_merge($res_total, $result["monitorRecords"]);
            if (count($result["monitorRecords"]) < $size) {
                break;
            }
            if (!$result["nextToken"]){
                break;
            }
            $next_token = $result["nextToken"];
        }
        phive('Logger')->getLogger('acuris')->debug($res_total);
        return $res_total;
    }
}
