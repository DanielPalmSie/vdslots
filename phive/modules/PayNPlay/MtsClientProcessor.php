<?php

namespace PayNPlay;

use Videoslots\Mts\MtsClient;

/**
 *
 */
class MtsClientProcessor implements ProcessorInterface
{
    /**
     * @var \Videoslots\Mts\MtsClient
     */
    private MtsClient $mtsClient;

    private SwishAdapter $swishAdapter;

    /**
     * @var string
     */
    private string $supplier;

    /**
     * @var array
     */
    private array $payloadData;

    /**
     * @param \Videoslots\Mts\MtsClient $mtsClient
     * @param string $supplier
     */
    public function __construct(MtsClient $mtsClient, string $supplier)
    {
        $this->mtsClient = $mtsClient;
        $this->swishAdapter = new SwishAdapter();
        $this->supplier = $supplier;
    }

    /**
     * @param array $result
     * @return ProcessorResponse
     */
    private function createResponse(array $result): ProcessorResponse
    {
        if ($this->supplier == \Supplier::Swish) {
            return $this->swishAdapter->createResponse($result, $this->payloadData);
        }

        return new ProcessorResponse($result['data']['url'], $result['data']['orderid']);
    }

    /**
     * @param ProcessorPayload $payload
     * @return ProcessorResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    public function process(ProcessorPayload $payload): ProcessorResponse
    {
        $this->payloadData = $payload->getPayload();

        if($this->supplier == \Supplier::Swish) {
            if(!$this->payloadData['userId']){
                $pnpResponse = phive('PayNPlay')->pnpLogin(rand(), $this->payloadData);
                $this->payloadData['userId'] = (int) $pnpResponse->getUserId();
            }

            $result = $this->mtsClient->deposit($this->supplier, $this->payloadData);
        } else {
            $result = $this->mtsClient->deposit($this->supplier, $this->payloadData);
        }

        if ($result['success']) {
            return $this->createResponse($result);
        } else {
            throw new \Exception('MTS client connection failed');
        }
    }

    /**
     * @param string $transaction_id
     * @return void
     */
    public function onSuccess(string $transaction_id): void
    {
        phive('PayNPlay')->logger->debug('PayNPlay -> MTSClient: onSuccess', ['transaction_id' => $transaction_id]);
    }
}
