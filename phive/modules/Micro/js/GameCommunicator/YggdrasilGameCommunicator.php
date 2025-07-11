<?php 
    require_once $_SERVER['DOCUMENT_ROOT'].'/phive/phive.php';
?>
<script type="text/javascript">
var MessageProcessor = (function (){

    return {
        test: true,
        bosMapping:{ 
                "gameRound/start"  : "gameRoundStarted", // takes bonuses into account
                "gameRound/end" : "gameRoundEnded", // takes bonuses into account
                "spin/start" : "spinStarted",
                "spin/end" : "spinEnded",
        },

        bosReloadBalance : function() {
            this.request("balance/update");
        },

        getEventType: function (event) {
            if(typeof(event.data) == "object") {
                return event.data.event;
            } else if (typeof(event.data) == "string") {
                return event.data;
            }
        },

        getEventData: function (event) {
            if (!event.data) return;
            return event.data;
        },

        process: function (event) {
            var type = this.getEventType(event);

            if(type ==='game/ready') {
                this.setMinBet();
            }
        },

        setMinBet: function() {
            this.request("bet/setMinBet");
        },

        pauseGame() {
            return new Promise((resolve,reject) => {
                let pauseGameFunction = ()=> {
                    this.request('StopGamePlay');
                    setTimeout(function() {
                        resolve();
                    }, 500);

                };
                if (this.roundEnded) {
                    pauseGameFunction();
                } else {
                    this.pendingRcFunctions.push(pauseGameFunction);
                }
            });
        },

        resumeGame() {
            return new Promise((resolve,reject) => {
                this.request('ReStartGamePlay');
                resolve();
            });
        }

    };
} )();
</script>
