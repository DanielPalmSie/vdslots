<?php
//ob_start();
$smicro = microtime(true);
require_once __DIR__ . '/../../phive/api.php';

/*
if(phive("Config")->getValue('network-status', 'qspin') == 'off')
  die("turned off");
*/

$sql 	= phive('SQL');
$qspin 	= phive('Qspin');

$test = $qspin->getSetting('test');

$body = file_get_contents('php://input');

if($test === true)
  phive()->dumpTbl('qspin_'.$_REQUEST['action'], array('body' => $body, 'req' => $_REQUEST));

//$sql->debug = true;
$result = $qspin->exec(json_decode($body, true), $_GET['action']);
//$sql->printDebug(true, false);

if($test === true)
  phive()->dumpTbl('qspin_reply', $result);

$duration 	= microtime(true) - $smicro;

$insert = array(
  'duration' => $duration,
  'token'    => 'QuickSpin',
  'method' 	 => $_REQUEST['action'],
  'username' => $GLOBALS['mg_username'],
  'host'     => gethostname());

if($qspin->isTest()){
    $sql->insertArray('game_replies', $insert);
}

phive('MicroGames')->logSlowGameReply($duration, $insert);

$output = ob_get_clean();

echo $result;


