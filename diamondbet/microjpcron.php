<?php
ini_set('max_execution_time', '30000');
ini_set('memory_limit', '500M');
require_once __DIR__ . '/../phive/phive.php';

if(isCli()){
    $GLOBALS['is_cron'] = true;
    phive('MicroGames')->parseJps();
}

