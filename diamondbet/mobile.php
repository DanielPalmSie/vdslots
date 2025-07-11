<?php
require_once __DIR__ . '/../phive/phive.php';
require_once __DIR__ . '/../phive/html/common.php';

phive('Localizer')->redirectToUsersNonSub('/mobile/', 1, 'mobile_start_lang');

commonRedirects();

if (!empty($_GET['newsite_go_back_url'])) {
    /** @var URL $p_url */
    $p_url = phive('Http/URL');
    $_SESSION['show_go_back_to_bos'] = true;
    $_SESSION['newsite_go_back_url'] = $p_url->prependMobileDirPart($_GET['newsite_go_back_url']);
}
//phive()->dumpTbl('mobile_server', $_SERVER);

$pager = phive('Pager');

$langtag = phive()->getModule('Localizer')->getCurNonSubLang();

$pager->initPage($langtag);
$is404 = $pager->is404();
$isLanding = $pager->isLanding();

if ($is404) {
  header("HTTP/1.1 404 Not Found");
}

$landing_bkg = phive('Pager')->fetchSetting("landing_bkg");
$body_style = $pager->getBodyStyle();

$css = $GLOBALS['css_extra'] = phive('Pager')->fetchSetting("css");

$GLOBALS['site_type'] = 'mobile';

// show mobile menu only when we are not on those pages
$show_mobile_menu = !(
    substr_count($_SERVER['REQUEST_URI'], '/mobile/rg-occupation') > 0 ||
    substr_count($_SERVER['REQUEST_URI'], '/mobile/rg-deposit') > 0 ||
    substr_count($_SERVER['REQUEST_URI'], '/mobile/rg-login') > 0 ||
    substr_count($_SERVER['REQUEST_URI'], '/mobile/rg-change-deposit-before-play') > 0 ||
    substr_count($_SERVER['REQUEST_URI'], '/mobile/rg-activity') > 0 ||
    substr_count($_SERVER['REQUEST_URI'], '/mobile/rg-net-deposit-info') > 0 ||
    substr_count($_SERVER['REQUEST_URI'], '/mobile/register') > 0 ||
    substr_count($_SERVER['REQUEST_URI'], '/mobile/login') > 0 ||
    substr_count($_SERVER['REQUEST_URI'], '/mobile/customer-service') > 0
);

$not_play = substr_count($_SERVER['REQUEST_URI'], '/mobile/playgame') === 0;

$cur_lic_iso = lic('topMobileLogos') ? lic('getIso') : '';

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
    <?php phive()->generalIndexerBlock() ?>
      <?php if (phive()->getSetting('viewport')): ?>
          <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
      <?php else: ?>
        <?php if (isIphone()): ?>
          <meta name="apple-mobile-web-app-capable" content="yes"/>
          <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
          <meta name="theme-color" content="black">
          <!-- maximum-scale=1 is used here to prevent auto zoom on IPhone devices when clicked on input with font-size less than 16px -->
          <!-- https://lukeplant.me.uk/blog/posts/you-can-stop-using-user-scalable-no-and-maximum-scale-1-in-viewport-meta-tags-now/ -->
          <meta id="viewport" name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
        <?php elseif (isIpad()): ?>
          <?php echo $ios7_app_meta ?>
        <?php else: ?>
          <meta name="mobile-web-app-capable" content="yes">
          <meta name="theme-color" content="black">
          <meta id="viewport" name="viewport" content="width=400">
        <?php endif ?>
      <?php endif; ?>

    <?php loadBasePWAConfig() ?>
    <?php loadBaseAppleIcons() ?>

    <?php loadJs("/phive/js/stay_standalone.js") ?>

    <?php if (phive()->getSetting('activate_prefetch_assets_from_vue')): ?>
    <!-- Battle of Slots Mobile  prefetching Block -->
    <link rel="prefetch" href="/static/images/bos/bos-logo-trimmed.png" as="image">
    <link rel="prefetch" href="/static/images/bos/battle-of-slots.png" as="image">
    <link rel="prefetch" href="/static/images/logo.png" as="image">
    <link rel="prefetch" href="/static/fonts/styles.css" as="style">
    <link rel="prefetch"  href="/static/styles/vs-preloader.css" as="style">
    <link rel="prefetch" href="/static/fonts/fonts/videoslots-font.eot" as="font">
    <link rel="prefetch" href="/static/fonts/fonts/videoslots-font.woff" as="font">
    <link rel="prefetch" href="/static/fonts/fonts/videoslots-font.ttf" as="font">
    <link rel="prefetch" href="/static/fonts/fonts/videoslots-font.svg" as="font">
    <link rel="prefetch" href="/app.js" as="script">
    <!-- Battle of Slots Mobile  prefetching Block -->
    <?php endif; ?>

    <?php if ($desc=$pager->getMetaDescription()): ?>
      <meta name="description" content="<?=htmlspecialchars(phive('Localizer')->getPotentialString($desc))?>" />
    <?php endif; ?>
    <?php if ($keywords=$pager->getMetaKeywords()): ?>
      <meta name="keywords" content="<?=htmlspecialchars(phive('Localizer')->getPotentialString($keywords))?>" />
    <?php endif; ?>
    <?php $pager->getHreflangLinks() ?>
    <?php if($pager->bot_block): ?>
      <meta name="robots" content="noindex">
    <?php else: ?>
      <meta name="robots" content="index">
    <?php endif; ?>

    <?php if ($title=$pager->getTitle()): ?>
      <title><?=htmlspecialchars(phive('Localizer')->getPotentialString($title))?></title>
    <?php else: ?>
        <title><?php echo phive()->getSiteTitle() ?></title>
    <?php endif; ?>

    <?php
    loadJs("/phive/js/jquery.min.js");
    phive('Casino')->setJsVars('mobile');
    if (phive()->deviceType() == 'iphone') {
      loadCss("/diamondbet/css/iphone.css");
    }
    loadBaseJsCss();
    loadJs("/diamondbet/js/analytics.js");
    loadJs("/diamondbet/js/navigation.js");
    loadJs("/phive/modules/Micro/mobile_play_mode.js");
    loadJs("/phive/js/reality_checks.js");
    loadJs("/phive/js/iscroll-4/iscroll.js");
    loadJs("/phive/js/jquery.scrollTo.js");
    loadJs("/phive/js/xui-2.3.2.js");
    loadJs("/phive/js/xui.swipe.js");
    loadJs('/phive/js/mobile.js');
    prBoxCSS($pager->all_boxes);
    if (hasMp()) {
        // TODO uncomment this in order to make the BoS start popup look good, currently
        // off because it's unkown if it would screw up some other layouts, plus the link in the
        // start link to the BoS links to desktop so needs to be fixed.
        // loadCss( "/diamondbet/css/tournament.css" );
        loadJs("/phive/modules/DBUserHandler/js/tournaments.js");
    }
    loadCss("/diamondbet/css/" . brandedCss() . "mobile.css");
    loadCss("/diamondbet/fonts/icons.css");
    loadCss("/diamondbet/css/" . brandedCss() . "mobile-top-menu.css");

    if( $isLanding) {
        loadCss("/diamondbet/css/" . brandedCss() . "landing-page.css");
    }

    if (!empty($css)) {
      loadCss("/diamondbet/css/$css.css");
    }
    if (isIpad()) {
      loadCss("/diamondbet/css/ipad.css");
    }

    execBoxEdit($pager);
    ?>
    <?php include(__DIR__.'/html/google_analytics_4.php' ) ?>
    <?php include(__DIR__.'/html/external_tracking_head.php') ?>
    <?php include(__DIR__.'/html/external_tracking_body.php') ?>
  </head>

  <?php if(!empty($landing_bkg)): ?>
  <body
    onload="<?php if($pager->edit_strings) echo 'removeAllLinks()'; ?>"
    style="background: #000;"
    id="<?php if ($isLanding) echo 'landing-page-container'; ?>"
    class="<?php if (isIphone()) echo 'iphone'; ?>"
  >
  <?php else: ?>
  <body
    onload="<?php if($pager->edit_strings) echo 'removeAllLinks()'; ?>"
    id="<?php if ($isLanding) echo 'landing-page-container'; ?>"
    class="<?php if (isIphone()) echo 'iphone'; ?>"
  >
  <?php endif ?>


  <?php

  loadCookiesBaseFile();

  if (!$pager->edit_boxes) {
      include(__DIR__ . '/html/mobile-loader.php');
  }
  $sticky = !empty(lic('topMobileLogos')) && !$show_mobile_menu;

  $class = !empty($cur_lic_iso) ? " wrapper-$cur_lic_iso " : '';

  // Build the wrapper class
  if (empty($cur_lic_iso)) { // If no jurisdiction specific case
      $class = '';
  } else { // If inside a jurisdiction
      $class = " wrapper-$cur_lic_iso ";
      if (lic('showTopLogos')) { // If must show a top bar for that jurisdiction
          // Add a top margin class for specific pages (like Italian homepage)
          $class .= lic('insideTopbar', [$pager->getRawPathNoTrailing()]);
      }
  }

  $class .= !empty($_SESSION['show_go_back_to_bos']) ? 'show_go_back_to_bos ' : '';
  $class .= $sticky ? ' has-sticky-bar ' : '';
  $id = $show_mobile_menu ? 'wrapper-container' : 'wrapper-container-reg';
  ?>

  <div class="<? echo $class ?>" id="<? echo $id ?>">
    <?php include(__DIR__ . '/html/mobile-left-menu.php'); ?>

    <?php if(!empty($landing_bkg)): ?>
    <div id="wrapper" style="<?php echo $body_style." background-image: url(".fupUri($landing_bkg, true).");" ?>">
      <?php else: ?>
      <div id="wrapper">
        <?php endif ?>

        <?php
        // do not show mobile top on new registration page
        if (!$isLanding && $show_mobile_menu && $not_play) {
            include(__DIR__ . '/html/mobile-top.php');
        }else{
            include( __DIR__ . '/html/topcommon.php' );
        }

        if (p('admin_top')) {
          include __DIR__ . '/html/admintopmenu.php';
        }

        if ($pager->edit_boxes) {
          printEditBoxesHeader("full");
        }
        ?>
        <div class="container-holder" style="<?=phive()->isMobileApp() ? 'padding-top:0;' : ''?>">


          <?php if($is404): ?>
            <?php box404() ?>
          <?php else: ?>
            <?php BoxPrinter($pager->all_boxes, $pager->edit_boxes, "full"); ?>
          <?php endif ?>

          <?php
          // do not show mobile footer on new registration page
          if (!$isLanding && $show_mobile_menu && $not_play) {
              include __DIR__.'/html/mobilefooter.php';
          }
          include __DIR__ . '/html/chat-support.php';
          ?>
        </div>
        <script>
          jQuery(document).ready(function(){
            $("#mobile-loader").remove();
            $('#mobile-left-menu').show();
          });

          if (isIos()) {
            document.documentElement.addEventListener('touchmove', function (event) {
                event.preventDefault();
            }, false);
          }

        </script>
      </div>
    </div>
  <?php showRCPopupFromTestParam() ?>
  </body>
  </html>
<?php endif; ?>
