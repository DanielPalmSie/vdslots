<?php

/**
 * @deprecated
 *
 * new methods were added to modules/Licensed/IT/IT.php
 *
 * @see \IT::canFormatMobileBalanceTable()
 * @see \IT::getMobileBalanceTable()
 * @see \IT::formatMobileBalanceTableToHtml()
 * @see \IT::formatMobileBalanceTableToJson()
 */
$user = cu();
$mobile_balance_amount = [
    'amount' => lic('getBalanceAvailableForWithdrawal', [$user], $user),
    'label_alias' => 'casino.tooltip.withdrawable',
    'id' => 'mobile-left-menu-withdrawable',
];
?>

<tr>
    <td>
        <span class="medium-bold"><?php et($mobile_balance_amount['label_alias']) ?></span>
    </td>
    <td class="right">
                <span class="medium-bold header-3">
                    <?= cs() ?>
                    <span id="<?= $mobile_balance_amount['id'] ?>"><?php echo $mobile_balance_amount['amount'] / 100 ?></span>
                </span>
    </td>
</tr>