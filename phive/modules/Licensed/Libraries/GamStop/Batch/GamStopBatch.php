<?php


class GamStopBatch
{
    CONST V_2 = 'v2';

    private $version;

    private $hostUrl;

    private $apiKey;

    private $users = [];

    private $allowedVersions = [self::V_2];

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
    public function getUsers(): array
    {
        return $this->users;
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
     * @param array $users
     */
    public function setUsers(array $users)
    {
        $returnUsers = [];

        foreach ($users as $user) {
            array_push($returnUsers,
                [
                    'correlationId' => $user["id"],    // string | User correlation Id
                    'firstName' => $user['firstname'],   // string | First name of person, only 20 characters are significant
                    'lastName' => $user['lastname'],     // string | Last name of person, only 20 characters are significant
                    'dateOfBirth' => $user['dob'],      // string | Date of birth in ISO format (yyyy-mm-dd)
                    'email' => $user['email'],            // string | Email address
                    'postcode' => $user['zipcode'],       // string | Postcode - spaces not significant,
                    'mobile' => $user['mobile']               // string | Mobile number
                ]
            );
        }
        $this->users = $returnUsers;
    }


    /**
     * @return GamStopBatchV2
     */
    private function getApiV2Instance(): GamStopBatchV2
    {
        require_once(__DIR__ . '/V2/GamStopBatchV2.php');
        return new GamStopBatchV2($this->getHostUrl(), $this->getApiKey(), $this->getTimeout());
    }


    /**
     * @return array
     * @throws Exception
     */
    public function execute(): array
    {
        $res = $this->getApiV2Instance()->rootPost($this->getUsers());
        $res = phive()->convertKeysToLower($res, true);
        return $this->composeResponse($res["statuscode"], $res["body"], $res["x-unique-id"], $res["userswitherrors"]);
    }


    /**
     * @param int $status_code
     * @param array $body
     * @param string $x_unique_id
     * @param array $users_with_errors
     * @return array
     */
    private function composeResponse(int $status_code, array $body, string $x_unique_id, array $users_with_errors): array
    {
        $excluded = [];
        $notExcluded = [];
        $previouslyExcluded = [];

        // Put all the users with validation errors in the excluded array
        foreach ($users_with_errors as $user) {
            array_push($excluded, $user["user"]["correlationId"]);
        }

        foreach ($body as $value) {
            switch ($value["exclusion"]) {
                case 'Y':
                    array_push($excluded, $value["correlationId"]);
                    break;
                case 'P':
                    array_push($previouslyExcluded, $value["correlationId"]);
                    break;
                case 'N':
                default:
                    array_push($notExcluded, $value["correlationId"]); break;
            }
        }


        return [
            "statusCode" => $status_code,
            "exclusions" => $body,
            "X-Unique-Id" => $x_unique_id,
            "excluded" => $excluded,
            "notExcluded" => $notExcluded,
            "previouslyExcluded" => $previouslyExcluded,
            "usersWithErrors" => $users_with_errors
        ];
    }

}