<?php
require_once __DIR__ . '/../../phive/phive.php';


$net = phive('Netent');
$referer = $_SERVER['HTTP_REFERER']; 
$mob_server = $net->getSettingOrProxy('static_url',$ud);
// if (strpos($referer, $mob_server) === false) {
//     header('Location: https://www.videoslots.com/mobile');
//     exit();
// }


$user = cu();
$staticHost = $net->getSettingOrProxy('static_url',$ud);
$seLinks = lic('getBaseGameParams', [$user]); 
$session_start = (int)lic('rcElapsedTime', [$user]);
$reality_check_interval = phive('Casino')->startAndGetRealityInterval();
$reality_check_interval = empty($reality_check_interval) ? 0 : $reality_check_interval;
$lang = phive('Localizer')->getCurNonSubLang();
?>
<!DOCTYPE html>
<html>
    <head>

	<script type="text/javascript" src="<?=$staticHost?>/gameinclusion/library/gameinclusion.js"></script>  
  	<script type="text/javascript">
	// Sweden icons Plugin Version 1.0
    var inGameRound = false;
    var execQueue = [];

    // Calls NetEnt Extend to add icon listeners. The icons have pre-defined ids.
    function addSwedenMarketButtons() {
        netent.plugin.addEventListener("spelgranser_click", function () {
        	handleInteraction( function() {
                // console.log("Navigate away to Spelgranser.");
                netent.plugin.call("stopAutoplay", [], function() {});
                // Put your code for handling spelgranser here.
                window.open("<?= $seLinks['accountlimits_url'] ?>","_top");
            });
        });
        netent.plugin.addEventListener("sjalvtest_click", function () {
            handleInteraction(
                function() {
                    // console.log("Navigate away to Sjalvtest.");
                    netent.plugin.call("stopAutoplay", [], function() {});
                    // Put your code for handling sjalvtest here.
                    window.open("<?= $seLinks['selfassessment_url'] ?>","_top");
                })
        });
        netent.plugin.addEventListener("spelpaus_click", function () {
        	handleInteraction( function() {
                // console.log("Navigate away to Spelpaus.");
                netent.plugin.call("stopAutoplay", [], function() {});
                // Put your code for handling spelpaus here.
                window.open("<?= $seLinks['selfexclusion_url'] ?>","_top");
            });
        });
    }
    
    function handleInteraction(f){
        // Here the click interactions are stacked and will be executed
        // once game enters idle mode, in the order in which the player
        // clicked the buttons.
        // The execQueue array can be cleared if only the most recent input
        // from player should be kept and acted upon.
        
        if(!inGameRound) {
        	f();
        } else {
            var o = {};
            o.execute = f
            execQueue.push(o)
        }
    }
    
    netent.plugin.addEventListener("gameReady", function () {
        // Success callback.
        addSwedenMarketButtons();
    });
        
    window.onload=function() {   
		netent.plugin.call("pluginReady", [], function() {/*console.log("pluginReady successful")*/}, function() {/*console.error("pluginReady FAILED")*/});

            
        netent.plugin.addEventListener("gameRoundStarted", function () {
        	inGameRound = true;
        	netent.plugin.get("betInCurrency", function(betInCurrency) {
        		var number = Number(betInCurrency.toString().replace(/[^0-9-]+/g,""));
        		bets = bets + number;
        		//console.log("Bet : " + number);
        	});
    	});
        
        netent.plugin.addEventListener("gameRoundEnded", function (){
            inGameRound = false;
            for(var j=0; j<execQueue.length;j++){
            	execQueue[j].execute();
            }
            execQueue = [];

            netent.plugin.get("winInCurrency", function (winInCurrency) {
            	var number2 = Number(winInCurrency.toString().replace(/[^0-9-]+/g,""));
				wins = wins + number2;
				//console.log("Win : " + number2);
			});
        });


        
        // Check if Realitycheck code is put in the same plugin
        if(realitycheckinit) {
            // Initialize the reality check plugin
            realitycheckinit();
        }
    }
    
    // Sweden Reality Check Plugin Version 1.0
    // This script assumes that a server session duration time has been retrieved
    // and is set in duration variable.
    // The duration is displayed using setter "inGameMessage" to the player in-game.
    // When Reality check message is displayed, player can select to continue
    // playing, or leave game.
    // If player selects to leave game, the player will be redirected to lobby
    // with reason codes 10 and 11 in this example.
    // The reason code, once intercepted by the lobby, can be used to redirect
    // player to e. g. game history page.
    // Time until reality check message, in seconds. 
    rc_params.rc_current_interval = <?=$reality_check_interval?>;
    var realitycheckmessage1 = "You have requested a Reality Check after %1 minutes of play.\n \
        						Your gaming session has now reached %2 minutes.\nTotal bets: SEK %3 Total wins: SEK %4 \n \
        						To continue playing, select Continue Playing or to stop playing, click Close Game.";
    var realitycheckmessage2 = "Select Game History to show game history or press Lobby to leave game and go to lobby.";
    var timer;
    // Update the duration parameter with the number of seconds in game session.
    var duration = <?php echo($session_start);?>;// <- Replace 0 with appropriate value as start value

    var wins = 0;
    var bets = 0;
    
    function realitycheckinit(){
        if (rc_params.rc_current_interval == 0) return;
        // Starts the updating of time in client
        timer = setInterval(setPluginTime, 1000); 	
        // Display the updated time in game client once every second.
        // Tell the game that the plugin has initialized. Plugin must call pluginReady
        // within 30 seconds, or game will provide error message to player.
        netent.plugin.call("pluginReady", [], function() {} );
        // Tell the game to show the clock in-game
        netent.plugin.call("showSystemClock", [], function(e) {}, function(e) {} );
        // Catch the player dialogbox interaction and route it to our own handler.
        netent.plugin.addEventListener("dialogBoxClosed", dialogboxbuttonhandler);
    }
    
    function setPluginTime(){
        // In implementation code, set duration parameter with the actual duration
        // from server. Here we will just emulate that time is passing.
        duration++;
        // Draw the timer on-screen
        updatetimer(duration);
    }

    function dialogboxbuttonhandler(box, buttonid) {
        // console.log("button pressed: "+buttonid);
        if(box == "realitycheck") {
            if(buttonid == "continue") {
                // No code needed here, dialog box will close automatically (it is
                // default behavior) and game play can continue.

            }else if( buttonid == 'close') {
                window.top.location = '<?= phive('Casino')->getLobbyUrl(false, $lang);?>';
            }else if( buttonid == 'history') {
                window.top.location = '<?= phive('Casino')->getHistoryUrl(false, $user, $lang);?>';
            } else {
                // Player has decided to leave the game. Display the next two options
                // to the player.
                clearInterval(timer);
                showseconddialogbox();

            }
        }
    }
    
    // Plugin will disable game if any error occurs within the plugin
    window.onerror = function() {
    	haltgame();
    }


    function haltgame() {
        // Stop updating the plugin.
        clearInterval(timer);
        // Notify the game that the plugin has encountered an error condition.
        netent.plugin.call("pluginError", [], function() {} )
        // Nothing more will happen from this point on.
    }


    function updatetimer(duration) {
        // Update the timer on screen
        var dt = new Date();
        var durationHours = String(Math.floor(duration / 3600));
        var durationMinutes = String(Math.floor((duration % 3600) / 60));
        var durationSeconds = String((duration % 3600) % 60);
    
        if(durationHours.length == 1) { durationHours = "0" + durationHours }
        if(durationMinutes.length == 1) { durationMinutes = "0" + durationMinutes }
        if(durationSeconds.length == 1) { durationSeconds = "0" + durationSeconds }
        var msgStr = "Session duration: " + durationHours + ":" + durationMinutes+":" + durationSeconds;
        var params = [ { "type":"text", "text":msgStr } ];
        // Displays message to player on screen, in the game
        netent.plugin.set("inGameMessage", params, function() { /* Message was updated */ }, function(e) { /* Something went wrong */ })
        if(duration > 0 && duration % (rc_params.rc_current_interval * 60) == 0) {
            // Stop any on-going autoplay.
            netent.plugin.call("stopAutoplay", [], function() {});
            buttons = [{buttonid:"continue", buttontext:"Continue playing"},
            {buttonid: "close", buttontext: "Leave game" }]
            var messageToShow =
            realitycheckmessage1.replace("%1",Math.floor(rc_params.rc_current_interval));
            messageToShow = messageToShow.replace("%2",Math.floor(duration/60));
            messageToShow = messageToShow.replace("%3",((bets/100).toFixed(2)));
            messageToShow = messageToShow.replace("%4",((wins/100).toFixed(2)));
            showDialogBox("Reality check", messageToShow, buttons);
        }
    }

    function showseconddialogbox() {
        var params = ["realitycheck"];
        netent.plugin.call("removeDialogbox", params, function(e) {
        /*console.log('Removed: success')*/ }, function(e) { /*console.log('failed: remove dialogbox'); console.log(e)*/ } );
        // Reason code 10 can be used to send player to game history page and reason
        // code 11 can be used to detect players coming back to lobby from this dialog
        // interaction
        buttons = [{buttonid:"history", action:"gotolobby", reason:10,
        buttontext:"Game History"},{buttonid: "lobby", action:"gotolobby", reason:11,
        buttontext: "Lobby" }]
        var messageToShow = realitycheckmessage2
        showDialogBox("Leave game", messageToShow, buttons);
    }
    
    var isFirstCall = true;
    
    function showDialogBox(header,text,buttons) {
        if(!isFirstCall) {
            // Avoid multiple dialogboxes, so remove the previous one (given that we
            // have already displayed it previously).
            var params = ["realitycheck"];
            netent.plugin.call("removeDialogbox", params, function(e) {
            /*console.log('Removed: success')*/ }, function(e) { /*console.error('failed: remove dialogbox'); console.error(e)*/ } );
        }
            
        isFirstCall = false;
        var params = ["realitycheck", header, text, buttons];
        netent.plugin.call("createDialogbox", params, function(e) {
        	/*console.log('Created: success')*/ }, function(e) { 
            /*console.error('failed: create dialogbox'); 
            console.error(e)*/ } );
    }

	</script>

    </head>
    <body>
    </body>
</html>
