<?php
ob_start();
$smicro = microtime(true);
require_once __DIR__ . '/../../phive/api.php';
$bsg 		= phive('Bsg');
$sql 		= phive('SQL');
/*
if(phive("Config")->getValue('network-status', 'bsg') == 'off')
	die("turned off");
*/

$result 	= $bsg->executeRequest($_REQUEST['action']);

//$sql->insertArray('sql_log', array('dump_txt' => $GLOBALS['HTTP_RAW_POST_DATA'], 'tag' => $qf->method));

$duration 	= microtime(true) - $smicro;

//if($duration > 1)
//	$sql->printDebug(true, true, $qf->method);

$insert = array(
    'duration' => $duration,
    'method'   => $_REQUEST['action'],
    'token'    => 'betsoft',
    'mg_id'    => $GLOBALS['mg_id'],
    'username' => $GLOBALS['mg_username'],
    'host'     => gethostname());

if($bsg->isTest()){
    $sql->insertArray('game_replies', $insert);
}

phive('MicroGames')->logSlowGameReply($duration, $insert);

$output = ob_get_clean();

echo $result;
