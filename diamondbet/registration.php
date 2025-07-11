<?php
require_once __DIR__ . '/../phive/phive.php';
require_once __DIR__ . '/../phive/html/common.php';

phive()->sessionStart();

$pager 	  = phive('Pager');
$loc 	  = phive('Localizer');
$langtag  = $loc->getCurNonSubLang();

if(!isLogged() && !empty($_REQUEST['loginname']))
  $user = phive('UserHandler')->login($_REQUEST['loginname'], $_REQUEST['password']);
else
  $user = cu();
baseRedirects($langtag, $user);

if(!empty($_REQUEST['ul']))
  phive('Localizer')->setLanguage($_REQUEST['ul']);

$pager->initPage($langtag);
$is404 = $pager->is404();
if($is404)
  header("HTTP/1.1 404 Not Found");

$landing_bkg 	= phive('Pager')->fetchSetting("landing_bkg");
$body_style 	= $pager->getBodyStyle();

$css = $GLOBALS['css_extra'] = phive('Pager')->fetchSetting("css");

global $global_onlysetup;
if(!$global_onlysetup):
  ?>
  <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//<?php echo strtoupper($langtag) ?>" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

  <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=$langtag?>" lang="<?=$langtag?>">
  <head>
    <meta name="globalsign-domain-verification" content="A-WmnZ0aBgY6RRi1e5xktD9JpI4nTrhyNKLKhcbnbz" />
    <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
    <meta name="google-site-verification" content="OcLKMfgXCSb06FeuMXJLBlXuX9VEw0egjBK76DdZwoU" />
    <?php loadMetaTag(); ?>
    
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
    <?php loadBasePWAConfig() ?>
    <?php phive('Casino')->setJsVars(!phive()->isMobile() ? 'normal' : 'mobile') ?>
    <?php loadBaseJsCss() ?>
    <?php loadJs( "/diamondbet/js/analytics.js" ); ?>
    <?php
    prBoxCSS($pager->all_boxes);
    if(hasMp()){
      loadCss("/diamondbet/css/" . brandedCss() . "tournament.css");
      loadJs("/phive/modules/DBUserHandler/js/tournaments.js");
    }
    drawFancyJs();
    execBoxEdit($pager);
    loadCss("/diamondbet/css/clean.css");
    loadCss("/diamondbet/css/registration.css");
    if(!empty($css)){
      loadCss("/diamondbet/css/$css.css");
    }
    ?>
    <?php include_once( __DIR__.'/html/google_analytics_4.php' ) ?>
    <?php include_once( __DIR__.'/html/external_tracking_head.php' ) ?>
    <?php include_once( __DIR__.'/html/external_tracking_body.php' ) ?>
  </head>

  <?php if(!empty($landing_bkg)): ?>
  <body onload="<?php if($pager->edit_strings) echo 'removeAllLinks()'; ?>">
  <?php else: ?>
  <body onload="<?php if($pager->edit_strings) echo 'removeAllLinks()'; ?>">
  <?php endif ?>

  <div id="wrapper2">
    <?php
    if(p('admin_top'))
      include __DIR__ . '/html/admintopmenu.php';

    include( __DIR__ . '/html/top_base_js.php' );
    include __DIR__ . '/html/javascriptcheck.php';

    if($pager->edit_boxes)
      printEditBoxesHeader("full");
    ?>
    <div class="container-holder">

      <?php if($is404): ?>
        <?php box404() ?>
      <?php else: ?>
        <?php BoxPrinter($pager->all_boxes, $pager->edit_boxes, "full"); ?>
      <?php endif ?>

    </div>
  </div>
  </body>
  </html>
<?php endif; ?>
