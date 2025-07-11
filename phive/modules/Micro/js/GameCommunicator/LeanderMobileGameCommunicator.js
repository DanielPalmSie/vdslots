GameCommunicator.setEventMapping({
    "gameRoundStarted": "gameRoundStarted",
    "gameRoundEnded": "gameRoundEnded",
    'bonusGameStarted': 'bonusGameStarted',
    'bonusGameEnded': 'bonusGameEnded'
});

GameCommunicator.setEventParser(function (event) {
    try {
        var data = JSON.parse(event.data);
        var type = "";
        if (data.configData && data.configData === "clientOrigin") {
            GameCommunicator.request(JSON.stringify({msgId: "broadcastToCasino", status: true}));
            type = "setup";
        }
        if (data.msgId && data.msgId === "rg2xcGameStatusChange") {
            if (data.status === "gameOpenPlay") {
                type = "gameRoundStarted";
            } else if (data.status === "gameIdle") {
                type = "gameRoundEnded";
            }
        }
        return type;
    } catch (e) {
        return event.data;
    }
});

GameCommunicator.setPauseFunction(function () {
    GameCommunicator.request(JSON.stringify({msgId: "xc2rgBlockGameStatus", status: true}));
    GameCommunicator.request(JSON.stringify({msgId: "xc2rgStopAutoPlayInGame"}));
    GameCommunicator.request('pauseGame');
});

GameCommunicator.setResumeFunction(function () {
    GameCommunicator.request(JSON.stringify({msgId: "xc2rgBlockGameStatus", status: false}));
    GameCommunicator.request('resumeGame');
});


