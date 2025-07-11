<?php
require_once __DIR__ . '/../phive/phive.php';
require_once __DIR__ . '/../phive/modules/Micro/Gpr.php';

phive()->sessionStart();

$gpr = phive('Gpr');

$device_type = phive()->deviceType();
$language = phive('SQL')->escape($_GET[GprFields::LANGUAGE], false);
$jurisdiction = phive('SQL')->escape($_GET[GprFields::JURISDICTION], false);
$game_launch_id = phive('SQL')->escape($_GET[GprFields::GAME_LAUNCH_ID], false);

// simple token validation to avoid people playing with this iframe and its args
if ($_GET[GprFields::IFRAME_TOKEN] !== sha1("vs_{$game_launch_id}_{$_SESSION['token']}")) {
    header('HTTP/1.0 403 Forbidden');
    die('forbidden');
}

$gameplay_url = phive()->isMobile()
    ? $gpr->getMobilePlayUrl($game_launch_id, $language, null, null)
    : $gpr->getDepUrl($game_launch_id, $language);
?>

<!DOCTYPE html>
<html lang="<?= $language ?>" >
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <title></title>
    </head>

    <body>
        <script type="text/javascript">
            const iframe = window.frameElement; // reference to iframe element container
            const device_type = "<?= $device_type ?>";
            const jurisdiction = "<?= $jurisdiction ?>";
            const gameplay_url = "<?= $gameplay_url ?>";
            const disableFullscreen = [
                "ES"
            ];

            // Evolution has own fullscreen which remove top bar and give issue with popups
            // For some countries (Spain for now) we remove option to use Evolution fullscreen
            if((disableFullscreen.indexOf(jurisdiction) <= -1) && device_type === 'android') {
                iframe.setAttribute('allowfullscreen', '');
            }
            if(!gameplay_url) {
                window.top.location.href = '/404';
            }
            iframe.setAttribute("src", "<?= $gameplay_url; ?>");
        </script>
    </body>
</html>


