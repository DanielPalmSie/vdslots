<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';
require_once __DIR__.'/ActivityFeedBoxBase.php';
require_once __DIR__.'/TournamentLobbyBoxBase.php';

class MgPlayBoxBase4 extends DiamondBox {

    /**
     * Game object
     */
    public $game = [];

    /**
     * Tournament singleton
     */
    public $th = null;

    /**
     * Tournament object
     */
    public $tournament = [];

    /**
     * Tournament entry
     */
    public $t_entry = [];

    /**
     * Tournament entry id
     */
    public $t_eid = '';

    /**
     * This will hold the user_id suffixed with the t_eid also known as token_uid
     */
    private $tournament_token_uid = null;

    /**
     * TournamentLobbyBoxBase instance
     */
    public $tl;

    /**
     * ActivityFeedBoxBase instance
     */
    public $af;

    /**
     * Game Url loaded in iframe
     */
    public $iframeUrl;

    /**
     * @return void
     */
    public function init()
    {

        $this->handlePost(['tags']);
        $this->tarr = explode(',', $this->tags);

        if($_GET['arg0'] == 'live-casino'){
            // 301 redirect from /play/live-casino to /casino/live-casino, this can be removed in the future /Paolo
            $lang = (cLang() == phive('Localizer')->getDefaultLanguage()) ? '' : '/'.cLang();
            $url = phive()->getSiteUrl().$lang.'/casino/live-casino';
            error_log('OLD Livecasino page supporting only NetEnt "/play/live-casino", user redirected to "/casino/live-casino" with a 301');
            phive('Redirect')->to($url, '');
            exit;
        }else{

            if(isLogged()){
                if(hasMp()){
                    $this->th = phive('Tournament');
                    // Battle start, here we lock onto if we have an entry id in the GET or not.
                    if(!empty($_REQUEST['eid'])){
                        $this->t_entry = $this->th->entryById($_REQUEST['eid']);
                        $this->tournament = $this->th->getByEntry($this->t_entry);

                        if($this->t_entry['user_id'] != $_SESSION['mg_id'])
                            die("This is not your tournament entry.");

                        $this->tl = new TournamentLobbyBoxBase();
                        $this->tl->init($this->tournament['id']);
                        $this->t_eid = $_REQUEST['eid'];
                    } else {
                        if (isset($_REQUEST['eid'])) $this->hasErrorLoadingTournament = true;
                    }
                }
                if(phive()->getSetting('lga_reality') === true) {
                    $user = cu();
                    lic('onLoggedPageLoad', [$user], $user);
                }
            }

            if(!empty($this->tournament)) {
                $this->tournament_token_uid = $_SESSION['mg_id']."e".$this->t_eid;
            }

            $game_url = $_GET['arg0'];
            $this->game = phive('MicroGames')->getByGameUrl($game_url);
        }
        $user = cu();
        lic('preventMultipleGameSessions', [$user, true], $user);

        if(!empty($_REQUEST['eid'])){
          $t_entry = empty($this->t_entry) ?  phive('Tournament')->entryById($_REQUEST['eid']) : $this->t_entry;
          $tournament = empty($this->tournament) ? phive('Tournament')->getByEntry($t_entry) : $this->tournament;
          $t_game = phive('Tournament')->getGame($tournament);
          if ($t_game['game_url'] != $_GET['arg0']) {
            $t_game_url = phive('Tournament')->playUrl($t_entry, $t_game);
            phive('Redirect')->to($t_game_url, cLang());
          }
        }

        if(!empty($this->game)){
            //phive('Pager')->setMetaDescription( empty($this->game['meta_descr']) ? phive()->decHtml($this->game['game_name']).' - '.phive()->getSetting('domain') : $this->game['meta_descr']);
            phive('Pager')->setMetaDescription(rep(tAssoc('game.description.play', $this->game)));
            phive('Pager')->setTitle(rep(tAssoc('game.title.play', $this->game)));

            $args = [
                'game_id' => $this->game['game_id'],
                'type' => 'flash',
                'lang' => cLang(),
                'user_id' => $this->tournament_token_uid ?? null,
                'show_demo' => $_REQUEST['show_demo'] ?? false,
            ];
            phMsetShard('curgid', $this->game['game_id'], $user);
            /**
             * TODO quick hotfix for return URL, this needs to be reworked properly.
             *  currently we cannot differentiate between URL for the Iframe VS URL for redirect in other ways.
             *  /Paolo
             */
            $on_play_result = phive('MicroGames')->onPlay($this->game, $args);
            list($launch_url, $redirect_url) = is_array($on_play_result) ? $on_play_result : [$on_play_result];
            $this->iframeUrl = $launch_url;
            if(!empty($redirect_url)) {
                phive('Redirect')->to($redirect_url);
            }
        }
        $this->mg = phive('MicroGames');
        $this->img_dir = "/diamondbet/images/" . brandedCss() . "game_page2/";

        if(phive('UserHandler')->getSetting('has_events') === true){
            $this->af = new ActivityFeedBoxBase();
            $this->af->show_feed_rows = 14;
            $this->af->setup();
        }

        if(phive()->moduleExists('Race'))
            $this->race = array_shift(phive('Race')->getActiveRaces());
    }
    function searchJs(){ ?>
    <script>
     $(document).ready(function(){
         <?php
            if (!empty($this->hasErrorLoadingTournament)) {
                $game_id = empty($this->game['id']) ? 0 : $this->game['id'];
                echo <<<JS
                    ajaxGetBoxHtml(
                        {func: 'showTournamentErrorDialog', game_id: '$game_id', is_play_box: 1},
                        cur_lang,
                        'TournamentBox',
                        function (ret) { mpBox(ret, 'tournament-error-box', 'parent', '400px');}
                    );
                JS;
            }
         ?>
         setupCasinoSearch(
             function(i, o){
                 var lang = (cur_lang === default_lang) ? '' : '/'+cur_lang;
                 str = '<li><a href="'+lang+'/games/'+this.game_url+'/">'+ellipsis(this.game_name, 20)+'<img src="'+'<?php echo getMediaServiceUrl(); ?>'+'/file_uploads/'+this.tag+'_icon.png" /></a></li>';
                 $("#search-result").append(str);
             },
             function(){
                 $("#search-result").html( $("#search-default").html() );
             }
         );
     });
    </script>
<?php }

function is404($args)
{
    if (empty($this->game))
        return true;
    if (count($args) > 1)
        return true;
    return false;
}

function js(){
    $this->printRaceJs();
    $has_lga_limits = !phive()->isEmpty(rgLimits()->getResettAble(cu()));
    $networkName = phive('MicroGames')->getNetworkName($this->game);
    phive('MicroGames')->addNetworkJsLibraries($networkName);
    $this->getGameCommunicator();
    $this->getGameLoader();
    $this->getDefaultMessageProcessor();
    $this->getMessageProcessor($networkName);
?>
    <script>
     // If current network does not support (does not exist in multiPlayNetworks) multi play we store it in the filterNetwork array.
     function handleMultiPlay(network){
         if(multiPlayNetworks.indexOf(network) == -1)
             filterNetwork.push(network);
     }

     //If trophy tab is open by cookie and we don't have a slot game we need to close it
     <?php if(!in_array($this->game['tag'], ['slots', 'videoslots','casino-playtech'])):  ?>
     if($.cookie('afContent') == 'trophy'){
         sCookie('afStatus', '');
         sCookie('afContent', '');
     }
     <?php endif ?>
     var currentGames = [];
     var currentGameIndex = 0;
     var iProgressBarHeight = 50;
     var g = <?php echo json_encode($this->game) ?>;
     var currentlyPlaying = {};
     // Networks that support multi play is configurable, we store them here.
     var multiPlayNetworks = <?php echo json_encode(phive('MicroGames')->getSetting('multi-play-gps')) ?>;
     var filterNetwork = [];

     <?php $this->tournamentCommon(); ?>

     currentlyPlaying[g.game_id] = g;

     // If current network does not support (does not exist in multiPlayNetworks) multi play we store it in the filterNetwork array.
     function handleMultiPlay(network){
         if(multiPlayNetworks.indexOf(network) == -1)
             filterNetwork.push(network);
     }

     handleMultiPlay(g.network);

     var lgaRealityCheck = false;
     <?php if(phive()->getSetting('lga_reality') === true && isLogged()): ?>
         <?php if($has_lga_limits || hasWs()): ?>
             var lgaRealityCheck = true;
             var lgaLimitsId = 0;
         <?php endif ?>

     <?php endif ?>


     function closeLgaReality(){
         $.multibox('close', 'mbox-popup');
         mboxClose();
     }

     function showPopup(content, goHome, gameRef, tournament, source, eid){
         if(goHome)
             $.multibox("close", "play-box");
         // If the message is not one of the below localized string aliases we reload the game iframe, otherwise not.
         // We also don't reload the game if we're supposed to redirect to the home page.
         if([
             'lgatime.reached.html',
             'mp.finished',
             'mp.cancelled',
             'frb.amount-msg.html',
             'frb.start-msg.html',
             'frb.end-msg.html'
             ].indexOf(source) === -1 && goHome != true){
             if(_.size(currentlyPlaying) == 1){
                 reloadIframe($('#mbox-iframe-play-box'));
             }else{
                 _.each(currentlyPlaying, function(g, gid){
                     if(g.ext_game_name == gameRef){
                         el = $("div[data-game-id='"+gid+"']").find('iframe').first();
                         reloadIframe(el);
                     }
                 });
             }
         }

         var options = {
             id: "mbox-popup",
             type: 'html',
             containerClass: 'game-msg-container',
             showClose: goHome ? false : true,
             onClose: function(){
                 if(source == 'lgatime.reached.html'){
                     goTo('<?php echo llink('/?signout=true') ?>');
                 }else if(goHome){
                     showLoader(function(){
                         goTo('<?php echo llink('/') ?>');
                     }, true);
                 }
             }
         };

         if(tournament == 'yes'){
             // BoS limits
             <?php if(hasMp()): ?>

                 options.content = content;
                 //TODO prettify the below copy paste mess
                 if(source == 'mp.finished'){
                     if(eid == '<?php echo $this->t_eid ?>')
                         mpFinishedAjax(eid, '<?php phive('Tournament')->finBkg() ?>');
                     return;
                 }else if(source == 'mp.cancelled'){
                     if(eid == '<?php echo $this->t_eid ?>')
                         mpCancelledRedirect(eid);
                     return;
                 }else
                     options.cls = 'mbox-deposit';

             <?php endif ?>

             $.multibox(options);

         }else if(typeof content !== 'undefined'){
             // Normal limits
             options.content = content + '<?php okCenterBtn('closeLgaReality()')  ?>';

             var gamePlayImage = !is_old_design ? 'max-bet-limit-reached.png' : '';
             mboxMsg(options.content, false, options.onClose, undefined, true, ...Array(6), 'game-msg-container', undefined, gamePlayImage);
         }
     }

     var lgaFunc = function(){
         if($("#mbox-popup").length > 0)
             return;
         mgAjax({action: 'lga_limits'}, function(ret){
             if(ret == "OK")
                 return;
             showPopup(ret, true);
             window.clearInterval(lgaLimitsId);
         });
     };

     function lga(extGameName = null){
         if(extGameName === null) {
             gameMsgSetup('<?php echo phive('UserHandler')->wsUrl('lgalimitmsg' . $this->game['ext_game_name']) ?>');
         }
         else{
             mgAjax({action: 'create-ws-for-split-game', ext_game_name: extGameName}, function (ret){
                 gameMsgSetup(ret);
             });
         }
     }

     /**
     * Subscribe to the inhouse frb channel to listen for frb popup or frb progress bar triggers
     */
     function setInhouseFrbWs(){
         // we subscribe to inhousefrb channel
         doWs('<?php echo phive('UserHandler')->wsUrl('inhousefrb') ?>', function(e) {
             var res = JSON.parse(e.data);

             if(['frb.start-msg.html', 'frb.end-msg.html', 'frb.amount-msg.html'].indexOf(res.source) >= 0){
                 // popup message on frb start or frb end
                 // popup message when player try to change amount informing not to alter amount
                 if($("#mbox-popup").length > 0){
                     return;
                 }
                 showPopup(res.msg, res.gohome == 'yes' ? true : false, res.game_ref, res.tournament, res.source, res.eid);

             } else if (['frb.remaining-msg.html'].indexOf(res.source) >= 0) {

                 // progress bar showing current frb remaining
                 if(res.gamedata.frb_remaining > 0){
                    $("#gameplay-mess-bar span strong").text(res.gamedata.frb_remaining);
                 } else {
                     cont.animate({top: "-="+iProgressBarHeight, opacity: 0}, 1000, function(){
                         cont.html('');
                     });
                 }
             }
         });
     }

     function showInhouseFrbProgress(){
         cont = $("#gameplay-mess-bar");
         ajaxGetBoxHtml({func: 'gamePlayProgressBar'}, cur_lang, 'MgPlayBox4', function(ret){
             if(ret != 'false'){
                 cont.html(ret);
                 cont.animate({top: "+="+iProgressBarHeight, opacity: 0.7}, 1000);
             }
         });
     }

     var curGame = <?php echo json_encode($this->game) ?>;
     var gameSelected = {
         'game_id':curGame.game_id,
         'game_name':curGame.game_name,
         'ext_game_name':curGame.ext_game_name,
         'network':curGame.network
     };
     currentGames.push(gameSelected);
     <?php if(phive('UserHandler')->getSetting('has_events') === true): ?>
     var fullAfWidth = 270;
     var afWidth = fullAfWidth / 2;
     <?php else: ?>
     var fullAfWidth = 0;
     var afWidth = 0;
     sCookie('afStatus', 'closed');
     sCookie('afContent', '');
     <?php endif ?>

     <?php if(!empty($this->tournament)): ?>
     sCookie('tStatus', 'open');
     var fullTwidth = 250;
     var tWidth = fullTwidth / 2;
     <?php else: ?>
     var fullTwidth = 0;
     var tWidth = 0;
     sCookie('tStatus', 'closed');
     <?php endif ?>


     function isDynamic(g){
         if(g.network == 'microgaming' || g.network == 'rival' || g.operator == 'Casino Technology' || g.network == 'redtiger')
             return false;
         return true;
     }

     function getNumSplit(){
         return $(".play-box-content").length;
     }

     var dynamic = true;
     function calcAspRatio(w, h) {
         if(typeof w == 'undefined')
             w = curGame.width;
         if(typeof h == 'undefined')
             h = curGame.height;
         return w / h;
     }

     function getPlayDims(w,h){
         var asp = 1.333;
         var gW = $(window).width();
         var gH = $(window).height();
         var dynamic = true;

         asp = getNumSplit() == 1 ? calcAspRatio() : calcAspRatio(w, h);
         dynamic = isDynamic(curGame);

         if($.cookie('afStatus') == 'open')
             gW -= fullAfWidth;

         if($.cookie('tStatus') == 'open')
             gW -= fullTwidth;

         if(getNumSplit() > 1)
             dynamic = true;

         var res = calcDims(asp, gW, gH, dynamic, curGame.width, curGame.height);
         var rW = Math.round(res[0]);
         var rH = Math.round(res[1]);
         return {width: rW, height: rH};
     }

     var initialWidth = undefined;
     function getresizeDims(w,h, resizeType) {
         var minimumWidth = (curGame.width > 1024) ? 1024 : curGame.width;;
         var expectedWidth;
         var aspectRadio;
         var currentWidth = $('#play-box').width();
         var countElementsGrid = parseInt($('.top-bar-pcontrols').attr('data-current-grid'));
         if (!initialWidth)
             initialWidth = currentWidth;


         if (typeof w == 'undefined' || typeof h == 'undefined') {
             aspectRadio = 1.3333;
         } else {
             aspectRadio = calcAspRatio(w,h); //1.3333
         }

         if (!countElementsGrid || countElementsGrid === 1) {
             aspectRadio = calcAspRatio();
         }


         if(resizeType) {
             expectedWidth =  currentWidth - 200;

             if (initialWidth && countElementsGrid === 2) {
                 minimumWidth = initialWidth - 200;
             }

             if (initialWidth && countElementsGrid === 4) {
                 minimumWidth = initialWidth + 100;
             }

             // Stop the resize when it is smaller than the  minimumWidth
             if (currentWidth < minimumWidth - 200)
                 minimumWidth = currentWidth + 200;

             if (expectedWidth < minimumWidth - 200)
                 expectedWidth = minimumWidth - 200;
         }
         else {
             expectedWidth =  currentWidth + 200;

             if (initialWidth && countElementsGrid === 2) {
                 maximunWidth = initialWidth + 200;
             }

             if (initialWidth && countElementsGrid === 4) {
                 maximunWidth = initialWidth - 100;
             }

             // Stop the resize when it is bigger than initial width, which is the considered as maximum possible width
             if (expectedWidth > initialWidth)
                 expectedWidth = initialWidth;
         }


         return { width: expectedWidth, height: expectedWidth / aspectRadio };
     }

     function openActivityFeed(){
         sCookie('afStatus', 'open');
         resizePlayBox();
         $("#activity-feed").css({width: fullAfWidth + 'px'});
         $("#af-nano").css({width: (fullAfWidth - 0) + 'px'});
         moveInc("#play-controls", [fullAfWidth, 0]);
         handleFeedButtons();
     }

     function handleFeedButtons(){
         if($.cookie('afStatus') == 'open'){
             if($.cookie('afContent') == 'af'){
                 $('#afclose-btn').show();
                 $('#afopen-btn').hide();
                 $('#raceclose-btn').hide();
                 $('#raceopen-btn').show();
                 $('#trophyclose-btn').hide();
                 $('#trophyopen-btn').show();
                 $('#wheelwinnersclose-btn').hide();
                 $('#wheelwinnersopen-btn').show();
             }else if ($.cookie('afContent') == 'race'){
                 $('#afclose-btn').hide();
                 $('#afopen-btn').show();
                 $('#raceclose-btn').show();
                 $('#raceopen-btn').hide();
                 $('#trophyclose-btn').hide();
                 $('#trophyopen-btn').show();
                 $('#wheelwinnersclose-btn').hide();
                 $('#wheelwinnersopen-btn').show();
             }else if ($.cookie('afContent') == 'trophy'){
                 $('#afclose-btn').hide();
                 $('#afopen-btn').show();
                 $('#raceclose-btn').hide();
                 $('#raceopen-btn').show();
                 $('#trophyclose-btn').show();
                 $('#trophyopen-btn').hide();
                 $('#wheelwinnersclose-btn').hide();
                 $('#wheelwinnersopen-btn').show();
             }else if ($.cookie('afContent') == 'woj'){
                 $('#afclose-btn').hide();
                 $('#afopen-btn').show();
                 $('#raceclose-btn').hide();
                 $('#raceopen-btn').show();
                 $('#trophyclose-btn').hide();
                 $('#trophyopen-btn').show();
                 $('#wheelwinnersclose-btn').show();
                 $('#wheelwinnersopen-btn').hide();
             }
         }else{
             $('#afclose-btn').hide();
             $('#afopen-btn').show();
             $('#raceclose-btn').hide();
             $('#raceopen-btn').show();
             $('#trophyclose-btn').hide();
             $('#trophyopen-btn').show();
             $('#wheelwinnersclose-btn').hide();
             $('#wheelwinnersopen-btn').show();
         }
     }

     function getEventsHeight(){
         return $('#play-box').height() + 20;
     }

     function getByAction(action){
         sCookie('afContent', action);
         switch(action) {
             case 'af':
                 if (trophyWsLoaded) { cleanUpRaceTab(); }
                 if (raceWsLoaded) { cleanUpRaceTab(); }
                 getEvents(getEventsHeight()); break;
             case 'race' :
                 if (trophyWsLoaded) { cleanUpRaceTab(); }
                 getRace('play'); break;
             case 'trophy' :
                 if (raceWsLoaded) { cleanUpRaceTab(); }
                 getTrophies('trophy', '<?= $this->game['ext_game_name'] ?>');
                 break;
             case 'woj' :
                 getWheelJackpotWinners();
                 break;

         }
     }

     function toggleActivityFeed(action){
         if($.cookie('afStatus') == 'closed'){
             openActivityFeed();
             getByAction(action);
         }else if(typeof action != 'undefined'){
             getByAction(action);
         }else{
            sCookie('afStatus', 'closed');
            clearInterval(afIntv);
            $("#activity-feed").width(0);

            // Validating websockets before close them
            if (trophyWsLoaded && trophyWsHandle) {
                trophyWsHandle.close();
                trophyWsLoaded = false;
            }
            if (raceWsLoaded && raceWsHandle) {
                raceWsHandle.close();
                raceWsLoaded = false;
            }

            resizePlayBox();
         }

         handleFeedButtons();
     }

     function rebuildCurPlaying(target, $targetDiv){
         var oldgame_id = typeof target != 'undefined' ? $targetDiv.attr('data-game-id') : false;
         if (!empty(oldgame_id)) {
             $('#play-box').trigger('game-changed', [$targetDiv.attr('data-game-network'), oldgame_id] );
         }
         var tmp = {};
         $('#play-box .play-box-content').each(function(i, v) {
             var gid = $(this).attr('data-game-id');
             if (gid && (gid !== oldgame_id || oldgame_id === false)) {
                 tmp[gid] = currentlyPlaying[gid];
             }
         });
         currentlyPlaying = tmp;
     }

     function removeOldGame(target, $targetDiv) {
         rebuildCurPlaying(target, $targetDiv);
     }

     function closeGameSessionAndRedirectHome() {
         var gameRefs = currentGames.map(function (game) {
             return game.ext_game_name;
         });

         mgAjax({action: 'close-game-session', game_refs: gameRefs}, function() {
             if ((typeof extSessHandler !== 'undefined') && (typeof mpUserId === 'undefined') && (cur_country === 'ES')) {
                 extSessHandler.showGameSummary();
             }
             else {
                 gotoLang('/');
             }
         });
     }

     function chooseGame(li, target) {
         var game, game_id, launch_url, network, aspect_ratio;

         game_id = li.getAttribute("data-game-id");
         gamesFooterDown();
         currentGameIndex = gameTarget;

         $.get('/phive/modules/Micro/json/game_search.php', {action: "get-game", game_id: game_id, lang: cur_lang, device: 'flash'}, function(res){
             if (isNaN(target) && !isNaN(target[3]))
                 target = target[3];

             target = gameTarget;
             var $targetDiv = $('#box' + target);

             game = res.game;
             launch_url = res.url;

             removeOldGame(target, $targetDiv);
             currentlyPlaying[game_id] = game;

             gameSelectedTarget = {
                'game_id':game.game_id,
                'game_name':game.game_name,
                'ext_game_name':game.ext_game_name,
                'network':game.network
             };
             currentGames[target] = gameSelectedTarget;

             network = game.network;
             aspect_ratio = game.width / game.height;

             var h = (100 / parseFloat(aspect_ratio) * 1.3333333333).toFixed(3);

             var newGameFrame = $('<iframe></iframe>');
             newGameFrame.attr({
                 src: launch_url,
                 frameBorder: '0',
                 border: '0',
                 width: '100%',
                 height: h + '%',
                 scrolling: 'no',
                 hspace: '0'
             }).css({
                 width: '100%',
                 height: h + '%'
             });

             var removeGame = $targetDiv.attr('data-game-id');
             var removeNetwork = $targetDiv.attr('data-game-network');
             $targetDiv.attr('data-game-id', game_id);
             $targetDiv.attr('data-game-network', network);

             handleMultiPlay(network);

             if (typeof extSessHandler !== 'undefined') {
                extSessHandler.newSession({network: network, game_id: game_id});
                $("#play-box")
                    .off('continue-game-load')
                    .on('continue-game-load', function() {
                    loadGameInContainer(target, $targetDiv, newGameFrame, game_id, network);
                });
             } else {
                 loadGameInContainer(target, $targetDiv, newGameFrame, game_id, network);
             }
             
             // we create a new ws channel for games opened via split mode
             if(target > 0){
                 lga(game.ext_game_name);
             }
         }, 'json');
     }

     function loadGameInContainer(target, $targetDiv, newGameFrame) {
         $targetDiv.html("");
         $targetDiv.append(newGameFrame);

         var par = $($(this).parent()).parent();
         $('#change-game-' + target).show();
         setSubCss(target);
         recreateFilterArrays();
     }

     function openGameChooser(target) {
         var off              = $('#play-box').offset().top;
         var bottom           = $(window).height() - off;
         var net              = $('#box' + target).attr('data-game-network');
         unlockNetwork        = net;
         gamesFooterMode      = 'multi';
         multiSelectorClicked = true;
         gameTarget           = target;
         footerList('getPopular', '.games-footer-popular');
         $("#games-footer-down").show();
         setupCasinoSearch(
             function(i, o){
                 onFooterResult(this);
             },
             function(){
                 $("#search-result" + target).html( "default stuff here" );
             },
             "#search-result" + target,
             "#search_str" + target);
     }

     function updateGridButtons(size) {
         $('.chgrd[data-target=\''+$('.top-bar-pcontrols').attr('data-current-grid')+'\']').toggle();
         $('.top-bar-pcontrols').attr('data-current-grid', size);
         $('.chgrd[data-target=\''+size+'\']').toggle();
     }

     function addNewBoxes(index) {
         var el = document.createElement('div');
         var frame = document.createElement('img');

         // Here we create the new Choose Game window and add a click listener to the image

         var src = $('#gamePicker').find('img').attr("src");
         frame.setAttribute("src", src);
         frame.setAttribute("style", "width: 100%;height: 100%;cursor: pointer;");
         el.className = 'play-box-content';
         el.id = 'box'+index;
         el.appendChild(frame);
         frame.onclick = function () {
             var currentTarget = index;
             return function(e) {
                 e.stopPropagation(); gameTarget = currentTarget; openGameChooser(currentTarget);
             }
         }();
         $('#play-box').find('.play-box-outer').append(el);
     }

     function updateGridStyle(idx, size, el) {
         var smap = {1: "full-width full-height", 2: "half-width full-height", 4: "half-width half-height"};
         el.removeAttr("style");
         var map = {0: 'top-left', 1: 'top-right', 2: 'bottom-left', 3: 'bottom-right'};
         el.removeClass("full-width half-width full-height half-height").addClass(smap[size]).addClass(map[idx]);
     }

     function removeExtraGameBoxes(size) {
         // Remove extra games, when changing to smaller grid
         $gameContainer = $('#play-box');
         var i = size, l, box = $gameContainer.find('.play-box-outer .play-box-content');
         l = box.length;
         while (i < l) {
             var el = $gameContainer.find('.play-box-outer .play-box-content').last();
             if (typeof extSessHandler !== "undefined") {
                 $gameContainer.trigger('game-closed', [el.attr('data-game-network'), el.attr('data-game-id')]);
             }
             el.remove();
             i++;
         }
         for(var x = 0; x < 4; x++)
             $('#change-game-' + x).hide();
     }

     function recreateFilterArrays(){
         filterNetwork = [];
         $('#play-box').find('.play-box-outer .play-box-content[data-game-id]').each(function(i, el){
             var network = $(this).attr('data-game-network');
             if (network) {
                 handleMultiPlay(network);
             }
         });
     }

     function createOpenerHandler(idx) {
         $("#change-game-" + idx).on('click', function() {
             var currentTarget = idx;
             gameTarget = currentTarget;
             return function(e) {
                 e.stopPropagation(); gameTarget = currentTarget; openGameChooser(currentTarget);
             }
         }());
     }

     function setSubCss(id){
         var box     = $('#box' + id);
         var game_id = box.attr('data-game-id');
         var g       = currentlyPlaying[game_id];
         var boxH    = box.height();
         var el      = $('#box' + id).find('iframe');
         var frameH  = el.outerHeight();
         var topV    = (boxH - frameH) / 2;
         if (el.length > 0) {
             dims = calcDims(g.width / g.height, box.width(), box.height(), isDynamic(g), g.width, g.height, 0, 0);
             el.css({"position": "relative"}).width(dims[0]).height(dims[1]).center(true);
         }
     }

     function useGridOfSize(size) {

         // a global variable used when minimizing the playbox
         initialWidth = undefined;
         var l = $('#play-box').find('.play-box-outer .play-box-content').length,
             i;

         gamesFooterMode = size > 1 ? 'multi' : 'single';
         gameTarget = size === 1 ? 0 : size - 1;

         updateGridButtons(size);

         for (; size > l; l++) { // add the new game divs if grid size bigger than previously
             addNewBoxes(l);
         }

         $('#play-box').find('.play-box-outer .play-box-content').each(function(idx) {
             updateGridStyle(idx, size, $(this));
         });

         removeExtraGameBoxes(size);
         recreateFilterArrays();
         rebuildCurPlaying();

         for (i = 0; i < 4; i++)
             createOpenerHandler(i);

         resizePlayBox(false, 'grid');
     }

     function resizePlayBox(resizeType, actionType = ''){

         var mode = $('.top-bar-pcontrols').attr('data-current-grid');
         var dims;
         var w = undefined;
         var h = 1;

         if(mode == '2')
             w = 2.66666;
         else if(mode == '4')
             w = 1.33333;
         else
             h = undefined;

         dims = (typeof resizeType == 'boolean' && actionType != 'grid')? getresizeDims(w, h, resizeType) : getPlayDims(w, h);

         if(typeof(gamesFooterStatus) != 'undefined' && gamesFooterStatus == 'up'){
             $(".games-footer").css({"bottom": footerMovement.down.fdist + 'px'});
             gamesFooterStatus = 'down';
             $("#games-footer-down").hide();
             $(".games-footer").find('li').removeClass('gfooter-selected');
             clearSlider();
         }

         var fullWidth = parseInt(dims.width);

         if($.cookie('afStatus') == 'open')
             fullWidth += fullAfWidth;

         if($.cookie('tStatus') == 'open')
             fullWidth += fullTwidth;

         $.multibox('resize', 'play-box', dims.width, dims.height);
         var offsX = $.cookie('afStatus') == 'open' ? (-25 - afWidth) : -25;
         var pbHeight = $('#play-box').height();

         if($.cookie('tStatus') == 'open'){
             offsX += tWidth;
             $("#leaderboard").height(pbHeight + 20);
             $("#leaderboard-tbl").height(getLeaderBoardHeight());
         }

         if($.cookie('afStatus') == 'open'){
             $("#activity-feed").height(pbHeight + 20);
             $("#race-nano").height(pbHeight  - 50);
             $("#af-nano").height(pbHeight - 0);
         }

         $.multibox('offset', 'play-box', offsX);
         $('#play-box .play-box-content').each(function(id,o) { setSubCss(id); });
         positionPlayControls();
         if(typeof(updateLeaderBoardHeight) != 'undefined') {
            updateLeaderBoardHeight();
         }
     }

     function positionPlayControls(){
         var tmp = $("#play-box").offset();
         var pboxWidth = $("#play-box").width();
         var pboxHeight = $("#play-box").height();
         $("#change-game-0").css({"display": "none", "left": tmp.left-2+'px', "top": tmp.top-30+'px', width: '100px;'});
         $("#change-game-1").css({"display": "none", "left": tmp.left+pboxWidth-12+'px', "top": tmp.top-30+'px', width: '100px;'});
         $("#change-game-2").css({"display": "none", "left": tmp.left-2+'px', "top": tmp.top+pboxHeight+15+'px', width: '100px;'});
         $("#change-game-3").css({"display": "none", "left": tmp.left+pboxWidth-12+'px', "top": tmp.top +pboxHeight+15+'px', width: '100px;'});

         if (gamesFooterMode == 'single') {
             for (var j = 0; j < 4; j++)
                 $('#change-game-'+j).css({"display": "none"});
         }

         $('.play-box-content > img').each(function(i,val){
             $('#change-game-'+i).css({"display": "none"});
         });

         $("#play-box").find("div[id^='box']").each(function(k, box){
             if(gamesFooterMode == 'multi' && $(box).find('iframe').length != 0)
                 $('#change-game-'+k).css({"display": "block"});
         });

         if($.cookie('tStatus') == 'open'){
             $('#leaderboard').css({left: tmp.left - fullTwidth + 'px', top: tmp.top + 'px'});
         }

         var x = tmp.left + pboxWidth  + 18;
         var y = tmp.top + pboxHeight + 18;
         var conf = {"left": x, "top": tmp.top};
         $("#activity-feed").css({"left": conf.left+'px', "top": conf.top+'px', width: '0px;'});
         $("#play-controls").css({"left": conf.left + $("#activity-feed").width() + 'px', "top": conf.top+'px'});
         var jurWidth = $.cookie('afStatus') == 'open' ? pboxWidth + 20 + fullAfWidth : pboxWidth + 20;
         if($.cookie('tStatus') == 'open'){
             jurWidth += fullTwidth;
             tmp.left -= 250;
         }
         $("#gameplay-mess-bar").css({"left": tmp.left+'px', "top": y+'px', "width": (jurWidth - 2) +'px'});
     }

     function setFullScreenBtn(btn, image){
         btn.attr('src', '/diamondbet/images/<?= brandedCss() ?>game_page2/'+image+'.png');
     }

     function toggleFullScreen(){
         var btn = $("#fullscreen-btn");
         var icon = btn.find(".icon");
         var isFullScreen = getFullScreenStatus();
         if(!isFullScreen){
             setFullScreenBtn(btn, 'minimise-screen');
             icon.removeClass("icon-bounding-box-selection").addClass("icon-arrows-in");
         }
         else {
             setFullScreenBtn(btn, 'fullscreen');
             icon.removeClass("icon-arrows-in").addClass("icon-bounding-box-selection");
         }
         toggleFull(function(action){});
     }

     function setProgressBar(bonusesProgress) {

        let freespinBonus = bonusesProgress.freespin;
        let welcomeBonus = bonusesProgress['welcome-bonus'];

        if (welcomeBonus) {
            $("#welcome-reward-progress").html(welcomeBonus.progress);
            $("#welcome-reward-progress-bar").css({width: welcomeBonus.progress_width + 'px'});
        }

        if (freespinBonus) {
            $("#freespin-reward-progress").html(freespinBonus.progress);
            $("#freespin-reward-progress-bar").css({width: freespinBonus.progress_width + 'px'});
        }
     }

     function rewardsWs(cont){
         doWs('<?php echo phive('UserHandler')->wsUrl('rewardprogress') ?>', function(e) {
             var res = JSON.parse(e.data);

             if (res.freespin?.length === 0 && res['welcome-bonus']?.length === 0) {
                cont.animate({top: "-="+iProgressBarHeight, opacity: 0}, 1000, function(){
                     cont.html('');
                 });
             } else {
                if (cont.html() === '') {
                    showRewardProgress(false);
                }

                setProgressBar(res);
             }
         });
     }

     function showRewardProgress(execWs){
         cont = $("#gameplay-mess-bar");
         ajaxGetBoxHtml({func: 'playBottom'}, cur_lang, 'TrophyListBox', function(ret){
             if(ret != 'false'){
                 cont.html(ret);
                 cont.animate({top: "+="+iProgressBarHeight, opacity: 1}, 1000);
             }
             if(execWs)
                 rewardsWs(cont);
         });
     }

     function initiatePlayFrame(){
         if(empty($.cookie('afStatus')))
             sCookie('afStatus', empty($.cookie('tStatus')) ? 'open' : 'closed');
         if(empty($.cookie('afContent')))
             sCookie('afContent', 'af');
         var dims = getPlayDims();

         var gameUrl = '<?= $this->iframeUrl ?>';
         var game = <?= json_encode($this->game)?>;

         <?php if (phive('MicroGames')->isBlocked($this->game)) { ?>
            var attribute = 'blocked-country';
         <?php }
         else { ?>
            var attribute = '<?php $network = phive('MicroGames')->getNetworkModule($this->game); echo $network->settings_data['iframe_attribute'] ?? null; ?>';
        <?php } ?>

        DefaultMessageProcessor.setGameLoader(GameLoader); // adds the game loader to the game communicator

         $.multibox({
             url: '',
             id: "play-box",
             type: 'iframe',
             attribute : attribute,
             width: dims.width+'px',
             height: dims.height+'px',
             globalStyle: {overflow: 'hidden'},
             cls: 'play-box',
             hideOverlay: true,
             onComplete: function(){
                 $("#play-box").find(".play-box-outer").append($("#activity-feed"));
                 $("#activity-feed").show();
                 $("#play-box").find(".play-box-outer").append($("#play-controls"));
                 $("#play-controls").show();
                 if($.cookie('tStatus') == 'open'){
                     $("#play-box").find(".play-box-outer").prepend($("#leaderboard"));
                     $("#leaderboard").show();
                     refreshLeaderBoard(curPlayTid);
                 }
                 $.multibox('offset', 'play-box', -25);
                 positionPlayControls();
                 //oldOffs = $("#play-box").offset();
                 GameCommunicator.init(
                    document.getElementById('mbox-iframe-play-box'), // iframe
                    gameUrl.split('?')[0],  // origin
                    DefaultMessageProcessor,
                    MessageProcessor,  // post message processor,
                    game
                );
             }
         });
         <?php $user = cu(); ?>
         <?php licOrFunc('startExternalGameSession', function () {?>
             GameLoader.init(DefaultMessageProcessor, document.getElementById('mbox-iframe-play-box'), gameUrl);
         <?php }, [$user, $this->game['network'], $this->iframeUrl, $this->game, $_GET['show_demo'] ?? false]); ?>

         <?php lic('doBalanceCheckInGamePlay', [$user], $user); ?>

         <?php lic('handleRgPopupInGamePage', [], $user); ?>

         var first = $("#play-box .play-box-outer .play-box-content").first();
         first.attr('id', 'box0');
         first.attr('data-game-network', '<?php echo $this->game['network'] ?>');
         first.attr('data-game-id', '<?php echo $this->game['game_id'] ?>');

         $(window).on('resize', function() {
                resizePlayBox();
                initialWidth = $('#play-box').width();
           });

         if($.cookie('afStatus') == 'open'){
             openActivityFeed();

             if($.cookie('afContent') == 'af')
                 getEvents(getEventsHeight());
             else if($.cookie('afContent') == 'trophy')
                 getTrophies('trophy', '<?= $this->game['ext_game_name'] ?>');
             else if($.cookie('afContent') == 'woj')
                getWheelJackpotWinners();
             else
                 getRace('play');
         }
         $("#fav-star").click(function(){
             ajaxGetBoxHtml({func: 'toggleFav', gid: '<?php echo $this->game['id'] ?>', uid: '<?php echo $_SESSION['mg_id'] ?>'}, cur_lang, <?php echo $this->getId() ?>, function(ret){
                 $("#fav-star span").attr("class", "icon " + ret);
             });
         });
         if(canFull()){
             $("#fullscreen-btn").click(function(){
                 toggleFullScreen();
             });
         }else
         $("#fullscreen-btn").hide();

         <?php if(isLogged()): ?>
         <?php
         // this will trigger the inhouse frb popup on start
         if(phive()->getSetting('inhousefrb') === true):
         ?>
         setInhouseFrbWs();
         <?php endif ?>
         <?php if(phive()->getSetting('lga_reality') === true): ?>
         lga();
         <?php endif ?>

         <?php endif ?>

          /**
         * Animate the progress bar shown under the game to show progression of bonusses etc. after xx seconds
         * ones the jurisdiction message has been animated away
         */
         setTimeout(function(){
             $("#gameplay-mess-bar").animate({top: "-="+iProgressBarHeight, opacity: 0}, 1000, function(){
                 var cont = $(this);
                 var trophy = <?php echo (phive()->moduleExists('Trophy') ? 'true;' : 'false;' ); ?>
                 var frb = <?php echo ((phive()->getSetting('inhousefrb') === true) ? 'true;' : 'false;' ); ?>;
                 if(hasWs() && (trophy === true || frb === true)){
                     if(trophy === true) { showRewardProgress(true);}
                     if(frb === true) { showInhouseFrbProgress();}
                 } else {
                     cont.remove()
                 }
             });
         }, 10000);
     }

     $(document).ready(function(){
         initiatePlayFrame();

         let prevIsFullScreen = getFullScreenStatus();

         setInterval(function(){
             let isFullScreen = getFullScreenStatus();

             if (prevIsFullScreen !== isFullScreen) {
                 if (!isFullScreen) {
                     setFullScreenBtn($('#fullscreen-btn'), 'fullscreen');
                 }
                 prevIsFullScreen = isFullScreen;
             }
         }, 500);
         setTimeout(function(){
             resizePlayBox();
         }, 2000);

         <?php if(in_array(cuCountry('', false), $this->mg->getSetting('vpn-gps')[$this->game['network']])): ?>
             mboxMsg('<?php echo addslashes(t('vpn.block.msg.html')) ?>', true);
         <?php endif ?>

         <?php $this->printTournamentJs() ?>

         <?php if(lic('noDemo', [false, $this->game])): ?>
             showRegistrationBox(registration_step1_url);
         <?php endif ?>




     });
    </script>
    <?php if(!empty($this->tournament)): ?>
        <?php $this->tl->chatBoxJs() ?>
    <?php endif ?>
<?php }

function img($name){
    return $this->img_dir."$name.png";
}

function gamePlayProgressBar(){
?>
<span><?php et('frb.remaining-msg.html') ?> <strong></strong></span>
<?php
}

function drawTrophyFeedItem() {
?>
    <div class="trophy-container-tab" id="{{teid}}-info">
        <div>
            <img title="<?php et('trophytab.info') ?>" style="float: left;" id="{{alias}}-img" class="trophy-img" src="<?php echo getMediaServiceUrl(); ?>/file_uploads/events/{{alias}}_event.png">
        </div>
        <div style="float: left; width: 165px;" class="pad-stuff-five">
            <div class="text-medium-bold" style="overflow: hidden;">{{trophyname}}</div>
            {{trophydescription}}
            <div style="position: relative; top: 5px; width: 100%;" class="trophy-progressbar-bkg"></div>
            <div class="trophy-progressbar-bar gradient-trophy-bar" style="position: relative; top: 0px; width: {{progress_percent}}%;"></div>
            <div class="progress-absolute">{{progr}} / {{threshold}}</div>
        </div>
        <div style="clear:both;"></div>
    </div>
<?php
  }

  function setupHandleBarsTrophyFeed(){ ?>
    <?php loadJs("/phive/js/handlebars.js") ?>
    <script id="trophy-item-tpl" type="text/x-handlebars-template">
      <?php $this->drawTrophyFeedItem() ?>
    </script>
    <script>
     var trophyFeedItemTpl = Handlebars.compile($('#trophy-item-tpl').html());
    </script>
  <?php }

  function toggleFav(){
    $isFavorited = $this->mg->toggleFavorite((int)$_REQUEST['uid'], (int)$_REQUEST['gid']) == 'inserted';
    echo $isFavorited ? 'icon-filled-star' : 'icon-bold-games-screen-b';
  }

  function favStarClass(){
    return $this->mg->isFavorite($_SESSION['mg_id'], $this->game) ? 'icon-filled-star' : 'icon-bold-games-screen-b';
  }

  function searchInput($id = "search_str", $alias = 'search.casino.games'){ ?>
    <div class="search-cont">
      <div>
        <?php dbInput($id, t2($alias, $this->mg->countWhere()), "text", "search-games") ?>
      </div>
    </div>
  <?php }

  function search(){ ?>
    <?php $this->searchInput() ?>
    <ul id="search-result"></ul>
  <?php
  }

  function afDo($func){
    if(!empty($this->af))
      $this->af->$func();
  }

  function showMp(){
    return hasMp() && isLogged() && !empty($this->tournament);
  }

  function printCSS(){
    loadCss("/diamondbet/css/" . brandedCss() . "top-play-bar.css");
    loadCss("/diamondbet/css/" . brandedCss() . "playbox4.css");
  }

    function printHTML()
    {
        require_once __DIR__ . '/../../../../../diamondbet/html/chat-support.php';
        if ($this->showMp()) {
            if (!$this->th->canPlay($this->tournament, $this->t_entry)) {
                jsRedirect(llink("/?tournament_finished=true&eid={$this->t_entry['id']}"));
            }

            //if(!$this->th->hasStarted($this->tournament))
            //  jsReloadBase();
        }
        loadCss("/diamondbet/fonts/icons.css");
        loadJs("/phive/js/jquery.cookie.js");
        loadJs("/phive/js/multibox.js");
        loadJs("/phive/js/fullscreen.js");

        $this->js();
        $this->afDo('printJS');
        $this->searchJs();
        $this->setupHandleBarsTrophyFeed();
        $url = "backgrounds/" . (phive('MicroGames')->getSetting('use_static_game_background', false) ? "GameBackground_BG.jpg" : $this->game['bkg_pic']);
        ?>
        <?php require_once phive()->getSetting('site_loc') . 'diamondbet/html/top-play-bar.php' ?>
        <?php if (!phive("Pager")->edit_boxes && !phive("Pager")->edit_strings): ?>
        <div class="play-bkg<?php echo(($this->game['stretch_bkg'] != 0) ? ' stretch' : ''); ?>" style="background-image: url(<?php fupUri($url) ?>);"></div>
        <?php if (phive('UserHandler')->getSetting('has_events') === true): ?>
            <div id="activity-feed">
                <?php $this->afDo('printGamePage') ?>
            </div>
        <?php endif ?>
        <div id="play-controls" style="position: fixed;">
            <div id="close-btn" class="play-controls-icon" onclick="closeGameSessionAndRedirectHome();" title="<?php et('close') ?>"><span class="icon icon-bold-games-screen-a"></span></div>
            <?php if (isLogged()): ?>
                <div id="fav-star" class="play-controls-icon" title="<?php et('add.to.favorites') ?>"><span class="icon <?php echo $this->favStarClass(); ?>"></span></div>
            <?php endif ?>
            <div id="fullscreen-btn" class="play-controls-icon" title="<?php et('make.fullscreen') ?>"><span class="icon icon-bounding-box-selection"></span></div>
            <div id="bigger-btn" class="play-controls-icon" onclick="resizePlayBox(false);" title="<?php et('make.bigger') ?>"><span class="icon icon-bold-games-screen-h1"></span></div>
            <div id="smaller-btn" class="play-controls-icon" onclick="resizePlayBox(true);" title="<?php et('make.smaller') ?>"><span class="icon icon-bold-games-screen-e2"></span></div>
            <?php if (phive('UserHandler')->getSetting('has_events') === true): ?>
                <div id="afopen-btn" class="play-controls-icon" onclick="toggleActivityFeed('af');" title="<?php et('open.activity.feed') ?>"><span class="icon icon-bold-games-screen-f2"></span></div>
                <div id="afclose-btn" class="play-controls-icon" style="display: none;" onclick="toggleActivityFeed();" title="<?php et('close.activity.feed') ?>"><span class="icon icon-wing-left"></span></div>
            <?php endif ?>
            <?php if (phive('Race')->getSetting('hide_race_tab') !== true && !empty($this->race)): ?>
                <div id="raceopen-btn" class="play-controls-icon" onclick="toggleActivityFeed('race');" title="<?php et('open.race') ?>"><span class="icon icon-vs-race-flag"></span></div>
                <div id="raceclose-btn" class="play-controls-icon" style="display: none;" onclick="toggleActivityFeed();" title="<?php et('close.race') ?>"><span class="icon icon-wing-left"></span></div>
            <?php endif ?>
            <?php if (phive('Trophy')->getSetting('trophy_tab_enabled') == true && isLogged() && in_array($this->game['tag'], ['slots', 'videoslots', 'videoslots_jackpot', 'casino-playtech'])): ?>
                <div id="trophyopen-btn" class="play-controls-icon" onclick="toggleActivityFeed('trophy');" title="<?php et('open.trophy') ?>"><span class="icon icon-vs-trophy"></span></div>
                <div id="trophyclose-btn" class="play-controls-icon" style="display: none;" onclick="toggleActivityFeed();" title="<?php et('close.trophy') ?>"><span class="icon icon-wing-left"></span></div>
            <?php endif ?>
            <div class="play-controls-icon" title="<?php et('chat') ?>" onclick="<?php echo phive('Localizer')->getChatUrl() ?>"><span class="icon icon-bold-games-screen-h2"></span></div>
        </div>
        <div id="change-game-0" class="change-game-tb" style="position: fixed;display:none;">
          <img id="matrix-btn-0" src="<?php echo $this->img('chg') ?>"/>
        </div>
        <div id="change-game-1" class="change-game-tb" style="position: fixed;">
          <img id="matrix-btn-1" src="<?php echo $this->img('chg') ?>"/>
        </div>
        <div id="change-game-2" class="change-game-tb" style="position: fixed;">
          <img id="matrix-btn-2" src="<?php echo $this->img('chg') ?>"/>
        </div>
        <div id="change-game-3" class="change-game-tb" style="position: fixed;">
          <img id="matrix-btn-3" src="<?php echo $this->img('chg') ?>"/>
        </div>
        <div id="gameplay-mess-bar"><span><?php et(lic('getGameJurisdictionString', [$this->game['network']])) ?></span></div>
        <?php if (!empty($this->tournament)): ?>
            <div id="leaderboard">
                <div id="txtScore" style="display:none"><?php et('score') ?></div>
                <div id="txtSpins" style="display:none"><?php et('spins.left') ?></div>
                <div class="timer">
                    <div>
                        <div class="tdown-bkg">&nbsp;</div>
                        <div class="tdown-bkg">&nbsp;</div>
                        <div class="tdown-semi">:</div>
                        <div class="tdown-bkg">&nbsp;</div>
                        <div class="tdown-bkg">&nbsp;</div>
                        <div class="tdown-semi">:</div>
                        <div class="tdown-bkg">&nbsp;</div>
                        <div class="tdown-bkg">&nbsp;</div>
                        <div class="tdown-num" id="tdown-hours"></div>
                        <div class="tdown-num" id="tdown-min"></div>
                        <div class="tdown-num" id="tdown-sec"></div>
                        <div class="clearer"></div>
                    </div>
                    <div class="tdown-headline"><?php et('mp.hours') ?></div>
                    <div
                            class="tdown-headline" style="text-indent: 6px;"><?php et('mp.min') ?></div>
                    <div
                            class="tdown-headline" style="text-indent: 14px;"><?php et('mp.sec') ?>
                    </div>
                </div>
                <div id="mp-topinfo-holder" class="mp-topinfo-holder"></div>
                <div id="leaderboard-tbl">
                    <div id="leaderboard-content"></div>
                </div>
                <button class="mp-btn-view-battle" onclick="toLobby('<?php echo llink('/mp-lobby/'); ?>', undefined, '<?php echo $this->tournament['id']; ?>')"> <?php echo t('mp.view.tournament'); ?> </button>
                <?php $this->tl->chatBox('display: block;') ?>
            </div>
        <?php endif ?>
        <div id="gamePicker">
            <?php img("click.here.play", 1264, 950); ?>
        </div>
    <?php endif ?>

        <?php
        if (hasMp()) {
            mpChooseBox($this->t_entries);
        }
        if (function_exists('depositTopBar')) {
            depositTopBar();
        }

        lic('printRealityCheck', [empty($_REQUEST['eid']) ? $this->game : null]);
        lic('loadGeoComplyJs', ['global']);
        lic('setGameSessionCloseListener', [uid()]);
  }

  function printExtra(){?>
    <p>
      Game tags that will prepend .cgames, ex: new,hot:
      <input type="text" name="tags" value="<?php echo $this->tags ?>"/>
    </p>
  <?php }

}

