GameCommunicator.setEventMapping({
    "gameBusy": "gameRoundStarted",
    "gameNotBusy": "gameRoundEnded"
});

GameCommunicator.setEventParser(function (event) {
    try {
        event = JSON.parse(event.data);
        return event.event;
    } catch (e) {
        return false;
    }
});

GameCommunicator.setPauseFunction(function() {
    var iframe = document.getElementById('vs-game-container__iframe-1');
    if (iframe) {
        iframe.contentWindow.postMessage('StopGamePlay', '*');
    }
});


