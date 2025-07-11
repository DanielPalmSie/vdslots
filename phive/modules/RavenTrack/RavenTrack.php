<?php
require_once __DIR__ . '/../../api/ExtModule.php';

class RavenTrack extends ExtModule{

    private bool $isEnabled;
    public string $ravenTrackBaseUrl;
    public string $ravenTrackToken;
    public string $ravenTrackRefreshToken;
    public int $timeout = 30; // second
    private array $headers = [];
    private string $defaultType;
    public string $gclid = "";
    public string $route;
    public const GA_CL_ID = 'gclid';

    public function __construct(){
        parent::__construct();

        $headers = [
            'Authorization: Bearer '. $this->getRavenTrackToken(),
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded'
        ];

        $this->headers = $headers;
        $this->defaultType = 'application/json';
    }

    /**
     * @return bool|mixed
     */
    public function isEnabled(): bool
    {
        if (!empty($this->isEnabled)) {
            return $this->isEnabled;
        }
        return $this->isEnabled = $this->getSetting('raventrack')['ENABLED'] ?? false;
    }

    /**
     * Get Google Click Identifier
     * @return string
     */
    public function getGoogleClickId(): string
    {
        $gclid = static::GA_CL_ID;
        if (!empty($_GET[$gclid])) {
            $this->gclid = $_GET[$gclid];
            // Also set cookie
            setCookieSecure($gclid, $_GET[$gclid]);
        } elseif (!empty($_COOKIE[$gclid])) {
            $this->gclid = $_COOKIE[$gclid];
        }
        return $this->gclid;
    }

    /**
     * Get RavenTrack baseURL
     * @return string
     */
    public function getRavenTrackBaseUrl(): string
    {
        if (!empty($this->ravenTrackBaseUrl)) {
            return $this->ravenTrackBaseUrl;
        }
        return $this->ravenTrackBaseUrl = $this->getSetting('raventrack')['TRACK_DOMAIN_URL'] ?? '';
    }

    /**
     * Get RavenTrack access token
     * @return string
     */
    public function getRavenTrackToken(): string
    {
        if (!empty($this->ravenTrackToken)) {
            return $this->ravenTrackToken;
        }
        return $this->ravenTrackToken = $this->getSetting('raventrack')['ACCESS_TOKEN'] ?? '';
    }

    /**
     * get RavenTrack refresh token
     * @return string
     */
    public function getRavenTrackRefreshToken(): string
    {
        if (!empty($this->ravenTrackRefreshToken)) {
            return $this->ravenTrackRefreshToken;
        }
        return $this->ravenTrackRefreshToken = $this->getSetting('raventrack')['REFRESH_TOKEN'] ?? '';
    }

    /**
     * @param string $route
     * @return string
     */
    public function getRoute(string $route = ''): string
    {
        $routes = [
            'refreshToken' => 'api/oauth/refresh-token',
            'getPing' => 'api/ping',
            'createClick' => 'api/track',
            'getClickLookup' => 'click/lookup'
        ];

        return $routes[$route] ?? '';
    }

    /**
     * refresh RavenTrack token with new one
     * @return void
     */
    public function refreshToken()
    {
        $endPoint = $this->getRoute('refreshToken');

        $this->route = $this->getRavenTrackBaseUrl() . $endPoint;

        $postData = [
            'refresh_token' => $this->getRavenTrackRefreshToken()
        ];

        $this->headers = [
            'Content-Type: application/x-www-form-urlencoded'
        ];

        $body = phive()->post($this->route, $postData, $this->defaultType, $this->headers);

        $response = $this->generateResponse($body);

        if ($response['status']) {
            $this->ravenTrackToken = $response['data']['access_token'];
            $this->ravenTrackRefreshToken = $response['data']['refresh_token'];
        }
    }

    /**
     * test RavenTrack connection
     * @return array
     */
    public function getPing(): array
    {
        $endPoint = $this->getRoute('getPing');

        $this->route = $this->getRavenTrackBaseUrl() . $endPoint;

        $body = phive()->post($this->route, '', $this->defaultType, $this->headers);

        return $this->generateResponse($body);
    }

    public function createClick(): array
    {
        $endPoint = $this->getRoute('createClick');

        $this->route = $this->getRavenTrackBaseUrl() . $endPoint;

        $postData = [
            'campaign_id' => '20868813771',
            'track_hash' => phive()->uuid(), // Required
            'ip_address' => remIp(), // Required
            's1' => 'ARIFtV',
            's2' => '6001611',
            's3' => '',
            't1' => '',
            't2' => '',
            't3' => '',
            'gclid' => $this->getGoogleClickId(),
            'msclkid' => '',
            'test' => '1',
            'geo' => '',
            'targeting' => ''
        ];

        $body = phive()->post($this->route, $postData, $this->defaultType, $this->headers);

        return $this->generateResponse($body);
    }

    /**
     * @param bool $btag
     * @return array|string[]
     */
    public function getClickLookup(bool $btag = false): array
    {
        $clickId = $this->getGoogleClickId();

        if (empty($clickId)) {
            return [
                'status' => 'error',
                'message' => 'gclid is not found'
            ];
        }

        $queryString = http_build_query([
            'click_id' => $clickId,
            'by' => 'click'
        ]);

        $endPoint = $this->getRoute('getClickLookup');
        $this->route = $this->getRavenTrackBaseUrl() . $endPoint . "?$queryString";

        $body = phive()->get($this->route, $this->timeout, $this->headers);

        $response = $this->generateResponse($body);

        if ($btag) {
            return $response['tracking_tag_information']['btag'];
        }

        return $response;
    }

    /**
     * @param $body
     * @return array
     */

    private function generateResponse($body): array
    {
        $response = json_decode($body, true);

        $status = isset($response['message']) ? 'error' : 'success';
        $response['message'] = $response['message'] ?? 'Call made successfully';
        $message = !empty($response['message']) ? $response['message'] : "Request is not completed";

        return [
            'status' => $status,
            'message' => $message,
            'data' => $response['result'] ?? []
        ];
    }
}
