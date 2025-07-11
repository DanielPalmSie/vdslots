<?php
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$max_allowed_cards = licSetting('prepaid_deposits_limit')['allowed_cards'];
$last_days = licSetting('prepaid_deposits_limit')['last_days'];
?>

<div class="prepaid_method_usage_limit css-flex-container-valign-center-halign-space-between css-flex-column css-flex-stretch ">

    <div class="prepaid_method_usage_limit__main-content">
        <div class="prepaid_method_usage_limit__logo">
            <img src="/diamondbet/images/<?= brandedCss() ?>king-warning.svg" />
        </div>

        <div class="prepaid_method_usage_limit__header">
            <div class="prepaid_method_usage_limit__title">
                <?php et($title) ?>
            </div>

            <div class="prepaid_method_usage_limit__description">
                <?php et2($description, ['max_allowed_cards' => $max_allowed_cards, 'last_days' => $last_days]) ?>
            </div>
        </div>
    </div>

    <div class="prepaid_deposit_limit__action css-flex-container css-flex-container-valign-center">
        <button class="accept-button" onclick="licFuncs.prepaidPopupHandler().submitHandler()">
            <span><?php et('prepaid.method.usage.limit.reached.btn') ?></span>
        </button>
    </div>

</div>
