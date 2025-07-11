<?php

/***********************************************************************
IMPORTANT: This cron runs every 35th minute, not every 15 minutes!
************************************************************************/

ini_set('max_execution_time', '1800');
//ini_set("log_errors", 1);
//ini_set("error_log", "/tmp/php-error.log");
//phpinfo(); die();
require_once __DIR__ . '/../../phive/phive.php';
if(isCli()){
    $GLOBALS['is_cron'] = true;

    $uh = phive("UserHandler");
    if(phive()->moduleExists('Trophy')){
        phive('SQL')->loopShardsSynced(function ($db, $sh_conf, $sh_num) {
            phive()->fire('trophy','trophyAwardProgressionEvent', [$sh_num, true], 0, function () use ($sh_num) {
                phive()->pexec('Trophy', 'minuteCron', [$sh_num, true], '100-0', true);
            });
        });
        //pExecShards('Trophy', 'minuteCron', [true]);

        phive()->fire('trophy','trophyXpCronAndExpireAwardsEvent', [], 0, function (){
            phive('Trophy')->xpCronAndExpireAwards();
        });
        pExecShards('Trophy', 'expireAwards');

        //pExecShards('Trophy', 'completeCron');
        //phive('Trophy')->completeCron();
    }

    // Removing this since it has been replaced by the same call
    // in IT/italy-every15min.php
    //
    // lics('gameSessionsAlignmentCommunicationCron');

	phive('Bonuses')->grantWelcomeBonusWeeklyAwards();

}
