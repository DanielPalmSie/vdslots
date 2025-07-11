<?php 
    require_once $_SERVER['DOCUMENT_ROOT'].'/phive/phive.php';
?>
<script type="text/javascript">
var MessageProcessor = (function (){
    var gameLoader = null;

    return {
        test: true,
        entryPoint: null,
        postParams: null,
        getParams: null,
        launcherType: null,
        roundEnded: true,
        pendingRcFunctions: [],
        bosMapping:{
            "ROUND_START"           : "gameRoundStarted",
            "ROUND_END"             : "gameRoundEnded",
            "FREE_ROUND_START"      : "freespinStarted",
            "FREE_ROUND_END"        : "freespinEnded"
        },

        bosReloadBalance : function(functionName, retRegRebuy){
            var entry_balance = retRegRebuy['entry_balance'] / 100;
            this.request({"event": "WALLET_UPDATED", "data": {"balance": entry_balance }});
        },


        process: function (event) {
            const type = this.getEventType(event);
            const data = this.getEventData(event);

            switch (type) {
                default:
                    // process other events if necessary 
                    break;
            }            
        },
        

        getEventType: function (event) {

            var event_type = '';

            if (!event.data.event) {
                return;
            }

            if(event.data.roundType === 'FREE_ROUND') {
                if (event.data.event === 'ROUND_START') {
                    event_type = 'FREE_ROUND_START';
                } else {
                    event_type = 'FREE_ROUND_END';
                }
            } else {
                event_type = event.data.event;
            }

            console.log("event: " + event_type);

            return event_type;
        },


        getEventData: function (event) {
            console.log(event.data);
            if (!event.data) return;
            return event.data;
        },
    };
} )();
</script>
