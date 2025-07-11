<?php

use Videoslots\User\ThirdPartyVerificationFields\Factory\TopPartFactory;

$mbox   = new MboxCommon();
$u_obj = $mbox->getUserOrDie();
$rg     = phive('Licensed')->rgLimits();
$limits = $rg->getGrouped($u_obj, $rg->resettable, true);
$type   = 'deposit';
?>
<div class="lic-mbox-wrapper">
    <?php
        $top_part_data = (new TopPartFactory())->create('dep-lim-info-box', 'rg.info.limits.set.headline', true);
        $mbox->topPart($top_part_data)
    ?>
    <div class="lic-mbox-container limits-deposit-set mobile">
        <div class="half half_first">
            <?php if(!phive()->isMobile()): ?>
                <img src="/diamondbet/images/<?= brandedCss() ?>deposit-limit-setup.png">
            <?php endif; ?>
            <h3><?php et('rg.info.limits.set.title') ?></h3>
            <p>
                <span><?php et('rg.info.limits.set.deposit.description.part1') ?></span>
                <span><?php et('rg.info.limits.set.deposit.description.part2') ?></span>
            </p>
        </div>
        <div class="half gray">
            <form>
                <?php $default_limits = lic('getDefaultLimitsByType', [$u_obj, $type], $u_obj); ?>
                <?php $highest_allowed_deposit_limits = licSetting('deposit_limit', $u_obj)['highest_allowed_limit'] ?? []; ?>
                <?php foreach ($rg->time_spans as $time_span): ?>
                    <div>
                        <label for="resettable-<?php echo $type ?>-<?php echo $time_span ?>">
                            <?php et("$time_span.ly") ?>
                            <span class="limits-deposit-set__unit right">
                                (<?php echo $rg->displayUnit($type, $u_obj) ?>)
                            </span>
                        </label>
                        <?php
                        if (!licSetting('deposit_limit', $u_obj)['show_default']) {
                            $limit_value = '';
                        } else if (!empty($limits[$type][$time_span]['cur_lim'])) {
                            $limit_value = $rg->prettyLimit($type, $limits[$type][$time_span]['cur_lim']);
                        } else {
                            $limit_value = $rg->prettyLimit($type, $default_limits[$time_span], true);
                        }
                        ?>
                        <input placeholder="<?php et('rg.info.limits.set.choose') ?>"
                               class="input-normal big-input full-width flat-input" type="tel" pattern="[0-9]*"
                               novalidate
                               data-resettable-deposit-limit="<?= $type === 'deposit' ? $highest_allowed_deposit_limits[$time_span] / 100 : '' ?>"
                               name="resettable-<?php echo $type ?>-<?php echo $time_span ?>"
                               id="resettable-<?php echo $type ?>-<?php echo $time_span ?>"
                               value="<?= $limit_value ?? 0 ?>"
                        />
                        <div class="deposit-limit-error" style="float: right; color: red; display:none;"><?php echo tAssoc('rg.up.to.x.limit', ['limit' => $highest_allowed_deposit_limits[$time_span] / 100]) ?></div>
                        <div class="deposit-limit-enter-limit" style="float: right; color: red; display:none;"><?php et('rg.info.limits.set.choose') ?></div>
                    </div>
                <?php endforeach ?>
                <div class="rg-popup-action">
                    <div class="rg-popup-action__checkbox">
                        <?php phive('BoxHandler')->getRawBox('AccountBox')->showCrossBrandLimitCheckbox($type, $u_obj) ?>
                    </div>
                    <div class="rg-popup-action__extra-text">
                        <?php phive('BoxHandler')->getRawBox('AccountBox')->showCrossBrandLimitText($type, $u_obj) ?>
                    </div>
                    <button class="btn btn-l good-green">
                        <?php et('rg.info.limits.set.confirm') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    licFuncs.limits.has_default_deposit_limits  = <?php echo json_encode(!empty($default_limits)) ?>;

    $(document).ready(function () {
        $('.limits-deposit-set .input-normal').on('change blur', function (e) {
            $(this).siblings('.deposit-limit-enter-limit').hide();
            if (e.target.value === '') {
                $(this).siblings('.deposit-limit-enter-limit').show();
                $(this).removeClass('required-input');
                $(this).addClass('input-error');
            } else {
                e.target.value = getMaxIntValue(e.target.value);
            }
        });

        if (licFuncs.assistOnLimitsChange) {
            licFuncs.assistOnLimitsChange("resettable-deposit")
        }
    });

    $(".limits-deposit-set button").click(function (e) {
        var rg_login_info_callback = '<?= phive('Licensed')->getRedirectBackToLinkAfterRgPopup() ?>';
        var closeSelf = '<?= $_POST['noRedirect'] ? 'dep-lim-info-box' : null ?>';
        e.preventDefault();
        if ($('form input.input-error').length === 0) {
            licFuncs.rgSubmitAllResettable(rg_login_info_callback, false, closeSelf);
        }
    })
</script>
