<?php 
    require_once __DIR__ . '/../phive/phive.php';
    phive()->sessionStart();
    /**
     * Instance of Evolution
     * @var $oGp Evolution
     */
    $smicro = microtime(true);
    $oGp = phive('Evolution');
    $oGp
    ->injectDependency(phive('Currencer'))
    ->injectDependency(phive('Bonuses'))
    ->injectDependency(phive('MicroGames'))
    ->injectDependency(phive('SQL'))
    ->injectDependency(phive('UserHandler'))
    ->injectDependency(phive('Localizer'))
    ->setStart($smicro);
    // phive()->sessionStart();
    $game= $_GET['game_id'] ?? '';
    $lang= $_GET['lang'] ?? '';
    $target= $_GET['mp_id'] ?? '';

    $launchUrl = $oGp->getIframeUrl($game,$lang,$target);
    $isMobile = phive()->isMobile();
    $jurisdiction = getCountry();
?>

<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title></title>
</head>
<body>

    <script type="text/javascript">
        var iframe = window.frameElement; // reference to iframe element container
        var isMobile = '<?= $isMobile ?>';
        var jurisdiction = '<?= $jurisdiction ?>';
        var disableFullscreen = [
            "ES"
        ]

        // Evolution has own fullscreen which remove top bar and give issue with popups
        // For some countries (Spain for now) we remove option to use Evolution fullscreen
        if((disableFullscreen.indexOf(jurisdiction) <= -1) && isMobile === 'android') {
            iframe.setAttribute('allowfullscreen', '');
        }

        iframe.setAttribute("src", "<?= $launchUrl; ?>");

    </script>
</body>
</html>


