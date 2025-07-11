<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/phive/phive.php';
?>
<script type="text/javascript">
    var MessageProcessor = (function () {

        return {
            test: <?= json_encode(phive('Leander')->getSetting('test', false)); ?>,
            roundEnded: true,
            bosMapping: {
                "gameRoundStarted": "gameRoundStarted",
                "gameRoundEnded": "gameRoundEnded",
                'bonusGameStarted': 'bonusGameStarted',
                'bonusGameEnded': 'bonusGameEnded'
            },
            bosReloadBalance: function (action, ret, onSuccess, onError) {
                this.request("reloadBalance");
            },
            getEventType: function (event) {
                if (!event.data) return;

                if (!this.isJsonParsable(event.data)) {
                    return event.data;
                }

                var data = JSON.parse(event.data);

                var type = "";
                if (data.configData && data.configData === "clientOrigin") {
                    type = "setup";
                }
                if (data.msgId && data.msgId === "rg2xcGameStatusChange") {
                    if (data.status === "gameOpenPlay") {
                        type = "gameRoundStarted";
                    } else if (data.status === "gameIdle") {
                        type = "gameRoundEnded";
                    }
                }

                if (this.test && type !== ""){
                    console.log("event: " + type);
                }
                return type;
            },
            process: function (event) {
                var type = this.getEventType(event);
                switch (type) {
                    case 'setup':
                        this.request(JSON.stringify({msgId: "broadcastToCasino", status: true}));
                        break;
                    case 'gameRoundStarted':
                        fi.gameRound = true;
                        this.roundEnded = false;
                        break;
                    case 'gameRoundEnded':
                         fi.gameRound = false;
                        this.roundEnded = true;
                        this.execPendingRcFunctions();
                        break;
                    default:
                        break;
                }
            },

            getEventData: function (event) {
                if (!event.data) return;
                if (this.test) console.log("Data: ", event.data);
                return event.data;
            },
            pauseGame: function () { // this will cease any active autoplay as the popup triggers
                return new Promise(function (resolve, reject) {
                    this.request("pauseGame");
                    resolve();
                }.bind(this));
            },

            resumeGame: function () { // there is no postMessage to resume gameplay
                return new Promise(function (resolve, reject) {
                    this.request("resumeGame");
                    resolve();
                }.bind(this));
            }
        };
    })();
</script>
