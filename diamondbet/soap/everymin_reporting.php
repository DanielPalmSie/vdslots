<?php
require_once __DIR__ . '/../../phive/phive.php';
if(isCli()){
    $GLOBALS['is_cron'] = true;

    lics('onEveryMinReporting');
}
