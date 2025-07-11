<?php
$user = cuPl();
$rg = rgLimits();
$type = 'deposit';
$session_entry = (array)json_decode(phMgetShard('ext-game-session-data', $user->getId()));
phMdelShard('ext-game-session-data', $user->getId());
$session_balance_settings = lic('getExtGameSessionStakes', [$user], $user);
$all_open_sessions_balance = (lic('getLicSessionService', [$user], $user))->getAllOpenSessionsBalance($user);
$game_ref = $_POST['game_ref'] ?? phive('MicroGames')->getGameRefByIdWithAutoDeviceDetect($_POST['game_id']);
$balance = lic('getMetaGameSessionBalance', [$user], $user);
$stake = $session_entry['stake'];

$session_balance_settings = lic('getExtGameSessionStakes', [$user], $user); // The 1000 max session stake
$all_open_sessions_balance = (lic('getLicSessionService', [$user], $user))->getAllOpenSessionsBalance($user);
// The user balance available to add as stake will be the user balance minus all the session balances
$available_user_balance = max($user->getBalance() - $all_open_sessions_balance, 0);

$remaining_stake = $session_balance_settings['max_session_stake'] - $stake;
$pretty_limit = $rg->prettyLimit($type, $stake);
$max_balance_value = $rg->prettyLimit($type, $remaining_stake);
phMsetShard('ext-game-session-balance-before-popup',$all_open_sessions_balance, $user->getId());

?>
<?php if (empty(phive()->isMobile())): ?>
    <style>
        #mbox-msg {
            width: 505px !important;
        }
    </style>
<? else: ?>
    <style>
        #mbox-msg {
            width: 100% !important;
        }
        .lic-mbox-container {
            padding: 0 0;
        }
    </style>
<? endif; ?>
<script>
    function submitDepositBalance(e) {
        e.preventDefault();
        window.extSessHandler.submitBalance('<?= $_POST['token'] ?>', <?= $max_balance_value ?>, <?= (int)$available_user_balance ?>, '<?= $game_ref ?>',  function(hasError) {
        });
    }

    function goToLobby(e) {
        e.preventDefault();
        goTo(llink('/'));
    }
</script>
<div class="session-balance-popup">
    <div class="session-balance-popup__part">
        <p>
            <span><?php et('rg.info.game-session-balance.under1k.line-1') ?></span>
        </p>
        <h2><?= t('rg.info.game-session-balance.under1k.line-2') ?> <span style="font-weight: normal;"><?= cs() . $pretty_limit ?></span></h2>
        <form class="session-balance-popup__form">
            <div class="session-balance-popup__form-field">
                <p>
                    <?php et('rg.info.game-session-balance.under1k.line-3') ?>
                </p>
                <label for="set-game-session-balance">
                    <?php et("rg.info.game-session-balance.under1k-label") ?>
                    <span>(<?= cs() ?>)</span>
                </label>
                <input
                        class="input-normal big-input full-width flat-input"
                        name="set-game-session-balance"
                        id="set-game-session-balance"
                        value="<?= min($max_balance_value, $rg->prettyLimit($type, $available_user_balance)) ?: 0; ?>"
                />
                <span class="right"><?php et2('rg.info.game-session-balance.under1k.limit-left', [cs(), $max_balance_value]); ?></span>
                <span class="hidden set-session-balance-over-limit error"><?php et('rg.info.game-session-balance.over-limit'); ?></span>
            </div>
            <div class="session-balance-popup__button-container">
                <?php btnDefaultL(t('play.now'), '', "submitDepositBalance(event)", '', 'set-session-balance-button') ?>
                <?php btnCancelXl(t('exit.game'), '', "goToLobby(event)", '', '') ?>
            </div>
        </form>

    </div>
</div>