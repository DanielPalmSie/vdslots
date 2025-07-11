<div class="deposit-response">
    <div class="deposit-response__logo">
        <div class="deposit-response__logo-icon"><img src="/diamondbet/images/<?= brandedCss() ?>deposit_failure.svg"></div>
    </div>
    <div class="deposit-response__content">
        <div class="deposit-response__title">
            <?= et('paynplay.deposit.failure.message') ?>
        </div>
        <div class="deposit-response__description">
            <?= et('paynplay.deposit.failure.description') ?>
        </div>
    </div>
    <div class="deposit-response__actions">
        <button
            class="btn btn-l btn-default-l success-btn"
            onclick="jsReloadWithParams()"
        >
            <?= et('paynplay.deposit.failure.btn') ?>
        </button>
    </div>
</div>
