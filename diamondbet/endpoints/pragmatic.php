<?php
/**
 * Created by PhpStorm.
 * User: pezo
 * Date: 2016.07.11.
 * Time: 7:49
 */

require_once __DIR__ . '/../../phive/phive.php';

if(phive("Config")->getValue('network-status', 'pragmatic') == 'off')
    die("turned off");

$action = $_GET['action'];
$pragmatic = phive('Pragmatic');

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode($pragmatic->errorMessage(10));
    die;
}


header('Content-Type:application/json;charset=utf-8');

if(!empty($action)){

    // Please keep in mind, $requestBody is an array
    // They send application/x-www-form-urlencoded POST requests

    $requestBody = $_POST;
    if($pragmatic->getSetting('debug')){
        $log = $requestBody;
        $log['action'] = $action;
        file_put_contents('/tmp/pragmatic-request.log', print_r(json_encode($log), true)."\n", FILE_APPEND);
    }

    switch($action){
        case 'authenticate.html':
            $response = json_encode($pragmatic->actionAuthenticate($requestBody));
            echo $response;
            break;
        case 'balance.html':
            $response = json_encode($pragmatic->actionBalance($requestBody));
            echo $response;
            break;
        case 'bet.html':
            $response = json_encode($pragmatic->actionBet($requestBody));
            echo $response;
            break;
        case 'endRound.html':
            $response = json_encode($pragmatic->actionEndRound($requestBody));
            echo $response;
            break;
        case 'result.html':
            $response = json_encode($pragmatic->actionResult($requestBody));
            echo $response;
            break;
        case 'bonusWin.html':
            $response = json_encode($pragmatic->actionBonusWin($requestBody));
            echo $response;
            break;
        case 'refund.html':
            $response = json_encode($pragmatic->actionRefund($requestBody));
            echo $response;
            break;
        default:
            echo json_encode(['message' => 'Unknown error, maybee endpoint does not exists']);
            break;
    }
    if($pragmatic->getSetting('debug')){
        $log = json_decode($response, true);
        $log['action'] = $action;
        file_put_contents('/tmp/pragmatic-response.log', print_r(json_encode($log), true)."\n", FILE_APPEND);
    }
}
exit();
