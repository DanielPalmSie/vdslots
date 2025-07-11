<?php
    require_once __DIR__ . '/../phive/phive.php';
    phive()->sessionStart();

    /*
        CHECK IF: BOUNCE BACK FROM TOUCH DEVICES

    */
    $lobbyUrl = phive('Casino')->getLobbyUrl(false, $_GET['lang']);
    if (isset($_GET['close_game'])) {
    // we have to call the parent javascript function for closing the iframe and redirect to lobby
    ?>
    <script type="text/javascript">
        window.top.location.href = '<?= $lobbyUrl?>';
    </script>
    <?php
        exit(0);
    }
    /*
        ELSE: NORMAL LAUNCH
    */
    // Load NetEnt module
    /** @var Netent $net */
    $net = phive('Netent');
    $sGameId = $net->fixGid($_GET['gid'],true)."_sw";

    if(strpos($sGameId, 'videopoker') !== false || strpos($sGameId, 'blackjack') !== false){
        $swf = preg_replace('/^lr|^hr|^sh|^fh|^th|^fiftyh|^tfh|^tenh/', '', $sGameId);
        $map = array('videopokerjob' => 'videopokerflash', 'videopokerdw' => 'deuceswild');
        if(in_array($swf, array_keys($map)))
            $sGameId = $map[$swf];
    }
    $bIsMobile = isset($_GET['channel']) && $_GET['channel']  == 'mobg' ? true : false;
    $oGame = phive('MicroGames')->getByGameId("netent_".$sGameId, $bIsMobile ? 1 : 0); // Game data

    $user = cu();
    $show_demo = (bool)filter_var($_GET['show_demo'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if(empty($oGame)) {
        $oGame = phive('MicroGames')->getOriginalGame("netent_".$sGameId, $user, getCountry($user), $bIsMobile ? 1 : 0);
    }

    $bIsLiveCasino = isset($oGame['tag']) && $oGame['tag'] == 'live-casino' ? true  : false;

    $config = getConfig($bIsLiveCasino,$bIsMobile, $oGame, $show_demo);
    $net->dumpTst('netent_launch_config', compact('config'));

    /* NETENT has 4 different configuration depending on the game, if it's live-casino and if it's mobile */
    function getConfig($isLive= false,$isMobile=false, $oGame, $show_demo = false)
    {
        /** @var Netent $net */
        $net = phive('Netent');
        $gameInIframe = phive('MicroGames')->gameInIframe($oGame);

        $sSessionId = (isLogged() && !$show_demo) ? $net->getSid($_GET['channel'] ?? '', false, '', $_GET['mp_id']) : 'DEMO-'.rand(1, 1000000)."-".ciso();
        $bet_lvls_str = null;
        $jurisdiction = licJur();
        $user = cu();
        $platform = $isMobile ? 'mobile' : 'desktop';

        $config = [ // common and optional parameters
            "targetElement" => 'netEntGame',
            //"lobbyURL" => $_GET['lobbyUrl'] ?? phive()->getSiteUrl(),
            "enforceRatio" => true,
            'language' => $_GET['lang']
        ];

        if($net->isTournamentMode()){
            $betLvls   = phive('Tournament')->getBetLvlArr($net->getTournamentEntry());

            if (!empty($betLvls) && is_array($betLvls) && $betLvls[0] >= 0.1) { // sanity check for wrong values
                $config['customConfiguration'] = [
                    'coinValues' => $betLvls,
                    'defaultCoinValue' => 1,
                    'defaultBetLevel' => 1
                ];
            }
        }

        // Buttons and reality checks if we have
        if ($net->getRcPopup($platform, $user) === 'ingame' && empty($_GET['mp_id'])) {
            $sRealityCheckURL = $net->getSettingOrDefault('reality_check_param',$user);
            $config['pluginURL'] = $sRealityCheckURL;
        }


        // for mobile, modify the lobby url in order to post message and exit from the iframe
        // $lobbyUrl = phive()->getSiteUrl();
        $lobbyUrl = phive('Casino')->getLobbyUrl(false, $_GET['lang']);
        // the redirection must be passed to netent_new with the params close_game=true
        if ($gameInIframe == true){
            $lobbyUrl = phive()->getSiteUrl() . "/diamondbet/netent_new.php?close_game=true&lang={$_GET['lang']}";
        }


        if ($isLive && !$isMobile) {
            $config = $config +  [
                "lobbyURL" => $lobbyUrl,
                "gameId"=> $net->fixGid($_GET['gid'],true)."_sw",
                "gameServer"=> $net->getSettingOrProxy('game_url', $user),
                "staticServer"=> $net->getSettingOrProxy('live_static_url', $user),
                "casinoBrand"=> $net->getSettingOrProxy('op_id', $user),
                "sessionId"=> $sSessionId,
                "tableId"=> $oGame['module_id'],
                "liveCasinoHost" => str_replace("https://","", $net->getSettingOrProxy('live_mpp_url', $user)), //New parameter for the HTML5 games
            ];
        }elseif ($isLive && $isMobile) {
            $config = $config +  [
                "lobbyURL" => $lobbyUrl,
                "gameId" => $net->fixGid($_GET['gid'],true)."_sw",
                "gameServer" => $net->getSettingOrDefault('mob_server', $user), //Note that the value is different from the flash desktop launch code value.
                "staticServer" => $net->getSettingOrProxy('live_static_url', $user),
                "sessionId" => $sSessionId, //Enter a valid sessionID, please make sure that the new gameIDs are configured in the wallet configuration.
                "tableId" => $oGame['module_id'], //Same tableIDs as for desktop games, please use the getOpenTables() funciton in order to receive a list of all current live tables.
                "liveCasinoHost" => str_replace("https://","", $net->getSettingOrProxy('live_mpp_url', $user)), //New parameter for the HTML5 games
                "casinoBrand" => $net->getSettingOrProxy('op_id', $user),
            ];

        }elseif (!$isLive && !$isMobile) {
            $config = $config +  [
                "lobbyURL" => $lobbyUrl,
                "gameId"=> $net->fixGid($_GET['gid'],true)."_sw",
                "gameServerURL"=> $net->getSettingOrProxy('game_url', $user),
                "sessionId"=> $sSessionId,
                "staticServerURL"=> $net->getSettingOrProxy('static_url', $user),
            ];
        }elseif (!$isLive && $isMobile) {
            $config = $config +  [
                "lobbyURL" => $lobbyUrl,
                "gameId"=> $net->fixGid($_GET['gid'],true)."_sw",
                "staticServerURL"=> $net->getSettingOrProxy('static_url', $user),
                "gameServerURL"=> $net->getSettingOrProxy('game_url', $user),
                "sessionId"=> $sSessionId,
            ];

        }
        if ($isMobile) {
            $config = array_merge($config, [
                "allowHtmlEmbedFullScreen" => true,
                "launchType" => "iframeredirect",
                "iframeSandbox" => "allow-scripts allow-popups allow-popups-to-escape-sandbox allow-top-navigation allow-top-navigation-by-user-activation allow-same-origin allow-forms allow-pointer-lock"
            ]);
        }

        if (lic('hasGameplayWithSessionBalance', []) === true && isLogged() && !$show_demo && !$net->isTournamentMode()) {
            $net->initNetentExternalGameSession($user, $config['sessionId'], $oGame);
        }

        return $config;
    }

?>
<!DOCTYPE html>
<html>
    <head>
        <title>NetEnt Game Page</title>
  </head>
  <body style="margin: 0; padding: 0;">
    <div id="netEntGame"></div>
    <!-- GAME INCLUSION GP LIBRARY -->
    <script type="text/javascript" src="<?= $net->getSettingOrDefault('static_url',$user) ?>/gameinclusion/library/gameinclusion.js"></script>

    <?php
    loadJs("/phive/js/underscore.js");
    loadJs("/phive/js/jquery.min.js");
    loadJs("/phive/js/gameplay.js");
    ?>

    <script type="text/javascript">
        /* ELSE: Normal game execution */
        var startGame = function() {
            /* GAME LAUNCH CONFIGURATION*/
            var config = <?= json_encode($config); ?>;
            config['width'] = $(window).width();
            config['height'] = $(window).height();
            // END OF CONFIG

            /* EVENT LISTENERS */
            <?php if ($bIsLiveCasino): ?>
            var success = function (netEntExtend) {
                /*NOTHING: It redirects*/
            };
            <?php else: ?>
            var success = function (netEntExtend) {
                parent.gameFi = netEntExtend;
                netEntExtend.resize($(window).width(), $(window).height());
                 $(window).on("resize", function () {
                     netEntExtend.resize($(window).width(), $(window).height());
                 });

                _.each(gameEvents, function (doFunc, funcName) {
                    netEntExtend.addEventListener(funcName, function () {
                        <?php if (!empty($_GET['mp_id'])): ?>
                        doFunc();
                        <?php endif ?>
                        window.parent.postMessage({type: funcName}, "*");
                    });
                });

            };
            <?php endif; ?>
            var error = function (e) {
                alert("error");
                console.log(e);
            };

            netent.launch(config, success, error);
        };

        window.onload = startGame;
    </script>

  </body>
</html>
