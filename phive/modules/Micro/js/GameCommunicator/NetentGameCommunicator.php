<?php 
    require_once $_SERVER['DOCUMENT_ROOT'].'/phive/phive.php';
?>
<script type="text/javascript">
var MessageProcessor = (function (){
    return {
        test: <?= json_encode(phive('Netent')->getSetting('test', false)); ?>,
        pauseGame: function() {
            return new Promise(function(resolve, reject) {
                if (typeof gameFi !== "undefined" && typeof gameFi.call === "function") {
                    gameFi.call("stopAutoplay", [], function () {});
                }
                resolve();
            }.bind(this));
        },

        resumeGame: function() {
            return new Promise(function(resolve,reject) {
                if(typeof gameFi !== "undefined" && typeof gameFi.call === "function")
                    gameFi.call("resumeAutoplay", [], function () {});
                resolve(); // always call this function
            }.bind(this));
        },

        process: function (event) {
            var type = this.getEventType(event);

            switch (type) {
                case 'gameRoundEnded':
                    this.roundEnded = true;
                    this.execPendingRcFunctions();
                    break;
                case 'gameRoundStarted':
                    this.roundEnded = false;
                    break;
                default:
                    break;
            }
        }
    };
} )();
</script>
