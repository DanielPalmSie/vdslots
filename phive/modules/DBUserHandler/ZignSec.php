<?php

require_once __DIR__ .'/ExternalVerifier.php';
require_once __DIR__ .'/ZignSecLookupPersonData.php';

class ZignSec extends ExternalVerifier
{
    protected $config = [];

    private $params = [];

    public function __construct()
    {
        $this->config = phive('DBUserHandler')->getSetting('zignsec_v2');
    }

    /**
     * I've overridden the function here, cause i'm not sure if having that session variable in the parent method may cause issues /Paolo
     * @param $result
     * @return array
     */
    public function success($result) {
        return ['success' => true, 'result' => $result];
    }

    protected function request($method, $params, $http_action = 'POST', $log = false)
    {
        $this->params = $params;
        $uri          = $this->config['uri'] . $method;
        $headers      = ["Authorization: {$this->config['auth_key']}"];
        $start_time   = microtime(true);

        try {
            if($http_action == 'GET'){
                $result   = phive()->get($uri.'?'.http_build_query($params), $this->config['timeout'], $headers, 'zignsec');
            }else{
                $result   = phive()->post($uri, $params, 'application/json', $headers, 'zignsec', 'POST', $this->config['timeout']);
            }

            $result = json_decode($result, true);

            if(empty($result) || $result == 'Internal Server Error'){
                return $this->fail('Connection error.');
            }

            if (!empty($log)) {
                phive()->externalAuditTbl("zignsec-{$log}", $params, $result, (microtime(true) - $start_time), 200, 0, ($result['id'] ?? 0));
            }

            //TEMP FIX AS THEIR API IS BROKEN
            if (reset($result['errors'])['code'] == 'Internal Exception') {
                return $this->success(['WasFound' => 'hidden']);
            }

            if (!empty($result['errors'])) {
                $err = $result['errors'][0];
                // BankIDSE Exception: ALREADY_IN_PROGRESS
                return $this->fail($err['description'].': '.$err['code'], 1);
            } else {
                return $this->success($result);
            }
        } catch (Exception $e) {
            return $this->fail($e->getMessage(), 2);
        }
    }

    /**
     * @param $country
     * @param $key
     * @param $value
     * @return array
     */
    private function getLookupPersonCommon($country, $key, $value)
    {
        if ($this->config['mock_kyc'] === true && phive('BrandedConfig')->isProduction() !== true) {
            return $this->success($this->config['mock_user_lookup_data']);
        }

        $nid = $this->config['mock_nid'] ?? $value;

        $nid   = phive()->rmNonNums($nid);
        $country = strtolower($country);
        $lookup_data = '-'.$country;
        $res = $this->request('ekyc/lookupperson', [
            'CountryCode' => $country,
            $key          => $nid
        ], 'POST', 'lookup'.$lookup_data);

        return $res;
    }

    public function extvIdStart($country, $nid, $u = null, $action = 'Authenticate')
    {
        $nid = phive()->rmNonNums($nid);
        if(empty($nid)){
            return $this->fail('NID missing');
        }

        $res = $this->request("BankID$country/$action", ['PersonalNumber' => $nid]);

        if($res['success'] === false){
            return $res;
        }

        // Zignsec supports callbacks that can be used with websockets to prevent polling.
        $res['result']['supports_callback'] = true;
        return $res;
    }

    public function verifyCallbackHash($json)
    {
        $hash = hash_hmac('sha256', $json, $this->config['auth_key']);

        // We use hash_equals to prevent timing attacks.
        if(!hash_equals($hash, $_SERVER['HTTP_X_ZIGNSEC_HMAC_SHA256'])){
            // Note that this results in a 2xx header which we want as we don't want them to retry with broken credentials.
            return $this->fail('Could not verify hash');
        }
    }

    public function getExtData($country, $nid, $req_id = null)
    {
        $lookup_res = $this->getLookupPersonCommon($country, 'IdentityNumber', $nid);

        if (!$lookup_res
            || !$lookup_res['success']
            || !isset($lookup_res['result']['WasFound'])
            || !$lookup_res['result']['WasFound']) {
            return false;
        }

        if (!empty($req_id)) {
            phMsetArr("$req_id-raw", $lookup_res['result']);
            phMsetArr("$req_id-nid", $nid);
        }

        return $this->mapLookupData($lookup_res['result']);
    }

    /**
     * @param $country
     * @param $nid
     * @return false|ZignSecLookupPersonData
     */
    public function getLookupPersonByNid($country, $nid)
    {
        $lookup_res = $this->getLookupPersonCommon($country, 'IdentityNumber', $nid);

        if (!$lookup_res['success']) {
            return false;
        }

        return ZignSecLookupPersonData::fromV2($lookup_res['result'], $country);
    }

    /**
     * @param $data
     * @param bool $return_array
     * @return array|ZignSecLookupPersonData
     */
    public function mapLookupData($data, bool $return_array = true)
    {
        $nationality = null;
        if (lic('oneStepRegistrationEnabled') || isBankIdMode()) {
            $nationality = 'SE';
        }

        $lookup_data = ZignSecLookupPersonData::fromV2($data, $nationality);

        $_SESSION['ext_normal_user'] = $lookup_data->isActiveNid();

        return $return_array ? $lookup_data->toArray() : $lookup_data;
    }

    public function isDebug()
    {
        return $this->config['debug'] === true;
    }
}
