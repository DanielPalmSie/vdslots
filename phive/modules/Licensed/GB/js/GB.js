

licFuncs.onRgReady = function(options){
    const lockRadio = $("input[name='lock_duration']");
    const otherRadio = $('#uk-other');
    const lockInput = $('#lock-hours');

    lockRadio.click(function (){
        $("#uk-lock-txt-holder").hide();
        $("#uk-other").prop("checked",false);
    });

    otherRadio.click(function(){
        $("#uk-lock-txt-holder").show();
        $("input:radio[name='lock_duration']:checked").prop('checked', false);
    });

    lockInput.on('input', function() {
        options.hideError();
    });
};

licFuncs.isRgLockDataEntered = function() {
    const lockRadio = $("input[name='lock_duration']");
    const lockInput = $('#lock-hours');

    return lockRadio.is(':checked') || lockInput.val().length !== 0;
};

licFuncs.onRgLockClick = function(options){
    options.num_hours = $("input[name='lock_duration']:checked").val();
    return options;
};

licFuncs.disallowsCreditCards = function(){
    return true;
};
/**
 *  Check the length of mobile, for GB players no more than 14, prefix(2) + mobile number(12)
 * @param field
 * @returns {boolean}
 */
licFuncs.validateMobileLength = function (field) {
    return field.length <= 12;
}


licFuncs.AddTimerToDOM = function (hour, min, sec) {
    $('.game-play-session__timer .timer-min').html( (min < 10 ? '0' + min : min) + ":" );
    $('.game-play-session__timer .timer-hour').html((hour < 10 ? '0' + hour : hour ) + ":");
    $('.game-play-session__timer .timer-sec').html((sec < 10 ? '0' + sec : sec ));
};


licFuncs.Timer = function (callback) {
    var clock = {
        'timer-hour': 0,
        'timer-min': 0,
        'timer-sec': -1
    };
    updateClock(clock['timer-hour'], clock['timer-min'], clock['timer-sec'], callback)
};



licFuncs.showDepositLimitPrompt = function (){
    var extraOptions = isMobile() ? {width: '100%'} : {width: 'auto', callb: function(el) { $.multibox('offset', 'reg-dep-lim-prompt', 0, -90); }};
    var params = {
        module:   'Licensed',
        file:     'reg_dep_lim_prompt',
        boxtitle: 'want.deposit.limit',
        closebtn: 'no'
    };
    extBoxAjax('get_html_popup', 'reg-dep-lim-prompt', params, extraOptions);
};

licFuncs.showMessageOnCashier = function (){
    var extraOptions = { width: isMobile() ? '100%' : 'auto' };
    var params = {
        module:   'Licensed',
        file:     'generic_info_popup',
        boxid:    'account-message-box',
        boxtitle: 'ukgc.rg.popup.title',
        closebtn: 'no',
        boxType: 'tickbox',
        depositIframeTarget: 1,
        bodyString: 'understand.accpolicy.html',
        buttonString: 'i.understand.the.info.above',
        action: 'viewed-account-policy',
        checkboxErrorMsg: true,
    };

    extBoxAjax('get_html_popup', 'account-message-box', params, extraOptions, window.top);
};
