<?php 
    require_once $_SERVER['DOCUMENT_ROOT'].'/phive/phive.php';
?>
<script src="/phive/modules/DBUserHandler/js/tournaments.js" type="text/javascript" charset="utf-8"></script>
<script type="text/javascript">
var autoSpinLeft = null;
let MessageProcessor = (function (){
    return {
        test: false,     // log incoming and outgoing messages
        roundEnded: true,
        bosMapping: { // Battle of slots events
            'roundStarted': 'gameRoundStarted',
            'roundEnded': 'gameRoundEnded',
            'freespinStarted': 'freeSpinStarted',
            'freespinEnded': 'freeSpinEnded',
            'spinStarted': 'spinStarted',
            'spinEnded': 'spinEnded',
            'bonusGameStarted': 'bonusGameStarted',
            'bonusGameEnded': 'bonusGameEnded',
            'autoplayEnded' : 'autoplayEnded',
            'autoplayStarted' : 'autoplayStarted',
            'autoplayNextRound' : 'autoplayNextRound'
        },

        getEventType: function (event) { // to read messages from iframe
           if (!event.data.type) return;
            return event.data.type;
        },

        process: function (event) {
            const type = this.getEventType(event);

            switch (type) {
                case 'backToLobby':
                    this.backToLobbyCallback();
                    break;
                case 'logout':
                    this.backToLobbyCallback();
                    break;
                case 'spinEnded':
                    this.execPendingRcFunctions();
                    this.roundEnded = true;
                    break;
                case 'roundEnded':
                    if (spinLeft <= 1 && canRebuy && (autoSpinLeft >= spinLeft)) {
                        this.request({messageType: "request", request: "stopAutoplay"});
                    }
                    this.roundEnded = true;
                    break;
                case 'autoplayNextRound':
                    autoSpinLeft = JSON.parse(event.data.data.numAutoplayLeft);
                    this.roundEnded = false;
                    break;
                case 'autoplayEnded':
                    autoSpinLeft = null;
                    this.roundEnded = false;
                    break;

                default:
                    this.roundEnded = false;
                    break;
            }            
        },

        bosReloadBalance: function () {
            this.request({messageType: "request", request: "stopAutoplay"});
            this.request({messageType: "request", request: "refreshBalance"});
        },

        startGameCommunicator: function (callback) {
            document.getElementById(self.iframeSelector).onload = function() {
                const events = ["roundStarted", "roundEnded", "freespinEnded", "freespinStarted", "gameDisabled", "logout", "backToLobby",
                    "spinStarted","spinEnded","bonusGameStarted","bonusGameEnded", "autoplayEnded", "autoplayStarted", "autoplayNextRound"]; // Added missing event from Phive GameCommunicator (Check for "gamepaused")

                for (var i = 0; i < events.length; i++) {
                    // console.log("GameCommunicator sent the following message:", {messageType: "addEventListener", eventType: events[i]});
                    document.getElementById(self.iframeSelector).contentWindow.postMessage({messageType: "addEventListener", eventType: events[i]}, '*');
                }
            }
        },

        pauseGame() {
            return new Promise((resolve, reject) => {
                let pauseGameFunction = () => {
                    this.request({messageType: "request", request: "stopAutoplay"});
                    setTimeout(function() {
                        resolve(); // always call this function
                    }, 500);

                };
                if (this.roundEnded) {  // direct execution
                    pauseGameFunction();
                } else {                // delayed execution
                    this.pendingRcFunctions.push(pauseGameFunction);
                }
            });
        }
    };
} )();
</script>
