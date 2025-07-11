<?php
$user = cu();
$balance_withdrawable = (int)lic('getBalanceAvailableForWithdrawal', [$user], $user);
$balance_non_withdrawable = (int)lic('getBalanceNonWithdrawable', [$user], $user);
?>
<div class="session-balance-popup">
    <div class="session-balance-popup__part withdraw-error-popup">

        <img src="/diamondbet/images/session-balance-setup.png">

        <h3><?php et('withdraw.error.popup.title') ?></h3>

        <?php et2('withdraw.error.popup.description.html') ?>

        <div class="withdraw-error-popup-resume">
            <ul>
                <li>
                    <div><?php et('withdraw.error.popup.withdrawable') ?></div>
                    <div><?php echo cs() . ' ' . $balance_withdrawable / 100; ?></div>
                </li>

                <li>
                    <div><?php et('withdraw.error.popup.non.withdrawable') ?></div>
                    <div><?php echo cs() . ' ' . $balance_non_withdrawable / 100; ?></div>
                </li>
            </ul>
        </div>

        <?php btnDefaultXl(t('ok'), '', "closePopup('mbox-msg', true, false)", '', 'withdraw-error-popup-button') ?>

    </div>
</div>
