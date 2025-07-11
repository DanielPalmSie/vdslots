<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../api/ExtModule.php';

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException as ClientException;
use GuzzleHttp\Exception\ConnectException as ConnectException;
use GuzzleHttp\Exception\RequestException as RequestException;

/*
 * Public methods of a module can be used via Phive this way:
 * phive('GeoComply')->getLicenseKey();
 */

class GeoComply extends ExtModule
{
    /**
     * @var string
     */
    private string $serviceURL;
    /**
     * @var string
     */
    private string $akey;
    /**
     * @var string
     */
    private string $skey;
    /**
     * @var string
     */
    private string $decryptionKey;
    /**
     * @var string
     */
    private string $decryptionIV;
    /**
     * @var \GuzzleHttp\Client
     */
    private GuzzleClient $client;

    /**
     * @var bool skip the ip check
     */
    private bool $skip_ip_change_check;

    /**
     * @var bool skip the session check
     */
    private bool $skip_session_mismatch_check;

    /**
     * @var array
     */
    private array $skip_ip_change_check_for;

    /**
     * @var bool
     */
    private bool $debug;

    public function __construct()
    {
        $this->client = new GuzzleClient(['defaults' => ['exceptions' => false]]);
        $this->skip_ip_change_check = false;
        $this->skip_session_mismatch_check = false;
        $this->skip_ip_change_check_for = [];
        $this->debug = false;
    }

    /**
     * Method for setting credentials
     * @param array $settings
     * @return void
     */
    public function init(array $settings): void
    {
        $this->serviceURL = $settings['URL'];
        $this->akey = $settings['AKEY'];
        $this->skey = $settings['SKEY'];
        $this->decryptionKey = $settings['DECRYPTIONKEY'];
        $this->decryptionIV = $settings['DECRYPTIONIV'];
        $this->skip_ip_change_check = $settings['skip_ip_change_check'] ?? false;
        $this->skip_ip_change_check_for = $settings['skip_ip_change_check_for'] ?? [];
        $this->debug = $settings['debug'];
    }

    /**
     * Because GeoComply starts before Login we need to use user id from a session
     * @return string
     */
    public function getUserId(): string
    {
        if(cu()){
            $userId = cu()->getId();
        }

        if(!$userId && cu($_SESSION['gcusername'])){
            $userId = cu($_SESSION['gcusername'])->getId();
        }

        if(!$userId){
            //Edge case. Should not be in the logs
            $userId = rand();
            $this->log('GeoComply initialisation in unlogged state', [$userId]);
        }

        return (string) $userId;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $_SESSION['gcusername'] ?? '';
    }

    /**
     * @return array
     */
    public function getFailedGeolocation(): array
    {
        $userID = $this->getUserId() ?: uid();
        $user = cu($userID);

        $failedAttempt = $user->getSetting('geolocation_failures');
        $nextGeolocation = $user->getSetting('geolocation_unlock_date');

        return [$failedAttempt, $this->calculateMinutesLeft($nextGeolocation)];
    }

    /**
     * @param $nextGeolocationTime
     * @return false|float|int
     */
    private function calculateMinutesLeft($nextGeolocationTime)
    {
        $currentTime = time();
        $nextGeolocationTimestamp = strtotime($nextGeolocationTime);
        $timeDifference = $nextGeolocationTimestamp - $currentTime;
        if ($timeDifference > 0) {
            $minutesLeft = ceil($timeDifference / 60);

            return $minutesLeft;
        }

        return 0;
    }

    /**
     * @return void
     */
    public function incFailedGeolocations(): void
    {
        $userID = $this->getUserId() ?: uid();
        $user = cu($userID);

        $settings = $this->getSetting('auth');

        $allowedAttempts = $settings['retry_allowed_attempts'];
        $nextGeolocationSeconds = $settings['retry_interval'];

        //if user is blocked from retries we should not increase attempts value on page refresh
        $nextGeolocationTime = $user->getSetting('geolocation_unlock_date');
        if ($this->calculateMinutesLeft($nextGeolocationTime)) {
            return;
        } else {
            //if date is in the past we don't need flag in DB anymore
            $user->deleteSetting('geolocation_unlock_date');
        }

        $user->incSetting('geolocation_failures');
        $failedAttempts = $user->getSetting('geolocation_failures');


        if ($failedAttempts >= $allowedAttempts) {
            $currentTime = time();
            $newTime = $currentTime + $nextGeolocationSeconds;
            $nextGeolocationTime = date('Y-m-d H:i:s', $newTime);

            $user->setSetting('geolocation_unlock_date', $nextGeolocationTime);
            $user->deleteSettings('geolocation_failures');
        }
    }

    /**
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * Catching and returning Guzzle exceptions as Array
     */
    private function retrieveLicenseKey(): array
    {
        $data = [];
        $queryData = [
            'akey' => $this->akey,
            'skey' => $this->skey,
            'html5' => 'secure',
        ];

        $url = $this->serviceURL . '?' . http_build_query($queryData);

        try {
            $res = $this->client->request('GET', $url, [
                'headers' => ['Accept' => 'application/xml'],
                'timeout' => 120,
            ]);

            $responseData = $this->xmlToArray($res->getBody()->getContents());

            //converting to a microtime for fast dates comparision in a future
            $valid = strtotime($responseData['@attributes']['expires']);
            $key = $responseData[0];

            $data['valid'] = $valid;
            $data['key'] = $key;
        } catch (ClientException $e) {
            $response = $e->getCode();
            $data['error'] = $response;
            $this->log(__METHOD__ . ' - ClientException: ', $data);
        } catch (ConnectException|RequestException $e) {
            $response = $e->getMessage();
            $data['error'] = $response;
            $this->log(__METHOD__ . ' ConnectException|RequestException', $data);
        }

        return $data;
    }

    /**
     * @return array
     *               Verifing saved data in a Redis.
     *               If we have license data - retrieving and comparing dates
     *               If we don't - saving license data to a Redis
     */
    public function getLicenseKey(): array
    {
        $license = phMgetArr('geocomply-v2');

        //we have geocomply licence key in a Redis
        if (isset($license)) {
            //compare dates and if outdated - refresh key
            if ($license['valid'] < microtime(true)) {
                $license = $this->setLicenseKey();
            }
        } else {
            $license = $this->setLicenseKey();
        }

        return $license;
    }

    /**
     * @return array
     *               On success we return [[valid]=>'date', [key]=>'key string']
     *               On fail [[error]=>'error reason']
     */
    private function setLicenseKey(): array
    {
        $licenseData = $this->retrieveLicenseKey();

        //If no errors - saving geocomply licence data to a Redis
        if (! isset($licenseData['error'])) {
            phMsetArr('geocomply-v2', $licenseData);
        }

        return $licenseData;
    }

    /**
     * @return array
     *               Getting user geocomply data and checking if it is valid, and if the ip address is the same.
     *               If not we are requesting new geocomply data
     * @param null|mixed $user
     */
    public function getUserGeoComplyData($user = null): array
    {
        $geo_comply_data = $this->loadGeoComplyData($user);

        if (! empty($geo_comply_data)) {
            if ($this->hasIpChanged($geo_comply_data)) {
                //we need to request new GeoComply Packet on IP change
                $geo_comply_data = ['valid' => 0];
                // we need to delete the data as the geolocation is no longer valid, we will not accept further bets
                $this->deleteGeoComplyData($user);
            }

            if ($this->isExpired($geo_comply_data)) {
                //we need to request ned GeoComply Packet on outdate
                $geo_comply_data = ['valid' => 0];
                // note that we don't delete the data here as geolocation is still valid for buffer_time seconds
                // and we will still accept bets from the player during this period until renovation
            }
        } else {
            $geo_comply_data = ['valid' => 0];
        }

        return $geo_comply_data;
    }

    /**
     * Loads the user data
     *
     * @param $user
     * @return array
     */
    public function loadGeoComplyData($user = null): array
    {
        $user_id = empty($user) ? $this->getUserId() : uid($user);

        return phMgetArr($user_id . '.geocomply') ?? [];
    }

    /**
     * Deletes the user data
     *
     * @param $user
     * @return void
     */
    public function deleteGeoComplyData($user = null)
    {
        $user_id = empty($user) ? $this->getUserId() : uid($user);
        phMdel($user_id . '.geocomply');
    }

    /**
     * Saving valid GeoComply to a Redis
     *
     * @param string $ip
     * @param int $ttl
     * @param array $data
     * @return void
     */
    public function setUserGeoComplyData(array $data): void
    {
        $user_id = $this->getUserId();
        $geo_comply_users = phMgetArr('geocomply.users');
        if (! isset($geo_comply_users[$user_id])) {
            $geo_comply_users[] = $user_id;
        }

        $ip = $data['ip']['@attributes']['ipaddress'];
        $remIp = $this->getIp();
        $ipMismatch = $ip != $remIp;

        $geolocate_in = $data['geolocate_in'];
        $buffer_time = $data['buffer_time'];
        $valid_till = microtime(true) + $geolocate_in - $buffer_time;
        $session_id = session_id();

        $this->log('setUserGeoComplyData',  [
            'user' => $user_id,
            'ip' => $ip,
            'remip' => $remIp,
            'ip_initial_mismatch' => $ipMismatch,
            'valid' => $valid_till,
            'geolocate_in' => $geolocate_in,
            'buffer_time' => $buffer_time,
            'session_id' => $session_id,
        ]);

        phMsetArr('geocomply.users', $geo_comply_users);
        phMsetArr($user_id . '.geocomply', [
            'ip' => $ip,
            'remip' => $remIp,
            'ip_initial_mismatch' => $ipMismatch,
            'valid' => $valid_till,
            'geolocate_in' => $geolocate_in,
            'buffer_time' => $buffer_time,
            'session_id' => $session_id,
        ]);
    }

    /**
     * @param string $geoPacket
     * @return array
     */
    public function decryptGeoPacket(string $geoPacket): array
    {
        $decryptedXML = openssl_decrypt(
            $geoPacket,
            'AES-128-CBC',
            $this->hexToStr($this->decryptionKey),
            0,
            $this->hexToStr($this->decryptionIV)
        );
        if (! $decryptedXML) {
            return ['error' => 'Decryption error'];
        }

        return $this->xmlToArray($decryptedXML);
    }

    /**
     * @param array $data
     *
     * @return array|int[]
     */
    public function parsePacket(array $data): array
    {
        $this->log('GeoPacket', $data);

        if (isset($data['error_code']) && ! empty($data['error_message'])) {
            return [
                'error_code' => $data['error_code'],
                'error_message' => $this->localizedErrors[$data['error_code']]['message'] ?? $data['error_message'],
                'troubleshooter' => $this->localizedErrors[$data['error_code']]['troubleshoot'] ?? $data['troubleshooter'],
                'status' => 0,
            ];
        }

        $this->setUserGeoComplyData($data);

        return ['status' => 1];
    }

    /**
     * Checks if the expiration time has been reached
     *
     * @param $geo_comply_data
     * @return bool
     */
    private function isExpired($geo_comply_data): bool
    {
        return (isset($geo_comply_data['valid']) && ($geo_comply_data['valid']) < microtime(true));
    }

    /**
     * Checks if the token is expired (we were not able to validate the geolocation of the user)
     *
     * @param $geo_comply_data
     * @return bool
     */
    private function isGeolocationExpired($geo_comply_data): bool
    {
        if (! ($geo_comply_data['valid'] && $geo_comply_data['buffer_time'])) {
            return false;
        }

        return ($geo_comply_data['valid'] + $geo_comply_data['buffer_time']) < microtime(true);
    }

    /**
     * Used for testing and background tasks
     *
     * @param bool $skip
     * @return void
     */
    public function setSkipChangeIpCheck(bool $skip = true)
    {
        $this->skip_ip_change_check = true;
    }

    /**
     * Used for testing and background tasks
     *
     * @return void
     */
    public function setSkipSessionMismatchCheck()
    {
        $this->skip_session_mismatch_check = true;
    }

    /**
     * The users that have gone above expiration time
     *
     * @return array
     */
    public function getExpiredUsers(): array
    {
        $geo_comply_users = phMgetArr('geocomply.users') ?? [];

        return array_filter($geo_comply_users, function ($user_id) {
            return !empty($user_id) && !$this->hasVerifiedIp($user_id);
        });
    }

    /**
     * If the IP of the player has been validated
     *
     * @param $user
     * @return bool
     */
    public function hasVerifiedIp($user): bool
    {
        $geo_comply_data = $this->loadGeoComplyData($user);

        if (empty($geo_comply_data)) {
            return false;
        }

        $hasIpChanged = $this->hasIpChanged($geo_comply_data);
        $isGeolocationExpired = $this->isGeolocationExpired($geo_comply_data);
        $sessionIdMismatch = $this->isSessionMismatch($geo_comply_data);

        if (! $hasIpChanged && ! $isGeolocationExpired && ! $sessionIdMismatch) {
            return true;
        }

        return false;
    }

    /**
     * If the session of the user has changed
     *
     * @param $geo_comply_data
     * @return bool
     */
    private function isSessionMismatch($geo_comply_data): bool
    {
        return  !$this->skip_session_mismatch_check && $geo_comply_data['session_id'] !== session_id();
    }

    /**
     * If the IP of the user has changed, can be skipped calling setSkipChangeIpCheck when running background tasks
     *
     * @param $geo_comply_data
     * @return bool
     */
    private function hasIpChanged($geo_comply_data): bool
    {
        if ($geo_comply_data['ip_initial_mismatch'] || $this->skip_ip_change_check || $this->isWhitelisted()
        ) {
            return false;
        }

        $remIp = $this->getIp();

        return $remIp != $geo_comply_data['ip'];
    }

    /**
     * Method for detecting user's IP
     * Claudflare detection with fallback on remIp();
     * @return string
     */
    private function getIp(): string
    {
        return $_SERVER['HTTP_CF_PSEUDO_IPV4'] ?? $_SERVER['HTTP_CF_CONNECTING_IP'] ?? remIp() ?? '';
    }

    /**
     * @param string $xmlData
     * @return array
     */
    private function xmlToArray(string $xmlData): array
    {
        $xml = simplexml_load_string($xmlData, "SimpleXMLElement", LIBXML_NOCDATA);
        $json = json_encode($xml);

        return json_decode($json, true);
    }

    /**
     * @param string $hex
     * @return string
     */
    private function hexToStr(string $hex): string
    {
        return implode(
            "",
            array_map(
                function ($value) {
                    return chr(hexdec($value));
                },
                str_split($hex, 2)
            )
        );
    }

    /**
     * Logging all necessary actions
     * @param string $message
     * @param array $payload
     * @param string $level
     * @return void
     */
    public function log(string $message, array $payload, string $level = "debug"): void
    {
        if ($this->debug) {
            switch ($level) {
                case "info":
                    phive('Logger')->getLogger('geocomply')->info($message, $payload);

                    break;
                case "notice":
                    phive('Logger')->getLogger('geocomply')->notice($message, $payload);

                    break;
                default:
                    phive('Logger')->getLogger('geocomply')->debug($message, $payload);

                    break;
            }
        }
    }

    /**
     * @return bool
     */
    public function isWhitelisted(): bool
    {
        return $this->getUsername()
            && in_array($this->getUsername(), $this->skip_ip_change_check_for);
    }
}
