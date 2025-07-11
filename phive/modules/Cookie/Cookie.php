<?php
$phive_base_dir = __DIR__ . '/../../';
require_once $phive_base_dir . 'api/PhModule.php';

class Cookie extends PhModule
{

    public $cookiesInfoCookie;

    /**
     * @return void
     */
    public function loadJs()
    {
        loadJs("/phive/js/jquery.min.js");
        loadJs( "/diamondbet/js/analytics.js" );
        loadJs("/phive/js/jquery.cookie.js");
        loadJs('/phive/modules/Cookie/js/cookie.js');
    }

    /**
     * @return void printCSS()
     */
    public function loadCss()
    {
        loadCss("/diamondbet/css/" . brandedCss() . "all.css");
        loadCss("/diamondbet/css/" . brandedCss() . "cookie.css");
    }

    /**
     * @param string $type
     * @param string $href
     * @param string $label
     * @return string
     */
    public function redirectLink(string $type, string $href, string $label): string
    {
        $llink = llink((phive()->isMobile() ? '/mobile' : '') . $type);
        $href = $llink;
        $action = 'target="_blank" rel="noopener noreferrer"';

        return "<a href='{$href}' {$action}>{$label}</a>";
    }

    public function updateCookiesInfoCookie()
    {
        $this->cookiesInfoCookie = isset($_COOKIE['cookies_consent_info']) ? $_COOKIE['cookies_consent_info'] : '';
    }

    /**
     * Checks if cookie notifications are enabled and if the 'cookies_consent_info' is empty.
     * This function is used to determine whether to display cookie notifications based on  settings
     *
     * @return bool
     */
    public function isCookieEnable(): bool
    {
        $cookieNotificationsEnable = phive()->getSetting('cookie_notifications');
        $this->updateCookiesInfoCookie();
        return $cookieNotificationsEnable && empty($this->cookiesInfoCookie);
    }

    /**
     * This function is responsible for to check necessary types cookies enables
     *
     */
    public function necessaryCookiesEnable(): bool
    {
        $this->updateCookiesInfoCookie();
        return strpos($this->cookiesInfoCookie, '_strict') !== false;
    }

    /**
     * This function is responsible for to check functional types cookies enables
     *
     */
    public function functionalCookiesEnable(): bool
    {
        $this->updateCookiesInfoCookie();
        return strpos($this->cookiesInfoCookie, '_functionality') !== false;
    }

    /**
     * This function is responsible for to check analytics types cookies enables
     *
     */
    public function analyticsCookiesEnable(): bool
    {
        $this->updateCookiesInfoCookie();
        return strpos($this->cookiesInfoCookie, '_performance') !== false;
    }

    /**
     * This function is responsible for to check marketing types cookies enables
     *
     */
    public function marketingCookiesEnable(): bool
    {
        $this->updateCookiesInfoCookie();
        return strpos($this->cookiesInfoCookie, '_marketing') !== false;
    }

    /**
     * This function is responsible for checking if analytics or marketing cookies are enabled.
     */
    public function areThirdPartyCookiesEnable(): bool
    {
        return $this->analyticsCookiesEnable() || $this->marketingCookiesEnable();
    }

    /**
     * Generates the JavaScript code for handling the cookie popup.
     * Loads necessary CSS and JS files and initializes the Cookie object
     * to check if cookies are accepted, then adds it to the popups queue.
     */
    public function cookiePopup()
    {
        $this->loadCss();
        $this->loadJs();

        // ## need to return if not set on test/local env
        if (!phive()->getSetting('cookie_notifications')) {
            return;
        }

        ?>
        <script type="text/javascript">
            $(document).ready(function () {
                // only if cookie is not already accepted
                if (!Cookie.isConsentStored()) {
                    Cookie.isCookieAccepted();
                }
            });
        </script>
        <?php
    }

}
