<?php

use Videoslots\User\ThirdPartyVerificationFields\Factory\TopPartFactory;

$mbox = new MboxCommon();
$u_obj = $mbox->getUserOrDie();
$rg = phive('Licensed')->rgLimits();
$limit = $rg->getLicDepLimit($u_obj);
?>
<style>
    .change-deposit-before-play {
        width: 400px;
        text-align: center;
    }

    .change-deposit-before-play__limit-info-box {
        background-color: #f4f4f4;
        padding: 20px;
        text-align: left;
        margin-bottom: 20px;
    }
</style>
<div class="lic-mbox-wrapper change-deposit-before-play">
    <?php
        $top_part_data = (new TopPartFactory())->create('change-deposit-before-play-box', null, true);
        $mbox->topPart($top_part_data);
    ?>
    <div class="lic-mbox-container">
        <div class="half">
            <img src="/diamondbet/images/time-limit-setup.png" height="110">
            <h3><?php et('rg.info.deposit.limits') ?></h3>
            <p>
                <span><?php et('rg.info.deposit.change-before-gameplay.description') ?></span>
            </p>
        </div>
        <div class="half gray">
            <form>
                <div class="change-deposit-before-play__limit-info-box">
                    <strong></string><?php et("rg.info.deposit.change.weekly.limit") ?></strong>
                    <span class="right"><?php echo rnfCents($limit['limit']) . ' ' . $rg->displayUnit('deposit', $u_obj) ?></span>
                    <input type="number" id="resettable-deposit-week" value="<?= phive()->twoDec($limit['limit']) ?>" style="display: none;">
                    <input type="number" id="autorevert-deposit-week" value="1" style="display: none;">
                </div>
                <div>
                    <?php btnActionXl(t('rg.info.deposit.accept.limit'), '', "setWeeklyLimit(event)", '', 'margin-ten-bottom') ?>
                    <?php btnDefaultXl(t('cancel'), '', "backToHome(event)", '', '') ?>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    var setWeeklyLimit = function (e) {
        e.preventDefault();
        var rg_login_info_callback = '<?= phive('Licensed')->getRedirectBackToLinkAfterRgPopup() ?>';
        var closeSelf = '<?= $_POST['noRedirect'] ? 'change-deposit-before-play-box' : null ?>';
        licFuncs.rgSubmitAllResettable(rg_login_info_callback, false, closeSelf);
    }

    var backToHome = function (e) {
        e.preventDefault();
        gotoLang('/');
    }
</script>
