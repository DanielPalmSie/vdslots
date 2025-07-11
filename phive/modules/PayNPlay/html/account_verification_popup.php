<?php

$box_id = $_POST['box_id'] ?? 'user_details_popup';
$user_details = $_POST['user_details'];

?>

<div class="pnp-account-verification__inner">

    <form id="pnp-account-verification-form" class="pnp-account-verification__form" action="javascript:">
        <div class="pnp-account-verification__text">
            <?php et('paynplay.account-verification.enter-code') ?>
        </div>

        <div class="pnp-account-verification__info">
            <div class="pnp-account-verification__fields">
                <span class="pnp-account-verification__field-name">
                    <?php et('paynplay.account-verification.email') ?>
                </span>
                <span class="pnp-account-verification__field-value">
                    <?= $user_details['email'] ?>
                </span>
                <span class="pnp-account-verification__field-name">
                    <?php et('paynplay.account-verification.mobile') ?>
                </span>
                <span class="pnp-account-verification__field-value">
                    <?= $user_details['country_prefix'] . ' ' . $user_details['mobile'] ?>
                </span>
            </div>

            <button
                id="pnp-change-email-mobile-btn"
                class="pnp-account-verification__info-btn"
                type="button"
            >
                <?php et('paynplay.account-verification.change-email-mobile') ?>
            </button>
        </div>

        <div class="pnp-account-verification__code-wrapper field-email_code">
            <label class="pnp-account-verification__label" for="pnp-account-verification-input">
                <input
                    id="email_code"
                    class="pnp-account-verification__input"
                    name="code"
                    type="text"
                    placeholder="<?php et('paynplay.account-verification.code-placeholder') ?>"
                    autocapitalize="off"
                    autocorrect="off"
                    autocomplete="email"
                />
                <span id="pnp-resend-code" class="pnp-account-verification__resend-code">
                    <?php et('paynplay.account-verification.resend-code') ?>
                </span>
                <span
                    id="pnp-account-verification-input-message"
                    class="pnp-validation-message info-message"
                    style="display: none"
                >
                    <?php et('paynplay.account-verification.invalid-code') ?>
                </span>
            </label>
            <div id="infotext" class="errors"></div>
        </div>

        <div class="action_btn">
            <button
                class="pnp-account-verification__btn btn btn-l btn-default-l lic-mbox-container-flex__button"
                type="submit"
            >
                <?php et('paynplay.account-verification.validate') ?>
            </button>
        </div>
    </form>
</div>

<script>
    $(function() {
        $('#pnp-change-email-mobile-btn').on('click', function() {
            PayNPlay.navigateBackToUserDetails(
                '<?= $box_id ?>',
                <?= $user_details["wish_to_receive_marketing"] ?>
            );
        });

        $('#pnp-resend-code').on('click', function() {
            PayNPlay.resendVerificationCode.call(this);
        });

        PayNPlay.initAccountVerificationForm(
            '<?= $box_id ?>',
            '<?= $user_details["wish_to_receive_marketing"] ?>' === 'false',
        );
    });
</script>
