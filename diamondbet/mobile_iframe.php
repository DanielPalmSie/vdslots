<?php
require_once __DIR__ . '/../phive/phive.php';
require_once __DIR__ . '/../phive/html/common.php';
require_once __DIR__ . '/../diamondbet/html/display.php';
require_once __DIR__ . '/../phive/html/display_base_diamondbet.php';

phive('Localizer')->redirectToUsersNonSub('/mobile/', 1, 'mobile_start_lang');

if (strpos($_SERVER['REQUEST_URI'], '/mobile/register3/') === false && phive('Config')->getValue('lga', 'reg-email') == 'yes' && isLogged() && cuSetting('email_code_verified') == 'no') {
  phive('Redirect')->to('/mobile/register3/', cLang());
} elseif (strpos($_SERVER['REQUEST_URI'], '/mobile/register3/') === false) {
  commonRedirects();
}

//phive()->dumpTbl('mobile_server', $_SERVER);

$pager = phive('Pager');

$langtag = phive()->getModule('Localizer')->getCurNonSubLang();

$pager->initPage($langtag);
$is404 = $pager->is404();
if ($is404) {
  header("HTTP/1.1 404 Not Found");
}

$landing_bkg = phive('Pager')->fetchSetting("landing_bkg");
$body_style = $pager->getBodyStyle();

$css = $GLOBALS['css_extra']		= phive('Pager')->fetchSetting("css");

$GLOBALS['site_type'] = 'mobile';

global $global_onlysetup;
if(!$global_onlysetup):
  $ios7_app_meta = phive('Config')->getValue('meta', 'ios7app');
  ?>
  <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//<?=$langtag?>" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

  <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=$langtag?>" lang="<?=$langtag?>">
  <head>
    <meta name="globalsign-domain-verification" content="A-WmnZ0aBgY6RRi1e5xktD9JpI4nTrhyNKLKhcbnbz" />
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <meta name="google-site-verification" content="OcLKMfgXCSb06FeuMXJLBlXuX9VEw0egjBK76DdZwoU" />
    <meta name="google-site-verification" content="8yJY0FKFY0r7ta32g3xQ5z_rycPBgOZuOadsPCK1BeY" />
    <?php loadMetaTag(); ?>
    <?php if (phive()->getSetting('viewport')): ?>
        <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <?php else: ?>
        <?php if (!isIpad()): ?>
            <meta name="apple-mobile-web-app-capable" content="yes"/>
            <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
            <meta id="viewport" name="viewport" content="width=400, user-scalable=no">
        <?php else: ?>
            <?php echo $ios7_app_meta ?>
        <?php endif ?>
    <?php endif; ?>
    <?php loadJs("/phive/js/stay_standalone.js") ?>


    <?php if ($desc=$pager->getMetaDescription()): ?>
      <meta name="description" content="<?=htmlspecialchars(phive('Localizer')->getPotentialString($desc))?>" />
    <?php endif; ?>
    <?php if ($keywords=$pager->getMetaKeywords()): ?>
      <meta name="keywords" content="<?=htmlspecialchars(phive('Localizer')->getPotentialString($keywords))?>" />
    <?php endif; ?>
    <?php $pager->getHreflangLinks() ?>
    <?php if($pager->bot_block): ?>
      <meta name="robots" content="noindex">
    <?php endif; ?>

    <?php if ($title=$pager->getTitle()): ?>
      <title><?=htmlspecialchars(phive('Localizer')->getPotentialString($title))?></title>
    <?php else: ?>
        <title><?php echo phive()->getSiteTitle() ?></title>
    <?php endif; ?>
    <?php loadBaseAppleIcons() ?>
    <?php loadBasePWAConfig() ?>
    <?php
    loadJs("/phive/js/jquery.min.js");
    phive('Casino')->setJsVars('mobile');
    if (phive()->deviceType() == 'iphone') {
        loadCss("/diamondbet/css/iphone.css");
    }

    loadBaseJsCss();

    loadJS('/diamondbet/mobile_game/js/old-pages-inside-iframe.js');
    loadCss('/diamondbet/mobile_game/css/old-pages-inside-iframe.css');

    loadJs("/diamondbet/js/analytics.js");
    loadJs("/diamondbet/js/navigation.js");
    loadJs("/phive/modules/Micro/mobile_play_mode.js");
    loadJs("/phive/js/iscroll-4/iscroll.js");
    loadJs("/phive/js/jquery.scrollTo.js");
    loadJs("/phive/js/xui-2.3.2.js");
    loadJs("/phive/js/xui.swipe.js");
    prBoxCSS($pager->all_boxes);
    loadCss("/diamondbet/css/" . brandedCss() . "mobile.css");

    if (!empty($css)) {
        loadCss("/diamondbet/css/$css.css");
    }

    if (isIpad()) {
        loadCss("/diamondbet/css/ipad.css");
    }

    loadJs("/phive/js/multibox.js");
    loadCss("/diamondbet/css/" . brandedCss() . "fancybox.css");

    // loadCss("/diamondbet/css/mobile_iframe.css");

    execBoxEdit($pager);
    ?>
    <?php include(__DIR__.'/html/google_analytics_4.php' ) ?>
    <?php include(__DIR__.'/html/external_tracking_head.php') ?>
    <?php include(__DIR__.'/html/external_tracking_body.php') ?>
  </head>

  <?php if(!empty($landing_bkg)): ?>
  <body onload="<?php if($pager->edit_strings) echo 'removeAllLinks()'; ?>" style="background: #000;">
  <?php else: ?>
  <body onload="<?php if($pager->edit_strings) echo 'removeAllLinks()'; ?>">
  <?php endif ?>
  <?php if(!empty(phive()->getSetting('mobile_in_iframe'))): ?>
      <div id="mobile-iframe-container">
          <div id="mobile-iframe-close-btn" onclick="closeMobileIframe()"></div>
          <div id="mobile-iframe"></div>
      </div>
  <?php endif ?>


  <?php if(!$pager->edit_boxes) include(__DIR__ . '/html/mobile-loader.php') ?>

<style>
.frame-holder{
  background: none !important;
}
</style>
  <div id="wrapper-container">
      <div id="wrapper" style="background: none; background-color: transparent;">

        <?php
        if (p('admin_top')) {
          include __DIR__ . '/html/admintopmenu.php';
        }
        if ($pager->edit_boxes) {
          printEditBoxesHeader("full");
        }
        ?>
        <div class="container-holder">

          <?php if($is404): ?>
            <?php box404() ?>
          <?php else: ?>
            <?php BoxPrinter($pager->all_boxes, $pager->edit_boxes, "full"); ?>
          <?php endif ?>
        </div>
        <script>
          jQuery(document).ready(function(){
            $("#mobile-loader").remove();
          });
        </script>
      </div>
    </div>


  </body>
  </html>
<?php endif; ?>
