<?php
require_once __DIR__ . '/../phive/phive.php';
phive()->sessionStart();
$stakelogic = phive('Stakelogic');
$response = $stakelogic->createGameSession($_GET['uid'], $_GET['gid'], $_GET['lang'], $_GET['device']);
$lobby_url = $stakelogic->getLobbyUrl(false, $_GET['lang']);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head><title>Stakelogic - Game</title></head>
    <body style="margin: 0; padding: 0; background-color: #000;">
        <ngc-game ngc-server-url="<?php echo $response['gameUrl']; ?>" ngc-options="videoslots=1"></ngc-game>
        <script src="<?php echo $response['jsUrl']; ?>" type="text/javascript" charset="utf-8"></script>
        <script type="text/javascript" charset="utf-8">
        	ngc.gameElementsInit(function(geType){
                if(geType == gcw.api.GameEventType.HOME) {
                    // handle home btn click here
                    window.top.location.href = '<?php echo $lobby_url ?>';
                }
            });
        </script>
        <noscript><p>Either scripts and active content are not permitted to run or Adobe Flash Player version is outdated.</p></noscript>
    </body>
</html>
