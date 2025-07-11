var MessageProcessor = GameCommunicator = (function () {
    var pendingAction = [];
    var roundEnded = true;
    var eventMap = {
        "providerRoundStarted": "gameRoundStarted",
        "providerRoundEnded": "gameRoundEnded"
    };
    var parsePostMessage = function (event) {
        return event.data;
    };
    var sendPauseGameRequest = function(){};
    var sendResumeGameRequest = function(){};

    function processPostMessage(event) {
        var eventType = parsePostMessage(event);
        if (typeof eventMap[eventType] === "undefined") {
            return;
        }
        switch (eventMap[eventType]) {
            case 'gameRoundEnded':
                roundEnded = true;
                while (pendingAction.length) {
                    var action = pendingAction.shift();
                    action();
                }
                break;
            case 'gameRoundStarted':
                roundEnded = false;
                break;
            default:
                break;
        }
    }

    $(document).ready(function () {
        window.addEventListener('message', function (event) {
            processPostMessage(event);
        });
    });


    return {
        waitRoundFinished: function (callback) {
            var onRoundFinished = function () {
                callback();
            }.bind(this);
            if (roundEnded) {
                onRoundFinished();
            } else {
                pendingAction.push(onRoundFinished);
            }
        },
        // TODO: Remove ES6 promise from pausing/resuming functionality
        pauseGame: function() {
             return new Promise(function (resolve, reject) {
                sendPauseGameRequest();
                this.waitRoundFinished(resolve);
            }.bind(this));
        },
        resumeGame: function() {
             return new Promise(function (resolve, reject) {
                sendResumeGameRequest();
                resolve();
            }.bind(this));
        },
        request: function(msg) {
            var iframe = document.getElementById('vs-game-container__iframe-1');
            if (iframe) {
                iframe.contentWindow.postMessage(msg, '*');
            }
        },
        setPauseFunction: function (gamePausingFunction) {
            sendPauseGameRequest = gamePausingFunction;
        },
        setResumeFunction: function (resumeGameFunction) {
            sendResumeGameRequest = resumeGameFunction;
        },
        setEventParser: function (eventParsingFunction) {
            parsePostMessage = eventParsingFunction;
        },
        setEventMapping: function (providerMapping) {
            eventMap = providerMapping;
        },
        getRoundStatus: function () {
            return roundEnded;
        }
    }
})();
