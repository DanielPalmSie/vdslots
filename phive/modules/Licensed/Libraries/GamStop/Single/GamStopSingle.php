<?php


class GamStopSingle
{
    CONST V_1 = 'v1';

    CONST V_2 = 'v2';

    private $version;

    private $hostUrl;

    private $apiKey;

    private $params = [];

    private $allowedVersions = [self::V_1, self::V_2];

    private $timeout;


    /**
     * GamStop constructor.
     * @param string $version
     * @param string $hostUrl
     * @param string $apiKey
     * @param int $timeout
     * @throws Exception
     */
    public function __construct(string $version, string $hostUrl, string $apiKey, int $timeout)
    {
        $this->setVersion($version);
        $this->setHostUrl($hostUrl);
        $this->setApiKey($apiKey);
        $this->setTimeout($timeout);
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return string
     */
    public function getHostUrl(): string
    {
        return $this->hostUrl;
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }


    /**
     * @param string $version
     * @throws Exception
     */
    public function setVersion(string $version)
    {
        if (!in_array($version, $this->allowedVersions)) {
            throw new Exception("Invalid GamStop version provided");
        }
        $this->version = $version;
    }

    /**
     * @param string $hostUrl
     */
    public function setHostUrl(string $hostUrl)
    {
        $this->hostUrl = $hostUrl;
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey(string $apiKey)
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


    /**
     * @param array $ud
     * @param $uuid
     */
    public function setParams(array $ud, $uuid)
    {
        $params = [
            'v1' => [
                'first_name' => $ud['firstname'],   // string | First name of person, only 20 characters are significant
                'last_name' => $ud['lastname'],     // string | Last name of person, only 20 characters are significant
                'date_of_birth' => $ud['dob'],      // string | Date of birth in ISO format (yyyy-mm-dd)
                'email' => $ud['email'],            // string | Email address
                'postcode' => $ud['zipcode'],       // string | Postcode - spaces not significant
                'x_trace_id' => $uuid     // string | A unique request ID, GUID, or a trace ID
            ],
            'v2' => [
                'firstName' => $ud['firstname'],   // string | First name of person, only 20 characters are significant
                'lastName' => $ud['lastname'],     // string | Last name of person, only 20 characters are significant
                'dateOfBirth' => $ud['dob'],      // string | Date of birth in ISO format (yyyy-mm-dd)
                'email' => $ud['email'],            // string | Email address
                'postcode' => $ud['zipcode'],       // string | Postcode - spaces not significant
                'mobile' => $ud['mobile'],          // string | Mobile number
                'X-Trace-Id' => $uuid     // string | A unique request ID, GUID, or a trace ID
            ]
        ];

        $this->params = $params[$this->getVersion()];
    }


    /**
     * @return \Swagger\Client\Api\DefaultApi
     */
    private function getApiV1Instance(): \Swagger\Client\Api\DefaultApi
    {
        require_once(__DIR__ . '/V1/autoload.php');
        $config = Swagger\Client\Configuration::getDefaultConfiguration()
            ->setApiKey('X-API-Key', $this->getApiKey())
            ->setHost($this->getHostUrl());
        $client = new Swagger\Client\ApiClient($config);
        return new Swagger\Client\Api\DefaultApi($client);
    }

    /**
     * @return GamStopSingleV2
     */
    private function getApiV2Instance(): GamStopSingleV2
    {
        require_once(__DIR__ . '/V2/GamStopSingleV2.php');
        return new GamStopSingleV2($this->getHostUrl(), $this->getApiKey(), $this->getTimeout());
    }


    /**
     * @return array
     * @throws Exception
     */
    public function execute(): array
    {
        if ($this->getVersion() === self::V_1) {
            $res = $this->getApiV1Instance()->rootPost($this->getParams());
            $res = phive()->convertKeysToLower($res);
            return $this->composeResponse($res["statuscode"], $res["httpheader"]["x-exclusion"], $res["httpheader"]["x-unique-id"]);
        } elseif ($this->getVersion() === self::V_2) {
            $instance = $this->getApiV2Instance();
            $res = $instance->rootPost($this->getParams());
            $res = phive()->convertKeysToLower($res, true);
            return $this->composeResponse($res["statuscode"], $res["x-exclusion"], $res["x-unique-id"]);
        }
    }

    /**
     * @param int $status_code
     * @param string $x_exclusion
     * @param string $x_unique_id
     * @return array
     */
    private function composeResponse(int $status_code, string $x_exclusion, string $x_unique_id): array
    {
        return [
            "statusCode" => $status_code,
            "X-Exclusion" => $x_exclusion,
            "X-Unique-Id" => $x_unique_id
        ];
    }

}