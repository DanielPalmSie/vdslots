<?php
    $email = $_POST['email'];
    $mobile = $_POST['mobile'];
?>

<div class="edit-profile-validation-code__popup">
    <div class="edit-profile-validation-code__body">
        <label for="edit-profile-validation-code-popup-input" class="edit-profile-validation-code__label">
            <?= t2('edit-profile.validation-code.description.html', ['email' => $email, 'mobile' => $mobile]) ?>
        </label>
        <div class="edit-profile-validation-code__input-wrapper">
            <input
                id="edit-profile-validation-code-popup-input"
                class="edit-profile-validation-code__input"
                type="number"
                placeholder="<?= t('edit-profile.validation-code.placeholder') ?>"
            >
            <button
                id="edit-profile-validation-code-popup-resend-code"
                class="edit-profile-validation-code__resend btn btn-l btn-default-l w-150"
            >
                <?= t('edit-profile.validation-code.resend-btn') ?>
            </button>
        </div>
        <div id="edit-profile-validation-code-popup-info" class="edit-profile-validation-code__info"></div>
        <div id="edit-profile-validation-code-popup-error" class="edit-profile-validation-code__error error"></div>
    </div>
    <div class="edit-profile-validation-code__action">
        <button
            id="edit-profile-validation-code-popup-submit"
            class="edit-profile-validation-code__submit btn btn-l btn-default-l w-200"
            type="button"
        >
            <?= t('edit-profile.validation-code.submit-btn') ?>
        </button>
    </div>
</div>

<script>
    $(function() {
        const infoSection = $('#edit-profile-validation-code-popup-info');
        const validationCodeError = $('#edit-profile-validation-code-popup-error');

        $('#edit-profile-validation-code-popup-resend-code').on('click', debounce(async function() {
            clearErrorsAndInfo();

            mgSecureAjax({ action: 'send-sms-code', regenerate: 1 });

            const res = await mgAjax({ action: 'send-email-code', regenerate: 1 });
            infoSection.html(res);
        }, 3000, true));

        $('#edit-profile-validation-code-popup-submit').on('click', async function() {
            const validationCodeInput = $('#edit-profile-validation-code-popup-input');
            const form = $('#edit-profile-form');

            const result = await checkValidationCode(validationCodeInput.val());

            clearErrorsAndInfo();

            if (result !== 'ok') {
                validationCodeError.html(result);
                return;
            }

            // add validation code from popup to Edit Profile form
            $('<input />').attr('type', 'hidden')
                .attr('name', 'validation_code')
                .attr('value', validationCodeInput.val())
                .appendTo(form);

            // submit Edit Profile form
            $('<input />').attr('type', 'hidden')
                .attr('name', 'submit_contact_info')
                .attr('value', 'submit_contact_info')
                .appendTo(form);

            form.submit();
        });

        async function checkValidationCode(code) {
            let responses = await Promise.all([
                mgSecureAjax({ action: 'check-email-code', code: code }),
                mgSecureAjax({ action: 'check-sms-code', code: code })
            ]);

            responses = responses.map(response => JSON.parse(response));
            const successfulResponse = responses.filter(response => response.message === 'ok')[0];

            if (!successfulResponse) {
                return responses[0].message;
            }

            return successfulResponse.message;
        }

        function clearErrorsAndInfo() {
            infoSection.html('');
            validationCodeError.html('');
        }
    });
</script>
