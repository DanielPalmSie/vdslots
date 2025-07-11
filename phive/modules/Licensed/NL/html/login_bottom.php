<br>

<input id="nid-field" autocomplete="username" type="email"
       placeholder="<?php echo phive('UserHandler')->getLoginFirstInput() ?>"
       class="input-normal lic-mbox-input style-email">

<div style="display: none" id="nid-password">
    <div id="nid-field-error" class="error"><?= t('login.idin.instructions.message') ?></div>
    <input id="password-field" autocomplete="password" type="password"
           placeholder="<?= t('registration.password'); ?>"
           class="input-normal lic-mbox-input style-password">
</div>

<div class="lic-mbox-btn lic-mbox-btn-active" onclick="licFuncs.startExternalVerification('login')">
    <div class="<?= phive()->isMobile() ? 'register-big-btn-txt' : '' ?>">
        <?php et('login.with.external.verifier') ?>
        <img src="<?php echo lic('imgUri', ['ext_verify_logo.png']) ?>"/>
    </div>
</div>
