<?php
require_once __DIR__ . '/../../phive/phive.php';
if(isCli()){
    $GLOBALS['is_cron'] = true;
    $date = date('Y-m-d', strtotime('-1 day'));
    phive('Trophy')->dayCron($date);
}
