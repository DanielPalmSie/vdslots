<?php 
    require_once $_SERVER['DOCUMENT_ROOT'].'/phive/phive.php';
?>
<script type="text/javascript">
var MessageProcessor = (function (){

    return {
        test: true,

        process: function (event) {
            const type = this.getEventType(event);
            console.log('type: ', type);
            const data = this.getEventData(event);
            console.log('data: ', data);

            switch (type) {
                case 'closeFrame':
                    this.backToLobbyCallback(); // this function is defined on MobileIframePlayBoxBase  
                    break;
                default:
                    // process other events if necessary
                    break;
            }            
        },


        getEventType: function (event) {
            if (!event.data.messageId) {
                return event.data;
            }
            return event.data.messageId;
        },


        getEventData: function (event) {
            if (!event.data) return;
            return event.data;
        },

        setGameLoader: function (gameLoader) {
            self.gameLoader = gameLoader;
        },

        startGameCommunicator: function (callback) {
        },

        loadGame: function (iframe, url) {

            self.gameLoader.launchGame(iframe, {
                'url': url,
            });
        },

    };
} )();
</script>
