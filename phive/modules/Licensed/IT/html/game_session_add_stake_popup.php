<?php
$user = cuPl();
$rg = rgLimits();
$type = 'deposit';

$session_id = phMgetShard('ext-game-session-id' . $_POST['token'], $user->getId());
$session_entry = phive('SQL')->sh($user)->loadAssoc(null, 'ext_game_participations', ['id' => $session_id]);
$stake = $session_entry['stake'];

$session_balance_settings = lic('getExtGameSessionStakes', [$user], $user); // The 1000 max session stake
$all_open_sessions_balance = (lic('getLicSessionService', [$user], $user))->getAllOpenSessionsBalance($user);

// The user balance available to add as stake will be the user balance minus all the session balances
$available_user_balance = max($user->getBalance() - $all_open_sessions_balance, 0);
$stake = $session_entry['stake']; // the amount already staked on the session

$max_balance_value = phive()->twoDec($session_balance_settings['max_session_stake'] - $stake); // 1000 max - already staked
$max_global_value = phive()->twoDec($session_balance_settings['max_session_stake']);

$display_balance_value = min($max_balance_value, $rg->prettyLimit($type, $available_user_balance)) ?: 0;

$game_ref = curPlaying($user->getId());

phMsetShard('ext-game-session-balance-before-popup',$all_open_sessions_balance, $user->getId());
?>
<script>
    function submitDepositBalance(e) {
        e.preventDefault();
        $('.set-session-balance-button').attr('disabled', true);

        window.extSessHandler.submitBalance(
            '<?= $_POST['token'] ?>',
            <?= $max_balance_value ?>,
            <?= (int)$available_user_balance ?>,
            '<?= $game_ref ?>',
            function() {
                $('.set-session-balance-button').attr('disabled', false);
            }
        );
    }

    function closePopup() {
        if(isMobile()) {
            // retrigger the functionality to close popup since below does not work.
            licFuncs.handleAddFunds();
        } else {
            $.multibox('close', 'mbox-msg');
        }
    }
</script>
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
<div class="session-balance-popup session-balance-popup-it-funds">
    <div class="session-balance-popup__part">
        <img src="/diamondbet/images/session-balance-setup.png">
        <h3><?php et('rg.info.game-session-balance.add.title') ?></h3>
        <p>
            <span><?php et('rg.info.game-session-balance.add.description') ?></span>
        </p>
    </div>
    <div class="session-balance-popup__part">
        <form class="session-balance-popup__form session-balance-popup__form--gray">
            <div class="session-balance-popup__form-field">
                <label for="set-game-session-balance">
                    <?php et('rg.info.game-session-balance.add.title') ?>
                    <span>(<?= cs() ?>)</span>
                </label>
                <input placeholder="<?php et('rg.info.limits.set.choose') ?>"
                       class="input-normal big-input full-width flat-input" type="number" step="any"
                       name="set-game-session-balance"
                       id="set-game-session-balance"
                       value="<?= $display_balance_value  ?>"
                />
                <span><?php et2('rg.info.game-session-balance.add.max-limit', [cs(), $display_balance_value, cs(), $max_global_value]); ?></span>
                <span class="hidden set-session-balance-over-limit error"><?php et('rg.info.game-session-balance.over-limit'); ?></span>
            </div>
            <div class="session-balance-popup__button-container">
                <?php btnDefaultL(t('rg.info.game-session-balance.add.title'), '', "submitDepositBalance(event)", '', 'set-session-balance-button') ?>
            </div>
        </form>
    </div>
</div>
