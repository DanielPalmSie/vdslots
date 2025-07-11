<?php

namespace PayNPlay;

/**
 *  For PNP integrations we are always expecting two parameters: URL to display and orderId
 */
class ProcessorResponse {
    /**
     * @var string
     */
    private string $orderId;
    /**
     * @var string
     */
    private string $url;

    /**
     * @param string $orderId
     * @param string $url
     */
    public function __construct(string $url, string $orderId) {
        $this->orderId = $orderId;
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->orderId;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

}
