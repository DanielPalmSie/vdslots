<?php
?>

<div class="deposit-response withdrawal-response">
    <div class="withdrawal-response__main-content">
        <div class="deposit-response__logo">
            <div class="deposit-response__logo-icon"><img src="/diamondbet/images/<?= brandedCss() ?>withdrawal-success.svg"></div>
        </div>
        <div class="deposit-response__content">
            <div class="deposit-response__title">
                <?= et('paynplay.withdraw.success.message') ?>
            </div>
            <div class="deposit-response__description">
                <?= et('paynplay.withdraw.success.description') ?>
            </div>
        </div>
    </div>
    <div class="deposit-response__actions">
        <button class="btn btn-l btn-default-l success-btn" onclick="PayNPlay.onWithdrawalSuccess()"><?= et('paynplay.withdraw.success.btn') ?></button>
    </div>
</div>
