<?php 

    require_once __DIR__ . '/../../phive/phive.php';
    require_once __DIR__ . '/BoSTester.php';
    require_once __DIR__ . '/BoSTestTournament.php';

    $request_body = file_get_contents('php://input');
    $data = json_decode($request_body);

    /* LOAD ALL MESSAGES */
    if (isset($_GET['fetchall']) && isset($_GET['tid'])) {
        $tournament = new BoSTestTournament($_GET['tid']);
        echo json_encode($tournament->getChatMessages());
        die;
    }

    /* SEND MESSAGE */
    if (isset($data->msg) && isset($data->author) && isset($data->tid)) {
        if (empty($data->author)) {
            $testUser = new BoSTester($data->tid);
        } else {
            $testUser = new BoSTester($data->tid, $data->author);
        }
        $testUser->registerUserInTournament();
        echo json_encode($testUser->sendChatMessage($data->msg));
        die;
    }

    /* CONVERSATION*/
    if (isset($data->users) && isset($data->messages) && isset($data->tid)) {
        $messages = [];
        // Use a pool of users so we don't end creating one user x msg
        $tournament = new BoSTestTournament($data->tid);
        $players = $tournament->getPlayers($data->users);
        for ($i=0; $i < $data->users; $i++) {
            $messages[] = $players[$i]->sendChatMessage();
        }
        echo json_encode($messages);
        die;
    }

    /* SYSTEM MESSAGES (FREESPINS / BONUSES)*/
    if ( isset($data->action) && $data->action == 'sendSystemMessages' ) {
        $messages = [];
        // Use a pool of users so we don't end creating one user x msg
        $tournament = new BoSTestTournament($data->tid);
        $player = $tournament->getPlayers($data->nrOfMessages);
        for ($i=0; $i < $data->nrOfMessages; $i++) {
            $messages[] = $player[$i]->sendSystemMessage();
        }
        echo json_encode($messages);
        die;
    }

    /* should not reach here */
    echo json_encode($data);
