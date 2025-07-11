<?php
require_once __DIR__ . '/../../phive/phive.php';
if(isCli()){
    if(hasMp()){
        $GLOBALS['is_cron'] = true;
        phive()->fire('tournament', 'tournamentCalcPrizesEvent', [], 0, function() {
            phive('Tournament')->calcPrizesCron();
            phive('Tournament')->importTournamentEntriesFromCsv();
        });
    }
}
