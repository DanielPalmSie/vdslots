<?php
$user = cu();
$data = lic('accountVerificationData', [$user], $user);

loadCss('/diamondbet/css/verification-reminder.css');
if (phive()->isMobile()) {
    loadCss('/diamondbet/css/mobile-verification-reminder.css');
}
?>
<?php if (empty(phive()->isMobile())): ?>
    <style>
        #mbox-msg {
            width: 500px !important;
        }
    </style>
<? endif; ?>
<div class="verification-reminder__container verification-reminder__container-overtime">
    <p>
    <?php foreach ($data['paragraphs'] as $paragraph): ?>
        <?= t($paragraph) ?>
        <br>
    <?php endforeach; ?>
    </p>
    <div class="verification-reminder__buttons">
        <?php btnDefaultL(t('verify'), '', "goTo('" . llink($user->accUrl('documents')) . "')", '', 'margin-five-top') ?>
    </div>
</div>
