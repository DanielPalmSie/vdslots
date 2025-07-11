<?php 
    require_once $_SERVER['DOCUMENT_ROOT'].'/phive/phive.php';
?>
<script type="text/javascript">
var MessageProcessor = (function (){
    return {
        test: true,
        bosMapping:{
            "gameRoundStarted"  : "gameRoundStarted",
            "gameRoundEnded" : "gameRoundEnded",
        },
        bosReloadBalance : function(){
            this.request({event: "refreshBalance"});

        },
        getEventType: function (event) {
            console.log("event: " + event.data.name);
            if (!event.data.name) return;     
            return event.data.name;
        },
    };
} )();
</script>
