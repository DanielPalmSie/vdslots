<?php
require_once __DIR__ . '/../../../phive.php';
require_once __DIR__ . '/../../../../phive/modules/DBUserHandler/Registration/RegistrationHtml.php';

phive('Localizer')->setFromReq();

/** @var DBUser $user */
$user = phive('Licensed')->doLicense("NL", 'verifyRedirectEnd', [$_GET['action']]);
/** @var DBUserHandler $uh */
$uh = phive('UserHandler');
$uh->ajax_context = true;
$args = [];
$url = '/';

if (is_object($user)) {
    switch ($_GET['action']) {
        case RegistrationHtml::CONTEXT_REGISTRATION:
            return phive('Redirect')->to('/', cLang(), false, '302 Found', [], ['show_reg_step_2' => 1]);
        case 'login':
            list($login_res, $action) = $uh->login($user->getUsername(), null, true, false);

            $_SESSION['login_res'] = $login_res;
            $_SESSION['login_action'] = $action;

            return phive('Redirect')->to('/', cLang(), false, '302 Found', [], []);
    }
}

return phive('Redirect')->to($url, cLang(), true, '302 Found', [], [
    'show_msg' => is_string($user) ? $user : "external.service.error.html"
]);

