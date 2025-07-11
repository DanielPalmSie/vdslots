<?php

require_once __DIR__ .'/ZignSecLookupPersonData.php';

class ZignSecV5 extends ZignSec
{
    const CANCELLED_RESPONSE = 'CANCELLED';

    const CANCELLED_DECLINED = 'DECLINED';

    const POSSIBLE_LANGUAGES_MITID_FORM = ['En', 'Da', 'Kl'];

    public function __construct()
    {
        $this->config = phive('DBUserHandler')->getSetting('zignsec_v5');
    }

    /**
     * @param int|string $nid
     * @param string|null $zipcode
     * @return array
     */
    private function getLookupPersonCommon($nid, ?string $zipcode = null)
    {
        if ($this->config['mock_kyc'] === true && phive('BrandedConfig')->isProduction() !== true) {
            return $this->success($this->config['mock_user_lookup_data']);
        }

        $nid = $this->config['mock_nid'] ?? $nid;

        $metadata = [
            'ssn' => (string) $nid,
        ];

        if ($zipcode) {
            $metadata['zip_code'] = $zipcode;
        }

        return $this->request('lookup_person/se', ['metadata' => $metadata], 'POST', 'lookup-v5-SE');
    }

    /**
     * Get MitID form
     *
     * @param string|null $language
     * @return array
     */
    public function getMitIdForm(string $language = null): array
    {
        $url_for_redirect = phive()->getSiteUrl('', false, 'phive/modules/DBUserHandler/json/zignsec_mitid_return.php');
        if(!in_array($language, self::POSSIBLE_LANGUAGES_MITID_FORM)) {
            $language = 'En';
        }

        return $this->request('identity_verification/mitid', [
            'metadata' => [
                'action' => 'LogOn',
                'language' => $language,
                'level' => 'Low',
                'method' => 'Loa',
                'popup_context' => false,
                'psd2' => true,
                'reference_text_body' => 'Log on to VideoSlots',
                'requested_attributes' => [
                    'DATE_OF_BIRTH',
                    'AGE',
                    'IDENTITY_NAME',
                    'IDENTITY_ADDRESS',
                ],
                'service_provider_reference' => 'VideoSlots'
            ],
            'redirect_failure' => $url_for_redirect,
            'redirect_success' => $url_for_redirect
        ]);
    }

    /**
     * Get result from existing login MitID
     *
     * @param string $mitId_response_id
     * @return array
     */
    public function getMitIdStatus(string $mitId_response_id): array
    {
        return $this->request('' . $mitId_response_id, [], 'GET', true);
    }

    /**
     * @param $data
     * @param bool $return_array
     * @return array|ZignSecLookupPersonData
     */
    public function mapLookupData($data, bool $return_array = true)
    {
        $nationality = null;
        if(lic('oneStepRegistrationEnabled')){
            $nationality = 'SE';
        }

        $lookup_data = ZignSecLookupPersonData::fromV5($data, $nationality);

        $_SESSION['ext_normal_user'] = $lookup_data->isActiveNid();

        return $return_array ? $lookup_data->toArray() : $lookup_data;
    }

    /**
     * Get MitID CPR match of user and his personal data
     *
     * @param $data
     * @return array
     */
    public function getMitIdCprMatch($data): array
    {
        $language = ucfirst($data['lang']);
        $url_for_redirect = phive()->getSiteUrl('', false, 'phive/modules/DBUserHandler/json/zignsec_mitid_return.php');
        if(!in_array($language, self::POSSIBLE_LANGUAGES_MITID_FORM)) {
            $language = 'En';
        }

        return $this->request('match_cpr/mitid', [
            'metadata' => [
                'action' => 'LogOn',
                'cpr_number' => $data['nid'],
                'language' => $language,
                'level' => 'Low',
                'method' => 'Loa',
                'popup_context' => false,
                'psd2' => true,
                'reference_text_body' => 'Log on to VideoSlots',
                'requested_attributes' => [
                    'DATE_OF_BIRTH',
                    'AGE',
                    'IDENTITY_NAME',
                    'IDENTITY_ADDRESS',
                ],
                'service_provider_reference' => 'VideoSlots'
            ],
            'redirect_failure' => $url_for_redirect,
            'redirect_success' => $url_for_redirect
        ]);
    }

    /**
     * @param $req
     * @return array
     */
    public function handleBankIdCallback($req)
    {
        $req = json_decode($req, true);

        if (!empty(phMget($req['id'] . '.result'))) {
            return ['success' => true, 'result' => $req];
        }

        $arr = phive()->mapit([
            'fullname' => 'fullName',
            'firstname' => 'firstName',
            'lastname' => 'lastName',
            'dob' => 'dateOfBirth',
            'nid' => 'personalNumber',
            'country' => 'countryCode',
            'sex' => 'gender'
        ], $req['result']['identity']);

        $arr['req_id'] = $req['id'];
        if (!empty($arr['sex']) && !in_array($arr['sex'], ['Male', 'Female'])) {
            $arr['sex'] = $arr['sex'] == 'F' ? 'Female' : 'Male';
        }
        phive('Licensed')->forceCountry($req['result']['identity']['countryCode']);

        return phive('Licensed')->doLicense($req['result']['identity']['countryCode'], 'onExtVerificationCallback', [$arr]);
    }

    public function getExtData($country, $nid, $req_id = null)
    {
        $lookup_res = $this->getLookupPersonCommon($nid);

        $responseCode = $lookup_res['result']['data']['result']['responseCode'] ?? '';
        if (strtolower($responseCode) !== 'ok') {
            return false;
        }

        if (!empty($req_id)) {
            phMsetArr("$req_id-raw", $lookup_res);
            phMsetArr("$req_id-nid", $nid);
        }

        return $this->mapLookupData($lookup_res);
    }

    /**
     * @param $country
     * @param $nid
     * @return false|ZignSecLookupPersonData
     */
    public function getLookupPersonByNid($country, $nid)
    {
        $lookup_res = $this->getLookupPersonCommon($nid);

        if (!$lookup_res['success']) {
            return false;
        }

        return ZignSecLookupPersonData::fromV5($lookup_res, $country);
    }

    public function getTwoFactorAuthSmsResult($data): array
    {
        return $this->request('two_factor_auth/sms', $data)['result'];
    }
}
