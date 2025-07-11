<?php
// Suppress undefined variable warnings for the following variables
/** @var array $selectedAmount */
/** @var int $minAmount */
/** @var int $maxAmount */
/** @var bool $showTitle */
/** @var bool $showChangeButton */
?>
<div class="deposit_popup__amount">
    <?php if ($showTitle): ?>
        <div class="deposit_popup__amount_title"><?= t('paynplay.choose.amount') ?></div>
    <?php endif; ?>
    <div class="deposit_popup__amount_value" onclick="PayNPlay.focusInput(event)">
        <input tabindex="1" type="number" class="amount_value" value="<?= $selectedAmount['value'] ?>" />
        <input type="hidden" class="amount_min" value="<?= $minAmount ?>"/>
        <input type="hidden" class="amount_max" value="<?= $maxAmount ?>"/>
        <div class="deposit_popup__currency"><?= $selectedAmount['currency'] ?></div>
        <?php if ($showChangeButton): ?>
            <a href="#" class="deposit_popup__change"
               onclick="PayNPlay.enableAmountChange(event)"><?php et('change.my.limit'); ?></a>
        <?php endif; ?>
    </div>
    <div class="deposit_popup__amount_title error hidden amount-incorrect"><?= t('paynplay.amount.incorrect') ?></div>
    <div class="deposit_popup__amount_title error hidden amount-min"><?= t('paynplay.amount.lessmin') ?></div>
    <div class="deposit_popup__amount_title error hidden amount-max"><?= t('paynplay.amount.moremax') ?></div>
</div>
