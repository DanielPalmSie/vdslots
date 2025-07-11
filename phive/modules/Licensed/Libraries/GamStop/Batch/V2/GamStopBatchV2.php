<?php

require_once __DIR__ . '/../../Validation/GamStopValidation.php';


class GamStopBatchV2
{

    private $hostUrl;

    private $apiKey;

    private $timeout;


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


    public function __construct(string $hostUrl, string $apiKey, int $timeout)
    {
        $this->setHostUrl($hostUrl);
        $this->setApiKey($apiKey);
        $this->setTimeout($timeout);
    }

    /**
     * @param array $users
     * @return array
     * @throws Exception
     */
    public function rootPost(array $users)
    {
        $users_with_errors = [];

        $validationClass = new GamStopValidation();

        foreach ($users as $key => $user) {
            try {
                $validationClass->validateBatch($user['firstName'], $user['lastName'], $user['dateOfBirth'], $user['email'], $user['postcode'], $user['mobile']);
            } catch (Exception $exception) {
                array_push($users_with_errors, [
                    "error" => $exception->getMessage(),
                    "user" => $user
                ]);
                // Remove the user from the users' array because it has validation problems.
                unset($users[$key]);
            }
        }
        // Rebase array keys after un-setting elements
        $users = array_values($users);

        list($body, $status_code, $header) = phive()->post($this->getHostUrl(), $users, null, ["X-API-Key: " . $this->getApiKey()], 'gamstop', 'POST', $this->getTimeout(), [], null, true);

        $body = json_decode($body, true);


        if ($status_code !== 200) {
            throw new Exception("Invalid parameters");
        }

        $header = phive()->convertKeysToLower($header);
        return [
            "statusCode" => $status_code,
            "body" => $body,
            "usersWithErrors" => $users_with_errors,
            "X-Unique-Id" => $header["x-unique-id"]
        ];
    }


}