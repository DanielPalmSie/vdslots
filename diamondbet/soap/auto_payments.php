<?php
ini_set('max_execution_time', '3600');
require_once __DIR__ . '/../../phive/phive.php';
if(isCli()){
    $GLOBALS['is_cron'] = true;
    phive('Cashier')->hourCron();
    phive('Cashier')->endPreprocessingWithdrawals();
}
