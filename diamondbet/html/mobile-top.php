<?php
include_once('topcommon.php');
$mg 	= phive('QuickFire');
$loc 	= phive('Localizer');
$mobile_top = phive('Menuer')->forRender('mobile-top-menu', '', true);
$pager 	= phive('Pager');


$u = cuPl();

$top_logos = lic('topMobileLogos');
$displayMode = phive()->isMobileApp() ? 'display:none' : '';
?>

<!-- Sticky Footer Begin -->
<?php $hide_deposit = strpos($_SERVER['REQUEST_URI'], '/cashier/withdraw/') !== false || strpos($_SERVER['REQUEST_URI'], '/cashier/deposit/') !== false || strpos($_SERVER['REQUEST_URI'], '/rg-verify/') !== false?>
<?php if(!$pager->get('hide_bottom')): ?>
    <?php if(empty($u)): ?>
        <div id="bottom-sticky" style="<?= $displayMode ?>">
            <div class="bottom-sticky__level">

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
                <div onclick="<?php echo $registration_action ?>" class="bottom-sticky__level-left bottom-sticky__level-left--60">
                    <div class="bottom-sticky__level__item">
                        <div class="icon icon-vs-person-add login-icon"></div>
                        <span class="bottom-sticky-item__text">
                            <?= t('register') ?>
                        </span>
                    </div>
                </div>
                <div onclick="<?php echo $login_action ?>" class="bottom-sticky__level-right bottom-sticky__level-right--40">
                    <div class="bottom-sticky__level__item">
                        <div class="icon icon-vs-login login-icon"></div>
                        <span class="bottom-sticky-item__text">
                            <?= t('login') ?>
                        </span>
                    </div>
                </div>
            <? } elseif (isPNP()){ ?>
                <div onclick="<?php echo $pnp_action ?>">
                    <div class="bottom-sticky__level__item gradient-green">
                        <div class="icon icon-vs-login login-icon"></div>
                        <span class="bottom-sticky-item__text">
                            <?= t('paynplay.login') ?>
                        </span>
                    </div>
                </div>
            <? } ?>
            </div>
        </div>
    <?php elseif(!$hide_deposit && ($u->getBalance() < mc(phive('Config')->getValue('thresholds', 'show-deposit-btn', 1000), $u))): ?>
        <div id="bottom-sticky" style="<?=$displayMode?>">
            <div class="bottom-sticky__level">
                <?php if(!isPNP()){ ?>
                <div class="bottom-sticky__level__item bottom-sticky__level__item--green" onclick="goTo('<?php echo llink('/mobile/cashier/deposit/') ?>')">
                    <? } elseif (isPNP()){ ?>
                    <div class="bottom-sticky__level__item bottom-sticky__level__item--green" onclick="showPayNPlayPopupOnDeposit()">
                        <? } ?>
                        <span class="bottom-sticky-item__icon icon icon-vs-casino-coin"> </span>
                        <span class="bottom-sticky-item__text"><?= t('deposit') ?></span>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div id="bottom-sticky" style="<?=$displayMode?>">
            <?php include_once('mobile-bottom-menu.php'); ?>
        </div>
    <?php endif ?>
<?php endif ?>
<!-- Sticky Footer End -->
<div class="mobile-top gradient-normal" id="mobile-top" style="<?=$displayMode?>">
    <?php if(!empty($top_logos)): ?>
        <div class="top-logos gradient-normal">
            <?= lic('rgOverAge',['over-age-mobile logged-in-time']); ?>
            <?= lic('rgLoginTime',['rg-top__item logged-in-time']); ?>
            <?php echo $top_logos ?>
        </div>
    <br clear="all"/>
    <?php endif ?>
    <?php if($_SESSION['show_go_back_to_bos']):?>
    <script>
        setTimeout(function(){
            hideBackToBoSBar();
        }, 3000);
    </script>
    <div id="mobile-top__back-to-battle-of-slots" class="mobile-top__back-to-battle-of-slots">
        <div id="mobile-top__back-to-battle-of-slots-inner" class="mobile-top__back-to-battle-of-slots-inner">
            <a class="mobile-top__back-to-battle-of-slots-link" href="<?php echo $_SESSION['newsite_go_back_url']; ?>">
                <?php et('mp.back.to.battle.of.slots'); ?>
            </a>
        </div>
        <div class="mobile-top__back-to-battle-of-slots-inner-close">
            <span id="mobile-top__back-to-battle-of-slots-close-icon" class="mobile-top__back-to-battle-of-slots-close-icon">
                <span class="icon icon-vs-close"></span>
            </span>
        </div>
    </div>
    <?php endif; ?>
    <?php include_once('mobile-top-menu.php') ?>

</div>

<?php   include_once('mobile-top-menu.php');   ?>
