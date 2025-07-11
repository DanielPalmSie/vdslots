<?php
$popup = $_POST['popup'];

$title = null;
$description = null;
$imageName = null;
$className = null;

if($_POST['popup'] === 'Registration without deposit is not allowed') {
    $popup = 'deposit-to-play';
}

switch ($popup) {
    case 'blocked':
        $title = t('paynplay.error.blocked.title');
        $description = t('paynplay.error.blocked.description');
        $imageName = 'blocked-account.png';
        $className = 'blocked-account';
        break;
    case 'self-excluded':
        $title = t('paynplay.error.self-excluded.title');
        $description = t('paynplay.error.self-excluded.description');
        $imageName = 'self-excluded-account.png';
        $className = 'self-excluded';
        break;
    case 'login-limit-reached':
        $title = t('paynplay.error.login-limit-reached.title');
        $description = t('paynplay.error.login-limit-reached.description');
        $imageName = 'login-limit-reached.png';
        break;
    case 'deposit-reached':
        $title = t('paynplay.error.deposit-reached.title');
        $description = t('paynplay.error.deposit-reached.description');
        $imageName = 'deposit-reached.png';
        break;
    case 'monthly-net-deposit-limit-reached':
        $title = t('paynplay.error.monthly-net-deposit-limit-reached.title');
        $description = t('paynplay.error.monthly-net-deposit-limit-reached.description');
        $imageName = 'monthly-net-deposit-limit-reached.png';
        break;
    case 'deposit-failure':
        $title = t('paynplay.error.deposit-reached.title');
        $description = t('paynplay.error.deposit-reached.description');
        $imageName = 'deposit-reached.png';
    case 'deposit-success':
        $title = t('paynplay.error.deposit-reached.title');
        $description = t('paynplay.error.deposit-reached.description');
        $imageName = 'deposit-reached.png';
        break;
    case 'deposit-block':
        $title = t('paynplay.error.deposit-block.title');
        $description = t2(
            'paynplay.error.deposit-block.description',
            ['supportemail' => t('actual.support.email')]
        );
        $imageName = 'deposit-reached.png';
        break;
    case 'login-success':
        $title = t('paynplay.error.login-success.sub-title');
        $description = t('paynplay.error.login-success.description');
        $imageName = 'login-success.svg';
        break;
    case 'deposit-to-play':
        $title = t('paynplay.deposit.to.play.message');
        $description = t('paynplay.deposit.play.description');
        $imageName = 'deposit-to-play.png';
        break;
    case 'casino-net-deposit-threshold-reached':
        $title = t('net.deposit.limit.info.month.header');
        $description = t('net.deposit.limit.info.month.body.html');
        $imageName = 'monthly-net-deposit-limit-reached.png';
        break;
    case 'customer-net-deposit-reached':
        $title = t('customer.net.deposit.limit.info.month.header');
        $description = t('customer.net.deposit.limit.info.month.body.html');
        $imageName = 'monthly-net-deposit-limit-reached.png';
        break;
    default:
        $title = t('paynplay.deposit.failure.message');
        $description = t('paynplay.deposit.unknown.failure.description');
        $imageName = 'deposit-reached.png';
        break;
}

/*
 * This is to handle unknown errors like '{"ip":"register.err.ip.toomany"}'
 * as we should not pass $popup to the onclick onErrorPopupClosed function.
 */
if (is_string($popup)) {
    $decoded = json_decode($popup);
    if (is_object($decoded) || is_array($decoded)) {
        $popup = '';
    }
} else {
    $popup = '';
}

?>

<div class="pnp-error-popup <?= $className ?> ">
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
        <div class="pnp-error-popup__title">
            <?= $title ?>
        </div>
        <div class="pnp-error-popup__description">
            <?= $description ?>
        </div>
    </div>
    <div class="pnp-error-popup__actions">
        <?php if ($popup === 'monthly-net-deposit-limit-reached'):?>
            <button
                class="pnp-error-popup__btn pnp-error-popup__btn--request-limit-increase"
                onclick="PayNPlay.onErrorPopupLimitIncreaseRequested()"
            >
                <?= t('paynplay.error.monthly-net-deposit-limit-reached.increase') ?>
            </button>
        <?php elseif ($popup === 'deposit-to-play'): ?>
            <button
                class="btn btn-l deposit_popup-btn deposit_to_play_btn deposit_popup-btn--deposit-btn"
                onclick="PayNPlay.closeDepositToPlayPopup()"
            >
                <?= t('paynplay.deposit.btn') ?>
            </button>
        <?php else: ?>
            <button class="btn-default-l pnp-error-popup__btn pnp-error-popup__btn--proceed" onclick="PayNPlay.onErrorPopupClosed('<?= $popup ?>')">
                <?= t('paynplay.error-popup.proceed') ?>
            </button>
        <?php endif ?>
    </div>
</div>

<script>
    PayNPlay.closePNPPopup();
</script>
