<?php
ob_start();
$smicro = microtime(true);
require_once __DIR__ . '/../../phive/api.php';
/*
if(phive("Config")->getValue('network-status', 'nyx') == 'off')
	die("turned off");
*/
//94.125.62.97

$nyx 	= phive('Nyx');
$sql 	= phive('SQL');
$test   = $nyx->getSetting('test'); 

if($test)
  phive()->dumpTbl('nyx_request', $_REQUEST);

//$sql->debug = true;


$result 	= $nyx->executeRequest($_REQUEST['request']);

if($test)
  phive()->dumpTbl('nyx_reply', $result);

//$sql->insertArray('sql_log', array('dump_txt' => $GLOBALS['HTTP_RAW_POST_DATA'], 'tag' => $qf->method));

$duration 	= microtime(true) - $smicro;

//if($duration > 1)
//	$sql->printDebug(true, true, $qf->method);

$insert = array(
    'duration' => $duration,
    'method'   => $_REQUEST['game_action'],
    'token'    => 'nyx',
    'mg_id'    => $GLOBALS['mg_id'],
    'username' => $GLOBALS['mg_username'],
    'host'     => gethostname());

if($nyx->isTest()){
    $sql->insertArray('game_replies', $insert);
}

phive('MicroGames')->logSlowGameReply($duration, $insert);

$output = ob_get_clean();


echo $result;
