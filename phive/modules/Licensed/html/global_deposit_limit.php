<?php

use Videoslots\User\ThirdPartyVerificationFields\Factory\TopPartFactory;

$mbox = new MboxCommon();
$u_obj = $mbox->getUserOrDie();
$rgl = rgLimits()->getLicDepLimit($u_obj);
$is_mobile = phive()->isMobile();
$branded_banner = licSetting('branded_banner', $u_obj);

$progress = rgLimits()->getLicDepLimitProgress($u_obj, $rgl);
if ($u_obj->hasSetting('forced_global_deposit_limit')) {
    $limit_reached = true;
    $progress = 0;
} else {
    // check existing deposits against global limit
    if (empty($rgl['allow_global_limit_override'])) {
        $limit_reached = $progress >= $rgl['limit'];
    } else {
        // check against users set rg_limits
        $limit_reached = rgLimits()->reachedType($u_obj, 'deposit', 0, true);
    }
}
$has_branded_banner = !empty($branded_banner['img']) && $limit_reached;

$box_title = $limit_reached ? "global.deposit.limit.title.reached" : "global.deposit.limit.title.reminder";
$box_id = $_REQUEST['box_id'] ?? 'global-deposit-limit';
$cashier_box_id = $_REQUEST['isFastDeposit'] ? $box_id : 'cashier-box';
// desktop is inside an iframe, mobile is a normal popup, so we need to target 2 different scenario.
$target_box_id = $is_mobile ? $box_id : $cashier_box_id;

// as fast deposit is not inside cashier iframe, we need to add a listener "on close" to be able to close both popups
if (!empty($rgl['allow_global_limit_override']) && $_REQUEST['isFastDeposit']) {
    ?>
    <script>
        $('.lic-mbox-close-box, #change-deposit-limit').click(function () {
            setTimeout(function () {
                $.multibox('close', 'fast-deposit-box');
            }, 1000)
        });
    </script>
    <?php
} elseif (!empty($rgl['allow_global_limit_override']) && $target_box_id == 'cashier-box') {
    ?>
    <script>
        $('.lic-mbox-close-box, #change-deposit-limit').click(function () {
            $.multibox('close', 'global-deposit-limit');
        });
    </script>
    <?php
}

/**
 * table that display current limit / progress
 *
 * @param $rgl
 * @param $progress
 */
function globalDepositLimitInfoTable($rgl, $progress)
{
    $class = $progress >= $rgl['limit'] ? 'red' : 'grey';
    ?>
    <table class="global-deposit-limit__table <?php echo $class ?>">
        <tr>
            <td class="label">
                <?php et('limit.left') ?>:
            </td>
            <td class="value">
                <?php if (($rgl['limit'] - $progress) < 0): ?>
                    <?php echo rnfCents(0) . ' ' . ciso() ?>
                <?php else: ?>
                    <?php echo rnfCents($rgl['limit'] - $progress) . ' ' . ciso() ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td class="label">
                <?php et('max.limit') ?>:
            </td>
            <td class="value">
                <?php echo rnfCents($rgl['limit']) . ' ' . ciso() ?>
            </td>
        </tr>
        <tr>
            <td class="label">
                <?php et('global.deposit.limit.until') ?>:
            </td>
            <td class="value">
                <?php et('sunday') ?>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * table that display current set deposit limits
 */
function printCurrentDepositLimitsTable($u_obj)
{
    $type = 'deposit';
    $rg = rgLimits();
    $limits = $rg->getGrouped($u_obj, $rg->resettable, true);
    ?>
    <table class="global-deposit-limit__table red text-black">
        <?php foreach ($rg->time_spans as $time_span): ?>
            <tr>
                <td class="label">
                    <?php et("rg.info.{$time_span}.limits") ?>:
                </td>
                <td class="value">
                    <?php echo $rg->prettyLimit($type, $limits[$type][$time_span]['cur_lim']) . ' ' . $rg->displayUnit($type, $u_obj) ?>
                </td>
            </tr>
        <?php endforeach ?>
    </table>
    <?php
}

/**
 * Right side banner (img and link configurable on licSetting)
 *
 * @param $branded_banner
 */
function printBanner($branded_banner)
{
    if (empty($branded_banner)) {
        return;
    }
    ?>
    <a id="brand-banner" href="<?= $branded_banner['link'] ?>" target="_blank" rel="noopener noreferrer">
        <img alt="banner" src="<?php fupUri($branded_banner['img']) ?>">
    </a>
    <?php
}

/**
 * Show the 3 limits to update (day,week,month)
 * TODO this is practically a copy/paste from dep_lim_info_box, next one working on this when we have time need to refactor this logic
 *  into a common method that would be reusable on other popups, for other type of limits (similar to AccountBox methods) /Paolo
 *
 * @param $u_obj
 * @param $box_id - box_id to close
 */
function printDepositLimit($u_obj, $box_id)
{
    $type = 'deposit';
    $default_limits = lic('getDefaultLimitsByType', [$u_obj, $type], $u_obj);
    $rg = rgLimits();
    $limits = $rg->getGrouped($u_obj, $rg->resettable, true);
    $class = phive()->isMobile() ? 'mobile' : '';
    ?>
    <div id="set-deposit-limit" class="global-deposit-limit__set_limit <?= $class ?>" style="display: none;">
        <?php foreach ($rg->time_spans as $time_span): ?>
            <div>
                <label for="resettable-<?php echo $type ?>-<?php echo $time_span ?>">
                    <?php et("$time_span.ly") ?>
                    <span class="limits-deposit-set__unit right">(<?php echo $rg->displayUnit($type, $u_obj) ?>)</span>
                </label>
                <?php
                if (!empty($limits[$type][$time_span]['cur_lim'])) {
                    $limit_value = $rg->prettyLimit($type, $limits[$type][$time_span]['cur_lim']);
                } else {
                    $limit_value = $rg->prettyLimit($type, $default_limits[$time_span], true);
                }
                ?>
                <input placeholder="<?php et('rg.info.limits.set.choose') ?>"
                       class="input-normal big-input full-width flat-input" type="tel" pattern="[0-9]*"
                       novalidate
                       name="resettable-<?php echo $type ?>-<?php echo $time_span ?>"
                       id="resettable-<?php echo $type ?>-<?php echo $time_span ?>"
                       value="<?= $limit_value ?>"
                />
            </div>
        <?php endforeach ?>
        <div>
            <button id="change-deposit-limit" class="btn btn-l positive-action-btn good-green">
                <?php et('rg.info.limits.set.confirm') ?>
            </button>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $('.limits-deposit-set .input-normal').on('change blur', function (e) {
                e.target.value = getMaxIntValue(e.target.value);
            });

            if (licFuncs.assistOnLimitsChange) {
                licFuncs.assistOnLimitsChange("resettable-deposit")
            }
        });

        $("#change-deposit-limit").click(function (e) {
            e.preventDefault();
            <?php if(phive()->isMobile()): ?>
            licFuncs.rgSubmitAllResettable(<?="'" . llink('/') . "'"?>, false, false, window, true);
            <?php else: ?>
            licFuncs.rgSubmitAllResettable(false, false, '<?=$box_id?>', parent);
            <?php endif; ?>
        })
    </script>
    <?php
}

/**
 * Print the generic button for the popup, will simply close this warning. (used when we don't have banner)
 *
 * @param $limit_reached
 * @param $box_id
 */
function printGlobalDepositButton($limit_reached, $box_id)
{
    $message = 'continue.with.deposit';
    if ($limit_reached) {
        $message = 'ok';
    }
    ?>
    <button class="btn btn-l positive-action-btn good-green lic-mbox-container-flex__button">
        <div onclick="mboxClose('<?= $box_id ?>')"><?php et($message) ?></div>
    </button>
    <?php
}

/**
 * Print the button that allow to show the "set deposit limit" action.
 * (this work only when banner is enabled + "allow_global_limit_override" licSetting)
 *
 * @param $u_obj
 * @param $rgl
 * @param $limit_reached
 * @return string
 */
function printOvverideLimitButton($u_obj, $rgl, $limit_reached)
{
    if (empty($rgl['allow_global_limit_override']) || !$limit_reached) {
        return '';
    }
    ?>
    <script>
        function showChangeLimit(e) {
            e.preventDefault();
            $('#change-limit-button').hide();
            $('#brand-banner').hide();
            $('.go-to-brand').hide();
            $('.lic-mbox-container-flex__half--right').removeClass('black').addClass('gray').show();
            $('#set-deposit-limit').show();
            $('#set-deposit-limit form div').first().css({'margin-top': 0})
            setTimeout(scrollToBottom.bind(this, true), 500);
        }
    </script>
    <button id="change-limit-button" type="button" class="btn btn-l btn-default-l lic-mbox-container-flex__button">
        <div onclick="showChangeLimit(event)"><?php et('change.my.limits') ?></div>
    </button>
    <?php
}

/**
 * Print button under banner img with link to other site.
 *
 * @param $branded_banner
 * @param string $side
 */
function printGoToBrandButton($branded_banner, $side = 'left-half')
{
    if (empty($branded_banner)) {
        return;
    }
    $remote = getRemote();
    ?>
    <button class="btn btn-l positive-action-btn good-green lic-mbox-container-flex__button go-to-brand <?=$side?> brand-overwrite-<?=$remote?>"
            onclick="goTo('<?= $branded_banner['link'] ?>', '_blank')">
        <?php et('go.to.' . $remote) ?>
    </button>
    <?php
}

$title = 'deposit.limit.reminder.info.headline';
$description = 'deposit.limit.reminder.info.html';
$div_class = 'full';
if ($has_branded_banner) {
    $title = 'deposit.limit.banner.info.headline';
    $description = 'deposit.limit.banner.info.html';
    $div_class = 'half';
}
if (!empty($rgl['allow_global_limit_override'])) {
    $title = 'deposit.limit.override.info.headline';
    $description = 'deposit.limit.override.info.html';
    $div_class = 'half';
}
?>

<div class="lic-mbox-wrapper global-deposit-limit">
    <?php
        $top_part_data = (new TopPartFactory())->create($target_box_id, $box_title, false, true, 'parent', true);
        $mbox->topPart($top_part_data);
    ?>
    <div class="lic-mbox-container-flex <?= $is_mobile ? 'mobile' : '' ?>">
        <div class="lic-mbox-container-flex__<?= $div_class ?>">
            <?php if (!$is_mobile): ?>
                <img src="/diamondbet/images/time-limit-setup.png" style="height: 120px; margin: auto;">
            <?php endif ?>
            <?php if (!$is_mobile || !$has_branded_banner): ?>
                <h3><?php et($title) ?></h3>
            <?php endif ?>
            <div>
                <?php
                et($description);
                if (!empty($rgl['allow_global_limit_override'])) {
                    printCurrentDepositLimitsTable($u_obj);
                } else {
                    globalDepositLimitInfoTable($rgl, $progress);
                }
                ?>
            </div>
            <?php
            if (!empty($rgl['allow_global_limit_override'])) {
                printOvverideLimitButton($u_obj, $rgl, $limit_reached);
                printGoToBrandButton($branded_banner);
            } else {
                printGlobalDepositButton($limit_reached, $box_id);
            }
            ?>
        </div>
        <?php if ($has_branded_banner || !empty($rgl['allow_global_limit_override'])): ?>
            <div class="lic-mbox-container-flex__<?= $div_class ?> lic-mbox-container-flex__<?= $div_class ?>--right black"
                 style="<?php echo !$has_branded_banner && !empty($rgl['allow_global_limit_override']) ? 'display:none;' : '' ?>">
                <?php printBanner($branded_banner) ?>
                <?php printDepositLimit($u_obj, $target_box_id) ?>
                <?php printGoToBrandButton($branded_banner, 'right-half') ?>
            </div>
        <?php endif ?>
    </div>
</div>
<?php // Hack div to allow scrolling at the end of the page, it needs to have at least 1px height ?>
<div id="end-of-popup">&nbsp;</div>
<script>
    function isPortrait() {
        var screenRatio = $(window).height() / $(window).width();
        return screenRatio > 1;
    }
    function scrollToBottom(forced) {
        if (!isPortrait() || forced) {
            var el = document.getElementById('end-of-popup');
            if(el){
                el.scrollIntoView();
            }
        }
    }
    $(document).ready(function () {
        setTimeout(scrollToBottom, 500);
        window.addEventListener('resize', function () {
            setTimeout(scrollToBottom, 500);
        });
    });
</script>

