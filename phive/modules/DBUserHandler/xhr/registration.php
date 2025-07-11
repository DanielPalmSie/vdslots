<?php

use FormerLibrary\CSRF\CsrfToken;
use FormerLibrary\CSRF\Exceptions\InvalidCsrfToken;
use Laraphive\Domain\User\Actions\Steps\DataTransferObjects\FinalizeRegistrationStep1Data;

require_once __DIR__ . '/../../../phive.php';
require_once __DIR__ . '/../../../../phive/modules/DBUserHandler/Registration/RegistrationHtml.php';

RegistrationHtml::skipDefaultProvince();
$GLOBALS['no-session-refresh'] = true;

/** @var DBUserHandler $uh */
$DBUserHandler = phive('DBUserHandler');

$request = $_REQUEST;
$response = [];

if (!empty($request['lang'])) {
    phive('Localizer')->setLanguage($request['lang']);
    phive('Localizer')->setNonSubLang($request['lang']);
}

if (!empty($request['bonus_code']) && empty($_SESSION['affiliate'])) {
    $_SESSION['affiliate'] = $request['bonus_code'];
}

if (empty($request['preferred_lang'])) {
    $request['preferred_lang'] = 'en';
}
licHtml('registration', cuRegistration());
phive('Localizer')->setFromReq();
phive('Licensed')->forceCountry($request['country']);

switch ($_REQUEST['step']) {
    case 1:
        trackRegistration();

        if(isLogged()){
            $response =  RegistrationHtml::actionResponse('gotoLang', ['/']);
            break;
        }

        try {
            (new CsrfToken())->check($request['csrf_token'] ?? '');
        } catch (InvalidCsrfToken $exception) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 405 Method Not Allowed');
            exit;
        }

        $registrationDisabled = phive('DBUserHandler')->getSetting('registration_disabled')
            || lic('getLicSetting', ['registration_disabled']);

        if($registrationDisabled){
            $ip = remIp();

            if(!isWhitelistedIp($ip)){
                header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
                exit(t('register.err.forbidden'));
            }
        }

        if (isPNP()) {
            phive('Logger')->getLogger('paynplay')->error('registration-blocked-paynplay', remIp());
            header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden');
            exit;
        }

        [$errors, $postedFieldsKeys] = RegistrationHtml::validateStep1FieldsV2(
            $request,
            $request['country'],
            true,
            false
        );

        $maintenance = lic('getLicSetting', ['scheduled_maintenance']);
        $is_maintenance_mode = !empty($maintenance) && $maintenance['enabled'];

        if ($is_maintenance_mode) {
            break;
        }

        foreach($postedFieldsKeys as $f) {
            $_SESSION['rstep1'][$f] = $_POST[$f];
        }

        $captcha_errors = RegistrationHtml::checkCountryCaptcha($request, $_REQUEST['country'], false);
        $errors = array_merge($errors, $captcha_errors);

        if (!empty($errors)) {
            trackRegistration($errors);
            $response = RegistrationHtml::failureResponse($errors);
            break;
        }

        if (isBankIdMode() && empty($_SESSION['cur_req_id'])) {
            $_SESSION['cur_req_id'] = $request['cur_req_id'];
        }

        if (RegistrationHtml::intermediaryStepRequired($request)) {

            $context = phive('DBUserHandler')->getRegistrationContext($request);
            $response = RegistrationHtml::actionResponse(lic('initIntermediaryStep', [$context, "", false, $request['country']]));

            break;
        }

        $finalizeRegistrationStep1Data = createFinalizeRegistrationStep1Data();

        [$user, $errors, $response] = RegistrationHtml::finalizeRegistrationStep1V2(
            $request,
            $finalizeRegistrationStep1Data
        );

        if (is_null($errors) && !is_null($user)) {
                $_SESSION['rstep1']['user_id'] = $user->getId();
        }

        if (!empty($response)) {
            break;
        }

        if (!empty($errors && empty($errors['login_context']))) {
            $response = RegistrationHtml::failureResponse($errors, true);
            break;
        }

        if (RegistrationHtml::communicationChannelVerificationRequired($request)) {
            $context = phive('DBUserHandler')->getRegistrationContext($request);
            $response = RegistrationHtml::actionResponse(lic('initIntermediaryStep', [$context, "", false, $request['country']]));

            break;
        }

        if (isBankIdMode()) {
            $context = phive('DBUserHandler')->getRegistrationContext($request);
            $response = RegistrationHtml::actionResponse(lic('initIntermediaryStep', [$context, "", false, $request['country']]));

            break;
        }

        (new CsrfToken())->clearToken();

        if (isBankIdMode() && !empty($errors['login_context'])) {
            $response = RegistrationHtml::actionResponse('gotoLang', ['/account']);
            break;
        }

        $response = RegistrationHtml::successResponse();
        break;
    case 2:

        $user = cuRegistration();

        if (isset($request['zipcode'])) {
            $request['zipcode'] = lic('formatZipcode', [$request['zipcode']]);
        }

        $errors = RegistrationHtml::validateStep2FieldsV2($request, $user);

        if (!empty($errors)) {
            $response = RegistrationHtml::failureResponse($errors);
            break;
        }

        $rstep1 = $_SESSION['rstep1'];
        $migration = $_SESSION['rstep2']['migration'];

        $deposit_blocked = 0;
        if($user->getSetting('deposit_block')){
            $deposit_blocked = 1;
        }

        $errors = lic('openAccountNaturalPerson', [cuRegistration(), false, $request]);

        if (isset($errors['idscan']) && $errors['idscan'] == 'check') {
            $is_mobile = phive()->isMobile();

            //based on this on successful IDScan verification we are removing specific KYC blocks
            // that were set in openAccountNaturalPerson while registration or migration
            if($migration){
                $request['migration'] = $migration;
                //if previously deposit blocked no need to remove this block on migration
                $request['deposit_blocked'] = $deposit_blocked;
            }

            phive('IdScan')->setStep2UserData($request, $user, $rstep1);

            $hashed_uid = $user->getSetting('hashed_uuid');

            $action = [
                "method" => $is_mobile ? "goTo" : "showRegistrationBox",
                "params" => $is_mobile ? ["/mobile/register-idscan/?uid=$hashed_uid"] : ["/registration-idscan/?uid=$hashed_uid"]
            ];

            unset($_SESSION['rstep1']);

            // Send registration postback to Raventrack
            phive()->postBackToRaventrack("registration", $user);

            $response = RegistrationHtml::actionResponse($action);
            break;
        }

        if(!empty($errors)) {
            $response = RegistrationHtml::failureResponse($errors);
            break;
        }

        if ($migration){
            list($data, $errors, $action) = RegistrationHtml::finalizeMigrationStep2V2($request, $user, $rstep1, false);
        } else {
            list($data, $errors, $action) = RegistrationHtml::finalizeRegistrationStep2V2($request, $user, $rstep1, false);
        }


        if (!empty($errors)) {
            $response = RegistrationHtml::failureResponse($errors);
            break;
        }

        if (!empty($action)) {
            $response = RegistrationHtml::actionResponse($action);
            break;
        }

        $response = RegistrationHtml::successResponse($data);
        break;
    default:
        break;
}

if (isset($response) && $response['success'] !== true) {
    logToActionsAndReturn($response);
}

die(json_encode($response));

function createFinalizeRegistrationStep1Data(): FinalizeRegistrationStep1Data
{
    return FinalizeRegistrationStep1Data::fromArray([
      'userId'                  => intval($_SESSION['rstep1']['user_id']),
      'affiliate'               => $_SESSION['affiliate'] ?? "",
      'affiliate_postback_id'   => intval($_SESSION['affiliate_postback_id']),
      'has_verify_external_url' => !empty($_POST['verifyExternalUrl']),
    ]);
}
