<?php
//ob_start();
$smicro = microtime(true);
require_once __DIR__ . '/../../phive/api.php';
/*
if(phive("Config")->getValue('network-status', 'rival') == 'off')
  die("turned off");
*/
$sql 	= phive('SQL');
$rival 	= phive('Rival');

$body = file_get_contents('php://input');

if(empty($body))
  die("no json");

$result = $rival->execute($body);

$output = ob_get_clean();

echo json_encode($result);

