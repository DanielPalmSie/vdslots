<?php
//ob_start();
$smicro = microtime(true);
require_once __DIR__ . '/../../phive/api.php';
/*
if(phive("Config")->getValue('network-status', 'yggdrasil') == 'off')
	die("turned off");
*/
$sql 	= phive('SQL');
$ygg 	= phive('Yggdrasil');

$test = $ygg->getSetting('test');

//$req = file_get_contents("php://input");
if($test === true)
  phive()->dumpTbl('ygg_'.$_REQUEST['action'], $_REQUEST);
//phive()->dumpTbl('ygg_ip', $_SERVER['REMOTE_ADDR']);
//$sql->debug = true;
$result = $ygg->execute($_REQUEST['action']);
//$sql->printDebug(true, false, 'yggdrasil_'.$_REQUEST['action']);
if($test === true)
  phive()->dumpTbl('ygg_reply', $result);





//phive()->dumpTbl('sheriff_req', "json: $req, action: ".$_REQUEST['action']);


//$sql->insertArray('sql_log', array('dump_txt' => $GLOBALS['HTTP_RAW_POST_DATA'], 'tag' => $qf->method));

$duration 	= microtime(true) - $smicro;

//if($duration > 1)
//	$sql->printDebug(true, true, $qf->method);

$insert = array(
	'duration' 	=> $duration, 
	'method' 	=> $_REQUEST['action'],
	'username' 	=> $GLOBALS['mg_username'],
    'token' => 'yggdrasil',
    'host' => gethostname());

if($ygg->isTest()){
    $sql->insertArray('game_replies', $insert);
}

phive('MicroGames')->logSlowGameReply($duration, $insert);

$output = ob_get_clean();

header('Content-Length: '.mb_strlen($result));
//echo $output;
echo $result;

//phive()->dumpTbl('sheriff_answer', $result);
//phive()->dumpTbl('sheriff_output', $output);

