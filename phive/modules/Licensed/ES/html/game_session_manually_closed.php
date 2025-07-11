<?php
$show_time_reached = ($_POST['show_time_reached'] === 'true'); // if false it will show spend limit reached.

$user = cuPl();

// if we launch a new session from a new tab, then the session is ended before we can get the data. We will use the return of lic() in this case
$participation = null;
$_POST['session'] === '' ? $participation = lic('getOpenParticipation', [$user]) : $participation = $_POST['session'];

$game_session = lic('getGameSessionByParticipation', [$participation, false], $user);

$last_session = lic('getLastSession', [$user, false], $user);

// check if last session was closed so that we show RG popup
if (empty($last_session['closed'])) {
    $time_remaining = $last_session['limit_future_session_for'];
    lic('doFinishExternalGameSession', [$user, $last_session['id']], $user);
}

$time_left = (int) $_POST['time_left'];
$time_limit_set = $participation['time_limit'];
$time_played = $time_limit_set - $time_left;

$redirectFunc = $_POST['redirect_func'] ?? "gotoLang('/')";

$amount_wagered = rnfCents($game_session['bet_amount']);
$amount_won = rnfCents($game_session['win_amount']);
$net_result = rnfCents($game_session['win_amount'] - $game_session['bet_amount']);

?>

<style>
    #won_amount_label {
        font-weight: normal;
    }
</style>
<div class="session-balance-popup">
    <div class="session-balance-popup__part">
        <img src="/diamondbet/images/session-balance-setup.png">
        <h3><?php et('game.session.ended') ?></h3>
        <div class="session-balance-popup__details">
            <p>
                <strong> <?php et('time.played') ?></strong>  <?php echo $time_played.' '.t('min.minute') ?>
            </p>
            <p>
                <strong> <?php et('amount.wagered') ?></strong> <?php echo cs().' '. $amount_wagered ?>
            </p>
            <p>
                <strong> <?php et('amount.won') ?></strong> <?php echo cs().' '. $amount_won; ?>
            </p>
            <p>
                <strong> <?php et('net.result') ?></strong> <?php echo cs().' '. $net_result ?>
            </p>
        </div>
        <div class="session-balance-popup__form">
            <div class="session-balance-popup__button-container">
                <?php btnDefaultL(t('ok'), '', $redirectFunc, '', '') ?>
            </div>
        </div>
    </div>
</div>
