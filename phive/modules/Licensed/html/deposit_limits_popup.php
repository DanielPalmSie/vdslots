<?php
$mbox   = new MboxCommon();
$user = $_POST['is_registration'] ? cuRegistration() : $mbox->getUserOrDie();
$user->deleteSetting("force_deposit_limit");
$rg = rgLimits();
$mobile = phive()->isMobile() ? 'mobile' : '';
$limits = $rg->getGrouped($user, $rg->resettable, true);
$type = 'deposit';
$on_submit = $_POST['on_submit'] ?? false;
?>
<?php if(empty(phive()->isMobile())): ?>
    <style>
        #mbox-msg {
            width: 775px !important;
        }
    </style>
<? else: ?>
    <style>
        #mbox-msg {
            width: 100% !important;
        }
        .mobile .half {
            width: 100%;
        }
    </style>
<? endif; ?>
<div class="lic-mbox-container limits-deposit-set <?php echo $mobile ?>" style="padding: 20px;">
    <div class="half">
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
            <?php $default_limits = lic('getDefaultLimitsByType', [$user, $type], $user); ?>
            <?php foreach($rg->time_spans as $time_span): ?>
                <div>
                    <label for="resettable-<?php echo $type ?>-<?php echo $time_span ?>">
                        <?php et("$time_span.ly") ?>
                        <span class="limits-deposit-set__unit right">(<?php echo $rg->displayUnit($type, $user) ?>)</span>
                    </label>
                    <?php
                    if (!empty($limits[$type][$time_span]['cur_lim'])) {
                        $limit_value = $rg->prettyLimit($type, $limits[$type][$time_span]['cur_lim']);
                    } else {
                        $limit_value =  $rg->prettyLimit($type, $default_limits[$time_span], true);
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
<script>
    $(document).ready(function() {
        $('.limits-deposit-set .input-normal').on('change blur', function(e){
            e.target.value = getMaxIntValue(e.target.value);
        });

        if (licFuncs.assistOnLimitsChange) {
            licFuncs.assistOnLimitsChange("resettable-deposit")
        }

        $("#resettable-deposit-day").trigger('keyup');
    });

    $(".limits-deposit-set button").click(function (e) {
        var rg_login_info_callback = '<?= phive('Licensed')->getRedirectBackToLinkAfterRgPopup() ?>';
        e.preventDefault();

        <?php if($on_submit): ?>
            <?= $on_submit ?>
        <?php else: ?>
            licFuncs.rgSubmitAllResettable(rg_login_info_callback)
        <?php endif; ?>
    })
</script>
