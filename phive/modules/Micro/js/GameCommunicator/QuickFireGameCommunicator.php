<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/phive/phive.php';
?>
<script type="text/javascript">
    var MessageProcessor = (function (){

        return {
            test: <?= json_encode(phive('QuickFire')->getSetting('test', false)); ?>,
            bosMapping:{
                "gameBusy"  : "gameRoundStarted",
                "gameNotBusy" : "gameRoundEnded"
            },
            bosReloadBalance : function(){
                 reloadIframe($('#mbox-iframe-play-box'));
                /**
                 * To be used when Microgaming implements their fix
                 */
                // this.request({
                //     jsonrpc: 2.0,
                //     method: "updateBalance",
                //     params: {}
                // });
            },
            getEventType: function (event) {
                if (!event.data) return;

                if(!this.isJsonParsable(event.data)) {
                    if(this.test) console.log("type: " + event.data.event);
                    return event.data.type;
                }

                var data = JSON.parse(event.data);
                if(this.test) console.log("type: " + data.event);
                return data.event;
            },
            process: function (event) {
                var type = this.getEventType(event);

                switch (type) {
                    case 'gameNotBusy':
                        this.roundEnded = true;
                        this.execPendingRcFunctions();
                        break;
                    case 'gameBusy':
                        this.roundEnded = false;
                        break;
                    default:
                        break;
                }
            },

            getEventData: function(event) {
                if(!event.data) return;
                if(this.test) console.log("Data: ", event.data);
                return event.data;
            },
            pauseGame: function() { // this will cease any active autoplay as the popup triggers
                return new Promise(function(resolve,reject) {
                    this.request('StopGamePlay');
                    resolve();
                }.bind(this));
            },

            resumeGame: function() { // there is no postMessage to resume gameplay
                return new Promise(function (resolve, reject){
                    resolve();
                });
            }
        };
    } )();
</script>