<?php
require_once __DIR__ . '/../../../phive.php';
require_once __DIR__.'/../../../html/display_base_diamondbet.php';

if(!empty($_GET['lang'])){
  phive('Localizer')->setLanguage($_GET['lang']);
  phive('Localizer')->setNonSubLang($_GET['lang']);
}

class Registration {

  public static function printOnlyJava($step)
  {
    $iso = $_REQUEST['iso'];
    if(empty($iso)) {
        $user_during_registration = cuRegistration();
        $iso = !empty($user_during_registration) ? $user_during_registration->getCountry() : null;
    }
      ?>
    addFormForValidation('#validation_step1');
    addFormForValidation('#validation_step2');

    <?php if(lic('triggerValidation')): ?>
        initialValidationPreCheck('#validation_step2');
    <?php endif ?>

    <?php if(phive('UserHandler')->getSetting('full_registration') === true): ?>
        doubleCheckStrings('#validation_step1','#secemail','#email',"<?php echo t('register.not.the.same'); ?>");
        doubleCheckStrings('#validation_step1','#secpassword','#password',"");
    <?php endif ?>

    addPostCheck('#validation_step1','#email','focus.addPostCheck','check_email','<?php echo t('register.email.taken'); ?>','<?php echo t('register.toomanyattempts'); ?>', '<?php echo t('register.email.already.registered'); ?>');

    addPostCheckForMobile('#validation_step1','#mobile','focus.addPostCheck','check_mobile','<?php echo t('register.mobile.taken'); ?>','#country_prefix','<?php echo t('register.toomanyattempts'); ?>');

    jQuery(document).ready(function(){
      <?php if($step == 2): ?>
        if(typeof regStep2PrePops != 'undefined'){
          $("#password").val(regStep2PrePops.password);
          $("#email").val(regStep2PrePops.email);
        }
      <?php elseif($step == 1): ?>
        if(typeof regStep1PrePops != 'undefined'){
          $("#password").val(regStep1PrePops.password);
          $("#email").val(regStep1PrePops.email);
        }
        showHidePassword();
        handleRegistrationFields(<?php echo json_encode(phive('DBUserHandler')->getRegistrationData($iso)) ?>);
      <?php endif ?>
        $("form").attr("autocomplete", "on");
    });
  <?php }

  public static function printJava($step){
  ?>
    <script type="text/javascript">

    <?php self::printOnlyJava($step) ?>

    </script>

  <?php
  }

  public static function printHtml($step = 1){
    $uh = phive('UserHandler');

    $width = array(1 => 400, 2 => 870, 3 => 380, 4 => 380);

    $step_map = array(1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four');

    $step_name = "step{$step_map[$step]}";

    Registration::printJava($step);

    ?>
      <!-- lightbox -->
      <div id="lightbox-content" class="lightbox-content" style="width:<?php echo empty($width[$step]) ? "400" : $width[$step] ?>px;">
        <form id="validation_step<?php echo $step?>" method="get" action="">
          <?php Registration::$step_name(); ?>
        </form>
      </div>

    <?php
  }

    public static function stepOne()
    {
        loadJs("/phive/js/jquery.cookie.js");

        $country = !empty($_SESSION['rstep1']['country']) ? $_SESSION['rstep1']['country'] : phive('IpBlock')->getCountry();

        // check if Netent games are allowed for users from this country
        $fspin = phive('Config')->getByTagValues('freespins');
        $can_do = function($key) use ($country, $fspin){
            return in_array(strtolower($country), explode(',', strtolower($fspin[$key])));
        };
        $can_netent = $can_do('netent-reg-bonus-countries');

        $disabled_countries = !empty($_SESSION['rstep2']) ? 'disabled' : '';
        $countries = array_diff_key(
            phive('Cashier')->displayBankCountries(phive('Cashier')->getBankCountries('', true), [], !phive()->isMobile()),
            phive('Config')->valAsArray('countries', 'block')
        );
        ?>
        <script>
            sCookie('afStatus', 'open');

            jQuery(document).ready(function(){
                hideLoader();
                resizeRegistrationbox(1);
            });
        </script>

        <div class="registration-content-left">
            <!-- step one content -->
            <div id="step1" class="step1">

                <!-- register info -->
                <div class="registration-info-txt">
                    <h3><?php echo t("register.step1.infoheader"); ?></h3>
                    <p><?php echo t("register.step1.infotext"); ?></p>
                </div>

                <!-- personal number -->
                <div id="personal_number_input" class="registration-step-1-control">
                    <label for="personal_number">
                        <input id="personal_number" class="input-normal" type="text" autocapitalize="off" autocorrect="off" placeholder='<?= t('register.personal_number.nostar'); ?>' value="<?= $_SESSION['rstep1']['personal_number']; ?>"/>
                        <div id="personal_number_msg" class="info-message" style="display:none">
                            <?= empty($msg = lic('personalNumberMessage')) ? t('register.personal_number.error.message') : $msg; ?>
                        </div>
                    </label>
                </div>

                <!-- email -->
                <div id="email_holder">
                    <label for="email">
                        <input id="email" class="input-normal required email" name="email" type="email" autocapitalize="off" autocorrect="off" autocomplete="email" placeholder='<?= t('register.email.nostar'); ?>' value="<?= $_SESSION['rstep1']['email']; ?>" />
                        <!--<div id="email_msg" class="info-message" style="display:none"><?php // echo t('register.email.error.message'); ?></div>-->
                    </label>
                </div>

                <!-- second email -->
                <div id="secemail_holder" class="registration-step-1-control">
                    <label for="secemail">
                        <input id="secemail" class="input-normal required email" name="secemail" type="email" autocapitalize="off" autocorrect="off" autocomplete="email" placeholder='<?= t('register.email2.nostar'); ?>' value="<?= $_SESSION['rstep1']['secemail']; ?>" />
                    </label>
                </div>

                <!-- password -->
                <div id="password_holder" class="registration-step-1-control">
                    <label for="password">
                        <input id="password" class="input-normal hasUpper hasLower hasTwoDigits" name="confirm_password" type="password" autocomplete="new-password" placeholder='<?= t('register.password.nostar'); ?>' value="<?= $_SESSION['rstep1']['password']; ?>" passwordrules="minlength: 8; required: lower; required: upper; required: [0]; required: [9];"/>
                        <a href="#" class="password-field-icon<?= phive()->isMobile() ? '-mobile' : ''?>">
                            <img src="/diamondbet/images/<?= brandedCss() ?>registration-icons/eye-open.png">
                            <img src="/diamondbet/images/<?= brandedCss() ?>registration-icons/eye-close.png" style="display: none">
                        </a>
                        <div id="password_msg" class="info-message" style="display:none"><?= t('register.password.error.message'); ?></div>
                    </label>
                </div>

                <!-- secpassword -->
                <div id="secpassword_holder" class="registration-step-1-control">
                    <label for="secpassword">
                        <input id="secpassword" class="input-normal" name="secpassword" type="password" autocomplete="new-password" placeholder='<?= t('register.secpassword.nostar'); ?>' value="<?= $_SESSION['rstep1']['password']; ?>" passwordrules="minlength: 8; required: lower; required: upper; required: [0]; required: [9];"/>
                    </label>
                </div>

                <!-- country name -->
                <label for="country">
                    <span class="styled-select styled-select-valid">
                        <?php dbSelect("country", $countries, $country, ['', t('choose.country')], 'valid', false, $disabled_countries) ?>
                    </span>
                </label>

                <!-- mobile name -->
                <div id="mobile-container">
                    <label for="mobile">
                        <input id="country_prefix" class="input-normal country_prefix" name="country_prefix" type="text"/>
                        <input id="mobile" class="input-normal mobileLength" name="mobile" type="tel" autocomplete="tel" placeholder="<?= t('register.mobile.nostar') ?>" value="<?= $_SESSION['rstep1']['mobile']; ?>"/>
                    </label>
                </div>

                <div style="clear:both;height:10px"></div>

                <!-- referring_friend -->
                <div id="referring_friend_info_holder" class="registration-step-1-control">
                    <label for="username"><?= t('register.refer.friend'); ?>
                        <input name="referring_friend" id="referring_friend" value="<?= htmlspecialchars($_POST['referring_friend']) ?>" class="input-normal" type="text" style="float:left"/>
                        <input id="referring_friend_info" type="hidden" value="<?= t("register.step1.refer.info.html") ?>"/>
                    </label>
                </div>

                <!-- accept privacy -->
                <label for="privacy">
                    <input class="registration-checkbox" id="privacy" name="check" type="checkbox" style="clear:left;" <?php if(!empty($_SESSION['rstep1']['privacy'])) { echo 'checked="checked"'; } ?> />
                    <span id="privacy-span">
                        <?php echo t('register.privacy1') ?>
                        <a href="#" onclick="window.open('<?php echo llink( phive('Config')->getValue('registration', 'privacy-policy-link') ) ?> ','sense','width=740,scrollbars=yes,resizable=yes');">
                            <?php echo t('register.privacy2') ?>
                        </a>
                    </span>
                </label>

                <!-- accept terms -->
                <br>
                <label class="registration-tc-checkbox" for="terms">
                    <input class="registration-checkbox" id="terms" name="check" type="checkbox" style="clear:left;" <?php if(!empty($_SESSION['rstep1']['conditions'])) { echo 'checked="checked"'; } ?> />
                    <span id="terms-span">
                        <?php echo t('register.toc1') ?>
                        <a href="#" onclick="window.open('<?php echo llink( phive('Config')->getValue('registration', 'tco_link') ) ?>','sense','width=740,scrollbars=yes,resizable=yes');">
                            <?php echo t('register.toc2') ?>
                        </a>
                    </span>
                </label>

                <div style="display:none">
                    <input id="step" value="1" type="hidden" />
                </div>

                <div id="submit_step_1">
                    <div class="<?= phive()->isMobile() ? 'register-big-btn-txt' : ''?>"></div>
                </div>
            </div>
        </div>

        <?php if(!phive()->isMobile() && substr_count($_SERVER['REQUEST_URI'], '/mobile/register') === 0): ?>
        <?php
        $freespins_all_image_alias = '';
        $freespins_exceptions_image_alias = '';
        $freecash_image_alias = '';
        $welcomebonus_image_alias = '';

        // check if we have a bonus code, and if so get the image aliases
        if(!empty(phive('Bonuses')->getBonusCode())) {
            $freespins_all_image_alias          = phive('ImageHandler')->getImageAliasForBonusCode('banner.registration.freespins.all.');
            $freespins_exceptions_image_alias   = phive('ImageHandler')->getImageAliasForBonusCode('banner.registration.freespins.exceptions.');
            $freecash_image_alias               = phive('ImageHandler')->getImageAliasForBonusCode('banner.registration.freecash.');
            $welcomebonus_image_alias           = phive('ImageHandler')->getImageAliasForBonusCode('banner.registration.welcomebonus.');
        }

        ?>
        <div class="registration-content-right">
            <h3><?php et('register.welcome.bonus.header'); ?></h3>
            <div class="registration-banner-container">
                <?php // show Free Spins banner ?>
                <div class="registration-banner">
                    <?php
                    if (true) { //TODO refactor this, netent condition was removed
                        img($freespins_all_image_alias, 362, 180, 'banner.registration.freespins.all.default');
                    } else {
                        img($freespins_exceptions_image_alias, 362, 180, 'banner.registration.freespins.exceptions.default');
                    }
                    self::echoOverlayLink('freespins');
                    ?>
                </div>
                <?php // show Free Cash banner ?>
                <div class="registration-banner">
                    <?php
                    img($freecash_image_alias, 362, 180, 'banner.registration.freecash.default');
                    self::echoOverlayLink('free_cash');
                    ?>
                </div>
                <?php if(phive('UserHandler')->getSetting('full_registration') === true): ?>
                    <div class="registration-banner">
                        <?php
                        img($welcomebonus_image_alias, 362, 180, 'banner.registration.welcomebonus.default');
                        self::echoOverlayLink('welcome_bonus');
                        ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php
    }

    public static function stepTwo()
    {
        $fc 			= new FormerCommon();

        // the user was saved in step 1, so we can get the user object from the database
        // we either have the user_id in a GET param, or we have it in the session
        if(!empty($_SESSION['rstep1']['user_id'])) {
            $user_id = $_SESSION['rstep1']['user_id'];
        } elseif(!empty($_GET['uid'])) {
            $user_id = $_GET['uid'];
        } else {
            // cannot get the user object
        }

        $user = cu($user_id);

        $forced_currency = lic('getForcedCurrency', [], $user);
        if (empty($forced_currency)) {
            $preselect_currency = empty($_SESSION['rstep2']['currency']) ? getCur() : $_SESSION['rstep2']['currency'];
        } else {
            $preselect_currency = $forced_currency;
        }



        if(empty($user)){
            // TODO handle this in a prettier way.
            die('timed out');
        }

        $excluded_languages = lic('getExcludedRegistrationLanguages', [], $user);
        $cur_langs 		= phive("Localizer")->getLangSelect("WHERE selectable = 1", $excluded_languages);

        phive('Licensed')->forceCountry($user->getCountry());
        ?>
        <script>
            function sendEmailCode(func){
                mgAjax({action: 'send-email-code'}, func);
            }

            function sendSMSCode(func) {
                mgSecureAjax({action: 'send-sms-code'}, func);
            }

            // change iframe height for step 2
            parent.$.multibox('resize', 'registration-box', null, 830, false, true);

            jQuery(document).ready(function(){
                hideLoader();

                resizeRegistrationbox(2);

                $("#close-registration-box").click(function(){ goTo('/?signout=true'); });
                // mgAjax({action: "load-stat", loc: "reg-step2"});

                $("#resend_code").click(function(){
                    $(this).hide();
                    sendSMSCode(function(ret){
                        $("#infotext").html(ret);
                    });
                    sendEmailCode(function(ret){
                        $("#infotext").html(ret);
                    });
                });

            });

        </script>

        <div id="step2" class="step2">
            <input id="country-step2" type="hidden" value="<?= $user->getCountry() ?>">
            <?php moduleHtml('DBUserHandler', 'privacyConfirmationPopupFields');?>
            <div class="registration-content-left">
                <div class="regstep2-left">

                    <!-- register info -->
                    <div class="registration-info-txt">
                        <p><?php echo t("register.step2.infotext"); ?></p>
                    </div>

                    <!-- first name -->
                    <label for="firstname">
                        <input id="firstname" class="input-normal input-normal-reg" name="minlen" type="text" autocorrect="off" autofocus
                               placeholder='<?php echo t('register.firstname.nostar'); ?>'
                            <?= lic('shouldDisableInput', ['firstname']) ? 'disabled' : '' ?>
                               autocomplete="given-name" value="<?php echo $_SESSION['rstep2']['firstname']; ?>"
                        />
                    </label>

                    <!-- last name -->
                    <label for="lastname">
                        <input id="lastname" class="input-normal" name="minlen" type="text" autocorrect="off"
                               placeholder='<?php echo t('register.lastname.nostar'); ?>'
                                <?= lic('shouldDisableInput', ['lastname']) ? 'disabled' : '' ?>
                               autocomplete="family-name" value="<?php echo $_SESSION['rstep2']['lastname']; ?>"
                        />
                    </label>

                    <!-- address name -->
                    <label for="address">
                        <input id="address" class="input-normal" name="minlen" type="text" autocapitalize="off"
                               autocorrect="off" placeholder='<?php echo t('register.address.nostar'); ?>'
                                <?= lic('shouldDisableInput', ['address']) ? 'disabled' : '' ?>
                               autocomplete="street-address"  value="<?php echo $_SESSION['rstep2']['address']; ?>"
                        />
                    </label>

                    <!-- zipcode name -->
                    <label for="zipcode">
                        <input id="zipcode" class="input-normal" name="minlen" type="text" autocapitalize="off"
                               autocorrect="off" placeholder='<?php echo t('register.zipcode.nostar'); ?>'
                               <?= lic('shouldDisableInput', ['zipcode']) ? 'disabled' : '' ?>
                               autocomplete="postal-code"  value="<?php echo $_SESSION['rstep2']['zipcode']; ?>"
                        />
                    </label>

                    <!-- city name -->
                    <label for="city">
                        <input id="city" class="input-normal" name="minlen" type="text" autocorrect="off"
                               placeholder='<?php echo t('register.city.nostar'); ?>'
                               <?= lic('shouldDisableInput', ['city']) ? 'disabled' : '' ?>
                               autocomplete="address-level2" value="<?php echo $_SESSION['rstep2']['city']; ?>"
                        />
                    </label>

                    <?php
                    $forced_language = lic('getForcedLanguage', [], $user);

                    if (!empty($forced_language)) {
                        $preselect_language = $forced_language;
                    } else {
                        if(empty($_POST['preferred_lang'])) {
                            $preselect_language = phive('Localizer')->getLanguage();
                        } elseif(!empty($_SESSION['rstep2']['preferred_lang'])) {
                            $preselect_language = $_SESSION['rstep2']['preferred_lang'];
                        } else {
                            $preselect_language = $_POST['preferred_lang'];
                        }
                    }
                    $disabled_language = lic('shouldDisableInput', ['preferred_lang']) || !empty($forced_language);

                    ?>

                    <?php if(phive()->isMobile()): ?>
                        <div id="language-currency">
                            <!-- pref lang -->
                            <!-- preselect language based on current website language-->
                            <label for="preferred_lang">
                                <span class="styled-select">
                                    <?php dbSelect("preferred_lang", $cur_langs, $preselect_language, array('', t('register.chooselang.nostar')), '', false, '', true, $disabled_language) ?>
                                </span>
                            </label>

                            <!-- currency -->
                            <?php if(phive("Currencer")->getSetting('multi_currency') == true): ?>
                                <label for="currency">
                                    <span class="styled-select currency">
                                        <?php cisosSelect(true, $preselect_currency, 'currency', 'site_input', array(), false, false, false, !empty($forced_currency)) ?>
                                    </span>
                                </label>
                            <?php endif ?>
                        </div>
                    <?php else: ?>
                        <!-- pref lang -->
                        <label for="preferred_lang">
                            <span class="styled-select">
                                <?php dbSelect("preferred_lang", $cur_langs, $preselect_language, array('', t('register.chooselang.nostar')), '', false, '', true, $disabled_language) ?>
                            </span>
                        </label>
                    <?php endif; ?>

                    <!-- bonuscode -->
                    <?php
                    $bonus_code = phive('Bonuses')->getBonusCode();
                    if(!empty($_SESSION['rstep2']['bonus_code'])) {
                        $bonus_code = $_SESSION['rstep2']['bonus_code'];
                    }
                    ?>
                    <?php if(!empty($bonus_code)): ?>
                        <div id="bonus_code_text" style="display:none">
                            <p><?php echo t('register.click.bonus.code');?></p>
                        </div>
                        <label for="bonus_code">
                            <input name="bonus_code" id="bonus_code" class="input-normal" type="text" autocapitalize="off" autocorrect="off"
                                   disabled="disabled"
                                   placeholder='<?php echo t('register.bonus_code.nostar'); ?>'
                                   value="<?php echo $bonus_code; ?>"
                            />
                        </label>
                    <?php endif; ?>
                </div>
            </div>

            <div class="registration-content-right">
                <div class="regstep2-right">

                    <!-- birth -->
                    <?php
                    $required_age = phive('SQL')->getValue("SELECT reg_age FROM bank_countries WHERE iso = '{$_SESSION['rstep1']['country']}'");
                    $day = !empty($_SESSION['rstep2']['birthdate']) ? $_SESSION['rstep2']['birthdate'] : $_POST['birthdate'];
                    $month = !empty($_SESSION['rstep2']['birthmonth']) ? $_SESSION['rstep2']['birthmonth'] : $_POST['birthmonth'];
                    $year = !empty($_SESSION['rstep2']['birthyear']) ? $_SESSION['rstep2']['birthyear'] : $_POST['birthyear'];

                    if(phive()->isMobile()): ?>
                        <div id="birthdate-container">
                            <label>
                                <?php echo t('register.birthdate') ?>
                            </label>
                            <span class="styled-select">
                                <?php dbSelect("birthdate", $fc->getDays(), $day, array('', t('day')), '',false,'', true, lic('shouldDisableInput', ['birthdate']), 'bday-day'); ?>
                            </span>
                            <span class="styled-select">
                                <?php dbSelect("birthmonth", $fc->getFullMonths(), $month, array('', t('month')), '',false,'', true, lic('shouldDisableInput', ['birthmonth']), 'bday-month'); ?>
                            </span>
                            <span class="styled-select" id="birthyear-cont">
                                <?php dbSelect("birthyear", $fc->getYears($required_age), empty($year) ? 1970 : $year, array('', t('year')), '',false,'', true, lic('shouldDisableInput', ['birthyear']), 'bday-year'); ?>
                            </span>
                        </div>

                    <?php else: ?>
                        <div id="birthdate-container">
                            <label>
                                <div id="birthdate-title">
                                    <?php echo t('register.birthdate.nostar') ?>
                                </div>
                                <span class="styled-select" id="birthyear-cont">
                                    <?php dbSelect("birthyear", $fc->getYears($required_age), empty($year) ? 1970 : $year, array('', t('year')), '', false,  '', true, lic('shouldDisableInput', ['birthyear']), 'bday-year'); ?>
                                </span>
                                <span class="styled-select">
                                    <?php dbSelect("birthdate", $fc->getDays(), $day, array('', t('day')), '', false,  '', true, lic('shouldDisableInput', ['birthdate']), 'bday-day') ?>
                                </span>
                                <span class="styled-select">
                                    <?php dbSelect("birthmonth", $fc->getFullMonths(), $month, array('', t('month')), '', false,  '', true, lic('shouldDisableInput', ['birthmonth']), 'bday-month') ?>
                                </span>

                            </label>
                        </div>

                    <?php endif ?>

                    <div style="clear:both;height:10px"></div>


                    <?php
                    if(lic('showRegistrationExtraFields', ['step2', 'birth_country'])):
                        $country = $user ? $user->getCountry() : phive('IpBlock')->getCountry();
                        $countries = lic('getBirthCountryList');
                        ?>
                        <!-- birth_country name -->
                        <label for="birth_country">
                            <div id="birth_country-title">
                                <?php echo t('register.birth_country') ?>
                            </div>
                            <span class="styled-select">
                                <?php dbSelect("birth_country", $countries, $country, ['', t('choose.country.of.birth')], '', false, '', true, lic('shouldDisableInput', ['birth_country'])) ?>
                            </span>
                        </label>
                    <?php endif; ?>

                    <?php if(!phive()->isMobile()): ?>
                        <div style="clear:both;height:10px"></div>
                        <!-- currency -->
                        <?php if(phive("Currencer")->getSetting('multi_currency') == true): ?>
                            <label for="currency">
                                <div id="currency-title">
                                    <?php echo t('register.currency.nostar') ?>
                                </div>
                                <span class="styled-select">
                                    <?php cisosSelect(true, $preselect_currency, 'currency', 'site_input', array(), false, false, false, !empty($forced_currency)) ?>
                                </span>
                            </label>
                        <?php endif ?>
                    <?php endif ?>
                    <div style="clear:both;height:10px"></div>

                    <!-- sex -->
                    <?php
                    if(!empty($_POST['sex'])) {
                        $sex = $_POST['sex'];
                    } elseif(!empty($_SESSION['rstep2']['sex'])) {
                        $sex = $_SESSION['rstep2']['sex'];
                    } else {
                        $sex = '';
                    }
                    ?>
                    <div class="step2-gender-lbl"><?php echo t('register.gender.nostar'); ?></div>
                    <input type="radio" id="female" name="sex" value="Female" class="rb"
                        <?php if($sex == 'Female') echo 'checked="checked"' ?>
                        <?= lic('shouldDisableInput', ['sex']) ? 'disabled' : '' ?>
                    />
                    <label class="gender" for="female">
                        <?php echo t('register.female') ?>
                    </label>
                    <input type="radio" id="male" name="sex" value="Male" class="rb"
                        <?php if($sex == 'Male' || empty($sex)) echo 'checked="checked"' ?>
                        <?= lic('shouldDisableInput', ['sex']) ? 'disabled' : '' ?>
                    />
                    <label class="gender" for="male">
                        <?php echo t('register.male') ?>
                    </label>

                    <div style="clear:both;height:20px"></div>

                    <?php if(!lic('hasExtVerification', [], $user)): ?>
                        <!-- validation code -->
                        <label for="validation_code">
                            <p><?php echo t('enter.validation.code'); ?></p>
                            <div id='customer_email'>
                               <p><?php echo t('enter.email') . ': '; echo $user->getAttribute('email'); ?></p>
                            </div>
                            <div id="change_email_mobile">
                                <p><?php echo t('register.change.email.mobile'); ?></p>
                            </div>
                            <div id='customer_mobile'>
                                <p><?php echo t('account.mobile') . ' '; echo $user->getAttribute('mobile'); ?></p>
                            </div>

                            <?php
                            $email_code = '';
                            if(!empty($_GET['email_code'])) {
                                $email_code = $_GET['email_code'];
                            }
                            ?>
                            <input name="minlen" id="email_code" class="input-normal" type="number" inputmode="numeric" autocapitalize="off" autocorrect="off" autocomplete="one-time-code"
                                   placeholder='<?php echo t('validation.code.nostar'); ?>'
                                   value="<?php echo $email_code; ?>"/>
                        </label>

                        <!-- resend code -->
                        <div id="resend_code" class="resend-button">
                            <div class=""><?php et('resend.code'); ?></div>
                        </div>
                    <?php endif ?>

                    <div id="infotext" class="errors"></div>
                    <br/>

                    <!-- i am 18 -->
                    <label for="eighteen">
                        <input id="eighteen" type="checkbox" name="check" />
                        <span id="eighteen-span">
                            <?php
                            if($required_age > 18) {
                                echo t('register.iamabove21');
                            } else {
                                echo t('register.iamabove18');
                            }
                            ?>
                        </span>
                    </label>

                </div>
            </div>

            <div style="clear: both"></div>

            <div id="submit_step_2" class="register-button register-button-step2" onclick="submitStep2()">
                <div class=""><?php et('register'); ?></div>
            </div>

        </div>
    <?php
  }

    public static function stepFour()
    {
        //jsRedirect(phive('Localizer')->langLink('', '/cashier/deposit/'));
        if(empty($_SESSION['experian_msg']))
            echo '<script>'.depGo().';</script>';
        return;
        ?>
            <script>
             // mgAjax({action: "load-stat", loc: "reg-step4"});
            </script>
            <div id="step3" class="step3">
                <div class="step3-txt"><?php et('register.step3.html'); ?></div>

                <div id="submit_step_3" class="register-big-btn">
                    <div class="register-big-btn-txt"><?php et('deposit'); ?></div>
                </div>
                <br/>
                <div id="submit_step_3" class="register-big-btn">
                    <div class="register-big-btn-txt" onclick="goTo('<?php echo phive('Localizer')->langLink('', '/account') ?>')"><?php et('account'); ?></div>
                </div>
            </div>
        <?php
    }

        public static function stepThree(){
            // Not used anymore
            phive()->dumpTbl('reg-step-three-debug', $_REQUEST);
        }

    /**
     *
     * @param string $type
     */
    public static function echoOverlayLink(string $type)
    {
        $overlay_link = phive('DBUserHandler')->getBannerOverlayLink($type);

        if(!empty($overlay_link)):
        ?>
            <a href="<?php echo $overlay_link; ?>"
                target="_blank"
                rel="noopener noreferrer"
                onclick="window.open('<?php echo $overlay_link; ?>','sense');"
                style="display: block; position: relative; z-index: 1000; margin-top: -25px; text-align: center;">
                  <?php et("box893.overlay.link") ?>
             </a>
        <?php
        endif;
    }
}

if(!empty($_GET['ajax']))
  Registration::printHtml($_GET['step']);
