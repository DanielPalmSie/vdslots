<?php
$game_id = $_POST['game_id'];
$js_data = ['game_id' => $game_id];
if (phive()->isMobile()) {
    $game_ref = $_POST['game_ref'] ?? phive('MicroGames')->getGameRefByIdWithAutoDeviceDetect($_POST['game_id']);
    $js_data = ['game_ref' => $game_ref];
}
$js_data = json_encode(array_merge($js_data, ['close_old_session' => true]));
$mobile_path = phive()->isMobile() ? 'mobile/' : '';
?>

<script>
    function newGameSession() {
        <?php if (!empty($_POST['submit_new_game_session'])): ?>
        window.submitNewGameSession()
        <?php else: ?>
        lic('showExternalGameSessionPopup', ['game_session_balance_set', <?= $js_data ?>]);
        <?php endif; ?>
    }

    function continueExistingSession() {
        mboxClose('game_session_close_existing_and_open_new_session_prompt', function () {
            licJson('getCurrentGame', {}, function (response) {
                if (!response.success) {
                    window.location.href = '/';
                }

                let selectedGame = window.gameSelected || {};
                var current_game_from_session = response.current_game || {};
                var is_current_game_empty = Object.keys(current_game_from_session).length === 0;

                if (!isMobile()) {
                    if (is_current_game_empty || (current_game_from_session.ext_game_name !== selectedGame.ext_game_name)) {
                        window.location.href = '/';
                    }
                    return;
                }

                if (!window.location.pathname.includes(current_game_from_session.game_url)) {
                    window.location.href = '/';
                    return;
                }
                

                $('body').removeClass('has-popup-overlay');
                $('#vs-popup-overlay')
                    .removeClass('animation__top--bottom-to-top')
                    .addClass('animation__top--top-to-bottom')
                    .one('webkitAnimationEnd oanimationend msAnimationEnd animationend', function (e) {
                        $(this).remove();
                        
                        if (window.fixGameFocus) {
                            window.fixGameFocus();
                        }

                        if (window.MessageProcessor) {
                            window.MessageProcessor.resumeGame();;
                        }
                    });
            });
        });
    }
</script>

<div class="session-balance-popup session-terminated">
    <div class="session-balance-popup__part">
        <h3><?php et('game.session.activity') ?></h3>
        <p><?php et('rg.game-session-terminated.description-1'); ?></p>
        <p><?php et('rg.game-session-terminated.description-2'); ?></p>
        <p><?php et('rg.game-session-terminated.description-3'); ?></p>
        <div class="session-balance-popup__form">
            <div class="session-balance-popup__button-container">
                <?php btnDefaultXl(t('new.game.session'), '', "newGameSession(event)", '', '') ?>
            </div>
            <div class="session-balance-popup__button-container">
                <?php btnDefaultXl(t('ok'), '', "continueExistingSession()", '', '') ?>
            </div>
        </div>
    </div>
</div>