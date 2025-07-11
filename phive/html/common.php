<?php

function loadCookiesBaseFile() {
    include(__DIR__ . '/../../diamondbet/html/cookie_base.php');
}
/**
 * Generates and loads meta tags for site verification and social media platforms.
 *
 * Domain Verification and generates corresponding meta tags to include in the HTML header.
 * If the settings are available, the appropriate meta tags are outputted based on the domain setting.
 */
function loadMetaTag() {
    $localizer = phive('Localizer');

    $googleSiteVerification = $localizer->getDomainSetting('google_site_verification');
    if (!empty($googleSiteVerification)) {
        echo '<meta name="google-site-verification" content="' . htmlspecialchars($googleSiteVerification, ENT_QUOTES, 'UTF-8') . '" />';
    }

    $metaContentFacebook = $localizer->getDomainSetting('facebook_meta_content');
    if (!empty($metaContentFacebook)) {
        echo '<meta name="facebook-domain-verification" content="' . htmlspecialchars($metaContentFacebook, ENT_QUOTES, 'UTF-8') . '" />';
    }

    $supportedMedia = isMobileSite()
        ? 'only screen and (max-width: 640px)'
        : 'only screen and (min-width: 641px)';
    echo '<meta name="supported-media" content="' . htmlspecialchars($supportedMedia, ENT_QUOTES, 'UTF-8') . '" />';
}

function loadCsrfFingerprintMobile(){
    loadJs("/phive/js/jquery.min.js");
    loadJs("/phive/js/utility.js");
    ?>
    <meta name="csrf_token" content="<?php echo $_SESSION['token'];?>"/>
    <input type="hidden" id="device-fingerprint"/>
    <?php generateFingerprint() ?>
    <script>
        $(document).ready(function (){
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf_token"]').attr('content'),
                    'X-DEVICE-FP': getFingerprint()
                },
                beforeSend: function (xhr) {
                    // Failsafe in case fingerprint is not set in time on page load.
                    xhr.setRequestHeader('X-DEVICE-FP', getFingerprint());
                }
                , statusCode: {
                    // response 403 returned by csrf token verification for ajax calls
                    403: function (response) {
                        var message, json;
                        // to avoid json parsing errors if something else than json is returned on 403
                        try {
                            json = /json/.test(response.getResponseHeader('content-type')) ? response.responseJSON : JSON.parse(response.responseText);
                        } catch (e) {
                            json = {};
                        }
                        if (json.error) {
                            switch (json.error) {
                                case 'invalid_origin':
                                case 'invalid_token':
                                    message = json.message;
                                    break;
                                default:
                                    message = 'Something went wrong.'
                            }
                        }
                        fancyShow(message);
                    }
                }
            });
        });
    </script>
    <?php
}

function loadBaseJsCss($do_extras = true){
  loadJs("/phive/js/bowser.js");
  loadJs("/phive/js/json2.js");
  loadJs("/phive/js/underscore.js");
  loadJs("/phive/js/jquery.min.js");
  // jQuery tablesorter was only used on old admin, and was not even working
  // if (!phive()->isMobile()) {
  //     loadJs("/phive/js/tablesorter/jquery.tablesorter.js");
  //     loadJs("/phive/js/tablesorter/bigcurrency.js");
  // }
  loadJs("/phive/js/utility.js");
  loadJs("/phive/js/mg_casino.js");
  loadJs("/phive/js/count_up.js");
  loadJs("/phive/modules/Licensed/Licensed.js");
  lic('loadJs', [], cuRegistration());
    // only set this if value contains live-casino, default null for casino and empty for jackpots
    if (isset($_REQUEST["dir"]) && str_contains($_REQUEST["dir"], "live-casino")) {
        $lobby_dir = "live-casino";
    }
    if (isset($_REQUEST["dir"]) && str_contains($_REQUEST["dir"], "jackpots")) {
        $lobby_dir = "videoslots_jackpot";
    }
    ?>
    <script type="text/javascript">
        var JURISDICTION = '<?=cuCountry()?>';
        var IS_LOGGED = '<?=isLogged()?>';
        var GAME_TAGS = '<?=$_REQUEST["tag"]?>';
        var LOBBY_DIR = '<?=$lobby_dir?>';
    </script>
    <?php
  loadJs("/phive/modules/BoxHandler/BoxActions.js");
  loadCss("/diamondbet/css/" . brandedCss() . "all.css");
  loadCss("/diamondbet/css/" . brandedCss() . "responsible-gaming.css");
  loadCss("/diamondbet/css/" . brandedCss() . "flexslider.css");
  loadCss("/diamondbet/css/css-flex.css");
  if( phive()->methodExists('PayNPlay', 'loadJS') && phive('PayNPlay')->isActive()) {
     phive('PayNPlay')->loadCss();
     phive('PayNPlay')->loadJs();
  }
  if (phive()->methodExists('Cookie', 'loadJS')) {
     phive('Cookie')->cookiePopup();
  }
  if($do_extras){
      loadCss("/diamondbet/css/topmenu.css");
      loadCss("/diamondbet/css/" . brandedCss() . "reg.css");
      loadCss("/diamondbet/css/" . brandedCss() ."documents_page.css");
      loadCss("/diamondbet/css/" . brandedCss() . "privacydashboard.css");
      loadCss("/diamondbet/css/" . brandedCss() . "uniform-select.css");
  }
  if(phive()->ieversion() >= 9)
    loadCss("/diamondbet/css/ie9.css");
  if(phive()->isFirefox())
    loadCss("/diamondbet/css/ff.css");
    // moved the csrf token stuff for ajax calls here from diamondbet/generic.php, there it was not loaded correctly everywhere (Ex. deposit box)
  ?>
    <meta name="csrf_token" content="<?php echo $_SESSION['token'];?>"/>
    <input type="hidden" id="device-fingerprint"/>
    <?php generateFingerprint() ?>
    <script type="text/javascript">
        // See config at http://api.jquery.com/jquery.ajax/
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf_token"]').attr('content'),
                'X-DEVICE-FP': getFingerprint()
            },
            beforeSend: function (xhr) {
                // Failsafe in case fingerprint is not set in time on page load.
                xhr.setRequestHeader('X-DEVICE-FP', getFingerprint());
            }
            , statusCode: {
                // response 403 returned by csrf token verification for ajax calls
                403: function(response) {
                    var message,json;
                    // to avoid json parsing errors if something else than json is returned on 403
                    try {
                        json = /json/.test(response.getResponseHeader('content-type')) ? response.responseJSON : JSON.parse(response.responseText);
                    } catch(e) {
                        json = {};
                    }
                    if(json.error) {
                        switch(json.error) {
                            case 'invalid_origin':
                            case 'invalid_token':
                                message = json.message; break;
                            default:
                                message = 'Something went wrong.'
                        }
                    }
                    fancyShow(message);
                }
            }
        });
    </script>
  <?php
}

function loadBasePWAConfig() {
    ?>
    <link rel="icon" type="image/png" sizes="16x16" href="/diamondbet/images/<?= brandedCss() ?>mobile/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/diamondbet/images/<?= brandedCss() ?>mobile/favicon-32x32.png">
    <link rel="icon" href="/diamondbet/images/<?= brandedCss() ?>favicon.ico">
    <link rel="manifest" crossorigin="use-credentials" href="/diamondbet/<?= brandedCss() ?>manifest.json" />
    <meta name="msapplication-config" content="/diamondbet/<?= brandedCss() ?>browserconfig.xml" />

    <?php
}

function loadBaseAppleIcons() {
    ?>
    <link rel="apple-touch-startup-image" href="/diamondbet/images/<?= brandedCss() ?>mobile/LoaderBackground.png" />
    <link rel="apple-touch-icon-precomposed" href="/diamondbet/images/<?= brandedCss() ?>mobile/apple-touch-icon-precomposed.png" />
    <link rel="apple-touch-icon-precomposed" href="/diamondbet/images/<?= brandedCss() ?>mobile/apple-touch-iphone-precomposed.png" />
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="/diamondbet/images/<?= brandedCss() ?>mobile/apple-touch-ipad-precomposed.png" />
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="/diamondbet/images/<?= brandedCss() ?>mobile/apple-touch-iphone4-precomposed.png" />
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="/diamondbet/images/<?= brandedCss() ?>mobile/apple-touch-ipad-retina-precomposed.png" />

    <?php
}

function isIpad(): bool
{
  return phive()->isIpad();
}

function isIphone(): bool
{
    return phive()->isIphone();
}

function do_options($options, $current_value, $value_sub = null, $display_sub = null){
  if (null===$display_sub) {
    $display_sub = $value_sub;
  }
  foreach ($options as $option):
  if($value_sub === null){
    $cval = $cdisp = $option;
  }else{
    $cval = $option[$value_sub];
    $cdisp = $option[$display_sub];
  }
?>
  <option value="<?=$cval?>" <?php if($cval==$current_value) echo 'selected="selected"' ?>><?=$cdisp?></option>
<?php endforeach;
}

function select_partner($partner_id = null, $label = 'full_name'){?><!--  -->
  <select name="partner_id">
    <option value="0">Select Partner</option>
    <?php do_options(phive('Raker')->getPartners(), $partner_id, 'partner_id', $label); ?>
  </select>
<?php }

function doDefaulter($id, $value){?>
  Use default: <input type="checkbox" name="<?=$id?>" id="<?=$id?>" <?php if (empty($value)) echo "checked='checked'";?> />
<?php }

function BoxPrinter($boxes,$EDITBOXES) {
  if(empty($boxes))
    return;

  $boxes[0]->first_box = true;

  foreach ($boxes as $b){
    if($b->render !== false){
      if($EDITBOXES)
        $b->printModeratorHTML();
      else{
        if($b->sub_box != 1)
          $b->printHTML();
      }
    }
  }
}

function prBoxCSS($boxes = null){
  loadCss("/phive/js/selectbox/css/jquery.selectbox.css");
  loadCss("/diamondbet/css/" . brandedCss() . "top-play-bar.css");
  loadCss("/diamondbet/css/" . brandedCss() . "game-chooser.css");
  loadCss("/phive/js/jcarousel/skins/videoslots/skin.css");
  loadCss("/diamondbet/css/" . brandedCss() . "cashier.css");
  loadCss('/diamondbet/css/' . brandedCss() . 'trophies.css');
  loadCss('/diamondbet/css/' . brandedCss() . 'intended-gambling.css');
  if(empty($boxes))
    return;
  foreach($boxes as $b){
    $b->printCSS();
  }
}

function execBoxEdit($pager){ ?>
  <?php if($pager->edit_boxes || $pager->edit_strings): ?>
    <script src="/phive/modules/BoxHandler/BoxActions.js" type="text/javascript" charset="utf-8"></script>
    <script type="text/javascript" charset="utf-8">
    function removeAllLinks(){
      var all = $('bg_layer1').descendants();
      all.each(function(c){
        if(c.tagName == 'A')
          c.removeAttribute('href');
      });
    }
    </script>
  <?php endif ?>
<?php }

if(!function_exists('printEditBoxesHeader')){
  function printEditBoxesHeader($type){ ?>
    <form id="addbox_<?=$type?>" name="addbox" method="post" action="?editboxes">
      <input type="hidden" name="token" value="<?echo $_SESSION['token'];?>">
      <div class="editboxes_header">
        <input type="hidden" name="container" value="<?=$type?>" />
        <table><tr><td>
          <?php $boxtypes = phive('BoxHandler')->getCompatibleBoxesFor($type); ?>
          <select name="boxtype">
            <?php foreach ($boxtypes as $boxtype): ?>
            <option><?php echo $boxtype ?></option>
            <?php endforeach; ?>
          </select>
        </td><td>
          <input type="submit" name="addbox" value="Add box">
        </td></tr></table>
      </div>
    </form>
  <?php
  }
}

function box404(){ ?>
  <div class="frame-block">
    <div class="frame-holder">
      <h1><?php et('404.header') ?></h1>
      <?php et("404.content.html") ?>
    </div>
  </div>
<?php }

/**
 * @param $langtag
 * @param DBUser $user
 */
function baseRedirects($langtag, $user){
    if(phive('QuickFire')->getSetting('has_mobile') == true){

        if(phive()->isMobile() !== false){
            if(!isPNP() && (!empty($_GET['signup']) || !empty($_SESSION['show_signup']))){
                unset($_SESSION['show_signup']);
                phive('Redirect')->to('/mobile/register/', $langtag);
            }
            phive('Redirect')->startGo("302 Found", $langtag);
        } elseif (!privileged()) {
            phive('Redirect')->startGo("302 Found", $langtag, true);
        }
    }

    if (isPNP()) {
        phive('PayNPlay')->baseRedirects();
    }

    if(is_object($user) && !privileged() && empty($_SESSION['zipcode_pending'])) {
        if (phive('Config')->getValue('lga', 'reg-email') == 'yes' && $user->getSetting('email_code_verified') == 'no' && $_SESSION['email_code_shown'] > 1)
            phive('UserHandler')->logout('failed to verify email');

        $is_empty_common_params = empty($_GET['showtc']) && empty($_GET['showbtc']) && empty($_GET['showstc']);

        //UKGC
        if ($is_empty_common_params && !$user->hasCurTc() && !$user->hasSetting('tac_block')) {
            phive('Redirect')->to("?showtc=true", $langtag, true);
        }

        // Check if user accepted Bonus T&S
        if ($is_empty_common_params && lic('hasBonusTermsConditions') && !$user->hasCurBtc() && !$user->hasSetting('bonus_tac_block')) {
            phive('Redirect')->to("?showbtc=true", $langtag, true);
        }

        // Check sports terms and conditions
        if($is_empty_common_params && lic('isSportsbookEnabled') && !$user->hasCurTcSports() && !$user->hasSetting('tac_block_sports')) {
            phive('Redirect')->to("?showstc=true", $langtag, true);
        }

        if ($is_empty_common_params && phive('DBUserHandler')->getSetting("pp_on") === true) {
            if(!$user->hasCurPp() && empty($_GET['showpp']) && ($user->hasCurTc() || $user->hasSetting('tac_block')) && (!lic('hasBonusTermsConditions') || ($user->hasCurBtc() || $user->hasSetting('bonus_tac_block')))) {
                phive('Redirect')->to("?showpp=true", $langtag, true);
            }
        }

        if(!lic('pnpRegistrationInProgress', [$user], $user)) {
            lic('loginRedirects', [$langtag], $user);
        }

        if (lic('doIntendedGambling')) {
            lic('intendedGambling');
        } elseif($is_empty_common_params && phive()->isMobile() && phive('UserHandler')->doForceDeposit()){
            if(phive('Pager')->getPath() != '/mobile/cashier/deposit/' && !isPNP() ){
                phive('Redirect')->to('/mobile/cashier/deposit/', cLang(), false, '302 Found');
            }
        }
    }
}



function commonRedirects(){
  if(!empty($_GET['signout']))
    phive('UserHandler')->logout('logout');

  $loc 		= phive('Localizer');
  $langtag	= $loc->getCurNonSubLang();



  $user = cu($_SESSION['user']);

  baseRedirects($langtag, $user);

  $mg = phive('QuickFire');

  //http://www.videoslots.loc/?uid=5129332&email_code=7430
    if(!empty($_GET['email_code'])) {
        $u = cu($_GET['uid']);
        if(!empty($u)) {
            $code = $u->getSetting('email_code');
            $uname = $u->getUsername();

            if ($u->getSetting('email_code_verified') === 'yes' || $u->getSetting('sms_code_verified') === 'yes') {
                return;
            }

            if(phive('UserHandler')->hasFinishedRegistrationStep2($u)) {
                if($code == $_GET['email_code'] && $u->getSetting('email_code_verified') == 'no') {
                    $u->setSetting('email_code_verified', 'yes');
                    $u->deleteSetting('email_code');
                    phive('MailHandler2')->sendWelcomeMail($u);
                    if(empty($_SESSION['mg_id'])) {
                        phive('UserHandler')->logout('session refresh');
                        phive('UserHandler')->login($uname, '', true, false);
                        $_SESSION['nocash_shown'] = true;
                    }
                }else if(!empty($code) && $code != $_GET['email_code']){
                    phive('UserHandler')->addBlock($u, 7);
                    phive('UserHandler')->logAction($u, "Tried to verify with the wrong code, real code: $code, supplied code: {$_GET['email_code']}", 'block');
                }
            }else if(!empty($code) && $code != $_GET['email_code']){
                phive('UserHandler')->addBlock($u, 7);
                phive('UserHandler')->logAction($u, "Tried to verify with the wrong code, real code: $code, supplied code: {$_GET['email_code']}", 'block');

            }
        }
    }

  if(is_object($user)){
    $redir_link = $loc->getNonSubLang() == '/'.$_SESSION['local_usr']['preferred_lang'] ? '/' : '/'.$_SESSION['local_usr']['preferred_lang'].'/';
    $sign_string = phive("Config")->getValue('sign', 'string');
    if(!empty($sign_string) && time() > strtotime(phive("Config")->getValue('sign', 'start')) && time() < strtotime(phive("Config")->getValue('sign', 'end')) && empty($_SESSION['sign_shown'])){
      $_SESSION['sign_shown'] = true;
      phive('Redirect')->to("?show_sign=true", $langtag);
    }

  }
  if (!empty($_GET['redirect_to_top_url'])) {
    $dir = $_GET['redirect_to_top_url'] === '/' ? '' : $_GET['redirect_to_top_url'];
    ?>
    <script type="text/javascript">
      window.top.location.href = "<?=phive()->getSiteUrl() . "/" . htmlentities($dir) ?>";
    </script>
    <?php
  }
}

/**
 * Outputs noindex,follow meta tag for specific URLs
 *
 * This function checks if the current URL is in the list of URLs that should
 * be noindexed and outputs the appropriate meta tag if needed.
 */
function addNoindexMetaForSpecificUrls() {
  $host = $_SERVER['HTTP_HOST'] ?? '';
  $uri = $_SERVER['REQUEST_URI'] ?? '';
  $noindex_urls = phive()->getSetting('noindex_urls') ?? [];
  foreach ($noindex_urls as $noindex_url) {
      if ($host === $noindex_url['host'] && $uri === $noindex_url['uri']) {
          echo '<meta name="robots" content="noindex,follow" />';
          break;
      }
  }
}
