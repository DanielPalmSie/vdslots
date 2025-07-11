<?php 
    require_once $_SERVER['DOCUMENT_ROOT'].'/phive/phive.php';
?>
<script type="text/javascript">
var MessageProcessor = (function (){
    return {
        test: true,
        bosMapping:{
            "roundStarted" : "gameRoundStarted",
            "roundEnded" : "gameRoundEnded",
        },
        bosReloadBalance : function(){
            //Pariplay excepts an "updateBalance" event
            //this.request({type: "updateBalance", data: undefined});

            //We have to send "*" as uri, the GameCommunicator sends the normal pariplay's url
            //So we make the call here
            window.postMessage({type: "updateBalance", data: undefined}, '*')
        },
        getEventType: function (event) {
            console.log("event: " + event.data.type);
            if (!event.data.type) return;     
            return event.data.type;
        },
        pauseGame() {
            return new Promise((resolve,reject) => {
                let pauseGameFunction = ()=> {
                    this.request('{"type" : "Tilt"}');
                };
                if (this.roundEnded) {  // direct execution
                    pauseGameFunction();
                } else {                // delayed execution
                    this.pendingRcFunctions.push(pauseGameFunction);
                }
            });
        },
    };
} )();
</script>
