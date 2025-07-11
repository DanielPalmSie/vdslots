<?php
$u_obj = cu();
$rg = rgLimits();
$limits = $rg->getGrouped($u_obj, $rg->resettable, true);
$type = 'login';
?>
<div class="half">
    <?php if(!phive()->isMobile()): ?>
        <div class="dialog__image--centered">
            <img src="<?php fupUri('/popups/Login-Icon.png')?>">
        </div>
    <?php endif; ?>
    <h3><?php et('login.limit.title') ?></h3>
    <p>
        <span><?php et('login.limit.description') ?></span>
    </p>
</div>
<div class="half gray">
    <form id="limits-login-set">
        <?php foreach($rg->time_spans as $time_span): ?>
            <div>
                <label for="resettable-<?php echo $type ?>-<?php echo $time_span ?>">
                    <?php et("rg.info.$time_span.limits"); ?>
                    <span class="right">(<?php echo $rg->displayUnit($type, $u_obj) ?>)</span>
                </label>
                <?php
                $limit_value = '';
                if (!empty($limits[$type][$time_span]['cur_lim'])) {
                    $limit_value = $rg->prettyLimit($type, $limits[$type][$time_span]['cur_lim']);
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
<script>
    $("#limits-login-set button").click(function (e) {
        e.preventDefault();
        licFuncs.rgSubmitAllResettable(null, true);
    })
</script>
