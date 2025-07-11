<?php
$rg = phive('Licensed')->rgLimits();
?>

<div id="register-set-deposit-limit" class="limits-deposit-set popup-limit">
    <div class="half">
        <img src="/diamondbet/images/deposit-limit-setup.png">
        <h3><?php et('rg.info.limits.set.title') ?></h3>
        <p>
            <span><?php et('registration.set.deposit.limit.description.part1') ?></span>
            <span><?php et('registration.set.deposit.limit.description.part2') ?></span>
        </p>
    </div>
    <div class="half gray">
        <form action="javascript:"
                onsubmit="return licFuncs.rgLimitPopupHandler().saveRgLimit(event, 'deposit')" method="post">
            <?php foreach($rg->time_spans as $time_span): ?>
                <div>
                    <label for="resettable-<?php echo $time_span ?>" class="fat">
                        <?php et("rg.info.$time_span.limits")  ?>
                        <span class="limits-deposit-set__unit right">(<?php echo cs() ?>)</span>
                    </label>
                    <input placeholder="0"
                           class="input-normal big-input full-width flat-input"
                           oninput="this.value = licFuncs.rgLimitPopupHandler().validateLimit('<?= $time_span ?>', 'deposit', this.value)"
                           onkeyup="licFuncs.rgLimitPopupHandler().populateCalculated('<?= $time_span ?>', 'deposit', this.value)"
                           name="deposit-<?= $time_span ?>"
                           maxlength="10"
                           id="popup-deposit-limit-<?= $time_span ?>"
                           value=""
                    />
                    <span class="error hidden"><?php et("post-registration.deposit-limit-{$time_span}.invalid") ?></span>
                </div>
            <?php endforeach ?>
            <div>
                <button
                        class="btn btn-l positive-action-btn deposit_limit_popup_btn">
                    <span><?php et('registration.set.limits.text') ?></span>
                </button>
            </div>
        </form>
    </div>
</div>




