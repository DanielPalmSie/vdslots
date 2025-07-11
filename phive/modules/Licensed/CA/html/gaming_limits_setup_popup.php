<?php
$rg = phive('Licensed')->rgLimits();
$resettable = ['deposit', 'loss', 'wager'];

$mbox = new MboxCommon();
$u_obj = $mbox->getUserOrDie();

?>

<script>
    var reSpans = <?php echo json_encode($rg->time_spans) ?>;
</script>


<?php if (phive()->isMobile()) : ?>
    <div id="register-set-deposit-limit" class="limits-deposit-set popup-limit mobile gaming-limit-container">
        <div class="w-100-pc">
            <img alt="Set gaming limit image" src="/diamondbet/images/gaming-limit-set.png">
            <h3><?php et('gaming.set.limit.mobile.title') ?></h3>
            <p>
                <span><?php et('registration.set.deposit.limit.description.part2') ?></span>
            </p>
        </div>
        <?php foreach ($resettable as $type): ?>

            <br/>
            <div class="vip-color gaming-limit-text"><?php et("rg.info.$type.limits") ?>
            </div>
            <div class="half gray center-stuff">
                <form action="javascript:" method="post">
                     <div class="rg-pop-lims-tbl">
                        <div class="full-width">
                            <div>
                                <?php foreach($rg->time_spans as $tspan): ?>
                                    <div>
                                        <label for="resettable-<?php echo $tspan ?>" class="fat gaming-limit-label">
                                            <?php et("rg.info.$tspan.limits")  ?>
                                            <span class="limits-deposit-set__unit right">(<?php echo $rg->displayUnit($type, $u_obj) ?>)</span>
                                        </label>
                                        <input
                                               class="input-normal big-input flat-input full-width"
                                               name="lp-<?= "$type-$tspan" ?>"
                                               maxlength="10"
                                               id="lp-<?= "$type-$tspan" ?>"
                                               value=""
                                               onkeyup="licFuncs.setLimitPopupHandler().populateCalculated('<?= $tspan ?>', '<?= $type ?>', this.value)"
                                        />
                                    </div>
                                <?php endforeach ?>
                            </div>
                            <br clear="all"/>
                        </div>
                     </div>
                </form>
            </div>
        <?php endforeach ?>
        <div>
            <button class="btn btn-l btn-default-xs" onclick="licFuncs.setLimitPopupHandler().setResettableLimits()">
                <span><?php et('registration.set.limits.text') ?></span>
            </button>
        </div>
    </div>
<?php else: // Desktop ?>
    <div class="lic-mbox-container limits-info gaming-limit-container">
        <div class="center-stuff"><?php et('gaming.set.limit.description') ?></div>
        <br/>
        <div class="rg-pop-lims-tbl">
            <?php foreach ($resettable as $type): ?>

                <div class="full-width">
                    <div class="vip-color left"><?php et("rg.info.$type.limits") ?>:</div>
                    <div class="right">
                        <?php foreach ($rg->time_spans as $tspan): ?>
                            <div class="left rg-column">
                                <div class="left"><?php et("rg.info.$tspan.limits") ?></div>
                                <div class="limits-deposit-set__unit right">(<?php echo $rg->displayUnit($type, $u_obj) ?>)</div>
                                <input
                                       class="input-normal big-input flat-input full-width"
                                       name="lp-<?= "$type-$tspan" ?>"
                                       maxlength="10"
                                       id="lp-<?= "$type-$tspan" ?>"
                                       value=""
                                       onkeyup="licFuncs.setLimitPopupHandler().populateCalculated('<?= $tspan ?>', '<?= $type ?>', this.value)"
                                />
                            </div>
                        <?php endforeach ?>
                    </div>
                    <br clear="all"/>
                </div>
            <?php endforeach ?>
        </div>
        <br clear="all"/>
        <div class="center-stuff rg-footer">
            <button class="btn btn-l btn-default-l w-300" onclick="licFuncs.setLimitPopupHandler().setResettableLimits()"><?php et('registration.set.limits.text') ?></button>
        </div>
    </div>
<?php endif ?>

