<?php

require_once __DIR__ . '/../../Validation/GamStopValidation.php';

class GamStopSingleV2
{

    private $hostUrl;

    private $apiKey;

    private $timeout;


    /**
     * GamStopSingleV2 constructor.
     * @param string $hostUrl
     * @param string $apiKey
     * @param int $timeout
     */
    public function __construct(string $hostUrl, string $apiKey, int $timeout)
    {
        $this->setHostUrl($hostUrl);
        $this->setApiKey($apiKey);
        $this->setTimeout($timeout);
    }

    /**
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function rootPost(array $params)
    {
        $this->validateParams($params);

        list($body, $status_code, $header) = phive()->post($this->getHostUrl(), http_build_query($params), 'application/x-www-form-urlencoded', ["X-API-Key: " . $this->getApiKey()], 'gamstop', 'POST', $this->getTimeout(), [], null, true);

        if ($status_code !== 200) {
            throw new Exception("Invalid parameters");
        }

        $header = phive()->convertKeysToLower($header);
        return [
            "statusCode" => $status_code,
            "X-Exclusion" => $header["x-exclusion"],
            "X-Unique-Id" => $header["x-unique-id"]
        ];
    }

    /**
     * @param array $params
     * @throws Exception
     */
    private function validateParams(array $params)
    {
        $validationClass = new GamStopValidation();
        $validationClass->validateSingle($params['firstName'], $params['lastName'], $params['dateOfBirth'], $params['email'], $params['postcode'], $params['mobile'], $params['X-Trace-Id']);
    }

    /**
     * @return mixed
     */
    public function getHostUrl()
    {
        return $this->hostUrl;
    }

    /**
     * @param mixed $hostUrl
     */
    public function setHostUrl($hostUrl)
    {
        $this->hostUrl = $hostUrl;
    }

    /**
     * @return mixed
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param mixed $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return mixed
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param mixed $timeout
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
    }

}