<?php
require_once __DIR__ . '/../../phive.php';

if(!empty($_GET['lang'])){
  phive('Localizer')->setLanguage($_GET['lang']);
  phive('Localizer')->setNonSubLang($_GET['lang']);
}

/** @var DBUserHandler $uh */
$uh = phive('UserHandler');
$is_step_1 = !empty($_REQUEST['step1']) && empty($_SESSION['rstep2']);
phive('Licensed')->forceCountry($_REQUEST['country']);

if ($is_step_1) {
    trackRegistration();
}

/** @var CasinoBonuses $bh */
$bh = phive('Bonuses');

if(!empty($_REQUEST['get_username'])){
    if((int)$uh->countUsersWhere('username', $_POST['username']) > 0)
      $err['username'] = 'username.taken';
    if(!empty($err)){
      $result = array('status' => 'err', 'info' => $uh->errorZone($err, true));
    }else{
        cuPlSetAttr('username', $_POST['username']);
      phive('UserHandler')->reload($_POST['username']);
      $result = array('status' => 'ok');
    }

    die(json_encode($result));
}

if(!empty($_REQUEST['step1'])) {
    // validating step 1
    if(lic('hasExtVerification')){
        $fields = lic('validateRegFields');
    }

    if(empty($fields)){
        $fields = array('personal_number', 'email', 'password', 'country', 'mobile', 'country_prefix');
    }

    if ($uh->getSetting('show_username') == true) {
        $fields[] = 'username';
    }

    if($uh->getSetting('full_registration') === true && !empty($_POST['secemail']))
        $fields[] = 'secemail';

    $err = $uh->validateStep1($uh->getReqFields($fields), true, true);

    // todo-dip: reminder, this will be handled on step 2 validation
    if (($uh->showNationalId($_POST['country']) === true && lic('getDataFromNationalId')) || lic('regGetDataFromNationalId')) {
        $nid_res = lic('getDataFromNationalId', [$_POST['country'], $_POST['personal_number']]);
        if ($nid_res === false) {
            $err['personal_number'] = 'invalid.personal.number';
        }
    }

    if ($is_step_1 && !empty($err)) {
        trackRegistration($err);
    }

    if(empty($err)){
        foreach(array_merge($fields, array('privacy', 'conditions', 'referring_friend')) as $f) {
            $_SESSION['rstep1'][$f] = filter_input(INPUT_POST, $f, FILTER_SANITIZE_STRING);
        }

        // We've submitted step 1
        if(empty($_SESSION['rstep2'])) {

            if (lic('hasExtVerification')) {
                trackRegistration("Has external verification");
                $nid = lic('validateExtVerAndGetNid', [$_POST]);
                if (empty($nid)) {
                    die(json_encode(["status" => 'err', 'info' => $uh->errorZone(['personal_number' => 'invalid.personal.number'], true)]));
                }
                $str = "SELECT * FROM users WHERE nid = '$nid' AND country = '{$_POST['country']}' LIMIT 1";
                $other = phive('SQL')->shs()->loadAssoc($str);
                if (!empty($other)) {
                    trackRegistration("Tried to register with an existing nid");
                    // we have to login
                    $uh->ajax_context = !empty($_REQUEST['ajax_context']);

                    // We need to mark the first session after registration as OTP validated.
                    $uh->markSessionAsOtpValidated();
                    list($result, $action) = $uh->login($other['username'], null, false, false, true);

                    die(json_encode($uh->ajax_context
                        ? $uh->getLoginAjaxContextRes($result, $action)
                        : array("status" => 'ok', "reload" => true)
                    ));
                } else {
                    trackRegistration("Nid is not in the database");
                }
            }

            trackRegistration("User should be created next");
            if (empty($_SESSION['rstep1']['user_id'])) {

                $_POST['bonus_code'] = empty($_SESSION['affiliate']) ? $_REQUEST['bonus_code'] : $_SESSION['affiliate'];
                $user = $uh->createUser($_POST);

                $_SESSION['rstep1']['user_id'] = $user->getId();
            } else {
                $user_id = $_SESSION['rstep1']['user_id'];
                $user = cu($user_id);
                $uh->updateUser($user, $_POST, 'step1');
            }

            if(empty($user)){
                die();
            }

            // We handle ext verification here now that we have a freshly created user.
            if(lic('hasExtVerification', null, $user)){
                list($res, $err_message) = lic('extVerify', [$user, $_POST['personal_number']], $user);
                // Player timed out, tried to register double account or is underage if this is false.
                // TODO make something nicer with error message / popup or something perhaps? /Henrik
                if(!$res){
                    $GLOBALS['reg_result'] = array("status" => 'err', 'personal_number_error' => t($err_message));
                    die(json_encode($GLOBALS['reg_result']));
                }

                if (lic('hasPrepopulatedStep2')) {
                    $prepop_data = lic('getCachedExtVerResult', null, $user);
                    if(!empty($prepop_data)){
                        $_SESSION['rstep2'] = $prepop_data['result']['lookup_res'];
                        $_SESSION['rstep2_disabled'] = $_SESSION['rstep2'];
                    }
                    $already_prepopulated = true;
                }
            }

            if ((empty($already_prepopulated) || lic('regGetDataFromNationalId')) && !empty($nid_res)) {
                // We try to set the NID
                $res = $user->setNid($_POST['personal_number']);

                if ($nid_res['WasFound'] == true) {
                    $user->setSetting('nid_data', json_encode($nid_res));
                } else {
                    $user->addComment("Customer personal number was not found by Zignsec // system");
                }
                if (lic('hasPrepopulatedStep2')) {
                    $_SESSION['rstep2'] = $_SESSION['tmp_rstep2'];
                    $_SESSION['rstep2_disabled'] = $_SESSION['rstep2'];
                }

                unset($_SESSION['tmp_rstep2']);
            }

            $user->setSetting('email_code_verified', 'no');
            $user->setSetting('registration_in_progress', 1);

            // add a user setting with the affiliate tracking code
            // (can we place this somewhere else????)
            if(isset($_SESSION['affiliate_postback_id']) && !empty($_SESSION['affiliate_postback_id'])) {
                $user->setSetting('affiliate_postback_id', $_SESSION['affiliate_postback_id']);
            }

            $user->setTrackingEvent('partially-registered', ['triggered' => 'yes', 'model' => 'users', 'model_id' => $user->getId()]);
            $user->setTcVersion();
            $user->setPpVersion();
            if (lic('isSportsbookEnabled')) {
                $user->setSportTcVersion();
            }

            if(lic('hasExtVerification', null, $user)){
                // For now we hijack the whole process, not ideal, would be better to prevent subsequent sms / email code harrassment instead
                // because it's not correct, they have in fact verified neither mobile nor email. /Henrik
                $user->setSetting('sms_code_verified', 'yes');
                $user->setSetting('email_code_verified', 'yes');
            } else {
                phive('UserHandler')->sendEmailCode();
                phive('UserHandler')->sendSmsCode();
            }

            if(lic('permanentSelfExclusion') && !empty($user->getNid())) {
                // try to get the permanent self excluded account associated to $nid
                $associated_nid_account = lic('expiredPermanentExclusionAccount', [$user->getNid()], $user);
                if (!empty($associated_nid_account)) {
                    $user->setSetting('id_before_exclusion', $associated_nid_account->getId());
                    $associated_nid_account->setSetting('id_after_exclusion', $user->getId());
                }
            }
        } else if(!lic('hasExtVerification')) {
            // We're at step 2
            // First check if the email or mobile was changed, to determine if we need to send a new code
            $send_email_code = false;
            $send_sms_code = false;
            $user = cu($_SESSION['rstep1']['user_id']);

            if($_POST['email'] != $user->data['email']) {
                // email was changed, regenerate and resend email-verification code
                $send_email_code = true;
            }

            if($_POST['mobile'] != $user->data['mobile']) {
                // mobile was changed, regenerate and resend mobile-verification code
                $send_sms_code = true;
            }

            $user_id = $uh->updateUser($user, $_POST, 'step1');

            if($send_email_code) {
                phive('UserHandler')->sendEmailCode(true);
            }

            if($send_sms_code) {
                phive('UserHandler')->sendSmsCode(true);
            }
        }

        unset($_SESSION['registration_step1_captcha_validated']);
    }

    $GLOBALS['reg_result'] = empty($err) ? array("status" => 'ok') : array("status" => 'err', 'info' => $uh->errorZone($err, true), 'errors' => $err);

} else {
    // validating step 2
    $fields = $uh->getReqFields();

    $to_unset = array('personal_number', 'mobile', 'email', 'password', 'username', 'country');
    if(phive('UserHandler')->getSetting('full_registration') === true) {
        $to_unset = array_merge($to_unset, ['password2', 'secemail']);
    }
    // fields from step 1 are already saved, no need to validate them again
    foreach($to_unset as $field)
        unset($fields[$field]);

    $user = cu($_SESSION['rstep1']['user_id']);

    if(empty($user))
        die('no user');

    $err = $uh->validateStep2($fields, true, $user);
    $uh->checkDob($err, $user);

    // To prevent duplicate submissions of step2 by different browsers or devices, check here if step 2 was already submitted.
    // If the user has already finished step 2, we simply redirect to the homepage without logging the user in.
    if(!$uh->hasFinishedRegistrationStep2($user)) {

        if(!lic('hasExtVerification', null, $user)){
            if($_REQUEST['email_code'] == $user->getSetting('email_code') && !empty($user->getSetting('email_code'))) {
                $user->setSetting('email_code_verified', 'yes');
                //$user->setAttribute('verified_email', '1');
                unset($_SESSION['email_code_shown']);
                // Remove AML flag if it has been set
                phive()->pexec('Cashier/Fr', 'removeEmailAndPhoneCheckFlag', [$user->getId()]);
            } elseif($_REQUEST['email_code'] == $user->getSetting('sms_code') && !empty($user->getSetting('sms_code'))) {
                // HenrikSMS: validate SMS code here, seems like we don't need to send an SMS here, just show a message on the site with mosms.verify.success
                $user->setSetting('sms_code_verified', 'yes');
                $user->setSetting('email_code_verified', 'yes');
                $user->setAttr('verified_phone', 1);
                unset($_SESSION['sms_code_shown']);
                // Remove AML flag if it has been set
                phive()->pexec('Cashier/Fr', 'removeEmailAndPhoneCheckFlag', [$user->getId()]);
            } else {
                $err = array('email_code' => 'wrong.email.code');
                // ARF check specific for verification code
                phive()->pexec('Cashier/Fr', 'emailAndPhoneCheck', [$user->getId()]);
            }
        }

        if(empty($err)) {

            $user_id = $uh->updateUser($user, $_POST);

            phive('MailHandler2')->sendWelcomeMail($user);

            // ARF checks on registration
            phive()->pexec('Cashier/Arf', 'invoke', ['onRegistration', $user_id]);

            $need_password = true;
            if(!empty($_SESSION['rstep1']['pr']) && $_SESSION['rstep1']['pr'] == 'no') {
                $need_password = false;
            }

            // let's remember who our user was
            $uh->ajax_context = true;
            $login_session_key = $uh->getSetting('show_username') === true ? 'username' : 'email';
            $has_nid = !empty(lic('getCachedExtVerResult', ['', 'nid'])) || !empty($user->getNid());
            // We need to mark the first session after registration as OTP validated.
            $uh->markSessionAsOtpValidated();
            if(lic('hasExtVerification') && $has_nid){
                // We have a 100% verified NID by way of external verification so we login without password.
                list($user, $action) = $uh->login($_SESSION['rstep1'][$login_session_key], null, false, false);
            } else {
                list($user, $action) = $uh->login($_SESSION['rstep1'][$login_session_key], $_SESSION['rstep1']['password'], false, $need_password);
            }

            $user_in_progress = cu($user_id);
            $user_in_progress->setSetting('registration_in_progress', 2);

            if ($user == 'country') {
                $GLOBALS['reg_result'] = ["status" => 'err',  'info' => $uh->errorZone(['user' => 'blocked.reason.2'], true)];
                $uh->logAction($user_in_progress, 'Registration failed due to customer country mismatch', 'registration-failed', true, $user_in_progress);

            } elseif (is_string($user) && $action) {
                $reg_result = array("status" => 'err', 'response' => $action);
                $uh->logAction($user_in_progress, "Registration failed due to: {$action}", 'registration-failed', true, $user_in_progress);

            } elseif (is_object($user)) {
                $user->setSetting('registration_in_progress', 3);

                list($user, $fraud_res) = $uh->registrationEnd($user, false, false, $_POST);

                if ($fraud_res != 'ok') {
                    die(json_encode(['fraud_msg' => $fraud_res]));
                }

                $GLOBALS['reg_result'] = is_object($user) ? array("status" => $user_id) : array("status" => 'err', 'info' => $uh->errorZone(array('user' => 'db.error'), true));

                // redirect to deposit box
                if(!empty($_SESSION['experian_msg'])) {
                    $reg_result = array('experian_msg' => $_SESSION['experian_msg'], 'llink' => llink('/'));
                }

                if(!empty($reg_result)) {
                    $reg_result['status'] = 'ok';
                } else {
                    $reg_result = array("status" => 'ok');
                }

                $uh->logAction($user, 'Finished registration successfully', 'registration', true, $user);
            }
        }
    } else {
        $reg_result['status'] = 'already_registered';
        $uh->logAction($user, 'Registration failed: user already registered', 'registration-failed', true, $user);
    }

    // we complain when there are errors so reg_result will not be empty in those cases
    if (empty($reg_result)) {
        $reg_result = ['status' =>'ok', 'reload' => true];
    }

    // registration step2 is now complete, no errors found so the user can be logged in
    if (empty($err) && !empty($_REQUEST['uid'])) {
        $uh->ajax_context = phive()->isMobile();
        // We need to mark the first session after registration as OTP validated.
        $uh->markSessionAsOtpValidated();
        $uh->login($_SESSION['rstep1'][$login_session_key], null, false, false);
    }

    $GLOBALS['reg_result'] = empty($err) ? $reg_result : array("status" => 'err', 'info' => $uh->errorZone($err, true));
}

if(empty($_POST['reg_submit']))
  echo json_encode($GLOBALS['reg_result']);
