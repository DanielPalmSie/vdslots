<?php
//
// Display a <div> container with the contents of Player Balance on top right
//
$balances = $GLOBALS['balances'];
?>
<div class="top-profile-balances">
    <span style="font-style: italic;"><?= t('casino.bonus.balance') ?></span>
    <span class="fat"><?= cs(true) ?>&nbsp;<strong id="top-bonus-balance"><?= nfCents($balances['bonus_balance'] + $balances['casino_wager']) ?></strong></span>
    <span style="font-style: italic;"><?= t('casino.balance') ?></span>
    <span class="fat"><?= cs(true) ?>&nbsp;<strong id="top-balance"><?= nf2($balances['cash_balance'] / 100, false, 1, '.', '') ?></strong></span>
</div>
