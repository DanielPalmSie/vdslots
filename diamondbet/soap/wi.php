<?php
ob_start();
$smicro = microtime(true);
require_once __DIR__ . '/../../phive/api.php';
/*
if(phive("Config")->getValue('network-status', 'wi') == 'off')
  die("turned off");
*/
$sql 		= phive('SQL');

//phive()->dumpTbl('wi_headers', apache_request_headers());

$wi 	= phive('Wi');
$result = $wi->execute($_GET['action'], file_get_contents('php://input'));
$output = ob_get_clean();

echo $result;
