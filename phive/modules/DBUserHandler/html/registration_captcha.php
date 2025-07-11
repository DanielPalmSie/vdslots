<?php
require_once __DIR__ . '/../../../phive.php';

$captchaVersion = phive()->getSetting('captcha_version');
$showCoolOffTimer = phive('DBUserHandler')->getSetting('show_cool_off_timer_in_captcha_popup');

function getCaptchaMaxLength() {
    return phive()->getSetting('captcha-config')['max_length'] ?? 5;
}

function renderCaptchaImage() {
    ?>
    <img id="captcha_img" src="<?= PhiveValidator::captchaImg('', true) ?>"/>
    <?php
}

function renderCaptchaInput($captchaVersion) {
    $placeholder = $captchaVersion ? t('registration.popup.captcha.placeholder') : '';
    ?>
    <input id="registration_captcha_input" class="input-normal" name="registration_captcha"
        type="text" autocapitalize="off" autocorrect="off"
        autocomplete="off" placeholder="<?php echo htmlspecialchars($placeholder, ENT_QUOTES, 'UTF-8'); ?>"
        maxlength="<?php echo getCaptchaMaxLength() ?>"/>
    <?php
}
?>

<div class="registration-captcha-popup">
    <div>
        <form action="javascript:" onsubmit="return validateRegistrationCaptcha()" autocomplete="off">
            <div>
                <div id="lic-mbox-registration-captcha">
                    <?php if ($captchaVersion) : ?>
                        <img src="/diamondbet/images/<?= brandedCss() ?>king_captcha.png" />
                        <label for="registration_captcha">
                            <div id="registration_captcha_msg" class="info-message">
                                <?php et('registration.popup.captcha.label') ?>
                            </div>
                            <?php renderCaptchaImage(); ?><br />
                            <div class="input-reset-container">
                                <?php renderCaptchaInput($captchaVersion); ?>
                                <button id="reset-captcha-button" type="button" onclick="licFuncs.resetCaptcha()">
                                    <span><?php et('registration.popup.captcha.reset') ?></span>
                                </button>
                            </div>
                        </label>
                    <?php else : ?>
                        <div class="clearfix">
                            <?php renderCaptchaImage(); ?>
                            <label for="registration_captcha">
                                <div id="registration_captcha_msg" class="info-message">
                                    <?php et('registration.popup.captcha.label') ?>
                                </div>
                                <?php renderCaptchaInput($captchaVersion); ?>
                            </label>
                        </div>
                    <?php endif; ?>
                    <div class="error-msg hidden error"></div>
                    <?php if ($showCoolOffTimer) : ?>
                        <div class="timer-wrapper hidden">
                            <?php et('registration.captcha.cool.off') ?>:
                            <img src="/diamondbet/images/Clock.svg" class="time-icon">
                            <span class="timer-section"></span>
                            <span class="minutes-section"><?php et('minutes') ?></span>
                            <span class="seconds-section hidden"><?php et('seconds') ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <?php if (!$captchaVersion) : ?>
                    <div class="flex-center flex-gap">
                        <button
                            class="btn btn-l positive-action-btn"
                            id="reset-captcha-button"
                            type="button"
                            onclick="licFuncs.resetCaptcha()"
                        >
                            <span><?php et("reset.code") ?></span>
                        </button>
                    <?php endif; ?>
                    <button
                        class="btn btn-l positive-action-btn"
                        id="confirm-captcha-button"
                        type="submit"
                    >
                        <span><?php et('confirm.title') ?></span>
                    </button>
                    <?php if (!$captchaVersion) : ?>
                    </div>
                    <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<script>
    $(function () {

        $(document).on('keyup', '#registration_captcha_input', function () {
            if($('.timer-wrapper').hasClass('hidden')) {
                $('.error-msg').addClass('hidden');
            }
        })

        $('.mboxloader-outer').css('background-image', 'none');

    })

    function validateRegistrationCaptcha() {
        const captcha = $('#registration_captcha_input').val();

        if (captcha != '') {
            mgAjax({
                action: 'validate_registration_captcha',
                captcha_code: captcha
            }, function (response) {

                if (!response) {
                    return;
                }

                if (response.status === 'success') {
                    mboxClose(undefined, function () {
                        submitStep1(<?= json_encode($_REQUEST["additional_fields"], JSON_HEX_QUOT|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS) ?>);
                    });
                    return;
                }

                if (response.error_type === 'timeout') {
                    $('#confirm-captcha-button').prop('disabled', true).addClass('disable_captcha_btn');

                    setTimeout(function () {
                        $('#confirm-captcha-button').removeAttr('disabled').removeClass('disable_captcha_btn');
                        $('.error-msg').text('');
                        $('.timer-wrapper').addClass('hidden');
                    }, parseInt(response.timeout, 10) * 1000);

                    var coolOffInMinutes = Math.floor(parseInt(response.timeout, 10) / 60);
                    var coolOffInSeconds = parseInt(response.timeout, 10);

                    $('.timer-wrapper').removeClass('hidden');

                    if(coolOffInMinutes <= 1) {
                        showSecondsTimer(coolOffInSeconds);
                    }

                    if(coolOffInMinutes > 1) {
                        showMinutesTimer(coolOffInMinutes, coolOffInSeconds);
                    }
                }

                $('.error-msg').removeClass('hidden').text(response.message);

            });
        }

        function showMinutesTimer(coolOffInMinutes, coolOffInSeconds)
        {
            $('.minutes-section').removeClass('hidden');
            $('.seconds-section').addClass('hidden');

            $('.timer-section').text(coolOffInMinutes)

            const minutesTimer = setInterval(function () {
                coolOffInMinutes --;
                coolOffInSeconds -= 60;
                $('.timer-section').text(coolOffInMinutes)

                if(coolOffInMinutes <= 1) {
                    showSecondsTimer(coolOffInSeconds);
                    clearInterval(minutesTimer);
                }
            }, 1000 * 60);
        }

        function showSecondsTimer(coolOffInSeconds)
        {
            $('.minutes-section').addClass('hidden');
            $('.seconds-section').removeClass('hidden');

            $('.timer-section').text(coolOffInSeconds)

            const secondsTimer = setInterval(function () {
                coolOffInSeconds--;
                $('.timer-section').text(coolOffInSeconds)

                if(coolOffInSeconds < 0) {
                    clearInterval(secondsTimer);
                }
            }, 1000);
        }
    }
</script>
