<?php

require_once __DIR__ . '/../phive/phive.php';

phive()->sessionStart();
$swintt = phive('Swintt');

$game_launch_url = $swintt->getGameLaunchUrl($_GET['userid'], $_GET['game_ref'], $_GET['game_name'], $_GET['lang']);

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
    iframe.setAttribute("src", "<?php echo $game_launch_url; ?>");
</script>
</body>
</html>

