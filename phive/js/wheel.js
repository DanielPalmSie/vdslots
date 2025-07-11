var segments       = "";
var parsedSegments = "";
var theWheel       = "";
var wheelSpinning  = false;
var fs             = false;
var okToPlay       = true;
var wheelData      = {};

/*function iosIsMaximized(){

    // Store initial orientation
    var axis = Math.abs(window.orientation);
    // And hoist cached dimensions
    var dims = {w: 0, h: 0};

    var ruler = document.createElement('div');

    ruler.style.position = 'fixed';
    ruler.style.height = '100vh';
    ruler.style.width = 0;
    ruler.style.top = 0;

    document.documentElement.appendChild(ruler);

    // Set cache conscientious of device orientation
    dims.w = axis === 90 ? ruler.offsetHeight : window.innerWidth;
    dims.h = axis === 90 ? window.innerWidth : ruler.offsetHeight;

    // Clean up after ourselves
    document.documentElement.removeChild(ruler);
    ruler = null;

    var wheight = Math.abs(window.orientation) !== 90 ? dims.h : dims.w;

    return window.innerHeight == wheight;
}*/

var wheelAudioIsLoadedIntvl;
var $swipeOverlayAnimatedSymbol = null;
var doIos = isIos();
var doAndroid = isAndroid();

function wheelAudioIsLoaded(){

    var allLoaded = true;

    $('audio').each(function(i){

        // Audio is not yet ready to play
        if(this.readyState < 3){
            // allLoaded = false;
        }

    });

    if(allLoaded || doIos || doAndroid){
        clearInterval(wheelAudioIsLoadedIntvl);
        hideLoader();
        if(doIos || doAndroid){
            if ($swipeOverlayAnimatedSymbol == null) {
                $swipeOverlayAnimatedSymbol = $('<div id="scrollUpBackgroundSymbol" class="scroll-up-background-symbol"><i class="fas fa-hand-point-up"></i></div>');
                $('body').append($swipeOverlayAnimatedSymbol);

                /* if (!isIos13orHigher() && !doAndroid) {
                     var maximizedIntvl = setInterval(function () {
                         if (iosIsMaximized()) {
                             $('#scrollUpBackgroundSymbol').hide();
                             resizeWheel(true);
                         } else {
                             $('#scrollUpBackgroundSymbol').show();
                         }
                     }, 200);
                     return;
                 }*/

                if (isGestureOverlayShown) {
                    $('#scrollUpBackgroundSymbol').show();
                    if (isIos13orHigher()) {
                        // Changes required to enable the simple scroll gesture.
                        $('body').css({overflow: 'auto'});
                        $('body').scrollTop(0);
                        $('#scrollUpBackgroundSymbol').css({'z-index': "1001", height: ($('body').height() + 100) + 'px'});
                    }
                    return;
                }

                $('#scrollUpBackgroundSymbol').hide();
                // The `scroll off` need to be set again, because the one set at the `wheelResize` function it has been lost at this stage.
                if(isIos() && !zoomOn) {
                    iosScrollOff();
                }
            }
        }
    }
}

function displayWheel(wheelSeg, spinTime) {

    segments = wheelSeg;

    parsedSegments = JSON.parse(segments);

    // Create new wheel object specifying the parameters at creation time.
    theWheel = new Winwheel({
        'outerRadius' : 342, // Set outer radius so wheel fits inside the
        // background.
        'innerRadius' : 102, // Make wheel hollow so segments don't go all way to
        // center.
        'textFontSize' : 5, // 24 Set default font size for the segments.
        'textOrientation' : 'vertical', // Make text vertical so goes down from
        // the outside of wheel.
        'textAlignment' : 'outer', // Align text to outside of wheel.
        'numSegments' : parsedSegments.length, // Specify number of segments.
        'drawText' : true,
        'imageDirection' : 'N',
        'lineWidth' : 7, // width of the segment borders
        'segments' : parsedSegments,
        'drawMode' : 'segmentImage',

        'animation' : // Specify the animation to use.
            {
                'type' : 'spinToStop',
                'duration' : parseInt(spinTime), // Duration in seconds .9
                'spins' : 3, // Default number of complete spins.
                'easing' : 'Power4.easeOut',
                'callbackFinished' : 'alertPrize()'
            },

        'imageOverlay' : true, // this will show the line dividers on top of
        // images

        'segmentLineOnly' : false, // Added new Jonathan Aber
        'segmentImageTopLayer' : true, // Added new Jonathan Aber
        'segmentColorGradient' : true,
        'gradientRadiusMultipler': 0.75,

    }, false);
    // We pass in false as the second argument so we render when all images have finished
    // loading.
}



function getRandomInt(min, max) {
    min = Math.ceil(min);
    max = Math.floor(max);
    return Math.floor(Math.random() * (max - min)) + min;
}

// -------------------------------------------------------
// Click handler for spin button.
// -------------------------------------------------------
function startSpin() {

    segWinner = 0;

    $.ajax({
        type: "POST",
        url: "/phive/modules/DBUserHandler/xhr/trophy_actions_xhr.php",
        data: {'action':'woj-spin'},
        dataType: 'json',
        async : false

    }).done(function(data) {

        scrollOn();

        wheelData = data;

        switch(data){
            case 'noAward':
                showNoPlayMsg();
                wheelSpinning = true;
                return;
            default:
                segWinner = parseInt(data['sort_order']) + 1;
                wheelSpinning = false;
                break;
        }

    }).fail(function() {
        alert( "Error: Cannot Spin the wheel" );
        wheelSpinning = true;
    });

    // the slice the wheel will land on
    luckySegmentWinner = segWinner;

    // Ensure that spinning can't be clicked again while already running.
    if (wheelSpinning == false) {

        if(isSoundOn()){
            $('#intro')[0].volume = 0.3;
            $('#wheelSpin')[0].play();
        }

        // Disable the spin button so can't click again while wheel is spinning.
        $('#spin_button').addClass('not_clickable');
        $('#spin_button').removeClass('clickable');

        // Calculate the prize
        calculatePrize(luckySegmentWinner);

        // Get random angle inside specified segment of the wheel.
        var stopAt = theWheel.getRandomForSegment(luckySegmentWinner);

        // Important thing is to set the stopAngle of the animation before stating the spin.
        theWheel.animation.stopAngle = stopAt;

        // Begin the spin animation by calling startAnimation on the wheel object.
        theWheel.startAnimation();

        // Set to true so that spin button can't be clicked during the current animation.
        wheelSpinning = true;
    }
}

// -------------------------------------------------------------------------------------
// Function with formula to work out stopAngle before spinning animation.
// -------------------------------------------------------------------------------------
function calculatePrize(theWinner) {

    var wheelNumSegments = parsedSegments.length;

    // This formula always makes the wheel stop somewhere inside the rigged prize at least
    // 1 degree away from the start and end edges of the segment.
    var angleToStartFrom = 360 / wheelNumSegments * (luckySegmentWinner - 1)+ 1;
    // var.log(angleToStartFrom);
    var segmentDegree = 360 / wheelNumSegments;
    // var.log(segmentDegree);
    var stopAt = angleToStartFrom + Math.floor((Math.random() * segmentDegree));
    // Important thing is to set the stopAngle of the animation before stating the spin.
    theWheel.animation.stopAngle = stopAt;
}

// -------------------------------------------------------
// Function for reset button.
// -------------------------------------------------------
function resetWheel() {

    // Stop the animation, false as param so does not call callback function.
    theWheel.stopAnimation(false);

    // Re-set the wheel angle to 0 degrees.
    theWheel.rotationAngle = 0;

    // Reset to false to power buttons and spin can be clicked again.
    wheelSpinning = false;

    //enable the spin button
    $('#spin_button').addClass('clickable');
    $('#spin_button').removeClass('not_clickable');

}

// -----------------------------------------------------------------------
// Called when the spin animation has finished by the callback feature of
// the wheel because I specified callback in the parameters.
// We use "wheelData" cause it contains the result from "woj-spin" from the server, so for example amount is converted in the user currency.
// -----------------------------------------------------------------------
function alertPrize() {
    // Prize functionality must be added in here

    if(empty(wheelData.award)) {
        wheelData.award = {}; // to avoid possible JS errors with empty award scenario
    }

    $('.won-reward').html(wheelData.award.description);
    $('#won-reward-img').attr('src', wheelData.award.image);

    if(wheelData.award.type === 'jackpot') {
        switch(wheelData.award.description) {
            case "Mini Jackpot": showJackpotWinning('#miniJackpot'); break;
            case "Mega Jackpot": showJackpotWinning('#megaJackpot'); break;
            case "Major Jackpot": showJackpotWinning('#majorJackpot'); break;
        }
    } else if (empty(wheelData.award_id)) {
        showNoWinMsg();
    } else {
	    playSoundEffect('clapping');
        showCongratsModal();
    }

    resetWheel();
}

function resizeWheel(maximize){
    var winHeight;
    if (isIos() && !isIosChrome()) {
        winHeight = innerHeight;
    } else {
        winHeight = $(window).height();
    }

    var winWidth = $(window).width();
    var isTablet = null;

    // Check if the height or width is greater than the aspect ratio
    if((winHeight / winWidth) < compWheelRatio){
        scale = winHeight / compWheelHeight;
    } else {
        scale = winWidth / compWheelWidth;
    }

    if(maximize){
        scale *= 1.02;
    }

    if(isPortrait()) {
        // We limit scaling here to avoid the wheel getting too big on devices like iPads
        // which are more quadratic than phones.
        if (scale > 0.3) {
            isTablet = true;
            scale = 0.3;
        }

        // In case of very small phones we need to make the wheel proportionally smaller in order to fit
        // the JP list properly
        if (winHeight < 600) {
            scale *= winHeight / 600;
        }

        // We need it to be bigger than in landscape.
        scale *= 2;
    }

    if(isIos() && maximize != true){
        // Change required to enable a simple scroll gesture.
        $('#scrollUpBackgroundSymbol').css({height: ($('body').height() + 20) + 'px'});

        if (!zoomOn) {
            iosScrollOff();
        }

        if(isLandscape()){
            $('#scrollUpBackgroundSymbol').css({'z-index': "1001"});

            if(!isIosChrome() && isIos13orHigher()){
                // Chrome doesn't keep the tabs visible so no need to make the wheel smaller in landscape mode.
                // Safari in iOS12 and lower should also hide the tabs.
                scale *= 0.85;
            }

            if(!isIosChrome() && isIos13orHigher() && !zoomOn){
                // Get the bottom position of the element to show in the lower position on the page.
                var bottomPositionLowerElementToShow = document.getElementById('woj-bottom-bar').getBoundingClientRect().bottom;
                // Check if the device is in landscape and the browser's address bar need to be hide to show the full page.
                if (
                    // Check if we are not in minimal Safari UI, because the minimal bar can't be hidden by the swipe gesture. The `20px` used in the comparison is the height of the screen used from the minimal Safari's bar.
                    (($(window).height() - winHeight > 20)
                        &&
                        // If the element to show in the lower position of the page is not visible and there is still way to minimize the browser.
                        (winHeight < bottomPositionLowerElementToShow))
                    ||
                    /* Current way to deal with the gesture overlay at the loading of the page in landscape on devices where this is possible and excluding it on iPads.
                     * This strategy is base on the fact that on iOS13 for device where are shown the tabs open on the browser when on landscape, the sized calculated after loading are wrongly calculated and are always equal to the full screen while on the iPads are not.
                     */
                    (!isOnResize && (screen.width === winHeight))
                ) {
                    $('body').addClass("fix-bar-and-tabs-ios");
                    $('#scrollUpBackgroundSymbol').show();
                    // Change required to enable a simple scroll gesture.
                    $('body').css({overflow: 'auto'});
                    isGestureOverlayShown = true;
                    scrollOn();
                } else {
                    $('body').removeClass("fix-bar-and-tabs-ios");
                    $('#scrollUpBackgroundSymbol').hide();
                    $('body').css({overflow: 'hidden'});
                    isGestureOverlayShown = false;
                }
            }

        }

        if (!isLandscape()) {
            $('body').removeClass("fix-bar-and-tabs-ios");
            $('#scrollUpBackgroundSymbol').hide();
            $('body').css({overflow: 'hidden'});
            isGestureOverlayShown = false;
            $('#scrollUpBackgroundSymbol').css({'z-index': "11"});
        }
    }

    if(isIos() && maximize == true){
        $('body').scrollTop(0);
        // We need to prevent scrolling in all directions as iOS does NOT support overflow: hidden,
        // does not work in Chrome for iOS.
        iosScrollOff();
    }

    if (isAndroid()) {
        // Check if the device is in landscape and the browser's address bar need to be hide to show the full page.
        if (isLandscape() && ((screen.height - window.innerHeight >= chromeMaxBarHeight) && (winHeight <= $('.mobile-jp-list-container').outerHeight()) || !isOnResize)) {
            $('#scrollUpBackgroundSymbol').show();
            isGestureOverlayShown = true;
        } else {
            $('#scrollUpBackgroundSymbol').hide();
            isGestureOverlayShown = false;
        }
    }

    var css = {
        // Set the transform position at the top left corner in order to scale from there
        "transform-origin": '0% 0%',
        // Scale the container
        "transform": 'scale(' + scale + ')'
    };

    if(maximize){
        // Remove the possibility to further scroll up or down.
        $('body').height(winHeight);
    }

    if (isMobileDevice()) {
        css['width'] = '0px';
        css['height'] = '0px';

        if (
            isIos()
            &&
            isPortrait()
            &&
            !isIosChrome()
            &&
            !isTablet
            &&
            (previousMobileOrientation === "landscape")
            &&
            wasSafariMinimal
            &&
            !safariHasBeenResizedBeforeChangeOrientation
            &&
            !wasNotOnResize
        ) {
            // Logic to resolve the mismatch of the bar sides collect by javascript when the orientation changes from landscape to portrait in safari normal size. Only in this case "100vh" return the correct side of the page.
            $("#completeWheel").css({width: 100 / scale + 'vw', height: 100 / scale + 'vh'});
            $('body').css({height: '100vh'});
            if (isModalShown) {
                $('.fs-modal-content').css({height: '100vh'});
            }
        } else {
            // The two dimensions are given in two different unit `vw` and `px`, to reduce the rescaling time and avoid the problem with the height on iOS devices.
            $("#completeWheel").css({width: 100 / scale + 'vw', height: winHeight / scale + 'px'});
            $('body').css({height: '100%'});
            if (isModalShown) {
                // Check if it is an android device with browser in minimal view.
                if (isAndroid() && (screen.height - window.innerHeight < chromeMaxBarHeight)) {
                    $('.fs-modal-content').css({height: '100vh'});
                } else {
                    $('.fs-modal-content').css({height: '100%'});
                }

            }
        }

        // Position of the jackpots information area.
        if(isPortrait()){
            $(".mobile-jp-list-container").css({top: (($("#canvasContainer").height() + 40) * scale) + 'px'});
        } else {
            $(".mobile-jp-list-container").css({top: "0"});
        }
    }

    $("#wheel").css(css);

    // Store the info about the current resize.
    wasSafariMinimal = !($(window).height() - winHeight > 20);
    safariHasBeenResizedBeforeChangeOrientation = safariHasBeenResized;

}

$('body').on('click','#btn-homepage', function(){
    $('#congratModal').hide();
    $('#overlay').hide();
    stopSound();
});

$('body').on('click','.close', function(){
    $('#congratModal').hide();
    $('#overlay').hide();
    stopSound();
});

$('body').on('click','#btn-fullScreenYes', function(){
    fs = true;
    requestFullScreen(document.body);
    modalHide('fullScreen');
});

$('body').on('click','#btn-fullScreenNo', function(){
    modalHide('fullScreen');
});

$('body').on('click','#btn-soundNo', function(){
    modalHide('playSound');
    toggleSoundOff();
});

$('body').on('click','#btn-soundYes', function(){
    modalHide('playSound');
    toggleSoundOn(true);
});

function playBkgMusic(){
    initSound();
    $('#intro')[0].currentTime = 0;
    $('#intro')[0].play();
}

var soundStatus = null;

function initSound() {
    $('#jackpotMoney')[0].play();
    $('#jackpotMoney')[0].pause();
    $('#jackpotMoney')[0].currentTime = 0;
    $('#jackpotWin')[0].play();
    $('#jackpotWin')[0].pause();
    $('#jackpotWin')[0].currentTime = 0;
    $('#clapping')[0].play();
    $('#clapping')[0].pause();
    $('#clapping')[0].currentTime = 0;
}

function stopSound() {
    $('#jackpotWin')[0].pause();
    $('#jackpotWin')[0].currentTime = 0;
    $('#jackpotMoney')[0].pause();
    $('#jackpotMoney')[0].currentTime = 0;
    $('#clapping')[0].pause();
    $('#clapping')[0].currentTime = 0;
    $('#intro')[0].currentTime = 0;
    $('#intro')[0].pause();
    soundStatus = 'stop';
}

function isSoundOn(){
    return soundStatus != 'stop';
}

function playSoundEffect(selector){
    if(!isSoundOn()){
        return false;
    }

    if(isIos()){
        // In case we're on an iOS device we take the wheelSpin sound which has already been "approved" by a click
        // and change its source to the source of the sound we want to play and play it.
        $('#wheelSpin')[0].src = $('#' + selector).find('source').first().attr('src');
        $('#wheelSpin')[0].play();
    } else {
        // In case we're not using an iOS device we just play the sound normally.
        $('#' + selector)[0].play();
    }

    return true;
}


function showCongratsModal(){
    modalShow('congratModal');
    // To prevent some on click events etc from interfering with clicking elements in the modal.
    // This is an issue in Safari in iOS.
    $("#wheel").remove();
}

function showJackpotWinning(selector){
    playSoundEffect('jackpotWin');
    setTimeout(function() {
        playSoundEffect('jackpotMoney');
        $('#overlay').show();
        $(selector).show();
        setTimeout(function() {
            $(selector).hide();
            playSoundEffect('clapping');
            showCongratsModal();
        }, 5000);
    }, 1000);
}

function toggleSoundOff(){
    // Volume down means we want to turn sounds off.
    var toggle = $('#sound-toggle-btn');
    stopSound();
    toggle.removeClass('fa-volume-up');
    toggle.addClass('fa-volume-down');
}

function toggleSoundOn(playSound){
    // Volume up means we want to turn sounds on.
    var toggle = $('#sound-toggle-btn');
    if(playSound !== false){
        playBkgMusic();
    }
    toggle.removeClass('fa-volume-down');
    toggle.addClass('fa-volume-up');
    soundStatus = 'play';
}

function toggleSound(){
    var toggle = $('#sound-toggle-btn');

    if(toggle.hasClass('fa-volume-down')){
        toggleSoundOn(true);
    } else if(toggle.hasClass('fa-volume-up')){
        toggleSoundOff();
    }
}

function getMobileOrientation(){
    screenRatio = $(window).height() / $(window).width();
    return screenRatio > 1 ? 'portrait' : 'landscape';
}

function isPortrait(){
    return deviceOrientation == 'portrait';
}

function isLandscape(){
    return deviceOrientation == 'landscape';
}

function showNoPlayMsg(){
    modalShow('noPlay');
}

function showNoWinMsg(){
    modalShow('noWin');
}

// This method disable any kind of touch move on iOS. Therefore, disable swipe gesture and zoom gesture as well.
function iosScrollOff(){
    // Doesn't work in Chrome which needs a click or more active action.
    if(isIos() && !isIosChrome()){
        document.ontouchmove = function(event){
            event.preventDefault();
        };
    }
}

function scrollOn(){
    document.ontouchmove = function(event){};
}

function modalShow(id){
    $('#' + id).show();
    isModalShown = true;
}

function modalHide(id){
    $('#' + id).hide();
    isModalShown = false;
}

function toggleOverlayInfo(){
    var toggle = $('#bottom-info-btn');
    if(toggle.hasClass('woj-yellow-bkg')){
        // Hide the overlay
        $("#woj-info-overlay").hide();
        toggle.removeClass('woj-yellow-bkg');

    } else {
        // Show the overlay
        toggle.addClass('woj-yellow-bkg');

        if($("#woj-info-jp-info").find(".mobile-jp-list").length == 0){
            // If it has not been cloned and moved already
            $(".mobile-jp-list").clone().appendTo("#woj-info-jp-info");

            ajaxGetBoxHtml({func: 'printOverlayLegend'}, cur_lang, 'WheelBox', function(ret){
                $("#woj-info-jp-legend").html(ret);
                $("#woj-info-overlay").show();
            });

        } else {
            $("#woj-info-overlay").show();
        }
    }
}

function goToProfile(){
    goTo(llink('/account/'), undefined, !isMobileDevice());
}

function goToHome(){
    goTo(llink('/'), undefined, !isMobileDevice());
}


$(window).resize( function(){
    if (isOnResize !== true) {
        isOnResize = true;
        wasNotOnResize = true;
    } else {
        wasNotOnResize = false;
    }
    // Check if the page it was zoomed.
    if (document.documentElement.clientWidth / window.innerWidth !== 1) {
        zoomOn = true;
    } else {
        zoomOn = false;
    }
    if(isMobileDevice()) {
        previousMobileOrientation = deviceOrientation;
        deviceOrientation = getMobileOrientation();
        if (previousMobileOrientation === deviceOrientation) {
            safariHasBeenResized = !safariHasBeenResized;
        } else {
            safariHasBeenResized = false;
        }
    }
    resizeWheel();
})

var compWheelHeight;
var compWheelWidth;
var compWheelRatio;

// Variable to store information about the mobile orientation.
var deviceOrientation = null;
var previousMobileOrientation = null;

// Variable to store information about Chrome mobile.
var chromeMaxBarHeight; // Max height of the screen not covered from the page on an Android device with Chrome.

// Variables to store information about the previous resize on Safari mobile.
var wasSafariMinimal = null;
var safariHasBeenResized = false;
var safariHasBeenResizedBeforeChangeOrientation = false;
var zoomOn = false;
// Variable to store the information if it has been done a resize.
var isOnResize = false;
// Variable to verify if before the resize the page has just been loaded or it had already a resize.
var wasNotOnResize;


var isModalShown = null;
var isGestureOverlayShown = null;   // This variable is required only in case the page has been loaded and it didn't have any resize.

$(function(){

    if(!okToPlay){
        showNoPlayMsg();
        return false;
    }

    // get the height, width and aspect ratio of the wheel
    // container at the begining of the loading of page
    compWheelHeight = $('#wheel').height();
    compWheelWidth = $('#wheel').width();
    compWheelRatio = compWheelHeight / compWheelWidth;

    if(isMobileDevice()){
        // Get the max height of the screen not covered from the page on an Android device with Chrome. This strategy is based on the fact that currently the new page are always open in normal browser mode and not in minimum mode.
        chromeMaxBarHeight = screen.height - window.innerHeight;
        previousMobileOrientation = deviceOrientation;
        deviceOrientation = getMobileOrientation();
    }

    resizeWheel();

    $('#spin_button').hover(
        function(){
            $('#spinButton1').hide();
            $('#spinButton2').show();
        },
        function(){
            $('#spinButton2').hide();
            $('#spinButton1').show();
        }
    );

    // Check if mobile, if mobile get the size of the whole screen
    if( isAndroid() ) {
        modalShow('fullScreen');
    }

    if( isIos() ){
        modalShow('playSound');
    } else {
        modalShow('playSound');
        // Set the volume control to its on state without playing sound as that is already happening automatically
        toggleSoundOn(false);
    }
});
