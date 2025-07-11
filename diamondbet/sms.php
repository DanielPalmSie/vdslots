<?php
ini_set('max_execution_time', '200');
require_once __DIR__ . '/../phive/phive.php';

if(isCli()){
    $GLOBALS['is_cron'] = true;
    phive('Mosms')->runQ(60);
}
