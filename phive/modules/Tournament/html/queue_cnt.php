<?php
//
?>
<div class="mp-popup-header gradient-default">
    <?php et('mp.registration.headline') ?>
</div>
<div class="mp-popup-content-wrapper">
    <p>
        <?php et('thank.you.for.waiting') ?>
    </p>
    <p class="small-bold">
        <?php et('you.are.now.in.the.queue') ?>
    </p>

    <div class="mp-q-countdown-box">
        <div id="qcnt">
            <?php echo $_POST['cnt'] ?>
        </div>
        <span class="mp-q-countdown-number">
            <?php echo strtoupper(t('position.in.queue')) ?>
        </span>
    </div>
    <?php btnDefaultL('OK', '', 'mboxClose()', 170) ?>
</div>
