<?php
$box_id = $_POST['boxid'];
$deposit_amount = $_POST['depositAmount'];
$currency = $_POST['currency'];
$till_date = $_POST['tillDate'];
$user = cu();
$userId = $user->getId();
$description = tAssoc('customer_net_deposit.box.description.html', [
                    'depositAmount' => $deposit_amount,
                    'currency' => $currency,
                    'tillDate' => $till_date
                ]);
$imageName = $_POST['imageName'] ?? 'warning.png';
$devicePath = Phive()->isMobile() ? '/mobile' : '';
?>

<div class="rg-info-popup">
    <div class="rg-info-popup__main-content">
        <div class="rg-info-popup__logo">
            <div class="rg-info-popup__logo-wrapper">
                <img
                        class="rg-info-popup__logo-img"
                        alt="error"
                        src="/diamondbet/images/<?= brandedCss() ?><?= $imageName ?>"
                >
            </div>
        </div>
        <div class="rg-info-popup__description">
            <?= $description ?>
        </div>
    </div>
    <div class="rg-info-popup__actions">
        <div class="rg-info-popup__actions-row">
            <button
                class="rg-info-popup__btn rg-info-popup__btn--continue"
                onclick="resubmitDepositRequest()"
            >
                <?= t('yes') ?>
            </button>
            <button
                class="rg-info-popup__btn rg-info-popup__btn--break"
                onclick="mboxClose('<?= $box_id ?>')"
            >
                <?= t('no') ?>
            </button>
        </div>
    </div>
</div>

<script>
    function resubmitDepositRequest() {
        if (ndlExceedPopupResolver) {
            ndlExceedPopupResolver(true);
            ndlExceedPopupResolver = null; // Reset resolver after use
        }
    }
</script>