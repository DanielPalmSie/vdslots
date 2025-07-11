<?php

require_once __DIR__ . '/../BoxHandler/boxes/diamondbet/CashierWithdrawBoxBase.php';

class AddAccountService
{
    public function handleTrustlyBankAdditionalFields(string $psp) { ?>
        <link rel="stylesheet" type="text/css" href="/phive/admin/plugins/select2/css/select2.css" />
        <script src="/phive/admin/plugins/select2/js/select2.full.min.js"></script>

        <span id="addBankAccountFields"></span>

        <script>
            let accountSelect;

            $(document).ready(function () {
                accountSelect = $('#account_select');

                accountSelect.find('option').each(function () {
                    if ($(this).val().startsWith('account_') || $(this).data('closed_loop_cents') === -1) {
                        $(this).attr('data-disable_message', 'true');
                    }
                });

                accountSelect.select2({
                    dropdownCssClass: 'bankAccountsDropdown',
                    minimumResultsForSearch: Infinity,
                    templateResult: function (data) {
                        return $('<span>')
                            .text(data.text)
                            .attr('data-option-id', data.id);
                    }
                }).next('.select2-container').addClass('cashierSelect cashierSelect2');

                // Append new buttons as the option
                accountSelect.append('<option value="select_account"><?php et("select.account.btn"); ?></option>');
                //accountSelect.append('<option value="add_account"><?php et("add.bank.account.btn"); ?></option>');

                setDefaultOption();

                accountSelect.on('select2:select', function (e) {
                    const selectedDropDownOption = e.params.data;
                    const selectedOptionId = selectedDropDownOption.id;
                    const originalOption = $(accountSelect.find(`option[value="${selectedOptionId}"]`));

                    const isDisabled = originalOption.is('[data-disable_message]');
                    if (isDisabled) {
                        mboxClose('mbox-msg', () =>
                            showGeneralInfoPopup(
                                'closed.loop.invalid.account.selection',
                                false,
                                {
                                    account: `<strong>(${originalOption.data('display_name')})</strong>`,
                                    validAccounts: getValidBankAccountOptions()
                                },
                                'invalid-account-selected-popup'
                            )
                        );

                        // This is purely for UI adjustment. If a scrollbar is present, it will eliminate
                        // unnecessary space. This cannot be achieved with CSS alone.
                        new MutationObserver((mutations, obs) => {
                            const el = document.querySelector('.valid-account-details');
                            if (el) {
                                el.classList.toggle('scrollbar-overflow-adjustment', el.scrollHeight - el.clientHeight > 5);
                                obs.disconnect();
                            }
                        }).observe(document.body, {childList: true, subtree: true});

                        setDefaultOption();
                    } else {
                        handleBankAccountSelectAction(selectedOptionId);
                    }
                });

                // Add class to the btn options when dropdown opens
                accountSelect.on('select2:open', function () {
                    const $dropdown = accountSelect.data('select2').$dropdown;

                    requestAnimationFrame(() => {
                        disableCustomOptions($dropdown);
                        styleButtonOptions($dropdown);
                    });
                });
            });

            function setDefaultOption() {
                const defaultOption = accountSelect.find('option')
                    .not('[data-disable_message]')
                    .first()
                    .val() || 'select_account';

                accountSelect.val(defaultOption).trigger('change');
                handleBankAccountSelectAction(defaultOption);
            }

            function disableCustomOptions($dropdown) {
                $dropdown.find('.select2-results__option').each(function () {
                    // dropdownOption: Represents a displayed option in the Select2 dropdown.
                    // originalOption: Represents the original <select> element, holding data attributes.
                    const dropdownOption = $(this);
                    const optionId = dropdownOption.find('[data-option-id]').data('option-id');
                    const originalOption = accountSelect.find(`option[value="${optionId}"]`);

                    if (originalOption.is('[data-disable_message]')) {
                        dropdownOption.addClass('custom-disabled-option').val('');
                    }
                });
            }

            function styleButtonOptions($dropdown) {
                const validIds = ['add_account', 'select_account'];
                $dropdown.find('.select2-results__option')
                    .filter(function () {
                        const optionId = $(this).find('[data-option-id]').data('option-id');
                        return validIds.includes(optionId);
                    })
                    .filter(':not(.btn-default-l)')
                    .addClass('btn-default-l dropdown-option-btn');
            }

            function handleBankAccountSelectAction(selectedValue) {
                const $withdrawForm = $('#withdrawForm-trustly');
                const $addBankAccountFields = $('#addBankAccountFields');
                const $amountInputs = $withdrawForm.find('input[name="amount"], .amount-label, .error');

                if (selectedValue === 'select_account') {
                    $addBankAccountFields.html('<input type="hidden" name="supplier" value="<?php echo $psp; ?>">');
                    $withdrawForm.attr('data-custom-action', 'bankSelectAccount');
                    $amountInputs.hide();
                } else if (selectedValue === 'add_account') {
                    $withdrawForm.attr('data-custom-action', 'handleAddBankAccount');
                    $.post("/phive/modules/Cashier/html/add_bank_account.php", {action: 'printAdditionalFields'}, function (response) {
                        $addBankAccountFields.html(response);
                        $amountInputs.hide();
                    });
                } else {
                    $withdrawForm.removeAttr('data-custom-action');
                    $addBankAccountFields.html('');
                    $amountInputs.show();
                }
            }

            function getValidBankAccountOptions() {
                const options = accountSelect.find('option')
                    .not('[data-disable_message], [value="select_account"]')
                    .map((_, option) => {
                        const $option = $(option);
                        const displayName = $option.data('display_name');
                        const currency = $option.data('user_currency');
                        const amount = $option.data('closed_loop_formatted');

                        return `
                          <div class="valid-account-option">
                            <div class="valid-account-option-context">
                              <span>${displayName}</span>
                              <span>${currency} ${amount}</span>
                            </div>

                            <button onclick="selectAlternativeBankAccountOption('${option.value}')">
                              <?php et("closed.loop.select.alternative.account.btn"); ?>
                            </button>
                          </div>
                        `;
                    }).get().join('');

                return options.trim() ? `<div class="valid-account-details">${options}</div>` : `<br><br>`;
            }

            function selectAlternativeBankAccountOption(selectedValue) {
                accountSelect.val(selectedValue).trigger('change');
                handleBankAccountSelectAction(selectedValue);
                handleClose(); // Close current popup after selection
            }
        </script>
        <?php
    }

    public function printTrustlyBankAdditionalFields() { ?>
        <div class="cashierInputLabel"><?php et("add.bank.account.section.title"); ?></div>
        <br><br>

        <?php (new CashierWithdrawBoxBase())->printWithdrawBankAccountInput('bank.countrycode', 'clearing_house', $this->getRegisterAccountCountriesForDropdown(), true); ?>

        <span id="dynamicBankFields"></span>

        <script>
            $(document).ready(function () {
                const bankCountrySelect = $('#clearing_house');
                const withdrawForm = $('#withdrawForm-trustly');

                bankCountrySelect.prepend('<option value="" selected disabled hidden></option>');
                bankCountrySelect.select2({
                    placeholder: "Select <?php echo str_replace(":", "", t("bank.countrycode")); ?>",
                    allowClear: false
                }).next('.select2-container').addClass('cashierSelect cashierSelect2');

                bankCountrySelect.on('select2:select', function (e) {
                    $.post("/phive/modules/Cashier/html/add_bank_account.php", {
                        bankCountry: bankCountrySelect.val(),
                        action: 'getBankAccountDetails'
                    }, function (response) {
                        const dynamicFieldsContainer = document.getElementById('dynamicBankFields');

                        dynamicFieldsContainer.innerHTML = '';

                        const fields = [
                            {
                                key: 'banknumber',
                                label: "<?php et('bank.number'); ?>:"
                            },
                            {
                                key: 'accountnumber',
                                label: response.banknumber && response.accountnumber
                                    ? "<?php et('bank.bank_account_number'); ?>"
                                    : "<?php et('iban'); ?>:"
                            }
                        ];

                        fields.forEach(({key, label}) => {
                            if (response[key]) {
                                const data = response[key];
                                const pattern = data.pattern;
                                const placeholder = data.placeholder;
                                const labelElement = document.createElement('div');
                                labelElement.className = 'cashierInputLabel';
                                labelElement.textContent = label;

                                const inputField = createInput(key.replace('number', '_number'), pattern, placeholder);
                                dynamicFieldsContainer.append(labelElement, inputField);

                                updateValidationRules(withdrawForm, {
                                    [inputField.name]: {
                                        required: true,
                                        regex: pattern
                                    }
                                }, {
                                    [inputField.name]: {
                                        required: '<?php et('cashier.error.required') ?>',
                                        regex: '<?php et('cashier.error.invalid.format') ?>',
                                    }
                                });
                            }
                        });

                        // Reinitialize form validation after updating rules
                        withdrawForm.validate().settings.ignore = ":hidden";
                        withdrawForm.validate().resetForm();  // Reset to apply new rules
                    }, "json");
                });
            });

            function createInput(name, pattern, placeholder) {
                const input = document.createElement('input');
                input.type = 'text';
                input.name = name;
                input.className = 'cashierInput';
                input.required = true;
                input.title = placeholder;
                input.pattern = pattern;
                input.placeholder = placeholder;
                return input;
            }

            function updateValidationRules($form, newRules, newMessages) {
                let formValidationData = $form.data('validation');

                if (!formValidationData) {
                    formValidationData = {
                        rules: {},
                        messages: {}
                    };
                }

                // Merge new rules and messages with the form's validation data
                formValidationData.rules = {...formValidationData.rules, ...newRules};
                formValidationData.messages = {...formValidationData.messages, ...newMessages};

                // Set updated validation data back to the form
                $form.attr('data-validation', JSON.stringify(formValidationData));

                // Update the jQuery Validation rules on the form
                $form.validate().settings.rules = formValidationData.rules;
                $form.validate().settings.messages = formValidationData.messages;
            }
        </script>

        <?php
    }

    public function getRegisterAccountCountriesForDropdown(): array
    {
        $countryNames = array_keys($this->getAccountNumberInfo());

        $displayCountryNames = array_map(
            fn($countryName) => ucwords(str_replace('_', ' ', $countryName)),
            $countryNames
        );

        return array_combine($countryNames, $displayCountryNames);
    }

    public function addBankAccount(array $data)
    {
        $supplier = 'trustly';

        $data['clearing_house'] = strtoupper($data['clearing_house']);

        $documentData = [
            'add_bank_account_request_data' => $data,
            'supplier' => $supplier,
        ];

        phive('Dmapi')->createEmptyDocument(
            cuPl()->getId(),
            $supplier,
            '',
            $data['account_number'],
            '',
            0,
            $documentData
        );

        return json_encode(['success' => true]);
    }

    public function getAccountNumberInfo(string $country = ''): array
    {
        $bankPatterns = [
            "austria" => [
                "accountnumber" => [
                    "pattern" => "^AT[0-9]{18}$",
                    "placeholder" => "e.g., AT123456789012345678"
                ],
                "banknumber" => null,
            ],
            "belgium" => [
                "accountnumber" => [
                    "pattern" => "^BE[0-9]{14}$",
                    "placeholder" => "e.g., BE12345678901234"
                ],
                "banknumber" => null,
            ],
            "bulgaria" => [
                "accountnumber" => [
                    "pattern" => "^BG[0-9]{2}[A-Z]{4}[0-9]{4}[0-9]{2}[A-Z0-9]{8}$",
                    "placeholder" => "e.g., BG12AAAA12345678"
                ],
                "banknumber" => null,
            ],
            "croatia" => [
                "accountnumber" => [
                    "pattern" => "^HR[0-9]{2}[0-9]{7}[0-9]{10}$",
                    "placeholder" => "e.g., HR12 1234 1234 1234 1234 5"
                ],
                "banknumber" => null,
            ],
            "cyprus" => [
                "accountnumber" => [
                    "pattern" => "^CY[0-9]{10}[0-9A-Z]{16}$",
                    "placeholder" => "e.g., CY12345678901234567890"
                ],
                "banknumber" => null,
            ],
            "czech_republic" => [
                "accountnumber" => [
                    "pattern" => "^CZ[0-9]{22}$",
                    "placeholder" => "e.g., CZ1234567890123456789012"
                ],
                "banknumber" => null,
            ],
            "denmark" => [
                "accountnumber" => [
                    "pattern" => "^DK[0-9]{16}$",
                    "placeholder" => "e.g., DK1234567890123456"
                ],
                "banknumber" => null,
            ],
            "estonia" => [
                "accountnumber" => [
                    "pattern" => "^EE[0-9]{18}$",
                    "placeholder" => "e.g., EE123456789012345678"
                ],
                "banknumber" => null,
            ],
            "finland" => [
                "accountnumber" => [
                    "pattern" => "^FI[0-9]{16}$",
                    "placeholder" => "e.g., FI1234567890123456"
                ],
                "banknumber" => null,
            ],
            "france" => [
                "accountnumber" => [
                    "pattern" => "^FR[0-9]{12}[0-9A-Z]{11}[0-9]{2}$",
                    "placeholder" => "e.g., FR12345678901234AB123456"
                ],
                "banknumber" => null,
            ],
            "germany" => [
                "accountnumber" => [
                    "pattern" => "^DE[0-9]{20}$",
                    "placeholder" => "e.g., DE12345678901234567890"
                ],
                "banknumber" => null,
            ],
            "greece" => [
                "accountnumber" => [
                    "pattern" => "^GR[0-9]{25}$",
                    "placeholder" => "e.g., GR1234567890123456789012345"
                ],
                "banknumber" => null,
            ],
            "hungary" => [
                "accountnumber" => [
                    "pattern" => "^HU[0-9]{26}$",
                    "placeholder" => "e.g., HU123456789012345678901234"
                ],
                "banknumber" => null,
            ],
            "ireland" => [
                "accountnumber" => [
                    "pattern" => "^IE[0-9]{2}[A-Z]{4}[0-9]{14}$",
                    "placeholder" => "e.g., IE12ABCD12345678901234"
                ],
                "banknumber" => null,
            ],
            "italy" => [
                "accountnumber" => [
                    "pattern" => "^IT[0-9]{2}[A-Z][0-9]{10}[0-9A-Z]{12}$",
                    "placeholder" => "e.g., IT12A123456789012345678901"
                ],
                "banknumber" => null,
            ],
            "latvia" => [
                "accountnumber" => [
                    "pattern" => "^LV[0-9]{2}[A-Z]{4}[0-9A-Z]{13}$",
                    "placeholder" => "e.g., LV12AAAA1234567"
                ],
                "banknumber" => null,
            ],
            "lithuania" => [
                "accountnumber" => [
                    "pattern" => "^LT[0-9]{18}$",
                    "placeholder" => "e.g., LT123456789012345678"
                ],
                "banknumber" => null,
            ],
            "luxembourg" => [
                "accountnumber" => [
                    "pattern" => "^LU[0-9]{18}$",
                    "placeholder" => "e.g., LU123456789012345678"
                ],
                "banknumber" => null,
            ],
            "malta" => [
                "accountnumber" => [
                    "pattern" => "^MT[0-9]{2}[A-Z]{4}[0-9]{5}[0-9A-Z]{18}$",
                    "placeholder" => "e.g., MT12ABCDE12345XYZ123456789012"
                ],
                "banknumber" => null,
            ],
            "netherlands" => [
                "accountnumber" => [
                    "pattern" => "^NL[0-9]{2}[A-Z]{4}[0-9]{10}$",
                    "placeholder" => "e.g., NL12ABCD1234567890"
                ],
                "banknumber" => null,
            ],
            "norway" => [
                "accountnumber" => [
                    "pattern" => "^NO[0-9]{13}$",
                    "placeholder" => "e.g., NO12345678901"
                ],
                "banknumber" => null,
            ],
            "poland" => [
                "accountnumber" => [
                    "pattern" => "^PL[0-9]{26}$",
                    "placeholder" => "e.g., PL12345678901234567890123456"
                ],
                "banknumber" => null,
            ],
            "portugal" => [
                "accountnumber" => [
                    "pattern" => "^PT[0-9]{23}$",
                    "placeholder" => "e.g., PT123456789012345678901"
                ],
                "banknumber" => null,
            ],
            "romania" => [
                "accountnumber" => [
                    "pattern" => "^RO[0-9]{2}[A-Z]{4}[0-9A-Z]{16}$",
                    "placeholder" => "e.g., RO12ABCD12345678901234"
                ],
                "banknumber" => null,
            ],
            "slovakia" => [
                "accountnumber" => [
                    "pattern" => "^SK[0-9]{22}$",
                    "placeholder" => "e.g., SK1234567890123456789012"
                ],
                "banknumber" => null,
            ],
            "slovenia" => [
                "accountnumber" => [
                    "pattern" => "^SI56[0-9]{15}$",
                    "placeholder" => "e.g., SI56123456789012345"
                ],
                "banknumber" => null,
            ],
            "spain" => [
                "accountnumber" => [
                    "pattern" => "^ES[0-9]{22}$",
                    "placeholder" => "e.g., ES12345678901234567890"
                ],
                "banknumber" => null,
            ],
            "sweden" => [
                "accountnumber" => [
                    "pattern" => "^[0-9]{1,15}$",
                    "placeholder" => "e.g., 1 or 123456789012345"
                ],
                "banknumber" => [
                    "pattern" => "^[0-9]{4,5}$",
                    "placeholder" => "e.g., 1234 or 12345"
                ],
            ],
            "united_kingdom" => [
                "accountnumber" => [
                    "pattern" => "^[0-9]{8}$",
                    "placeholder" => "e.g., 12345678"
                ],
                "banknumber" => [
                    "pattern" => "^[0-9]{6}$",
                    "placeholder" => "e.g., 123456"
                ],
            ],
        ];

        if (!empty($country)) {
            return $bankPatterns[$country];
        }

        return $bankPatterns;
    }
}
