<?php
$smicro = microtime(true);
require_once __DIR__ . '/../../phive/api.php';

if(phive("Config")->getValue('network-status', 'redtiger') == 'off'){
    die("turned off");
}

$redTiger       = phive('Redtiger');
$response       = null;
$apiKey         = null;
$refundFound    = false;

$mode = $redTiger->getSetting('mode');
$user = $redTiger->getSetting($mode."_api_user"); 
$pwd  = $redTiger->getSetting($mode."_api_pwd"); 


header('Content-Type:application/json;charset=utf-8');

if ($redTiger->getSetting('debug', false)) {
    phive()->dumpTbl('redtiger-request-auth', ['http_user' => $_SERVER['PHP_AUTH_USER'], 'http_pw' => $_SERVER['PHP_AUTH_PW']]);
}

//NOTE, we need a properly base64 encoded user:password here!!
//https://en.wikipedia.org/wiki/Basic_access_authentication
if($mode == 'production' && ($_SERVER['PHP_AUTH_USER'] != $user || $_SERVER['PHP_AUTH_PW'] != $pwd)){
   echo json_encode($redTiger->errorMessage('Invalid API key', 108));
   exit();
}

//When using apache the rewriting doesn't seem to preserve the http body?
$requestData = json_decode(file_get_contents('php://input'));
if ($redTiger->getSetting('debug', false)) {
    phive()->dumpTbl('redtiger-request-body', $requestData);
}
echo json_encode($redTiger->execute($requestData));

$sql = phive('SQL');
$duration = microtime(true) - $smicro;
$insert = [
    'duration' => $duration,
    'method' => $_GET['action'].'/'.$_GET['param'].'/redtiger',
    'username' => $GLOBALS['mg_username'],
    'token' => 'redtiger',
    'host' => gethostname()
];

//$sql->insertArray('game_replies', $insert);

//if($duration > 1) {
//    $sql->insertArray('slow_game_replies', $insert);
//}

// log responses if we're in debug mode
// TODO: Disabled this since on phive: mrvegas-mga does not support this method. Once this is resolved we will re-add it
// $redTiger->logMessage('responses', $response);
