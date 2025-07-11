<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/phive/phive.php';

$greentube = phive('Greentube');

$home_url = phive('Casino')->getLobbyUrl(false, $_REQUEST['lang']);

$help_url = $home_url . $greentube->getSetting('help_url');

?>
<script type="text/javascript">
    var MessageProcessor = (function (){

        return {
            test: '<?= $greentube->getSetting('test') ?>',
            entryPoint: null,
            postParams: null,
            getParams: null,
            launcherType: null,
            roundEnded: true,
            pendingRcFunctions: [],

            init: function () {

            },
            loadGame: function (iframe, url) {
                self.gameLoader.launchGame(iframe, {
                    'url': url,
                });

            },

            pauseGame() {
                return new Promise((resolve,reject) => {
                    let pauseGameFunction = ()=> {

                        this.request('{"action" : { "name" : "pauseRendering"}}');

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
                    this.request('{"action":{ "name" : "resumeRendering"}}');
                    resolve();
                });
            },

            process: function (event) {
                var type = this.getEventType(event);
                console.log('type:' + type);
                var data = this.getEventData(event);
                console.log('data', data);


                switch (type) {
                    case 'gameClosing':
                        this.gameCloseCallback();
                        break;
                    case 'OPENHELP:':
                        this.redirectToHelp();
                        break;
                    default:

                        break;
                }
            },

            gameCloseCallback: function() {
                var home_url = '<?=$home_url ?>';
                window.top.location.href = home_url;
            },

            redirectToHelp: function () {
                var help_url = '<?=$help_url ?>';
                window.top.location.href = help_url;
            },

            startGameCommunicator: function (callback) {

            },

            getEventType: function (event) {

                try {
                    var json_data = JSON.parse(event.data);
                } catch(e) {
                    return event.data;
                }

                if(json_data.hasOwnProperty('state')) {
                    return json_data.state.name;
                } else if(json_data.hasOwnProperty('event')) {
                    return json_data.event.name;
                } else {
                    return false;
                }

            },

            getEventData: function (event) {
                try {
                    var json_data = JSON.parse(event.data);
                } catch(e) {
                    return false;
                }

                return json_data
            },

            setGameLoader: function (gameLoader) {
                self.gameLoader = gameLoader;
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
