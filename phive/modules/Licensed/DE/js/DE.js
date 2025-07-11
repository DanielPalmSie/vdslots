

licFuncs.showDepositLimitPrompt = function () {
    var extraOptions = isMobile() ? {width: '100%'} : {width: 800};
    var params = {
        module: 'Licensed',
        file: 'dep_lim_info_box',
        noRedirect: true
    };
    extBoxAjax('get_raw_html', 'dep-lim-info-box', params, extraOptions, top);
};

licFuncs.panicButtonInit = function (args) {
    var panicButtonSwiped = false, gestureThreshold = 10, gestureTresholdConfAttr = 'gesture_treshold';

    if (args.hasOwnProperty(gestureTresholdConfAttr)
        && typeof args[gestureTresholdConfAttr] === 'number'
        && args[gestureTresholdConfAttr] > 0
    ) {
        gestureThreshold = args[gestureTresholdConfAttr];
    }

    $(document).ready(function() {
        var elemIdAndClass = 'panic-button__switch',
            touchedElemClass = elemIdAndClass + '--touched',
            el = $(document.getElementById(elemIdAndClass));

        if (el.length > 0) {
            var panicButtonSwitchHammer = new Hammer(el[0], {threshold: gestureThreshold});
            // The swipe gesture doesn't work on desktop
            panicButtonSwitchHammer.on('panright', function () {
                if (!panicButtonSwiped) {
                    if (el.hasClass(touchedElemClass)) {
                        el.removeClass(touchedElemClass);
                    } else {
                        el.addClass(touchedElemClass);
                    }
                    panicButtonSwiped = !panicButtonSwiped;
                    lic('onPanicButtonClick', []);
                }
            });
        }
    });
};

licFuncs.onPanicButtonClick = function () {
    saveAccCommon('lock', {num_days: 1}, function() {
        goTo('/?signout=true');
    });
};

licFuncs.rgValidateResettable = function(arr, type, enforcedNonEmptyValidation){

    var ret = licFuncs.rgValidateResettableCommon(arr, enforcedNonEmptyValidation);

    if(ret !== true){
        return ret;
    }

    // We need to force deposits to be set.
    if(type == 'deposit'){
        ret = [];
        _.each(arr, function(el){
            if(empty(el.limit)){
                ret.push(el.time_span);
            }
        });

        return empty(ret) ? true : ret;
    }

    return ret;
};

licFuncs.appendExtraJurisdictionalInformations = function (postData) {
    if ($('#birth_country')) {
        postData['birth_country'] = $('#birth_country').val();
    }
    return postData;
}

licFuncs.realityCheckConfig = {
    width: siteType === 'normal' ? '600px' : '100vw',
    height: siteType === 'normal' ? '250px' : 'auto',
}

licFuncs.printGamePlayPaused = function () {
    licJson('gamePlayPaused', {country: 'DE'}, function(result) {
        if (result !== true) {
            return
        }
        if (typeof MessageProcessor !== 'undefined' && typeof MessageProcessor.pauseGame === 'function') {
            GameCommunicator.pauseGame();
        }
        close_selector = '.verification-reminder__later-button';
        extBoxAjax('get_html_popup', 'mbox-msg', {
            module: 'Licensed',
            file: 'enhanced_timer',
            boxtitle: 'reality-check.game-play-paused.title',
            closebtn: 'no'
        }, {
            width: siteType === 'normal' ? '600px' : '100vw',
            height: 'auto',
            containerClass: 'game-play-paused-popup'
        });
    });
}

licFuncs.acceptRealityCheck = function () {
    if ($("#dialogRcAcceptButton").data('ingame')) {
        licFuncs.printGamePlayPaused();
    }
};

licFuncs.preventMultipleGameSessionsHandler = function (data) {
    var redirectUrl = llink('/');
    if (data.message) {
        redirectUrl += '?show_msg=' + encodeURI(data.message);
    }
    return goTo(redirectUrl);
};

licFuncs.assistOnLimitsChange = function(type) {
    var daily_input = '#' + type + '-day';
    var weekly_input = '#' + type + '-week';
    var monthly_input = '#' + type + '-month';

    $(daily_input + ',' + weekly_input + ',' + monthly_input).on('keyup', function () {
        $(this).removeClass('required-input');
        $(this).removeClass('input-error');
        $(this).siblings('.deposit-limit-error').hide();
        $(this).siblings('.deposit-limit-enter-limit').hide();
        if (
            ($(this).val() !== '' && !isNumber($(this).val())) ||
            $(this).val() > $(this).data(type + '-limit')
        ) {
            $(this).addClass('input-error');
            $(this).siblings('.deposit-limit-error').show();
        } else {
            $(this).addClass('required-input');
        }
    });
};