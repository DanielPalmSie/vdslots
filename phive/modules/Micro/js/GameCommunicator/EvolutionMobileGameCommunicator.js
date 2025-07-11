GameCommunicator.setEventMapping({
    "GAME_STATE_BETS_CLOSED": "gameRoundStarted",
    "GAME_STATE_BETS_OPENED": "gameRoundEnded"
});
GameCommunicator.setEventParser(function(event) {
    if (!event.data) return false;
    if(event.data.hasOwnProperty('type')) {
        return event.data.type;
    } else if(event.data.hasOwnProperty('event')) {
        if (event.data.event === "EVO:APPLICATION_READY") {
            event.source.postMessage({
                command: "EVO:EVENT_SUBSCRIBE",
                event: "EVO:GAME_LIFECYCLE"
            }, "*");
        } else if (event.data.event === "EVO:GAME_LIFECYCLE") {
            if(event.data.interruptAllowed) {
                event.data.event = 'GAME_STATE_BETS_OPENED'
            } else {
                event.data.event = 'GAME_STATE_BETS_CLOSED'
            }
        }
        return event.data.event;
    }
});
GameCommunicator.setPauseFunction(function () {
    var iframe = document.getElementById('vs-game-container__iframe-1');
    if (iframe) {
        iframe.contentWindow.postMessage({
            command: "EVO:STOP_AUTOPLAY"
        }, '*');
    }
});