<?php
require_once __DIR__ . '/../../phive/phive.php';
if(isCli()){
    if(date('d') == 1) 
        phive('Cashier')->calcUserMonthlyCache(phive()->today()); 

    // TODO Archiving does not work sharded atm.
    exit;
    
    $archive = phive('SQL')->getSetting('archive');

    if(empty($archive))
        exit;

    if(date('d') == 2){
        phive("SQL")->replaceTable('micro_games');
        phive("QuickFire")->archive('bets');
        phive("QuickFire")->archive('bets_mp');
    }

    if(date('d') == 3){
        phive("SQL")->replaceTable('micro_games');
        phive("QuickFire")->archive('wins');
        phive("QuickFire")->archive('wins_mp');
    }
    
    
    if(date('d') == 4){
        phive("SQL")->replaceTable('micro_games');
        phive("Bonuses")->archive();
        phive("Vouchers")->archive();
    }

    if(date('d') == 5){
        phive('UserHandler')->archiveActions();
    }

    if(date('d') == 6){
        //disabled for now, doesn't improve much and is prone to create orphans, needs to respect shards before it can be turned on
        //phive('UserHandler')->archiveUsers();
        phive('SQL')->archiveTable('load_stats');
    }

    if(date('d') == 7){
        // We archive all session related data that is older than half a year
        $date = phive()->hisMod('-6 month', '', 'Y-m-01 00:00:00');
        phive('SQL')->archiveTable('users_sessions', $date);
        phive('SQL')->archiveTable('users_game_sessions', $date, 'id', false, true, 'start_time');
    }
}
