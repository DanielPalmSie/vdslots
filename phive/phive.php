<?php

require_once __DIR__ . '/vendor/autoload.php';
include_once __DIR__ . '/api/BrandedConfig.php';
include_once __DIR__ . '/api/Phive.base.php';
require_once __DIR__ . '/api/data_sanitization.php';

phive()->addModule(new BrandedConfig(), 'BrandedConfig');
phive('BrandedConfig')->bootstrap(__DIR__);

// Sanitize $_GET variables to avoid XSS injection via $_GET and URLs(uses $_GET)
if (!isCli()) {
    if (function_exists('filter_input_array')) {
        $_GET = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);
        // $_POST = empty($_POST) ? [] : filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
    }

    // Make sure GET's on $_REQUEST are filtered aswell ($_REQUEST contains $_GET aswell)
    foreach ($_GET as $key => $value){
        $_GET[$key] = str_replace(['"', "`"], ["&quot;", "&grave;"], $value);
        //$_REQUEST[$key] = str_replace('"', "&quot;", $value);
    }

    foreach ($_POST as $key => $value){
        if(strpos($key, 'password') !== false){
            continue;
        }

        $_POST[$key] = str_replace("`", "&grave;", $value);
        $_REQUEST[$key] = str_replace("`", "&grave;", $value);
    }
}
$modules_file = phive('BrandedConfig')->getModulesFile();

if (file_exists($modules_file)) {
    include_once $modules_file;
}

foreach(['get' => $_GET, 'post' => $_POST, 'request' => $_REQUEST] as $action => $arr){

    $arr = phive('SQL')->sanitizeArray($arr, $action);

    switch($action){
        case 'get':
            $_GET = $arr;
            break;
        case 'post':
            $_POST = $arr;
            break;
        case 'request':
            $_REQUEST = $arr;
            break;
    }
}
phive()->install();
// LaraPhive requires modules to be loaded to access settings

if (!isset($GLOBALS['from-admin2'])) {
    include_once __DIR__ . '/initLaraPhive.php';
}

if(!isset($_SESSION) && !isCli()){
    phive()->sessionStart();

    $session_has_token = !!($_SESSION['token'] ?? false);
    SecurityApi::GenerateCsrf();

    // if CSRF token was generated - save it to Redis early to minimize token mismatch errors
    if (!$session_has_token) {
        phive()->persistSession();
    }
}

if (!isCli() && $_SERVER['REQUEST_METHOD'] == 'POST' && $_SESSION['user_id']) {
    $csrf_token = $_POST['csrf_token'] ?? $_POST['token'] ?? null;
    SecurityApi::CheckCsrf($csrf_token);
}

if(phive('SQL')->isOnDisabledNode($_SESSION['user_id'])){
    phive('UserHandler')->logout(t('chat.offline.message.subtitle'));
}

// player is under $_SESSION['mg_id'] while admin can be under $_SESSION['user_id']
if (!empty($_SESSION['mg_id']) && !isCli() && phive('SQL')->isOnDisabledNode($_SESSION['mg_id'])) {
    return phive('DBUserHandler')->logout();
}

if(!isCli()){
    if(!phive()->isEmpty($_SERVER['HTTP_LOGINTOKEN'])){
        $user = phive('UserHandler')->loginWithToken($_SERVER['HTTP_LOGINTOKEN']);
    }else{
        phive('IpBlock')->ipLimit();
        $user = cu($_SESSION['user_id']);
    }
}

if(!isCli() &&
   phive()->moduleExists('Currencer') &&
   phive()->moduleExists('Redirect') &&
   phive("Currencer")->getSetting('multi_currency') === true &&
   phive()->moduleExists('QuickFire')){

    phive("Currencer")->setSessionCur($_REQUEST['site_currency']);

    if(!empty($_REQUEST['site_currency']))
        phive("Redirect")->toBaseRef();
}

phive()->htmlQuotes($_POST, $user);
phive()->htmlQuotes($_GET, $user);
phive()->htmlQuotes($_REQUEST, $user);

function handle_fatal_errors(){
  if(isCli())
    return;
  $res = error_get_last();
  if (isset($res) && $res['type'] == E_ERROR){ // fatal error
      $data = phive('Logger')->clearSensitiveFields($_REQUEST);
      error_log("Fatal Error request data: ". json_encode($data));
  }
}

register_shutdown_function('handle_fatal_errors');

if (!empty($_GET['auth_token'])) {
    $isLogged = phive('UserHandler')->loginWithAuthToken(urldecode($_GET['auth_token']));
    if (!$isLogged) {
        http_response_code(401);
        exit();
    }
}
