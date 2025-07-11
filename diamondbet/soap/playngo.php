<?php
ob_start();
require_once __DIR__ . '/../../phive/api.php';

/*
if (phive("Config")->getValue('network-status', 'playngo') == 'off') {
    die("turned off");
}
*/
$start_time = microtime(true);

/** @var Playngo $playngo */
$playngo = phive('Playngo');

$req = file_get_contents("php://input");
$result = $playngo->execReq($req);


/* TODO reply duration log functions has to be moved to generic logic */
$duration = microtime(true) - $start_time;
$insert = array(
    'duration' => $duration,
    'method' => $_REQUEST['game_action'],
    'mg_id' => $GLOBALS['mg_id'],
    'token' => 'playngo',
    'username' => $GLOBALS['mg_username'],
    'host' => gethostname()
);
if ($playngo->isTest()) {
    phive('SQL')->insertArray('game_replies', $insert);
}
phive('MicroGames')->logSlowGameReply($duration, $insert);

$output = ob_get_clean();

echo $result;

