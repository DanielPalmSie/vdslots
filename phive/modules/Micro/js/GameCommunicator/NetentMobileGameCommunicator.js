GameCommunicator.setEventMapping({
    "gameRoundStarted": "gameRoundStarted",
    "gameRoundEnded": "gameRoundEnded"
});
GameCommunicator.setEventParser(function(event) {
    if (!event.data || typeof event.data[1] !== 'string') return false;
    return event.data[1];
});