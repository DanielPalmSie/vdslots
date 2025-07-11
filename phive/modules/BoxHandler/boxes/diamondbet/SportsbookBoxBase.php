<?php

require_once __DIR__ . '/../../../../../diamondbet/boxes/DiamondBox.php';

class SportsbookBoxBase extends DiamondBox
{

    private $mobile_route = "";

    function init()
    {
        $should_add_mobile_routing = empty(phive('Pager')->getSetting('device_dir_mapping'));
        if ($should_add_mobile_routing && phive()->isMobile()) {
            $this->mobile_route = "/mobile";
        }
    }

    public function is404($args)
    {
        return false;
    }

    /**
     * Print the main content displayed on the box.
     * The HTML gets loaded from the vuejs project.
     */
    public function printHTML()
    {
        if (empty(lic('isSportsbookEnabled'))) {
            $this->notAvailableHtml();
            return;
        }

        if (lic('isSportsbookOnMaintenance') && !phive('Micro/Sportsbook')->isLoggedInAsMaintenanceUser ()) {
            $this->onMaintenanceHtml();
            return;
        }

        if (phive()->isMobile()) {
            phive('Menuer')->renderSecondaryMobileMenu();
        }

        if (phive('Sportsbook')->getSetting('frontend_remote', false)) {
            if (($content = file_get_contents(phive('Sportsbook')->getSetting('frontend_url'))) === false) {
                $error = error_get_last();
                phive('Logger')->getLogger('sportsbook')->critical(
                    sprintf('Cannot get content of Sportsbook in: %s/%s::%s',
                        __DIR__,
                        __CLASS__,
                        __FUNCTION__
                    ),
                    ['error' => $error['message']]
                );
            } else {
                echo $content;
            }
        } else {
            include(phive('Sportsbook')->getSetting('frontend_url'));
        }
    }

    private function notAvailableHtml() {
        ?>
        <div class="frame-block">
            <div class="frame-holder">
                <h1><?php et('404.header') ?></h1>
                <?php et("404.content.html") ?>
            </div>
        </div>
        <?php
    }

    private function onMaintenanceHtml() {
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
