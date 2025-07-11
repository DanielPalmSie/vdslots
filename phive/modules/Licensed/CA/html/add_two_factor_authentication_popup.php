<div class="lic-mbox-container authentication-popup">
    <img class="" src="<?php echo lic('imgUri', ['two-factor-authentication.png']) ?>"/>
    <div class="two-factor-authentication">
        <p>
            <?php et('two.factor.authentication.description') ?>
        </p>
        <input placeholder="<?= t('enter.code') ?>"
               class="new-standard-input authentication-input" type="text"
               name=""
               id=""
               maxlength="4"
               required'
        />
        <br />
        <p class="error hidden auth-error"><?php et('auth.error.empty') ?></p>
    </div>
    <div class="authentication-footer">
        <button class="btn btn-l btn-default-l" onclick="validateAuthCode()"><?php et('validate') ?></button>
        <button id="resend_code" class="btn btn-l btn-default-l resend-btn" onclick="sendNewCode()"><?php et('resend.code') ?></button>
    </div>
</div>
<script>

    function validateAuthCode (){
        const input = $('.authentication-input').val();


        if(input != ''){
            mgAjax({
                action: 'validate_authentication_code',
                auth_code: input
            }, function (response) {
                console.log(response);
                if (!response) {
                    return;
                }
                if (response.status === 'success') {
                    mboxClose('get_html_popup');
                }

                if (response.status === 'error') {
                    $('.auth-error').removeClass('hidden').text(response.message);
                }

            });
        }else if(input == ''){
            $('.auth-error').removeClass('hidden');
            return;
        }
        $('.auth-error').addClass('hidden')
    }

    function closePopup(){
        fbCloseLogOut()
    }


    function sendNewCode(){
        mgAjax({action: 'get_2fa_code'});
    }



</script>
