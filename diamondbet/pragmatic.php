<?php
require_once __DIR__ . '/../phive/phive.php';
phive()->sessionStart();
// Ajax Callback when the user clicks the continue button of the reality check popup (resets the countdown)
if ( isset($_GET['continue']) ) {
    $user = cu($_GET['userid']);
    $interval = phive('Casino')->startAndGetRealityInterval($user);
    phMsetShard(phive('Pragmatic')::PREFIX_MOB_RC_TIMEOUT, '1', $_GET['userid'], (int)$interval * 60);
}

// Normal Game Launch
$hash = $_GET['hash'];

if(empty($hash) || empty($_GET['data']) || phive()->checkSigned($_GET['data'], $hash) === false) {
    phive("Redirect")->to('/');
}

$data = unserialize(base64_decode($_GET['data']));

phive("Localizer")->setLanguage($data['lang'], true);


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head><title></title></head>
    <body style="margin: 0; padding: 0; background-color: #000;">
        <script src="<?php echo $data['jsUrl']; ?>" type="text/javascript" charset="utf-8"></script>
        <script type="text/javascript" charset="utf-8">
        var url = GameLib.gameUrl(
          "<?php echo $data['domain']; ?>", 
          "<?php echo $data['token']; ?>", 
          "<?php echo $data['symbol']; ?>", 
          "<?php echo $data['technology']; ?>", 
          "<?php echo $data['platform']; ?>", 
          "<?php echo $data['language']; ?>", 
          "<?php echo $data['cashierUrl']; ?>", 
          "<?php echo $data['lobbyUrl']; ?>", 
          "<?php echo $data['secureLogin']; ?>"
        );

        window.location = url;
        </script>
        
        <noscript><p>Either scripts and active content are not permitted to run or Adobe Flash Player version is outdated.</p></noscript>
    </body>
</html>
