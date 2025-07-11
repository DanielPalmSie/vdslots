<?php 
    require_once $_SERVER['DOCUMENT_ROOT'].'/phive/phive.php';
?>
<script type="text/javascript">
var MessageProcessor = (function (){
    return {
        test: <?= json_encode(phive('Pragmatic')->getSetting('test', false)); ?>,
        bosMapping:{
            "gameRoundStarted"  : "gameRoundStarted",
            "resultShown" : "gameRoundEnded",
        },
        bosReloadBalance : function(){
            this.request({event: "refreshBalance"});

        },
        getEventType: function (event) {
            console.log("event: " + event.data.name);
            if (!event.data.name) return;     
            return event.data.name;
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
