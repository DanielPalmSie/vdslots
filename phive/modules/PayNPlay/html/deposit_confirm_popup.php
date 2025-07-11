<?php
require_once '../../../phive.php';

$currency = lic('getForcedCurrency', [], null, null, 'SE');

$limitMax = $_POST['limit'];
$depositAmount = $_POST['deposit_amount'];
$amountToDisplay = $limitMax / 100;

$selectedAmount = [
    'value' => $amountToDisplay,
    'currency' => $currency,
];

$c = phive('Cashier');
$psp = 'swish';
$minAmount = $c->getLowerLimit($psp, 'in');
?>

<div class="confirm_popup">
    <div class="confirm_popup__body">
        <div class="confirm_popup__logo">
            <img src="/diamondbet/images/<?= brandedCss() ?>/pay-n-play/swish-logo-big.png" alt="Swish Logo"/>
        </div>
        <div class="confirm_popup__message"><?php echo tAssoc('paynplay.deposit.confirmation', ['currency'=>$currency, 'attempted_amount'=>$depositAmount, 'maximum_amount'=>$amountToDisplay]) ?></div>

    <?= moduleHtml(
    'PayNPlay',
    'deposit_amount_input',
    false,
    null,
    [
        'selectedAmount' => $selectedAmount,
        'minAmount' => $minAmount, 'maxAmount' => $limitMax,
        'showTitle' => false,
        'showChangeButton' => true,
    ]
) ?>
    </div>
    <div class="confirm_popup__actions">
        <button class="btn btn-l confirm_popup-btn confirm_popup-btn--deposit-btn" onclick="PayNPlay.depositConfirm()"><?= t('paynplay.deposit.btn') ?></button>
        <button class="btn btn-l deposit_popup-btn deposit_popup-btn--cancel primary-color" onclick="PayNPlay.closeConfirmationPopup()"><?= t('cancel') ?></button>
    </div>
</div>
