<?php
require_once 'deposit_withdrawal_popup_variables.php';

$currency = lic('getForcedCurrency', []);
$hasMultipleWithdrawOptions = count($userDepositPsps) > 1;
?>

<div class="deposit_popup deposit-withdraw">
    <?php
    echo moduleHtml(
        'PayNPlay',
        'payment_provider_selector',
        false,
        null,
        [
            'action_type' => 'withdraw',
            'userDepositPsps' => $userDepositPsps,
            'withdrawalEnabledPsps' => $withdrawalEnabledPsps,
            'selectedPsp' => $selectedPsp,
            'hasMultipleWithdrawOptions' => $hasMultipleWithdrawOptions,
        ]
    );
    ?>

    <div class="deposit-withdraw__main-content margin-twenty-top">
        <div class="deposit-withdraw__amount">
            <div class="deposit-withdraw__amount-title"><?= et('choose.amount') ?></div>
            <div class="deposit-withdraw__amount-value" onclick="PayNPlay.focusInput(this)">
                <input tabindex="1" type="text" id="amount_value" value="500" />
                <div class="deposit-withdraw__currency"><?= $currency ?></div>
            </div>
            <p class="red invalid-amount" style="display:none;"><?= et('paynplay.withdraw.invalidAmountError') ?></p>
            <p class="red invalid-amount-positive" style="display:none;"><?= et('paynplay.withdraw.invalidAmountPositiveError') ?></p>
            <p class="red invalid-numeric" style="display:none;"><?= et('paynplay.withdraw.invalidNumericalValueError') ?></p>
        </div>
        <div class="deposit-withdraw__description">
            <span><?= et('paynplay.withdraw.description') ?></span>
            <?php if (in_array('swish', $userDepositPsps)) { ?>
                <p class="swish-fee" <?php if ($selectedPsp === 'trustly') { ?>style="display: none !important;" <?php } ?>>
                    Swish <?= et('fee') . ': ' . phive('CasinoCashier')->getDisplayDeduct('swish', $currentUser)?>.
                </p>
            <?php } ?>
        </div>
    </div>
    <div class="deposit-withdraw__actions">
        <button class="btn btn-l btn-default-l deposit_popup-btn"
            onclick="PayNPlay.withdraw()"><?= et('paynplay.withdraw.now') ?></button>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        var amountInput = $('#amount_value');

        amountInput.on('input', function(e) {
            var value = e.target.value;

            // Check if the input value is empty
            if(empty(value)) return false;

            // Remove non-numeric characters except the dot
            value = value.replace(/[^0-9.]/g, '');

            // Prevent more than one dot
            value = value.replace(/(\..*)\./g, '$1');

            // Split the input into whole and decimal parts
            let parts = value.split('.');

            // If there's a decimal part and it's longer than two digits, truncate it
            if (parts.length > 1) {
                parts[1] = parts[1].substring(0, 2);
                value = parts[0] + '.' + parts[1];
            }

            $(this).val(value);
        });

        amountInput.on('paste', function(e) {
            e.preventDefault();
        });
    });
</script>
