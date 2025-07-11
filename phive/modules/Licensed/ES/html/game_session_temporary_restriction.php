<?php
$user = cu();

$last_session = lic('getLastSession', [$user, false], $user);

// we calculate time left before the player can play again in minutes
if (empty($last_session['closed'])) {
    $time_remaining = $last_session['limit_future_session_for'];
    lic('doFinishExternalGameSession', [$user, $last_session['id']], $user);
} else {
    $current_date = new DateTime(phive()->hisNow());
    $interval = $current_date->diff(new DateTime($last_session['ended_at']));
    $elapsed_minutes = $interval->days * 1440 + $interval->h * 60 + $interval->i;
    $time_remaining = (int)$last_session['limit_future_session_for'] - $elapsed_minutes;
}
?>

<style>
    #game_session_temporary_restriction .lic-mbox-container {
        overflow: hidden;
    }
</style>
<div class="session-balance-popup session-temporary-restriction">
    <div class="session-balance-popup__part">
        <img src="/diamondbet/images/session-balance-setup.png">
        <h3><?php et('temporarily.restrictions') ?></h3>
        <p class="session-temporary-restriction__description"><?php et('rg.info.game-session-limit.temporarily.restrictions.description') ?></p>
        <div class="session-balance-popup__details">
            <p>
                <strong> <?php et('selected.restricted.time') ?>:</strong> <?php echo $last_session['limit_future_session_for'].' '.t('min.minute') ?>
            </p>
            <p>
                <strong> <?php et('time.remaining') ?>:</strong> <?php echo $time_remaining.' '.t('min.minute'); ?>
            </p>
        </div>
        <div class="session-balance-popup__form">
            <div class="session-balance-popup__button-container">
                <?php btnDefaultL(t('ok'), '', "gotoLang('/')", '', '') ?>
            </div>
        </div>
    </div>
</div>
