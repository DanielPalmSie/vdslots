<?php 

    require_once __DIR__ . '/../../phive/phive.php';
    require_once __DIR__ . '/BoSTester.php';
    require_once __DIR__ . '/BoSTestTournament.php';

    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body);


    /* LOAD LEADERBOARD */
    if (isset($_GET['action']) && $_GET['action'] == 'fetchLeaderboard' && isset($_GET['tid'])) {        
        echo json_encode(getLeaderboard($_GET['tid']));
        die;
    }

    /* Plays one spin for one user */
    if ( isset($data->action) && $data->action == 'playTournament' ) {
        $tournament = new BoSTestTournament($data->tid);
        if (!$tournament->hasStarted()) {
            $tournament->startTournament();
        }
        $testUser = new BoSTester($data->tid, $data->user_id);
        $testUser->spin();
        echo json_encode(getLeaderboard($data->tid));
        die;
    }

    /* Plays one spin for all users subscribed in the tournament */
    if ( isset($data->action) && $data->action == 'playAllUsers' ) {
        $tournament = new BoSTestTournament($data->tid);
        if (!$tournament->hasStarted()) {
            $tournament->startTournament();
        }
        $players = $tournament->getPlayers();
        foreach ($players as $player) {
            $player->spin();            
        }
        echo json_encode(getLeaderboard($data->tid));
        die;
    }

    /* Plays all spins (or until the user goes out of tournament cash) for all users */
    if ( isset($data->action) && $data->action == 'playAllSpins' ) {
        $tournament = new BoSTestTournament($data->tid);
        if (!$tournament->hasStarted()) {
            $tournament->startTournament();
        }
        $players = $tournament->getPlayers();
        $playerSpins = $players[0]->getSpinsLeft(); // let's suppose that all players have the same spins
        for ($i=0; $i < $playerSpins; $i++) {    
            foreach ($players as $player) {
                $player->spin();            
            }
            shuffle($players); // randomize the array
        }
        echo json_encode(getLeaderboard($data->tid));
        die;
    }


    function getLeaderboard($tid) {
        $tournament = new BoSTestTournament($tid);
        $players = $tournament->getLeaderboard();
        $leaderboard = [];
        // $i = 1;
        foreach ($players as $player) {
            $leaderboard[] = [
                // 'pos' => $i,
                'id' => $player->getUserId(),
                'username' => $player->getUsername(),
                'alias' => $player->getAlias(),
                'total_win' => $player->getTotalWin()/100,
                'spins_left' => $player->getSpinsLeft(),
                'cash_balance' => $player->getCashBalance()/100,
                'currency' => '€',
            ];
            // $i++;
        }
        return $leaderboard;
    }
