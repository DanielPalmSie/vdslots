<?php
require_once __DIR__ . '/../phive/phive.php';
require_once __DIR__ . '/../phive/html/common.php';

phive()->sessionStart();

$pager = phive('Pager');
$loc = phive('Localizer');
$mg = phive('MicroGames');
$casino = phive('Casino');
$uh = phive('UserHandler');
$langtag = $loc->getCurNonSubLang();
$user = cu();

lic('preventMultipleGameSessions', [$user, true], $user);
// baseRedirects( $langtag, $user ); // TODO implement this later

$pager->initPage();

// We get the game URLs from the argX variable extracted via nginx.
$games_url = [
    'arg0' => $_GET['arg0'],
    'arg1' => $_GET['arg1'],
];

$games = [];
$launch_urls = [];
foreach ($games_url as $index => $game_url) {
    // To avoid fallback to game with id=0
    if(empty($game_url)) {
        continue;
    }

    // in most cases mobile games doesn't have "game_url", so we need to grab the "game_url" from the desktop version and then load the mobile version of the game
    // but in some scenarios we have the "game_url" defined directly on mobile (Ex. starburst) so we need to take in account both scenarios.
    $mobile_game = null;
    $desktop_game = $mg->getByGameUrl($game_url);
    if (empty($desktop_game)) {
        $mobile_game = $mg->getByGameUrl($game_url, "device_type = 'html5'");
    }

    // we get the mobile game from the desktop.
    if (empty($mobile_game)) {
        $mobile_game = $mg->getMobileGame($desktop_game);
    }

    // No game found - skipping the rest.
    if (empty($mobile_game)) {
        continue;
    }
    $games[] = $mobile_game;
    $show_demo = filter_var($_GET['show_demo'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $mg->handle_redirect_url = true;
    list($launch_url, $redirect_url) = $mg->onPlay($mobile_game, ['lang'=>$langtag, 'type'=>'mobile', 'game_ref'=> $mobile_game['ext_game_name'], 'show_demo' => $show_demo]);
    // we have some action required by the user (Ex. deposit limit), so we redirect before going back
    // and we set as return url the current page
    if(!empty($redirect_url)) {
        phive('Redirect')->to($redirect_url, $langtag);
    }
    if(!empty($launch_url)) {
        $network_name = $mg->getNetworkName($mobile_game);
        if (phive()->moduleExists($network_name)) {
            $gp_module = phive($network_name);
            $rc_popup_type = $gp_module->getRcPopup('mobile');

            // TODO: this is a temporary fix until GameCommunicator refactor is live
            $jspath = __DIR__ . "/../phive/modules/Micro/js/GameCommunicator/{$network_name}MobileGameCommunicator.js";
            if(file_exists($jspath)) {
                $post_message_handler =  $network_name.'MobileGameCommunicator.js';
            }
        }

        $clean_game = [
            'network' => $mobile_game['network'],
            'game_id' => $mobile_game['game_id'],
            'launch_url' => $launch_url,
            'ext_game_name' => $mobile_game['ext_game_name'],
            'rc_popup' => $rc_popup_type
        ];

        $launch_urls[] = $clean_game;
    }

    // We set the meta only for the first game
    if ($index === "arg0") {
        phive('Pager')->setMetaDescription(rep(tAssoc('game.description.play', $mobile_game)));
        phive('Pager')->setTitle(rep(tAssoc('game.title.play', $mobile_game)));
    }
}

// No game found - unable to retrieve any game from the provided "game_url"
if (empty($games)) {
    // t('no.game.html')
    phive('Redirect')->to('/mobile/404/game-not-found', $langtag);
}

// check if the user has any limits and redirect to error page
if (phive()->getSetting('lga_reality') === true && isLogged()) {
    // Check for RG limit for a player
    $msg = $casino->lgaLimitsCheck($user, false);
    // Allow to play Free Spins even if reached RG limit
    $isFreeSpinActive = phive('CasinoBonuses')->isFreeSpinGameSession($user, $games[0]);
    if ($msg != 'OK' && !$isFreeSpinActive) {
        $url = "/mobile/message/?showstr=$msg";
        phive('Redirect')->to($url, $langtag);
    }
    lic('onLoggedPageLoad', [$user], $user);
}

// Unable to retrieve a launch_url for the requested games - (Ex. current config has an error, the game is blocked for that country, etc...)
if (empty($launch_urls)) {
    // t('invalid.launch.url')
    phive('Redirect')->to('/mobile/404/invalid-game-launcher', $langtag);
}

if (!empty($user) &&
    ((lic('getLicSetting', ['gamebreak_24']) || lic('getLicSetting', ['gamebreak_indefinite'])) || !empty($user->getRgLockedGames())) &&
    $user->isGameLocked($games[0]['tag']))
{
    $_SESSION['locked_game_popup'] = $games[0]['tag'];
    phive('Redirect')->to("/mobile/", $langtag);
}

$url_args = [false, $langtag, 'mobile'];
$notifications = [];
foreach ($uh->getLatestNotifications('', 12) as $notification) {
    $notifications[] = [
        'img' => $uh->eventImage($notification, true),
        'str' => $uh->eventString($notification, 'you.')
    ];
}

$mg->addMultiplayGameFlagsToGameList($launch_urls, $games);
$start_game = $games[0];
$redirect_deposit_url = '';
$is_redirect_from_deposit = false;
$disabled_add_funds = phive('MicroGames')->getSetting('balance_reload_disabled_networks', []);
if($_GET['iframeUrl']) {
    $parsed_url = parse_url($_GET['iframeUrl']);
    if(stripos($parsed_url['path'], 'deposit') !== false && stripos($parsed_url['query'], 'end=true') !== false) {
        $redirect_deposit_url = $_GET['iframeUrl'];
        $is_redirect_from_deposit = true;
    }
}

if(!empty($user) && 'F' === $user->getData('sex')[0]) {
    $avatar_url = 'Female_Profile.jpg';
} else {
    $avatar_url = 'Male_Profile.jpg';
}

// This will be passed into the page as JSON encoded object and will be used by the JS logic to load the proper game.
$config = [
    'user_is_logged' => isLogged(),
    'language' => $langtag,
    'currency' => cs(),
    'jurisdiction' => licJur(),
    // game(s) URLs for loading inside an iframe
    'games' => $launch_urls,
    // trigger the deposit iframe automatically when loading the page with "deposit_page_redirect" instead of "deposit_page" URL.
    'show_deposit_result_on_load' => $is_redirect_from_deposit,
    // for the game strip info button (redirections and URLs for pages / iframes )
    'urls' => [
        'home'         => $casino->getLobbyUrl(...$url_args),
        'register'     => $casino->getRegistrationUrl(...$url_args),
        'login'        => $casino->getLoginUrl(...$url_args),
        'user_account' => isLogged() ? $uh->getUserAccountUrl('', $langtag, 'mobile') : '',
        'deposit_page' => isLogged() ? llink('/mobile/deposit/') : '', // link to load the "Deposit" box only.
        'rewards_page' => isLogged() ? $casino->getAjaxBoxInIframeUrl('my-prize') : '', // link to load the "My rewards" box only.
        'deposit_page_redirect' => $redirect_deposit_url // Link used to display successful deposit when a redirect comes from a 3rd party (Ex. swish), triggered by
    ],
    // mainly for images URLs
    'assets' => [
        'game_empty_container_bg' => phive('ImageHandler')->img("click.here.play", 1264, 950)[0],
        'avatar_url' => '/diamondbet/images/' . brandedCss() . $avatar_url,
        'add_funds_img' => '/diamondbet/images/add-funds.png'
    ],
    // localized strings for popup headers etc.
    'strings' => [
        'deposits' => t('deposit'),
        'rewards' => t('my.rewards'),
        'games_search' => t('search.games'),
        'notifications' => t('notifications'),
        'errors' => [
            'multiplay_generic_error' => t('multiplay.error.generic'),
            'multiplay_not_supported' => t('multiplay.error.not.supported'),
            'multiplay_with_performance_issue' => t('multiplay.error.game.performance.issues')
        ],
        'ios_hide_toolbar' => t('ios.hide.toolbar'),
        'dont_show_again' => t('mp.dont.show.again'),
        'panic_button' => t('panic.button.action.text'),
    ],
    'notifications' => $notifications,
    'licensing_strip' => lic('getBaseGameParams', [$user], $user),
    'game_search' => [
        'api' => [
            'game_search' => phive()->getSiteUrl() . '/phive/modules/Micro/ajax.php?action=mobile-game-search',
            'game_launcher' => phive()->getSiteUrl() . '/phive/modules/Micro/ajax.php?action=get-mobile-game-launcher-url',
            'close_game_session' => phive()->getSiteUrl() . '/phive/modules/Micro/ajax.php?action=close-game-session',
        ],
        'bottom_strip' => [ // we need to pass this as an array or the object will fuck up the ordering of the elements in JS
            [ // search
                'label' => '', // t('search'),
                'icon' => 'vs-search',
                'filter_type' => 'search'
            ],
            [ // new games
                'label' => '', // t('new.cgames'),
                'icon' => 'vs-slot-machine',
                'filter_type' => 'new'
            ],
            [ // hot
                'label' => '', // t('hot'),
                'icon' => 'vs-flame',
                'filter_type' => 'hot'
            ],
            [ // popular
                'label' => '', // t('popular'),
                'icon' => 'vs-popular-icon',
                'filter_type' => 'popular'
            ],
            [ // last played
                'label' => '', // t('last.played'),
                'icon' => 'vs-arrows-last-played',
                'filter_type' => 'last_played'
            ],
        ]
    ],
    'rewards' => [
        'count' => phive('Trophy')->getUserAwardCount($user, array('status' => 0, 'mobile_show' => 1))
    ],
    'licensing' => [
        'multi_view_play' => lic('hasMultiViewPlay'),
        'delay_gameplay_after_action' => lic('delayMobileLaunch', [$user, $_GET['show_demo'] ?? false, $mobile_game], $user)
    ],
    'mobile_swipe_sign' => phive('MicroGames')->getSetting('mobile_swipe_sign', true),
    'game_play_session' => lic('getLicSetting', ['game_play_session']),
    'game_play_session_logos' => [
        'net_winnings' => '/diamondbet/images/netWinnings.png',
        'session_timer' => '/diamondbet/images/sessionTime.png',
    ],
    // TODO check if this need to be provided via lic function instead, currently force "= ES" on mobile game project. /Paolo
    'session_info' => [
        'currency' => cs(),
        'balance' => [
            'label' => t('session.balance'),
            'value' => '0'
        ],
        'won' => [
            'label' => t('won'),
            'value' => '0'
        ],
        'wagered' => [
            'label' => t('my.all.time.wagered'),
            'value' => '0'
        ]
    ]
];

// only for logged players we inject the "favorites" in the bottom strip
if(isLogged()) {
    $favorite_strip_config = [[ // favorite
        'label' => '', // t('my.favorites'),
        'icon' => 'vs-star',
        'filter_type' => 'favorites'
    ]];
    array_splice($config['game_search']['bottom_strip'], 4,0, $favorite_strip_config);

    $fast_deposit_provider = phive('Cashier')->getFastPsp();
    if (!empty($fast_deposit_provider)) {
        $config['fast_deposit'] = [
            'icon' => '/file_uploads/'.$fast_deposit_provider .'-mini.png',
            'callback' => 'showFastDepositPopup',
            'arguments' => [$fast_deposit_provider, false, 'mobile-play-page']
        ];
    }
}

// TODO add reality check and game communicator stuff.
?>

<html lang="<?= $langtag ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <?php loadCsrfFingerprintMobile() ?>
    <?php if ($title = $pager->getTitle()): ?>
        <title><?= htmlspecialchars(phive('Localizer')->getPotentialString($title)) ?></title>
    <?php else: ?>
        <title><?php echo phive()->getSiteTitle() ?> - Games</title>
    <?php endif; ?>
    <?php if ($desc = $pager->getMetaDescription()): ?>
        <meta name="description" content="<?= htmlspecialchars(phive('Localizer')->getPotentialString($desc)) ?>"/>
    <?php endif; ?>

    <?php loadBasePWAConfig() ?>
    <?php $pager->getHreflangLinks() ?>
    <?php
    loadCSS('/diamondbet/fonts/icons.css');
    loadCSS('/diamondbet/mobile_game/css/main.css');
    phive('Casino')->setJsVars('mobile');
    ?>
    <script>
        var CONF = <?= json_encode($config) ?>;
    </script>
</head>
<body class="split-game-mode">
<div id="vs-game-container" class="vs-game-container">
    <div id="vs-games-container" class="vs-games-container">
        <div id="vs-game-container__iframe-container-1" class="vs-game-container__iframe-container">
            <iframe id="game-container__iframe-1" class="vs-game-container__iframe responsive-iframe-fix"></iframe>
        </div>
    </div>
    <div id="vs-game-info-strip" class="vs-game-info-strip gradient-normal"></div>
</div>
<div id="vs-game-mode-overlap"></div>

<?php
loadJs("/phive/js/hammerjs/hammer.min.js");
loadJS('/diamondbet/mobile_game/js/main.js');
?>

<?php
/**
 * JS & CSS from the old site
 * Needed for example on the notifications when clicking on "playGameDepositCheckBonus()"
 * TODO for now tested only the normal response "ok" and the game was launching properly
 *  we need to test what happens when we get back messages like "force_self_assesment_popup" or "force_deposit_limit"
 *  and check if we need to include more JS/CSS code or even some HTML/Boxes from the old site.
 */
loadJs("/phive/js/underscore.js");
loadJs("/phive/js/multibox.js");
loadJs("/phive/js/mg_casino.js");
loadJs("/phive/js/utility.js");
loadJs("/phive/modules/Micro/play_mode.js");
loadJs("/phive/modules/Micro/mobile_play_mode.js");
loadJs("/phive/modules/Licensed/Licensed.js");
loadJs('/phive/modules/Micro/js/GameCommunicator/DefaultMobileGameCommunicator.js');
if (!empty($post_message_handler)) {
    loadJs('/phive/modules/Micro/js/GameCommunicator/'.$post_message_handler);
}
lic('loadJs', []);
if (phive()->methodExists('PayNPlay', 'loadJs') && phive('PayNPlay')->isActive()) {
    phive('PayNPlay')->loadJs();
}

// CSS
loadCss("/diamondbet/css/" . brandedCss() . "all.css");
// for extBoxAjax (Ex. when "force_self_assesment_popup" is triggered)
loadCss("/diamondbet/css/" . brandedCss() . "fancybox.css");
loadCss("/diamondbet/css/" . brandedCss() . "mobile.css");
loadCss("/diamondbet/css/".brandedCss()."pay-n-play.css");
if (phive()->methodExists('PayNPlay', 'loadCss') && phive('PayNPlay')->isActive()) {
    phive('PayNPlay')->loadCss();
}

lic('printRealityCheck', [$start_game]);
lic('loadGeoComplyJs', ['global']);
$mg->addNetworkJsLibraries($network_name);
?>
<?php if (isLogged()): ?>
    <script>
        // get notifications via websocket
        doWs('<?php echo phive('UserHandler')->wsUrl('notifications') ?>', function (event) {
            var notification = JSON.parse(event.data);
            // This event is defined in "vs-games-mobile" on project "videoslots-old-mobile-game" (contained into main.js)
            $(document).trigger('vsevent.notifications.showCustomNotification', notification);
        });

        // get updates on reward count
        doWs('<?php echo phive('UserHandler')->wsUrl('rewardcount') ?>', function (e) {
            var res = JSON.parse(e.data);
            $(document).trigger('vsevent.rewardsCounterChange', {counter: res.status0});
        });

        doWs('<?php echo phive('UserHandler')->wsUrl('game-play-session') ?>', function (e) {
            var res = JSON.parse(e.data);
            $(document).trigger('vsevent.netWinnings', {net: res.session.net_winnings});
        });

        // get logout msg via websocket
        doWs('<?php echo phive('UserHandler')->wsUrl('logoutmsg'.substr(session_id(), 0, 5), false) ?>', function (e) {
            closeVsWS();
            mgSecureAjax({action: 'obsolete-session'});
            mboxMsg(e.data, true, function () {
                gotoLang("/?signout=true");
            }, 300, false, false);
        });

        var lgaRealityCheck = true;

        gameMsgSetup('<?php echo phive('UserHandler')->wsUrl('lgalimitmsg' . $start_game['ext_game_name']) ?>', {showFastDepositPopup: ['mobile-play-page']});

        $(document).ready(function () {
            <?php lic('startExternalGameSession', [$user, $start_game['network'], $start_game['game_url'], $start_game, $_GET['show_demo'] ?? false], $user); ?>
            <?php lic('handleRgPopupInGamePage'); ?>

            <?php lic('doBalanceCheckInGamePlay', [$user], $user); ?>
        });
        //TODO move it to a proper place
        window.addEventListener("message", function (event) {
            var trustlyOrigins = ["https://trustly.com", "https://checkout.trustly.com", "https://test.trustly.com", "https://checkout.test.trustly.com"];
            if (!trustlyOrigins.includes(event.origin)) { return; }

            // Ensure that the origin of the message is Trustly
            var data = JSON.parse(event.data);
            if (data.method === "OPEN_APP") {
                // Opens the app by assigning the URL
                location.assign(data.appURL);
            }
        }, false);

        $(document).on('extSessionHandlerLoaded', function () {
            var disabled = '<?= json_encode($disabled_add_funds)?>';
            var session = window.extSessHandler.activeSessions[0];

            if (session && disabled.indexOf(session.network) < 0) {
                $("#top-bar-add-funds").removeClass('hidden');
            }
        });
    </script>
<?php endif; ?>
    <?php showRCPopupFromTestParam() ?>
    <?php lic('setGameSessionCloseListener', [uid()]) ?>
</body>
</html>
