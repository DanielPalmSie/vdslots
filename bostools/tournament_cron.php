<?php

// Call this cron like this: 
// php -q /var/www/videoslots/bostools/tournament_cron.php

require_once __DIR__ . '/../../phive/phive.php';
require_once __DIR__ . '/Models/BoSTester.php';

// Include the normal cron in this file, just in case it will be changed at some point. 
require_once __DIR__ . '/../../diamondbet/soap/tournament_cron.php';

if(isCli()){
    if(hasMp()){

        // Register a random number of users to battles that have registration open, and don't have any players yet.
        $tournaments = phive('SQL')->loadArray("SELECT * FROM tournaments WHERE status IN ('registration.open') AND registered_players = 0 AND start_format = 'mtt'");
        foreach ($tournaments as $tournament) {

            if($tournament['max_players'] > 25) {
                $i = 1;
                $number_of_players_to_register = mt_rand(25, 50);
                while ($i <= $number_of_players_to_register) {
                    $newPlayer = new BoSTester($tournament['id']);
                    $newPlayer->registerUserInTournament();
                    $i++;
                    echo "new player should be registered to battle with id {$tournament['id']} \n";

                    // PROBLEM: registered_players column is only updated on the master, not on the shards (why??)
                    // --> This also happens on LIVE
                    // Only finished battles are updated in the shards
                }
            }
        }

        // exclude devtest users from the following
        $devtest_users = phive('SQL')->shs('merge', '', null, 'users')->loadArray("SELECT * FROM users WHERE username LIKE 'devtest%' ");

        $devtest_users_ids = [];
        foreach ($devtest_users as $user) {
            $devtest_users_ids[] = $user['id'];
        }

        // Battles that are in.progress
        // These already have users registered, we only need to simulate gameplay
        $tournaments = phive('SQL')->loadArray("SELECT * FROM tournaments WHERE status = 'in.progress' OR status = 'late.registration'");
        foreach ($tournaments as $tournament) {
            $entries = phive('Tournament')->_getEntriesWhere(['t_id' => $tournament['id']]);
            foreach ($entries as $e) {
                if(!in_array($e['user_id'], $devtest_users_ids)) {
                    $e['win_amount']   = rand(0, 100);
                    $e['cash_balance'] = $cash_balance;
                    $e['spins_left']   = 0;
                    $e['turnover']     = rand(10, 1000);
                    phive("SQL")->sh($e)->save('tournament_entries', $e); // TODO: maybe tap into some other function that also does WS updates etc.

                    echo "player {$e['user_id']} should have played battle with id {$tournament['id']} \n";
                }
            }
        }
        
    }
}

