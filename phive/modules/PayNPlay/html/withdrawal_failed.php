<?php

$popup = $_POST['popup'];
$responseMessage = $_POST['resultMessage'];

$title = null;
$description = null;
$imageName = null;

switch ($popup) {
    case 'withdrawal-failed':
        $title = t('paynplay.withdraw.failure.message');
        $description = $responseMessage;
        $imageName = 'withdrawal-failure.svg';
        break;
    case 'withdrawal-block':
        $title = t('paynplay.withdraw.block.title');
        $description = t('paynplay.withdraw.block.description');
        $imageName = 'withdrawal-failure.svg';
        break;
    default:
        throw new Exception('Unknown error');
}
?>

<div class="pnp-error-popup withdrawal-error-popup">
    <div class="pnp-error-popup__main-content">
        <div class="pnp-error-popup__logo">
            <div class="pnp-error-popup__logo-wrapper">
                <img
                    class="pnp-error-popup__logo-img"
                    alt="error"
                    src="/diamondbet/images/<?= brandedCss() ?>pay-n-play/<?= $imageName ?>"
                >
            </div>
        </div>
        <div class="pnp-error-popup__content">
            <div class="pnp-error-popup__title">
                <?= $title ?>
            </div>
            <div class="pnp-error-popup__description">
                <?= $description ?>
            </div>
        </div>
    </div>
    <div class="pnp-error-popup__actions">
        <button class="btn-default-l pnp-error-popup__btn pnp-error-popup__btn--proceed" onclick="PayNPlay.onErrorPopupClosed()">
            <?= t('paynplay.error-popup.proceed') ?>
        </button>
    </div>
</div>
