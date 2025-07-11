<?php
require_once __DIR__.'/../../phive/html/display_base_diamondbet.php';

function editCardUrl($username, $btype, $userprofile = false, $extra = ''){
    $btype = $btype != 'mobile' ? '' : "/$btype";
    return empty($userprofile) ? llink("$btype/account/$username/documents/$extra") : llink("admin/userprofile/?username=$username&action=documents");
}

function drawFlagSpans($u, $classes = '', $return = false) {
    $u = cu($u);
    $classes = empty($classes) ? phive('Cashier')->fraud->getFlags($u) : $classes;

    $spans = implode(' ', array_map(fn($type) => '<span class="' . $type . '" style="width:10px; cursor:default;" title="Status: ' . $type . '">&nbsp;&nbsp;&nbsp;</span>', $classes));

    if ($return) {
        return $spans;
    }

    echo $spans;
}

function btnDefaultXs($txt, $link, $onclick = '', $width = '', $class = '', $icon = ''){ btn('btn btn-xs btn-default-xs '.$class, $txt, $link, $onclick, $width, $icon); }
function btnDefaultS($txt, $link, $onclick = '', $width = '', $class = '', $icon = ''){ btn('btn btn-s btn-default-s '.$class, $txt, $link, $onclick, $width, $icon); }
//blue btn
function btnRequestS($txt, $link, $onclick = '', $width = '', $class = '', $icon = ''){ btn('btn btn-s gradient-trophy-bar '.$class, $txt, $link, $onclick, $width, $icon); }
function btnDefaultM($txt, $link, $onclick = '', $width = '', $class = '', $icon = ''){ btn('btn btn-m btn-default-l '.$class, $txt, $link, $onclick, $width, $icon); }
function btnDefaultL($txt, $link, $onclick = '', $width = '', $class = '', $icon = ''){ btn('btn btn-l btn-default-l '.$class, $txt, $link, $onclick, $width, $icon); }
function btnDefaultXl($txt, $link, $onclick = '', $width = '', $class = '', $icon = ''){ btn('btn btn-xl btn-default-xl '.$class, $txt, $link, $onclick, $width, $icon); }
function btnDefaultXxl($txt, $link, $onclick = '', $width = '', $class = '', $icon = ''){ btn('btn btn-xxl btn-default-xxl '.$class, $txt, $link, $onclick, $width, $icon); }

function btnCancelDefaultXs($txt, $link, $onclick = '', $width = '', $class = '', $icon = ''){ btn('btn btn-xs btn-cancel-default-l '.$class, $txt, $link, $onclick, $width, $icon); }
function btnCancelDefaultS($txt, $link, $onclick = '', $width = '', $class = '', $icon = ''){ btn('btn btn-s btn-cancel-default-l '.$class, $txt, $link, $onclick, $width, $icon); }
function btnCancelDefaultL($txt, $link, $onclick = '', $width = '', $class = '', $icon = ''){ btn('btn btn-l btn-cancel-default-l '.$class, $txt, $link, $onclick, $width, $icon); }

function btnActionXs($txt, $link, $onclick = '', $width = '', $class = '', $icon = ''){ btn('btn btn-xs btn-action-l '.$class, $txt, $link, $onclick, $width, $icon); }
function btnActionL($txt, $link, $onclick = '', $width = '', $class = '', $icon = ''){ btn('btn btn-l btn-action-l '.$class, $txt, $link, $onclick, $width, $icon); }

function btnCancelL($txt, $link, $onclick = '', $width = '', $class = '', $icon = ''){ btn('btn btn-l btn-cancel-l '.$class, $txt, $link, $onclick, $width, $icon); }
function bigCashBtn($txt, $link, $onclick = '', $width = '', $class = '', $icon = ''){ btn('btn btn-xl btn-default-xl '.$class, $txt, $link, $onclick, $width, $icon); }

function btnCancelXl($txt, $link, $onclick = '', $width = '', $class = '', $icon = ''){ btn('btn btn-new-xl btn-cancel-xl '.$class, $txt, $link, $onclick, $width, $icon); }
function btnActionXl($txt, $link, $onclick = '', $width = '', $class = '', $icon = ''){ btn('btn btn-new-xl btn-action-xl '.$class, $txt, $link, $onclick, $width, $icon); }

    //TODO use this when logging in and has running tournaments
function mpChooseBox($es, $ajax = false){ ?>
  <?php if(!empty($es)): ?>
    <div <?php echo $ajax ? '' : 'id="t-entries" style="display: none;"' ?>>
      <div><?php et('pick.tournament.info.html') ?></div>
      <table>
        <col width="375">
        <col width="25">
        <col width="100">
        <?php foreach($es as $e): ?>
          <tr>
            <td><?php echo $e['tournament_name'] ?></td>
            <td></td>
            <td><?php btnDefaultL(t('play'), phive('Tournament')->getPlayUrl($e), '', 100) ?></td>
          </tr>
        <?php endforeach ?>
      </table>
    </div>
  <?php endif ?>
<?php }

function depositTopBar($str = 'deposit', $onclick = 'closeQuickDeposit()')
{
    $domId = phive('Casino')->generateDOMId($str, 'cashier-top-bar');
    ?>
    <div id="quick-cashier-top-bar" class="cashier-top-bar gradient-default">
        <?php et($str); ?>
        <div id="<?php echo $domId; ?>" class="multibox-close" onclick="<?php echo $onclick; ?>" style="display:none;"><span class="icon icon-vs-close"></span></div>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                var forceDeposit = <?php echo json_encode(phive('UserHandler')->doForceDeposit()); ?>;
                var element = document.getElementById("<?php echo $domId; ?>");
                element.style.display = forceDeposit ? 'none' : 'block';
            });
        </script>
    </div>
    <?php
}

function drawLoginReg($extra_class = '')
{
    loadCss("/diamondbet/css/" . brandedCss() . "new-registration.css");
    loadJs('/phive/js/utility.js');
    $registration_page = phive('DBUserHandler')->getSetting('registration_path', 'registration1');
    ?>
    <script>
        if (httpGet('goto') === 'verify') {
            showLoginBox('verify');
        }
    </script>

    <div class="login-form">
        <input type="hidden" name="token" value="<? echo $_SESSION['token']; ?>">
        <?
        $is_auth_allowed = phive('DBUserHandler')->isRegistrationAndLoginAllowed();
        $login_action = $is_auth_allowed ? 'checkJurisdictionPopupOnLogin()' : 'showAccessBlockedPopup()';
        $registration_action = $is_auth_allowed ? "showRegistrationBox('/$registration_page/')" : 'showAccessBlockedPopup()';
        if (isBankIdMode()) {
            loadJs("/phive/modules/DBUserHandler/js/registration.js");
            $registration_action = $login_action = $is_auth_allowed ? "licFuncs.startBankIdRegistration('registration')" : 'showAccessBlockedPopup()';
        }
        $pnp_action = $is_auth_allowed ? 'showPayNPlayPopupOnLogin()' : 'showAccessBlockedPopup()';
        if(!isPNP()){
        ?>
        <div class="holder">
            <?php btnDefaultXl(t('login'), '', $login_action, 100, "right gradient-grey-light gradient-login-btn margin-ten-left login-btn $extra_class", 'vs-login') ?>
            <?php btnDefaultXl(t('open-account'), '', $registration_action, 160, "right gradient-default register-btn $extra_class", 'vs-person-add') ?>
        </div>
        <? } elseif (isPNP()){ ?>
            <!-- PayNPlay button -->
            <?php btnDefaultXl(t('login'), '', $pnp_action, 100, "right gradient-grey-light margin-ten-left login-btn $extra_class", 'vs-login') ?>
        <? } ?>
        <div style="height: 16px;"></div>
    </div>
<?php
}

function drawStartPlaying($extra_class = '') {
    loadCss("/diamondbet/css/" . brandedCss() . "new-registration.css");
    loadJs('/phive/js/utility.js');
    $is_auth_allowed = phive('DBUserHandler')->isRegistrationAndLoginAllowed();
    $pnp_action = $is_auth_allowed ? 'showPayNPlayPopupOnLogin()' : 'showAccessBlockedPopup()';

    ?>
        <div class="login-form">
            <input type="hidden" name="token" value="<? echo $_SESSION['token']; ?>">
            <div class="holder">
                <?php btnDefaultXl(t('paynplay.login'), '', $pnp_action, 160, "right gradient-green margin-ten-left login-btn $extra_class", 'vs-login') ?>
            </div>
            <div style="height: 16px;"></div>
        </div>
    <?php
}
  /*
function btnSmall($txt, $link, $onclick = ''){
  btnDefaultS($txt, $link, $onClick);
}
  */

function btnSmall($txt, $link, $onclick = ''){ ?>
  <div>
      <table class="btn-small">
          <tr>
              <td class="left-td"></td>
              <td class="middle-td">
                  <a <?php if(!empty($onclick)): ?> onclick="<?php echo $onclick ?>" <?php else: ?> href="<?php echo $link ?>" <?php endif ?>> <?php echo $txt ?>  </a>
              </td>
              <td class="right-td"></td>
          </tr>
      </table>
  </div>
<?php }

function btnNormal($txt, $location, $return = false, $width = 150){
    if($return)
        ob_start();
    btnDefaultXl($txt, '', $location, $width);
    if($return)
        return ob_get_clean();
}

function printStatsTable($header, $data, $ext_header){
  phive('UserSearch')->showCsv($data);
?>
  <table class="stats_table">
    <tr class="stats_header">
      <?php $i = 0; foreach($header as $h): ?>
        <td><?php echo ucfirst(str_replace('_', ' ', $h)).$ext_header[$i] ?></td>
      <?php $i++; endforeach ?>
    </tr>
    <?php $i = 0; foreach($data as $r):
    ?>
      <tr class="<?php echo $i % 2 == 0 ? 'grey' : 'white' ?>_fill" >
        <?php foreach($header as $h): ?>
          <?php if (in_array(array('username', 'recipient'), $h)) : ?>
            <td> <?php profileLink($r[$h],$r[$h]) ?> </td>
          <?php else: ?>
            <td> <?php echo $r[$h] ?> </td>
          <?php endif ?>
        <?php endforeach ?>
      </tr>
    <?php $i++; endforeach ?>
  </table>
<?php }

function depositGoToBonuses($deposit_bonus, $username){
  if($deposit_bonus == 'bonus'):
  ?>
    <p>
      <?php et('depositbonus.notification.html') ?>
    </p>
    <p>
      <a href="/account/<?php echo $username ?>/my-bonuses/"><?php et('goto.mybonuses') ?></a>
    </p>
  <?php endif;
}

function checkAmount(&$err, $amount, $withdrawal = false){

  $amount = phive('Cashier')->cleanUpNumber(!empty($amount) ? $amount : $_REQUEST['amount']);

  if($withdrawal)
    $amount = phive('Cashier')->handleDepBonuses($_SESSION['mg_id'], $amount * 100) / 100;

  if(empty($amount))
    $err['amount'] = 'err.empty';

  return $amount;
}

function bigButton($text,$onClick)
{
  return <<< eof
    <div class="button-left-normal">&nbsp;</div>
    <div class="button-middle-normal button-middle-normal-big" onclick="$onClick">$text</div>
    <div class="button-right-normal" style='width:8px'>&nbsp;</div>
eof;
}

function fbWithdrawComplete($show = false){ ?>
  <div id="withdraw_complete" <?php if(!$show) echo 'style="display: none;"' ?>>
    <h3><?php et('withdraw.complete.headline') ?></h3>
    <p><?php et('withdraw.complete.body') ?></p>
  </div>
<?php }

function fbDepositComplete(int $depositCount = 0){
    depositCompletePopup($depositCount);
}

function depositCompletePopup($depositCount)
{
    if ($depositCount <= 1) {
        ?>
        <div>
            <h3><?php echo et('deposit.complete.headline'); ?></h3>
            <p><?php echo et('deposit.complete.body'); ?></p>
        </div>
        <?php
    } else {
        ?>
        <div>
            <?php echo et('deposit.successful'); ?>
        </div>
        <?php
    }
}

function phoneUsMailForm(){ ?>
  <script>

    function submitPhoneUs(){
      mgJson({
        action: "submit-phone-us",
        country: $("#country_and_calling_codes").val(),
        mobile: $("#mobile").val(),
        email: $("#email").val(),
        question: $("#question").val(),
        captcha: $("#captcha").val()
      },function(ret){
        if(ret.res == 'fail') {
            $("#errorZone").html(ret.error);
        } else {
            $.multibox('close', 'mbox-msg');
            setTimeout(function() {
                mboxMsg(ret.res, true, '', 400, false, false);
            },1000);
        }
      });
    }

    function setCountryCode(){
        var option = $('.phone-us-form__input-select option:selected')[0];
        var prefix = $(option).attr('data-calling_code');
        $('.phone-us-form__input-prefix').val(prefix);
    };

    $("#phone-us-form").ready(function(){
        setCountryCode();
        $('.multibox-close').addClass('phone-us-form__btn-close');
        $('.phone-us-form__input-select').change(function(evt) {
            setCountryCode();
        });
    })
  </script>
  <form id="phone-us-form" action="javascript:" onsubmit="return submitPhoneUs()" autocomplete="off">
      <div class="phone-us-form__header">
          <h3 class="phone-us-form__header-title"><?php et("eform.headline.phoneus") ?></h3>
      </div>
      <div class="phone-us-for__main">
          <div class="phone-us-form">
              <div class="phone-us-form__input-container">
                  <?php $country = !empty($_SESSION['local_usr']['country']) ? $_SESSION['local_usr']['country'] : phive('IpBlock')->getCountry(); ?>
                  <span class="styled-select">
                    <?php dbSelect("country_and_calling_codes", phive('Cashier')->displayBankCountries(phive('Cashier')->getBankCountries('', true), [], true, ['calling_code']), $country, array('', t('choose.country')), 'phone-us-form__input phone-us-form__input-select') ?>
                  </span>
              </div>
              <div class="phone-us-form__input-container">
                  <?php dbInput('prefix-mobile', false, 'number', 'phone-us-form__input phone-us-form__input-prefix', 'disabled') ?>
                  <?php dbInput('mobile', false, 'tel', 'phone-us-form__input phone-us-form__input-mobile', 'id="mobile-phone-us" required', true, false, false, t('register.mobile')) ?>
              </div>
              <div class="phone-us-form__input-container">
                <?php dbInput('email',
                    false,
                    'email',
                    'phone-us-form__input phone-us-form__input-icon',
                    lic('getMaxLengthAttribute', ['email']) . ' required',
                    true,
                    false,
                    false,
                    'Email*'
                ) ?>
            </div>
              <div class="phone-us-form__input-container">
                  <?php dbInputTextArea('question', false, 'phone-us-form__input phone-us-form__input-question', 'required', true, t('register.question')) ?>
              </div>
              <div class="phone-us-form__input-container">
                  <img class="captcha captcha-img" src="<?php echo PhiveValidator::captchaImg() ?>"/>
                  <input type="text" name="captcha" id="captcha" value="" placeholder="<? et('contactus.code') ?>"
                  required class="phone-us-form__input captcha captcha-text"/></div>
              <div class="phone-us-form__input-container">
                  <?php btnDefaultL(t('submit'), '', '', '100%') ?>
              </div>
          </div>
          <div id="errorZone" class="errors">
          </div>
      </div>
  </form>
<?php }


function smsValidationForm(){

  ob_start();
  ?>
  <script>
    function submitSmsValidation(){
      mgJson({
        action: "submit-sms-validation",
        country: $("#sms-validation-code").val(),
      },function(ret){
        if(ret.res == 'fail')
          $("#errorZone").html(ret.error);
        else
          $("#sms-validation-form").html(ret.res);
      });
    }
  </script>
  <div id="sms-validation-form">
    <h3>
      <?php et("eform.headline.phoneus") ?>
    </h3>
    <div>
      <?php et("eform.description.phoneus.html") ?>
    </div>
    <br/>
    <table class="registerform simple-airy-table">
      <tr>
        <td><?php et('register.mobile') ?></td>
        <td>
          <?php dbInput('sms-validation-code') ?>
        </td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td><?php btnDefaultL('submit', '', 'submitSmsValidation()', 100) ?></td>
      </tr>
    </table>
    <div id="errorZone" class="errors">
    </div>
  </div>

  <?php

  return ob_get_clean();
}

/**
* @param string $type
* @return mixed
*/
function showEmptyDobBox($type = '')
{
    if (!empty($_GET['showtc']) || !empty($_GET['showbtc'])) {
       return null;
   }
   $url = '/emptydob1/?redirect=true';

   if (in_array($type, ['zipcode', 'nid'])) {
       $url .= "&{$type}=true";
   }

   if (!empty(phive('UserHandler')->ajax_context)) {
       return [
           "method" => "showEmptyDobBox",
           "params" => [$url]
       ];
   }

   echo jsTag("$(document).ready(function(){ showEmptyDobBox('{$url}'); });");

   return null;
}

function showSourceOfFundsBox($url)
{
    echo jsTag("$(document).ready(function(){ showSourceOfFundsBox('{$url}'); });");
}
