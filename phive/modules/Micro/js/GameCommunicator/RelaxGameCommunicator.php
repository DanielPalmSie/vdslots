<?php
    require_once $_SERVER['DOCUMENT_ROOT'].'/phive/phive.php';
    $user = cu();
    $license = licJur($user);
?>
<script type="text/javascript">
    var license = '<?=$license; ?>';
    var MessageProcessor = (function (){

        return {
            test: true,
            roundEnded: true,
            pendingRcFunctions: [],
            bosMapping :{
                "GameEvent_ROUND_STARTED"  : "gameRoundStarted",
                "GameEvent_ROUND_ENDED" : "gameRoundEnded",
                "GameEvent_FREE_SPINS_STARTED" : "freeSpinStarted",
                "GameEvent_FREE_SPINS_ENDED" : "freeSpinEnded",
            },

            bosReloadBalance : function(){
                this.request({method: "GameEvent_UPDATE_BALANCE", params: null});
            },

            getEventType: function (event) {
                if (!event.data.method) return;
                return event.data.method;
            },

            getEventData: function (event) {
                if (!event.data) return;
                return event.data;
            },

            process: function (event) {
                var type = this.getEventType(event);
                var data = this.getEventData(event);

                if(type === "message") {
                    switch (type.rgMessage) {
                        case 'gprg_UserAction':
                            console.log(data.payload.action);
                            this.redirectCallback(data.payload.action);
                            break;
                        case 'gprg_GamePaused':
                            console.log("They paused the game");
                            break;
                        case 'gprg_GameResumed':
                            console.log("They paused the game");
                            break;
                        default:
                            break;
                    }
                }
            },

            pauseGame() {
                return new Promise(function (resolve,reject) {
                    var pauseGameFunction = function () {
                        if(license === 'SE') {
                            this.request(
                                {
                                    "rgMessage" : "oprg_GamePause",
                                    "payload" : {
                                        "realityCheck": true
                                    }
                                }
                            );
                        } else {
                            this.request(
                                {
                                    "method" : "pauseGame",
                                    "params": {
                                        "callback": "none"
                                    }
                                }
                            );
                        }
                        setTimeout(function() {
                            resolve();
                        }, 500);
                    }.bind(this);
                    pauseGameFunction();
                    if (this.roundEnded) {
                        pauseGameFunction();
                    } else {
                        this.pendingRcFunctions.push(pauseGameFunction);
                    }
                }.bind(this));


            },

            resumeGame() {
                return new Promise(function (resolve,reject) {
                    console.log('resuming game...');
                    if(license === 'SE'){
                        this.request(
                            {
                                "rgMessage" : "oprg_GameResume",
                                "payload" : {
                                    "realityCheck": true
                                }
                            }
                        );
                    } else {
                        this.request(
                            {
                                "method" : "resumeGame",
                                "params": {
                                    "callback": "none"
                                }
                            }
                        );
                    }
                    resolve();
                }.bind(this));
            },


            execPendingRcFunctions: function() {
                while(this.pendingRcFunctions.length){
                    var rcFunction = this.pendingRcFunctions.shift();
                    rcFunction();
                }
            },
        };
    } )();
</script>
