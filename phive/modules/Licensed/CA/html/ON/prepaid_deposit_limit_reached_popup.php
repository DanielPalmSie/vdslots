<?php

$total_deposit = $_POST['total_prepaid_deposits'];
$max_allowed_deposit = licSetting('prepaid_deposits_limit')['allowed_amounts'];
$last_days = licSetting('prepaid_deposits_limit')['last_days'];
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$currency_sign = cs();

?>

<div class="prepaid_deposit_limit css-flex-container-valign-center-halign-space-between css-flex-column css-flex-stretch ">

    <div class="prepaid_deposit_limit__main-content">
        <div class="prepaid_deposit_limit__logo">
            <img src="/diamondbet/images/<?= brandedCss() ?>king-warning.svg" />
        </div>

        <div class="prepaid_deposit_limit__header">
            <div class="prepaid_deposit_limit__title">
                <?php et($title) ?>
            </div>

            <div class="prepaid_deposit_limit__description">
                <?php et2($description, ['max_allowed_deposit' => $currency_sign.''.rnfCents($max_allowed_deposit), 'days' => $last_days]) ?>
            </div>
        </div>

        <div class="prepaid_deposit_limit__content css-flex-container css-flex-stretch css-flex-column">
            <div class="w-100-pc css-flex-container css-flex-container-valign-center-halign-space-between">
                <div>
                    <?php et2('prepaid.deposit.limit.total.deposit', ['days' => $last_days]) ?>
                </div>
                <div class="currency"><?php echo $currency_sign.''.rnfCents($total_deposit); ?></div>
            </div>
            <div class="w-100-pc css-flex-container css-flex-container-valign-center-halign-space-between">
                <div><?php et('prepaid.deposit.limit.remaining.deposit') ?></div>
                <div class="currency"><?php echo $currency_sign.''.rnfCents($max_allowed_deposit - $total_deposit); ?></div>
            </div>
            <div class="w-100-pc css-flex-container css-flex-container-valign-center-halign-space-between">
                <div><?php et('prepaid.deposit.limit.max.allowed.deposit') ?></div>
                <div class="currency"><?php echo $currency_sign.''.rnfCents($max_allowed_deposit); ?></div>
            </div>
        </div>

    </div>

    <div class="prepaid_deposit_limit__action css-flex-container css-flex-container-valign-center">
        <button class="accept-button" onclick="licFuncs.prepaidPopupHandler().submitHandler()"><?php et('prepaid.deposit.limit.btn') ?></button>
    </div>

</div>
