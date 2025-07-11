<?php
/**
 * This file is called by microgaming directly, is set on their BO under the setting
 * "features.inGameInterface.interfaceUrl": "https://www.videoslots.com/diamondbet/reality_checks/microgaming.php"
 *
 * DO NOT REMOVE this file for now...
 *
 * they will soon provide a better way to deal with RC and GC stuff, so we will remove this after we properly integrate in the new way.
 */

require_once __DIR__ . '/../../phive/phive.php';
$reality_check_interval = phive('Casino')->startAndGetRealityInterval();
if (phive("Config")->getValue('reality-check-mobile', 'microgaming') !== 'on' || empty($reality_check_interval)) {
    exit;
}
$user = ud();
$username = $user['username'];
unset($user);
$lang = phive('Localizer')->getCurNonSubLang();
$default_lang = phive('Localizer')->getDefaultLanguage();
$pluginUrl = preg_replace("/^http:/i", "https:", $_GET['p']);
?>
<!DOCTYPE html>
<html>
<head>
    <script>
        var cur_lang = '<?=$lang?>';
        var default_lang = '<?=$default_lang?>';
    </script>
    <?php loadJs("/phive/js/jquery.min.js"); ?>
    <?php loadJs("/phive/js/mg_casino.js") ?>
    <?php loadJs("/phive/js/reality_checks.js"); ?>
    <?php loadJs("/phive/js/utility.js"); ?>
    <?php loadJs("/phive/js/multibox.js"); ?>
    <?php loadCss("/diamondbet/css/" . brandedCss() . "reality_checks.css"); ?>
    <script type="text/javascript" src="<?= $pluginUrl  ?>/MobileWebGames/js/InterfaceApi/InterfaceApi.js"></script>
    <script type="text/javascript">
        var realitychecktimeout = 60 * <?= $reality_check_interval ?>;
        var realitycheckmessage1 = '<? et('reality-check.msg.elapsedtime',$lang)?>';
        var timer;
        var messageToShow;
        var duration = 0;
        var mg = '';
        window.onload = function () {
            mgs.inGameInterface.init();
            mg = mgs
            reality_checks_js_mobile.startRc();
        }
        window.onerror = function () {}

        function pluginInit() {
            if (typeof mg == 'object') {
                mg.inGameInterface.setMode(mg.inGameInterface.modes.hidden);
            }
        }

        function realityCheckMsg() {
            rc_msg = document.getElementById('rc_msg');
            rc_msg.innerHTML = messageToShow;
            mg.inGameInterface.preventGameplay();
            mg.inGameInterface.setMode(mg.inGameInterface.modes.fullscreen);
        }


        function closeAndResumeGame() {
            mg.inGameInterface.setMode(mg.inGameInterface.modes.hidden);
            mg.inGameInterface.allowGameplay();
        }

        function gameHistory() {
            window.open("<?= phive()->getSiteUrl() ?>/account/<?= $username ?>/game-history/")
        }

        function stopPlay() {
            window.top.location.href = '<?= phive()->getSiteUrl() ?>/mobile/'
        }

        function showExtraClock() {}

    </script>
    <meta charset="UTF-8">
    <title>Reality Check Component</title>
</head>
<body>
<?
include '../html/reality_check.php';
?>
</body>
</html>
