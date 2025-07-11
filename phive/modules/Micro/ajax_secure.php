<?php

$GLOBALS['no-session-refresh'] = true;

require_once __DIR__ . '/../../phive.php';
require_once __DIR__ . '/../../../diamondbet/html/display.php';

phive('Localizer')->setFromReq();
phive('Licensed')->forceCountry($_REQUEST['iso'] ?? $_REQUEST['country']);

$cur_player = cuPl();

if (phive()->getSetting('log_micro_ajax', false) === true) {
    phive()->dumpTbl('micro_ajax', [remIp(), $_REQUEST], $cur_player);
}

$csrf_token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
SecurityApi::CheckCsrf($csrf_token, true);

$login_actions = ['token_login', 'otp-login', 'uname-pwd-login'];
$is_login_action = in_array($_REQUEST['action'], $login_actions, true);

if ($is_login_action && limitAttempts('csrf:' . $csrf_token, '', null, false)) {
    die(getMockedLoginResponse());
}

switch($_REQUEST['action']) {
    case 'token_login':
    case 'otp-login':
    case 'uname-pwd-login':
        /** @var DBUserHandler $uh */
        $uh = phive('UserHandler');
        $uh->ajax_context = true;
        $maintenance = lic('getLicSetting', ['scheduled_maintenance']);

        if (is_array($maintenance) && $maintenance['enabled']) {
            echo json_encode($uh->getLoginAjaxContextRes(false));
            exit;
        }

        $action = $_REQUEST['action'] ?? '';
        $too_many_attempts = false;
        $error_msg = '';

        switch ($action) {
            case 'otp-login':
                $too_many_attempts = limitAttempts($action, $_POST['otp']);
                $error_msg = 'otp.toomanyattempts';
                break;

            case 'uname-pwd-login':
                if (isset($_REQUEST['login_captcha'])) {
                    $allowed_captcha_attempt = $uh->getSetting('allowed_captcha_attempt', 5);
                    if (!empty($allowed_captcha_attempt)) {
                        $too_many_attempts = limitAttempts($action . 'captcha', $_REQUEST['login_captcha'], $allowed_captcha_attempt);
                        $error_msg = 'captcha.toomanyattempts';
                    }
                } else {
                    $too_many_attempts = limitAttempts($action, $_REQUEST['username'], 6);
                    $error_msg = 'uname-pwd.toomanyattempts';
                }
                break;
        }

        if (isset($_REQUEST['login_captcha']) && empty($_REQUEST['login_captcha'])) {
            $error_msg = 'captcha.err';
            echo json_encode($uh->getLoginAjaxContextRes($error_msg));
            exit;
        }

        if ($too_many_attempts) {
            $remip = (isPNP() && $_SESSION['rstep1']['pnp_ip']) ? $_SESSION['rstep1']['pnp_ip'] : remIp();

            if (!isWhitelistedIp($remip)) {
                echo json_encode($uh->getLoginAjaxContextRes($error_msg));
                exit;
            }
        }

        if ($action === 'token_login') {
            list($login_res, $login_action) = $uh->loginWithToken($_REQUEST['token']);
        } else {
            if ($action === 'uname-pwd-login' && is_numeric($_POST['username'])) {
                echo json_encode($uh->getLoginAjaxContextRes(false));
                exit;
            }

            if (!empty($_POST['import_from_brand'])) {
                $copy_res = phive('Distributed')->createUserFromRemoteBrand($_POST['username'], $uh->encryptPassword($_POST['password']));
                if ($copy_res === false) {
                    echo json_encode($uh->getLoginAjaxContextRes('import_failed'));
                    exit;
                }
                $user = cu($_POST['username']);
                linker()->setDocumentStatusSetting($user);
                linker()->syncRemoteSettingsAfterRegistration($user);
                linker()->syncRgLimitsOnRegistration($user);
            }

            list($login_res, $login_action) = $uh->login($_POST['username'], $_POST['password'], true);
        }

        if ($login_res instanceof User) {
            lic('onPasswordLogin', [$login_res]);
        }

        echo json_encode($uh->getLoginAjaxContextRes($login_res, $login_action));
        exit;
    case 'send-sms-code':
        if (limitAttempts('send-sms-code')) {
            echo 'toomanyattempts';
            break;
        }

        $regenerate = !!$_REQUEST['regenerate'];

        phive('UserHandler')->sendSmsCode($regenerate);
        break;
    case 'check-sms-code':
        if (limitAttempts('code-verification')) {
            echo 'toomanyattempts';
            break;
        }

        $user = cu();
        if(empty($user)) {
            die('no user');
        }

        $code = $_REQUEST['code'];
        if ($code !== $user->getSetting('sms_code')) {
            echo json_encode([
                'status' => 'error',
                'message' => t('wrong.email.code')
            ]);
            break;
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'ok'
        ]);
        break;
    case 'check-email-code':
        if (limitAttempts('code-verification')) {
            echo 'toomanyattempts';
            break;
        }

        $user = cu();
        if(empty($user)) {
            die('no user');
        }

        $code = $_REQUEST['code'];
        if ($code !== $user->getSetting('email_code')) {
            echo json_encode([
                'status' => 'error',
                'message' => t('wrong.email.code')
            ]);
            break;
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'ok'
        ]);
        break;

    case 'obsolete-session':
        $_SESSION['OBSOLETE'] = true;
        break;

    case 'log-action':
        if (limitAttempts('log-action')) {
            die('Too many attempts');
        }

        $user = $cur_player;
        if (!$user) {
            $user = cuRegistration();
        }

        if (!$user) {
            die('No user');
        }

        $tag = phive('SQL')->escape($_REQUEST['tag'], false);
        $data = phive('SQL')->escape(json_encode($_REQUEST['data']), false);
        $tag_prefix = 'frontend';

        phive('DBUserHandler')->logAction(
            $user->getId(),
            $data,
            $tag ? ($tag_prefix . '_' . $tag) : $tag_prefix,
        );

        break;
}


/**
 * Generates a mocked failed login response.
 * Used in case of enumeration attack (many requests with same CSRF token in short time period).
 *
 * @return string
 */
function getMockedLoginResponse(): string
{
    return json_encode([
        'success' => false,
        'login_context' => true,
        'result' => [
            'msg' => t2('blocked.login_fail_attempts.html', ['attempts' => random_int(1, 4)]),
            'action' => null,
            'redirect_url' => null
        ]
    ]);
}
