<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/phive/phive.php';
?>
<script type="text/javascript">
    var MessageProcessor = (function (){
        return {
            test: <?= json_encode(phive('Evolution')->getSetting('test', true)); ?>,
            bosMapping: {
                "gameRoundStarted": "gameRoundStarted",
                "gameRoundEnded": "gameRoundEnded"
            },

            getEventType: function (event) {
                if(!event.data) return false;

                if (event.data && event.data.hasOwnProperty('type')) {
                    return event.data.type;
                } else if(event.data && event.data.hasOwnProperty('event')) {
                    return event.data.event
                }
            },

            /**
             * @param {{data:object, interruptAllowed:boolean}} event
             */

            process: function (event) {
                var type = this.getEventType(event);
                var interruptAllowed = event.data.interruptAllowed;

                switch (type) {
                    case "EVO:APPLICATION_READY":
                        this.request({
                            command: "EVO:EVENT_SUBSCRIBE",
                            event: "EVO:GAME_LIFECYCLE"
                        });
                        break;
                    case "EVO:GAME_LIFECYCLE":
                        if(interruptAllowed) {
                            this.roundEnded = true;
                            this.execPendingRcFunctions();
                            return 'gameRoundEnded';
                        } else {
                            this.roundEnded = false;
                            return 'gameRoundStarted';
                        }
                    case 'GAME_STATE_BETS_OPENED':
                        this.roundEnded = true;
                        this.execPendingRcFunctions();
                        break;
                    case 'GAME_STATE_BETS_CLOSED':
                        this.roundEnded = false;
                        break;
                    default:
                        break;
                }
            },

            pauseGame: function() {
                return new Promise(function(resolve, reject) {
                    this.request({
                        command: "EVO:STOP_AUTOPLAY",
                    });
                    resolve();
                }.bind(this));
            },

            bosReloadBalance : function(){
                reloadIframe($('#mbox-iframe-play-box'))
            },
        };
    } )();
</script>
