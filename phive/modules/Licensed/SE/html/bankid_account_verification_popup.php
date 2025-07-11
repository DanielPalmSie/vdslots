<?php

$box_id = $_POST['box_id'] ?? 'cc_verification_popup';
$user_details = $_POST['user_details'];

?>

<div class="account-verification__inner">

<div class="account-verification__profile-image">
        <img src="/diamondbet/images/<?= brandedCss() ?>login.png" alt="Profile icon">
    </div>

    <p class="account-verification__title"><?php et('account-verification.title') ?></p>

    <div class="account-verification__user-details">
        <p class="account-verification__instructions">
            <?php et('account-verification.instructions') ?>
        </p>
        <div class="account-verification__email">
             <?= et('account-verification.email') ?>: <?= $user_details['email'] ?>
        </div>
        <div class="account-verification__mobile">
        <?= et('account-verification.mobile') ?>: +<?= $user_details['country_prefix'] ?> <?= $user_details['mobile'] ?>
        </div>
    </div>

    <div class="account-verification__change-link">
        <a href="javascript:" id="change-email-mobile-btn"><?php et('account-verification.change-link') ?></a>
    </div>

    <form id="account-verification-form" class="account-verification__form" action="javascript:">

        <div class="account-verification__input-wrapper">
            <input
                type="text"
                id="email_code_validation"
                name="code"
                class="account-verification__input"
                placeholder="<?php et('account-verification.validation-code') ?>"
                maxlength="4"
                autocapitalize="off"
                autocorrect="off"
                autocomplete="email"
            >
        </div>

        <div class="account-verification__bottom-actions">
            <button type="submit" id="submit" class="account-verification__validate-btn">
                <?php et('account-verification.validate') ?>
            </button>

            <div class="account-verification__resend-link">
                <a href="javascript:" id="resend-code-btn"><?php et('account-verification.resend-code') ?></a>
            </div>

            <div id="general_error" class="errors" style="display:none"></div>
            <div id="cc-verification-infotext" class="account-validation-message"></div>
        </div>
    </form>
</div>

<script>
    $(function() {
        $('#change-email-mobile-btn').on('click', function() {
            closePopup('bankid-account-verification-popup', false, false);
            top.registration1.goTo('/registration-step-1/', '_self', false);
        });

        $('#resend-code-btn').on('click', function() {
            mgSecureAjax({action: 'send-sms-code'}, function (ret){});
            mgAjax({action: 'send-email-code'}, function (ret){ $("#cc-verification-infotext").html(ret); });
        });

        if (isMobile()) {
            $('#submit').on('click', function () {
                top.registration1.goTo('/' + cur_lang + '/registration-step-2/', '_self', false);
            });
        }

        initAccountVerificationForm(
            '<?= $box_id ?>',
            true,
        );
    });
</script>
