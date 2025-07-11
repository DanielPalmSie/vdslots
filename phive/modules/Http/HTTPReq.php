<?php

require_once rtrim(__DIR__, '/') . '/URL.php';

/**
 * Class HTTPReq
 *
 * Handles parsing of the HTTP Request
 */
class HTTPReq extends PhModule {

    public const ORIGIN_COOKIE = '_origin';

    /**
     * Get the $_GET & $_POST parameters of this request
     * Filter them by $filter if provided
     *
     * @param bool $check_post - Read from $_POST as well
     * @param array $filter
     * @return array
     */
    public function getRequestParams(array $filter = [], bool $check_post = false): array
    {
        $params = ($check_post) ? $_GET + $_POST : $_GET;
        if (!is_array($params) || empty($params)) return [];

        // Filter out the parameters to only return the specified list
        if (!empty($filter)) {
            return array_filter($params, function ($key) use ($filter) {
                return in_array(strtolower($key), $filter);
            }, ARRAY_FILTER_USE_KEY);
        }

        return $params;
    }

    /**
     * @param string $param
     * @param bool $check_post - Read from $_POST as well
     * @return mixed|null
     */
    public function getRequestParam(string $param, bool $check_post = false)
    {
        return $this->getRequestParams([], $check_post)[$param] ?? null;
    }

    /**
     * Checks if the current request contains the Google click id
     *
     * @param bool $check_post - Read from $_POST as well
     * @return bool
     */
    public function hasGoogleClickID(bool $check_post = false): bool
    {
        return !empty($this->getRequestParam(URL::GOOGLE_CLICK_ID, $check_post));
    }

    /**
     * Checks if the current request contains the Microsoft click id
     *
     * @param bool $check_post - Read from $_POST as well
     * @return bool
     */
    public function hasMicrosoftClickID(bool $check_post = false): bool
    {
        return !empty($this->getRequestParam(URL::MICROSOFT_CLICK_ID, $check_post));
    }

    /**
     * Checks if the current request has an advertising id (example GCLID)
     *
     * @param bool $check_post - Read from $_POST as well
     * @return bool
     */
    public function hasAdParam(bool $check_post = false): bool
    {
        return !empty($this->getRequestParams([
            URL::MICROSOFT_CLICK_ID,
            URL::GOOGLE_CLICK_ID
        ], $check_post));
    }

    /**
     * Checks if the current request contains UTM parameters
     *
     * @param bool $check_post - Read from $_POST as well
     * @return bool
     */
    public function hasUTMParameter(bool $check_post = false): bool
    {
        return !empty($this->getRequestParams(URL::UTM_PARAMETERS, $check_post));
    }

    /**
     * @return string
     */
    public function getCurrentURL(): string
    {
        return (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    }

    /**
     * @return string|null
     */
    public function getReferrerURL(): ?string
    {
        return (isset($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : null;
    }

    /**
     * Tracks the First Touch URL if it has UTM or Ad parameters
     *
     * @return void
     */
    public function trackOriginURL(): void
    {
        // Set the origin_url cookie that hold UTM / Ad params
        if (isExternalTrackingEnabled()) {
            if (!isset($_COOKIE['cookies_consent_info'])) {
                if (!isset($_COOKIE[self::ORIGIN_COOKIE])) {
                    if ($this->hasAdParam() || $this->hasUTMParameter()) {
                        $this->setCookie(self::ORIGIN_COOKIE, json_encode([
                            'origin'    => $this->getCurrentURL(),
                            'referrer'  => $this->getReferrerURL()
                        ]));
                    }
                }
            } else {
                $this->setCookie(self::ORIGIN_COOKIE, null, true);
            }
        }
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getCookie(string $key): ?string
    {
        if (isset($_COOKIE[$key]) && $_COOKIE[$key] !== '-') return $_COOKIE[$key];
        return null;
    }

    /**
     * Set a cookie that can also be accessed during the execution of this script
     *
     * @param string $key
     * @param string|null $value
     * @param bool $expire
     * @return void
     */
    public function setCookie(string $key, ?string $value, bool $expire = false)
    {
        setCookieSecure($key, $expire ? '-' : $value, $expire ? 1 : null);
        $_COOKIE[$key] = ($expire) ? null : $value;
    }
}



