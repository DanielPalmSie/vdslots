<?php

$body = file_get_contents('php://input');

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
//$sql->debug = true;

if($qf->getSetting('test') === true)
  phive()->dumpTbl('ggi_req', $body);

//phive('SQL')->debug = true;
$result 	= $qf->ggiExecuteRequest($body);

if($qf->getSetting('test') === true)
  phive()->dumpTbl('ggi_reply', $result);

//phive('SQL')->printDebug(true, false, 'ggi');
//$sql->insertArray('sql_log', array('dump_txt' => $GLOBALS['HTTP_RAW_POST_DATA'], 'tag' => $qf->method));
$duration 	= microtime(true) - $smicro;

//if($duration > 1)
//	$sql->printDebug(true, true, $qf->method);


if(empty($qf->game))
{
    $qf->game = $qf->getGameByRef($qf->gref);
}

$insert = array(
	'duration' 	=> $duration, 
	'method' 	=> $qf->method,
	'mg_id'		=> $GLOBALS['mg_id'],
	'token'		=> $qf->game['operator'],
	'username' 	=> $GLOBALS['mg_username'],
    'host' => gethostname());

if($qf->isTest()){
    $sql->insertArray('game_replies', $insert);
}

phive('MicroGames')->logSlowGameReply($duration, $insert);

$output = ob_get_clean();

//echo $output;
echo $result;
