<?php
require_once __DIR__ . '/../phive/phive.php';
phive()->sessionStart();
$lang = $_GET['lang'];
$end = $_GET['end'];

$playtech  = phive('Playtech');
$uid = cu()->userId;


if (!empty($uid)) {
    $user_lang = ud()['preferred_lang'];
    $extToken = $playtech->getGuidv4($uid);
    // add prefix for stage
    $uid = ($playtech->getSetting('test')) ? $playtech->getSetting('prefix').$uid : $uid;
    $playtech->toSession($extToken, $uid, $_GET['gid']);
}

function launchClient($target, $type, $gid){
    if ($target == "mobile")
        echo("launchClient('ngm_mobile','$type','$gid');");
    else
        echo("launchClient('ngm_desktop','$type','$gid');");
}

if ($end === "true"):?>
<html>
	<head>
		<title>Playtech Game Page</title>
	</head>

	<body style="margin: 0; padding: 0;background-color:black;"> </body>
</html>

<?php else:?>

<html>
	<head>
		<title>Playtech Game Page</title>

<?php
        loadJs("/phive/js/underscore.js");
        loadJs("/phive/js/jquery.min.js");
        loadJs("/phive/js/gameplay.js");
?>
        <script src="<?= $playtech->getLicSetting('PAS_API') ?>"></script>
	</head>

	<body style="margin: 0; padding: 0;">
		<div id="playtech">
			<script type="text/javascript">

                var user = '<?= $uid; ?>';
                var token = '<?= $extToken; ?>';
                var language = '<?= $user_lang; ?>';

				//Define login listener function
				function callbackLogin(response) {
					console.log("callbackLogin");
						if (response.errorCode) {
                            <?php launchClient($_GET['target'],'offline', $_GET['gid'])?>
						} else {
                            <?php launchClient($_GET['target'],'real', $_GET['gid'])?>
						}
							console.log(response);
						}

				//Define logout listener function
				function callbackLogout(response) {
					if (response.errorCode) {
     					//handle error, game launching is not possible
     					// alert("callbackLogout failed");
                    } else {
                     	// create a new session and launch the game
                    }
                    iapiGetLoggedInPlayer(1); // check/create session & launch game
                    console.log(response);
        		}

                function callbackGetLoggedInPlayer(response){
                	if (response.errorCode) {
                		alert("callbackGetLoggedInPlayer failed");
                	} else {
                		if (response.cookieExists == "1") {
                            <?php launchClient($_GET['target'],'real', $_GET['gid'])?>
                        } else {
                            iapiLoginUsernameExternalToken(user, token, "1", language);
                        }
                    }
                    console.log(response);
                }

				function launchClient(clientUrl,mode,gamecode) {
                	var gamecode = gamecode; //"bib";

                	//Set mode!
                	var mode = mode; //offline or real

                    //Select correct launcher based on link click.
                    //Pull game client links from configuration in IMS.
                    var flashclient = iapiConf['clientUrl_casino'];
                    var ngmdesk = iapiConf["clientUrl_ngm_desktop"];
                    var ngmmobile = iapiConf["clientUrl_ngm_mobile"];
                    var flashlive = iapiConf["clientUrl_live_flash"];
                    var live = iapiConf["clientUrl_live"];
                    var livemob = iapiConf["clientUrl_live_mobile"];
                    var html_poker = iapiConf["clientUrl_html_poker"];
                    var html_pokermob = iapiConf["clientUrl_html_pokermob"];

                    if (clientUrl == "ngm_desktop" && ngmdesk != null){
                        var ngmgame = gamecode;
                        iapiSetClientParams(clientUrl, "language=en&advertiser=ptt&fixedsize=1");
                    } else if (clientUrl == "ngm_mobile" && ngmmobile != null){
                        var ngmgame = gamecode;
                        iapiSetClientParams(clientUrl, "language=en&advertiser=ptt&fixedsize=1");
                        iapiLaunchClient(clientUrl,ngmgame,mode,'_self');

					/* To keep for future reference
                    } else if (clientUrl == "casino" && flashclient != null){
                        iapiSetClientParams(clientUrl, "language=en&advertiser=ptt");
                        iapiLaunchClient(clientUrl, gamecode, mode, 'testframe');
                    } else if (clientUrl == "live" && live != null){
                    	window.open(live+gamecode+"/",'_blank');
                    } else if (clientUrl == "live_mobile" && livemob != null){
                    	window.open(livemob+gamecode+"/",'_blank');
                    } else if (clientUrl == "htmlpoker" && html_poker != null){
                    	window.open(html_poker,'_blank');
                    } else if (clientUrl == "htmlpokermob" && html_pokermob != null){
                		window.open(html_pokermob,'_blank');
                    } else if (clientUrl=="live_flash" && flashlive != null){
                		window.open(flashlive+gamecode,'_blank');
					*/
                    } else {
                    	alert("Game client not defined in the IMS");
                    }

                    iapiSetClientPlatform("web");
                	//iapiSetClientParams(clientUrl, "language=en&advertiser=ptt&fixedsize=1&debug=1");
                	iapiSetClientParams(clientUrl, "language=<?= $user_lang;?>&advertiser=ptt&fixedsize=1");
        			iapiLaunchClient(clientUrl, gamecode, mode ,'_self');
                }

                window.onload = function() {
                    //Define listener for login callout.
                    iapiSetCallout(iapiCALLOUT_GETLOGGEDINPLAYER, callbackGetLoggedInPlayer);
                    iapiSetCallout(iapiCALLOUT_LOGIN, callbackLogin);
                    iapiSetCallout(iapiCALLOUT_LOGOUT, callbackLogout);

                    <?php   if (empty($uid)):
                                launchClient($_GET['target'],'offline', $_GET['gid']);
                            else: ?>
                        		// login out the user from any previous session and in the callback we create new sessiona and launch the game
								$realMode = 1;
                        		$allDevices = 1; // 0: NO , 1:yes
                    			iapiLogout($allDevices, $realMode);
                    <?php   endif; ?>
				};

			</script>
		</div>
	</body>
</html>

<?php endif ?>
