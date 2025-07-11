<?php

use Laraphive\Domain\Payment\Services\PspConfigServiceInterface;

$pspConfigService = phiveApp(PspConfigServiceInterface::class);

?>

<div class="pp_strategy" <?php if ((!$hasMultipleWithdrawOptions || count($withdrawalEnabledPsps) <= 1) && $action_type === 'withdraw') {?>style="display: none !important;" <?php } ?>>
    <?php
    if ($pspConfigService->getPspSetting('trustly', $action_type)['active'] === true) {
    ?>
        <button class="pp_strategy_btn <?php if ($selectedPsp === 'trustly') { ?>active<?php } ?>" id="strategy_trustly"
                onclick="PayNPlay.selectStrategy('trustly')">
            <span class="checkmark-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
                    <circle class="checkmark-circle" cx="9" cy="9" r="9"/>
                    <path d="M13 6L7.78368 12L5 8.79874" stroke="white" stroke-width="2" stroke-linecap="round"
                          stroke-linejoin="round"/>
                </svg>
            </span>
            <img src="/diamondbet/images/<?= brandedCss() ?>/pay-n-play/trustly-logo-black.png" alt="Trustly Logo"/>
        </button>
        <input type="radio" id="strategy_trustly_radio" name="strategy" value="trustly" class="hidden"
        <?php if ($selectedPsp === 'trustly') { ?> checked <?php } ?>>
    <?php } ?>

    <?php
    if ($pspConfigService->getPspSetting('swish', $action_type)['active'] === true) {
    ?>
        <button class="pp_strategy_btn <?php if ($selectedPsp === 'swish') { ?>active<?php } ?>" id="strategy_swish"
                onclick="PayNPlay.selectStrategy('swish')">
            <span class="checkmark-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 18 18" fill="none">
                    <circle class="checkmark-circle" cx="9" cy="9" r="9"/>
                    <path d="M13 6L7.78368 12L5 8.79874" stroke="white" stroke-width="2" stroke-linecap="round"
                          stroke-linejoin="round"/>
                </svg>
            </span>
            <img src="/diamondbet/images/<?= brandedCss() ?>/pay-n-play/swish-logo.png" alt="Swiss Logo"/>
        </button>
        <input type="radio" id="strategy_swish_radio" name="strategy" value="swish" class="hidden"
        <?php if ($selectedPsp === 'swish') { ?> checked <?php } ?>>
    <?php } ?>
</div>

<?php if((!$hasMultipleWithdrawOptions || count($withdrawalEnabledPsps) <= 1) && $action_type === 'withdraw') { ?>
<div class="deposit-response__logo">
    <div class="deposit-response__logo-icon"><img src="/diamondbet/images/<?= brandedCss() ?>withdraw.svg">
    </div>
</div>
<?php } ?>
