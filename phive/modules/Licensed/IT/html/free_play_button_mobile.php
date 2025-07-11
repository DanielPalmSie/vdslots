<?php
$mg = phive('MicroGames');
$game = $mg->getByGameUrl($_GET['arg0'], 1);
$mobile_game = $mg->getMobileGame($game);
?>
<button class="btn btn-m btn-mobile-game-review btn-mobile-game-play-fun"
        onclick="playMobileGame('<?= $mobile_game['ext_game_name'] ?>', true)">
    <div class="icon icon-vs-slot-machine">
        <?php echo t('play.free'); ?>
    </div>
</button>