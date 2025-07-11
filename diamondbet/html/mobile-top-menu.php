<?php

$play_extra_class = phive('Pager')->getId() == 326 ? 'vs-mobile-menu__item--active icon icon-vs-joystick' : 'icon icon-vs-joystick';
$favs_extra_class = $_SERVER['REQUEST_URI'] === llink('/mobile/favourites/') ? 'vs-mobile-menu__item--active icon icon-vs-star' : 'icon icon-vs-star';

?>

<nav class="vs-mobile-menu__container">
    <div class="vs-mobile-menu__part vs-mobile-menu__part--left">
        <span class="vs-mobile-menu__item vs-mobile-menu__item-menu icon icon-vs-hamburger" onclick="toggleMenu()"></span>
        <a href="<?php echo llink('/mobile/') ?>">
            <img class="mobile-logo" src="/diamondbet/images/<?= brandedCss() ?>mobile/logo.png">
        </a>
        <a href="<?php echo llink(phive('Pager')->getPath(326)) ?>">
            <span class="vs-mobile-menu__item vs-mobile-menu__item-games <?php echo $play_extra_class ?>"></span>
        </a>

        <?php if(isLogged()): ?>
            <a href="<?php echo llink('/mobile/favourites/') ?>">
                <span class="vs-mobile-menu__item vs-mobile-menu__item-games <?php echo $favs_extra_class ?>"></span>
            </a>
        <?php endif ?>
    </div>

    <div class="vs-mobile-menu__part vs-mobile-menu__part--right">
        <?php // If needed, we will show the Occupation and gambling budget popup before the user is redirected to the mobile BoS ?>
        <?php if(hasMobileMp()): ?>
            <img id="link_to_mobile_bos" class="vs-mobile-menu__item-bos-logo" src="<?php fupUri('vs-battleofslots-logo.png'); ?>" width="42px" height="16px">
        <?php endif;?>

        <?php if(!isLogged()): ?>
            <?
            $registration_page = phive('DBUserHandler')->getSetting('registration_path', 'registration1');
            $is_auth_allowed = phive('DBUserHandler')->isRegistrationAndLoginAllowed();
            $login_action = $is_auth_allowed ? 'checkJurisdictionPopupOnLogin()' : 'showAccessBlockedPopup()';
            $registration_action = $is_auth_allowed ? "checkJurisdictionOnRegistration('/$registration_page/')" : 'showAccessBlockedPopup()';
            if (isBankIdMode()) {
                loadJs("/phive/modules/DBUserHandler/js/registration.js");
                $registration_action = $login_action = $is_auth_allowed ? "licFuncs.startBankIdRegistration('registration')" : 'showAccessBlockedPopup()';
            }
            $pnp_action = $is_auth_allowed ? 'showPayNPlayPopupOnLogin()' : 'showAccessBlockedPopup()';
            if(!isPNP()){
            ?>
                <a href="#" onclick="<?php echo $registration_action ?>">
                    <span class="vs-mobile-menu__item vs-mobile-menu__item-register icon icon-vs-person-add"></span>
                </a>

                <a href="#" onclick="<?php echo $login_action ?>">
                    <span class="vs-mobile-menu__item vs-mobile-menu__item-login icon icon-vs-login mobile-gradient-login-btn"></span>
                </a>
            <? } elseif (isPNP()){ ?>
                <!-- PayNPlay button -->
                <a href="#" onclick="<?php echo $pnp_action ?>">
                    <span class="vs-mobile-menu__item vs-mobile-menu__item-login icon icon-vs-login"></span>
                </a>
            <? } ?>
        <?php endif ?>

        <?php if(isLogged()): ?>
            <?php //fastDepositIcon() ?>
        <?
        if(!isPNP()) { ?>
            <span class="vs-mobile-menu__item vs-mobile-menu__item-deposit icon icon-vs-casino-coin"
               onclick="<?= depGo() ?>">
            </span>
        <? }  elseif (isPNP() && isLogged()) { ?>
        <!-- PayNPlay button -->
             <span class="vs-mobile-menu__item vs-mobile-menu__item-deposit icon icon-vs-casino-coin"
                onclick="showPayNPlayPopupOnDeposit()">
             </span>
        <? }  elseif (isPNP() && !isLogged()) { ?>
            <!-- PayNPlay button -->
            <span class="vs-mobile-menu__item vs-mobile-menu__item-deposit icon icon-vs-casino-coin"
                  onclick="showPayNPlayPopupOnLogin()">
             </span>
        <? }
            $award_count = phive('Trophy')->getUserAwardCount($user, array('status' => 0, 'mobile_show' => 1)); ?>
            <?php if(phive('UserHandler')->getSetting('has_notifications') == true && !empty($award_count)): ?>
                <span class="vs-mobile-menu__item vs-mobile-menu__item-rewards icon icon-vs-gift"
                      onclick="goTo('<?php echo llink('/mobile/account/'.cuPlId().'/my-prizes/') ?>')">
                    <div id="notification-count" class="mobile_reward-count btn-cancel-default-l"><?php echo $award_count ?></div>
                </span>
            <?php endif ?>
        <?php endif ?>
    </div>

</nav>
