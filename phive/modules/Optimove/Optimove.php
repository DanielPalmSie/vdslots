<?php
require_once __DIR__ . '/../../api/ExtModule.php';

class Optimove extends ExtModule
{
    private bool $isEnabled;
    private string $apiKey;
    private string $tenantID;
    private string $tenantToken;
    public string $optimoveBaseUrl;
    private int $optimoveBrandId;
    private array $optimoveBrandMap;

    public int $timeout = 30;
    private array $headers = [];
    private string $defaultJsonType;
    public string $route;
    public object $brand;
    private array $privacyStrings;
    private bool $addLogs = false;

    public function __construct(){
        parent::__construct();
        $this->brand = phive('BrandedConfig');
        $settings = $this->getSetting('optimove');
        $this->isEnabled = (bool)($settings['ENABLED'] ?? false);
        $this->addLogs = (bool)($settings['IS_LOG_ENABLED'] ?? false);
        $this->apiKey = $settings['API_KEY'] ?? '';
        $this->tenantID = $settings['TENANT_ID'] ?? '';
        $this->tenantToken = $settings['TENANT_TOKEN'] ?? '';
        $this->optimoveBaseUrl = $settings['API_BASE_URL'] ?? '';
        $this->optimoveBrandMap = $this->getSetting('optimove_brand_ids') ?? [];
        $this->privacyStrings = $this->getSetting('optimove_privacy_strings') ?? [];
        $this->optimoveBrandId = $this->optimoveBrandMap[$this->brand->getBrand()] ?? 0;


        $headers = [
            'X-API-KEY: '. $this->getOptimoveApiKey(),
            'accept: application/json'
        ];

        $this->headers = $headers;
        $this->defaultJsonType = 'application/json';
    }

    /**
     * Function to check if Optimove module is enalbed at Optimove.config.php file
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    /**
     * Function to check if string is privacy string managed by Optimove
     * @param string $setting
     * @return bool
     */
    public function isPrivacySetting(string $setting = ''): bool
    {
        return in_array($setting, $this->privacyStrings);
    }

    /**
     * get brand id of the environment based on optimove account
     * @return int
     */
    public function getBrandId(): int
    {
        return $this->optimoveBrandId;
    }

    /**
     * To add web sdk simple call this function
     * More info on sdk integration
     * https://developer.optimove.com/docs/web-sdk-integration-guide-v3
     * @return string
     */
    public function addWebSDK()
    {
        $queryString = http_build_query([
            'tenant_id' => $this->getTenantId(),
            'tenant_token' => $this->getTenantToken()
        ]);
        return '<script async src="https://sdk.optimove.net/websdk/?'.$queryString.'"></script>';
    }

    /**
     * get optimove api key
     * @return string
     */
    public function getOptimoveApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * get optimove api base url
     * @return string
     */
    public function getOptimoveBaseUrl(): string
    {
        return $this->optimoveBaseUrl;
    }

    /**
     * get optimove tenant id
     * @return int
     */
    public function getTenantId(): int
    {
        return $this->tenantID;
    }

    /**
     * get Optimove tenant token
     * @return string
     */
    public function getTenantToken(): string
    {
        return $this->tenantToken;
    }

    public function getRoute($route = '')
    {
        $routes = [
            'getLastDataUpdate' => 'current/general/GetLastDataUpdate',
            'info' => 'Apikey/Info',
            'getCustomerAttributeList' => 'Model/GetCustomerAttributeList',
            'getUnsubscribers' => 'Optimail/GetUnsubscribers',
            'addUnsubscribers' => 'Optimail/AddUnsubscribers',
            'deleteUnsubscribers' => 'Optimail/DeleteUnsubscribers',
        ];

        return $routes[$route] ?? '';
    }

    /**
     * Returns the date of the most recently available customer data.
     * @return array
     */
    public function getLastDataUpdate(): array
    {
        $endPoint = $this->getRoute('getLastDataUpdate');
        $this->route = $this->getOptimoveBaseUrl() . $endPoint;
        $body = phive()->get($this->route, $this->timeout, $this->headers);
        return $this->generateResponse($body);
    }

    /**
     * Returns information about the key such associated channels and attributes
     * Info related scope of api key
     * @return array
     */
    public function getApiKeyInfo(): array
    {
        $endPoint = $this->getRoute('info');
        $this->route = $this->getOptimoveBaseUrl() . $endPoint;
        $body = phive()->get($this->route, $this->timeout, $this->headers);
        return $this->generateResponse($body);
    }

    /**
     * Get all attributes for a Specific customer.
     * @return array
     */
    public function getCustomerAttributeList(): array
    {
        $endPoint = $this->getRoute('getCustomerAttributeList');
        $this->route = $this->getOptimoveBaseUrl() . $endPoint;
        $body = phive()->get($this->route, $this->timeout, $this->headers);
        return $this->generateResponse($body);
    }

    /**
     * Allows to retrieve email addresses that are a part of a global unsubscribe list of a particular brand.
     * @return array
     */
    public function getUnsubscribers(): array
    {
        $queryString = http_build_query([
            'BrandId' => $this->getBrandId(),
            //'StartDate' => '2020-03-16',
            //'EndDate' => '2020-03-17'
        ]);
        $endPoint = $this->getRoute('getUnsubscribers');
        $this->route = $this->getOptimoveBaseUrl() . $endPoint . '?' .$queryString;
        $body = phive()->get($this->route, $this->timeout, $this->headers);
        return $this->generateResponse($body);
    }

    /**
     * Adds one or more email addresses to a global unsubscribe list of a particular brand.
     * @param array $emails
     * @return array|string[]
     */
    public function addUnsubscribers(array $emails = []): array
    {
        if (empty($emails)) {
            return [
                'status' => 'error',
                'message' => 'Email is required'
            ];
        }

        if (!is_array($emails)) {
            return [
                'status' => 'error',
                'message' => 'Email should be array type'
            ];
        }

        $data = [
            'BrandId' => $this->getBrandId(),
            'EmailAddresses' => $emails
        ];
        $endPoint = $this->getRoute('addUnsubscribers');
        $this->route = $this->getOptimoveBaseUrl() . $endPoint;

        $body = phive()->post($this->route, $data, $this->defaultJsonType, $this->headers);

        return $this->generateResponse($body, $data);
    }

    /**
     * Deletes one email addresses from the global unsubscribe list of a particular brand.
     * @param string $emails
     * @return array|string[]
     */
    public function deleteUnsubscribers(string $emails = ''): array
    {
        if (empty($emails)) {
            return [
                'status' => 'error',
                'message' => 'Email is required'
            ];
        }

        if (is_array($emails)) {
            return [
                'status' => 'error',
                'message' => 'Single email is required'
            ];
        }

        $data = [
            'BrandId' => $this->getBrandId(),
            'EmailAddress' => $emails
        ];
        $endPoint = $this->getRoute('deleteUnsubscribers');
        $this->route = $this->getOptimoveBaseUrl() . $endPoint;

        $body = phive()->post($this->route, $data, $this->defaultJsonType, $this->headers);

        return $this->generateResponse($body, $data);
    }

    /**
     * Function to check the user privacy setting and call the add or delete subscriber function.
     * @param string $value
     * @return string[]|void
     */
    public function processPrivacySettings(string $value = '')
    {
        $currentUser = cu();
        if (empty($currentUser)) {
            $currentUser = cuRegistration();
        }
        $email = $currentUser->data['email'];
        if ($value) {
            $this->deleteUnsubscribers($email);
        } else {
            $this->addUnsubscribers([$email]);
        }
    }

    /**
     * Generating response from api
     * There is no consistance of response so added common function to return array
     * [
     *      'status' => '', // success|error
     *      'message' => '', // depends
     *      'errors => '', // depends
     *      'data' => [] // depends
     * ]
     * @param string $body
     * @param array $data;
     * @return array
     */
    private function generateResponse(string $body, array $data = []): array
    {
        $response = json_decode($body, true);
        if (!is_array($response)) {
            $response = json_decode($response, true);
        }

        $error = false;
        $return['status'] = 'success';
        if (isset($response['errors'])) {
            $error = true;
            $return['status'] = 'error';

            if (is_array($response['errors'])) {
                $return['message'] = $response['title'] ?? $response['errors'][0];
                $return['errors'] = $response['errors'];
            }
        }

        if (isset($response['Errors'])) {
            $error = true;
            $return['status'] = 'error';

            if (is_array($response['Errors'])) {
                $return['message'] = $response['title'] ?? $response['Errors'][0];
                $return['errors'] = $response['Errors'];
            }
        }

        if (isset($response['Error']) || $response['Error'] === null) {
            $error = true;
            $return['status'] = 'error';

            if (is_array($response['Error'])) {
                $return['message'] = $response['title'] ?? $response['Error'][0];
            } else {
                $return['message'] = $response['title'] ?? 'Invalid request';
            }
            $return['errors'] = $response['Error'] ?? 'Invalid request';
        }

        if ($error) {
            $return['data'] = [];
        } else {
            $return['message'] = $return['message'] ?? 'Call made successfully';
            $return['data'] = $response ?? [];
        }

        $this->logElastic($return, $data);
        return $return;
    }

    /**
     * Log error from api in elastic.videoslots.com
     * @param array $response
     * @param array $data
     * @return void
     */
    private function logElastic(array $response = [], array $data = [])
    {
        if (!$this->addLogs) {
            return false;
        }
        /*
        |--------------------------------------------------------------------------
        | Send log to elastic if there is api error.
        |--------------------------------------------------------------------------
        */
        if ($response && $response['status'] === 'error') {
            $response['post'] = [
                'route' => $this->route,
                'data' => $data
            ];

            phive('Logger')
                ->getLogger('web_logs')
                ->error(
                    "Error response in Optimove subscribe api ". date('Y-m-d H:i:s'),
                    $response
                );
        }
    }
}
