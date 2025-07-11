<?php

include_once('display.php');

/** @var DBUserHandler $uh */
$uh = phive('UserHandler');
$mg = phive('Casino');
$cashier = phive('Cashier');
$loc = phive('Localizer');
$pager = phive('Pager');


$mg->getHomeRedirectInIframe();

if(phive()->isMobile() && isset($_GET['ad']) && $_GET['ad'] === 'ext-browser') {
?>
<div id="fb-browser-popup" style="display:none;">
    <div class="fb-browser-popup-content">
        <h2><?php et('fb-popup-sub-title') ?></h2>
        <p><?php et('fb-popup-description') ?></p>
        <a href="https://<?php echo htmlspecialchars($_SERVER['HTTP_HOST'], ENT_QUOTES, 'UTF-8') . $pager->removeQueryParams(['ad']); ?>" target="_blank" rel="noopener noreferrer" class="mbox-ok-btn btn btn-l btn-default-l">
            <?php et('fb-popup-btn') ?>
        </a>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        addToPopupsQueue(function () {
          mboxMsg($("#fb-browser-popup").html(), false, undefined, undefined, false, ...Array(7));
        })
    });
</script>
<?php
}
if (!empty($redirect = $_GET['global_redirect'])) {
    switch ($redirect) {
        case "rg":
            $lang = phive('Localizer')->getLanguage();
            ?>
            <script>window.location.href = "<?php echo phive('Licensed')->getRespGamingUrl(cu(), $lang); ?>"</script>
            <?
            break;
        default:
    }
}

if(phive('Tournament')->hasBosSearchLink()) {
    ?>
        <script type="text/javascript">
            $(document).ready(function(){

                var searchtype = '<?php echo phive('Tournament')->getMobileBosSearchType($_GET['bos_category'], $_GET['bos_start_format']); ?>';

                if(isMobile()) {
                    window.location.href="/mobile/battle-of-slots/?search_type=" + searchtype;
                } else {
                    showMpBox('/tournament');
                }
            });
        </script>
    <?php
}

if (!empty($_POST['login_username'])){

    $login_res = phive('UserHandler')->login($_POST['login_username'], $_POST['login_password'], true);

    if($login_res == false){
        $login_msg = t('login.error');
    }elseif($login_res == 'go_to_step_two') {
        $login_msg = '';
    }
    else if(is_string($login_res)){ // if it is my string, show the form and send the SMS
        if($login_res == 'sms-on-login'){
            //$login_msg = 'SMS validation';
            unset($login_msg);
        } elseif($login_res == 'fillin-dob') {
            $login_msg = '';
        } elseif($login_res == 'rg_login_limit_reached') {
            $lim = rgLimits()->getSingleLimit(cu(), 'login')['resets_at'];
            $login_msg = tAssoc('blocked.rg_login_limit_reached.html', [1 => $lim]);
        } else {
            $login_msg = t("blocked.$login_res.html");
        }
    }
    else{
        // No need to redirect since we don't do the login=true thing anymore.
        // Only on mobile to refresh the menu or if current language for some reason is different from preferred_lang.
        $pref_lang = cuPlAttr('preferred_lang');
        if(cLang() != $pref_lang || phive()->isMobile())
            jsGoTo('/'.$pref_lang.'/'.(siteType() != 'normal' ? 'mobile/' : ''));
    }
}else{
    if(!empty($_GET['signout'])){
?>
  <script>
      sessionStorage.setItem("clear_zendesk_widget","1");
      jsReloadBase();
  </script>
<?php
  }
}

drawFancyJs();

$user = cu();

if(!empty($user)){

    if($user->isBlocked()) {
        phive('UserHandler')->logout('blocked');
    }

    lic('onLoggedPageLoad', [$user], $user);

    $GLOBALS['balances'] = phive('Casino')->balances();

    // Show a popup to let the user fill in his date of birth if we don't have it already
    if($user->getAttribute('dob') == '0000-00-00') {
        showEmptyDobBox();
    }

    $err = [];
    if (!phive('DBUserHandler')->validateZipcode($err, $user->getCountry(), $user->getAttr('zipcode')) && $user->hasCurTc() && $user->hasCurPp()) {
        showEmptyDobBox('zipcode');
    }
    /* "rg_login_info" popup must be showed right after login (Ex. SE players) and takes priority compared
       to RG popups, plus at the moment showing 2 popup at the same time is not properly supported */
    if (empty($_GET['rg_login_info'])) {
        // First we show popup that enforce a limit, after the one that may logout the player.
         $forced_rg_popups_question = ['ask_bet_too_high', 'ask_play_too_long'];
         $forced_rg_popups_limits = ['force_max_bet_protection', 'force_login_limit'];
         $forced_rg_popups_logout = ['ask_gamble_too_much'];

         foreach (array_merge($forced_rg_popups_question, $forced_rg_popups_limits,
             $forced_rg_popups_logout) as $setting) {
             if ($user->hasSetting($setting)) {
                 // we are displaying the set_limit_popup
                 if (in_array($setting, $forced_rg_popups_limits)) {
                     ?>
                     <script>
                         var extra = {
                             closebtn: 'no',
                             btntype: 'none',
                             boxtype: 'set_limit'
                         };
                         rgSimplePopup('<?=$setting?>', function () {
                             // console.log('enforced set limit');
                         }, null, extra);
                     </script>
                     <?php
                 } else {
                     ?>
                     <script>
                         // show yes/no popup
                         rgDialogPopup('<?= $setting ?>', function (res) {
                             var extra = {
                                 closebtn: 'no',
                             };
                             if (res.action_type === 'set_limit_popup') {
                                 extra.btntype = 'none';
                                 extra.boxtype = 'set_limit';
                             }
                             // trigger next action (if any)
                             if (!empty(res.next_action)) {
                                 rgSimplePopup(res.next_action, function () {
                                     // Redirect to home page after click OK.
                                     if (res.action_type === 'logout_popup') {
                                         gotoLang('/');
                                     }
                                     // Set limit popups have custom actions on "set limit button" click
                                     // we can add a common callback onClose here (If needed)
                                     if (res.action_type === 'set_limit_popup') {
                                     }
                                 }, null, extra);
                             }
                         }, null, null, {closebtn: 'no'});
                     </script>
                     <?php
                 }
                 // We display only 1 popup at a time
                 // if more than 1 is requested, the next one will be displayed on next page load.
                 break;
             }
         }
     }

    $do_force_deposits = phive('UserHandler')->doForceDeposit();
    if (lic('doIntendedGambling')) {
        lic('intendedGambling');
    } elseif(!$do_force_deposits && lic('shouldAskForCompanyDetails')) {
    ?>
        <script>
            addToPopupsQueue(function () {
                lic('showCompanyDetailsPopup');
            });
        </script>
    <?php
    } elseif($IdScanVerificationPath = lic('IdScanVerificationRedirect', [$user], $user)){
    ?>
        <script>
            $(document).ready(function(){
                goTo("<?= $IdScanVerificationPath ?>");
            });
        </script>

    <?php
    } elseif (!phive()->isMobile() && $do_force_deposits) {
        $redirectUrl = phive('CasinoCashier')->generateRedirectUrl();
        ?>
        <script>
            $(document).ready(function() {
                addToPopupsQueue(function () {
                    if (registration_mode === 'paynplay') {
                        showPayNPlayPopupOnDeposit();
                    } else {
                        mboxDeposit('<?php echo $redirectUrl ?>');
                    }
                });
            });
        </script>
        <?php
    }
    /* verification and deposit modal for DE */
    elseif (lic('shouldRedirectToVerificationModal', [$user], $user)) {
        if (phive()->isMobile()) {
            ?>
            <script>
                $(document).ready(function(){
                    goTo("<?= lic('getVerificationModalUrl', [true, true]) ?>")
                });
            </script>
            <?php
        } else {
            lic('handleRgLimitPopupRedirection', [$user, 'flash', 'gbg_verification']);
        }
    } elseif (!$do_force_deposits && phive('DBUserHandler')->showNationalId($user->getAttribute('country'), 'popup') && !$user->hasAttr('nid') && !$user->isTestAccount() && $user->hasCurTc() && $user->hasCurPp()) {
        showEmptyDobBox('nid'); //We show this after the deposit
    } elseif(isPNP() && $do_force_deposits && phive()->isMobile()) {
        ?>
        <script>
            $(document).ready(function(){
                addToPopupsQueue(function () {
                    showPayNPlayPopupOnDeposit();
                });
            });
        </script>
        <?php
    }


    ?>

    <?php
        if($uh->isUserEnforcedToDocumentVerification($user, $_SERVER['REQUEST_URI'])):

            if(empty($_SESSION['restricted_redirected_once_to_docs'])):
                $_SESSION['restricted_redirected_once_to_docs'] = true;
            ?>
                <script>
                     goTo('<?php echo llink($user->accUrl('documents')); ?>');
                </script>
            <?php
            endif;
            if(!empty($user->getSetting('sowd-enforce-verification')) && !empty($_SESSION['restricted_msg_shown'])):
                if(empty($_SESSION['sowd-enforce-url'])) {
                    $source_of_funds = phive('CasinoCashier')->getPendingSourceOfFunds($user);
                    if(!empty($source_of_funds)) {
                        $_SESSION['sowd-enforce-url'] = '/sourceoffunds1/?document_id='.$source_of_funds['id'];
                    }
                }
                showSourceOfFundsBox($_SESSION['sowd-enforce-url']);
            endif;
        endif;

    if ($_SESSION['show_successful_idscan_verification'] && !$user->hasSetting('id_scan_failed')) {
        ?>
        <script>
            addToPopupsQueue(function () {
                lic('showSuccessfullIdscanPopup', []);
            });
        </script>
        <?php
    }
    if ($_GET['show_occupation'] && !$user->hasSetting('viewed-resp-gaming')) {
        ?>
        <script>
            addToPopupsQueue(function () {
                var extraOptions = isMobile() ? {} : {width: <?= isPNP() ? 450 : 550 ?>, enableScrollbar: true};
                var options = {
                    module: 'Licensed',
                    file: 'spending_amount_box',
                    gid: '<?= $_GET['gid'] ?? 0 ?>'
                };
                extBoxAjax('get_raw_html', 'spending-amount-box', options, extraOptions);
            });
        </script>
        <?php
    }

    if ($user->hasSetting('show_occupation_popup')) {
        ?>
        <script>
            addToPopupsQueue(function () {
                var extraOptions = isMobile() ? {containerClass: 'flex-in-wrapper-popup button-fix--mobile'} : {width: 500 };
                var options = {
                    module: 'Licensed',
                    file: 'occupations_popup',
                    gid: '<?= $_GET['gid'] ?? 0 ?>'
                };
                extBoxAjax('get_raw_html', 'occupation-popup-box', options, extraOptions);
            });
        </script>
        <?php
    }

    if ($user->hasSetting('currency_changed_from') && $user->hasSetting('currency_changed_to')) {
        ?>
        <script>
            addToPopupsQueue(function () {
                var extraOptions = isMobile() ? {containerClass: 'flex-in-wrapper-popup button-fix--mobile'} : { width: 450 };
                var options = {
                    module: 'DBUserHandler',
                    file: 'currency_changed_popup'
                };
                extBoxAjax('get_raw_html', 'currency-changed-popup', options, extraOptions);
            });
        </script>
        <?php
    }

    if ($_SESSION['account_verification_reminder']) {
        ?>
        <script>
            addToPopupsQueue(function () {
                lic('showVerifyIdentityReminder', []);
            });
        </script>
        <?php
        lic('stopShowingReminderPopup',[]);
    }
    if ($_SESSION['account_verification_overtime']) {
        ?>
        <script>
            addToPopupsQueue(function () {
                lic('showAccountVerificationOvertime', []);
            });
        </script>
        <?php
        lic('stopShowingReminderPopup',['account_verification_overtime']);
    }

    if ($_SESSION['show_add_province_popup']) {
        ?>
        <script>
            addToPopupsQueue(function () {
                lic('showProvincePopup', []);
            });
        </script>
        <?php
    }

    lic('checkNationalityBirthCountrySession', [$user]);
    if ($_SESSION['show_add_nationalityandpob_popup'] && cu()->hasDeposited()) {
        ?>
        <script>
            addToPopupsQueue(function () {
                lic('showNationalityAndPOBPopup', []);
            });
        </script>
        <?php
    }


    if ($_SESSION['show_responsible_gaming_message_popup']) {
        if(strpos($_SERVER['REQUEST_URI'], '/responsible-gambling/') === false) {
            ?>
            <script>
                addToPopupsQueue(function () {
                    lic('showResponsibleGamingMessagePopup', []);
                });
            </script>
            <?php
        }
    }

    if ($_SESSION['show_add_limits_popup'] && cu()->hasDeposited()) { //
        ?>
        <script>
            addToPopupsQueue(function () {
                lic('showLimitConfirmationPopup', []);
            });
        </script>
        <?php
    }

    if ($_SESSION['show_add_two_factor_authentication_popup']) {
        ?>
        <script>
            addToPopupsQueue(function () {
                lic('showTwoFactorAuthenticationPopup', []);
            });
        </script>
        <?php
    }

    if ($_SESSION['idscan_failed_expiry_date']) {
        ?>
        <script>
            addToPopupsQueue(function () {
                lic('showFailedExpiryDatePopup', []);
            });
        </script>
        <?php
    }

    if ($user->hasSetting('id_scan_failed_login_redirect')) {
        ?>
        <script>
            goTo('<?php echo llink($user->accUrl('documents')); ?>');
        </script>
        <?php
        $user->deleteSetting('id_scan_failed_login_redirect');
    }

    $isTrustlySelectAccountSuccessResponse = (
        $_GET['action'] === 'select_account'
        && $_GET['success'] === 'true'
        && !isset($_GET['redirected'])
    );

    $trustly_withdraw_config = $cashier->getPspSetting('trustly', 'withdraw');
    if (
        !$isTrustlySelectAccountSuccessResponse
        && strpos($_SERVER['REQUEST_URI'], '/cashier/withdraw/') !== false
        && empty($_GET['end'])
        && $trustly_withdraw_config['active']
        && in_array($user->getCountry(), $trustly_withdraw_config['included_countries'])
        && $user->hasSetting('show_trustly_withdrawal_popup')
        && $cashier->canWithdraw($user)['success']
    ) {
        ?>
        <script>
            addToPopupsQueue(function() {
                showTrustlyWithdrawalPopup();
            });
        </script>
        <?php
    }

    $deposit_limit_warning = phive('DBUserHandler/RgLimits')->getDepositLimitWarning();
    if (strpos($_SERVER['REQUEST_URI'], '/mobile/cashier/deposit/') !== false && $deposit_limit_warning) {
        ?>
        <script>
            addToPopupsQueue(function() {
                depositLimitMessage("<?php echo $deposit_limit_warning; ?>")
            });
        </script>
        <?php
    }
}

$new_version_jquery_ui = phive('BoxHandler')->getSetting('new_version_jquery_ui') ?? '';

loadJs("/phive/js/jQuery-UI/".$new_version_jquery_ui."jquery-ui.min.js");
loadJs("/phive/js/jquery.validate.min.js");
loadJs("/phive/js/jquery.validate.password.js");
if(phive('DBUserHandler')->getSetting("new_registration")) {
    loadJs("/phive/modules/DBUserHandler/html/registration_new.js");
    loadJs("/phive/js/privacy_dashboard.js");
    loadJs("/phive/js/license.js");
} else {
    loadJs("/phive/modules/DBUserHandler/html/registration.js");
}
loadJs("/phive/js/sourceoffunds.js");
loadJs("/phive/js/emptydob.js");
loadJs("/phive/js/privacy_dashboard.js");
$show_msg = $_GET['show_msg'] ?? $_SESSION['show_msg'];
unset($_SESSION['show_msg']);
?>

<?php if (!empty($_GET['showTournamentBox'])): ?>
    <script>

        const urlParams = new URLSearchParams(window.location.search);

        showMpBox('/tournament/');

        const cleanUrl = window.location.origin + window.location.pathname;
        window.history.replaceState({}, document.title, cleanUrl);
    </script>
<?php endif ?>

<?php if(!empty($show_msg)): ?>
    <script>
         mboxMsg('<?php et($show_msg) ?>', true);
    </script>
<?php endif ?>

<?php if (!empty($user) &&
    !empty($game_tag = $_SESSION['locked_game_popup']) &&
    ((lic('getLicSetting', ['gamebreak_24']) || lic('getLicSetting', ['gamebreak_indefinite'])) || !empty($user->getRgLockedGames())))
{
    unset($_SESSION['locked_game_popup']);

    $game_category = $user->extractCategoryFromLockedGame($game_tag);
    $game_category = phive('Localizer')->getPotentialString($game_category[0]['name']);
    $game_locked_description_alias = lic('getLicSetting', ['gamebreak_indefinite'])
        ? 'game-category-block-indefinite.blocked.info'
        : 'game-category.locked.info';
    ?>

    <script>
        var title = '<?= t('game-category.locked.title') . ': ' . $game_category ?>';
        var description = '<?= t($game_locked_description_alias) ?>';
        var categoryLockedImage = !is_old_design ? 'responsible-gaming/locked-game-category.png' : '';

        mboxMsg(description, true, 'mboxClose()', undefined, true, false, title, undefined, undefined, undefined, undefined, 'rg-locked-game-category__popup-container', undefined, categoryLockedImage);
    </script>
<?php } ?>

<?php
    if(!empty($type = $_GET['rg_login_info']) && isLogged()):
        $desktop_popup_width = $type === 'change-deposit-before-play' ? 400 : 800;
        $boxPopupID = isPNP() ? 'pnp-gaming-experience-box' : 'rg-login-box';
?>
    <script>
        var extraOptions = isMobile() ? {} : {width: <?php echo $desktop_popup_width ?>};
        extBoxAjax('get_login_rg_info', '<?php echo $boxPopupID ?>', {rg_login_info: '<?php echo $type ?>'}, extraOptions);
    </script>
<?php endif ?>

<?php
if (!empty($user) && $user->hasSetting('nationality_required')) {
    ?>
    <script>
        addToPopupsQueue(function () {
            lic('showNationalityPopup', []);
        });
    </script>
    <?php
}

if (lic('pnpRegistrationInProgress') && !phive('Permission')->hasAnyPermission($user)) {
    ?>
    <script>
        addToPopupsQueue(function () {
            PayNPlay.showUserDetailsPopup();
        });
    </script>
    <?php
}
?>

<?php if (!empty($_GET['show_reg_step_2'])) {
    if (empty($_SESSION['reg_uid'])) {
        return;
    }
    $user_object = cu($_SESSION['reg_uid']);
    if (empty($user_object)) {
        return;
    }

    $res = phive('UserHandler')->showRegistrationStep2($user_object, '', '', false);
    if (!empty($res['method'])) {
        ?>
        <script>window.top['<?=$res['method']?>'].apply(null, <?= json_encode($res['params']) ?>);</script>
        <?php
    }
}

if (!isLogged() && !empty($_GET['action']) && $_GET['action'] === 'login-regular') {
    ?>
    <script>
        checkJurisdictionPopupOnLogin();
    </script>
    <?php
}

if (!empty($_SESSION['login_res']) || !empty($_SESSION['login_action'])) {
    $session_login_res = $uh->getLoginAjaxContextRes($_SESSION['login_res'], $_SESSION['login_action']);
    unset($_SESSION['login_res']);
    unset($_SESSION['login_action']);
    ?>
    <script>
        loginCallback(<?= json_encode($session_login_res) ?>)
    </script>
    <?php
}
?>

<?php if(!empty($_GET['email_code'])): ?>

  <?php
  // Check if the user has finished step 2 of the registration
    if(!empty($_GET['uid'])) {
        $uuid = $_GET['uid'];
        $user_id = phive('SQL')->shs()->getValue("SELECT user_id FROM users_settings WHERE setting = 'hashed_uuid' AND value = '$uuid' ");
        $user_object = cu($user_id ?: $uuid);
          if(!phive('UserHandler')->hasFinishedRegistrationStep2($user_object)) {
              phive('UserHandler')->showRegistrationStep2($user_object, '', $_GET['email_code'], false);
          } elseif (lic('doIntendedGambling')) {
              lic('intendedGambling');
          } else {
              // show email verified message
              ?>

            <div id="email-verified-msg" style="display:none;">
                <?php et("email.verified.success.html") ?>
                <br/>
                <br/>
                <br/>
                <br/>
                <center>
                  <button onclick="mboxDeposit('/cashier/deposit/', false, true)" class="btn btn-l btn-default-l w-125 neg-margin-top-25">
                    <?php echo 'OK' ?>
                  </button>
                </center>
              </div>
              <script>
               $(document).ready(function(){
                   addToPopupsQueue(function () {
                       fancyShow($("#email-verified-msg").html(), [300, 150], function () {
                           mboxDeposit('/cashier/deposit/', false, true);
                       });
                   });
               });
              </script>

              <?php
          }
      }
  ?>

<?php endif ?>


<?php if(!empty($_SESSION['sms-login-token']) && !isLogged()){ ?>
  <script>
    function smsAjaxLogin() {
      var code = $('#sms-login-code').val();
      mgAjax({
        'action': 'sms-login',
        'code': code
      }, function(ret) {
        var result = JSON.parse(ret);
        if(result.success == true) {
          window.location.href = "/";
        } else {
          if(result.action == 'restart-login') {
            window.location.href = "/?signout=true";
          }
          $('#sms-login-err').html('Wrong code, try again please.');
        }
      })
    }
  </script>
<div id="sms-on-login">
  <?php et('login.sms.code') ?>
  <div id="sms-login-err"></div>
  <center>
      <table>
        <tr>
          <td>
            <input class="input-normal" id="sms-login-code" type="text">
          </td>
        </tr>
      </table>
      <br/>
      <br/>
      <br/>
      <button onclick="smsAjaxLogin()" class="btn btn-l btn-default-l w-125 neg-margin-top-25">
        <?php echo 'Submit' ?>
      </button>
  </center>
</div>
<script>
  $(document).ready(function(){
    fancyShow($("#sms-on-login"), [300, 150], function(){
    });
  });
</script>
<?php } ?>

<?php if(empty($_SESSION['mg_username'])): ?>
<script>
  var logged_in = false;
</script>
<?php else: ?>
<script>
  var logged_in = true;
</script>
<?php endif ?>

<?php if(!empty($_SESSION['mg_username']) && phive()->getSetting('lga_reality') === true && $user->hasSetting('dep-period') && empty($_POST['login_username'])):
  $user->deleteSetting('dep-lim');
$user->deleteSetting('dep-period');
$user->deleteSetting('dep-lim-unlock-date');
$go_link = "/account/{$_SESSION['mg_username']}/responsible-gambling/";
if($GLOBALS['site_type'] == 'mobile')
  $go_link = "/mobile".$go_link;
?>
  <div id="new-dep-msg" style="display:none;">
    <?php et("new.deplim.info.html") ?>
    <br/>
    <br/>
    <br/>
    <br/>
    <center>
      <button onclick="goTo('<?php echo llink($go_link) ?>')" class="btn btn-l btn-default-l w-125 neg-margin-top-25">
        <?php et('responsible.gambling') ?>
      </button>
    </center>
  </div>
  <script>
   $(document).ready(function(){
     fancyShow($("#new-dep-msg").html(), [300, 200]);
   });
  </script>
<?php elseif(isLogged() && empty($type = $_GET['rg_login_info']) && $user->getSetting('pwd_changed') == 'yes'): ?>
    <?php if($GLOBALS['site_type'] != 'mobile' || strpos($_SERVER['REQUEST_URI'], '/mobile/message/') === false): ?>
        <?php
        function createPasswordField($id, $placeholderKey, $autocomplete) {
            $placeholder = t($placeholderKey);
            ?>
            <div class="pwd-container">
                <input id="<?= $id ?>" autocomplete="<?= $autocomplete ?>" type="password" placeholder="<?= htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8'); ?>" class="input-normal hasUpper hasLower hasTwoDigits lic-mbox-input">
                <div class="input-icon"></div>
            </div>
            <?php
        }
        ?>
        <div id="new-pwd-msg" style="display:none;">
            <div id="new-pwd-msg-content">
                <center>
                    <?php et("new.pwd.info.html") ?>
                    <?php if(lic('isForgotPasswordUpgradeEnabled')) {
                        createPasswordField('new-pwd1', 'register.password', 'new-password');
                        createPasswordField('new-pwd2', 'register.secpassword', 'repeat-password');
                    } else {
                        ?>
                        <table class="new-pwd-table">
                            <tr>
                                <td>
                                    <?php et('register.password') ?>
                                </td>
                                <td>
                                    <?php dbInput('new-pwd1', '', 'password', 'input-normal') ?>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <?php et('register.secpassword') ?>
                                </td>
                                <td>
                                    <?php dbInput('new-pwd2', '', 'password', 'input-normal') ?>
                                </td>
                            </tr>
                        </table>
                        <?php
                    }
                    ?>
                    <br/>
                    <div class="register-big-btn">
                        <div id="submit-pwd" class="password-big-btn-txt"><?php et('submit') ?></div>
                    </div>
                </center>
            </div>
            <div id="infotext" class="errors"></div>
        </div>
    <?php endif ?>
    <script>
        function submitPwd(){
            $("#submit-pwd").click(function(){
                var pwd1 = $("#new-pwd1");
                var pwd2 = $("#new-pwd2");
                if(pwd1.val() != pwd2.val())
                    $("#infotext").html("<?php et('register.err.password2'); ?>");
                else{
                    mgAjax({action: 'new-pwd', pwd: pwd1.val()}, function(ret){
                        if(ret === 'ok'){
                            $.multibox('close', 'mbox-msg', function () {
                                mboxMsg('<?php et('password.changed.successfully');  ?>', true, '', 300, true, false, ...Array(5), 'new-pwd-success-container');
                            });
                            $("#back-btn-cont").show();
                            $("#infotext").html("");
                        }else
                            $("#infotext").html(ret);
                    });
                }
                return false;
            });
        }

        $(document).ready(function(){
            var html = $("#new-pwd-msg").html();
            $("#new-pwd-msg").remove();
            mboxMsg(html, false, undefined, undefined, false, ...Array(6), 'new-pwd-popup-container');
            submitPwd();
        });
    </script>
<?php elseif((!empty($_GET['signup']) || $_GET['showlight'] == 'registration' || $_SESSION['show_signup'] === true) && !isLogged()): ?>
  <script>
   $(document).ready(function(){
       if (registration_mode === 'bankid') {
           licFuncs.startBankIdRegistration('registration');
       } else {
           showRegistrationBox(registration_step1_url);
       }
    });
  </script>
  <?php unset($_SESSION['show_signup']) ?>
<?php elseif (lic('doIntendedGambling')):
    lic('intendedGambling');
elseif (
    !empty($_GET['show_deposit'])
    && isLogged()
    && phive('DBUserHandler/PrivacyHandler')->hasPrivacySettings(cu())
):
    $_GET['signup'] = true;
    $_SESSION['nocash_shown'] = true;
    $redirectUrl = phive('CasinoCashier')->generateRedirectUrl();
?>
  <script>
   $(document).ready(function(){
       addToPopupsQueue(function () {
            mboxDeposit('<?php echo $redirectUrl ?>');
       });
   });
  </script>
<?php elseif(!empty($login_msg)):
    $login_msg = addslashes($login_msg);
  $_GET['signup'] = true;
?>
  <script>
    $(document).ready(function(){
        fancyShow('<?php echo $login_msg ?>');
    });
  </script>
<?php elseif(!empty($_GET['show_sign'])):
  $_GET['signup'] = true;
  $sign_string = phive("Config")->getValue('sign', 'string');
?>
  <script>
    $(document).ready(function(){
      fancyShow("<?php et($sign_string) ?>");
    });
  </script>
<?php elseif(!empty($_GET['tournament_cancelled'])): ?>
  <div id="tournament-cancelled-content" style="display: none;"><?php et('mp.cancelled.html') ?></div>
  <script>
   jQuery(document).ready(function(){
     mboxMsg($('#tournament-cancelled-content').html(), true, undefined, 400);
   });
  </script>
<?php elseif(isLogged() && cuPl()->hasSetting('has_old_limits')): ?>
  <script>
   extBoxAjax('get_html_popup', 'mbox-msg', {module: 'Licensed', file: 'revert_to_old_limits_prompt', boxtitle: 'you.have.revertable.limits', closebtn: 'no'});
  </script>
<?php elseif(!empty($_GET['tournament_finished'])): ?>
  <script>
    mpFinishedAjax(<?php echo $_GET['eid'] ?>, '<?php phive('Tournament')->finBkg() ?>');
  </script>
<?php elseif(!empty($_GET['tournament_myresults'])): ?>
  <script>
   showMyTournaments();
  </script>
<?php elseif(!empty($_GET['tournament_lobby'])): ?>
  <script>
   toLobby('<?php echo llink('/mp-lobby/') ?>', undefined, <?php echo $_REQUEST['t_id'] ?>);
  </script>
<?php endif ?>

<?php if(!isLogged() && $_GET['show_login'] === 'true'): // TODO add the logic like "show_signup" with the redirect... ?>

    <?php
        $_SESSION['home_page'] = true;
        if(isPNP()):
     ?>
        <script>showPayNPlayPopupOnLogin();</script>
    <?php else: ?>
        <script>showLoginBox('login');</script>
    <?php endif; ?>
<?php endif; ?>
<?php include_once 'top_base_js.php' ?>

<?php loadJs( "/phive/js/jquery.cookie.js" ); // NEEDED to use sCookie ?>

<script type="text/javascript">
    /* Mobile Battle of Slots */
    function hideBackToBoSBar(){
        $("#wrapper-container").removeClass('show_go_back_to_bos');
        $("#wrapper-container").removeClass('topmarginshow_go_back_to_bos');
        $("#mobile-top__back-to-battle-of-slots").slideUp("fast", function() {
            $("#mobile-top__back-to-battle-of-slots").addClass("mobile-top__back-to-battle-of-slots--hidden");
            // Remove the session variable when this is being clicked
            var mpActionsUrl = '/phive/modules/Tournament/xhr/actions.php';
            $.post(mpActionsUrl, {action: 'remove-session-var-for-back-button'}, function(ret){

            }, 'json');
        });
    }
    $(document).ready(function() {
        $('.mobile-top__back-to-battle-of-slots').on('click', function (e) {
            hideBackToBoSBar();
        });

        $('.mobile-top__back-to-battle-of-slots-link, #link_to_mobile_bos, #link_to_mobile_bos__battle-strip').on('click', function (e) {
            // before going to battle check if we have set deposit limits
            licJson('beforePlay', {}, function(ret) {
              var bos_url = '<?php echo phive('Tournament')->getSetting('mobile_bos_url'); ?>';

              if (empty(ret.url)) {
                goToMobileBattleOfSlots(bos_url);
              }else{
                // If we get an URL then we need the player to complete some action (Ex. deposit page) then we redirect to mobile BoS
                goTo(ret.url + '?redirect_after_action=' + bos_url);
              }
            });
        });
    });
</script>
<?php lic('printRealityCheck', [null, true]); ?>
<?php lic('loadGeoComplyJs', ['global']); ?>
<?php $localstorage_key = phive('Localizer')->getSetting('ls_odds_format_key'); ?>
<?php $local_storage_key = phive('Localizer')->getSetting('ls_selected_outcome_key'); ?>

<?php lic('juristdictionalNotice', [], $user); ?>
<?php lic('geocomplyNDBInfo', [], $user); ?>
<script>
    <?php if(isLogged()): ?>
        <?php if(lic('isRGPopupEnabled', [], $user)): ?>
            $(document).ready(function (){
                showRgPopup();
            });
        <?php endif ?>

        <?php if(lic('isIntensiveGamblerPopupEnabled', [], $user)): ?>
        $(document).ready(function (){
            showIntensiveGamblerPopup(
                "<?php echo isPNP() ? 'pnp-gaming-experience-box' : 'rg-login-box'; ?>"
            );
        });
        <?php endif ?>
        <?php if (!phive()->isMobile()) {
            hasMp("mpStart('".phive('UserHandler')->wsUrl('mp-start')."');");
        } ?>
        // get logout msg via websocket
    doWs('<?php echo phive('UserHandler')->wsUrl('logoutmsg'.substr(session_id(), 0, 5), false) ?>', function (e) {
            closeVsWS();
            mgSecureAjax({action: 'obsolete-session'});
            mboxMsg(e.data, true, function () {
                gotoLang("/?signout=true");
            }, 300, false, false);
            <?php if($local_storage_key): ?>
                window.localStorage.removeItem('<?=$local_storage_key?>');
            <?php endif ?>
        });
    <?php endif ?>

    $(document).ready(function  (){
        execNextPopup();
        <?php if($localstorage_key): ?>
        $(document).on('click', 'a[href*="/?signout=true"]', function (event) {
            window.localStorage.removeItem('<?=$localstorage_key?>');
        });
        <?php endif ?>
        <?php if($local_storage_key): ?>
        $(document).on('click', 'a[href*="/?signout=true"]', function (event) {
            window.localStorage.removeItem('<?=$local_storage_key?>');
        });
        <?php endif ?>
    });
</script>

<?php
    if ($_SESSION['mitid_error'] || $_SESSION['mitid_cpr_error']) {
    ?>
    <script type="text/javascript">
        mboxMsg("<?= t($_SESSION['mitid_error'] ?? $_SESSION['mitid_cpr_error']) ?>", true,function() {
            window.top.location.reload();
        });
    </script>
<?php
unset($_SESSION['mitid_error']);
unset($_SESSION['mitid_cpr_error']);
}
