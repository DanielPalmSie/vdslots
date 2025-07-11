<?php

require_once __DIR__ . '/../phive/phive.php';
phive()->sessionStart();

/*
    CHECK IF: BOUNCE BACK FROM TOUCH DEVICES

*/
$lobbyUrl = phive('Casino')->getLobbyUrl(false, $_GET['lang']);
$lobbyUrl = phive('Casino')->wrapUrlInJsForRedirect($lobbyUrl);

if (isset($_GET['close_game'])) {
    // we have to call the parent javascript function for closing the iframe and redirect to lobby
    ?>
    <script type="text/javascript">
        window.top.location.href = '<?= $lobbyUrl?>';
    </script>
    <?php
    exit(0);
}

$tom_horn = phive('Tomhorn');

$game_id    = $_GET['game_ref'];
$user_id    = $_GET['userid'];
$lang       = $_GET['lang'];
$device     = $_GET['device'];

$user = cu($user_id);

$params = $tom_horn->getGameModuleParams($game_id, $user_id); // we pass in the session value so we have the tournament id

$tom_horn->log(print_r($params, true), 'launch-params');

$base_url = '';

$GameModuleParams = [];

foreach ($params['Parameters'] as  $params) {
    if($params['Key'] == 'param:base') {
        $base_url = $params['Value'];
    }
    $GameModuleParams[$params['Key']] = $params['Value'];
}

$GameModuleParams['width'] = '100%';
$GameModuleParams['height'] = '100%';
$GameModuleParams['var:lang'] = phive('Localizer')->getLocale($lang, 'langtag');

$varRealityCheck = ['var:realitycheck_startduration', 'var:realitycheck_interval', 'var:realitycheck_historyurl'];

if($tom_horn->getRcPopup($device, $user) == 'ingame') {
    $GameModuleParams = array_merge($GameModuleParams,(array)$tom_horn->getRealityCheckParameters($user, false, $varRealityCheck));
}

$tom_horn->log(print_r($GameModuleParams, true), 'GameModuleParams');

?>

<html>
<head>
    <title>Tom Horn Game Page</title>
    <style>
        h2 {
            color: white;
        }

        iframe#casinoClient {
            border: 0;
        }
    </style>
</head>

<body style="margin: 0; padding: 0;">

<div id="gameClientPlaceholder">
    <h2>Loading game...</h2>
</div>

<script type="text/javascript" src="<?= $base_url ?>ClientUtils.js"></script>

<?php
loadJs("/phive/js/swfobject.js");
loadJs("/phive/js/underscore.js");
loadJs("/phive/js/jquery.min.js");
loadJs("/phive/js/gameplay.js");
?>

<script type="text/javascript">
    var params = <?php echo json_encode($GameModuleParams); ?>;
    renderClient(params, 'gameClientPlaceholder');
</script>

</body>
</html>

