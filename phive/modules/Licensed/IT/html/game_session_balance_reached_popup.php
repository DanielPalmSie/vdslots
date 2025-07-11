<?php if (empty(phive()->isMobile())): ?>
    <style>
        #mbox-msg {
            min-width: 450px !important;
        }
    </style>
<? else: ?>
    <style>
        #mbox-msg {
            width: 100% !important;
        }

        .lic-mbox-container {
            height: calc(100vh - 103px);
        }
    </style>
<? endif; ?>

<script>
    function goToLobby(e) {
        e.preventDefault();
        goTo(llink('/'));
    }
</script>

<div class="session-balance-popup">
    <div class="session-balance-popup__part">
        <p>
            <?php et('rg.info.game-session-balance.reached.line-1') ?>
        </p>
        <p>
            <?php et('rg.info.game-session-balance.reached.line-2') ?>
        </p>
        <div class="session-balance-popup__form">
            <div class="session-balance-popup__button-container">
                <?php btnDefaultL(t('exit.game'), '', "goToLobby(event)", '', '') ?>
            </div>
        </div>
    </div>

</div>
