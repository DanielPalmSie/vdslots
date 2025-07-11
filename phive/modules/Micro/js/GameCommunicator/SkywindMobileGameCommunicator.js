GameCommunicator.setEventMapping({
    "gameRoundStarted": "gameRoundStarted",
    "gameRoundEnded": "gameRoundEnded",
});

GameCommunicator.setEventParser(function (event) {
    if (!event.data) return;
    var data = JSON.parse(event.data)
    var type = "";
    if (data.msgId && data.msgId === "sw2opLoading") {
        if (data.state === 'ended' && CONF.jurisdiction === 'IT') {
            GameCommunicator.request(JSON.stringify({msgId: 'op2swUpdateBalance'}));
        }
    }

    if(data.msgId === 'sw2opRound') {
        data.state === 'started' ? type = 'gameRoundStarted' : type = 'gameRoundEnded'
    }

    return type;

});



