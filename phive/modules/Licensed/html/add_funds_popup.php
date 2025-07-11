<?php
// TODO: Read session values
$session_balance_limit = 500;
$limit_left = 50.04;
$game_session_limit_left = 500;
?>
<script>
    function submitAddFunds(e) {
        e.preventDefault();

        var session = window.extSessHandler.activeSessions[0];
        window.extSessHandler.showSessionBalancePopup('balance_addin_popup', session);
    }

    function cancelAddFunds(e) {
        e.preventDefault();
        // goTo(llink('/'));
        parent.removeChild();
        // closeIFrame();
        <?php // TODO: cancel without reloading page (parent removeChild(), closeIFrame() or similar ?) ?>
    }
</script>
<div class="session-balance-popup">
    <div class="session-balance-popup__part">
        <p>
            <span>would you like to add more funds to your <?php et('add.funds.question') ?></span>
        </p>
        <h2><?= t('session.balance.limit') ?> <span style="font-weight: normal;"><?= $session_balance_limit . cs() ?></span></h2>
        <h2><?= t('limit.left') ?> <span style="font-weight: normal;"><?= $limit_left . cs() ?></span></h2>
        <form id="add-funds-popup__form" class="session-balance-popup__form">
            <div class="session-balance-popup__form-field">
                <label for="set-add-funds">
                    <?php et("rg.info.game-session-balance.under1k-label") ?>
                    <span>(<?= cs() ?>)</span>
                </label>
                <input
                        class="input-normal big-input full-width flat-input"
                        name="set-add-funds"
                        id="set-add-funds"
                />
                <span class="right"><?php et2('rg.info.game-session-balance.under1k.limit-left', [cs(), $game_session_limit_left]); ?></span>
                <span class="hidden set-session-balance-over-limit error"><?php et('rg.info.game-session-balance.over-limit'); ?></span>
            </div>
            <div class="session-balance-popup__button-container">
                <?php btnCancelXl(t('cancel'), '', "cancelAddFunds()", '', '') ?>
                <?php btnDefaultL(t('add.funds'), '', "submitAddFunds(event)", '', 'set-session-balance-button') ?>
            </div>
        </form>

    </div>
</div>
