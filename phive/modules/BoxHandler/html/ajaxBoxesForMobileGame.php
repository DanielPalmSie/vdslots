<?php
require_once __DIR__ . '/../../../phive.php';
require_once '../../../html/common.php';

phive('Localizer')->setFromReq();
$cur_player = cuPl();
$GLOBALS['site_type'] = 'mobile';

?>
<html>
    <head>
        <meta charset="UTF-8">
<?php
// Main js / css that needs to be loaded for the old site pages
phive('Casino')->setJsVars('mobile');
loadBaseJsCss();
prBoxCSS();

loadCss("/diamondbet/css/" . brandedCss() . "mobile.css");

// Rules taken from mobile.php // TODO check what we can remove from this...
if(phive()->deviceType() == 'iphone')
    loadCss("/diamondbet/css/iphone.css");
//loadCss("/diamondbet/fonts/icons.css");
//loadCss("/diamondbet/css/" . brandedCss() . "mobile-top-menu.css");
if(isIpad())
    loadCss("/diamondbet/css/ipad.css");

// special JS / CSS rules that applies only when the page is loaded inside the iframe in the new mobile game mode.
loadJs('/diamondbet/mobile_game/js/old-pages-inside-iframe.js');
loadCss('/diamondbet/mobile_game/css/old-pages-inside-iframe.css');

// Extra code that needs to be injected in the header (Ex. JS/CSS only for a specific page)
switch ($_GET['load_box']) {
    case 'my-prize':
        // contains some functions required in the logic when we activate an award.
        loadJs("/phive/modules/Micro/mobile_play_mode.js");
        loadJs("/phive/js/reality_checks.js");

        // multibox js/css
        loadJs("/phive/js/multibox.js");
        /*loadCss("/phive/js/fancybox/fancybox/jquery.fancybox-1.3.4.css");*/
        loadCss("/diamondbet/css/" . brandedCss() . "fancybox.css");
        break;
}

?>
    </head>
    <body>
<?php

switch ($_GET['load_box']) {
    case 'my-prize':
        // we cannot use boxHtml(xxx) here cause a page / box pointing directly to TrophyListBox doesn't exist on DB.
        $trophy_box = phive('BoxHandler')->getRawBox('TrophyListBox');
        $trophy_box->init($cur_player);
        $trophy_box->myRewards($cur_player);
        break;
        /*
           // Not needed as the new iframe template replaces this but we keep it a bit longer just in case. /Henrik
    case 'deposit':
        loadJs("/phive/js/multibox.js");
        loadCss("/diamondbet/css/" . brandedCss() . "fancybox.css");
        //loadCss("/diamondbet/css/" . brandedCss() . "mobile.css");
        loadCss("/diamondbet/css/cashier.css");
        //loadCss("/diamondbet/css/cashier2mobile.css");
        phive('BoxHandler')->getRawBox('MobileDepositBox')->printCSS();
        phive("BoxHandler")->boxHtml(852); // DepositBox for Mobile deposit page
        break;
        */
}

?>
    </body>
</html>
