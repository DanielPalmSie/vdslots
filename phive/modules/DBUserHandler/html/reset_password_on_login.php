<?php
?>

<span>
    <?= t('login.reset-password.description') ?>
</span>

<label>
    <input
        id="new-password-field"
        type="password"
        placeholder="<?= t('login.reset-password.password-input.placeholder') ?>"
        class="input-normal lic-mbox-input style-password"
    >
</label>

<label>
    <input
        id="new-password-field-confirmation"
        type="password"
        placeholder="<?= t('login.reset-password.password-confirmation-input.placeholder') ?>"
        class="input-normal lic-mbox-input style-password"
    >
</label>

<div class="lic-mbox-btn lic-mbox-btn-active" onclick="doResetPasswordLogin()">
    <span><?= t('login.reset-password.btn') ?></span>
</div>

<br>
