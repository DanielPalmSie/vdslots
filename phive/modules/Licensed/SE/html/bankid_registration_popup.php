<?php
?>

<div class="registration-container">
  <div class="registration-content">
    <div class="registration-content-left">
      <img src="/diamondbet/images/<?= brandedCss() ?>welcometodbet.png" alt="<?php echo t("bankid.registration.form.title"); ?>" class="welcome-logo">
    </div>

    <div class="registration-content-right">
      <p class="registration-info-txt">
        <?php echo t("bankid.registration.infotext"); ?>
      </p>

      <form id="register_form">
        <div id="email_holder">
          <label for="email">
            <input id="email" class="input-normal required email" name="email" type="email" autocapitalize="off" autocorrect="off" autocomplete="email" placeholder='<?= t('register.email.nostar'); ?>' value="" />
          </label>
          <div id="email_msg" class="info-message" style="display:none;"><?= t('register.email.error.message') ?></div>
          <div id="email_taken_msg" class="info-message taken-error" style="display:none;"><?= t('register.email.already.registered') ?></div>
        </div>

        <div id="mobile-container">
          <label for="mobile">
          <span id="mobile-prefix-select" class="styled-select">
            <select id="country_prefix" name="country_prefix">
              <option value="46">46</option>
            </select>
          </span>
          <input id="mobile" class="input-normal mobileLength" name="mobile" type="tel" autocomplete="tel" placeholder="<?= t('register.mobile.nostar') ?>" value=""/>
          </label>
          <span id="mobile_msg" class="info-message" style="display:none;">
            <?php et('bankid.registration.invalid-mobile-number') ?>
          </span>
        </div>

        <div class="checkbox-container">
          <div class="privacy-check">
            <label for="privacy">
              <input class="registration-checkbox required" id="privacy" name="privacy" type="checkbox"/>
              <span id="privacy-span">
                <?php echo t('register.privacy1') ?>
                <a href="#" onclick="window.open('<?php echo llink( phive('Config')->getValue('registration', 'privacy-policy-link') ) ?> ','sense','width=740,scrollbars=yes,resizable=yes');">
                    <?php echo t('register.privacy2') ?>
                </a>
              </span>
            </label>
            <span id="privacy_msg" class="error" style="display:none;"></span>
          </div>

          <div class="terms-check">
            <label class="registration-tc-checkbox" for="terms">
              <input class="registration-checkbox required" id="terms" name="conditions" type="checkbox"/>

              <span id="terms-span">
                <?php echo t('register.toc1') ?>
                <a href="#" onclick="window.open('<?php echo llink( phive('Config')->getValue('registration', 'tco_link') ) ?>','sense','width=740,scrollbars=yes,resizable=yes');">
                    <?php echo t('register.toc2') ?>
                </a>
              </span>
            </label>
            <span id="conditions_msg" class="error" style="display:none;"></span>
          </div>

          <div class="age-check">
            <label for="eighteen">
              <input id="eighteen" class="required" type="checkbox" name="eighteen" />
              <span id="eighteen-span">
                <?php echo t('bankid.registration.iam18orolder'); ?>
              </span>
            </label>
            <span id="eighteen_msg" class="error" style="display:none;"></span>
          </div>
        </div>

        <div id="errorZone" class="errors"></div>

        <div class="register-button" id="register_button">
          <span><?php et('continue') ?></span>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
  addFormForValidation('#register_form');

  addPostCheck('#register_form', '#email', 'focus', 'check_email', '<?php echo t("register.email.taken"); ?>', '<?php echo t("register.toomanyattempts"); ?>', '<?php echo t("register.email.already.registered"); ?>');

  addPostCheckForMobile('#register_form', '#mobile', 'focus', 'check_mobile', '<?php echo t("register.mobile.taken"); ?>', '#country_prefix', '<?php echo t("register.toomanyattempts"); ?>');

  showRegistrationBox(registration_step1_url);
  top.$.multibox('hide', 'registration-box');
  $('#multibox-overlay-registration-box').remove();

  $('#register_form :input[type="text"], #register_form :input[type="email"], #register_form :input[type="tel"]').blur(function() {
    validate('#register_form', this);
  });

  $('#register_form :input[type="checkbox"]').click(function() {
    validate('#register_form', this);
  });

  $('#register_button').click(function(e) {
      e.preventDefault();
      if (!$('#register_form').valid()) {

        if ($('#email').val() === '<?php echo t("register.email.taken"); ?>') {
          $('#email_msg').hide();
          $('#email_taken_msg').show();
        }
      
          return;
      }

      let bank_id_data = JSON.parse('<?= $_POST['bank_id_data']; ?>');

      showLoader(function () {
          const stepOneData = {
              country: 'SE',
              country_prefix: $('#country_prefix').val(),
              csrf_token: window.top.registration1.$('#csrf_token').val(),
              email: $('#email').val(),
              mobile: $('#mobile').val(),
              privacy: Number($('#privacy').prop('checked')),
              conditions: Number($('#terms').prop('checked')),
              age_check: Number($('#eighteen').prop('checked')),
              personal_number: bank_id_data.nid,
              cur_req_id: bank_id_data.req_id
          };
          Registration.submitStep1(stepOneData);
      }, true);
  });
});
</script>
