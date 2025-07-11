<?php
$nationalities = lic('getNationalities');
$calling_codes = phive('DBUserHandler')->getCallingCodesForDropdown();
$is_from_deposit = $_POST['from_deposit'] === 'true';
?>

<div class="company-details-popup">
    <form id="company-details-popup-form" class="company-details-popup-form" action="javascript:void(0);">
        <div>
            <p class="company-details-popup-description">
                <?php et($is_from_deposit ? 'company-details-popup.deposit-description' : 'company-details-popup.description') ?>
            </p>

            <div class="company-details-popup-input-wrapper">
            <span class="company-details-popup-input-label">
                <?php et('company-details-popup.citizenship') ?>
            </span>
                <span class="styled-select">
                 <?php dbSelect('citizenship-select', array_merge(['' => t('company-details-popup.citizenship.placeholder')], $nationalities)) ?>
            </span>
                <span id="citizenship-select-error" class="error" style="display: none">
                    <?php et('company-details-popup.error.required') ?>
                </span>
            </div>

            <div class="company-details-popup-input-wrapper">
                <?php dbInput('company-name-input', '', 'text', '', '', true, false, false, t('company-details-popup.company-name.placeholder')) ?>
                <span id="company-name-input-error" class="error" style="display: none">
                    <?php et('company-details-popup.error.required') ?>
                </span>
            </div>

            <div class="company-details-popup-input-wrapper">
                <?php dbInput('company-address-input', '', 'text', '', '', true, false, false, t('company-details-popup.company-address.placeholder')) ?>
                <span id="company-address-input-error" class="error" style="display: none">
                    <?php et('company-details-popup.error.required') ?>
                </span>
            </div>

            <div class="company-details-popup-input-wrapper">
                <label for="company-phone-number-input">
                <span class="company-details-popup-input-label">
                    <?php et('company-details-popup.country-code') ?>
                </span>
                    <span class="css-flex-container">
                    <span id="mobile-prefix-select" class="styled-select company-details-popup-country-prefix">
                    <?php dbSelect('company-country-prefix-input', $calling_codes, '', [], 'company-details-popup-country-prefix-select') ?>
                </span>
                <?php dbInput('company-phone-number-input', '', 'number', 'company-details-popup-phone-number-input', '', true, false, false, t('company-details-popup.company-phone-number.placeholder')) ?>
                </span>
                </label>
                <span id="company-phone-number-input-error" class="error" style="display: none">
                    <?php et('company-details-popup.error.required') ?>
                </span>
                <span id="company-details-popup-error" class="error" style="display: none"></span>
            </div>
        </div>

        <div class="company-details-popup-buttons">
            <button id="company-details-popup-skip" class="company-details-popup-skip-btn btn btn-xl btn-default-xl w-100-pc" >
                <?php et('company-details-popup.skip') ?>
            </button>
            <button id="company-details-popup-submit" class="btn btn-xl btn-default-xl w-100-pc">
                <?php et('company-details-popup.submit') ?>
            </button>
        </div>
    </form>

    <script>
        $(function() {
            $('#company-details-popup-skip').on('click', function() {
                mboxClose('company-details-popup');
            });

            $('#company-details-popup-submit').on('click', function() {
                $('#company-details-popup-error').hide();

                $('#company-details-popup-form').validate({
                    rules: {
                        'citizenship-select': {
                            required: true,
                        },
                        'company-name-input': {
                            required: true,
                        },
                        'company-address-input': {
                            required: true,
                        },
                        'company-phone-number-input': {
                            required: true,
                            number: true,
                        }
                    },
                    highlight: function(element) {
                        showError($(element));
                    },
                    unhighlight: function(element) {
                        hideError($(element))
                    },
                    focusInvalid: false,
                    errorPlacement: function(error, element) {},
                    submitHandler: function() {
                        const data = {
                            citizenship: $('#citizenship-select').val(),
                            company_name: $('#company-name-input').val(),
                            company_address: $('#company-address-input').val(),
                            company_phone_number: '+' + $('#company-country-prefix-input').val() + $('#company-phone-number-input').val(),
                        };

                        saveAccCommon('save_company_info', data, function (res) {
                            if (!res.success) {
                                $('#company-details-popup-error').text(res.msg);
                                $('#company-details-popup-error').show();
                                return;
                            }

                            mboxClose('company-details-popup');
                        });
                    },
                })
            });

            function showError(input) {
                $(`#${input.attr('id')}-error`).show();
            }

            function hideError(input) {
                $(`#${input.attr('id')}-error`).hide();
            }
        });
    </script>
</div>
