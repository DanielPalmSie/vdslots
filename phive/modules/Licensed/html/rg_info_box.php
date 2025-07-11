<?php

use Videoslots\User\ThirdPartyVerificationFields\Factory\TopPartFactory;
use Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\TotalWinLossData;
use Videoslots\RgLimits\Settings;
use Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\WinLossContainerData;
use Videoslots\RgLimitsPopup\Factories\FooterFactory;
use Videoslots\RgLimitsPopup\Factories\HeaderFactory;
use Videoslots\RgLimitsPopup\Factories\RgLimitsContainerFactory;
use Videoslots\RgLimitsPopup\Factories\WinLossContainerFactory;

use Carbon\Carbon;

$action = $_POST['action'] ?? '';
$on_game_page = $_POST['on_game_page'] ?? '';
$rg_config = 'rg_info';
$extra = [];

if (!empty($_POST['intensive_gambler'])) {
    $rg_config = 'rg_65_info';
    $extra = [ 'setting' => 'rg_65_info', 'subheader' => 'rg.65.info.popup.winloss.period'];
}

$mbox = new MboxCommon();
$u_obj = $mbox->getUserOrDie();
$header = (new HeaderFactory())->create($u_obj, false, $rg_config);
$headerDescription = $header->getDescription();

$winloss_container = (new WinLossContainerFactory())->create($u_obj, $extra);
$winloss_container_totals = $winloss_container->getTotals();
$rg_limits_container = (new RgLimitsContainerFactory())->create($u_obj);
$footer = (new FooterFactory())->create($u_obj, false);

// Always positive for the time being.
$rg = phive('Licensed')->rgLimits();
$rg_info_setting = licSetting($rg_config, $u_obj);

$is_paynplay_mode = isPNP();
$box_id = $is_paynplay_mode ? 'pnp-gaming-experience-box' : 'rg-login-box';

/**
 * This function is used to format date
 * eg: 2023-12-03 to 2023 / 12 / 03
 *
 * @return string
 */
function formatDate($headerDescription): string
{
    $loginData = $headerDescription->getLastLoginData();
    $placeholders =  $loginData->getPlaceholders();
    $dateString = $placeholders['date'];
    $date = Carbon::parse($dateString);
    $formattedDate = $date->format('Y / m / d  H:i:s');
    $parts = explode("  ", $formattedDate);
    return $parts[0] . "&nbsp;&nbsp;&nbsp;&nbsp;" . $parts[1];
}

/**
 * @param \DBUser $u_obj
 * @param \Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\WinLossContainerData $winloss_container
 * @param \Laraphive\Domain\User\DataTransferObjects\RgLimitsPopup\TotalWinLossData $winloss_container_totals
 * @param array|null $rg_info_setting
 *
 * @return void
 */
function rgWagerResultSection(
    DBUser $u_obj,
    WinLossContainerData $winloss_container,
    TotalWinLossData $winloss_container_totals,
    ?array $rg_info_setting = []
): void { ?>
<script>
    function showWagerResult(){
        $('#winloss-result').show();
        $('#show-sum-btn').hide();
    }
</script>
<?php
    $show_win_loss_by_default = isset($rg_info_setting['default_show_win_loss_result'])
        && $rg_info_setting['default_show_win_loss_result'];
    $is_paynplay_mode = isPNP();
?>
<div class="winloss-container left">
    <?php if (!phive()->isMobile()): ?>
        <br clear="all"/>
    <?php endif ?>
    <div class="left <?= phive()->isMobile() ? 'center-stuff' : '' ?>">
        <div><?php et($winloss_container->getHeadline()) ?>:</div>
        <div class="vip-color">
            <?php et($winloss_container->getSubheader()) ?>
        </div>
    </div>
    <div id="show-sum-btn"
         class="right"
         style="<?= $show_win_loss_by_default ? 'display: none;' : ''; ?> font-size: 12px;"
         onclick="showWagerResult()"
    >
        <span class="vip-color small-bold"><?php et($winloss_container->getButton()->getAlias()) ?></span>
    </div>
    <div class="right" id="winloss-result" style="<?= !$show_win_loss_by_default ? 'display: none;' : '' ?>">
        <?php if ($is_paynplay_mode): ?>
            <div class="right positive-number"
                 style="<?= phive()->isMobile() ? 'width: 295px;' : '' ?>"
            >
               <span class="result-amount"> <?= $winloss_container->getAmount() ?> </span> <span class="result-currency"> <?php echo $u_obj->getCurrency() ?> </span>
            </div>
        <?php else: ?>
            <div class="right">
                (<?php echo $u_obj->getCurrency() ?>)
            </div>
            <div class="right positive-number"
                 style="<?= phive()->isMobile() ? 'width: 295px;' : '' ?>"
            >
                <?= $winloss_container->getAmount() ?>
            </div>
        <?php endif ?>
    </div>
    <?php if (!empty($winloss_container_totals->getTypes())): ?>
        <br clear="all"/>
        <div class="winloss-info__container">
            <div class="winloss-info__container-item winloss-info__container-item--left">
                <div class="winloss-container__activity">
                    <?php et($winloss_container_totals->getHeadline()) ?> (<?= $u_obj->getCurrency() ?>)
                </div>
            </div>
            <div class="winloss-info__container-item  winloss-info__container-item--right">
                <?php foreach ($winloss_container_totals->getTypes() as $winloss_container_type): ?>
                    <span class="winloss-info">
                        <span class="winloss-info__label">
                            <?php et($winloss_container_type->getAlias()) ?>:
                        </span>
                        <span class="winloss-info__amount">
                            <?= $winloss_container_type->getAmount() ?>
                        </span>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif ?>
</div>
<br clear="all"/>
<?php
}
?>
<?php
    $top_part_data = (new TopPartFactory())->create(
        $box_id,
        'rg.info.limits.show.headline',
        $header->isForceAccept(),
        !$on_game_page
    );
    $mbox->topPart($top_part_data);
?>
<?php if (phive()->isMobile()): ?>
    <style>
        /* Leave this css here to overwrite the default width only for this case */
        #rg-login-box {
            width: 100% !important;
            left: 0;
        }
    </style>
    <div class="lic-mbox-container limits-info mobile rg-login-popup-mobile">
        <div style="padding: 10px;">
            <?php lic('rgLogo', ['black', 'center'], $u_obj); ?>
            <br clear="all"/>
            <div id="rg-login-popup-proceed" class="positive-action-btn rg-activity-div"
                 data-action="<?= $action ?>"
                 data-on_game_page="<?= $on_game_page ?>"
                 style="width: 200px; margin: auto;"
            >
                <?php et($header->getButton()->getAlias()) ?>
            </div>
            <br clear="all"/>
            <div class="center-stuff">
                <h3><?php et($header->getHeadline()) ?></h3>
            </div>
            <?php if ($is_paynplay_mode): ?>
                <div class="center-stuff">
                    <?php et($headerDescription->getAlias()) ?>
                    <br>
                    <span class="last-login">
                        <?php et2('rg.info.box.last_login_date', ['date' => formatDate($headerDescription)]) ?>
                    </span>
                </div>
            <?php else: ?>
                <div class="center-stuff">
                    <?php et($headerDescription->getAlias()) ?>
                    <br>
                    <?php etAssoc(
                        $headerDescription->getLastLoginData()->getAlias(),
                        $headerDescription->getLastLoginData()->getPlaceholders()
                    )?>
                </div>
            <?php endif; ?>

            <?php rgWagerResultSection($u_obj, $winloss_container, $winloss_container_totals, $rg_info_setting) ?>

            <div class="center-stuff table-prefix"><h3><?php et($rg_limits_container->getHeadline()) ?></h3></div>
            <table class="rg-pop-lims-tbl">
                <?php foreach ($rg_limits_container->getLimitTypes() as $limit_types): ?>
                    <tr class="first">
                        <td colspan="3">
                            <span class="vip-color" style="font-weight: bold;">
                                <?php et("rg.info.{$limit_types->getType()}.limits") ?>
                            </span>
                            <?php if (!$is_paynplay_mode): ?>
                                <span style="font-size: 10px;">
                                    (<?php echo $rg->displayUnit($limit_types->getType(), $u_obj) ?>)
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr class="last">
                        <?php foreach ($limit_types->getLimits() as $limit): ?>
                            <?php if ($limit_types->getType() !== Settings::LIMIT_BALANCE): ?>
                                <td>
                                    <div class="left">
                                        <?php et("rg.info.{$limit->getTimeSpan()}.limits") ?>
                                        <?php if ($is_paynplay_mode): ?>
                                            (<?php echo $rg->displayUnit($limit_types->getType(), $u_obj) ?>)
                                        <?php endif; ?>
                                    </div>
                                    <?php dbInput(
                                        "resettable-{$limit_types->getType()}-{$limit->getTimeSpan()}",
                                        $limit->getCurrentLimit(),
                                        'text',
                                        "input-normal big-input discreet-border flat-input",
                                        "disabled"
                                    )?>
                                </td>
                            <?php else: ?>
                                <td>
                                    <div class="left"><?php et("rg.info.balance.active.limits") ?></div>
                                    <?php dbInput(
                                        "resettable-balance-active",
                                        $limit->getCurrentLimit(),
                                        'text',
                                        "input-normal big-input discreet-border flat-input",
                                        "disabled")
                                    ?>
                                </td>
                                <td>
                                    <div class="left"><?php et("rg.info.balance.remaining.limits") ?></div>
                                    <?php dbInput(
                                        "resettable-balance-remaining",
                                        $limit->getRemaining(),
                                        'text',
                                        "input-normal big-input discreet-border flat-input",
                                        "disabled"
                                    )?>
                                </td>
                            <?php endif; ?>
                        <?php endforeach ?>
                    </tr>
                <?php endforeach ?>
            </table>
            <div class="center-stuff rg-footer">
                <span>
                    <?php et($footer->getHeadline()) ?>
                </span>
                <br clear="all"/><br clear="all"/>
                <button class="btn btn-l btn-default-l w-300"
                        onclick="goTo('<?= $footer->getButtonData()->getPage(); ?>')">
                        <?php et($footer->getButtonData()->getAlias())  ?>
                </button>
            </div>
        </div>
    </div>
<?php else: // Desktop ?>
    <style>
        /* Leave this css here to overwrite the default width only for this case */
        #rg-login-box {
            width: 840px !important;
        }
    </style>
    <div class="lic-mbox-container limits-info rg-login-popup">
        <?php lic('rgLogo', ['black', 'left'], $u_obj); ?>
        <div id="rg-login-popup-proceed" class="positive-action-btn right rg-activity-div"
             data-action="<?= $action ?>"
             data-on_game_page="<?= $on_game_page ?>">
            <?php et($header->getButton()->getAlias()) ?>
        </div>
        <br clear="all"/>
        <?php if ($is_paynplay_mode): ?>
            <div class="horizontal-line"></div>
            <div class="center-stuff title">
                <?php et($header->getHeadline()) ?>
            </div>
            <div class="center-stuff">
                <?php et($headerDescription->getAlias()) ?>
                <br>
                <span class="last-login">
                    <?php et2('rg.info.box.last_login_date', ['date' => formatDate($headerDescription)]) ?>
                </span>
            </div>
        <?php else: ?>
            <div class="center-stuff">
                <h3><?php et($header->getHeadline()) ?></h3>
            </div>
            <div class="center-stuff">
                <?php et($headerDescription->getAlias()) ?>
                <br>
                <?php etAssoc(
                    $headerDescription->getLastLoginData()->getAlias(),
                    $headerDescription->getLastLoginData()->getPlaceholders()
                ) ?>
            </div>
        <?php endif ?>
        <?php rgWagerResultSection($u_obj, $winloss_container, $winloss_container_totals, $rg_info_setting) ?>

        <div class="table-prefix center-stuff"><h3><?php et($rg_limits_container->getHeadline()) ?></h3></div>
        <div class="rg-pop-lims-tbl">
            <?php foreach ($rg_limits_container->getLimitTypes() as $limit_types): ?>
                <div class="full-width">
                    <div class="vip-color left">
                        <?php et("rg.info.{$limit_types->getType()}.limits") ?>
                    </div>
                    <div class="right">
                        <?php foreach ($limit_types->getLimits() as $limit): ?>
                            <?php if ($limit_types->getType() !== Settings::LIMIT_BALANCE): ?>
                                <div class="left rg-column">
                                    <?php if ($is_paynplay_mode): ?>
                                        <div class="left">
                                            <?php et("rg.info.{$limit->getTimeSpan()}.limits") ?>
                                            (<?= $rg->displayUnit($limit_types->getType(), $u_obj) ?>)
                                        </div>
                                    <?php else: ?>
                                        <div class="left">
                                            <?php et("rg.info.{$limit->getTimeSpan()}.limits") ?>
                                        </div>
                                        <div class="right">
                                            (<?= $rg->displayUnit($limit_types->getType(), $u_obj) ?>)
                                        </div>
                                    <?php endif ?>
                                    <div>
                                        <?php dbInput(
                                            "resettable-{$limit_types->getType()}-{$limit->getTimeSpan()}",
                                            $limit->getCurrentLimit(),
                                            'text',
                                            "input-normal big-input discreet-border flat-input full-width",
                                            "disabled"
                                        )?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="left rg-column">
                                    <div class="left"><?php et("rg.info.balance.active.limits") ?></div>
                                    <div class="right">
                                        (<?= $rg->displayUnit($limit_types->getType(), $u_obj) ?>)
                                    </div>
                                    <div>
                                        <?php dbInput(
                                            "resettable-balance-active",
                                            $limit->getCurrentLimit(),
                                            'text',
                                            "input-normal big-input discreet-border flat-input full-width",
                                            "disabled"
                                        )?>
                                    </div>
                                </div>
                                <div class="left rg-column">
                                    <div class="left">
                                        <?php et("rg.info.balance.remaining.limits") ?>
                                    </div>
                                    <div class="right">
                                        (<?= $rg->displayUnit($limit_types->getType(), $u_obj) ?>)
                                    </div>
                                    <div>
                                        <?php dbInput(
                                            "resettable-balance-remaining",
                                            $limit->getRemaining(),
                                            'text',
                                            "input-normal big-input discreet-border flat-input full-width",
                                            "disabled"
                                        )?>
                                    </div>
                                </div>
                                <div class="left rg-column"></div>
                            <?php endif; ?>
                        <?php endforeach ?>
                    </div>
                    <br clear="all"/>
                </div>
            <?php endforeach ?>
        </div>
        <br clear="all"/>
        <div class="center-stuff rg-footer">
            <span><?php et($footer->getHeadline()) ?></span>
            <button class="btn btn-l btn-default-l w-300"
                    onclick="goTo('<?= $footer->getButtonData()->getPage(); ?>')">
                <?php et($footer->getButtonData()->getAlias())  ?>
            </button>
        </div>
    </div>
<?php endif ?>

<script>
    $(document).ready(function (){
        if ($('.rg-pop-lims-tbl').children().length == 0) {
            $('.rg-popup-title').hide();
            $('.rg-popup-title.empty').show();
        }
    });

    $(".rg-activity-div").click(function (e) {
        if($(this).data('on_game_page') === 'yes') {
            return;
        }

        e.preventDefault();
        var rg_login_info_callback = '<?= lic('getRedirectBackToLinkAfterRgPopup', [], $u_obj) ?>';
        var rg_activity = '<?= !($header->isForceAccept()) ? 'false' : 'true'; ?>';
        var rg_config = '<?= $rg_config ?>';

        if (rg_activity === 'true') {
            licJson('rgActivityAccepted', { 'config_name': rg_config }, function () {
                window.location.href = rg_login_info_callback;
            });
        } else {
            window.location.href = rg_login_info_callback;
        }
    });

    $('#rg-login-box').on('click', '.lic-mbox-close-box', function() {
        window.location.href = '<?= lic('getRedirectBackToLinkAfterRgPopup', [], $u_obj) ?>';
    });
</script>
