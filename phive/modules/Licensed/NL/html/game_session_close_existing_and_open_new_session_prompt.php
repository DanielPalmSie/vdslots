<?php

?>

<div class="session-balance-popup session-terminated">
    <div class="session-balance-popup__part">
        <h3><?php et('game.session.activity') ?></h3>
        <p><?php et('rg.game-session-terminated.description-1'); ?></p>
        <p><?php et('rg.game-session-terminated.description-2'); ?></p>
        <p><?php et('rg.game-session-terminated.description-3'); ?></p>
        <div class="session-balance-popup__form">
            <div class="session-balance-popup__button-container">
                <?php btnDefaultXl(t('new.game.session'), '', "lic('openNewGameSession')") ?>
            </div>
            <div class="session-balance-popup__button-container">
                <?php btnDefaultXl(t('ok'), '', "lic('closeNewGameSession')") ?>
            </div>
        </div>
    </div>
</div>
