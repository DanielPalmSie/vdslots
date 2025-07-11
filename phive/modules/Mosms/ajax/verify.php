<?php
require_once __DIR__ . '/../../../phive.php';
require_once __DIR__.'/../../../html/display_base_diamondbet.php';

phive('Localizer')->ajaxSetLang();
$mosms = phive('Mosms');
$err   = '';

switch($_REQUEST['action']){
    case 'send-sms':
        $result = $mosms->validateAndSendSms(false);
        if($result !== 0){
            $err = $mosms->getMsg($result);
        } else {
            $_SESSION['sms_tries']++;
        }
        break;
    case 'validate-sms':
        $result = $mosms->validateSms();
        if($result){
            $_SESSION['sms_ok'] = true;
        } else {
            $err = t('mosms.wrong.code');
        }
        break;
}

echo json_encode(['success' => empty($err), 'error' => $err]);
