<?php
// TODO check if common logic across all popup can be centralized /Paolo
$user = cuPl();
$box_id = $_POST['box_id'];

$participation = lic('getOpenParticipation', [$user], $user);

$time_left = (int) $_POST['time_left'];

$percentage_threshold = 10;
$time_warning = $_POST['time_warning'] === 'true';
$spend_warning = round($participation['balance'] / $participation['stake']) * 100 <= $percentage_threshold;

$time_limit_set = $participation['time_limit'];
$spend_limit_set = rnfCents($participation['stake']);

$time_elapsed = $time_limit_set - $time_left;
$spend_spent = rnfCents($participation['stake'] - $participation['balance']);
?>
<style>
    .color_highlight {
        color: red;
    }
</style>

<div class="session-balance-popup">
    <div class="session-balance-popup__part">
        <img src="/diamondbet/images/session-balance-setup.png">
        <h3><?php et('limit.reminder') ?></h3>
        <p><?php et('about.to.reach.limits') ?></p>
        <div class="session-balance-popup__details">
            <p>
                <strong> <?php et('time.elapsed') ?></strong> <span class="<?php echo ($time_warning)? 'color_highlight' : '' ; ?>"> <?php echo $time_elapsed.' '.t('min.minute') ?> </span>
            </p>
            <p>
                <strong> <?php et('time.limit.set') ?></strong> <span class="<?php echo ($time_warning)? 'color_highlight' : '' ; ?>"> <?php echo $time_limit_set.' '.t('min.minute') ?> </span>
            </p>
            <p>
                <strong><?php et('spend.spent') ?></strong> <span class="<?php echo ($spend_warning)? 'color_highlight' : '' ; ?>"> <?php echo cs().' '.$spend_spent ?> </span>
            </p>
            <p>
                <strong> <?php et('spend.limit.set') ?></strong> <span class="<?php echo ($spend_warning)? 'color_highlight' : '' ; ?>"> <?php echo cs().' '.$spend_limit_set ?> </span>
            </p>
        </div>
        <div class="session-balance-popup__form">
            <div class="session-balance-popup__button-container">
                <?php btnActionXL(t('continue.playing'), '', "extSessHandler.closePopup('$box_id')", '', 'btn-action-bg-flat btn-action-round') ?>
            </div>
            <div class="session-balance-popup__button-container">
                <?php btnDefaultXL(t('exit.game'), '', "gotoLang('/')", '', 'btn-action-round') ?>
            </div>
        </div>
    </div>
</div>
