<?php

require_once('ExternalKyc.php');

class ID3 extends ExternalKyc
{
    /* Identity check provider */
    public const GBG_PROVIDER = 'GBG';

    /**
    * ID3 API require the country to be passed as a string, instead of the ISO.
    * So we get all the countries an use the "printable_name".
    *
    * TODO check if our own countries matches the one from ID3, other we may need to amend our data or add an extra mapping. /Paolo
    *
    * @var array
    */
    private $bank_countries = [];

    /**
     * @var mixed
     */
    public $failed_id3_dob_result;
    /**
     * @var true
     */
    private bool $allow_delegate = false;

    public function __construct()
    {
        parent::__construct();
        $this->kyc_settings = phive('Licensed')->getSetting('kyc_suppliers')['id3'];
        $this->bank_countries = phive('Localizer')->getAllBankCountries('iso');
    }

    /**
     * We are currently using the first word from "BandText"
     * to map the results into a common format for the user_settings
     *
     * @param $result
     * @return string
     */
    public function mapDobResultToSetting($result)
    {
        $map = [
            'REFER' => self::DOB_RESULTS['REFER'],
            'PASS' => self::DOB_RESULTS['PASS'],
            'ALERT' => self::DOB_RESULTS['ALERT'],
            'NO MATCH' => self::DOB_RESULTS['NO MATCH'],
            'NO' => self::DOB_RESULTS['NO MATCH'],
            'PASS DUAL' => self::DOB_RESULTS['PASS DUAL'],
            'PASS CREDIT' => self::DOB_RESULTS['PASS CREDIT'],
        ];
        $setting_value = $map[$result] ?? self::DOB_RESULTS['ERROR'];
        if ($setting_value === self::DOB_RESULTS['ERROR']) {
            phive()->dumpTbl('id3global_auth_error', "A non mapped result '$result' is being returned");
        }
        return $setting_value;
    }

    /**
     * Age checks for GBG ID3 Global // Old function name: ID3GlobalDob
     *
     * @param DBUser $user
     * @return array|string
     */
    public function checkDob($user)
    {
        $ud = $user->getData();

        $res = $this->requestID3Global($this->kyc_settings['dob_id'][$user->getCountry()], $this->kyc_settings['dob_reference'], $ud);
        // Needed on Admin2
        $user->setSetting('id3global_full_res', json_encode((array)$res));

        if (phive()->isLocal() && !empty($this->kyc_settings['mock_result_dob'])) {
            $result = $this->kyc_settings['mock_result_dob'];
            $res_setting = $this->mapDobResultToSetting($result);
        } else {
            // TODO shall we find a more solid way to parse the results? /Paolo
            $result = explode(' ', strtoupper($res->AuthenticateSPResult->BandText));
            $result = strtoupper($result[0]);
            $res_setting = $this->mapDobResultToSetting($result);
        }

        $user->setSetting('id3global_res', $res_setting);
        $user->setSetting('id3global_requested_at', date('Y-m-d H:i:s'));

        /* Inserting entry to responsibility_check table */
        $this->insertIdentityCheck($result, $ud);

        if ( !in_array($result, array('PASS','PASS DUAL','PASS CREDIT'))) {
            if (empty($result)) {
                $res_setting  = empty($this->failed_id3_dob_result) ? "ID3 failed, unknown reason" : $this->failed_id3_dob_result;
            }
            // we return the mapped error code to "standardize" the content on the Actions/Comments/Email
            return $res_setting;
        }

        return true;
    }

    /**
     * PEP and Sanction Lists check with GBG ID3 Global
     *
     * @param int|DBUser $user
     * @return bool
     */
    public function checkPEPSanctions($user)
    {
        $user = cu($user);

        if (in_array($user->getCountry(), $this->config_settings['pep_excluded_countries'])) {
            return true;
        }

        if (phive()->isLocal() && !empty($this->kyc_settings['mock_result_pep'])) {
            $result = $this->kyc_settings['mock_result_pep'];
        } else {
            $ud = $user->data;
            $res = $this->requestID3Global($this->kyc_settings['pep_id'], $this->kyc_settings['pep_reference'], $ud);

            $result = strtoupper(explode(' ', strtoupper($res->AuthenticateSPResult->BandText))[0]);
        }

        if (empty($result)) {
            $to_setting = self::PEP_RESULTS['ERROR'];
        } else {
            $to_setting = isset(self::PEP_RESULTS[$result]) ? self::PEP_RESULTS[$result] : self::PEP_RESULTS['NOT_MAPPED'];
            if ($to_setting === self::PEP_RESULTS['NOT_MAPPED']) {
                phive()->dumpTbl('id3global_pep_error', "A non mapped result '$to_setting' is being returned");
            }
        }
        $user->refreshSetting('id3global_pep_res', $to_setting);

        if ($result == 'PASS') {
            return true;
        } else {
            if (empty($result)) {
                $result  = empty($this->failed_id3_pep_result) ? "ID3 failed, unknown reason" : $this->failed_id3_pep_result;
            }
            return $result;
        }
    }



    /**
     * @param string $id API call identifier
     * @param string $reference Our reference for the call
     * @param array $ud User data array
     * @param int $attempt User data array
     * @return bool
     */
    public function requestID3Global($id, $reference, $ud, int $attempt = 1)
    {
        $pwd = $this->kyc_settings['password'];
        $username = $this->kyc_settings['login'];
        $wsdl = $this->kyc_settings['wsdl'];
        $version = $this->kyc_settings['version'] ?? 0;
        $wsse_header = new WsseAuthHeader($username, $pwd);

        list($year, $month, $day) = explode('-', $ud['dob']);
        $client = new SoapClient($wsdl, ['trace' => $this->kyc_settings['enable_soap_trace']]);
        $client->__setSoapHeaders(array($wsse_header));
        $encoded_variables = $this->getEncodedRequestVariables($ud);

        $params = array(
            'ProfileIDVersion' => array('ID' => $id, 'Version' => $version),
            'CustomerReference' => $reference,
            'InputData' => array(
                'Personal' => array(
                    'PersonalDetails' => array(
                        'Forename' => $encoded_variables['Forename'],
                        'Surname' => $encoded_variables['Surname'],
                        'DOBDay' => $day,
                        'DOBMonth' => $month,
                        'DOBYear' => $year
                    )
                ),
                'Addresses' => array(
                    'CurrentAddress' => $this->getCurrentAddress($ud, $encoded_variables)
                )
            ),
        );

        $start_time = microtime(true);
        $map_request_type = array_merge(
            array_fill_keys($this->kyc_settings['dob_id'], 'age'),
            [$this->kyc_settings['pep_id'] => 'pep']
        );
        $request_type = $map_request_type[$id] ?: 'unknown';

        try {
            $res = $client->AuthenticateSP($params);

            if (isCli() && !empty($this->kyc_settings['enable_soap_trace'])) {
                echo "ID3 VALID REQUEST:\n" . print_r($client->__getLastRequest(), true);
            }
        } catch (Exception $e) {
            if (isCli() && !empty($this->kyc_settings['enable_soap_trace'])) {
                echo "ID3 INVALID REQUEST:\n" . print_r($client->__getLastRequest(), true);
            }
            // To keep compatibility with error mapping
            $this->failed_id3_dob_result = 2; // Network error
            $this->failed_id3_pep_result = $e->getMessage();
            phive()->externalAuditTbl("id3global_{$request_type}", $params, "ID3 auth failed. Reason: {$e->getMessage()}", (microtime(true) - $start_time), 500, 0, 0, $ud['id']);

            $max_attempts_count = $this->kyc_settings['max_attempts_count'] ?? 2;

            if ($attempt < $max_attempts_count) {
                $wait_time = 5;
                sleep($wait_time);

                return $this->requestID3Global($id, $reference, $ud, $attempt + 1);
            }

            return false;
        }

        phive()->externalAuditTbl("id3global_$request_type", $params, $res, (microtime(true) - $start_time), 200, 0, $res->AuthenticateSPResult->AuthenticationID ?? 0, $ud['id']);

        return $res;
    }

    public function getCurrentAddress($user, $encoded_variables): array
    {
        $user_data= cu($user);
        switch ($user_data->getSetting('main_province')) {
            case 'ON':
                $address = [
                    'Country' => isset($this->bank_countries[$user['country']]) ? $this->bank_countries[$user['country']]['printable_name'] : null,
                    'Street' => $user['address'],
                    'City' => $user['city'],
                    'StateDistrict' => 'ON',
                    'Region' => 'ON',
                    'ZipPostcode' => $user['zipcode'],
                    'Building' => $user_data->getSetting('building'),
                ];
                break;
            default:
                $address = [
                    'Country' => isset($this->bank_countries[$user['country']]) ? $this->bank_countries[$user['country']]['printable_name'] : null,
                    'AddressLine1' => $encoded_variables['AddressLine1'],
                    'AddressLine2' => $encoded_variables['AddressLine2'],
                    'AddressLine3' => strtoupper(str_replace(' ', '', $user['zipcode']))
                ];
        }
        return $address;
    }

    /**
     * Return different version of encoded user variables depending on a kyc_setting
     * Possible values are:
     * - "raw": we do not do any encoding, this will work for most of the customers,
     *          but will break with an invalid XML format in case of special chars or quotes
     * - "cdata" / Default: we wrap the fields with <![CDATA[xxx]]> to be able to send any type of char
     *
     * 2020-10-02 according to ID3 cdata should be the correct solution but doesn't seem to work
     * as expected, possibly this could have been broken from 2020-07-29. /Paolo
     *
     * @param $ud
     * @return array
     */
    private function getEncodedRequestVariables($ud)
    {
        switch ($this->kyc_settings['request_encoding']) {
            case 'raw':
                $encoded_variables = [
                    // Personal
                    'Forename' => $ud['firstname'],
                    'Surname' => $ud['lastname'],
                    // Addresses
                    'Country' => isset($this->bank_countries[$ud['country']]) ? $this->bank_countries[$ud['country']]['printable_name'] : null,
                    'AddressLine1' => $ud['address'],
                    'AddressLine2' => $ud['city'],
                ];
                break;
            case 'cdata':
            default:
                $encoded_variables = [
                    // Personal
                    'Forename' => new \SoapVar("<Forename><![CDATA[{$ud['firstname']}]]></Forename>", XSD_ANYXML),
                    'Surname' => new \SoapVar("<Surname><![CDATA[{$ud['lastname']}]]></Surname>", XSD_ANYXML),
                    // Addresses
                    'Country' => isset($this->bank_countries[$ud['country']]) ? $this->bank_countries[$ud['country']]['printable_name'] : null,
                    'AddressLine1' => new \SoapVar("<AddressLine1><![CDATA[{$ud['address']}]]></AddressLine1>", XSD_ANYXML),
                    'AddressLine2' => new \SoapVar("<AddressLine2><![CDATA[{$ud['city']}]]></AddressLine2>", XSD_ANYXML),
                ];
        }
        return $encoded_variables;
    }

    /**
     * @param string $result
     * @param array $user_data
     */
    private function insertIdentityCheck(string $result, array $user_data)
    {
        $insert['user_id']  = $user_data['id'];
        $insert['fullname'] = $user_data['firstname'] . ' ' . $user_data['lastname'];
        $insert['country'] = $user_data['country'];
        $insert['requested_at'] = phive()->hisNow();
        $insert['status'] = $result;
        $insert['solution_provider'] = self::GBG_PROVIDER;

        phive('SQL')->sh($user_data['id'])->insertArray('responsibility_check', $insert);
    }
}

/**
 * Create a special SOAP header required by communication with ID3.
 *
 * Class WsseAuthHeader
 */
class WsseAuthHeader extends SoapHeader
{
    private $wss_ns = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    public function __construct($user, $pass, $ns = null)
    {
        if ($ns) {
            $this->wss_ns = $ns;
        }
        $auth = new stdClass();
        $auth->Username = new SoapVar($user, XSD_STRING, null, $this->wss_ns, null, $this->wss_ns);
        $auth->Password = new SoapVar($pass, XSD_STRING, null, $this->wss_ns, null, $this->wss_ns);
        $username_token = new stdClass();
        $username_token->UsernameToken = new SoapVar($auth, SOAP_ENC_OBJECT, null, $this->wss_ns, 'UsernameToken', $this->wss_ns);
        $security_sv = new SoapVar(
            new SoapVar($username_token, SOAP_ENC_OBJECT, null, $this->wss_ns, 'UsernameToken', $this->wss_ns),
            SOAP_ENC_OBJECT,
            null,
            $this->wss_ns,
            'Security',
            $this->wss_ns
        );

        parent::__construct($this->wss_ns, 'Security', $security_sv, true);
    }
}
