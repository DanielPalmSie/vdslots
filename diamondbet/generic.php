<?php
require_once __DIR__ . '/../phive/phive.php';
require_once __DIR__ . '/../phive/html/common.php';

phive( 'IpGuard' )->block( phive()->getSetting( 'domain' ) );

$loc     = phive( 'Localizer' );
$langtag = $loc->getCurNonSubLang();
$mg      = phive( 'QuickFire' );
$user    = cu();

commonRedirects();

$pager = phive('Pager');
$pager->initPage();
$is404 = $pager->is404();
$isLanding = $pager->isLanding();

if ( $is404 )
    header( "HTTP/1.1 404 Not Found" );

$css = $GLOBALS[ 'css_extra' ] = phive( 'Pager' )->fetchSetting( "css" );
$GLOBALS[ 'site_type' ] = 'normal';

$cur_lic_iso = lic('getIso');

$getClasses = function () use ($pager, $cur_lic_iso) {
    $classes = [];
    if (!empty($cur_lic_iso) && lic('showTopLogos')) { // If inside some jurisdiction and must show a top bar
        $classes[] = "wrapper-$cur_lic_iso"; // Wrap using .wrapper-XX
        $marginClass = lic('insideTopbar', [$pager->getRawPathNoTrailing()]);
        if (!empty($marginClass)) { // For jurisdiction specific pages (like Italian homepage), add a top margin class
            $classes[] = $marginClass;
        }
    }
    if (!empty(phive('Pager')->fetchSetting("stretch_bkg"))) {
        $classes[] = 'stretch';
    }
    if (!empty($classes)) {
        return ' class="' . implode(' ', $classes) . '"';
    } else {
        return "";
    }
};

$getStyle = function () {
    $sStyle = getBg();
    return !empty($sStyle) ? ' style="' . $sStyle . '"' : '';
};

global $global_onlysetup;
if ( !$global_onlysetup ):
  ?>
  <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//<?php strtoupper( $langtag ) ?>" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
  <html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?= $langtag ?>" lang="<?= $langtag ?>">
  <head>
    <meta name="globalsign-domain-verification" content="A-WmnZ0aBgY6RRi1e5xktD9JpI4nTrhyNKLKhcbnbz"/>
    <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
    <meta name="google-site-verification" content="OcLKMfgXCSb06FeuMXJLBlXuX9VEw0egjBK76DdZwoU"/>
    <meta name="google-site-verification" content="8yJY0FKFY0r7ta32g3xQ5z_rycPBgOZuOadsPCK1BeY"/>
    <?php loadMetaTag(); ?>
    <?php phive()->generalIndexerBlock() ?>
    <meta http-equiv="X-UA-Compatible" content="requiresActiveX=true"/>
    <?php addNoindexMetaForSpecificUrls(); ?>
    <?php if ( $desc = $pager->getMetaDescription() ): ?>
      <meta name="description" content="<?= htmlspecialchars( phive( 'Localizer' )->getPotentialString( $desc ) ) ?>"/>
    <?php endif; ?>
    <?php if ( $keywords = $pager->getMetaKeywords() ): ?>
      <meta name="keywords" content="<?= htmlspecialchars( phive( 'Localizer' )->getPotentialString( $keywords ) ) ?>"/>
    <?php endif; ?>
    <?php $pager->getHreflangLinks() ?>
    <?php if ( $pager->bot_block || !$loc->isSelectable() ): ?>
      <meta name="robots" content="noindex">
    <?php endif; ?>

    <?php if ( $title = $pager->getTitle() ): ?>
      <title><?= htmlspecialchars( phive( 'Localizer' )->getPotentialString( $title ) ) ?></title>
    <?php else: ?>
      <title><?php echo phive()->getSiteTitle() ?></title>
    <?php endif; ?>
    <?php loadBasePWAConfig() ?>

    <?php phive('Casino')->setJsVars( 'normal' ) ?>
    <?php loadBaseJsCss() ?>
    <?php loadJs( "/diamondbet/js/analytics.js" ) ?>
    <?php
    loadCss( "/phive/js/selectbox/css/jquery.selectbox.css" );
    loadCss( '/diamondbet/css/top-currency-chooser.css' );
    loadCss( "/diamondbet/css/" . brandedCss() . "extra.css" );

    if( $isLanding) {
        loadCss("/diamondbet/fonts/icons.css");
        loadCss("/diamondbet/css/" . brandedCss() . "landing-page.css");
    }
    prBoxCSS( $pager->all_boxes );
    phive( "BoxHandler" )->boxHtml( 967, 'printCSS' );
    if ( hasMp() ) {
      loadCss( "/diamondbet/css/" . brandedCss() . "tournament.css" );
      loadJs( "/phive/modules/DBUserHandler/js/tournaments.js" );
    }
    if ( !empty( $css ) )
      loadCss( "/diamondbet/css/$css.css" );
    if ( strpos( $_SERVER[ "HTTP_USER_AGENT" ], 'Chrome' ) !== false )
      loadCss( "/diamondbet/css/chrome.css" );
    if ( phive()->ieversion() >= 9 )
      loadCss( "/diamondbet/css/ie9.css" );
    loadJs( "/diamondbet/js/navigation.js" );
    if ( phive()->isTest() )
      loadJs( "/phive/js/jquery.cookie.js" );
    ?>
    <?php if ( $pager->edit_boxes || $pager->edit_strings ): ?>
      <?php //  loadJs( "/phive/modules/Ajaculator/prototype.js" ); ?>
      <script type="text/javascript" charset="utf-8">
        function removeAllLinks () {

          var all = $('#bg_layer1').find('*')
          all.each(function(){
            if($(this).is("a")){
                $(this).remove("href")
            }
          });
//          var all = $( 'bg_layer1' ).descendants();
//          all.each( function ( c ) {
//            if ( c.tagName == 'A' )
//              c.removeAttribute( 'href' );
//          } );

        }
      </script>
    <?php endif; ?>
    <?php if ( strpos( $_SERVER[ "HTTP_USER_AGENT" ], 'MSIE' ) !== false ): ?>
    <?php endif ?>
    <?php include( __DIR__ . '/html/google_analytics_4.php' ) ?>
    <?php include( __DIR__ . '/html/external_tracking_head.php' ) ?>
    <?php include( __DIR__ . '/html/external_tracking_body.php' ) ?>
  </head>

  <body onload="<?php if ( $pager->edit_strings ) echo 'removeAllLinks()'; ?>" class="generic"
        id="<?php if ($isLanding) echo 'landing-page-container'; ?>" >

  <div<?php echo $getClasses() ?> id="wrapper"<?php echo $getStyle() ?>>
    <?php if ( !empty( $_GET[ 'fullscreen' ] ) ): ?>
    <?php else: ?>
  <?php
  if ( p( 'admin_top' ) )
    include __DIR__ . '/html/admintopmenu.php';
  include __DIR__ . '/html/javascriptcheck.php';

  loadCookiesBaseFile();
  if($isLanding) {
      include( __DIR__ . '/html/topcommon.php' );
  }else{
      include( __DIR__ . '/html/top.php' );
  }
  if ( $pager->edit_boxes )
    printEditBoxesHeader( "full" );
  ?>
    <div class="container-holder">
      <?php
      if ( $is404 )
        box404();
      else
        BoxPrinter( $pager->all_boxes, $pager->edit_boxes, "full" );

      if( !$isLanding) {
          include( __DIR__ . '/html/footermenu.php' );
          include __DIR__ . '/html/footer.php';
      }
      ?>
      <?php endif ?>
    </div>

    <?php if ( !phive()->isLocal() ):
      $pixelurl = phive( "Affiliater" )->pixelUrlFromUser( $_SESSION[ 'mg_id' ] );
      ?>
      <?php if ( !empty( $pixelurl ) ): ?>
      <img src="<?php echo $pixelurl ?>" style="width: 0px; height: 0px;"/>
    <?php endif ?>
    <?php endif ?>

    <?php depositTopBar() ?>
  </div>
  </div>
  <?php showRCPopupFromTestParam() ?>
  </body>
  </html>
<?php endif; ?>
