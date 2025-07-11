<?php

$box_id = $_POST['box_id'] ?? 'cc_verification_popup';
$user_details = $_POST['user_details'];

?>

<div class="account-verification__inner">

    <form id="account-verification-form" class="account-verification__form" action="javascript:">
        <div class="account-verification__text">
            <?php et('account-verification.enter-code') ?>
        </div>

        <div class="account-verification__info">
            <div class="account-verification__fields">
                <span class="account-verification__field-name">
                    <?php et('account-verification.email') ?>
                </span>
                <span class="account-verification__field-value">
                    <?= $user_details['email'] ?>
                </span>
                <span class="account-verification__field-name">
                    <?php et('account-verification.mobile') ?>
                </span>
                <span class="account-verification__field-value">
                    <?= $user_details['country_prefix'] . ' ' . $user_details['mobile'] ?>
                </span>
            </div>

            <button
                id="change-email-mobile-btn"
                class="account-verification__info-btn"
                type="button"
            >
                <?php et('account-verification.change-email-mobile') ?>
            </button>
        </div>

        <div class="account-verification__code-wrapper field-email_code">
            <label class="account-verification__label" for="account-verification-input">
                <input
                    id="email_code_validation"
                    class="account-verification__input"
                    name="code"
                    type="text"
                    placeholder="<?php et('account-verification.code-placeholder') ?>"
                    autocapitalize="off"
                    autocorrect="off"
                    autocomplete="email"
                />
                <span id="resend-code" class="account-verification__resend-code">
                    <?php et('account-verification.resend-code') ?>
                </span>
            </label>
            <div id="cc-verification-infotext" class="account-validation-message"></div>
        </div>

        <div class="action_btn">
            <button
                class="account-verification__btn btn btn-l btn-default-l lic-mbox-container-flex__button"
                type="submit"
            >
                <?php et('account-verification.validate') ?>
            </button>
        </div>
    </form>
</div>

<script>
    $(function() {
        $('#change-email-mobile-btn').on('click', function() {
            closePopup( 'account_verification-box', false, false);
        });

        $('#resend-code').on('click', function() {
            mgSecureAjax({action: 'send-sms-code'}, function (ret){});
            mgAjax({action: 'send-email-code'}, function (ret){ $("#cc-verification-infotext").html(ret); });
            $(this).hide();
        });

        initAccountVerificationForm(
            '<?= $box_id ?>',
            '<?= $user_details["wish_to_receive_marketing"] ?>' === 'false',
        );
    });
</script>
