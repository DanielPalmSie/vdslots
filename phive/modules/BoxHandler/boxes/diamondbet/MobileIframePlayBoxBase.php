<?php
require_once __DIR__.'/../../../../../diamondbet/boxes/DiamondBox.php';

// TODO can we remove this after extracting RC + GC logic? it's the Old mobile game page /Paolo

error_log('PAGE NOT USED');
header("HTTP/1.1 404 Not Found");
box404();
die();

/** 
* @class MobileIframePlayBoxBase
* Displays Mobile games inside an iframe  
*/
class MobileIframePlayBoxBase extends DiamondBox{

    private $user = null;            // DBUser
    private $topLogos = null;       // logos displayed at the top we get from lic
    private $gameUrl = null;        // game url we get from the query param
    private $gameRef = null;        // game reference we get from the query param
    private $networkName  = null;   // game network 
    private $game = null;
    private $rcPopup = false;
    private $rcPopupRedirect = false;
    private $licRcPopupData = false;

    function init()
    {
        $this->user = cu();
        $this->gameRef = $_GET['gref'] ?? '';
        $this->gameUrl = $_GET['url'] ?? '';
        $this->networkName = $this->getNetworkName();
        $this->topLogos = $this->showTopButtons() ? lic('topMobileLogos') : false;
        $this->rcPopup = $this->showRcPopup();
//        $this->rcPopupRedirect = phive($this->networkName)->rcPopupRedirect($this->networkName, $this->user);
        $this->licRcPopupData = $this->isRedirectionRealityCheck();
    }      
    
    function printHTML(){
        if (empty($this->gameUrl)) {
            header("HTTP/1.1 404 Not Found");
            box404();            
        }else{
            $this->printCss();
            $top_sticky_bar_parent = !empty(lic('topMobileLogos')) ? 'has-sticky-bar' : '';
            ?>
            <!-- Contains the game and licenses logos -->
            <div id="iframe-container" class="<? echo $top_sticky_bar_parent ?>">
                <!-- Full Screen button -->
            <?php if (false): ?>
                <div id="iframe-container__fullscreen__button" class="icon icon-vs-arrows-out"></div>
            <?php endif;?>
            <?php if(!empty(lic('topMobileLogos'))): ?>
                <div class="top-logos gradient-normal">
                    <a href="<?php echo llink('/mobile/') ?>" class="iframe-game-home-button">
                        <span class="vs-mobile-menu__item vs-mobile-menu__item-home icon icon-vs-home"></span>
                    </a>
                    <?= lic('rgLoginTime',['rg-top__item logged-in-time']); ?>
                    <?php echo lic('topMobileLogos') ?>
                </div>
                <br clear="all"/>
            <?php endif; ?>
                <!-- Game -->
                <iframe id="iframe-container__iframe"></iframe>
            </div>
            <?php
            $this->printJs();       
        }
    }

    function printJs()
    {
        $this->addNetworkJsLibraries($this->networkName);           
        $this->getGameCommunicator();
        $this->getGameLoader();
        $this->getDefaultMessageProcessor();        
        $this->getMessageProcessor($this->networkName);

        if ($this->rcPopup && $this->gameRef) {
            $game = phive('MicroGames')->getByGameRef($this->gameRef);
            $reality_check_interval = phive('Casino')->startAndGetRealityInterval($this->user->getId(), $game['ext_game_name']);
            if ($reality_check_interval) {
                loadJs("/phive/js/multibox.js");
                loadJs("/phive/js/reality_checks.js");
            }
        }
        ?>
        <script type="text/javascript"> 
        
        /**
        *    Toggles between fullscreen and normal mode on mobile
        */
        let FullScreenButton = (function(){
            let fullscreenButton = null;

            return {
                id: null,
                init(fullscreenButtonId) {
                    self.fullscreenButton = document.getElementById(fullscreenButtonId);
                    $(self.fullscreenButton).click(this.toggleFullScreen);
                },

                toggleFullScreen() {
                  var doc = window.document;
                  var docEl = doc.documentElement;

                  var requestFullScreen = docEl.requestFullscreen || docEl.mozRequestFullScreen || docEl.webkitRequestFullScreen || docEl.msRequestFullscreen;
                  var cancelFullScreen = doc.exitFullscreen || doc.mozCancelFullScreen || doc.webkitExitFullscreen || doc.msExitFullscreen;

                  if(!doc.fullscreenElement && !doc.mozFullScreenElement && !doc.webkitFullscreenElement && !doc.msFullscreenElement) {
                    requestFullScreen.call(docEl);
                    this.fullscreen = true;
                  }
                  else {
                    cancelFullScreen.call(doc);
                    this.fullscreen = false;
                  }

                  // update button class
                  const updateButtonClass = this.fullscreen ? 'icon icon-arrows-in' : 'icon icon-vs-arrows-out';
                  self.fullscreenButton.className = updateButtonClass;
                },

            };
        })();

        /**
        * Creates an iframe and resizes it to fill the available space
        * Then loads the game inside
        */
        let ResizableIframe = (function() {
            let iframe = null,      // the reference to the iframe
                container = null;   // view container

            return {
                id: null,     // id of the iframe 
                fullscreen: false,

                init(iframeId, container) {              // constructor & listeners
                    this.id = iframeId;
                    
                    this.create(container);

                    window.addEventListener('resize',(e) => {
                        this.setSize();  
                    });                    
                },

                create: function(container) {  // append iframe to container
                    self.iframe = document.getElementById(this.id);
                    self.container = document.getElementById(container)

                    if (self.iframe == null){
                        self.iframe = document.createElement('iframe');
                        self.iframe.id = this.id;                       
                        self.container.appendChild(self.iframe);
                    }
                    this.setSize();
                },

                setSize: function() {               // resize iframe 
                    var x = $(window).width(),
                        y = $(window).height() - this.getOccupiedHeightByOtherElements();

                    // set the element width and height
                    self.iframe.width = x; 
                    self.iframe.height = y;
                },                

                getOccupiedHeightByOtherElements: function() {
                    let occupiedSpace = 0; 

                    for(let i = 0, length1 = self.container.children.length; i < length1; i++){
                        if (self.container.children[i].id !== this.id && self.container.children[i].id !== 'iframe-container__fullscreen__button') 
                            occupiedSpace += $(self.container.children[i]).outerHeight(true);
                    }
                    return occupiedSpace;
                },
                
                getIframeElement() {
                    return self.iframe;
                }                
            };
        })();
       

        $(document).ready(function() {
            const gameUrl = '<?= $this->gameUrl ?>',
                  rcPopup = <?= $this->rcPopup ? 1 : 0 ?>,
                  showDialog = <?= $this->licRcPopupData ? 1 : 0; ?>,
                  topLogos = <?= !empty($this->topLogos) ? 1 : 0 ?>,
                  game = <?= json_encode($this->game) ?>;
            DefaultMessageProcessor.setGameLoader(GameLoader); // adds the game loader to the game communicator  
            
            let initGameCommunicator = function() { 
                // Callback for the iframe load event that initializes the game communicator
                GameCommunicator.init(
                    ResizableIframe.getIframeElement(), // iframe
                    gameUrl.split('?')[0],  // url
                    DefaultMessageProcessor,
                    MessageProcessor,  // post message processor,      
                    game              
                );
                ResizableIframe.getIframeElement().removeEventListener('load', initGameCommunicator);
            };

            // 1. Create the iframe, resize it and load the game accordingly  
            FullScreenButton.init('iframe-container__fullscreen__button');
            ResizableIframe.init("iframe-container__iframe", "iframe-container" );   
            GameLoader.init(DefaultMessageProcessor, ResizableIframe.getIframeElement(), gameUrl);
            
            // 2. Once the iframe loads start the game communicator 
            ResizableIframe.getIframeElement().addEventListener("load", initGameCommunicator);   

            // 3. Create reality checks
            if (rcPopup && typeof reality_checks_js !== 'undefined') {
                reality_checks_js.realitychecktimeout  = '<?=$reality_check_interval * 60 ?>';
                //reality_checks_js.redirectToRcPage = <?//= $this->rcPopupRedirect ? 1 : 0 ?>//;
                reality_checks_js.isRedirectionDialog = showDialog;
                reality_checks_js.network = '<?= $this->networkName ?>';
                reality_checks_js.gref    = game.ext_game_name;
                reality_checks_js.doAfter = function() {}; // for not reloading again the game
                reality_checks_js.rc_createDialog(showDialog ? "dialog" : undefined);
            }        

            // 4. Start the timer of the top logos if available 
            if (topLogos) {
                setupFullClock();
            }
        });
        </script>
        <?php
    }

    function printCss()
    {
        ?>
            <style type="text/css">
            #iframe-container__fullscreen__button {
                color: white;
                font-weight: bold;
                font-size: 18px;
                 z-index: 99;
                position: absolute;
                top: 2px;
                left: 9px;
            }
            #iframe-container__iframe {
                margin: 0;
                padding: 0;
                border: 0;
                font-size: 100%;
                font: inherit;
                vertical-align: baseline;
            }
            body, .container-holder, #wrapper, #wrapper-container{  
                width: 100%;
            }
            .logged-in-time {
                margin-left: 35px;
            }
            #wrapper-container, #wrapper-container.wrapper-SE {
                margin-top: 0px !important;
            }
            .container-holder {
              padding-top: 0 !important;
            }

            </style>               
        <?php
    }

    function printExtra()
    {
        # code...
    }


    private function getNetworkName()
    {
        $mgs = phive('MicroGames');
        return $mgs->getNetworkName($this->getGame($mgs));
    }    

    private function getGame($mgs)
    {
        $this->game = $this->game != null ? $this->game : $mgs->getByGameRef($this->gameRef, 1);
        return $this->game;
    }

    /**
    *   Conditions to show the Top buttons in mobile
    *   - A setting in the GP config exists for the current license, example:
        $this->setSetting('rg-buttons', [
          'SE' => true
        ]);
        - By default it will show them
    */
    private function showTopButtons()
    {
        $jurisdiction = licJur($this->user);
        $showTopButtons = phive($this->networkName)->getSetting('rg-buttons__mobile') ?? [];
        $globalSetting = phive('Licensed')->getLicSetting('sweden-rg-buttons') ?? false;
        return $globalSetting && (!isset($showTopButtons[$jurisdiction]) || $showTopButtons[$jurisdiction] === true); 
    }

    private function showRcPopup()
    {
        $module = phive('Casino')->getNetworkName($this->networkName);
        $rc_popup_in_game = phive($module)->getRcPopup('mobile', $this->user) === 'ingame';
        return phive()->getSetting('ukgc_lga_reality') === true && !$rc_popup_in_game && isLogged();
    }

    /**
    *  If we are coming from a reality check redirection we need to load the reality check with 
    *  a special parameter. We control this via memory show_lic_rc_popup_data
    */
    private function isRedirectionRealityCheck()
    {
        if (phMgetShard('show_lic_rc_popup_data',cuPlAttr('id')  )){
            phMdelShard('show_lic_rc_popup_data',cuPlAttr('id')  ); // delete the popup trigger
            return true;
        }
        return false;
    }

    
}


?>
