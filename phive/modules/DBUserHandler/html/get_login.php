<?php

use Videoslots\User\LoginFields\Factory\LoginFieldsFactory;
use Videoslots\User\ThirdPartyVerificationFields\Factory\TopPartFactory;

require_once __DIR__ . '/../../../modules/DBUserHandler/Registration/RegistrationHtml.php';
loadCSS("/diamondbet/fonts/icons.css");


phive('Licensed')->forceCountry($_REQUEST['country']);

$mbox       = new MboxCommon();
$context   = empty($_POST['context']) ? 'login' : $_POST['context'];
$err       = $_POST['error'];
$top_logos = phive()->isMobile() ? lic('topMobileLogos') : false;
$box_id    = empty($_POST['box_id']) ? 'login-box' : $_POST['box_id'];

$login_fields_data = (new LoginFieldsFactory())->getFields($box_id, $context);

$maintenance_data = $login_fields_data->getMaintenanceData();
$is_maintenance_mode = $maintenance_data->isEnabled();
$maintenance_message = null;

$header = $login_fields_data->getTopPartData()->getBoxHeadlineAlias();
$login_default_data = $login_fields_data->getLoginDefaultData();

if ($is_maintenance_mode) {
    $maintenance_message = t2($maintenance_data->getAlias(), [
        'start_time' => $maintenance_data->getFrom(),
        'end_time' => $maintenance_data->getTo()
    ]);
}

$allow_close_redirection = isset($_POST['allow_close_redirection']) ? ($_POST['allow_close_redirection'] == 'true') : true;
$is_auth_allowed = phive('DBUserHandler')->isRegistrationAndLoginAllowed();

if (in_array($context, [RegistrationHtml::CONTEXT_REGISTRATION, RegistrationHtml::CONTEXT_REGISTRATION_MITID])) {
    trackRegistration();

    return RegistrationHtml::intermediaryStep(
        $context,
        $box_id,
        $_REQUEST['country']
    );
}

if ($context === 'login' && empty(phive()->isMobile())) {
    generateFingerprint(true);
}

?>

<?php if (!empty($top_logos)): ?>
    <script>
        licFuncs.setTopMobileLogos(true)
    </script>
    <div class="top-logos gradient-normal">
        <?= lic('rgOverAge', ['over-age-mobile logged-in-time']); ?>
        <?= lic('rgLoginTime', ['rg-top__item logged-in-time']); ?>
        <?php echo $top_logos ?>
    </div>
    <br clear="all" />
<?php endif ?>

<div class="lic-mbox-wrapper version2">
    <?php
    $top_part_data = (new TopPartFactory())->create($box_id, $header, false, $allow_close_redirection);
    $mbox->topPart($top_part_data);
    ?>
    <div class="login-popup">
        <div class="lic-mbox-container minimal-padding country-<?= phive('Licensed')->getLicCountry() ?>">

            <div class="login-popup__top-section">
                <?php
                if (!useOldDesign()): ?>
                    <img src="/diamondbet/images/<?= brandedCss() ?>login.png" alt="login-image">
                <?php endif; ?>
            </div>

            <?php if ($login_fields_data->hasCustomLoginTopFields()): ?>
                <div id="lic-mbox-login-custom-top" style="display: none;">
                    <?php lic('customLoginTop', [$context]) ?>
                </div>

                <div id="lic-mbox-login-separator" style="display: none;">
                    <?php lic('loginSeparator', [$context]) ?>
                </div>

                <div id="lic-mbox-login-custom-info" style="display: none;">
                    <?php lic('customLoginInfo', [$context]) ?>
                </div>
            <?php endif ?>

            <div id="lic-mbox-login-default" class="registration-container">
                <label for="email">
                    <div class='field-container'>
                        <?php $user_name_field = $login_default_data->getUsernameFieldData() ?>
                        <input id="lic-login-username-field"
                            autocomplete="username"
                            type="<?= $user_name_field->getInputType() ?>"
                            value="<?= $_POST['login_username'] ?>"
                            <?= $_POST['disabled'] ? "disabled" : "" ?>
                            placeholder="<?= t($user_name_field->getPlaceholder()) ?>"
                            class="input-normal lic-mbox-input"
                            <?php
                            if ($user_name_field->getInputType() === 'email') {
                                echo lic('getMaxLengthAttribute', ['email']);
                            }
                            ?>>
                        <div class='input-icon'></div>
                    </div>
                </label>

                <label for="password">
                    <div class='field-container'>
                        <?php $password_field = $login_default_data->getPasswordFieldData() ?>
                        <input id="lic-login-password-field"
                            autocomplete="current-password"
                            type="<?= $password_field->getInputType() ?>"
                            placeholder="<?php et($password_field->getPlaceholder()) ?>"
                            class="input-normal lic-mbox-input">
                        <div class='input-icon'></div>
                    </div>
                </label>

                <?php $captcha_field = $login_default_data->getLoginCaptchaData() ?>
                <div class="clearfix" id="lic-mbox-login-captcha">
                    <img id="captcha_img" src="" />
                    <label for="login-captcha">
                        <p><?php et($captcha_field->getAlias()) ?></p>
                        <input id="login-captcha" type="text" class="input-normal lic-mbox-input">
                    </label>
                </div>

                <?php licHtml('extra_login_inputs') ?>
                <?php $submit_field = $login_default_data->getSubmitFieldData() ?>
                <div class="flex-center flex-gap">
                    <div class="lic-mbox-btn lic-mbox-btn-active" id="lic-mbox-login-captcha-reset" onclick="licFuncs.resetCaptcha()">
                        <span><?php et('reset.code') ?></span>
                    </div>
                    <?php if ($is_auth_allowed): ?>
                        <div onclick="doLogin()" id="login-btn" class="lic-mbox-btn <?php echo $submit_field->isDisabled() ? 'lic-mbox-btn-inactive btn-disabled' : 'lic-mbox-btn-active' ?>">
                            <span><?php et($submit_field->getAlias()) ?></span>
                        </div>
                    <?php else: ?>
                        <div onclick="#" id="login-btn" class="lic-mbox-btn lic-mbox-btn-inactive btn-disabled">
                            <span><?php et($submit_field->getAlias()) ?></span>
                        </div>
                    <?php endif ?>
                </div>
                <br clear="all" />
                <?php $forgot_password_field = $login_default_data->getForgotPasswordData() ?>
                <div class="login-popup__terms-link-wrapper">
                    <a href="<?php echo $forgot_password_field->getUrl() ?>" class="login-popup__terms-link">
                        <?php et($forgot_password_field->getAlias()) ?>
                    </a>
                </div>
            </div>

            <div id="lic-mbox-login-otp" class="registration-container" style="display: none;">
                <div id="otp-header" class="otp-header"><?php et('otp.popup.top.html') ?></div>
                <div id="otp-description" class="otp-description"></div>

                <label id="otp-label" for="otp">
                    <span class="icon icon-security"></span>
                    <input id="lic-login-otp-field" autocomplete="one-time-code" type="tel" placeholder="<?php et('login.otp') ?>" class="input-normal lic-mbox-input">
                </label>

                <?php $captcha_field = $login_default_data->getLoginCaptchaData() ?>
                <div class="clearfix" id="lic-mbox-login-otp-captcha" style="display: none;">
                    <img id="captcha_img_otp" src=""/>
                    <label for="login-captcha">
                        <p><?php et($captcha_field->getAlias()) ?></p>
                        <input id="login-otp-captcha" type="text" class="input-normal lic-mbox-input">
                    </label>
                </div>

                <div class="flex-center flex-gap">
                    <div class="lic-mbox-btn lic-mbox-btn-active" id="lic-mbox-login-otp-captcha-reset" onclick="licFuncs.resetCaptcha('otp')" style="display: none">
                        <span><?php et('reset.code') ?></span>
                    </div>

                    <div class="lic-mbox-btn lic-mbox-btn-active" onclick="doOtpLogin()">
                        <span><?php et('login') ?></span>
                    </div>
                </div>

                <br clear="all" />
                <div id="otp-bottom-link" class="black-link">
                    <a href="#/" onclick="parent.<?php echo phive('Localizer')->getChatUrl()  ?>">
                        <?php et('not.received.otp') ?>
                    </a>
                </div>
            </div>

            <div id="lic-mbox-login-reset-password" class="registration-container" style="display: none;">
                <?php moduleHtml('DBUserHandler', 'reset_password_on_login', false, null) ?>
            </div>

            <?php if (lic('ipVerificationOnLogin')) { ?>
                <div id="lic-mbox-login-ipverification" class="registration-container geo_comply_wrapper" style="display: none;">

                    <div id="ipverification-loader" popup-title="<? et('verification.in.progress') ?>" style="display: none">
                        <?php moduleHtml('GeoComply', 'checking_locate_popup', false, null) ?>
                    </div>
                    <div id="ipverification-install" popup-title="<? et('install.geocomply') ?>" style="display: none">
                        <?php moduleHtml('GeoComply', 'install_popup', false, null) ?>
                    </div>
                    <div id="ipverification-open" popup-title="<? et('open.geocomply') ?>" style="display: none">
                        <?php moduleHtml('GeoComply', 'open_app_popup', false, null) ?>
                    </div>
                    <div id="ipverification-locate" popup-title="<? et('verify.location') ?>" class="flex-1" style="display: none">
                        <?php moduleHtml('GeoComply', 'locate_popup', false, null) ?>
                    </div>
                    <div id="ipverification-error" popup-title="<? et('connection.error') ?>" class="flex-1" style="display: none">
                        <?php moduleHtml('GeoComply', 'location_connection_error_popup', false, null) ?>
                    </div>
                    <div id="ipverification-success" popup-title="<? et('successful.verification') ?>" class="flex-1" style="display: none">
                        <?php moduleHtml('GeoComply', 'location_success_popup', false, null) ?>
                    </div>
                    <div id="ipverification-checking" popup-title="<? et('verification.in.progress') ?>" style="display: none">
                        <?php moduleHtml('GeoComply', 'checking_locate_popup', false, null) ?>
                    </div>
                    <div id="ipverification-failed" popup-title="<? et('verification.failed') ?>" class="flex-1" style="display: none"></div>

                    <div id="ipverification-troublershooter" popup-title="<? et('error.details') ?>" class="flex flex-1 overflow-scroll flex-column" style="display: none"></div>
                    <br clear="all" />
                </div>
            <?php } ?>

            <div id="lic-mbox-login-custom" style="display: none;">
                <?php lic('customLoginBottom', [$context]) ?>
            </div>
        </div>
        <?php if ($is_maintenance_mode && !is_null($maintenance_message)): ?>
            <div id="lic-login-errors"  class="error"><?php echo $maintenance_message ?></div>
        <?php elseif (!$is_auth_allowed): ?>
            <div id="lic-login-errors" class="error"><?php echo t('blocked.access.popup.desc') ?></div>
        <?php elseif (!empty($err)): ?>
            <div id="lic-login-errors" class="error"><?php echo t($err) ?></div>
        <?php else: ?>
            <div id="lic-login-errors"></div>
        <?php endif ?>
    </div>
</div>

<script>
    <?php
    if (lic('methodExists', ['customLoginJs'])) {
        lic('customLoginJs', [$context]);
    }
    ?>
    // Login on enter
    $("#lic-login-username-field, #lic-login-password-field, #login-captcha").keydown(function(e) {
        if (e.keyCode == 13 && !($("#login-btn").hasClass('btn-disabled'))) {
            doLogin();
        }
    });

    <?php
    if (!lic('ipVerificationOnLogin')) {
    ?>
        $(document).mousedown(hideMultiboxOnOutsideClick.bind(event, '<?= $box_id ?>'));
    <?php
    }
    ?>
</script>

<?php
if (phive()->isMobile()) {
    include __DIR__ . '/../../../../diamondbet/html/chat-support.php';
}
?>
