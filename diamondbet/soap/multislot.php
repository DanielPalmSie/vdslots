<?php
ob_start();
$smicro = microtime(true);
require_once __DIR__ . '/../../phive/api.php';
/*
if(phive("Config")->getValue('network-status', 'multislot') == 'off')
	die("turned off");
*/

$multislot 	= phive('Multislot');
$sql 		= phive('SQL');

//$sql->debug = true;

$result 	= $multislot->execute($_REQUEST);

//$sql->printDebug(true, false, 'sheriff');

//$sql->insertArray('sql_log', array('dump_txt' => $GLOBALS['HTTP_RAW_POST_DATA'], 'tag' => $qf->method));

$duration 	= microtime(true) - $smicro;

//if($duration > 1)
//	$sql->printDebug(true, true, $qf->method);

$insert = array(
    'duration' => $duration,
    'method'   => $_REQUEST['action'],
    'token'    => 'multislot',
    'mg_id'    => $GLOBALS['mg_id'],
    'username' => $GLOBALS['mg_username'],
    'host'     => gethostname());

if($multislot->isTest()){
    $sql->insertArray('game_replies', $insert);
}

phive('MicroGames')->logSlowGameReply($duration, $insert);

$output = ob_get_clean();

echo $result;
