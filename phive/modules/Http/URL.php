<?php

/**
 * Class URL
 *
 * Handles URL transformations
 */
class URL extends PhModule {

    public const MOBILE_DIR_PART = '/mobile';
    public const UTM_CAMPAIGN = 'utm_campaign';
    public const UTM_MEDIUM = 'utm_medium';
    public const UTM_TERM = 'utm_term';
    public const UTM_CONTENT = 'utm_content';
    public const UTM_SOURCE = 'utm_source';
    public const GOOGLE_CLICK_ID = 'gclid';
    public const MICROSOFT_CLICK_ID = 'msclkid';

    public const UTM_PARAMETERS = [
        self::UTM_CAMPAIGN,
        self::UTM_MEDIUM,
        self::UTM_TERM,
        self::UTM_CONTENT,
        self::UTM_SOURCE
    ];

    public const MARKETING_PARAMETERS = [
        self::UTM_CAMPAIGN,
        self::UTM_MEDIUM,
        self::UTM_TERM,
        self::UTM_CONTENT,
        self::UTM_SOURCE,
        self::GOOGLE_CLICK_ID,
        self::MICROSOFT_CLICK_ID
    ];

    /**
     * Check if we can find a hostname name in the url provided
     * Note: This is a relatively soft check and should not be used with very complex urls
     *
     * @param string $url
     * @return bool
     */
    public function isRelative(string $url): bool
    {
        return !parse_url($url, PHP_URL_HOST);
    }

    /**
     * Get the base URL from a string without any query string or anchor
     * Note: Does not modify absolute or relative style URLs
     *
     * Option to specify if a trailing slash is retained / added / removed
     *
     * @param string $url
     * @return string
     */
    public function getBaseUrl(string $url): string
    {
        if ($parts = $this->getURLParts($url)) {
            if (!$this->isRelative($url)) {
                $base = $parts['scheme'] . '://' . $parts['host'];
                if (!empty($parts['port'])) return $base .= ':' . $parts['port'];
            } else {
                return '/';
            }
        }

        return $url;
    }

    /**
     * Add a directory path to a URL - Modifies url in place
     *
     * @param string $url The base URL to modify
     * @param string $prepend If specified this will be added to the beginning of the path
     * @param string $append If specified this will be added to the end of the path
     * @param bool $with_trailing_slash Include / Remove trailing slash from path
     * @return string The modified url if successful or original url on failure
     */
    public function addDirPart(string $url, string $prepend = '', string $append = '', bool $with_trailing_slash = false): string
    {
        if ($prepend || $append) {
            $prepend = trim($prepend, '/');
            $append = trim($append, '/');

            if ($parts = $this->getURLParts($url)) {
                $path = trim($parts['path'], '/');
                $final_path = rtrim(($prepend) ? $prepend . '/' . $path : $path . '/' . $append, '/');

                if ($with_trailing_slash) $final_path = $final_path . '/';

                return $this->replaceURLElement($url, 'path', $final_path);
            }
        }

        // Failed parsing the url or $prepend & $append where not provided; return original
        return $url;
    }

    /**
     * Append the mobile dir to the path of the URL specified
     * either if the user is on mobile or if $force is enabled
     *
     * Note: If URL cannot be parsed the original URL is returned
     *
     * @param string $url
     * @param string $mobile_dir_part
     * @param bool $force - Force the mobile path even if not currently on mobile
     * @return string
     */
    public function prependMobileDirPart(string $url, string $mobile_dir_part = self::MOBILE_DIR_PART, bool $force = false): string
    {
        if ($force || phive()->isMobile()) {
            $parts = $this->getURLParts($url);
            if ($parts && (!isset($parts['path']) || !str_contains($parts['path'], $mobile_dir_part))) {
                return $this->addDirPart($url, $mobile_dir_part, '', true);
            }
        }

        return $url;
    }

    /**
     * Get all query parameters from a URL as an array of key value pairs
     *
     * @param string $url
     * @param array $filter
     * @return array
     */
    public function getQueryParams(string $url, array $filter = []): array
    {
        if ($parts = $this->getURLParts($url)) {
            parse_str($parts['query'], $params);

            // Filter out the parameters to only return the specified list
            if (!empty($filter)) {
                return array_filter($params, function ($key) use ($filter) {
                    return in_array(strtolower($key), $filter);
                }, ARRAY_FILTER_USE_KEY);
            }

            return $params;
        }

        return [];
    }

    /**
     * Get the value of a particular query parameter from URL
     *
     * @param string $url
     * @param string $param
     * @return mixed|null
     */
    public function getQueryParam(string $url, string $param)
    {
        $params = $this->getQueryParams($url, [$param]);
        return (isset($params[$param])) ? $params[$param] : null;
    }

    /**
     * Add a query string parameter to a URL
     *
     * @param string $url
     * @param string $name
     * @param $value
     * @return string
     */
    public function addQueryParam(string $url, string $name, $value): string
    {
        $params = $this->getQueryParams($url);
        $params[$name] = $value;

        return $this->replaceURLElement($url, 'query', http_build_query($params));
    }

    /**
     * Remove a query string parameter from a URL
     *
     * @param string $url
     * @param string $name
     * @return string
     */
    public function removeQueryParam(string $url, string $name): string
    {
        $params = $this->getQueryParams($url);
        if (array_key_exists($name, $params)) unset($params[$name]);

        return $this->replaceURLElement($url, 'query', http_build_query($params));
    }

    /**
     * Get the url parts or null on failure
     *
     * @param string $url
     * @return array|null
     */
    public function getURLParts(string $url): ?array
    {
        $parts = parse_url($url);
        return (is_array($parts) && !empty($parts)) ? $parts : null;
    }

    /**
     * @param string $url
     * @param string $replace
     * @param string $with
     * @return string
     */
    public function replaceURLElement(string $url, string $replace, string $with): string
    {
        if ($parts = $this->getURLParts($url)) {
            $parts[$replace] = $with;

            $uri = '/';
            if (!$this->isRelative($url)) {
                $uri = $parts['scheme'] . '://' . $parts['host'];
                if (!empty($parts['port'])) $uri .= ':' . $parts['port'];
            }

            if (!isset($parts['path'])) $parts['path'] = '/';
            $uri = rtrim($uri, '/') . '/' . ltrim($parts['path'], '/');

            if (!empty($parts['query']))    $uri .= '?' . $parts['query'];
            if (!empty($parts['fragment'])) $uri .= '#' . $parts['fragment'];

            return $uri;
        }

        return $url;
    }

    /**
     * Checks if the specified URL contains the Google click id
     *
     * @param string $url
     * @return bool
     */
    public function isGoogleAdsURL(string $url): bool
    {
        return !empty($this->getQueryParam($url, self::GOOGLE_CLICK_ID));
    }

    /**
     * Checks if the specified URL contains a microsoft click id
     *
     * @param string $url
     * @return bool
     */
    public function isMicrosoftAdsURL(string $url): bool
    {
        return !empty($this->getQueryParam($url, self::MICROSOFT_CLICK_ID));
    }

    /**
     * Checks if the specified URL has an advertising id (example GCLID)
     *
     * @param string $url
     * @return bool
     */
    public function isAdURL(string $url): bool
    {
        return !empty($this->getQueryParams($url, [
            self::MICROSOFT_CLICK_ID,
            self::GOOGLE_CLICK_ID
        ]));
    }

    /**
     * Checks if the specified URL contains UTM parameters
     *
     * @param string $url
     * @return bool
     */
    public function hasUTMParameter(string $url): bool
    {
        return !empty($this->getQueryParams($url, self::UTM_PARAMETERS));
    }
}
