<?php
require_once __DIR__ . '/../../DBUserHandler/ExternalVerifier.php';
require_once __DIR__ . '/Bluem/Request/IBANRequest.php';
require_once __DIR__ . '/Bluem/Request/CRUKSRequest.php';
require_once __DIR__ . '/Bluem/Request/BSNRequest.php';
require_once __DIR__ . '/Bluem/Request/FOREIGNRequest.php';

class Bluem extends ExternalVerifier
{
    const ACCOUNT_TYPE_PERSON = 'NATURAL_PERSON';
    const ACCOUNT_TYPE_ORGANISATION = 'ORGANISATION';
    const IBAN_CHECK_RESULT_KNOWN = 'KNOWN';
    const IBAN_CHECK_RESULT_UNKNOWN = 'UNKNOWN';
    const IBAN_CHECK_NAME_RESULT_MATCHING = 'MATCHING';
    const IBAN_CHECK_NAME_RESULT_NON_MATCHING = 'NON_MATCHING';
    const IBAN_CHECK_NAME_RESULT_MISTYPED = 'MISTYPED';
    const IBAN_CHECK_ACCOUNT_RESULT_ACTIVE = 'ACTIVE';
    const IBAN_CHECK_ACCOUNT_RESULT_INACTIVE = 'INACTIVE';
    const IBAN_CHECK_SERVICE_TEMPORARILY_NOT_AVAILABLE = 'SERVICE_TEMPORARILY_NOT_AVAILABLE';

    public const CRUKS_SERVICE_WAS_DOWN_FLAG = 'cruks-service-was-down';
    public const CRUKS_SERVICE_IS_UP_FLAG = 'cruks-service-is-up';

    /** @var string $token */
    public string $token;
    /** @var string|int $sender_id */
    public $sender_id;
    /** @var string $sender_brand */
    public string $sender_brand;
    /** @var string $base_url */
    public string $base_url;

    /** @var bool $cruks_check_enabled */
    private bool $cruks_check_enabled = false;

    /**
     * Bluem constructor
     *
     * @param string $base_url
     * @param string $token
     * @param string|int $sender_id
     * @param string $sender_brand
     */
    public function __construct(string $base_url, string $token, $sender_id, string $sender_brand)
    {
        $this->token = $token;
        $this->sender_id = $sender_id;
        $this->sender_brand = $sender_brand;
        $this->base_url = $base_url;
    }

    /**
     * Enable/Disable Cruks Check
     *
     * @param bool $value
     * @return void
     */
    public function toggleCruksCheck($value): void
    {
        $this->cruks_check_enabled = $value;
    }

    /**
     * Method used to do all requests for Bluem
     *
     * @param $type
     * @param $sub_type
     * @param $action
     * @param $xml
     * @param string $debug_key
     * @return array|null
     */
    public function request($type, $sub_type, $action, $xml, $debug_key = 'bluem'): ?array
    {
        $d = new DateTime();
        $stamp = date('D, j M Y H:i:s') . ' GMT';
        $file_name = "{$sub_type}-{$this->sender_id}-BSP1-" . $d->format('YmdHisv') . ".xml";
        $url = "{$this->base_url}/{$type}/{$action}?token={$this->token}";
        $type = "application/xml; type={$sub_type}; charset=utf-8;";
        $headers = [
            "x-ttrs-date: $stamp",
            "x-ttrs-files-count: 1",
            "x-ttrs-filename: $file_name"
        ];

        $res = phive()->post($url, $xml, $type, $headers, $debug_key, 'POST', '', [], '');
        $res = phive()->xmlToArr($res);
        $result = array_pop($res);

        phive()->dumpTbl("{$debug_key}-check", [$xml, $result]);

        return $result ?? [];
    }

    /**
     * Get current timestamp in required format
     *
     * @return string
     */
    public function getXmlStamp(): string
    {
        // 2019-05-02T08:28:40.314Z
        return (new DateTime())->format("Y-m-d\TH:i:s.v\Z");
    }

    /**
     * Will check the given IBAN against Bluem's services with the help of the user's full name
     * does the account belong to the user or not is the question.
     *
     * @param DBUser $user
     * @param array $data [Should include iban, full_name and user_id]
     *
     * @return array A result array on this form: ['success' => false, 'result' => 'error_message|valid_response']
     * if a response is received from external service. Response will be in following structure
     * [
     *  'success' => true,
     *  'result' => [
     *      'known' => ?, 'matched' => ?, 'active' => ?, 'mistyped' => ?, 'account_type' => ?, 'suggested_name' => ''
     *  ]
     * ]
     */
    public function checkIBAN(array $data, DBUser $user = null)
    {
        $request = new IBANRequest($data);

        if (!$request->isValidRequest()) {
            return $this->fail('internal_validation_error' . $request->getMissingFields());
        }

        $xml = $request->getXmlWithWrapper($this->sender_id, $this->getXmlStamp());
        $res = $this->request('icr', 'INX', 'createTransactionWithToken', $xml, 'bluem-iban');
        if (empty($res)) {
            return $this->fail('connection_error');
        }

        $log_data = [
            'original_response' => $res,
            'request_xml' => $request->getRequestXml()
        ];

        $error = isset($res['IBANCheckErrorResponse']) && isset($res['IBANCheckErrorResponse']['Error']) ? $res['IBANCheckErrorResponse']['Error'] : [];
        if (!empty($error)) {
            phive('Logger')->error('bluem-iban-error-response', ['user_id' => uid($user)]);
            return $this->fail($error);
        }

        $res = $res['IBANCheckTransactionResponse'];
        if (empty($res)) {
            phive('Logger')->error('bluem-iban-error-response-missing_result', ['user_id' => uid($user)]);
            return $this->fail('missing_result');
        }

        $check_result = $res['IBANCheckResult'];
        $account_result = $res['AccountDetails'];

        if ($check_result['IBANResult'] === self::IBAN_CHECK_SERVICE_TEMPORARILY_NOT_AVAILABLE) {
            phive('Logger')->error('bluem-iban-error-response-service_down', ['user_id' => uid($user)]);
            return $this->fail('service_down');
        }

        phive('Logger')->debug('bluem-iban-check-success', [$log_data]);

        $response = [
            'known' => isset($check_result['IBANResult']) && $check_result['IBANResult'] === self::IBAN_CHECK_RESULT_KNOWN,
            'matched' => isset($check_result['NameResult']) && $check_result['NameResult'] === self::IBAN_CHECK_NAME_RESULT_MATCHING,
            'active' => isset($check_result['AccountStatus']) && $check_result['AccountStatus'] === self::IBAN_CHECK_ACCOUNT_RESULT_ACTIVE,
            'mistyped' => isset($check_result['AccountStatus']) && $check_result['NameResult'] === self::IBAN_CHECK_NAME_RESULT_MISTYPED,
            'account_type' => isset($account_result['AccountType']) && $account_result['AccountType'],
            'suggested_name' => $check_result['SuggestedName'] ?? $data['full_name'],
        ];

        return $this->success($response);
    }

    /**
     * Execute the cruks check request
     *
     * @param string $xml
     * @return array|null
     */
    private function requestCruksCheck($xml): ?array
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <CRUKSCheckInterface xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" type="TransactionRequest"
                mode="direct" senderID="' . $this->sender_id . '" version="1.0"
                createDateTime="' . $this->getXmlStamp() . '" messageCount="1"
            >
                ' . $xml . '
            </CRUKSCheckInterface>
        ';

        return $this->request('ccr', 'CTX', 'createTransactionWithToken', $xml, 'bluem-cruks');
    }

    /**
     * Check if provided data is a user registered in CRUKS
     *
     * @param array $data
     * @param DBUser|null $user
     * @param bool $on_login
     * @return array
     */
    public function isUserRegisteredInCruks($data, $user = null, $on_login = false): array
    {
        if (empty($this->cruks_check_enabled)) {
            // Cruks check is disabled, consider all users not excluded
            return $this->success(false);
        }

        if (!empty($data['cruks_code'])) {
            // Dutch with CRUKSCode
            $request = new CRUKSRequest($data);
        } elseif (!empty($data['bsn'])) {
            // Dutch player with BSN, no CRUKSCode
            $request = new BSNRequest($data);
        } else {
            // Player without BSN, no CRUKSCode
            $request = new FOREIGNRequest($data);
        }

        if (!$request->isValidRequest()) {
            return $this->fail('Internal validation failed with missing fields: ' . $request->getMissingFields());
        }

        $res = $original_response = $this->requestCruksCheck($request->getRequestXml());

        if (empty($res)) {
            phMset(self::CRUKS_SERVICE_WAS_DOWN_FLAG, 1);

            if (!empty($user)) {
                $user->setSetting(self::CRUKS_SERVICE_WAS_DOWN_FLAG, 1);
                // Allow player to login when service is down
                // User will be logged out if found excluded when the service gets back up
                if($on_login) {
                    return $this->success(false);
                }
            }

            return $this->fail('connection_error');
        }

        phMset(self::CRUKS_SERVICE_IS_UP_FLAG, 1);

        // this scenario should never happen
        $res = $res['CRUKSCheckTransactionResponse'] ?? [];
        if (empty($res)) {
            phive()->dumpTbl('bluem-cruks-error-response-missing_result', [$original_response, $request->getRequestXml()], $user);
            return $this->fail('missing_result');
        }

        $error = $res['Error'] ?? [];
        if (!empty($error)) {
            phive()->dumpTbl('bluem-cruks-error-response', $error, $user);
            return $this->fail($error);
        }

        // this scenario should never happen
        $res = $res['CRUKSCheckResult'];
        if (empty($res)) {
            phive()->dumpTbl('bluem-cruks-error-response-missing_result', [$original_response, $request->getRequestXml()], $user);
            return $this->fail('missing_result');
        }

        if (!empty($user) && !empty($res['ResponseCRUKSCode'])) {
            $user->setSetting('cruks_code', $res['ResponseCRUKSCode']);
            lic('clearBSN', [$user], $user);
        }

        return $this->success($res['IsRegistered'] === 'true');
    }

    /**
     * Create identity transaction
     * Here we set the redirect url
     *
     * @param string $return_url
     * @param DBUser $user
     * @param null|string $bic
     * @return array
     */
    public function createIdentityTransaction(string $return_url, DBUser $user, $bic = null): array
    {
        $debtor_reference = $user->getId();
        $entrance_code = uniqid();

        $xml = phive()->ob(function () use ($return_url, $entrance_code, $debtor_reference, $bic) {
            echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
            ?>
            <IdentityInterface xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" type="TransactionRequest"
                               mode="direct" senderID="<?php echo $this->sender_id ?>" version="1.0"
                               createDateTime="<?php echo $this->getXmlStamp() ?>" messageCount="1"
                               xsi:noNamespaceSchemaLocation="../IdentityInterface.xsd">
                <IdentityTransactionRequest entranceCode="<?php echo $entrance_code ?>" language="nl"
                                            brandID="<?php echo $this->sender_brand ?>" sendOption="none">
                    <RequestCategory>
                        <CustomerIDRequest action="request"/>
                        <NameRequest action="request"/>
                        <AddressRequest action="request"/>
                        <BirthDateRequest action="request"/>
                        <AgeCheckRequest ageOrOlder="18" action="skip"/>
                        <GenderRequest action="request"/>
                        <TelephoneRequest action="skip"/>
                        <EmailRequest action="skip"/>
                    </RequestCategory>
                    <Description><?php et('bluem.identification') ?></Description>
                    <DebtorReference><?php echo $debtor_reference ?></DebtorReference>
                    <DebtorReturnURL automaticRedirect="1"><?php echo htmlentities($return_url) ?></DebtorReturnURL>
                    <?php if (!empty($bic)): ?>
                        <DebtorWallet>
                            <IDIN>
                                <BIC><?php echo $bic ?></BIC>
                            </IDIN>
                        </DebtorWallet>
                    <?php endif ?>
                </IdentityTransactionRequest>
            </IdentityInterface>
            <?php
        });

        $res = $this->request('ir', 'ITX', 'createTransactionWithToken', $xml);
        $trans_id = $res['IdentityTransactionResponse']['TransactionID'];
        $url = $res['IdentityTransactionResponse']['TransactionURL'];

        if (empty($trans_id)) {
            return [];
        }

        return [$url, $trans_id, $entrance_code];
    }

    /**
     * Get identity transaction status
     *
     * @param $trans_id
     * @param $entrance_code
     * @return array
     */
    public function getIdentityTransactionStatus($trans_id, $entrance_code): array
    {
        $xml = phive()->ob(function () use ($trans_id, $entrance_code) {
            echo '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
            ?>
            <IdentityInterface xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" type="StatusRequest" mode="direct"
                               senderID="<?php echo $this->sender_id ?>" version="1.0"
                               createDateTime="<?php echo $this->getXmlStamp() ?>" messageCount="1">
                <IdentityStatusRequest entranceCode="<?php echo $entrance_code ?>">
                    <TransactionID><?php echo $trans_id ?></TransactionID>
                </IdentityStatusRequest>
            </IdentityInterface>
            <?php
        });

        $res = $this->request('ir', 'ISX', 'requestTransactionStatusWithToken', $xml);

        if (empty($res)) {
            return $this->fail('Connection error');
        }

        if (!empty($res['IdentityInterface']['IdentityErrorResponse'])) {
            return $this->fail($res['IdentityInterface']['IdentityErrorResponse']['Error']['ErrorMessage']);
        }

        if (empty($res['IdentityStatusUpdate'])) {
            return $this->fail('Unknown error');
        }

        return $this->success($res);
    }
}
