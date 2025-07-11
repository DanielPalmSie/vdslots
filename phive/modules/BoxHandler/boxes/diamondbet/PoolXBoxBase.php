<?php

require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';

class PoolXBoxBase extends DiamondBox
{
    private const POOLX_GET_GAME_LAUNCH_METHOD = 'getgamelaunchurl';

    private const POOLX_GAME_TAG = 'superstip';

    private string $brand;

    private const NAVIGATION_ID_MAP = [
        'spelvinnare' => [BrandedConfig::BRAND_DBET => 4],
        'succeandelar' => [BrandedConfig::BRAND_DBET => 11],
        'tipsklubben' => [BrandedConfig::BRAND_DBET => 6],
        'simsalabim' => [BrandedConfig::BRAND_DBET => 15],
        'direkten_ryd' => [BrandedConfig::BRAND_DBET => 21],
        'direkten_city_torget' => [BrandedConfig::BRAND_DBET => 22],
        'game_on_betting' => [BrandedConfig::BRAND_DBET => 23],
    ];

    public function is404() {
        return false;
    }

    public function printHTML()
    {
        if (phive()->isMobile()) {
            phive('Menuer')->renderSecondaryMobileMenu();
        }

        $user = cu() ?: null;
        $game = ['tag' => self::POOLX_GAME_TAG];
        lic('beforePlay', [$user, 'flash', $game], $user);

        if ($user) {
            $this->RgCheckBeforePlay($user);
        }

        $this->loadPoolX($user);
    }

    /**
     * @param DBUser|bool|string $user
     */
    public function loadPoolX($user): void
    {
        $this->brand = phive('BrandedConfig')->getBrand();

        if (empty(lic('isPoolxEnabled'))) {
            $this->renderUnavailable();
            return;
        }
        
        if (lic('isPoolxOnMaintenance') && !phive('Micro/Sportsbook')->isLoggedInAsMaintenanceUser()) {
            $this->onMaintenanceHtml();
            return;
        }

        loadJs('/phive/js/poolx.js');
        loadCss('/diamondbet/css/' . brandedCss() . 'poolx.css'); 

        $iframeUrl = $this->fetchPoolXIframeUrl($user);

        if (!$iframeUrl) {
            $this->renderUnavailable();
            return;
        }

        if ($this->isAgentsPage()) {
            $iframeUrl = $this->appendAgentsPageParams($iframeUrl);
        } else if ($this->hasNavigationParams()) {
            $iframeUrl = $this->appendNavigationParams($iframeUrl);
        }

        ?>
        <iframe id="poolx" src="<?php echo $iframeUrl; ?>" scrolling="no"></iframe>
        <?php
    }

    private function fetchPoolXIframeUrl(?DBUser $user)
    {
        $settings = phive('Sportsbook')->getSetting('api_endpoints');

        $url = $settings['API_ROOT_URL'] . $settings['POOLX_GET_GAME_LAUNCH_PATH'];
        $body = [
            'method' => self::POOLX_GET_GAME_LAUNCH_METHOD,
            'playerid' => $user ? $user->getId() : null,
            'ext_playerip' => remIp(),
            'lang' => phive('Localizer')->getLanguage(),
            'currency' => $user ? $user->getCurrency() : getCur()['code'],
        ];
        $headers = ["X-API-KEY: " . $settings['API_KEY']];

        $response = phive()->post($url, json_encode($body), 'application/json', $headers);
        $iframeUrl = json_decode($response)->gameLaunchUrl;

        return $iframeUrl;
    }

    private function appendNavigationParams(string $url): string
    {
        $navigationKey = $_GET['navigationKey'];
        $navigationId = $_GET['navigationId'];

        return "$url&navigationKey=$navigationKey&navigationId=$navigationId";
    }

    private function isAgentsPage(): bool
    {
        return strpos($_SERVER['REQUEST_URI'], '/agents/') !== false;
    }

    private function hasNavigationParams(): bool
    {
        return $_GET['navigationKey'] && $_GET['navigationId'];
    }

    private function appendAgentsPageParams(string $url): string
    {
        $urlParts = explode('/', trim($_SERVER['REQUEST_URI'], '/'));
        $agentName = end($urlParts);

        $navigationId = self::NAVIGATION_ID_MAP[$agentName][$this->brand];

        if (!$navigationId) return $url; // invalid agent name passed in URL

        return "$url&navigationKey=agent&navigationId=$navigationId";
    }

    private function renderUnavailable()
    {
        ?>
        <div class="frame-block">
            <div class="frame-holder">
                <h1><?php et('404.header'); ?></h1>
                <?php et('404.content.html'); ?>
            </div>
        </div>
        <?php
    }

    private function RgCheckBeforePlay(DBUser $user)
    {
        $game = ['tag' => self::POOLX_GAME_TAG];
        lic('beforePlay', [$user, 'flash', $game], $user);
    }

    private function onMaintenanceHtml()
    {
        ?>
        <div class="frame-block">
            <div class="frame-holder" style="text-align: center">
                <img class="sb-maintenance-img"
                     alt="sportsbook-maintenance-image"
                     src="/diamondbet/maintenance/sb-maintenance-ball.png"
                     style="display: block; margin-left: auto; margin-right: auto; max-width: min(350px, 100%)">
                <h1><?php et('sb.maintenance.header') ?></h1>
                <?php
                $body = t2(
                    'sb.maintenance.body',
                    ['support_email' => phive('MailHandler')->getSetting('support_mail')]
                );
                echo $body;
                ?>
            </div>
        </div>
        <?php
    }
}
