<?php
//require_once __DIR__ . '/../../admin.php';
require_once __DIR__ . '/../../admin.php';

$url = phive("QuickFire")->getPlayCheckUrl($_REQUEST['uid'], $_REQUEST['token']);

if(!empty($url)){
	header("HTTP/1.1 301 Moved Permanently"); 
	header("Location: $url"); 
	header("Connection: close");
	exit;
}

?>

Player has not played anything yet.
