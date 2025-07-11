<?php
$rg = phive('Licensed')->rgLimits();
?>

<div id="register-set-login-limit" class="limits-deposit-set popup-limit">
    <div class="half">
        <img src="/diamondbet/images/time-limit-setup.png">
        <h3><?php et("registration.login.limit.set.title") ?></h3>
        <p>
            <span><?php et("registration.set.login.limit.description.part1") ?></span>
            <span><?php et("registration.set.login.limit.description.part2") ?></span>
        </p>
    </div>
    <div class="half gray">
        <form action="javascript:"
                onsubmit="return licFuncs.rgLimitPopupHandler().saveRgLimit(event, 'login')" method="post">
            <?php $display_unit = rgLimits()->displayUnit('login', cu()) ?>
            <?php foreach($rg->time_spans as $time_span): ?>
                <div>
                    <label for="resettable-<?php echo $time_span ?>" class="fat">
                        <?php et("rg.info.$time_span.limits") ?>
                        <span class="right">(<?php echo $display_unit ?>)</span>
                    </label>
                    <input placeholder="0"
                           class="input-normal big-input full-width flat-input"
                           oninput="this.value = licFuncs.rgLimitPopupHandler().validateLimit('<?= $time_span ?>', 'login', this.value)"
                           onkeyup="licFuncs.rgLimitPopupHandler().populateCalculated('<?= $time_span ?>', 'login', this.value)"
                           name="login-<?= $time_span ?>"
                           maxlength="10"
                           id="popup-login-limit-<?= $time_span ?>"
                           value=""
                    />
                    <span class="error hidden"><?php et("post-registration.login-limit-{$time_span}.invalid") ?></span>
                </div>
            <?php endforeach ?>
            <div>
                <button class="btn btn-l positive-action-btn">
                    <span><?php et('rg.info.limits.set.confirm') ?></span>
                </button>
            </div>
        </form>
    </div>
</div>




