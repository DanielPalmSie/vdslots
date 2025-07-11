<?php
require_once __DIR__ . '/../../../../phive/phive.php';

$user = cu();

$minutes = lic('getGamingPauseTime', [true], $user);
$start = (new DateTime())->getTimestamp();
$end = lic('gamePlayPaused', [$user, false, true], $user);
$timers = ['minutes', 'seconds'];
$message = tAssoc('reality-check.game-play-paused.message', [
    'rc_minutes_break' => $minutes
]);
$on_timer_ended = "mboxClose('mbox-msg');";
$buttons = [
    [
        'title' => t('mp.back.to.lobby'),
        'action' => "gotoLang('/')"
    ]
];

$mobile = phive()->isMobile() ? 'mobile' : '';
$color = '#931919';

loadCss('/diamondbet/css/verification-reminder.css');
if (phive()->isMobile()) {
    loadCss('/diamondbet/css/mobile-verification-reminder.css');
}
?>

<div id="enhanced-timer" class="verification-reminder__container <?= $mobile ?>">
    <div class="verification-reminder__body">
        <div class="verification-reminder__part verification-reminder__timers dark-red">
            <?php foreach ($timers as $time): ?>
                <div class="verification-reminder-timer" data-type="<?= $time ?>">
                    <div class="verification-reminder-timer__circle">
                        <svg class="verification-reminder-timer__bag-circle" height="100" width="100">
                            <circle cx="50" cy="50" r="40" stroke-width="2" fill="none"></circle>
                            <text x="70%" y="40%" text-anchor="middle" dy=".3em">
                                <tspan x="50%" dy=".6em">13</tspan>
                            </text>
                        </svg>
                        <svg class="verification-reminder-timer__over-circle" height="100" width="100">
                            <circle cx="50" cy="50" r="40" stroke-width="4" fill="none" stroke-dasharray="13,13"></circle>
                        </svg>
                    </div>
                    <span class="verification-reminder-title"><?= t($time) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="verification-reminder__part verification-reminder__text">
            <p><?= $message ?></p>
        </div>
    </div>
    <div class="verification-reminder__buttons">
        <?php foreach ($buttons as $button): ?>
        <?php btnDefaultL($button['title'], '', $button['action'], '', 'margin-five-top') ?>
        <?php endforeach; ?>
    </div>
</div>
<script>
    $(document).ready(function() {
        var circle_length = 2 * Math.PI * 40;
        var start = new Date(<?= $start ?> * 1000);
        var end = new Date(<?= $end ?> * 1000);
        if (start > end) {
            return null;
        }

        var updateValue = function (type, value, divisor) {
            var item = $("#enhanced-timer .verification-reminder-timer[data-type='"+type+"']");
            item.find(".verification-reminder-timer__bag-circle text tspan").text(value < 10 ? '0' + value : value)
            var stroke = ((value / divisor) * circle_length).toFixed(2)
            stroke += ',' + circle_length.toFixed(2)
            item.find(".verification-reminder-timer__over-circle circle").attr('stroke-dasharray', stroke)
        }

        var seconds = (end - start) / 1000

        var startTimer = function () {
            seconds--;
            if (seconds <= 0) {
                clearInterval(timer);
                reality_checks_js.duration = 0
                reality_checks_js.rc_createDialog()
                <?= $on_timer_ended ?>;
                return
            }
            updateValue('minutes', parseInt((seconds / 60).toString()), <?= $minutes ?>)
            updateValue('seconds', parseInt((seconds % 60).toString()), 60)
        }

        var timer = setInterval(startTimer, 1000)
        startTimer();
    })
</script>
