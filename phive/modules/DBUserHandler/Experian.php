<?php

require_once('ExternalKyc.php');

/**
 * Currently used only for external DOB validation in UK.
 *
 * XSD for data validation, valid for Request only (to implement if we need to start using more services from them)
 * Search: https://xml.proveid.experian.com/xsd/Search.xsd
 * Common: https://xml.proveid.experian.com/xsd/Common.xsd
 *
 * Class Experian
 */
class Experian extends ExternalKyc
{
    private $failed_experian_dob_result;

    /**
     * The original XML contains another html_encoded XML inside.
     * We store the parsed decoded internal XML with the data on this variable.
     *
     * @var XML string
     */
    private $parsed_result_xml;


    public function __construct()
    {
        parent::__construct();
        $this->kyc_settings = phive('Licensed')->getSetting('kyc_suppliers')['experian'];
    }

    /**
     * Will map the result from Experian API to a standardized value on our DB.
     *
     * @param $result
     * @return array
     */
    public function mapDobResultToSetting($result)
    {
        // If we have an error code from Experian, we will set that into the user settings.
        if (!empty($this->failed_experian_dob_result)) {
            return ['experian_error', $this->failed_experian_dob_result];
        }

        $map = [
            -1 => ['experian_res', self::DOB_RESULTS['ALERT']],
            0 => ['experian_res', self::DOB_RESULTS['REFER']],
            1 => ['experian_res', self::DOB_RESULTS['PASS']],
            2 => ['experian_error', self::DOB_RESULTS['ERROR']],
        ];

        list($setting_key, $setting_value) = $map[$result] ?: ['experian_error', 'unknown.error'];
        if ($setting_value === 'unknown.error') {
            phive()->dumpTbl('experian_error', "A non mapped result '$result' is being returned");
        }
        return [$setting_key, $setting_value];
    }

    /**
     * Age checks for Experian / Old function name: experianDob
     *
     * @param DBUser $user
     * @return bool
     */
    public function checkDob($user)
    {
        $pwd = $this->kyc_settings['password'];
        $uname = $this->kyc_settings['login'];

        $user = cu($user);
        $ud = $user->getData();
        // Premise is a required field together with Postcode, that's why if is missing we are skipping the call.
        $premise = $this->getPremise($ud);
        $gender = $ud['sex'][0];
        // need to remove all non numeric chars from mobile number
        $mobile = preg_replace('/[^0-9]+/', '', $ud['mobile']);

        if (!empty($premise)) { // TODO check premise /Paolo
            $xml = "".
"<Search>
    <Authentication>
        <Username>$uname</Username>
        <Password>$pwd</Password>
    </Authentication>
    <CountryCode>GBR</CountryCode>
    <Person>
        <Name>
            <Forename>{$ud['firstname']}</Forename>
            <Surname>{$ud['lastname']}</Surname>
        </Name>
        <Gender>$gender</Gender>
        <DateOfBirth>{$ud['dob']}</DateOfBirth>
    </Person>
    <Addresses>
        <Address Current=\"1\">
            <Premise>$premise</Premise>
            <Postcode>{$ud['zipcode']}</Postcode>
            <CountryCode>GBR</CountryCode>
        </Address>
    </Addresses>
    <Telephones>
        <Telephone>
            <Number>{$mobile}</Number>
        </Telephone>
    </Telephones>
    <IPAddress>{$ud['reg_ip']}</IPAddress>
    <Emails>
        <Email>{$ud['email']}</Email>
    </Emails>
    <YourReference>Age verification on user: {$ud['username']}</YourReference>
    <SearchOptions>
        <ProductCode>ProveID</ProductCode>
    </SearchOptions>
</Search>";

            phive()->dumpTbl("experian_out", $xml);
            // 0 is a valid test result, so we need to support that.
            if (phive()->isLocal() && (!empty($this->kyc_settings['mock_result_dob']) || $this->kyc_settings['mock_result_dob'] === 0)) {
                $res = $this->kyc_settings['mock_result_dob'];
            } else {
                try {
                    $start_time = microtime(true);
                    $post_result = $this->experianPost($xml);
                    $res = $this->parseExperian($post_result, 'Decision');
                    phive()->externalAuditTbl("experian_dob", $xml, $this->parsed_result_xml, (microtime(true) - $start_time), 200, 0, 0, $ud['id']);
                } catch (Exception $e) {
                    // To keep compatibility with error mapping
                    $this->failed_experian_dob_result = $e->getMessage();
                    phive()->externalAuditTbl("experian_dob", $xml, "Experian failed. Reason: {$e->getMessage()}", (microtime(true) - $start_time), 500, 0, 0, $ud['id']);
                }
            }
        } else {
            phive()->externalAuditTbl("experian_dob", "Premise was not found for user {$ud['id']}, address doesn't contain numbers.", "Experian failed. Reason: API will not work without Premise.", 0, 500, 0, 0, $ud['id']);
            $res = 0;
        }

        $res = (int)$res;
        list($setting_key, $setting_value) = $this->mapDobResultToSetting($res);
        $user->setSetting($setting_key, $setting_value);

        if ($res !== 1) {
            // we return the mapped error code to "standardize" the content on the Actions/Comments/Email
            return $setting_value;
        }

        return true;
    }

    /**
     * We only care of the decision result returned by Experian:
     * - "1"    Accept
     * - "-1"   Reject
     * - "0"    Refer
     * - "2"    Network error - This is a custom VS result we return if no $res is provided
     * - "Experian ERROR CODE" - see mapExperianError
     *
     * @param $res
     * @param string $key
     * @return int|string
     */
    function parseExperian($res, $key = '')
    {
        if (empty($res)) {
            return 2;
        }

        $xml = preg_replace("/(<\/?)(\w+):([^>]*>)/", '$1$2$3', $res);
        $xml = simplexml_load_string($xml);
        $json = json_encode($xml);
        $response_array = json_decode($json,true);

        $search_return_xml = $response_array['soapenvBody']['ns1searchResponse']['searchReturn'] ?? false;
        phive()->dumpTbl("experian_in", $search_return_xml);

        $xml_obj = simplexml_load_string($search_return_xml);
        $this->parsed_result_xml = $xml_obj;


        $json = json_encode($xml_obj);
        $search_return_array = json_decode($json,true);
        /**
         * To avoid concatenating multiple "ErrorCode" we search in order on:
         * - DecisionMatrix
         * - CreditReference
         * - Anywhere
         */
        $error_code = $search_return_array['Result']['Summary']['DecisionMatrix']['ErrorCode'] ?? false;
        if(empty($error_code)) {
            $error_code = $search_return_array['Result']['Summary']['CreditReference']['ErrorCode'] ?? false;
            if(empty($error_code)) {
                $error_code = $search_return_array['ErrorCode'] ?? false;
            }
        }

        $outcome = $search_return_array['Result']['Summary']['DecisionMatrix']['Decision']['Outcome'] ?? false;

        if (!empty($error_code) || empty($outcome)) {
            $this->failed_experian_dob_result = $this->mapExperianError($error_code ?? false);
            return false;
        }

        if (empty($key)) {
            return $search_return_xml;
        }

        return $outcome;
    }

    /**
     * @param $code
     * @return string
     *
     * Authentication / Charging Errors 000 - 099
     * - Authentication Error: 000 - 049
     * - Charging Error: 050 - 099
     * Parsing errors 100-499
     * - Generic: 100 - 119
     * - Invalid Entry: 150 - 199
     * Search Errors 500 - 519
     * - Generic: 500 - 519
     * System Errors 900 / 999
     * - System: 900 - 950
     * - XML parsing: 950 - 998
     * - Unknown: 999
     */
    function mapExperianError($code)
    {
        $errors = [
            50 => 'Authentication Error',
            100 => 'Charging Error',
            120 => 'Parsing errors - Generic',
            500 => 'Parsing errors - Invalid Entry',
            520 => 'Search Errors - Generic',
            950 => 'System',
            998 => 'XML parsing'
        ];

        return phive()->getLvl($code, $errors, 'Unknown');
    }

    /**
     * Send the request to Experian API.
     * The XML need to be signed with a specific hash_mac base64encoded.
     *
     * Special note:
     * 2020-02-26 - The UAT environment will be taken down after some time (1-2months max)
     * if we need a new one we need to request that to Experian.
     *
     * @param $xml
     * @param string $op
     * @return mixed
     */
    function experianPost($xml, $op = 'IDSearch')
    {
        $timestamp = time();
        $message = $this->kyc_settings['login'].$this->kyc_settings['password'].$timestamp;
        $secret = $this->kyc_settings['hmac_private_key'];
        $hash = base64_encode( hash_hmac('sha256', $message, $secret, true));
        $signature = "{$hash}_{$timestamp}_{$this->kyc_settings['hmac_public_key']}";

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
          <soapenv:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:head="http://xml.proveid.experian.com/xsd/Headers" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:cor="http://corpwsdl2.oneninetwo">
             <soapenv:Header>
                <head:Signature>'.$signature.'</head:Signature>
             </soapenv:Header>
             <soapenv:Body>
                <cor:search soapenv:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/">
                   <xml xsi:type="xsd:string" xs:type="type:string" xmlns:xs="http://www.w3.org/2000/XMLSchema-instance"><![CDATA[' . $xml . ']]></xml>
                </cor:search>
             </soapenv:Body>
          </soapenv:Envelope>';
        $url = $this->kyc_settings['api_url'];
        $host = parse_url($url, PHP_URL_HOST);
        $headers = "SOAPAction: \"\"\r\nHost: $host\r\nConnection: Keep-Alive";
        $post_url = $this->kyc_settings['api_url']."$op.cfc";
        $timeout = $this->kyc_settings['dob_timeout'] ?: self::DEFAULT_API_TIMEOUT;
        $res = phive()->post($post_url, $xml, 'text/xml;UTF-8', $headers, "experian_{$op}", 'POST', $timeout);
        return $res;
    }
}