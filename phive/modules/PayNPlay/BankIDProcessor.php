<?php

namespace PayNPlay;

/**
 * BankID Processor for PNP
 * Processor starts transaction with external supplier and saves External User Data (extData) to PNP flow
 *
 */
class BankIDProcessor implements ProcessorInterface
{
    /**
     * @var string
     */
    private string $strategy;
    /**
     * @var
     */
    private $step;
    /**
     * @var \BankId
     */
    private $extv;

    private array $payload;

    /**
     * @param $strategy
     * @param $step
     */
    public function __construct($strategy, $step)
    {
        $this->strategy = $strategy;
        $this->step = $step;
    }

    /**
     * @param $supplier
     * @return void
     */
    public function setVerificationSupplier($supplier)
    {
        $this->extv = $supplier;
    }

    /**
     * @param string $bankIdRequestId
     * @return void
     */
    private function saveExternalData(string $bankIdRequestId): void
    {
        $res = $this->extv->getExtvIdResult($bankIdRequestId, $this);

        $nid = $res['result']['nid'];
        $extData = $this->extv->getExtData('SE', $nid, $bankIdRequestId);
        $extData['nid'] = $nid;

        //@todo - after deployment of CANV-4962 we need to obfuscate extData
        phive('PayNPlay')->logger->debug('BankIDProcessor: saveExternalData', [$bankIdRequestId, $res, $extData]);

        phive('PayNPlay')->setBankIDExtData($bankIdRequestId, $extData);
    }

    /**
     * @param mixed $payload
     * @return ProcessorResponse
     * @throws \Exception
     */
    public function process(ProcessorPayload $payload): ProcessorResponse
    {
        $this->payload = $payload->getPayload();

        $transactionId = $this->generateNewTransactionId();
        $url_params = http_build_query([
            'transaction_id' => $transactionId,
            'strategy' => $this->strategy,
            'step' => $this->step,
        ]);
        $this->extv->setSuccessUrl($this->payload['successUrl'] . '?' . $url_params);
        $this->extv->setFailUrl($this->payload['failUrl'] . '?' . $url_params);
        $this->extv->force_polling = true; // we are not using polling, is to avoid webhook calling
        $res = $this->extv->extvIdStart($this->payload['country']);

        if (! $res['success']) {
            throw new \Exception("Error starting BankId Verification");
        }

        phive('PayNPlay')->logger->debug('BankIDProcessor: process', $res);

        $bankIdRequestId = $res['result']['id'];

        //saving payload and bankIdRequestId to be used later
        $bankIdTransactionData = $this->payload;
        $bankIdTransactionData['bankIdRequestId'] = $bankIdRequestId;

        phive('PayNPlay')->setBankIDTransactionData($transactionId, $bankIdTransactionData);
        $_SESSION['bankid_request_id'] = $bankIdRequestId;

        return new ProcessorResponse($res['result']['redirect_url'], $transactionId);
    }

    /**
     * @param string $transactionId
     * @return void
     */
    public function onSuccess(string $transactionId): void
    {
        $pnp = phive('PayNPlay');
        $bankIdTransactionData = $pnp->getBankIDTransactionData($transactionId);

        $bankIdRequestId = $bankIdTransactionData['bankIdRequestId'];

        $this->saveExternalData($bankIdRequestId);

        $pnpResponse = $pnp->pnpLogin($transactionId, $bankIdTransactionData);
        $message = $pnpResponse->getMessage();
        $userId = $pnpResponse->getUserId();

        if($userId){
            phive('PayNPlay')->setBankIDExtData($bankIdRequestId, ['userId' => $userId]);
        }

        phive('PayNPlay')->logger->debug('BankIDProcessor: onSuccess', [
            'bankid_transaction_id' => $bankIdRequestId,
            'pnp_response' => $message,
            'pnp_user_id' => $userId,
        ]);
    }

    /**
     * Generates a 16 characters unique identifier suitable for URL
     * Always with prefix "bid"
     *
     * @return string
     */
    private function generateNewTransactionId(): string
    {
        return 'bid'.substr(md5(uniqid('', true) . mt_rand()), 0, 13);
    }
}
