<?php 
    require_once $_SERVER['DOCUMENT_ROOT'].'/phive/phive.php';
?>
<script type="text/javascript">
var MessageProcessor = (function (){
    

    return {
        test: true,
        roundEnded: true,
        pendingRcFunctions: [],

        process: function (event) {
            const type = this.getEventType(event);

            if (type === 'notifyCloseContainer') {
                this.backToLobbyCallback();
            } else {
                this.roundEnded = false;
            }
        },

        getEventType: function (e) {
            console.log(e.data);
            if(!e.data || typeof e.data[1] !== 'string') return false;
            return e.data;
        },

        getEventData: function (e) {
            console.log('Data: ', e);
        },

        execPendingRcFunctions: function() {
            while(this.pendingRcFunctions.length){
                var rcFunction = this.pendingRcFunctions.shift();
                rcFunction();
            }                 
        }

    };
} )();
</script>
