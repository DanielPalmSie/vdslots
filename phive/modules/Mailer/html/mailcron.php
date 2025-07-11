<?php
ini_set("memory_limit", "200M");
ini_set("max_execution_time", "600");
require_once __DIR__ . '/../../../phive.php';
$GLOBALS['is_cron'] = true;
$mh = phive('MailHandler2');
$num = $mh->getSetting('per_minute');
if(empty($num))
    $num = 1000;
$mh->sendMailQueue(null, $num, $_GET['email']);
echo "ok";
