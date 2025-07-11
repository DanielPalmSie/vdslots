<?php
require_once __DIR__ . '/../phive/phive.php';
require_once __DIR__ . '/../phive/html/common.php';

commonRedirects();

$pager 	  = phive('Pager');
$loc 	  = phive('Localizer');
$langtag  = $loc->getCurNonSubLang();

$pagePath = $_SERVER['REQUEST_URI'];
$basePage = $pagePath === '/help/' || $pagePath === '/mobile/help/';

if(!empty($_REQUEST['ul']))
    phive('Localizer')->setLanguage($_REQUEST['ul']);

$pager->initPage($langtag);
$is404 = $pager->is404();
if($is404)
    header("HTTP/1.1 404 Not Found");

$landing_bkg 	= phive('Pager')->fetchSetting("landing_bkg");
$body_style 	= $pager->getBodyStyle();

$css = $GLOBALS['css_extra'] = phive('Pager')->fetchSetting("css");

$menuer = phive('Menuer');

if (phive()->isMobile()) {
    $top_logos = lic('topMobileLogos');
    $secondary_menu = $menuer->forRender($menuer->getSecondaryMobileMenuId());
} else {
    $top_logos = lic('topLogos', ['black']);
    $secondary_menu = $menuer->forRender($menuer->getSecondaryMenuId());
}

$getStyle = function () {
    $sStyle = getBg();
    return !empty($sStyle) ? ' style="' . $sStyle . '"' : '';
};

$cur_lic_iso = lic('getIso');

global $global_onlysetup;
if(!$global_onlysetup):
    ?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//<?php echo strtoupper($langtag) ?>" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

    <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=$langtag?>" lang="<?=$langtag?>">
    <head>
        <meta name="globalsign-domain-verification" content="A-WmnZ0aBgY6RRi1e5xktD9JpI4nTrhyNKLKhcbnbz" />
        <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
        <meta name="google-site-verification" content="OcLKMfgXCSb06FeuMXJLBlXuX9VEw0egjBK76DdZwoU" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
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
        <?php loadBaseJsCss() ?>
        <?php loadJs( "/diamondbet/js/analytics.js" ); ?>
        <?php
        prBoxCSS($pager->all_boxes);
        execBoxEdit($pager);
        loadCss("/diamondbet/css/clean.css");
        loadCss("/diamondbet/css/" . brandedCss() . "help.css");

        if(phive()->isMobile()) {
            loadCss("/diamondbet/css/" . brandedCss() . "mobile.css");
        }
        loadCss("/diamondbet/fonts/icons.css");
        ?>
        <?php include_once( __DIR__.'/html/google_analytics_4.php' ) ?>
        <?php include_once( __DIR__.'/html/external_tracking_head.php' ) ?>
        <?php include_once( __DIR__.'/html/external_tracking_body.php' ) ?>
    </head>

    <body id="help-page" onload="<?php if($pager->edit_strings) echo 'removeAllLinks()'; ?>">

    <div id="wrapper" class="domain-help-page wrapper-<?php echo $cur_lic_iso ?><?php echo $basePage ? ' base-page' : '' ?>" <?php echo $getStyle() ?>>
        <?php
        if(p('admin_top'))
            include __DIR__ . '/html/admintopmenu.php';
        ?>

        <?php if(!empty($top_logos)): ?>
            <div class="gradient-normal rg-top-<?php echo lic('getIso') ?>" id="rg-top-bar">
                <div class="rg-top__container">
                    <?= lic('rgOverAge', [ 'logged-in-time' ,'over-age-desktop']); ?>
                    <?= lic('rgLoginTime', ['rg-top__item logged-in-time']); ?>
                    <?= $top_logos ?>
                </div>
            </div>
        <?php endif ?>
        <?php
        if($pager->edit_boxes)
            printEditBoxesHeader("full");
        ?>

        <div class="top-header">
            <div class="top-header-container">
                <div class="left-item">
                    <a href="/help">
                        <img src="/diamondbet/images/<?= brandedCss() ?>home.svg"/>
                        <span>Help</span>
                    </a>
                </div>
                <div class="center-item"></div>
            </div>
        </div>

        <div class="container-holder">
                <div class="domain-help-container">
                    <div class="domain-help-frame-container ">
                        <?php if($is404): ?>
                            <?php box404() ?>
                        <?php else: ?>
                            <?php BoxPrinter($pager->all_boxes, $pager->edit_boxes, "full"); ?>
                        <?php endif ?>
                    </div>
                    <div class="expand-extra-space"></div>
                    <div class="game-filter">

                        <?php if ( phive()->isMobile() ): ?>

                            <?php phive('Menuer')->renderSecondaryMobileMenu(); ?>

                        <?php else: ?>
                            <?php if(phive('Menuer')->getSetting('secondary_nav', false) && $secondary_menu): ?>
                                <div id="<?= phive('Menuer')->getSecondaryMenuHtmlId() ?>">
                                    <ul>
                                        <?php foreach($secondary_menu as $item): ?>
                                            <li <?php echo $item['current'] ? 'class="active"' : '' ?> id=<?php echo 'sec-menu--' . ($item['alias'] != 'sportsbook'? $item['alias'] : 'sportsbook-prematch') ?>  onclick="secondaryMenuClickHandler('<?php echo $item['alias'] ?>')">
                                                <a <?php echo $item['params'] ?>>
                                                    <span class="icon <?=$item['icon']?>"></span>
                                                    <?php echo $item['txt']?>
                                                </a>
                                            </li>
                                        <?php endforeach ?>
                                    </ul>
                                </div>
                            <?php endif ?>
                        <?php endif ?>

                    </div>
                    <div class="domain-help-footer">
                        <div class="domain-help-footer-container">
                            <?php if(phive()->isMobile()): ?>
                                <?php et2('mobile.footer.section.html', array(date('Y'))) ?>
                            <?php else: ?>
                                <?php et2('footer.section.html') ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
        </div>
    </div>
    </body>
    </html>
<?php endif; ?>
