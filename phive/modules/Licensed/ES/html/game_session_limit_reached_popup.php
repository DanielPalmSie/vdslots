<?php
$show_time_reached = ($_POST['show_time_reached'] === 'true'); // if false it will show spend limit reached.

$user = cuPl();

$participation = lic('getOpenParticipation', [$user], $user);
$game_session = lic('getGameSessionByParticipation', [$participation], $user);

$time_limit_set = $participation['time_limit'];
$spend_limit_set = rnfCents($participation['stake']);

$amount_wagered = rnfCents($game_session['bet_amount']);
$amount_won = rnfCents($game_session['win_amount']);
$net_result = rnfCents($game_session['win_amount'] - $game_session['bet_amount']);
$spend_spent = rnfCents($participation['stake'] - $participation['balance']);

?>

<style>
    #won_amount_label {
        font-weight: normal;
    }
</style>
<div class="session-balance-popup">
    <div class="session-balance-popup__part">
        <img src="/diamondbet/images/session-balance-setup.png">
        <h3><?php et('game.session.limit.reached') ?></h3>
        <p><?php $show_time_reached ? et('rg.info.game-session-limit.reached.description-time') : et('rg.info.game-session-limit.reached.description-spend'); ?></p>
        <div class="session-balance-popup__details session-balance-popup__session-reached">
            <?php if ($show_time_reached) : ?>
                <p>
                    <strong> <?php et('time.elapsed') ?></strong>  <?php echo $time_limit_set.' '.t('min.minute') ?>
                </p>
                <p>
                    <strong> <?php et('time.limit.set') ?></strong> <?php echo $time_limit_set.' '.t('min.minute') ?>
                </p>
            <?php else : ?>
                <p>
                    <strong><?php et('spend.spent') ?></strong> <?php echo cs().' '.$spend_spent ?>
                </p>
                <p>
                    <strong> <?php et('spend.limit.set') ?></strong> <?php echo cs().' '.$spend_limit_set ?>
                </p>
            <?php endif; ?>
            <p>
                <strong> <?php et('amount.wagered') ?></strong> <?php echo cs().' '.$amount_wagered ?>
            </p>
            <p>
                <strong> <?php et('amount.won') ?></strong> <?php echo cs().' '. $amount_won; ?>
            </p>
            <p>
                <strong> <?php et('net.result') ?></strong> <?php echo cs().' '.$net_result ?>
            </p>
        </div>
        <div class="session-balance-popup__form">
            <div class="session-balance-popup__button-container">
                <?php btnDefaultL(t('exit.game'), '', "gotoLang('/')", '', '') ?>
            </div>
        </div>
    </div>
</div>
