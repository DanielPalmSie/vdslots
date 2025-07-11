<?php 
    require_once $_SERVER['DOCUMENT_ROOT'].'/phive/phive.php';
?>
<script type="text/javascript">
let MessageProcessor = (function (){

    return {
        test: true,
        mapping:{
            "roundstarted"  : "gameRoundStarted",
            "idlestateentered" : "gameRoundEnded",
            "spinstarted" : "spinStarted",
            "roundended": "spinEnded",
            "featurewon" : "bonusGameStarted", 
            "featureexited" : "bonusGameEnded",
            "backtolobby" : "backtolobby" 
        },

        bosReloadBalance : function(){
            reloadIframe($('#mbox-iframe-play-box'))
        },

        process: function (event) {
            const type = this.getEventType(event);

            switch (type) {
                case 'backtolobby':
                    this.backToLobbyCallback(); 
                    break;
                case 'idlestateentered':
                    this.execPendingRcFunctions(); 
                    this.roundEnded = true;
                    break;
                case 'gamestarted':
                    this.request( {"eventid":"setupcommunication"});
                    this.roundEnded = false;
                    break;
                case 'gameRoundEnded':
                    fi.spinning = false;
                    this.roundEnded = true;
                    break;
                default:
                    this.roundEnded = false;
                    break;
            }            
        },

        pauseGame() {
            return new Promise((resolve,reject) => {
                let pauseGameFunction = ()=> {
                    this.request({"eventid" : "pausegame"}); 
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
                this.request({'eventid': 'resumegame'});
                resolve(); 
            });
        },

        getEventType: function (e) {
            if(!e.data || typeof e.data[1] !== 'string') return false;
            const event = JSON.parse(e.data);
            return this.getMappedEvent(event.eventid);
        },

        getMappedEvent: function (event) {    
            console.log(event);        
            return this.mapping[event] !== undefined ? this.mapping[event] : event;
        }
    };
} )();
</script>
