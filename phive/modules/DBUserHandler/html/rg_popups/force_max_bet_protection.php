<?php
$u_obj = cu();
$rg = rgLimits();
$type = 'betmax';
?>
<div class="half">
    <?php if(!phive()->isMobile()): ?>
        <div class="dialog__image--centered">
            <img src="<?php fupUri('/popups/MaxBet-Icon.png')?>">
        </div>
    <?php endif; ?>
    <h3><?php et('max.bet.protection.title') ?></h3>
    <p>
        <span><?php et('max.bet.protection.description') ?></span>
    </p>
</div>
<div class="half gray">
    <form id="rg-duration-form">
        <div>
            <label for="betmax" class="betmax-label">
                <?php et("new.limit") ?>
                <span class="right">(<?php echo $rg->displayUnit($type, $u_obj) ?>)</span>
            </label>
            <input placeholder="<?php et('rg.info.limits.set.choose') ?>"
               class="input-normal big-input full-width flat-input" type="tel" pattern="[0-9]*" novalidate
               name="<?php echo $type ?>"
               id="<?php echo $type ?>"
               value=""
            />

            <div id="rg-duration-<?php echo $type ?>" class="left">
                <div class="half left padding-top-bottom">
                    <label class="left">
                        <input class="left" type="radio" name="rg_duration" value="day" checked="checked"/>
                        <?php et("rg.day.cooloff") ?>
                    </label>
                </div>
                <div class="half left padding-top-bottom">
                    <label class="left">
                        <input class="left" type="radio" name="rg_duration" value="week"/>
                        <?php et("rg.week.cooloff") ?>
                    </label>
                </div>
                <div class="half left padding-top-bottom">
                    <label class="left">
                        <input class="left" type="radio" name="rg_duration" value="month"/>
                        <?php et("rg.month.cooloff") ?>
                    </label>
                </div>
            </div>
        </div>
    </form>
    <div class="button-position">
        <button class="btn btn-l positive-action-btn good-green" onclick="setSingleLimit('<?php echo $type ?>', true)">
            <?php et('rg.info.limits.set.confirm') ?>
        </button>
    </div>
</div>
