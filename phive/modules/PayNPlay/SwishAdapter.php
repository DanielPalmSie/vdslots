<?php

namespace PayNPlay;

class SwishAdapter
{
    /**
     *
     */
    public function __construct()
    {
    }

    /**
     * @param array $result
     * @return void
     */
    private function setPageData(array $result): void
    {
        phive('PayNPlay')->setSwishIframeData($result['data']);
    }

    /**
     * @param array $result
     * @param array $payload
     * @return ProcessorResponse
     */
    public function createResponse(array $result, array $payload): ProcessorResponse
    {
        $orderId = $result['data']['orderid'];

        $loginResponse = phive('PayNPlay')->pnpLogin($orderId, $payload);
        $result['data']['amount'] = $payload['amount'];
        $result['data']['status'] = $loginResponse->getStatus();

        if($limit = $loginResponse->getLimit()){
            $result['data']['limit'] = $limit;
        }

        $this->setPageData($result);

        $url = phive()->getSiteUrl('', true, "payment-swish/?transaction_id=$orderId");

        return new ProcessorResponse($url, $orderId);
    }

}
