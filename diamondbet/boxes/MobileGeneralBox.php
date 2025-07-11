<?php

require_once __DIR__ . '/../../phive/modules/BoxHandler/boxes/diamondbet/MobileRegBoxBase.php';

class MobileGeneralBox extends MobileRegBoxBase
{
    function printHTML()
    {
        loadCss("/diamondbet/css/" . brandedCss() . "all.css");
        loadCss("/diamondbet/css/" . brandedCss() . "mobile.css");

        $route = phive('Pager')->getLastLvl();

        switch ($route) {
            case 'login':
                $this->printLogin();
                break;
            case 'rg-deposit':
                $this->printRgBox('dep_lim_info_box');
                break;
            case 'rg-login':
                $this->printRgBox('login_limit_info_box');
                break;
            case 'rg-occupation':
                $this->printRgBox('spending_amount_box');
                break;
            case 'rg-change-deposit-before-play':
                $this->printRgBox('change_deposit_limit_before_gameplay_popup');
                break;
            case 'rg-activity':
                $this->printRgBox('rg_info_box');
                break;
            case 'rg-net-deposit-info':
                $this->printRgBox('net_deposit_info_box');
                break;
            case 'rg-verify':
                $this->printRgBox('verification_box');
                break;
            case 'customer-service':
                $this->printContactUsBox();
                break;
        }

        loadJs("/phive/js/jquery.cookie.js");
        loadJs("/phive/js/emptydob.js");
        drawFancyJs();
    }

    function printLogin()
    {
        ?>
        <style>
            #cookie-banner {
                display: none !important;
            }
        </style>
        <?

        moduleHtml('DBUserHandler', 'get_login');
    }

    function printContactUsBox() {
        moduleHtml('DBUserHandler', 'get_contactUs');
    }

    function printTopLogos() {
        if (!empty($top_logos = phive()->isMobile() ? lic('topMobileLogos') : false)) {
            ?>
            <script>licFuncs.setTopMobileLogos(true)</script>
            <div class="top-logos gradient-normal">
                <?= lic('rgOverAge',['over-age-mobile logged-in-time']); ?>
                <?= lic('rgLoginTime', ['rg-top__item logged-in-time']); ?>
                <?php echo $top_logos ?>
            </div>
            <br clear="all"/>
            <?
        }
    }

    function printRgBox($type)
    {
        $this->printTopLogos();
        moduleHtml('Licensed', $type);
    }
}
