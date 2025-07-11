<?php
/*
   Filtering of incoming data
   CSRF Token generation
 */

// Parameters to not escape
$para_esc = array("password", "captcha");
// Parameters not to encode
$para_enc = array("password", "message", "subject", "captcha");

class SecurityApi
{
    // Generate CSRF Token
    public static function GenerateCsrf()
    {
        if (!isset($_SESSION['token'])) {
            $token = hash_hmac('sha512', openssl_random_pseudo_bytes(32), openssl_random_pseudo_bytes(16));
            $_SESSION['token_time'] = time();
            $_SESSION['token'] = $token;
        }
    }

    // return the CSRF token
    public static function GetCsrf()
    {
        if (isset($_SESSION['token'])) {
            return $_SESSION['token'];
        }
    }

    // Generate CSRF Token field as META
    public static function GenerateCsrfMeta()
    {
        if (isset($_SESSION['token'])) {
            return "<meta name='csrf_token' content='{$_SESSION['token']}'/>";
        }
    }

    // Generate CSRF Token field for FORM
    public static function GenerateCsrfField()
    {
        if (isset($_SESSION['token'])) {
            return "<input type='hidden' name='token' value='{$_SESSION['token']}'>";
        }
    }

    public static function checkOrigin()
    {
        $target_origin = phive()->getSiteUrl();
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
        $referer = $_SERVER['HTTP_REFERER'] ?? null;

        if (!$origin && !$referer) {
            return "CSRF protection: Neither Origin nor Referer header is present.";
        }

        $header = $origin ?: $referer;
        $header_top_domain = self::getTopDomain($header);
        $target_top_domain = self::getTopDomain($target_origin);

        if (strcasecmp($header_top_domain, $target_top_domain) !== 0) {
            return "CSRF protection: Top domain from header doesn't match target top domain.";
        }

        return true;
    }

    /**
     * Gets the top domain from a given url
     * This function only handles domains without public suffixes
     * It returns the top domain as a string
     * @param $url
     * @return string
     */
    public static function getTopDomain($url)
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (preg_match('/(?:[^.]+\.)?([^.]+\.[^.]+)$/', $host, $matches)) {
            $top_domain = $matches[1];
        } else {
            $top_domain = $host;
        }

        return $top_domain;
    }


    // Check CSRF Token
    public static function CheckCsrf($token, $force_check = false)
    {
        if (!$force_check && empty($_SESSION['token'])) {
            return true;
        }

        /**
         * TODO 2018-02-27 check if any GP uses the main way to load the site (videoslots/index.php) instead of api.php, in that case they are subject to CheckCsrf
         * TODO look at commit 8257cabbf744f3fb5a0593d7f2235184ef232c27, to get an idea on how to manage that
         */

        // i need to whitelist callbacks from these PSP that should not be subject to CSRF validation, otherwise user cannot deposit.
        // TODO tested with and it's working, double check if in any case an encoded URL is returned, and consider comparing againt the encoded version of the $url.
        $mtsConfig = phive('Cashier')->getSetting('mts');
        if ($mtsConfig && $mtsConfig['types']) {
            if ($mtsConfig['types']['wirecard']) {
                foreach ($mtsConfig['types']['wirecard'] as $url) {
                    if (stripos($_SERVER['REQUEST_URI'], $url) !== false) {
                        return true;
                    }
                }
            }
            if ($mtsConfig['types']['emp']) {
                foreach ($mtsConfig['types']['emp'] as $url) {
                    if (stripos($_SERVER['REQUEST_URI'], $url) !== false) {
                        return true;
                    }
                }
            }
        }

        if (($result = self::checkOrigin()) !== true) {
            self::InvalidOriginResponse($result);
        }

        // If token is not passed as a form parameter i'll try to check if it's present in the request headers 'X-CSRF-TOKEN' (laravel style)
        // This check can works even with ajax GET request, but not for normal pages
        // for more reference see https://laravel.com/docs/5.5/csrf
        if (empty($token)) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        // TODO double check this condition, if no token is provided anywhere something is wrong... so trigger error log
        if (empty($token)) {
            self::InvalidTokenResponse('missing');
        }

        if ($token != $_SESSION['token']) {
            self::InvalidTokenResponse();
        } else {
            return true;
        }
    }

    public static function logAndRespond($type, $error, $message)
    {
        // Log error
        phive('Logger')->error($type, $error);

        logToActionsAndReturn([
            'csrf_error',
            $_SERVER['REQUEST_URI']
        ]);

        if (phive()->getSetting('csrf_validation') === true) {
            // Check if request is AJAX
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                http_response_code(403);
                echo json_encode(['error' => $type, 'message' => $message]);
                exit;
            }

            // Redirect based on privileges
            if (privileged()) {
                phive('Redirect')->to("/admin2/404-tk/?msg={$message}");
            } else {
                echo $message;
                phive('Redirect')->to("/404/", '', false, '404 Not Found');
            }
            exit;
        }
    }

    public static function InvalidTokenResponse($type = null)
    {
        if ($type === 'missing') {
            // Missing token, should never happend.
            $message = t('err.security.token.missing.html');
        } else {
            if (privileged()) {
                // Admin2 users
                $message = "CSRF token mismatch, take a screenshot or copy paste this text and send it to dev support: Request URI: {$_SERVER['REQUEST_URI']}, Referer: {$_SERVER['HTTP_REFERER']}, Username: " . cu()->getUsername() . " (token_mismatch_admin)";
            } else {
                // Language is not set in session or Localizer at this point, so we need to get it from the request subpath
                phive('Localizer')->setFromReq();
                // standard users of the website (to keep the token mismatch info?) // FIXME not sure how things are managed on the website for this kind of stuff...
                $message = t('err.security.token.mismatch.user.html'); //'<p><strong>Something went wrong</strong></p><p>please send a screenshot to support@videoslots.com</p>'; //  (token_mismatch_user)';
            }
        }

        self::logAndRespond('invalid_token', [
            '_SERVER' => $_SERVER,
            '_GET' => $_GET,
            '_POST' => $_POST
        ], $message);
    }

    public static function InvalidOriginResponse($message)
    {
        self::logAndRespond('invalid_origin', [
            'error' => $message,
            'origin' => $_SERVER['HTTP_ORIGIN'],
            'referer' => $_SERVER['HTTP_REFERER'],
            '_GET' => $_GET,
            '_POST' => $_POST,
            '_SERVER' => $_SERVER
        ], 'Bad Request: CSRF token has been tampered');
    }
}
