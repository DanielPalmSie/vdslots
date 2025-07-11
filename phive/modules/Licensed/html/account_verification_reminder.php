<?php
$user = cu();
$data = lic('accountVerificationData', [$user], $user);
// Calculate days, hours, mins left
$time = lic('getTimeLeftToUploadDocuments', [$user], $user);
// calculate radians to display % of time left with the circles
$circle_length = 2 * pi() * 40;
$timers = [
    'days' => round(($time['days'] / $data['days_left']) * $circle_length),
    'hours' => round(($time['hours'] / 24) * $circle_length),
    'minutes' => round(($time['minutes'] / 60) * $circle_length),
];
$circle_length = round($circle_length);
$animate = true;

loadCss('/diamondbet/css/verification-reminder.css');
if (phive()->isMobile()) {
    loadCss('/diamondbet/css/mobile-verification-reminder.css');
}
?>
<?php if (empty(phive()->isMobile())): ?>
    <style>
        #mbox-msg {
            min-width: 775px !important;
        }
    </style>
<? endif; ?>
    <div class="verification-reminder__container">
        <div class="verification-reminder__body">

            <div class="verification-reminder__part verification-reminder__timers">
                <? foreach ($timers as $interval => $radians): ?>
                    <div class="verification-reminder-timer">
                        <div class="verification-reminder-timer__circle">
                            <svg class="verification-reminder-timer__bag-circle" height="100" width="100">
                                <circle cx="50" cy="50" r="40" stroke="#ff4e4e" stroke-width="2" fill="none">
                                </circle>
                                <text x="70%" y="40%" text-anchor="middle" fill="#eb191a" dy=".3em">
                                    <tspan x="50%" dy=".6em"><?= $time[$interval] < 10 ? '0' . $time[$interval] : $time[$interval] ?></tspan>
                                </text>
                            </svg>
                            <svg class="verification-reminder-timer__over-circle" height="100" width="100">
                                <?php if ($animate): ?>
                                <circle cx="50" cy="50" r="40" stroke="#eb191a" stroke-width="4" fill="none">
                                    <animate attributeType="CSS" attributeName="stroke-dasharray" from="<?= 0 ?>,<?= $circle_length ?>"
                                             to="<?= $circle_length - $radians ?>,<?= $circle_length ?>" dur="2s" fill="freeze" repeatCount="1"/>
                                    <?php else: ?>
                                    <circle cx="50" cy="50" r="40" stroke="#eb191a" stroke-width="4" fill="none"
                                            stroke-dasharray="<?= $radians ?>,<?= $circle_length ?>">
                                        <?php endif; ?>
                                    </circle>
                            </svg>
                        </div>
                        <span><?= t($interval) ?></span>
                    </div>
                <? endforeach; ?>
            </div>
            <div class="verification-reminder__part verification-reminder__text">
                <h3><?= t('verify.identification.title') ?></h3>
                <?php foreach ($data['paragraphs'] as $paragraph): ?>
                    <p><?= t($paragraph) ?></p>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="verification-reminder__buttons">
            <?php btnDefaultL(t('my.documents'), '', "goTo('" . llink($user->accUrl('documents')) . "')", '', 'margin-five-top') ?>
            <?php btnDefaultL(t('later'), '', "$.multibox('close', 'mbox-msg')", '', 'verification-reminder__later-button margin-five-top') ?>
        </div>
    </div>
