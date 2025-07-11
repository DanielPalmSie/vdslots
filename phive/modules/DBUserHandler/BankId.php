<?php

require_once __DIR__ . '/ZignSecV5.php';

class BankId extends ZignSecV5
{
    public bool $force_polling = true;
    private $log;
    private string $fail_url = "";
    private string $success_url = "";

    public function __construct()
    {
        parent::__construct();

        if ($this->config['bankid_webhook_url']) {
            $this->force_polling = false;
        }

        $this->log = phive('Logger')->getLogger('bankid');

    }

    /**
     * This function will be called each 1.4 seconds to check if the BankID authentication is complete.
     *
     * Documentation:
     * @link https://www.bankid.com/en/utvecklare/guider/teknisk-integrationsguide/graenssnittsbeskrivning/collect
     * @param $order_ref string
     * @param $lic_obj
     * @return array
     */
    public function getExtvIdResult(string $order_ref, $lic_obj): array
    {
        if ($this->config['mock_kyc'] === true &&
            phive('BrandedConfig')->isProduction() !== true &&
            $this->config['mock_automation'] === true
        ) {
            return [
                'success' => 1,
                'result' => $this->mockIdentity($order_ref)
            ];
        }

        $res = $this->request("sessions/$order_ref", [], 'GET');

        if ($res['success'] && isset($res['result']['result']['identity'])) {
            $identity = $res['result']['result']['identity'];

            try {
                $parsedIdentity = $this->parseIdentity($identity, $order_ref);
            } catch (\Exception $e) {
                $this->log->error('BankID Error', [$e->getMessage(), $identity, $order_ref]);
                return $this->fail(t('bankid.unknown-status.fail'));
            }

            return [
                'success' => 1,
                'result' => $parsedIdentity
            ];
        } else {
            $this->log->debug('BankID Identity failure', $res);
        }

        if (!in_array($res['result']['status'], ['Failed', 'Cancelled', 'TimeOut'])) {
            return [
                'success' => 0,
                'result' => 'waiting_for_result'
            ];
        }

        return [];
    }

    /**
     * @param array $identity
     * @param $order_ref
     * @return array
     */
    private function parseIdentity(array $identity, $order_ref): array
    {
        $data = [
            'nid' => $identity['personalNumber'],
            'country' => $identity['countryCode'],
            'req_id' => $order_ref,
            'firstname' => $identity['firstName'],
            'lastname' => $identity['lastName'],
            'fullname' => $identity['fullName'],
            'dob' => $identity['dateOfBirth']
        ];

        return $data;
    }


    private function mockIdentity($order_ref): array
    {
        $mockData = $this->config['mock_user_lookup_data']['data']['result'];
        $mockNid = $this->config['mock_nid'];

        $data = [
            'nid' => $mockNid,
            'country' => 'SE',
            'req_id' => $order_ref,
            'firstname' => $mockData['basic']['firstName'],
            'lastname' => $mockData['basic']['lastName'],
            'fullname' => $mockData['basic']['firstName'].' '.$mockData['basic']['lastName'],
            'dob' => $mockData['additional']['birthDate']
        ];

        return $data;
    }

    /**
     * Generate a new Verification order for a user with BankID
     *
     * On desktop, we store the start data in the session to be able to generate the QR code every second.
     * On mobile, we will trigger the application directly with the returned autoStartToken
     * Documentation
     * @link: https://www.bankid.com/en/utvecklare/guider/teknisk-integrationsguide/graenssnittsbeskrivning/auth
     *
     * @param $country
     * @param mixed $nid
     * @param mixed $u
     * @param string $action
     * @param bool $isApi
     * @return array
     */
    public function extvIdStart($country, $nid = null, $u = null, $action = 'auth', $isApi = false)
    {
        $methode = 'bankidse/browser/auth';
        $payload = [
            'metadata' => [
                'requirement' => [
                    'mrtd' => false,
                ],
            ],
        ];

        if ($this->success_url) {
            $payload['redirect_success'] = $this->success_url;
        }
        if ($this->fail_url) {
            $payload['redirect_failure'] = $this->fail_url;
        }

        if ($isApi){
            $methode = 'bankidse/auth';
            $payload = [
                'metadata' => [
                    'end_user_ip' => '127.0.0.1',
                    'requirement' => [
                        "mrtd" => false,
                        "pin_code" => false
                    ],
                ],
            ];
        }
        if ($this->config['certificate_policies']) {
            $payload["metadata"]["requirement"]["certificate_policies"] = $this->config['certificate_policies'];
        }

        if (!$this->force_polling) {
            $payload["webhook"] = $this->config['bankid_webhook_url'];
        }

        // https://docs.zignsec.com/api/v5/eid-bankid-se/
        $res = $this->request($methode, $payload);

        $res['result'] = $res['result']['data'];

        if ($this->config['bankid_webhook_url']) {
            $res['result']['supports_callback'] = true;
        }

        return $res;
    }

    public function setSuccessUrl(string $url)
    {
        $this->success_url = $url;
    }

    public function setFailUrl(string $url)
    {
        $this->fail_url = $url;
    }


}
