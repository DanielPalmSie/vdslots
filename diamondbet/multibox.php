<?php
require_once __DIR__ . '/../phive/phive.php';
require_once __DIR__ . '/html/display.php';
require_once __DIR__ . '/../phive/html/common.php';

$langtag = phive('Localizer')->getCurNonSubLang();
$cur_player = cuPl();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//<?php echo strtoupper( $langtag ) ?>" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?= $langtag ?>" lang="<?= $langtag ?>">
    <head>
        <meta name="globalsign-domain-verification" content="A-WmnZ0aBgY6RRi1e5xktD9JpI4nTrhyNKLKhcbnbz"/>
        <meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
        <meta name="robots" content="noindex">
        <title>VideoSlots.com</title>
        <?php
        phive('Casino')->setJsVars( 'normal' );
        loadBaseJsCss(false);
        drawFancyJs();
        loadCss( "/diamondbet/css/clean.css" );
        ?>
    </head>
    <div id="wrapper" style="background: #fff;">
        <div class="container-holder">
            <?php moduleHtml($_REQUEST['module'], $_REQUEST['file'], false, phive('Licensed')->getLicCountry($cur_player)) ?>
        </div>
    </div>
  </body>
</html>
