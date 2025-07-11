<?php

use Laraphive\Contracts\EventPublisher\EventPublisherInterface;
use Laraphive\Domain\User\Actions\Steps\DataTransferObjects\FinalizeRegistrationStep1Data;
use Laraphive\Domain\User\DataTransferObjects\LoginCommonData;
use Videoslots\HistoryMessages\Exceptions\InvalidMessageDataException;
use Videoslots\HistoryMessages\UserRegistrationHistoryMessage;
use Videoslots\User\ThirdPartyVerificationFields\Factory\ThirdPartyVerificationFieldsFactory;
use Laraphive\Domain\User\DataTransferObjects\ValidateStep1FieldsResponse;

require_once __DIR__ . '/../html/registration_new.php';
require_once __DIR__ . '/Step1FieldsTrait.php';
require_once __DIR__ . '/Step2FieldsTrait.php';
require_once __DIR__ . '/RegistrationFormBuilder.php';

/**
 * Class RegistrationHtml
 */
class RegistrationHtml extends Registration
{
    const CONTEXT_REGISTRATION = 'registration';
    const CONTEXT_REGISTRATION_MITID = 'registration_mitid';

    use Step1FieldsTrait;
    use Step2FieldsTrait;

    /**
     * @param string $type
     * @param string $href
     * @param string $label
     * @return string
     */
    protected static function returnLink(string $type, string $href, string $label): string
    {
        $link_lic = lic('getRegistrationFilePath', [$type]);
        if ($link_lic) {
            $href = $link_lic;
            $action = 'target="_blank" rel="noopener noreferrer"';
        } else {
            $uri = phive('Config')->getValue('registration', $type);
            $llink = llink((isMobileSite() ? '/mobile' : '') . $uri);
            $action = 'onclick="window.open(\' ' . $llink . ' \',\'sense\',\'width=740,scrollbars=yes,resizable=yes\');"';
        }

        return "<a href='{$href}' {$action}>{$label}</a>";
    }

    /**
     * Installs all the input fields for the registration step 1 process
     */
    public static function stepOne()
    {
        loadJs("/phive/js/jquery.cookie.js");
        loadJs("/phive/modules/DBUserHandler/js/registration.js");

        $country = !empty($_SESSION['rstep1']['country']) ? $_SESSION['rstep1']['country'] : phive('IpBlock')->getCountry();

        // check if Netent games are allowed for users from this country
        $fspin = phive('Config')->getByTagValues('freespins');
        $can_do = function ($key) use ($country, $fspin) {
            return in_array(strtolower($country), explode(',', strtolower($fspin[$key])));
        };
        $can_netent = $can_do('netent-reg-bonus-countries');

        $disabled_countries = !empty($_SESSION['rstep2']) ? 'disabled' : '';
        $is_country_dropdown_shown = lic('showCountryFieldOnRegistration');

        $countryClass = lic('shouldDisableInput', ['country']);
        $countries = self::getCountriesForDropdown();
        $calling_codes = phive('DBUserHandler')->getCallingCodesForDropdown();
        $security_questions = RegistrationHtml::getSecurityQuestions();
        $required_age = phive('SQL')->getValue("SELECT reg_age FROM bank_countries WHERE iso = '{$country}'");
        $maintenance = lic('getLicSetting', ['scheduled_maintenance']);
        $is_maintenance_mode = !empty($maintenance) && $maintenance['enabled'];
        $maintenance_message = null;
        $is_auth_allowed = phive('DBUserHandler')->isRegistrationAndLoginAllowed();
        $auth_blocked_message = '';

        if ($is_maintenance_mode) {
            $maintenance_message = t2('blocked.maintenance.login.html', ['start_time' => $maintenance['from'], 'end_time' => $maintenance['to']]);
        }

        if (!$is_auth_allowed) {
            $auth_blocked_message = t('blocked.access.popup.desc');
        }
        self::commonPlugins();
        ?>
        <script>
            sCookie('afStatus', 'open');

            jQuery(document).ready(function () {
                hideLoader();
                resizeRegistrationbox(1);
                $("form").attr("autocomplete", "on");
            });
        </script>

        <div class="registration-content-left">
            <!-- step one content -->
            <div id="step1" class="step1">
                <?php csrf_input(); ?>

                <!-- register info -->
                <div class="registration-info-txt">
                    <h3><?php echo t("register.step1.infoheader"); ?></h3>
                    <p><?php echo t("register.step1.infotext"); ?></p>
                </div>

                <!-- personal number -->
                <div id="personal_number_input" class="registration-step-1-control">
                    <label for="personal_number">
                        <input id="personal_number" class="input-normal" type="text" autocapitalize="off" autocorrect="off" style="display: none;"/>
                        <div id="personal_number_msg" class="info-message" style="display:none"></div>
                    </label>
                </div>

                <!-- email -->
                <div id="email_holder" class="registration-step-1-control">
                    <label for="email">
                    <input id="email"
                            class="input-normal required email"
                            name="email"
                            type="email"
                            autocapitalize="off"
                            autocorrect="off"
                            autocomplete="email"
                            <?php echo lic('getMaxLengthAttribute', ['email']); ?>/>
                        <div id="email_msg" class="info-message" style="display:none"></div>
                        <div id="email_taken_msg" class="info-message taken-error" style="display:none"></div>
                    </label>
                </div>

                <!-- second email -->
                <div id="secemail_holder" class="registration-step-1-control">
                    <label for="secemail">
                        <input id="secemail" class="input-normal required email" name="secemail" type="email" autocapitalize="off" autocorrect="off" autocomplete="email" style="display: none;"/>
                        <div id="secemail_msg" class="info-message" style="display:none"></div>
                    </label>
                </div>

                <!-- password -->
                <div id="password_holder" class="registration-step-1-control">
                    <label for="password">
                        <input id="password" class="input-normal hasUpper hasLower hasTwoDigits" name="confirm_password" type="password" autocomplete="off" passwordrules="minlength: 8; required: lower; required: upper; required: [0]; required: [9];"/>
                        <button class="password-field-icon<?= phive()->isMobile() ? '-mobile' : '' ?>">
                            <img class="password-eye-icon" src="/diamondbet/images/<?= brandedCss() ?>registration-icons/eye-open.png">
                            <img class="password-eye-icon" src="/diamondbet/images/<?= brandedCss() ?>registration-icons/eye-close.png" style="display: none">
                        </button>
                        <div id="password_msg" class="info-message" style="display:none"></div>
                    </label>
                </div>

                <!-- secpassword -->
                <div id="secpassword_holder" class="registration-step-1-control">
                    <label for="secpassword">
                        <input id="secpassword" class="input-normal" name="secpassword" type="password" autocomplete="new-password" style="display: none;" passwordrules="minlength: 8; required: lower; required: upper; required: [0]; required: [9];"/>
                        <div id="secpassword_msg" class="info-message" style="display:none"></div>
                    </label>
                </div>

                <!-- security question -->
                <label for="security_question" class="registration-step-1-control">
                    <span class="styled-select">
                        <?php dbSelect("security_question", $security_questions, '', ['', t('register.security.question')], 'new', false) ?>
                        <div id="security_question_msg" class="info-message" style="display:none"></div>
                    </span>
                </label>

                <!-- security answer -->
                <div id="security_answer_holder" class="registration-step-1-control">
                    <label for="security_answer">
                        <input id="security_answer" class="input-normal hasletter" type="text" autocapitalize="off" autocorrect="off" autocomplete="off"/>
                        <div id="security_answer_msg" class="info-message" style="display:none"></div>
                    </label>
                </div>

                <!-- country name -->
                <?php if($is_country_dropdown_shown): ?>
                    <label for="country">
                        <span class="<?php echo $countryClass ? 'styled-select styled-select-valid disabled-field' : 'styled-select styled-select-valid' ?>">
                            <?php dbSelect(
                                "country",
                                $countries,
                                $country,
                                ['', t('choose.country')],
                                $countryClass ? 'disabled-field new valid' : 'new',
                                false,
                                $disabled_countries
                            ) ?>
                        </span>
                    </label>
                <?php else: ?>
                    <input id="country" class="new" name="country" value="<?= $country ?>" type="hidden"/>
                <?php endif; ?>

                <!-- mobile name -->
                <div id="mobile-container">
                    <label for="mobile">
                        <span id="mobile-prefix-select" class="styled-select">
                            <?php dbSelect("country_prefix", $calling_codes, '', [], 'new') ?>
                        </span>
                        <input id="mobile" class="input-normal mobileLength" name="mobile" type="tel" autocomplete="tel"/>
                        <div id="mobile_msg" class="info-message" style="display:none"></div>
                    </label>
                </div>

                <div style="clear:both;height:10px"></div>

                <!-- referring_friend -->
                <div id="referring_friend_info_holder" class="registration-step-1-control">
                    <label for="referring_friend"><?= t('register.refer.friend'); ?>
                        <input id="referring_friend" name="referring_friend" class="input-normal" type="text" style="float:left"/>
                        <input id="referring_friend_info" type="hidden"/>
                        <div id="referring_friend_msg" class="info-message" style="display:none"></div>
                    </label>
                </div>

                <!-- accept privacy -->
                <div class="privacy-check">
                    <label for="privacy" class="registration-step-1-control">
                        <input class="registration-checkbox" id="privacy" name="privacy_check" type="checkbox" style="clear:left;" <?php echo !empty($_SESSION['rstep1']['privacy']) ? 'checked="checked"' : '' ?>/>
                        <span id="privacy-span">
                        <?php echo t('register.privacy1') ?>
                        <?php
                        echo self::returnLink('privacy-policy-link', '#', t('register.privacy2'));
                        ?>
                    </span>
                    </label>
                </div>

                <!-- accept terms -->
                <div class="conditions-check">
                    <label for="conditions" class="registration-step-1-control registration-tc-checkbox">
                        <input class="registration-checkbox" id="conditions" name="conditions_check" type="checkbox" style="clear:left;" <?php echo !empty($_SESSION['rstep1']['conditions']) ? 'checked="checked"' : '' ?>/>
                        <span id="terms-span">
                        <?php echo t('register.toc1') ?>
                        <?php
                        echo self::returnLink('tco_link', '#', t('register.toc2'));
                        ?>
                    </span>
                    </label>
                </div>

                <!-- accept bonus terms -->
                <div class="bonus-terms-and-conditions">
                    <label for="bonus_terms_and_conditions" class="registration-step-1-control registration-tc-checkbox">
                        <input class="registration-checkbox" id="bonus_terms_and_conditions" name="check" type="checkbox" style="clear:left;" <?php echo !empty($_SESSION['rstep1']['bonus_terms_and_conditions']) ? 'checked="checked"' : '' ?>/>
                        <span id="bonus-terms-span">
                        <?php echo t('register.toc1') ?>
                        <?php
                        echo self::returnLink('btco_link', '#', t('register.btoc1'));
                        ?>
                    </span>
                    </label>
                </div>

                <!-- accept gambling -->
                <div class="gambling-check">
                    <label for="gambling_check" class="registration-step-1-control step1-check">
                        <input class="registration-checkbox" id="gambling_check" name="check" type="checkbox" style="clear:left;" <?php echo !empty($_SESSION['rstep1']['gambling_check']) ? 'checked="checked"' : '' ?>/>
                        <span id="gambling-span">
                        <?php echo t('register.gambling') ?>
                        <?php
                        echo self::returnLink('gambling_link', '#', t('register.gambling.link'));
                        ?>
                    </span>
                    </label>
                </div>

                <!-- accept age -->
                <div class="age-check">
                    <label for="age_check" class="registration-step-1-control step1-check">
                        <input class="registration-checkbox" id="age_check" name="check" type="checkbox" style="clear:left;" <?php echo !empty($_SESSION['rstep1']['age_check']) ? 'checked="checked"' : '' ?>/>
                        <span id="eighteen-span"><?php echo t($required_age > 18 ? 'register.iamabove21' : 'register.iamabove18'); ?></span>
                        <div id="age_check_msg" class="info-message" style="display:none"></div>
                    </label>
                </div>

                <div style="display:none">
                    <input id="step" value="1" type="hidden"/>
                </div>

                <div id="submit_step_1">
                    <div class="<?= phive()->isMobile() ? 'register-big-btn-txt' : '' ?>"></div>
                </div>
                <?php if ($is_maintenance_mode): ?>
                    <div id="errorZone" class="errors"><?php echo $maintenance_message ?></div>
                <?php elseif (!$is_auth_allowed): ?>
                    <div id="errorZone" class="errors"><?php echo $auth_blocked_message ?></div>
                <?php else: ?>
                    <div id="errorZone" class="errors" style="display:none"></div>
                <?php endif ?>
            </div>
        </div>


        <?php if (!phive()->isMobile() && substr_count($_SERVER['REQUEST_URI'], '/mobile/register') === 0): ?>
        <?php
        $freespins_all_image_alias = '';
        $freespins_exceptions_image_alias = '';
        $freecash_image_alias = '';
        $welcomebonus_image_alias = '';

        // check if we have a bonus code, and if so get the image aliases
        if (!empty(phive('Bonuses')->getBonusCode())) {
            $freespins_all_image_alias = phive('ImageHandler')->getImageAliasForBonusCode('banner.registration.freespins.all.');
            $freespins_exceptions_image_alias = phive('ImageHandler')->getImageAliasForBonusCode('banner.registration.freespins.exceptions.');
            $freecash_image_alias = phive('ImageHandler')->getImageAliasForBonusCode('banner.registration.freecash.');
            $welcomebonus_image_alias = phive('ImageHandler')->getImageAliasForBonusCode('banner.registration.welcomebonus.');
        }

        ?>
        <div class="registration-content-right">
            <h3><?php et('register.welcome.bonus.header'); ?></h3>
            <div class="registration-banner-container" data-jurisdiction="<?= $country ?>">
                <?php // show Free Spins banner ?>
                <div class="registration-banner">
                    <?php
                        img($freespins_all_image_alias, 362, 180, 'banner.registration.freespins.all.default');
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
                <?php if (phive('UserHandler')->getSetting('full_registration') === true): ?>
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

    /**
     * Installs all the input fields for the registration step 2 process
     */
    public static function stepTwo($migration = false)
    {
        loadJs("/phive/modules/DBUserHandler/js/registration.js");
        lic('loadCss');
        self::commonPlugins();
        $user = cuRegistration();
        if (empty($user)) {
            // TODO handle this in a prettier way.
            die('timed out');
        }

        $country = $user->getCountry();
        phive('Licensed')->forceCountry($country);
        /** @var array $fields */

        if($migration){
            $fields = lic('registrationStep2FieldsMigration');
        } else {
            $fields = lic('registrationStep2Fields');
        }

        ?>
        <div class="registration-wrapper-<?= $country ?>">
            <div id="step2" class="step2<?= isOneStep() ? ' hidden' : '';?>">
                <input id="country-step2" type="hidden" value="<?= $country ?>">
                <!-- register info -->
                <div class="step2-description" style="display: none">
                    <div class="registration-info-txt">
                        <p><?php echo t("register.step2.infotext"); ?></p>
                    </div>
                </div>
                <div class="registration-country-default registration-country-<?= $country ?> ">
                    <? new RegistrationFormBuilder('left', $fields['left'], self::getStep2Fields()) ?>
                    <? new RegistrationFormBuilder('middle', $fields['middle'], self::getStep2Fields()) ?>
                    <? new RegistrationFormBuilder('right', $fields['right'], self::getStep2Fields()) ?>
                </div>

                <div id="infotext" class="errors"></div>
                <div><div><div id="general_error" class="errors info-message" style="display:none"></div></div></div>

                <div style="clear: both"></div>

                <div id="submit_step_2" class="register-button register-button-step2" onclick="handleRegistrationStep2()">
                    <div class=""><?php
                        if($migration){
                            et('msg.ontario.popup.box.button');
                        } else {
                            et('register');
                        }

                    ?></div>
                </div>
            </div>
        </div>
        <?php licHtml('registration_step2_misc'); ?>
        <script>
            $(document).ready(function () {
                var codeCallback = function (ret) {
                    $("#infotext").html(ret);
                    resizeRegistrationbox(2);
                };

                $("#close-registration-box").click(function () {
                    goTo('/?signout=true');
                });

                $("#resend_code").click(function () {
                    $(this).hide();
                    mgSecureAjax({action: 'send-sms-code'}, codeCallback);
                    mgAjax({action: 'send-email-code'}, codeCallback);
                });

                resizeRegistrationbox(2);
                $("form").attr("autocomplete", "on");
                <?php licHtml('load_step2_data');?>

                top.$.multibox('posMiddle', 'registration-box');

                if (registration_mode === 'onestep' || (registration_mode === 'bankid' && !isMobile())) {
                    handleRegistrationStep2();
                    showLoader();
                } else {
                    hideLoader();
                }
            })
        </script>
        <?php
    }

    /**
     * Show the common nid form
     *
     * @param string $context // value in [registration]
     * @param $box_id
     * @param $country
     */
    public static function intermediaryStep($context, $box_id, $country)
    {
        $fields = (new ThirdPartyVerificationFieldsFactory())->getFields($context, $box_id, $country);

        $mbox = new MboxCommon();
        $top_part_data = $fields->getTopPartData();

        $personal_number_message = $fields->getPersonalNumberMessage();
        $verification_button = $fields->getStartExternalVerificationButtonData();

        loadJs("/phive/modules/DBUserHandler/js/registration.js");
        ?>
        <style>
            #nid-field-error {
                display: none;
                margin-top: 20px;
                padding-left: 10px;
                padding-right: 10px;
            }

            .country-SE #lic-mbox-login-custom-info {
                height: 64px;
                margin-top: 10px;
            }

            #multibox-overlay-mbox-loader {
                z-index: 3008 !important;
            }
            #mbox-loader {
                z-index: 3009 !important;
            }
        </style>
        <div class="lic-mbox-wrapper version2">
            <?php $mbox->topPart($top_part_data) ?>
            <div class="lic-mbox-container minimal-padding country-<?= $country ?>">
                <div id="lic-mbox-login-custom-info"><?= t($fields->getCustomLoginInfo()) ?></div>

                <?php if(!useOldDesign()): ?>
                    <img src="/diamondbet/images/<?= brandedCss() ?>login.png" alt="login-image">
                <?php endif; ?>

                <div id="lic-mbox-login-custom">
                    <input id="nid-field"
                           type="number"
                           placeholder="<?= $fields->getNidPlaceholder() ?>"
                           class="input-normal lic-mbox-input"
                    />
                    <div id="nid-field-error" class="error" style="display: none;">
                        <?= t($personal_number_message) ?>
                    </div>
                    <div id="nid_field_msg" class="error" style="display: none;">
                        <?= t($personal_number_message) ?>
                    </div>
                    <p>
                        <input type="checkbox" id="remember_nid"><?= t($fields->getRememberNidMessage()) ?>
                    </p>
                    <?php if ($context == RegistrationHtml::CONTEXT_REGISTRATION_MITID): ?>
                        <div class="register-button lic-mbox-btn">
                            <div id="mitid-verify-btn" class="register-big-btn-txt register-button-second_denmark"
                                <?= $verification_button->isDisabled() === true
                                    ? ''
                                    : 'onclick="licFuncs.startExternalVerification(\''. $context . '\')"'
                                ?>
                            >
                                <span><?= t($verification_button->getAlias()) ?></span>
                                <img class="register-button-second_denmark-img"
                                     src="<?= $verification_button->getImage() ?>"
                                />
                            </div>
                            <?php if($verification_button->isDisabled() === true): ?>
                                <span class="lic-mbox-label-info-mit-id--unavailable">
                                    <?= t($verification_button->getDisabledText()) ?>
                                </span>
                            <?php endif ?>
                        </div>
                    <?php else: ?>
                        <div class="lic-mbox-btn lic-mbox-btn-active verification-btn-<?= $country ?>"
                             onclick="licFuncs.startExternalVerification('<?=$context?>')"
                        >
                            <span><?= t($verification_button->getAlias()) ?></span>
                        </div>
                    <?php endif;?>
                </div>
            </div>
        </div>
        <script>handleRememberNid()</script>
        <?php
    }

    /**
     * Common Plugins
     */
    public static function commonPlugins(){
        if(isLogged()):?>
            <script>
              gotoLang('/');
            </script>
        <?php endif;

        if (phive()->isMobile()) {
            include __DIR__ . '/../../../../diamondbet/html/chat-support.php';
        }
    }

    /**
     * Detect if intermediary step(external validation) is required
     *
     * @param array $request
     * @return bool
     */
    public static function intermediaryStepRequired($request)
    {
        $hasExtVerification = !empty(lic('hasExtVerification', [], null, null, $request['country']));
        $passedExtVerification = lic('passedExtVerification', [$request], null, null, $request['country']);

        return $hasExtVerification && !$passedExtVerification;
    }

    public static function communicationChannelVerificationRequired($request): bool {
        $oneStepRegistration = lic('oneStepRegistrationEnabled', [], null, null, $request['country']);
        $user = cuRegistration();
        $passedCommunicationChannelVerification = $user->getSetting('email_code_verified') === 'yes' || $user->getSetting('sms_code_verified') === 'yes';

        return $oneStepRegistration && !$passedCommunicationChannelVerification;
    }

    /**
     * Common logic applied before inserting the user in DB
     * Returns false for success(no errors found) or array otherwise
     *
     * @param $request
     * @return array|bool
     */
    public static function beforeUserCreate($request)
    {
        if (empty(lic('hasExtVerification', [], null, null, $request['country']))) {
            return false;
        }

        trackRegistration("Has external verification");

        /** @var DBUserHandler $DBUserHandler */
        $DBUserHandler = phive('DBUserHandler');
        $nid = $request['personal_number'];
        if (!isset($request['mitid_user']) && !$_SESSION['rstep1']['pnp_user']) {
            $nid = lic('validateExtVerAndGetNid', [$request], null, null, $request['country']);
            if (empty($nid)) {
                return ['personal_number' => 'invalid.personal.number'];
            }
        }

        /** @var DBUser $other */
        $other = $DBUserHandler->doubleNid($nid, $request['country'], true);
        if (empty($other)) {
            trackRegistration("Nid is not in the database");
            return false;
        }


        //if it's unfinished registration we can update step1 data
        if($other && !phive('UserHandler')->hasFinishedRegistrationStep2($other)){
            return false;
        }


        trackRegistration("Tried to register with an existing nid");
        // We need to mark the first session after registration as OTP validated.
        $DBUserHandler->setAjaxContext()->markSessionAsOtpValidated();

        // we have to login
        [$result, $action] = $DBUserHandler->login($other->getUsername(), null, false, false, true);

        $result = $DBUserHandler->getLoginAjaxContextRes($result, $action);

        if (empty($request["success"])) {
            return $result;
        }

        return false;
    }

    /**
     * Common logic applied after the user was created
     * todo-dip: reminder, the code from here will be refactored during the step 2 process
     *
     * @param DBUser $user
     * @param $request
     * @return array|bool
     */
    public static function afterUserCreate($user, $request)
    {
        /** @var DBUserHandler $uh */
        $uh = phive('UserHandler');

        self::skipDefaultProvince($user);

        self::setupSecurityQuestion($user, $request);

        if (($uh->showNationalId($request['country']) === true && lic('getDataFromNationalId'))) {
            /** @var false|ZignSecLookupPersonData $nid_res */
            $nid_res = lic('getDataFromNationalId', [$request['country'], $request['personal_number']]);
            if ($nid_res === false) {
                return ['personal_number' => 'invalid.personal.number'];
            }
        } else {
            $lookup_res = phMgetArr($_SESSION['cur_req_id'] . "-raw");
            $nid = phMgetArr($_SESSION['cur_req_id'] . "-nid");

            if (!empty($lookup_res) && !empty($nid)) {
                $lookup_data = lic('getPersonLookupHandler', [], $user)->mapLookupData($lookup_res, false);

                if ($lookup_data->wasFound()) {
                    $user->setJsonSetting('nid_data', $lookup_data->getResponseData());
                } else {
                    $user->addComment("Customer personal number was not found by Zignsec // system");
                }
            }
        }

        // We handle ext verification here now that we have a freshly created user.
        if (lic('hasExtVerification', null, $user) && !$_SESSION['rstep1']['pnp_user']) {
            [$res, $err_message] = lic('extVerify', [$user, $request['personal_number']], $user);
            // Player timed out, tried to register double account or is underage if this is false.
            // TODO make something nicer with error message / popup or something perhaps? /Henrik
            if (!$res) {
                return ['personal_number_error' => $err_message];
            }

            if (lic('hasPrepopulatedStep2')) {
                $lookup_data = json_decode($user->getSetting('nid_data'), true);
                trackRegistration($lookup_data, "afterUserCreate_extVerCachedData");
                if (!empty($lookup_data)) {
                    $lookup_data = lic('getPersonLookupHandler', [], $user)->mapLookupData($lookup_data);
                    $_SESSION['rstep2'] = $lookup_data;
                    $_SESSION['rstep2_disabled'] = $_SESSION['rstep2'];
                    trackRegistration($_SESSION['rstep2'], "afterUserCreate_extVerCachedData2");
                }
                $already_prepopulated = true;
            }
        }

        if (empty($already_prepopulated) && !empty($nid_res)) {
            // We try to set the NID
            $res = $user->setNid($request['personal_number']);

            if ($nid_res->wasFound()) {
                $user->setJsonSetting('nid_data', $nid_res->getResponseData());
            } else {
                $user->addComment("Customer personal number was not found by Zignsec // system");
            }
            if (lic('hasPrepopulatedStep2')) {
                $_SESSION['rstep2'] = $_SESSION['tmp_rstep2'];
                $_SESSION['rstep2_disabled'] = $_SESSION['rstep2'];

                unset($_SESSION['tmp_rstep2']);
            }
        }

        phMdel($_SESSION['cur_req_id'] . "-raw");
        phMdel($_SESSION['cur_req_id'] . "-nid");
        lic('addProvince', [$user]);

        return false;
    }

    /**
     * Here the user is created and user settings are applied
     *
     * @param $request
     * @return array
     */
    public static function finalizeRegistrationStep1($request)
    {
        $user = null;
        /** @var DBUserHandler $DBUserHandler */
        $DBUserHandler = phive('DBUserHandler');

        if (!empty($request['bonus_code']) && empty($_SESSION['affiliate'])) {
            $_SESSION['affiliate'] = $request['bonus_code'];
        }

        $request['bonus_code'] = empty($_SESSION['affiliate']) ? $request['bonus_code'] : $_SESSION['affiliate'];

        $error_message = self::beforeUserCreate($request);
        if (!empty($error_message)) {
            return [$user, $error_message];
        }

        trackRegistration("User should be created next (or updated in case of change email/mobile)");
        if (empty($_SESSION['rstep1']['user_id'])) {
            $user = $DBUserHandler->createUser($request);
        } else {
            $user = cu($_SESSION['rstep1']['user_id']);
            $DBUserHandler->updateUser($user, $request, 'step1');
        }
        if (empty($user)) {
            return [$user, "general_error"];
        }

        $_SESSION['rstep1']['user_id'] = $user->getId();
        $error_message = self::afterUserCreate($user, $request);
        if (!empty($error_message)) {
            return [$user, $error_message];
        }

        // add a user setting with the affiliate tracking code
        if (!empty($_SESSION['affiliate_postback_id'])) {
            $user->setSetting('affiliate_postback_id', $_SESSION['affiliate_postback_id']);
        }

        if(!isOneStep() && $user->getSetting('email_code_verified') !== 'yes'){
            $user->setSetting('email_code_verified', 'no');
        }

        $user->setSetting('registration_in_progress', 1);
        $user->setTrackingEvent('partially-registered', ['triggered' => 'yes', 'model' => 'users', 'model_id' => $user->getId()]);
        $user->setTcVersion();
        $user->setPpVersion();

        if (!empty($request['bonus_terms_and_conditions'])) {
            $user->setBtcVersion();
        }

        if (lic('isSportsbookEnabled')) {
            $user->setSportTcVersion();
        }

        $DBUserHandler->handleInternalUserVerification($user);
        $DBUserHandler->handleReturningPermanentSelfExcludedUser($user);

        if (!empty($_POST['verifyExternalUrl'])) {
            $res = lic('verifyRedirectStartWrapper', [$user], $user);
            if (is_string($res)) {
                return [$user, $res];
            } elseif (is_array($res)) {
                return [$user, null, $res];
            }
        }

        return [$user, null];
    }

    /**
     * Here the user is created and user settings are applied
     *
     * @param array $request
     *
     * @return array
     */
    public static function finalizeRegistrationStep1V2(
      array $request,
      FinalizeRegistrationStep1Data $data
    ): array {
        $affiliate = $data->getAffiliate();
        $user = null;
        /** @var DBUserHandler $DBUserHandler */
        $DBUserHandler = phive('DBUserHandler');

        if (!empty($request['bonus_code']) && empty($affiliate)) {
            $affiliate = $request['bonus_code'];
        }

        if($_SESSION['rstep1']['pnp_user']){
            $request['personal_number'] = $_SESSION['rstep1']['pnp_user'];
        }

        $request['bonus_code'] = empty($affiliate) ? $request['bonus_code']  : $affiliate;

        $error_message = self::beforeUserCreate($request);
        if (!empty($error_message)) {
            return [$user, $error_message];
        }

        trackRegistration("User should be created next (or updated in case of change email/mobile)");

        $nid = lic('getCachedExtVerResult', ['', 'nid']);
        $nid_user = null;
        $is_nid_user_registered = true;
        if (!empty($nid)) {
            $nid_user = phive('DBUserHandler')->getUserByNid($nid, $request['country']);
            if (!empty($nid_user)) {
                $is_nid_user_registered = $nid_user->hasSetting('registration_end_date');
            }
        }

        if (isBankIdMode() || isPNP(null, $request['country'])) {
            $user = $DBUserHandler->getUserByNid($request['personal_number'], $request['country']);
            if (!empty($user)) {
                $DBUserHandler->updateUser($user, $request, 'step1');
            } else {
                $user = $DBUserHandler->createUser($request);
            }
        } elseif (!empty($nid_user) && !$is_nid_user_registered) {
            $user = $nid_user;
            $DBUserHandler->updateUser($user, $request, 'step1');
        } elseif (empty($data->getUserId())) {
            $user = cu($request['email']);
            if (!empty($user)) {
                $DBUserHandler->updateUser($user, $request, 'step1');
            } else {
                $user = $DBUserHandler->createUser($request);
            }
        } else {
            $user = cu($data->getUserId());
            $DBUserHandler->updateUser($user, $request, 'step1');
        }

        if (empty($user)) {
            return [$user, "general_error"];
        }

        $user->refresh();

        if (!empty($request['cur_req_id']) && !isPNP($user)) {
            $request['personal_number'] = lic('validateExtVerAndGetNid', [$request], null, null, $request['country']);
            if (empty($request['personal_number'])) {
                return [$user, ["personal_number" => "invalid.personal.number"]];
            }
        }

        $error_message = self::afterUserCreate($user, $request);
        if (!empty($error_message)) {
            return [$user, $error_message];
        }

        // add a user setting with the affiliate tracking code
        if (!empty($data->getAffiliatePostbackId())) {
            $user->setSetting('affiliate_postback_id', $data->getAffiliatePostbackId());
        }

        if(!isOneStep() && $user->getSetting('email_code_verified') !== 'yes'){
            $user->setSetting('email_code_verified', 'no');
        }

        $user->setSetting('calling_code', $request['country_prefix']);
        $user->setSetting('registration_in_progress', 1);
        $user->setTrackingEvent('partially-registered', ['triggered' => 'yes', 'model' => 'users', 'model_id' => $user->getId()]);
        $user->setTcVersion();
        $user->setPpVersion();

        // add a user setting with wpa promotion on step one
        if(isset($_SESSION['seasonal_promot'])) {
            $user->setSetting('seasonal_promot', $_SESSION['seasonal_promot']);
            unset($_SESSION['seasonal_promot']);
        }

        if (!empty($request['bonus_terms_and_conditions'])) {
            $user->setBtcVersion();
        }

        if (lic('isSportsbookEnabled')) {
            $user->setSportTcVersion();
        }

        $DBUserHandler->handleInternalUserVerification($user);
        $DBUserHandler->handleReturningPermanentSelfExcludedUser($user);

        if ($data->getHasVerifyExternalUrl()) {
            $res = lic('verifyRedirectStartWrapper', [$user], $user);
            if (is_string($res)) {
                return [$user, $res];
            } elseif (is_array($res)) {
                return [$user, null, $res];
            }
        }

        return [$user, null];
    }

    /**
     * Prepare session and data for registration step2
     *
     * @param $user
     * @param $password
     * @param string $email_code
     * @param bool $need_password
     * @return array
     */
    public static function initRegistrationStep2($user, $password, $email_code = '', $need_password = true)
    {
        // @Edwin - this is not unsetting the cookie, the correct way to do it is calling something like ""
        // you need to set the value to false and expiration date in the past, the problem here is that the registration form is in an iframe so it's not updating the main page cookie and removing the key.
        // can you figure out where the "remove cookie" function needs to be called, before in the main page scope, so it will clear the cookie?

        // Player is now considered registered for all intents and purposes with regards to the affiliate so we delete the cookie.
        unset($_COOKIE["referral_id"]);

        /** @var DBUserHandler $DBUserHandler */
        $DBUserHandler = phive('DBUserHandler');
        $country_prefix = phive('Cashier')->phoneFromIso($user->data['country']);

        // populate session with necessary info
        $_SESSION['rstep1']['email'] = $user->data['email'];
        $_SESSION['rstep1']['username'] = $DBUserHandler->getSetting('show_username') ? $user->data['username'] : $user->data['email'];
        $_SESSION['rstep1']['password'] = $password;
        $_SESSION['rstep1']['country'] = $user->data['country'];
        $_SESSION['rstep1']['mobile'] = str_replace($country_prefix, '', $user->data['mobile']);
        $_SESSION['rstep1']['country_prefix'] = $country_prefix;
        if ($DBUserHandler->getSetting('full_registration') === true) {
            $_SESSION['rstep1']['secemail'] = $user->data['email'];
        }
        $_SESSION['rstep1']['referring_friend'] = '';
        $_SESSION['rstep1']['user_id'] = $user->data['id'];
        $_SESSION['rstep1']['conditions'] = 1;
        $_SESSION['rstep1']['privacy'] = 1;
        $_SESSION['rstep1']['full_mobile'] = $user->data['mobile'];

        if (!$need_password) {
            $_SESSION['rstep1']['pr'] = 'no';  // Call this pr (password required) because a session variable called need_password might give away too much information
        }

        lic('rehydrateRegistrationSessionParameters', [$user], $user);

        if (empty($_SESSION['rstep2'])) {
            if ($user->hasSetting('nid_data')) {
                $lookup_data = json_decode($user->getSetting('nid_data'), true);
            } elseif (!empty($user->getNid())) {
                // the user has registered with nid so we can get the data for registration step 2
                $lookup_data = lic('lookupNid', [$user]);
                if (!empty($lookup_data)) {
                    $lookup_data = $lookup_data->getResponseData();
                }
            }

            if (lic('hasPrepopulatedStep2') && !empty($lookup_data)) {
                $_SESSION['rstep2'] = lic('getPersonLookupHandler', [], $user)->mapLookupData($lookup_data);
                $_SESSION['rstep2_disabled'] = $_SESSION['rstep2'];
            }
        }

        $registration_page = phive('DBUserHandler')->getSetting('registration_path_2', 'registration2');
        $is_mobile = phive()->isMobile();
        $url = $is_mobile ? '/mobile/register2/?redirect=true' : "/$registration_page/?redirect=true";
        if(cLang() != $user->getLang()) {
           $url = llink($url, $user->getLang());
        }

        // pass the email code as a GET parameter to the iframe
        if (!empty($email_code)) {
            $url .= "&email_code=$email_code";
        }

        return [
            "method" => $is_mobile ? "goTo" : "showRegistrationBox",
            "params" => [$url . '&uid=' . $user->getId()]
        ];
    }

    /**
     * Finalize registration step 2
     *
     * @param array $request_data
     * @return array [success_data, errors, action]
     *
     * @deprecated
     */
    public static function finalizeRegistrationStep2($request_data)
    {
        /** @var DBUserHandler $DBUserHandler */
        $DBUserHandler = phive('DBUserHandler');
        /** @var DBUser $user */
        $user = cu($_SESSION['rstep1']['user_id']);

        if ($DBUserHandler->hasFinishedRegistrationStep2($user)) {
            $DBUserHandler->logAction($user, 'Registration failed: user already registered', 'registration-failed', true, $user);
            return [['already_registered' => 'true']];
        }

        $user_id = $DBUserHandler->updateUser($user, $request_data);

        phive('MailHandler2')->sendWelcomeMail($user);

        // ARF checks on registration
        phiveApp(EventPublisherInterface::class)->fire('authentication', 'AuthenticationRegistrationEvent', [$user_id], 0);

        $DBUserHandler->setAjaxContext();
        // We need to mark the first session after registration as OTP validated.
        $DBUserHandler->markSessionAsOtpValidated();

        $login_session_key = $DBUserHandler->getSetting('show_username') === true ? 'username' : 'email';
        $has_nid = !empty($user->getNid()) || !empty(lic('getCachedExtVerResult', ['', 'nid']));

        if (lic('hasExtVerification') && $has_nid) {
            // We have a 100% verified NID by way of external verification so we login without password.
            [$user, $action] = $DBUserHandler->login($_SESSION['rstep1'][$login_session_key], null, false, false, true);
        } else {
            $need_password = empty($_SESSION['rstep1']['pr']) || $_SESSION['rstep1']['pr'] !== 'no';
            [$user, $action] = $DBUserHandler->login($_SESSION['rstep1'][$login_session_key], $_SESSION['rstep1']['password'], false, $need_password, true);
        }

        $user_in_progress = cu($user_id);

        if (!empty($user_in_progress)) {
            $user_in_progress->setSetting('registration_in_progress', 2);
        }

        if ($user == 'country') {
            $DBUserHandler->logAction($user_in_progress, 'Registration failed due to customer country mismatch', 'registration-failed', true, $user_in_progress);
            return [null, ['user' => t('register.err.blocked.reason.2')]];
        }

        if (is_string($user) && $action) {
            $DBUserHandler->logAction($user_in_progress, "Registration failed due to: {$action}", 'registration-failed', true, $user_in_progress);
            return [null, null, $action];
        }

        if ($user === 'external-self-excluded') {
            $DBUserHandler->logAction($user_in_progress, 'Registration failed due to external self exclusion', 'registration-failed', true, $user_in_progress);
            $error = t('errors.user.external_self_excluded');

            return [null, ['personal_number' => $error]];
        }

        if (!is_object($user)) {
            return [null, ['user' => 'no user']];
        }

        $user->setSetting('registration_in_progress', 3);

        [$user, $fraud_res] = $DBUserHandler->registrationEnd($user, false, false, $request_data);

        if ($fraud_res !== 'ok') {
            return [['fraud_msg' => $fraud_res]];
        }

        // We log a successful completion here so we know for sure that all the above logic has executed.
        $DBUserHandler->logAction($user, 'Finished registration successfully', 'registration', true, $user);

        /** @uses Licensed::addRecordToHistory() */
        lic('addRecordToHistory', [
            'user_registered',
            new UserRegistrationHistoryMessage([
                'user_id' => (int) $user_id,
                'event_timestamp' => time(),
            ])
        ], $user_id);

        // redirect to deposit box
        if (!empty($_SESSION['experian_msg'])) {
            return [['experian_msg' => $_SESSION['experian_msg'], 'llink' => llink('/')]];
        }

        return [];
    }

    /**
     * Finalize registration step 2
     *
     * @param array $request_data
     * @param \DBUser $user
     * @param array $rstep1
     * @param bool $isApi
     *
     * @return array [success_data, errors, action]
     */
    public static function finalizeRegistrationStep2V2($request_data, DBUser $user, array $rstep1, bool $isApi)
    {
        /** @var DBUserHandler $DBUserHandler */
        $DBUserHandler = phive('DBUserHandler');

        if ($DBUserHandler->hasFinishedRegistrationStep2($user) && !$user->hasSetting('registration_data_saved')) {
            $DBUserHandler->logAction($user, 'Registration failed: user already registered', 'registration-failed', true, $user);

            if ($isApi){
                return ['already-registered'];
            }

            return [['already_registered' => 'true']];
        }

        if ($user->hasSetting('registration_data_saved')) {
            $user->deleteSetting('registration_data_saved');
            $user_id = $user->getId();
        } else {
            $user_id = $DBUserHandler->updateUser($user, $request_data);
        }

        $user_in_progress = cu($user_id);

        $isPnpRegistration = (isset($request_data['loginCommonData']) && $request_data['loginCommonData']->isPnp())? true : false;
        if(!$isPnpRegistration){
            phive('MailHandler2')->sendWelcomeMail($user_in_progress);
        } else {
            $user->refresh();
        }

        // ARF checks on registration
        phiveApp(EventPublisherInterface::class)->fire('authentication', 'AuthenticationRegistrationEvent', [$user_id], 0);

        $DBUserHandler->setAjaxContext();
        // We need to mark the first session after registration as OTP validated.
        $DBUserHandler->markSessionAsOtpValidated();

        $has_nid = !empty($user->getNid()) || !empty(lic('getCachedExtVerResult', ['', 'nid']));


        $loginResponse = self::handleLogin($rstep1, $has_nid, $isApi, $request_data['loginCommonData'] ?? null);
        $action = false;
        if (count($loginResponse) == 2) {
            [$user, $action] = $loginResponse;
        } else if (count($loginResponse) == 1) {
            $user = $loginResponse[0];
        } else {
            $user = "";
        }

        if (!empty($user_in_progress)) {
            $user_in_progress->setSetting('registration_in_progress', 2);
        }

        if ($user == 'country') {
            $DBUserHandler->logAction($user_in_progress, 'Registration failed due to customer country mismatch', 'registration-failed', true, $user_in_progress);
            return [null, ['user' => t('register.err.blocked.reason.2')]];
        }

        if (is_string($user) && $action) {
            $action_log = json_encode($action);
            $DBUserHandler->logAction($user_in_progress, "Registration failed due to: {$action_log}", 'registration-failed', true, $user_in_progress);
            return [null, null, $action];
        }

        if ($user === 'external-self-excluded') {
            $DBUserHandler->logAction($user_in_progress, 'Registration failed due to external self exclusion', 'registration-failed', true, $user_in_progress);

            if ($isPnpRegistration || $isApi) {
                return ['external-self-excluded'];
            }

            $error = t('errors.user.external_self_excluded');

            return [null, ['personal_number' => $error]];
        }

        if (!is_object($user)) {
            $DBUserHandler->logAction($user_in_progress, "Registration failed, user is not an object", 'registration-failed', true, $user_in_progress);
            if ($isApi) {
                return ['no-user'];
            }

            return [null, ['user' => 'no user']];
        }

        // Handling edge case when user finished step 1 on jurisdiction that has external verification (e.g. SE),
        // but then dropped registration and finished it later on jurisdiction without external verification (e.g. GB).
        if (!lic('hasExtVerification', [], $user)) {
            $user->deleteExternalVerificationData();
        }

        // Handling edge case when user finished step 1 on jurisdiction with province (e.g. CA-ON),
        // but then dropped registration and finished it later on jurisdiction without province
        if (!licSetting('require_main_province', $user)) {
            $user->deleteSetting('main_province');
        }

        $user->setSetting('registration_in_progress', 3);

        [$user, $fraud_res] = $DBUserHandler->registrationEnd(
                $user,
                $user->hasSetting('similar_fraud'),
                $isApi,
                $request_data
        );

        if ($fraud_res !== 'ok') {
            if ($isApi) {
                return ['fraud-msg'];
            }

            return [['fraud_msg' => $fraud_res]];
        }

        // We log a successful completion here so we know for sure that all the above logic has executed.
        $DBUserHandler->logAction($user, 'Finished registration successfully', 'registration', true, $user);

        /** @uses Licensed::addRecordToHistory() */
        lic('addRecordToHistory', [
            'user_registered',
            new UserRegistrationHistoryMessage([
                'user_id' => (int) $user_id,
                'event_timestamp' => time(),
            ])
        ], $user_id);

        if ($isApi) {
            return [];
        }

        //redirect based on an action
        if($action){
            return [null, null, $action];
        }

        // redirect to deposit box
        if (!empty($_SESSION['experian_msg'])) {
            return [['experian_msg' => $_SESSION['experian_msg'], 'llink' => llink('/')]];
        }

        return [];
    }


    /**
     * This method is called throw migration flow. Resaving already existing user's data is evailable under migration flow
     *
     * @param $request_data
     * @param DBUser $user
     * @param array $rstep1
     * @param bool $isApi
     * @return array|array[]
     */
    public static function finalizeMigrationStep2V2($request_data, DBUser $user, array $rstep1, bool $isApi){
        /** @var DBUserHandler $DBUserHandler */
        $DBUserHandler = phive('DBUserHandler');

        $request_data['is_migration'] = 1;

        if ($user->hasSetting('registration_data_saved')) {
            $user->deleteSetting('registration_data_saved');
            $user_id = $user->getId();
        } else {
            $user_id = $DBUserHandler->updateUser($user, $request_data);
        }
        // We mark the user as migrated for CAON jurisdiction only
        self::setMigratedSettingForUser($user);

        $DBUserHandler->setAjaxContext();
        // We need to mark the first session after registration as OTP validated.
        $DBUserHandler->markSessionAsOtpValidated();

        $has_nid = !empty($user->getNid()) || !empty(lic('getCachedExtVerResult', ['', 'nid']));

        $loginResponse = self::handleLogin($rstep1, $has_nid, $isApi, $request_data['loginCommonData'] ?? null);
        if (count($loginResponse) == 2) {
            [$user, $action] = $loginResponse;
        } else if (count($loginResponse) == 1) {
            $user = $loginResponse[0];
        } else {
            $user = "";
        }

        if (!is_object($user)) {
            return [null, ['user' => 'no user']];
        }

        if ($isApi) {
            return [];
        }

        return [['migrated' => 'success']];
    }

    /**
     * Functionality to send Kafka message and set migrated setting at one go for a specific CAON user
     *
     * @param DBUser $user
     * @return void
     */
    public static function setMigratedSettingForUser(DBUser $user): void
    {
        $user_id = $user->getId();
        $user->setSetting('migrated', 1);
        phive('UserHandler')->logAction($user, 'Finished migration successfully', 'migration', true, $user);

        try {
            $args = [
                'user_id' => (int)$user_id,
                'event_timestamp' => time(),
            ];
            /** @uses Licensed::addRecordToHistory() */
            lic('addRecordToHistory', [
                'user_registered',
                new UserRegistrationHistoryMessage($args)
            ], $user_id);

        } catch (InvalidMessageDataException $exception) {
            phive('Logger')
                ->getLogger('history_message')
                ->error("Invalid message data exception on RegistrationHtml",
                    [
                        'report_type' => 'user_registered',
                        'args' => $args,
                        'validation_errors' => $exception->getErrors(),
                        'user_id' => $user_id,
                    ]);
        } catch (Exception $exception) {
            phive('Logger')
                ->getLogger('history_message')
                ->error($exception->getMessage(), $user_id);
        }
    }

    /**
     * @param array $rstep1
     * @param bool $has_nid
     * @param bool $isApi
     * @param \Laraphive\Domain\User\DataTransferObjects\LoginCommonData|null $loginCommonData
     *
     * @return array
     */
    private static function handleLogin(
        array $rstep1,
        bool $has_nid,
        bool $isApi,
        LoginCommonData $loginCommonData = null
    ): array {
        /** @var DBUserHandler $DBUserHandler */
        $DBUserHandler = phive('DBUserHandler');

        $login_session_key = $DBUserHandler->getSetting('show_username') === true ? 'username' : 'email';
        $username = $rstep1[$login_session_key];

        if (lic('hasExtVerification') && $has_nid) {
            $password = null;
            $need_password = false;
        } else {
            $password = $rstep1['password'];
            $need_password = empty($rstep1['pr']) || $rstep1['pr'] !== 'no';
        }

        if ($isApi) {
            return $DBUserHandler->loginCommon(
                $loginCommonData,
                null,
                null,
                false,
                false
            );
        } else {
            return $DBUserHandler->login(
                $username,
                $password,
                false,
                $need_password
            );
        }
    }

    /**
     * Returns a response which will trigger specific functions to be called on the FE
     *
     * @param string|array $method
     * @param array $params
     * @return array
     */
    public static function actionResponse($method, $params = [])
    {
        if (is_array($method)) {
            $action = $method;
            [$method, $params] = $action; // $action is ['method', [...params]]

            if (empty($method) && empty($params)) { // $action is [method=>'', params => [...]]
                $method = $action['method'];
                $params = $action['params'];
            }
        }

        return array_merge(["success" => true, "action" => compact('method', 'params')]);
    }

    /**
     * Returns the success response
     *
     * @param array $data
     * @return array
     */
    public static function successResponse($data = [])
    {
        return array_merge(["success" => true, "data" => $data]);
    }

    /**
     * Returns the error response, usually with error messages attached
     *
     * @param $messages
     * @param bool $translate
     * @return array
     */
    public static function failureResponse($messages, $translate = false): array
    {
        if (!is_array($messages)) {
            $messages = [$messages];
        }
        if ($translate) {
            foreach ($messages as $key => &$value) {
                $value = t($value);
            }
        }
        return array_merge(["success" => false, "messages" => $messages]);
    }

    /**
     * During registration we have to skip the province logic if the user is not from the country
     * jurisdiction applies for.
     *
     * @return void
     */
    public static function skipDefaultProvince($user = null)
    {
        $user = $user ?? cuRegistration();
        if (!empty($user)) {
            $country = $user->getCountry();
            if (strpos(phive('Localizer')->getDomainSetting('domain_iso_overwrite'), $country) === false) {
                phive('Licensed')->skipDomainIsoOverride();
                phive('Licensed')->forceCountry($country);
            }
        }
    }

    private static function getCountriesForDropdown(): array
    {
        $all_countries = phive('Cashier')->displayBankCountries(
            phive('Cashier')->getBankCountries('', true),  [], !phive()->isMobile()
        );
        $blocked_countries = phive('Config')->valAsArray('countries', 'block');

        $country_jurisdiction_map = phive('Licensed')->getSetting('country_by_jurisdiction_map');
        $non_mga_countries = array_filter($country_jurisdiction_map, function ($jurisdiction) {
            return $jurisdiction !== 'MGA';
        });

        return array_diff_key($all_countries, $blocked_countries, $non_mga_countries);
    }

    public static function checkCountryCaptcha($request, $country, bool $isApi)
    {
        $errors = $isApi ? '' : [];
        $deviceName = method_exists($request, 'getDeviceName') ? $request->getDeviceName() : '';

        if (phive('DBUserHandler')->getSetting('show_captcha_on_registration_country_mismatch')
            && phive('IpBlock')->getCountry() !== $country
            && !in_array($deviceName, ['app_android', 'app_iphone'])
        ) {
            if ($isApi){
                $captchaSessionKey =  phMget($request->getCaptchaSessionKey());
                if (!$captchaSessionKey) {
                    return 'registration.popup.captcha.label';
                }
            }else{
                if (!isset($_SESSION['registration_step1_captcha_validated'])) {
                    $errors['captcha'] = 'show';
                    if ($request['mitID']) {
                        $errors['additional_fields']['mitID'] = 1;
                    }
                }
            }
        }
        return $errors;
    }
}
