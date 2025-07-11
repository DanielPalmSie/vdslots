<?php
//ob_start();
sleep(5);
$smicro = microtime(true);
require_once __DIR__ . '/../../phive/phive.php';

if(phive("Config")->getValue('network-status', 'panda') == 'off')
  die("turned off");

$sql 	= phive('SQL');
$panda 	= phive('Panda');

$req = file_get_contents("php://input");
phive()->dumpTbl('panda', $req);
//phive()->dumpTbl('ygg_ip', $_SERVER['REMOTE_ADDR']);
//$sql->debug = true;
$result = $panda->execute($req);
//$sql->printDebug(true, false, 'yggdrasil_'.$_REQUEST['action']);
phive()->dumpTbl('panda_reply', $result);





//phive()->dumpTbl('sheriff_req', "json: $req, action: ".$_REQUEST['action']);


//$sql->insertArray('sql_log', array('dump_txt' => $GLOBALS['HTTP_RAW_POST_DATA'], 'tag' => $qf->method));

$duration 	= microtime(true) - $smicro;

//if($duration > 1)
//	$sql->printDebug(true, true, $qf->method);

$insert = array(
  'duration' 	=> $duration, 
  'method' 	=> $_REQUEST['action'],
  'username' 	=> $GLOBALS['mg_username']);

//$sql->insertArray('game_replies', $insert);
//if($duration > 1)
//  $sql->insertArray('slow_game_replies', $insert);

$output = ob_get_clean();

//echo $output;
echo $result;

//phive()->dumpTbl('sheriff_answer', $result);
//phive()->dumpTbl('sheriff_output', $output);



