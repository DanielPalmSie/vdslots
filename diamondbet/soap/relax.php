<?php
//ob_start();
$smicro = microtime(true);
require_once __DIR__ . '/../../phive/api.php';

/*
if(phive("Config")->getValue('network-status', 'relax') == 'off')
  die("turned off");
*/

$sql 	= phive('SQL');
$relax 	= phive('Relax');

$test = $relax->getSetting('test');

$body = file_get_contents('php://input');

if ($test === true) {
    phive()->dumpTbl('relax_' . $_REQUEST['action'], array('body' => $body, 'req' => $_REQUEST));
}
phive('Logger')->getLogger('relax')->debug('relax_'.$_REQUEST['action'].'_request', ['body' => $body]);

//$sql->debug = true;
$result = $relax->exec(json_decode($body, true), $_GET['action']);
//$sql->printDebug(true, false);

if ($test === true) {
    phive()->dumpTbl('relax_reply', $result);
}
phive('Logger')->getLogger('relax')->debug('relax_reply', ['action' => $_REQUEST['action'], 'request-body' =>$body, 'response' => $result]);

$duration 	= microtime(true) - $smicro;

$insert = array(
    'duration' => $duration,
    'method'   => $_GET['action'],
    'token'    => 'relax',
    'username' => $GLOBALS['mg_username'],
    'host'     => gethostname(),
);

if($relax->isTest()){
    $sql->insertArray('game_replies', $insert);
}
phive('Logger')->getLogger('relax')->debug('relax_game_replies', [$insert]);
phive('MicroGames')->logSlowGameReply($duration, $insert);

$output = ob_get_clean();

echo $result;

