<?php
$user = cuPl();
$rg = rgLimits();
$mobile = phive()->isMobile() ? 'mobile' : '';
$type = 'deposit';
$session_balance_settings = lic('getExtGameSessionStakes', [$user], $user);
$default_stake = $rg->prettyLimit($type, $session_balance_settings['default_session_stake']);
$max_stake_value = $rg->prettyLimit($type, $session_balance_settings['max_session_stake']);
$game_ref = $_POST['game_ref'] ?? phive('MicroGames')->getGameRefByIdWithAutoDeviceDetect($_POST['game_id']);
$balance = $user->getBalance();

$all_open_sessions_balance = (lic('getLicSessionService', [$user], $user))->getAllOpenSessionsBalance($user);
phMsetShard('ext-game-session-balance-before-popup',$all_open_sessions_balance, $user->getId());
?>
<?php if (empty(phive()->isMobile())): ?>
    <style>
        #mbox-msg {
            width: 775px !important;
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
    function submitDepositBalance(e) {
        e.preventDefault();
        window.extSessHandler = window.extSessHandler || lic('extSessHandler', []);
        var sessionIndex = <?= $_POST['sessionIndex']  ?? -1 ?>;
        window.extSessHandler.submitBalance(0, <?= (int)$max_stake_value ?>, <?= (int)$balance?>, '<?= $game_ref ?>', function () {
            if (sessionIndex >= 0) {
                window.extSessHandler.loadGame(<?= $_POST['sessionIndex'] ?>);
            }
        });
    }

    function goToLobby(e) {
        e.preventDefault();
        goTo(llink('/'));
    }
</script>

<div class="session-balance-popup session-balance-popup-it">
    <div class="session-balance-popup__part">
        <img src="/diamondbet/images/session-balance-setup.png">
        <h3><?php et('rg.info.game-session-balance.set.title') ?></h3>
        <p>
            <span><?php et('rg.info.game-session-balance.description') ?></span>
        </p>
    </div>
    <div class="session-balance-popup__part">
        <form class="session-balance-popup__form session-balance-popup__form--gray">
            <div class="session-balance-popup__form-field">
                <label for="set-game-session-balance">
                    <?php et("rg.info.game-session-balance.set-label") ?>
                    <span>(<?= cs() ?>)</span>
                </label>
                <input placeholder="<?php et('rg.info.limits.set.choose') ?>"
                       class="input-normal big-input full-width flat-input" type="number" step="any"
                       name="set-game-session-balance"
                       id="set-game-session-balance"
                       value="<?= min($default_stake, $rg->prettyLimit($type, $balance)) ?>"
                />
                <span><?php et2('rg.info.game-session-balance.set.max-limit', [cs(), $max_stake_value]); ?></span>
                <span class="hidden set-session-balance-over-limit error"><?php et('rg.info.game-session-balance.over-limit'); ?></span>
            </div>
            <div class="session-balance-popup__button-container">
                <?php if (min($balance, $max_stake_value) == 0): ?>
                    <?php btnCancelXl(t('exit.game'), '', "goToLobby(event)", '', '') ?>
                <?php else: ?>
                    <?php btnDefaultL(t('play.now'), '', "submitDepositBalance(event)", '', 'set-session-balance-button') ?>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

