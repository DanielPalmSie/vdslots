<?php

use Videoslots\User\ThirdPartyVerificationFields\Factory\TopPartFactory;

$mbox = new MboxCommon();
$u_obj = $mbox->getUserOrDie();
$rgl = rgLimits()->getLicWithdrawLimit($u_obj);
list($progress, $amount) = rgLimits()->getLicWithdrawLimitProgress($u_obj, $rgl);

function globalDepositLimitInfoTable($u_obj, $rgl, $progress){
?>
    <table class="global-deposit-limit__table green">
        <tr>
            <td class="label">
                <?php et('withdraw.limit.left') ?>:
            </td>
            <td class="value">
                <?php if (($rgl['limit'] - $progress) < 0): ?>
                    <?php echo rnfCents(0).' '.ciso() ?>
                <?php else: ?>
                    <?php echo rnfCents($rgl['limit'] - $progress).' '.ciso() ?>
                <?php endif; ?>

            </td>
        </tr>
        <tr>
            <td class="label">
                <?php et('withdraw.limit.max') ?>:
            </td>
            <td class="value">
                <?php echo rnfCents($rgl['limit']).' '.ciso() ?>
            </td>
        </tr>
        <tr>
            <td class="label">
                <?php et('withdraw.limit.until.label') ?>:
            </td>
            <td class="value">
                <?php et('withdraw.limit.until.text') ?>
            </td>
        </tr>
    </table>
<?php
}
?>

<div class="lic-mbox-wrapper global-withdraw-limit">
    <?php
        $top_part_data = (new TopPartFactory())->create('mbox-msg', "withdraw.limit.title", false, false);
        $mbox->topPart($top_part_data)
    ?>
    <div class="lic-mbox-container limits-deposit-set <?= phive()->isMobile() ? 'mobile' : ''?>">
        <?php if(!phive()->isMobile()): ?>
        <div>
            <img src="/diamondbet/images/time-limit-setup.png" style="height: 120px;">
            <h3><?php et('withdraw.limit.info.headline') ?></h3>
            <p>
                <span><?php et('withdraw.limit.info.html') ?></span>
            </p>
            <?php globalDepositLimitInfoTable($u_obj, $rgl, $progress) ?>
        </div>
        <?php else: ?>
            <h3><?php et('withdraw.limit.info.headline') ?></h3>
            <p>
                <span><?php et('withdraw.limit.info.html') ?></span>
            </p>
            <?php globalDepositLimitInfoTable($u_obj, $rgl, $progress) ?>
        <?php endif; ?>
        <div>
            <button class="btn btn-l positive-action-btn good-green">
                <div onclick="mboxClose()"><?php et('withdraw.limit.ok.btn') ?></div>
            </button>
        </div>
    </div>
</div>
