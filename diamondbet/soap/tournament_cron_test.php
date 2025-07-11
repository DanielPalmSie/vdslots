<?php
require_once __DIR__ . '/../../phive/phive.php';
if(isCli()){
    if(hasMp()){
        phive('Tournament')->calcPrizesCron();
        phive('Tournament')->importTournamentEntriesFromCsv();
    }
}
