<?php

class KycEventHandler
{
    private IdScan $idScan;
    private Logger $logger;
    private Dmapi $dmapi;

    public function __construct()
    {
        $this->idScan = new IdScan();
        $this->idScan->init();
        $this->logger = phive('Logger');
        $this->dmapi = phive('Dmapi');
    }


    /**
     * @param int $user_id
     * @param string $url
     * @param string $json
     * @return void
     */
    public function onDmapiCreateEmptyDocument(int $user_id, string $url, string $json):void {
        $this->logger->getLogger('dmapi')->info("Event: onDmapiCreateEmptyDocument", [$user_id, $url, $json]);
        $this->dmapi->createEmptyDocumentAsync($user_id, $url, $json);
    }

    /**
     * @param int $user_id
     * @param string $url
     * @param string $json
     * @param int $retry
     * @return void
     */
    public function onDmapiCreateEmptyDocumentError(int $user_id, string $url, string $json, int $retry):void {
        $this->logger->getLogger('dmapi')->info("Event: onDmapiCreateEmptyDocumentError", [$user_id, $url, $json, $retry]);
        $this->dmapi->createEmptyDocumentAsync($user_id, $url, $json, $retry);
    }

    /**
     * @param string $token
     * @param string $userId
     * @param string $userCountry
     * @param string $journeyId
     * @param string $journeyImageUrl
     *
     * @return void
     */
    public function onIdScanSaveDocument(string $token, string $userId, string $userCountry, string $journeyId, string $journeyImageUrl, string $expiryDate, string $expiryDateStatus)
    {
        $this->logger->getLogger('id-scan')->info("Event: onIdScanSaveDocument. JourneyID: ".$journeyId);

        $this->idScan->onIdScanSaveDocument($token, $userId, $userCountry, $journeyId, $journeyImageUrl, $expiryDate, $expiryDateStatus);
    }


    /**
     * @param string $token
     * @param string $userId
     * @param string $userCountry
     * @param string $journeyId
     * @param string $journeyImageUrl
     * @param int    $retry_num
     *
     * @return void
     */
    public function onIdScanDocumentSaveError(string $token, string $userId, string $userCountry, string $journeyId, string $journeyImageUrl,string $expiryDate, string $expiryDateStatus, $retry_num = 0)
    {
        $this->logger->getLogger('id-scan')->info("Event: onIdScanDocumentSaveError. JourneyID: ".$journeyId);
        $this->idScan->onIdScanDocumentSaveError($token, $userId, $userCountry, $journeyId, $journeyImageUrl, $expiryDate, $expiryDateStatus, $retry_num);
    }


}
