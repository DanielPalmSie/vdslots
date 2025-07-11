<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api/ExtModule.php';


use \GuzzleHttp\Client as GuzzleClient;
use \GuzzleHttp\Exception\ClientException as ClientException;
use \GuzzleHttp\Exception\ConnectException as ConnectException;

use IdScan\Exceptions\NotReadableImageException;
use IdScan\Interfaces\DocumentRequest;
use IdScan\IdScanDocument;
use IdScan\IdScanImage;
use IdScan\IdScanDocumentRequest;


class IdScan extends ExtModule
{
    const SAVE_DOCUMENT_RETRIES = 5;
    /**
     * @var GuzzleClient
     */
    private GuzzleClient $client;

    /**
     * @var string
     */
    private string $sdkURL;
    /**
     * @var string
     */
    private string $serviceURL;
    /**
     * @var string
     */
    private string $username;
    /**
     * @var string
     */
    private string $password;
    /**
     * @var string
     */
    private string $token;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var array
     */
    private array $remoteBrandWebhookURL;


    public function __construct(?GuzzleClient $client = null, $logger = null)
    {
        parent::__construct();

        $this->logger = $logger ?? phive('Logger')->getLogger('id-scan');
        $this->client = $client ?? new GuzzleClient();
    }

    /**
     * @param array|null $auth - idScan credentials
     * @param array|null $remoteBrandWebhookURL - remote brand webhook url
     * @return void
     */
    public function init(?array $auth = null, ?array $remoteBrandWebhookURL = null): void
    {
        // If $auth is null, we load the credentials from the config file
        if ($auth === null) {
            $auth = $this->getSetting('auth');
        }
        if ($auth['URL'] && $auth['USERNAME'] && $auth['PASSWORD'] && $auth['WEB_SDK_URL']) {
            $this->sdkURL = $auth['WEB_SDK_URL'];
            $this->serviceURL = $auth['URL'];
            $this->username = $auth['USERNAME'];
            $this->password = $auth['PASSWORD'];
        } else {
            $this->logger->critical('Missing credentials');
        }

        // If $remoteBrandWebhookURL is null, we load the credentials from the config file
        if ($remoteBrandWebhookURL === null) {
            $remoteBrandWebhookURL = $this->getSetting('remote_brand_webhook_url');
        }
        if ($remoteBrandWebhookURL) {
            $this->remoteBrandWebhookURL = $remoteBrandWebhookURL;
        } else {
            $this->logger->debug('Missing remote_brand_webhook_url');
        }
    }

    /**
     * @param string|null $area
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function generateToken(string $area = null): array
    {
        $data = [];

        if (!$area) {
            $area = 'scanning';
        }

        try {
            $res = $this->client->request('POST', $this->serviceURL . '/token', [
                    'form_params' => [
                        'UserName' => $this->username,
                        'Password' => $this->password,
                        'Area' => "$area",
                        'grant_type' => 'password'
                    ]
                ]
            );

            $res = json_decode($res->getBody()->getContents(), true);
            $data = $res;

        } catch (ClientException $e) {
            $response = $e->getResponse();
            $result = json_decode($response->getBody()->getContents(), true);
            $data = $result;
        } catch (ConnectException $e) {
            $response = $e->getMessage();
            $data['error'] = $response;
        }

        return $data;
    }

    /**
     * @param string $token
     * @return void
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Returns hashed_uuid from user_settings table
     *
     * @param $user
     * @return string
     */
    public function getHashedUuid($user):string {
        $hashed_uuid = $user->getSetting('hashed_uuid');

        //if hashed_uuid is not set, generate it and store it in the user settings
        if (!$hashed_uuid){
            $hashed_uuid = phive()->uuid();
            $user->setSetting('hashed_uuid', $hashed_uuid);
        }

        return $hashed_uuid;
    }

    /**
     * @param string $journeyId
     * @return array
     */
    public function getJourney(string $journeyId): array
    {
        return $this->getRequest($this->serviceURL . "/Journey/Get?journeyID=$journeyId");
    }

    /**
     * @param string $imageUrl
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getJourneyImage(string $imageUrl): array
    {
        return $this->getStream($imageUrl);
    }


    /**
     * @param string $url
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getRequest(string $url): array
    {
        $data = [];

        try {
            $res = $this->client->request('GET', $url, [
                'headers' => [
                    'Authorization' => $this->token
                ]
            ]);

            $res = json_decode($res->getBody()->getContents(), true);
            $data = $res;

        } catch (ClientException $e) {
            $response = $e->getResponse();
            $result = json_decode($response->getBody()->getContents(), true);
            $data = $result;
        } catch (ConnectException $e) {
            $response = $e->getMessage();
            $data['error'] = $response;
        }

        return $data;
    }

    /**
     * @param string $url
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getStream(string $url): array
    {
        $data = [];

        try {
            $res = $this->client->request('GET', $url, [
                ['stream' => true],

                'headers' => [
                    'Authorization' => $this->token
                ]
            ]);

            $body = $res->getBody()->getContents();
            $data['base64'] = $body;

        } catch (ClientException $e) {
            $response = $e->getResponse();
            $result = json_decode($response->getBody()->getContents(), true);
            $data = $result;
        } catch (ConnectException $e) {
            $response = $e->getMessage();
            $data['error'] = $response;
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getRemoteBrandWebhookURL(): array
    {
        return $this->remoteBrandWebhookURL;
    }


    /**
     * @param $request_data
     * @param $user
     * @param $rstep1
     * @return void
     */
    public function setStep2UserData($request_data, $user, $rstep1): void
    {
        $user_id = $user->getId();
        $hashed_uuid = $this->getHashedUuid($user);

        phive('DBUserHandler')->logAction($user_id, 'Saving registration Step2 Data for IDScan', 'IDScan');

        $ar = [];
        $ar['step1data'] = $rstep1;
        $ar['step2data'] = $request_data;
        $ar['uid'] = $user_id;
        $ar['hashed_uid'] = $hashed_uuid;

        phMsetArr($hashed_uuid, $ar);
    }

    /**
     * Sets additional temporary data to be saved later after verification
     *
     * @param string $key
     * @param array $data
     * @param $user
     * @return void
     */
    public function setTemporaryData(string $key, array $data, $user):void {
        $user_id = $user->getId();
        $hashed_uuid = $this->getHashedUuid($user);

        $ar = $this->getSavedUserData($hashed_uuid);
        $ar[$key] = $data;
        $ar['uid'] = $user_id;
        $ar['hashed_uid'] = $hashed_uuid;

        phMsetArr($hashed_uuid, $ar);
    }

    /**
     * @param string $key
     * @param $user
     * @return void
     */
    public function removeTemporaryData(string $key, $user){
        $user_id = $user->getId();
        $hashed_uuid = $this->getHashedUuid($user);

        $ar = $this->getSavedUserData($hashed_uuid);
        unset($ar[$key]);
        phMsetArr($hashed_uuid, $ar);
    }

    /**
     * @param string $hashed_uuid
     * @param string $status
     * @return void
     */
    public function setVerificationStatus(string $hashed_uuid, string $status): void
    {
        $ar = $this->getSavedUserData($hashed_uuid);
        $userId = $ar['uid'];
        $ar['status'] = $status;

        phive('DBUserHandler')->logAction($userId, 'IDScan verification status: ' . $status, 'IDScan');

        phMsetArr($hashed_uuid, $ar);
    }

    /**
     * Removed verification status
     *
     * @param string $hashed_uuid
     * @return void
     */
    public function resetVerification(string $hashed_uuid):void {
        $ar = $this->getSavedUserData($hashed_uuid);
        $userId = $ar['uid'];

        unset($ar['step1data']);
        unset($ar['step2data']);
        unset($ar['status']);

        phive('UserHandler')->logAction($userId, 'IDScan verification was resetted by new verification', 'IDScan');

        phMsetArr($hashed_uuid, $ar);
    }

    /**
     * Getting saved data from Redis
     * @param string $hashed_uuid
     * @return array|null
     */
    public function getSavedUserData(string $hashed_uuid): ?array
    {
        return phMgetArr($hashed_uuid);
    }


    /**
     * @param DocumentRequest $request
     * @param int             $retry_num
     *
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function saveDocuments(DocumentRequest $request, int $retry_num = self::SAVE_DOCUMENT_RETRIES): bool
    {
        $this->logger = phive('Logger')->getLogger('id-scan');
        $this->logger->info('Saving IDScan documents for journey ID: ' .  $request->getUid(), [$request->getJourneyID()]);
        phive('DBUserHandler')->logAction($request->getUid(), "Trying to save IDScan documents. Journey ID: ".$request->getJourneyID() , 'IDScan');

        try {
            $journeyImageUrl = $request->getJourneyImage();
            $journeyImage = $this->getJourneyImage($journeyImageUrl); //base64

            if(!$journeyImage['base64']){
                throw new NotReadableImageException($request->getJourneyImage());
            }

            $imageBase64 = $journeyImage['base64'];
            $image = new IdScanImage($imageBase64);

            $idScanDocument = new IdScanDocument($this->logger, phive('Dmapi'));
            if ($idScanDocument->saveDocuments($request, $image)) {
                $this->logger->info('IDScan documents saved successfully for journey ID: ' .  $request->getUid(), [$request->getJourneyID()]);
                phive('DBUserHandler')->logAction($request->getUid(), "IDScan documents saved successfully for journey ID: ".$request->getJourneyID() , 'IDScan');
                return true;
            }

        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $this->retryIfTheDocumentIsNotCreated($request, $retry_num);
        }

        return false;
    }

    /**
     * Resets user blocks after KYC checks
     *
     * @param string $hashed_uuid
     * @return void
     */
    public function resetRestrictions(string $hashed_uuid): void
    {
        $userData = $this->getSavedUserData($hashed_uuid);
        $userId = $userData['uid'];
        $user = cu($userId);
        $deposit_blocked = $userData['step2data']['deposit_blocked'];

        $user->deleteSetting('experian_block');
        $user->deleteSetting('idscan_block');
        unset($_SESSION['experian_msg']);

        if ($deposit_blocked) {
            //skip from deposit unblocking
        } else {
            //on registration and migration - removing deposit_block
            $user->resetDepositBlock();
        }
        if ($user->hasSetting('id_scan_failed')) {
            $user->depositBlock();
        }
    }


    /**
     * @return string
     */
    public function getSdkURL(): string
    {
        return $this->sdkURL;
    }

    /**
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param $retry_num
     * @param string $hashed_uuid
     * @param string $imageBase64
     * @param string $journeyId
     * @param $e
     * @return void
     */
    public function retryIfTheDocumentIsNotCreated(DocumentRequest $request, int $retry_num): void
    {
        if ($retry_num > 0) {
            $retry_num--;

            $requestAr = [$this->token, $request->getUid(), $request->getCountryCode(), $request->getJourneyID(), $request->getJourneyImage(),$request->getExpiryDate(), $request->getExpiryDateStatus(), $retry_num];

            $this->logger->log("Retrying to save document. Retry number: " . $retry_num, $requestAr);

            phive()->fire('IdScan', 'IdscanDocumentSaveError', $requestAr, $this->delay($retry_num),
                function () use ($request, $retry_num) {
                    $this->saveDocuments($request, $retry_num);
                }
            );
        }
    }

    public function onIdScanSaveDocument(string $token, string $userId, string $userCountry, string $journeyId, string $journeyImageUrl, string $expiryDate, string $expiryDateStatus): void
    {
        $this->setToken($token);

        $request = new IdScanDocumentRequest();
        $request->setUid($userId);
        $request->setCountryCode($userCountry);
        $request->setJourneyID($journeyId);
        $request->setJourneyImage($journeyImageUrl);
        $request->setExpiryDate($expiryDate);
        $request->setExpiryDateStatus($expiryDateStatus);

        if($request->verify()){
            $this->saveDocuments($request);
        } else {
            $this->logger->critical('Request data is incomplete', (array) $request);
        }


    }

    public function onIdScanDocumentSaveError(string $token, string $userId, string $userCountry, string $journeyId, string $journeyImageUrl,string $expiryDate, string $expiryDateStatus, $retry_num = 0)
    {
        // if retry number is 0, we need to log alert
        if ($retry_num == 0) {
            $this->logger->alert('IdScan document save MAX RETRIES', [
                'journeyImageURL' => $journeyImageUrl
            ]);
        } else {
            $this->logger->alert('IdScan document save try: '.$retry_num, [
                    'journeyImageURL' => $journeyImageUrl
            ]);

            $this->setToken($token);

            $request = new IdScanDocumentRequest();
            $request->setUid($userId);
            $request->setCountryCode($userCountry);
            $request->setJourneyID($journeyId);
            $request->setJourneyImage($journeyImageUrl);
            $request->setExpiryDate($expiryDate);
            $request->setExpiryDateStatus($expiryDateStatus);

            $this->saveDocuments($request, $retry_num);
        }
    }

    /**
     * @param int $retry_num
     * @return int
     */
    private function retries(int $retry_num): int
    {
        return (self::SAVE_DOCUMENT_RETRIES - $retry_num);
    }

    /**
     *  5 ^ 3 = 125 seconds, 5 ^ 2 = 25 seconds, 5 ^ 1 = 5 seconds, 5 ^ 0 = 1 second
     *
     * @param int $retry_num
     * @return int milliseconds
     */
    private function delay(int $retry_num): int
    {
        return (int)pow(5, $this->retries($retry_num)) * 1000;
    }

}
