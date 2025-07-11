<?php
if (in_array($_REQUEST['func'], ['getEvents'])) {
    $GLOBALS['no-session-refresh'] = true;
}

require_once __DIR__ . '/../../../phive.php';
require_once __DIR__ . '/../../../html/common.php';

addCacheHeaders("nocache");
phive('Localizer')->setLanguage($_REQUEST['lang'], true);
	
$action = 'ajax'.$_REQUEST['action'];

//echo $action;
$bh = phive('BoxHandler');
if(method_exists($bh, $action))
    $bh->$action($_REQUEST['func']);
else
    error_log("Fatal error: method $action does not exist on BoxHandler.");
