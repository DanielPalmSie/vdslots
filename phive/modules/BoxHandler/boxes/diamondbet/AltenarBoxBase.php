<?php

require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';

class AltenarBoxBase extends DiamondBox
{
    public function printHTML()
    {
        if (phive()->isMobile()) {
            phive('Menuer')->renderSecondaryMobileMenu();
        }
        
        $user = cu();

        if ($user) {
            $this->renderRgPopup($user);
        }

        $this->loadSportsbook($user);
    }

    /**
     * @param DBUser|bool|string $user
     */
    public function loadSportsbook($user): void
    {
        if (empty(lic('isSportsbookEnabled'))) {
            $this->renderUnavailable();
            return;
        }

        if (lic('isSportsbookOnMaintenance') && !phive('Micro/Sportsbook')->isLoggedInAsMaintenanceUser()) {
            $this->renderMaintenancePage();
            return;
        }

        $settings = phive('Sportsbook')->getSetting('altenar');

        $this->loadStaticFiles($settings['INTEGRATION_SCRIPT_SRC']);

        $token = null;

        if ($user) {
            $token = $this->fetchToken($user->getId()); 
        }

        if ($user && !$token) {
            $this->renderUnavailable();
            return;
        }

        ?>
        <div id="altenar-container"></div>

        <script>
            const integration = "<?= $this->getIntegrationName(); ?>";
            const token = <?= json_encode($token) ?>;

            initializeApp(integration, token);
        </script>
        <?php
    }

    private function loadStaticFiles(string $altenarScriptUrl): void
    {
        ?>
        <script src="<?= $altenarScriptUrl ?>"></script>
        <?php

        loadCss('/diamondbet/css/' . brandedCss() . 'altenar.css');
        loadJs('/phive/js/altenar.js');
    }

    private function fetchToken(string $userId): ?string
    {
        $settings = phive('Sportsbook')->getSetting('api_endpoints');

        $url = $this->buildGenerateTokenUrl($userId);
        $headers = ["X-API-KEY: " . $settings['API_KEY']];

        $response = phive()->get($url, '', $headers);

        return json_decode($response)->Token;
    }

    private function buildGenerateTokenUrl(string $userId): string
    {
        $settings = phive('Sportsbook')->getSetting('altenar');

        $endpoint = $settings['API_ROOT_URL'] . '/generate-token';
        $args = '?json=true&userId=' . $userId;

        return $endpoint . $args;
    }

    private function getIntegrationName(): string
    {
        $jurisdictionSpecificName = lic('getLicSetting', ['altenar_integration_name']);

        if (!empty($jurisdictionSpecificName)) {
            return $jurisdictionSpecificName;
        }

        return phive('Sportsbook')->getSetting('altenar')['INTEGRATION_NAME'];
    }

    private function renderRgPopup(DBUser $user): void
    {
        $game = ['tag' => 'sportsbook'];
        lic('beforePlay', [$user, 'flash', $game], $user);
    }

    private function renderUnavailable(): void
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

    private function renderMaintenancePage(): void
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
