<script>
    function sendEmailUs() {
        mgJson({
            action: "send-email-us",
            from: $("#from").val(),
            subject: $("#subject").val(),
            message: $("#message").val(),
            captcha: $("#captcha").val()
        },function(ret){
            if(ret.res == 'fail'){
                $("#errorZone").html(ret.error).removeClass("email-success");
            }else{
                $("#errorZone").html(ret.res).addClass("email-success");
                $("#send-email-form").html();
                $("#from").val("");
                $("#subject").val("");
                $("#message").val("");
                $("#captcha").val("");
            }
        });
    }

    function closePopup() {
        gotoLang('/');
    }
</script>
<div id="contact-us-form">
    <div class="contact-us-form__header">
        <h3 class="contact-us-form__header-title"><?php et('contact.us') ?></h3>
        <div class="contact-us-close-box" onclick="closePopup()"><span class="icon icon-vs-close"></span></div>
    </div>
    <div class="contact-us-for__main">
        <div class="contact-us-form">
            <div class="contact-us-form__input-container">
                <?php $user = cu() ?>
                <?php dbInput('from', !empty($user) ? $user->getAttribute('email') : '' , 'email', 'contact-us-form__input', lic('getMaxLengthAttribute', ['email']), true, false, false, t('contactus.email')) ?>
            </div>
            <div class="contact-us-form__input-container">
                <?php dbInput('subject', false, 'text','contact-us-form__input','', true, false, false, t('contactus.subject')) ?>
            </div>
            <div class="contact-us-form__input-container">
                <?php dbInputTextArea('message', false, 'contact-us-form__input phone-us-form__input-question', '', true, t('contactus.message')) ?>
            </div>
            <div class="contact-us-form__input-container">
                <img class="captcha captcha-img" src="<?php echo PhiveValidator::captchaImg() ?>"/>
                <input type="text" name="captcha" id="captcha" value="" placeholder="<?php et('contactus.code') ?>" class="contact-us-form__input captcha captcha-text"/></div>
            <div class="contact-us-form__input-container">
                <?php btnDefaultL(t('submit'), '', 'sendEmailUs()', '100%') ?>
            </div>
            <div>
               <?php echo tAssoc( 'contactus.start.live.chat.html' , ['click_url' => phive('Localizer')->getChatUrl()] ) ?>
            </div>
        </div>
        <div id="errorZone" class="contact-us-form__errors">
        </div>
    </div>
</div>
