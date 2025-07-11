<?php 
    require_once $_SERVER['DOCUMENT_ROOT'].'/phive/phive.php';
?>
<script type="text/javascript">
let DefaultMessageProcessor = (function (){
    let gameLoader = null;
    /**
    *  Custom javascript for ----- mobile game launches
    */

    return {
        test: true, // log incoming and outgoing messages
        entryPoint: null,
        postParams: null,
        getParams: null,
        launcherType: null,
        roundEnded: true,
        pendingRcFunctions: [],

        init: function () {
            //postParams :  
        },

        /**
        *   Each provider has a different way of loading the iframe 
        */
        loadGame: function (iframe, url) {

            // A. for normal providers that doesn't have their own libraries to launch the game
            self.gameLoader.launchGame(iframe, {
                // 'type' : Type of launch, choose between:
                //      'src' [default if left blank], 
                //      'srcDoc' -> add the post params to be sent in the attribute 'postParams'
                //      'innerHtml' -> add param requestType ('GET' or 'POST'), if post add also param postParams
                'url': url, // with query params included
                // 'postParams': {'gameId': ....}
                // 'callback' : function <- returns the control after game set in iframe
            });

            // B. for providers like Netent that use their own libraries... forget about A (AKA don't use gameLoader)
                // 1. Instead of loading a diamondbet script like netenet_new.php, 
                //    take care of all in this class
                //    (THE URL OF getDepUrl OR _getUrl WILL BE IGNORED )
                // 2. Implement in the game provider module the function 'getNetworkLibraries'
                //    this function returns an array with the URLS of the libraries, this libraries 
                //    will be inserted in MobileIframePlayBoxBase.php before this script is loaded 
                //    so you will have access to the library functions from this object
                // 3. Perform the game launch, you have access from here to phive and library functions as normal
                //    You have the iframe DOM object, so you have iframe.id, etc... 
        },
        
        /* For Reality Checks, we need to pause the game when we are showing the message 
        *  Use the network postmessages or the method they provide to pause the game 
        *  Use this.request to send postMessages from this file
        *  Returns a promise as you may need to wait for the round to end before resuming 
        */
        pauseGame() {
            return new Promise((resolve,reject) => {
                let pauseGameFunction = ()=> {
                    this.request({"eventid" : "pausegame"}); // Example from Thunderkick API
                    setTimeout(function() {
                        resolve(); // always call this function
                    }, 500);

                };
                if (this.roundEnded) {  // direct execution 
                    pauseGameFunction();
                } else {                // delayed execution
                    this.pendingRcFunctions.push(pauseGameFunction);
                }                
            });
        }, 

        /* For Reality Checks, we need to resume the game when the user clicks on continue 
        *  Use the network postmessages or the method they provide to resume the game 
        *  Use this.request to send postMessages from this file
        *  Returns a promise as you may need to wait before resuming 
        */
        resumeGame() {
            return new Promise((resolve,reject) => {
                this.request({'eventid': 'resumegame'}); // Example from Thunderkick API
                resolve(); // always call this function
            });
        },

        /**
        *  Process the events sent from the game to the iframe  
        *  This is our defaul API that providers can use
        */
        process: function (event) {
            const type = this.getEventType(event);
            const data = this.getEventData(event);

            switch (type) {
                case 'lobbyRedirect':
                    this.backToLobbyCallback(); // this function is defined on MobileIframePlayBoxBase  
                    break;
                case 'depositRedirect':
                    this.backToCashierCallback();
                    break;
                case 'historyRedirect':
                    this.backToHistoryCallback();
                    break;
                case 'redirect':
                    this.redirectCallback(data.url);
                    break;
                default:
                    // process other events if necessary 
                    break;
            }            
        },

        /* Some provider have a large amount of events, and provide a way to subscribe to events on demand*/
        startGameCommunicator: function (callback) {
            // let empty if nothing to subscribe to, check playngo for implementation example
        },

        
        /**
        *  Each provider has a different way of sending the type of the event
        *  The logic here is different for each one
        */
        getEventType: function (event) {
            if (!event.data.type) return;     
            return event.data.type;
        },

        /**
        *  Each provider has a different way of sending the type of the event
        *  The logic here is different for each one
        */
        getEventData: function (event) {
            if (!event.data.data) return;     
            return event.data.data;
        },

        /* Sets the game loader that inserts the game in the iframe*/
        setGameLoader: function (gameLoader) {
            self.gameLoader = gameLoader;
        },

        /* It will execute all funcions for the RC popup and then return control to the RC */
        execPendingRcFunctions: function() {
            while(this.pendingRcFunctions.length){
                let rcFunction = this.pendingRcFunctions.shift();
                rcFunction();
            }                 
        },

        backToLobbyCallback: function (event) {
            window.location.href = '<?= phive('Casino')->getLobbyUrl(false, $_REQUEST["lang"]) ?>';
        },

        backToCashierCallback: function (event) {
            goTo('/cashier/deposit/');
        },

        backToHistoryCallback: function (event) {
            goTo("/account/" + '<?= ud()['username'] ?>' + "/game-history/");
        },

        redirectCallback: function (url) {
            goTo(url);
        }, 

        bosReloadBalance : function(){
            this.request({req: "reloadBalance"});   
        },

        isJsonParsable: function (str) {
            try {
                JSON.parse(str);
            } catch (e) {
                return false;
            }
            return true;
        },

        waitRoundFinished: function (callback) {
            var onRoundFinished = function () {
                callback();
            }.bind(this);
            if (this.roundEnded) {
                onRoundFinished();
            } else { 
                this.pendingRcFunctions.push(onRoundFinished);
            }
        },
    };
} )();
</script>
