<?php

use GuzzleHttp\Exception\BadResponseException;
use Laraphive\Domain\User\DataTransferObjects\LoginCommonData;
use Laraphive\Domain\User\DataTransferObjects\LoginKycData;
use Laraphive\Domain\User\DataTransferObjects\LoginKycResponse;
use PayNPlay\ProcessorFactory;
use PayNPlay\ProcessorPayload;

require_once __DIR__ . '/../../../phive/modules/DBUserHandler/Registration/RegistrationHtml.php';

class PayNPlay extends PhModule
{
    /**
     *
     */
    public const SUCCESS_URL = 'phive/modules/PayNPlay/html/success.php';
    /**
     *
     */
    public const FAIL_URL = 'phive/modules/PayNPlay/html/fail.php';
    /**
     *
     */
    public const MTS_API_VERSION = '1.0';

    /**
     *
     */
    private const REDIS_KEY = 'pnp';

    /**
     *
     */
    private const PNPSWISH_REDIS_KEY = 'pnpswish';

    /**
     *
     */
    private const PNPBANKID_REDIS_KEY = 'pnpbankid';

    /**
     *
     */
    private const STRATEGY_TRUSTLY = 'strategy_trustly';
    /**
     *
     */
    private const STRATEGY_SWISH = 'strategy_swish';

    /**
     * @var \PayNPlay\ProcessorFactory|null
     */
    private $processorFactory;

    /**
     *
     */
    private const REDIS_KEY_BONUS_CODE = 'pnp_bonus_code';

    /**
     * @var
     */
    public $logger;

    /**
     *
     */
    public function __construct()
    {
        $this->logger = phive('Logger')->getLogger('paynplay');
        $this->processorFactory = ProcessorFactory::getInstance();
    }

    /**
     * @param $request
     * @return string
     */
    public function getDepositIframeUrl($request)
    {
        // validate the request
        if (! is_numeric($request['amount'])) {
            return $this->jsonFail(t('paynplay.invalid_amount'));
        }

        if ($this->getSetting('test_iframe', false) || p('account.pnp.login')) {
            return $this->jsonSuccess([
                'url' => phive()->getSiteUrl(
                    '',
                    true,
                    'phive/modules/PayNPlay/html/test_iframe.php?amount=' . $request['amount'] . '&currency=' . ciso()
                ),
            ]);
        }

        $client = $this->processorFactory->create($request['strategy'], $request['strategy_step']);
        $payload = new ProcessorPayload($request);

        try {
            $result = $client->process($payload);

            return $this->jsonSuccess([
                'url' => $result->getUrl(),
                'transactionId' => $result->getOrderId(),
            ]);
        } catch (BadResponseException $e) {
            $result = $e->getResponse()->getBody()->getContents();
            $this->logger->error('MTS connection failure', [$e->getMessage(), $request['strategy']]);

            return $this->jsonFail($result);
        } catch (Throwable $e) {
            $this->logger->error('MTS connection failure', [$e->getMessage(), $request['strategy']]);

            return $this->jsonFail('');
        }
    }

    /**
     * @param string $transactionId
     * @param array $payload
     * @return void
     */
    public function setBankIDTransactionData(string $transactionId, array $payload)
    {
        phMsetArr(self::PNPBANKID_REDIS_KEY . $transactionId, $payload);
    }

    /**
     * @param string $transactionId
     * @return array
     */
    public function getBankIDTransactionData(string $transactionId): array
    {
        return phMgetArr(self::PNPBANKID_REDIS_KEY . $transactionId) ?? [];
    }

    /**
     * @param string $transactionId
     * @return void
     */
    public function deleteBankIDTransactionData(string $transactionId): void {
        phMdel(self::PNPBANKID_REDIS_KEY . $transactionId);
    }

    /**
     * @param string $transactionId
     * @param array $extData
     * @return void
     */
    public function setBankIDExtData(string $transactionId, array $data): void
    {
        $oldData = $this->getBankIDExtData($transactionId);
        $newData = array_merge($oldData, $data);
        phMsetArr(self::PNPBANKID_REDIS_KEY . $transactionId, $newData);
    }

    /**
     * @param string $transactionId
     * @return array
     */
    public function getBankIDExtData(string $transactionId): array
    {
        return phMgetArr(self::PNPBANKID_REDIS_KEY . $transactionId) ?? [];
    }

    /**
     * @param array $result
     * @param array $data
     * @return void
     */
    public function setSwishIframeData(array $data): void
    {
        $orderId = $data['orderid'];
        phMsetArr(PayNPlay::PNPSWISH_REDIS_KEY . $orderId, $data);
    }

    /**
     * @param string $orderId
     * @return array
     */
    public function getSwishIframeData(string $orderId): array
    {
        $data = phMgetArr(PayNPlay::PNPSWISH_REDIS_KEY . $orderId);

        $data['successUrl'] = $this->getSuccessUrl();
        $data['failUrl'] = $this->getFailUrl();

        return $data;
    }

    /**
     * @return string
     */
    public function getSuccessUrl(): string
    {
        return phive()->getSiteUrl('', true, PayNPlay::SUCCESS_URL);
    }

    /**
     * @return string
     */
    public function getFailUrl(): string
    {
        return phive()->getSiteUrl('', true, PayNPlay::FAIL_URL);
    }

    /**
     * @param $transaction_id
     * @param $strategy
     * @param $step
     * @return void
     */
    public function onDepositSuccess($transaction_id, $strategy, $step)
    {
        $client = $this->processorFactory->create($strategy, $step);
        $client->onSuccess($transaction_id);
    }

    /**
     * @param string $orderId
     * @param array $payload
     * @return \Laraphive\Domain\User\DataTransferObjects\LoginKycResponse
     */
    public function pnpLogin(string $orderId, array $payload): LoginKycResponse
    {
        if(isLogged()){
            $u = cu();

            $this->logger->debug('paynplay:pnpLogin', [
                'loggedinUserId' => $u->getId()
            ]);

            $u->refresh();
            $userData['firstname'] = $u->getData('firstname');
            $userData['lastname'] = $u->getData('lastname');
            $userData['dob'] = $u->getData('dob');
            $userData['sex'] = $u->getData('sex');
            $userData['address'] = $u->getData('address');
            $userData['city'] = $u->getData('city');
            $userData['zipcode'] = $u->getData('zipcode');
            $userData['nid'] = $u->getData('nid');
        } else {
            $bankIdRequestId = $payload['bankIdRequestId'] ?? $_SESSION['bankid_request_id'];

            $this->logger->debug('paynplay:pnpLogin', [
                'payloadBankIdRequestId' => $payload['bankIdRequestId'],
                'sessionBankIdRequestId' => $_SESSION['bankid_request_id']
            ]);

            $userData = $this->getBankIDExtData($bankIdRequestId);

            $this->logger->debug('paynplay:pnpLogin', [
                'BankIDExtData' => $userData
            ]);
        }

        $nid = $userData['nid'];
        $amount = $payload['amount'];
        $currency = $payload['currency'];
        $userId = (int) $payload['userId'] ?? 0;

        $data = new LoginKycData(
            new LoginCommonData(true, '', '', 'mobile', '', '', '', '', $orderId, '', '', '', remIp()),
            $orderId,
            $nid,
            $userData['firstname'],
            $userData['lastname'],
            $userData['dob'],
            $userData['sex'],
            $userData['address'],
            $userData['city'],
            $userData['zipcode'],
            'Sweden',
            $amount,
            $currency,
            remip(),
            '',
            $userId
        );


        return phive('DBUserHandler')->loginKYC($data);
    }

    /**
     * @param $request
     * @return string|void
     */
    public function loginUserAfterRedirect($request)
    {
        $transaction_id = $request['transaction_id'] ?? '';
        $data = $this->getTransactionDataFromRedis($transaction_id);

        if (empty($data) || empty($data['status'])) {
            $this->logger->error('PayNPlay login: Missing data for transaction_id: ' . $transaction_id, $data);

            return $this->jsonFail('missing_data');
        } elseif ($data['userid']) {
            $this->logger->debug('PayNPlay login: Redirect Success', [
                'transaction_id' => $transaction_id,
                'data' => $data,
            ]);

            $request = LoginCommonData::fromArray([
                'is_API' => false,
                'action' => 'uname-pwd-login',
                'pnp' => "login-$transaction_id",
                'fingerprint' => $_SERVER['HTTP_X_DEVICE_FP'],
                'login_method' => 'paynplay',
                'ip' => $_SESSION['rstep1']['pnp_ip'] ?? remIp(),
            ]);

            $user = phive('DBUserHandler')->loginCommon($request, $data['userid'], null, false, false, true);
            if (! ($user instanceof DBUser)) {
                $login_error = $user;
                $this->logger->error('PayNPlay login: Redirect Success', [
                    'transaction_id' => $transaction_id,
                    'data' => $data,
                ]);

                return $this->jsonFail($login_error);
            }

            $this->deleteTransactionDataFromRedis($transaction_id);
            $this->updateBonusCode($data, $transaction_id);
            $showWelcomeActivationPopup = phive('Bonuses')->showWelcomeActivationPopup($user->userId);

            $this->logger->info('PayNPlay successful login', [
                'transaction_id' => $transaction_id,
                'data' => $data,
            ]);

            return $this->jsonSuccess([
                'userId' => $user->userId,
                'showWelcomeActivationPopup' => $showWelcomeActivationPopup,
                'myProfileLink' => phive('UserHandler')->getUserAccountUrl('my-profile'),
            ]);
        }
    }

    /**
     * @param string $transactionId
     * @param array $redisData
     * @return void
     */
    public function setTransactionDataToRedis(string $transactionId, array $redisData): void
    {
        phMsetArr(PayNPlay::REDIS_KEY . $transactionId, $redisData);
    }

    /**
     * @param $transaction_id
     * @return array
     */
    public function getTransactionDataFromRedis($transaction_id): array
    {
        $redis_key = PayNPlay::REDIS_KEY . $transaction_id;
        $redis_data = phMgetArr($redis_key) ?? [];

        return $redis_data;
    }

    /**
     * @param $transaction_id
     * @return void
     */
    public function deleteTransactionDataFromRedis($transaction_id)
    {
        $redis_key = PayNPlay::REDIS_KEY . $transaction_id;
        phMdel($redis_key);
    }

    /**
     * @param string $bonusCode
     * @return void
     */
    public function setBonusDataToRedis(string $bonusCode):void {
        phMsetArr(PayNPlay::REDIS_KEY_BONUS_CODE.remIp(), $bonusCode);
    }

    /**
     * @param string $ip
     * @return string
     */
    public function getBonusDataFromRedis(string $ip): string
    {
        $redis_key = PayNPlay::REDIS_KEY_BONUS_CODE.$ip;
        return phMgetArr($redis_key) ?? "";
    }

    /**
     * @param string $ip
     * @return void
     */
    public function deleteBonusDataFromRedis(string $ip)
    {
        $redis_key = PayNPlay::REDIS_KEY_BONUS_CODE.$ip;
        phMdel($redis_key);
    }

    /**
     * Get the response in success redirect and update the bonus_code
     * @param array $data
     * @param string $transaction_id
     * @return void
     */
    public function updateBonusCode(array $data = [], string $transaction_id = "")
    {
        if ($data['userid'] && $transaction_id && $this->getBonusDataFromRedis($data['ip'])) {
            $uid = $data['userid'];
            $bonusCode = $this->getBonusDataFromRedis($data['ip']);

            $findQuery = "SELECT u.id, u.bonus_code
                            FROM users u
                                LEFT JOIN deposits d ON d.user_id = u.id
                            WHERE u.id = '{$uid}'
                              AND (u.bonus_code IS NULL OR u.bonus_code = '')
                              AND d.mts_id = '{$transaction_id}'";

            $hasData = phive('SQL')->sh($uid)->loadArray($findQuery);

            if ($hasData) {
                // Update Master
                phive('SQL')->updateArray('users', [
                    'bonus_code' => $bonusCode
                ], ['id' => $uid]);

                // Update Shared
                phive('SQL')->sh($uid)->updateArray('users', [
                    'bonus_code' => $bonusCode
                ], ['id' => $uid]);

                $this->deleteBonusDataFromRedis($data['ip']);
            }
        }
    }

    public function baseRedirects()
    {
        switch (phive('Pager')->getPath()) {
            case '/cashier/deposit/':
            case '/mobile/cashier/deposit/':
            case '/cashier/withdraw/':
            case '/mobile/cashier/withdraw/':
            case '/mobile/register/':
            case '/mobile/login/':
                phive('Redirect')->to('/', cLang(), false, '302 Found', []);
                // no break
            default:
        }
    }

    /**
     * @return void
     */
    public function loadJs()
    {
        loadJs('/phive/modules/PayNPlay/js/pay-n-play.js');
    }

    /**
     * @return void
     */
    public function loadCss()
    {
        loadCss("/diamondbet/css/" . brandedCss() . "pay-n-play.css");
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return isPNP();
    }

    /**
     * @param $base_url
     * @param string $uri
     * @return string
     */
    private function getMtsUrl($base_url, string $uri): string
    {
        $base_url = substr($base_url, 0, strrpos($base_url, '/api/'));
        $base_url = $base_url . '/api/' . PayNPlay::MTS_API_VERSION . '/';

        return $base_url . $uri;
    }

    /**
     * @param $request
     * @param $user
     * @return string
     */
    public function updateUserInfo($request, $user): string
    {
        // keeps only allowed fields
        $allowed_field = ['email', 'mobile', 'nationality', 'country_prefix'];
        $allowed_fields_keys = array_flip($allowed_field);

        $request['toupdate'] =  array_intersect_key($request['toupdate'], $allowed_fields_keys);

        if (! empty($request['toupdate'])) {
            if (! empty($user->data['country'])) {
                $request['toupdate']['country'] = $user->data['country'];
            }

            $email_ref = $_REQUEST['toupdate']['email'];
            $skip_email_check = false;
            if ($user->data['email'] === $request['toupdate']['email']) {
                unset($request['toupdate']['email']);
                $skip_email_check = true;
            }

            [$errors, $postedFieldsKeys] = RegistrationHtml::validateStep1FieldsV2($request['toupdate'], $user->data['country']);

            if ($errors['email'] && $skip_email_check) {
                unset($errors['email']);
            }

            // using step1 validation where we have checkbox, but it's not needed in pnp
            unset($errors['privacy'], $errors['conditions'], $errors['password']);

            if (! empty($errors)) {
                return json_encode(
                    logToActionsAndReturn(RegistrationHtml::failureResponse($errors))
                );
            }

            $request['toupdate']['email'] = $email_ref;
            // TODO
            // should we update username also based on email or keep the same auto generated one
            if (isset($request['toupdate']['email'])) {
                if (filter_var($request['toupdate']['email'], FILTER_VALIDATE_EMAIL)) {
                    $request['toupdate']['username'] = $request['toupdate']['email'];
                }
            }

            $request['toupdate']['mobile'] = $request['toupdate']['country_prefix'] . $request['toupdate']['mobile'];
            $request['toupdate']['calling_code'] = $request['toupdate']['country_prefix'];

            unset($request['toupdate']['country'],
                $request['toupdate']['country_prefix']);

            $newContactInfo = phive('DBUserHandler')->updateContactInformation($_SESSION['mg_id'], $request['toupdate']);
            if ($newContactInfo->hasErrors()) {
                $errors = logToActionsAndReturn(
                    $newContactInfo->getErrors()
                );

                $firstError = $errors[0];

                return t($firstError);
            }
            if (isset($request['send-opt'])) {
                phive('UserHandler')->sendEmailCode();
                phive('UserHandler')->sendSmsCode();
            }
            unset($request['toupdate']);

            return json_encode(RegistrationHtml::successResponse(t('contact.details.updated.successfully')));
        }

        return "";
    }

    /**
     * @param $request
     * @param $user
     * @return string
     */
    public function validateCode($request, $user): string
    {
        if (! empty($request['email_code'])) {
            $errors = [];

            if ($request['email_code'] == $user->getSetting('email_code') && ! empty($user->getSetting('email_code'))) {
                $user->setSetting('email_code_verified', 'yes');
                $user->deleteSetting('registration_in_progress');
                //$user->setAttribute('verified_email', '1');
                unset($_SESSION['email_code_shown']);
                $user->verify();
            } elseif ($request['email_code'] == $user->getSetting('sms_code') && ! empty($user->getSetting('sms_code'))) {
                $user->setSetting('sms_code_verified', 'yes');
                $user->setSetting('email_code_verified', 'yes');
                $user->deleteSetting('registration_in_progress');
                $user->setAttr('verified_phone', 1);
                unset($_SESSION['sms_code_shown']);
                $user->verify();
            } else {
                $errors = ['email_code' => 'wrong.email.code'];

                return json_encode(["success" => false, "messages" => $errors]);
            }

            phive('Cashier/Fr')->checkForSuspiciousEmail($user);

            return json_encode(["success" => true, "messages" => $errors]);
        }

        return "";
    }

    /**
     * @return array
     */
    public function getValidCountries(): array
    {
        return $this->getSetting('countries');
    }

    /**
     * @return array
     */
    public function getCountryCurrencies(): array
    {
        return $this->getSetting('country_currency');
    }

    /**
     * Generate email using specific pattern
     *
     * @param $transactionId
     * @return string
     * @throws Exception
     */
    public function generateEmail(string $transactionId): string
    {
        $random = md5(random_int(10000000, 99999999));
        $email = "pnp.$transactionId@$random.pnp";

        return $email;
    }

    /**
     * Retrieves users IP based on a transaction ID
     * Fallbacks to a remip();
     *
     * @param string $transactionId
     * @return string
     */
    public function getIp(string $transactionId): string
    {
        $data = $this->getTransactionDataFromRedis($transactionId);

        return $data['ip'] ?? remIp();
    }

    /**
     * @param string $tag
     * @param array $data
     * @param string $logLevel
     * @return void
     */
    public function log(string $tag, array $data, string $logLevel = 'info'): void
    {
        switch ($logLevel) {
            case 'debug':
                $this->logger->debug($tag, $data);

                break;
            case 'info':
                $this->logger->info($tag, $data);

                break;
            case 'error':
                $this->logger->error($tag, $data);

                break;
        }
    }

    /**
     * Used to format person ID received from 3rd party services (ex: Trustly)
     *
     * @param string $person_id
     * @return false|string
     */
    public function formatPersonId(string $person_id)
    {
        $match_countries = implode('|', phive('PayNPlay')->getValidCountries());

        if (preg_match("/^($match_countries)\d{12}$/", $person_id)) {
            return substr($person_id, 2);
        }

        return $person_id;
    }
}
