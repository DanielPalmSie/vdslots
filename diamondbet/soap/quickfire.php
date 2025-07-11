<?php
$body = file_get_contents("php://input");

if(empty($body))
  die("no xml");

ob_start();
$smicro = microtime(true);
require_once __DIR__ . '/../../phive/api.php';
$qf 		= phive('QuickFire');
$sql 		= phive('SQL');
/*
if(phive("Config")->getValue('network-status', 'microgaming') == 'off')
	die("turned off");
*/

if($qf->getSetting('test_log') === true)
  phive()->dumpTbl('quickfire_body', $body);

$result 	= $qf->ggiExecuteRequest($body);

if($qf->getSetting('test_log') === true)
  phive()->dumpTbl('quickfire_answer', $result);


//$sql->insertArray('sql_log', array('dump_txt' => $GLOBALS['HTTP_RAW_POST_DATA'], 'tag' => $qf->method));

$duration 	= microtime(true) - $smicro;

//if($duration > 1)
//	$sql->printDebug(true, true, $qf->method);

$insert = array(
	'duration' 	=> $duration, 
	'method' 	=> $qf->method,
	'mg_id'		=> $GLOBALS['mg_id'],
	'token'		=> $qf->token['token'],
	'username' 	=> $GLOBALS['mg_username'],
    'host' => gethostname());

if($qf->isTest()){
    $sql->insertArray('game_replies', $insert);
}

phive('MicroGames')->logSlowGameReply($duration, $insert);

$output = ob_get_clean();

//phive()->dumpTbl('quickfire_output', $output);
echo $result;
