<?php 
    require_once __DIR__ . '/../phive/phive.php';
    phive()->sessionStart();
    /**
     * Instance of Egt
     * @var $oGp Egt
     */
    $smicro = microtime(true);
    $oGp = phive('Egt');
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
    $target= $_GET['target'] ?? '';

    $launchUrl = $oGp->getIframeUrl($game,$lang,$target);

?>

<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title></title>
</head>
<body>

    <script type="text/javascript">
		var script = window.parent.document.createElement('script');

		script.text = `
						function onExitGamePlatformEGT() {window.location.href = "<?php echo phive()->getSiteUrl();?>";};
						function receiveMessage(event) {
                			if(event.origin === "<?php echo $oGp->getLicSetting("egtdomain");?>") {
                    			if(event.data && event.data.command == "com.egt-bg.exit") {
	  								onExitGamePlatformEGT();
   	  							};
   	  						};
						}
	  						window.addEventListener("message", receiveMessage, false);
        	  		`;
		window.parent.document.getElementsByTagName('head')[0].appendChild(script);

        var iframe = window.frameElement; // reference to iframe element container
        iframe.setAttribute("src", "<?= $launchUrl; ?>");

    </script>
</body>
</html>

