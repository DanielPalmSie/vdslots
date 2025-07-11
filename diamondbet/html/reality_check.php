<?
require_once __DIR__ . '/../../phive/phive.php';
require_once __DIR__ . '/RealityCheckButtons.php';
//$lang = phive('Localizer')->getCurNonSubLang();
$isMobile =  phive()->isMobile();

loadCss("/diamondbet/css/" . brandedCss() . "all.css");
loadCss("/diamondbet/css/" . brandedCss() . "reality_checks.css");

if ($isMobile) {
    loadCss("/diamondbet/css/" . brandedCss() . "mobile.css");
}

$user = cu();
$rcmsg = '';
$close_setting_popup_class = "";
$isSetting = !empty($_POST['isSetting']);

if (!empty($_POST['lang'])) {
    $lang = $_POST['lang'];
    phive('Localizer')->setLanguage($_POST['lang'], true);
}

$rc_data = lic('getRealityCheck', [$user, $lang, $_POST['ext_game_name']], $user);
if ($isSetting) {
    $rc_configs = lic('getRcConfigs', [], $user);
    $close_setting_popup_class = "rc-setting-close";
} elseif (!$isSetting) {
    $rcmsg = $rc_data['message'];
    $reality_check_buttons = new RealityCheckButtons($rc_data['buttons']);
    $reality_check_buttons->printJs();
}
?>
<script>
    reality_checks_js.gref = "<?php echo $_POST['ext_game_name'] ?>";
</script>

<?php lic('getCustomRcStyle') ?>

<div id="reality-check-popup" class="rc-wrapper">

    <div class="rc__top-part">
        <div class="rc__top-part--title"><?= $rc_data['header'] ?></div>
        <?php if ($rc_data['closeButton'] ?? true): ?>
        <div class="rc__top-part--close icon icon-vs-close <?= $close_setting_popup_class?>"></div>
        <?php endif; ?>
    </div>

    <div class="rc__body">
        <div class="rc__body-container clear-fix">
            <?php if (isset($rc_data['title'])): ?>
                <div class="rc__body--title"><?= $rc_data['title'] ?></div>
            <? endif; ?>
            <div class="rc__body--text"><?= $rcmsg ?: t('reality-check.msg.set', $lang) ?></div>
        </div>
    </div>

    <div class="rc__actions">
        <?php if ($isSetting): ?>
            <div id="extendedBy" class="rc__actions--container">
                <div class="reality-check-btn left"><span>−</span></div>
                <input type="tel" id="reality-check-interval" value="<?= $rc_configs['rc_default_interval'] ?>" min="<?= $rc_configs['rc_min_interval'] ?>" max="<?= $rc_configs['rc_max_interval'] ?>" readonly>
                <div class="reality-check-btn right"><span>+</span></div>
            </div>
            <div id="dialogRcSetButton" class="button-rc dialogWindowDualButton" onclick="reality_checks_js.validateAndSet()">
                <?= t('reality-check.label.set') ?>
            </div>
        <?php else: ?>
            <div class="rc__actions--buttons">
                <? $reality_check_buttons->printButtons($_POST['in_game']); ?>
            </div>
        <?php endif ?>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $('body').addClass('disable-scroll');

        <?php if ($isMobile && lic('topMobileLogos')): ?>
        $('#multibox-overlay-rc-msg').addClass('hidden-force');
        $('#reality-check-popup, #multibox-overlay-rc-msg, .rc__actions').addClass('has-logos');
        <?php endif ?>
    });
    $('.rc__top-part--close.rc-setting-close').click(function () {
        mgAjax({action: 'refused-reality-check'}, function (ret) {});
    });
    $('.rc__top-part--close, .button-rc').click(function () {
        $('body').removeClass('disable-scroll');
        if (typeof doOnClickContinueButton != 'undefined' && typeof doOnClickContinueButton == 'function') {
            doOnClickContinueButton();
        } else {
            mboxClose('rc-msg');
        }
    });
</script>