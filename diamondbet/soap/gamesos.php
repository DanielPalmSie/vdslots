<?php
//ob_start();
$smicro = microtime(true);
require_once __DIR__ . '/../../phive/api.php';
/*
if(phive("Config")->getValue('network-status', 'gamesos') == 'off')
  die("turned off");
*/
$sql 	= phive('SQL');
$gos 	= phive('Gamesos');

$test = $gos->getSetting('test');

$body = file_get_contents('php://input');

if($test === true)
  phive()->dumpTbl('gos_'.$_REQUEST['action'], array('body' => $body, 'req' => $_REQUEST));

//$sql->debug = true;
$result = $gos->exec($_POST, $_GET['action']);
//$sql->printDebug(true, false);

if($test === true)
  phive()->dumpTbl('gos_reply', $result);

$duration 	= microtime(true) - $smicro;

$insert = array(
  'duration' 	=> $duration, 
  'method' 	=> $_REQUEST['action'],
  'username' 	=> $GLOBALS['mg_username'],
  'host' => gethostname());

//$sql->insertArray('game_replies', $insert);
//if($duration > 1)
//  $sql->insertArray('slow_game_replies', $insert);

$output = ob_get_clean();

echo $result;


