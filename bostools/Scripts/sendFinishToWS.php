<?php 
    require_once __DIR__ . '/../../../phive/phive.php';
    $uid = 5235886;
    $user = ud($uid);
    $game = 'netent_deadoralive_not_mobile_sw';
    $go_home = 'no';
    $wstag = 'mplimit';
    phive('Casino')->pexecLimit($user, 'mp.finished', $game, $go_home, 000000, $wstag);

?>
