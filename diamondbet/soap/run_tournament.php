<?php
require_once __DIR__ . '/../../phive/phive.php';
if(isCli()){
    if(hasMp()){
        $GLOBALS['is_cron'] = true;var_dump('222222');die;
        phive()->fire('tournament', 'tournamentCalcPrizesEvent', [], 0, function() {var_dump('ttttt'); die;
            phive('Tournament')->calcPrizesCron();
        });
    }
}
