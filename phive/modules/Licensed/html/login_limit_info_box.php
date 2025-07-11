<?php

use Videoslots\User\ThirdPartyVerificationFields\Factory\TopPartFactory;

$mbox   = new MboxCommon();
$u_obj  = $mbox->getUserOrDie();
$rg     = phive('Licensed')->rgLimits();
$limits = $rg->getGrouped($u_obj, $rg->resettable, true);
$type   = 'login';
?>
<div class="lic-mbox-wrapper">
    <?php
        $top_part_data = (new TopPartFactory())->create('rg-login-box', 'rg.login.limits.set.headline', true);
        $mbox->topPart($top_part_data);
    ?>
    <div class="lic-mbox-container limits-deposit-set" style="padding: 20px;">
        <div class="half">
            <?php if(!phive()->isMobile()): ?>
                <img src="/diamondbet/images/<?= brandedCss() ?>time-limit-setup.png">
            <?php endif; ?>
            <h3><?php et('rg.login.limits.set.title') ?></h3>
            <p>
                <span><?php et('rg.login.limits.set.deposit.description.part1') ?></span>
                <span><?php et('rg.login.limits.set.deposit.description.part2') ?></span>
            </p>
        </div>
        <div class="half gray">
            <form>
                <?php $default = licSetting('login_limit', $u_obj); ?>
                <?php foreach($rg->time_spans as $time_span): ?>
                    <div>
                        <label for="resettable-<?php echo $type ?>-<?php echo $time_span ?>">
                            <?php et("$time_span.ly") ?>
                            <span class="right">(<?php et("limit.label.". $rg->displayUnit($type, $u_obj)) ?>)</span>
                        </label>
                        <?php
                        if (!empty($limits[$type][$time_span]['cur_lim'])) {
                            $limit_value = $rg->prettyLimit($type, $limits[$type][$time_span]['cur_lim']);
                        } else {
                            $limit_value = $default['popup_default_values'][$time_span] ?? '';
                        }
                        ?>
                        <input placeholder="<?php et('rg.info.limits.set.choose') ?>"
                               class="input-normal big-input full-width flat-input" type="tel" pattern="[0-9]*" novalidate
                               name="resettable-<?php echo $type ?>-<?php echo $time_span ?>"
                               id="resettable-<?php echo $type ?>-<?php echo $time_span ?>"
                               value="<?= $limit_value ?>"
                        />
                    </div>
                <?php endforeach ?>
                <div>
                    <button class="btn btn-l positive-action-btn good-green">
                        <?php et('rg.info.limits.set.confirm') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('.limits-deposit-set .input-normal').on('change blur', function(e){
            e.target.value = getMaxIntValue(e.target.value);
        });

        if (licFuncs.assistOnLoginLimitsChange) {
            licFuncs.assistOnLoginLimitsChange(
                "#resettable-login-day",
                "#resettable-login-week",
                "#resettable-login-month"
            );
        }
    });
    $(".limits-deposit-set button").click(function (e) {
        var rg_login_info_callback = '<?= phive('Licensed')->getRedirectBackToLinkAfterRgPopup() ?>';
        e.preventDefault();
        licFuncs.rgSubmitAllResettable(rg_login_info_callback, true);
    })
</script>
