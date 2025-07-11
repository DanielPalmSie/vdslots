$(document).ready(function() {
  /**
   * Initial setup
   */

  // Set all global variables
  var $vsGameContainer = $('#vs-game-container');
  var iframeId = 'vs-game-container__iframe';
  var $iframe = $('#' + iframeId);
  var $vsStickyBar = $('#vs-sticky-bar');
  var $vsStickyBarVertical = $('#vs-sticky-bar');
  var $vsGameModeOverlap = $('#vs-game-mode-overlap');

  var fullScreenModeTouchOverlayId = 'fullScreenModeTouchOverlay';

  var $swipeTouchOverlay = null;  
  var $swipeOverlayAnimatedSymbol = null;

  var isAndroid = /(android)/i.test(navigator.userAgent);
  var isIos = /^(iPhone|iPad|iPod)/.test(navigator.platform);
  var isIphone = /^(iPhone)/.test(navigator.platform);
  var isIpad = /^(iPad)/.test(navigator.platform);

  var SUPPORT_FULLSCREEN = true;
  var SUPPORT_FULLSCREEN_IOS = true;

  // Set game url for the game iframe
  $iframe.attr('src', gameUrl);


  /**
   * Fullscreen related stuff
   */

  // For devices with fullscreen API support
  // we switch to fullscreen using the screenfull library
  var switchToFullscreen = function (event) {
    if (!screenfull.enabled)
      return;

    screenfull
      .request()
      .then(
        // success
        function () {
          console.log('Fullscreen mode established');
        },

        //failure
        function (error) {
          console.log('Can not set the fullscreen mode:', error);
        }
      )
      .finally(
        function () {
          removeFullscreenButton();
        }
      )
    ;
  };

  var removeFullscreenButton = function () {
    $('#' + fullScreenModeTouchOverlayId).remove();
  };


  // For iOS devices
  // we need to detect if the screen is in minimal UI (bars hidden)
  // and then set the interface accordingly
  var setIosFullscreen = function () {
    if (!(isIos && SUPPORT_FULLSCREEN_IOS))
      return;

    var windowInnerHeight = window.innerHeight;
    var iosInnerH = iosInnerHeight();

    if (
      isIos
        && 
      SUPPORT_FULLSCREEN_IOS 
        && 
      ( 
        (windowInnerHeight === iosInnerH)
        ||
        // iPhone 5s, 5se
        ((iosInnerH === 568) && (windowInnerHeight === 529))  
        ||
        // iPhone 6, 6s, 7, 8
        ((iosInnerH === 667) && (windowInnerHeight === 628))  
        ||
        // iPhone 6 Plus, 6s Plus, 7 Plus, 8 Plus
        ((iosInnerH === 736) && (windowInnerHeight === 696))
        ||
        // iPhone X
        ((iosInnerH === 736) && (windowInnerHeight === 696))
        ||
        // iPhone XS
        ((iosInnerH === 724) && (windowInnerHeight === 748))
        ||
        // iPhone XS
        ((iosInnerH === 808) && (windowInnerHeight === 832))        
        ||
        // iPhone X, XS
        (windowInnerHeight > iosInnerH)
      )             
    ) {
      $('body').addClass('fullscreen');
      
      if ($swipeOverlayAnimatedSymbol != null) {
        $swipeOverlayAnimatedSymbol.remove();
        $swipeOverlayAnimatedSymbol = null;
      }
    }  else {
      $('body').removeClass('fullscreen');
    }

    var vsGameContainerHeight = (iosInnerH > windowInnerHeight) ? iosInnerH : windowInnerHeight;
    $vsGameContainer.css({'height': vsGameContainerHeight + 'px'});
    
    // needed for proper viewport placement
    setTimeout(
      function () {
        window.scroll(0,0);
      }, 400);
    };


  // Constructs an overlay which triggers the fullscreen mode
  var showFullscreenButton = function (supportsFullscreenMode) {

    // for Android we switch the full screen on using the click/tap anywhere  
    if (!isIos && isAndroid && supportsFullscreenMode && !$('#' + fullScreenModeTouchOverlayId).length) {
      var $fullScreenModeTouchOverlay = $('<div id="' + fullScreenModeTouchOverlayId + '"></div>')
        .css({'position': 'fixed', 'width': '100%', 'height': '100%', 'left': 0, 'top': 0, 'pointer-events': 'all'})
        .one('click', switchToFullscreen)
      ;
      $('body').append($fullScreenModeTouchOverlay);
    }

    // for iPhone (not iPad) we create overlay for resizing using the swipe gesture  
    if (isIphone && SUPPORT_FULLSCREEN_IOS) {
      console.log('Set iOS swipe gesture', 'Window Inner Height', window.innerHeight, 'IOS inner height', iosInnerHeight(), 'Window Inner width', window.innerWidth);

      // prevent the viewport change by drag and drop gesture on the sticky bar
      $vsStickyBar.on('touchmove', function (e) {
        e.preventDefault();
      });
      
      if (window.innerHeight === iosInnerHeight()) 
        return;
            
      // we need to renew this button every time, otherwise it is shifted up after first succesfull gesture
      if ($swipeOverlayAnimatedSymbol == null) {
        $swipeOverlayAnimatedSymbol = $('<div id="scrollUpBackgroundSymbol"><i class="fas fa-hand-point-up"></i></div>')
          .addClass('scroll-up-background-symbol')
        ;
        $('body').append($swipeOverlayAnimatedSymbol);        
      }

      if ($swipeTouchOverlay != null)
        return;

      $swipeTouchOverlay = $('<div id="' + fullScreenModeTouchOverlayId + '"></div>')
        .addClass('scroll-up-background')
        //.css({'width': '100%'})
        .on('touchstart', function () {
          $vsGameContainer.css({'position': 'fixed'});
        })
        .on('touchmove', function (e) {
          setTimeout(function () {
            $(this).trigger('touchend');            
            window.scroll(0,0);
          }, 200);
        })
        .on('touchend', function (e) {
          console.log('touchend', iosInnerHeight());

          window.scroll(0,0);
          $vsGameContainer.css({'position': ''});

          setIosFullscreen();
        })
      ;
      
      $('body').append($swipeTouchOverlay);
    }
  };


  // Fullscreen button setup is trigered here
  var setFullscreenButton = function (iosInnerH, windowInnerHeight) {
    var supportsFullscreenMode = screenfull && screenfull.enabled;
    var isInFullScreenMode = screenfull.isFullscreen;
    var isFullScreen = 
      supportsFullscreenMode && isInFullScreenMode 
      || 
      !supportsFullscreenMode && (iosInnerH != null) && (iosInnerH === windowInnerHeight)
    ;
    
    if (isFullScreen) {
      return;
    }

    showFullscreenButton(supportsFullscreenMode);
  };


  /**
   * Iframe setup
   */      


  // fixGameFocus is needed to keep games "alive".
  // If some action removes focus from the game iframe content, we return it back
  var fixGameFocus = function () {
    var activeElement = document.activeElement;

    if (activeElement.id === iframeId)
      return;  

    $iframe[0].contentWindow.focus();
  };


  // Sets the game container, iframe, sticky bar dimensions and other essential parameters
  // Most important function!
  var setGameIframeHeight = function () {
    console.log('Set Game Iframe Height','Window Inner Height', window.innerHeight, 'IOS inner height', iosInnerHeight(), 'Window Inner width', window.innerWidth)


    var vsStickyBarVerticalSmallClass = 'vs-sticky-bar-vertical__small';
    var vsStickyBarVerticalLimit = 290;

    $vsStickyBarVertical.css('height', '');

    var iosInnerH = iosInnerHeight();
    var isPortrait = (window.innerHeight > window.innerWidth);

    var windowInnerHeight = window.innerHeight;
    var windowInnerWidth = window.innerWidth;

    var vsStickyBarHeight = $vsStickyBar.outerHeight(true);
    var vsStickyBarWidth = $vsStickyBar.outerWidth(true);

    if (isPortrait) {
      var iframeHeight = windowInnerHeight - vsStickyBarHeight; 
      var iframeWidth = window.innerWidth;
    } else {
      var iframeHeight = windowInnerHeight; 
      var iframeWidth = windowInnerWidth - vsStickyBarWidth;

      $vsStickyBarVertical.css('height', iframeHeight + 'px');

      // fix for small iPhones (or similar Android devices) in landscape after user taps the URL bar
      if (iframeHeight < vsStickyBarVerticalLimit) {
        $vsStickyBarVertical.addClass(vsStickyBarVerticalSmallClass);
      } else {
        $vsStickyBarVertical.removeClass(vsStickyBarVerticalSmallClass);
      }
    }

    $iframe.css('min-height', iframeHeight + 'px');
    $iframe.css('min-width', iframeWidth + 'px');
    
    if (SUPPORT_FULLSCREEN) {
      setFullscreenButton(iosInnerH, windowInnerHeight);
    }

    window.scroll(0,0);

    fixGameFocus();

    if (SUPPORT_FULLSCREEN_IOS)
      setIosFullscreen();
  };


  /**
   * App initialisation
   */       

  // Function for application init
  var initApp = function () {
    // we set first all dimensions and overlays/interactions 
    // before the game loads
    setGameIframeHeight();

    // we calibrate the setup after the game source loads
    // (this is only a start of game initialization)
    $iframe.on('load', function () {console.log('iframe loaded')
      setGameIframeHeight();     
    });  

    // Finally, we change everything if resize event happens
    // this covers also orientation change.
    // We have to use proven time interval for the setup
    // as the dimensions info is updated only after the after orientation change animation ended 
    $(window).on('resize', function () {console.log('resize')
      setTimeout(function () {
        setGameIframeHeight();
      }, 200);
    });
  };      

  // Let's init it all here ...
  initApp();
});