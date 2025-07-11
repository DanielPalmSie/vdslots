<?php

$box_id = $_POST['box_id'] ?? 'pnp-user_details_popup';
$wish_to_receive_marketing = $_POST['wish_to_receive_marketing'] ?? false;

$user = cu();

$email = $user->data['email'];
$mobile = $user->data['mobile'];
$country_prefix = phive('Cashier')->phoneFromIso($user->data['country']);

$mobile_without_prefix = substr($mobile, strlen($country_prefix));

if (str_starts_with($email, "pnp.")) {
    $mobile_without_prefix = '';
    $email = '';
}

$nationalities  = lic('getNationalities');

?>

<div class="pnp-user-details__inner">
    <div class="pnp-user-details__image-wrapper">
        <img
            class="pnp-user-details__image"
            src="/diamondbet/images/<?= brandedCss() ?><?= phive()->isMobile() ? '/mobile' : ''?>/pay-n-play/user-details-main.png"
            alt="user details image"
        />
    </div>

    <form id="pnp-user-details-form" class="pnp-user-details__form" action="javascript:">
        <div class="pnp-user-details__details">
            <div class="pnp-user-details__details-text">
                <?php et('paynplay.user-details.more-details') ?>
            </div>

            <div class="pnp-user-details__email-wrapper">
                <label
                    class="pnp-user-details__label pnp-user-details__label--email"
                    for="pnp-user-details-email-input"
                >
                    <input
                        id="email"
                        class="pnp-user-details__input pnp-user-details__input--email"
                        name="email"
                        type="email"
                        placeholder="<?php et('paynplay.user-details.email-placeholder') ?>"
                        autocapitalize="off"
                        autocorrect="off"
                        autocomplete="email"
                        value="<?= $email ?>"
                    />
                    <span
                        id="email_msg"
                        class="pnp-validation-message"
                        style="display: none"
                    >
                                <?php et('paynplay.user-details.invalid-email') ?>
                            </span>
                </label>
            </div>

            <div class="pnp-user-details__phone-wrapper">
                <label for="pnp-user-details-country-prefix-input" class=pnp-user-details__label">
                    <input
                        id="pnp-user-details-country-prefix-input"
                        class="pnp-user-details__input pnp-user-details__input--country-prefix"
                        name="country_prefix"
                        type="text"
                        disabled
                        value="<?= $country_prefix ?>"
                    />
                </label>
                <label
                    for="pnp-user-details-phone-input"
                    class="pnp-user-details__label pnp-user-details__label--phone"
                >
                    <input
                        id="mobile"
                        class="pnp-user-details__input pnp-user-details__input--phone"
                        name="phone"
                        type="tel"
                        placeholder="<?php et('paynplay.user-details.phone-placeholder') ?>"
                        autocomplete="tel"
                        value="<?= $mobile_without_prefix ?>"
                    />
                    <span
                        id="mobile_msg"
                        class="pnp-validation-message"
                        style="display: none"
                    >
                                <?php et('paynplay.user-details.invalid-phone') ?>
                            </span>
                </label>
            </div>

            <div class="pnp-user-details__country-wrapper">
                <label
                    class="pnp-user-details__label pnp-user-details__label--country"
                    for="pnp-user-details-country-input"
                >
                    <span class="styled-select">
                        <?php dbSelect('nationality',
                            array_merge(['' => t('nationality.default.select.option')], $nationalities),
                            '', array(), 'pnp-user-details__input pnp-user-details__input--country') ?>
                    </span>
                    <span
                        id="nationality_msg"
                        class="pnp-validation-message"
                        style="display: none"
                    >
                                <?php et('paynplay.user-details.invalid-country') ?>
                            </span>
                </label>
            </div>

            <div class="action_btn">
                <button
                    class="pnp-user-details__btn btn btn-l btn-default-l lic-mbox-container-flex__button"
                    type="submit"
                >
                    <?php et('paynplay.user-details.continue') ?>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    $(function() {
        PayNPlay.initUserDetailsForm('<?= $box_id ?>');
    });
</script>
