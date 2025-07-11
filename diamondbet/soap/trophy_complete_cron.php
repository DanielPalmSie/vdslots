<?php
require_once __DIR__ . '/../../phive/phive.php';
if(isCli()){
    $GLOBALS['is_cron'] = true;
    // We loop instead to avoid the host from taking a massive CPU hit
    phive('SQL')->loopShardsSynced(function($db, $sh_conf, $sh_num){
        phive('Trophy')->completeCron($sh_num);
    });
    //pExecShards('Trophy', 'completeCron');
}
