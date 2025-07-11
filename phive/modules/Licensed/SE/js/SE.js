licFuncs.validateNid = function(nid){

    // 1976 05 12 1610

    // We get rid of all non numbers.
    nid = nid.replace(/\D/g, '');

    if(nid.length != 12){
        return false;
    }

    var n;
    var checkSum = 0;

    // Luhn check
    // We can use split('') as we're not working with a Unicode string here.
    var ms       = '212121212'.split('');
    var toCheck  = nid.split('').slice(2, 11);

    for (var i = 0; i < 9; i++) {

        res = parseInt(ms[i]) * parseInt(toCheck[i]);

        if(res > 9){
            arr = res.toString().split('');
            res = parseInt(arr[0]) + parseInt(arr[1]);
        }

        checkSum += res;
    }

    var lastDigit = 10 - parseInt(checkSum.toString().slice(-1));
    lastDigit     = lastDigit == 10 ? 0 : lastDigit;

    if (lastDigit != parseInt(nid.slice(-1))) {
        return false;
    }

    return nid;
};

licFuncs.validatePersonalNumberOnRegister = function (value) {
    return value.length === 12 && isNumber(value);
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

licFuncs.onRgLoginPopupLoaded = function(){
    if(empty(rgls.deposit)){
        $('#rg-login-popup-proceed').hide();
        $('.lic-mbox-close-box').hide();
    }
};

licFuncs.onRgReady = function(options){
    const lockInput = $('#lock-hours');
    const indefiniteCheckbox = $('#se-indefinite');

    lockInput.on('input', function() {
        indefiniteCheckbox.prop('checked', false);
        options.hideError();
    });

    indefiniteCheckbox.on('input', function() {
        lockInput.val('');
        options.hideError();
    });
};

licFuncs.isRgLockDataEntered = function() {
    const lockInput = $('#lock-hours');
    const indefiniteCheckbox = $('#se-indefinite');

    return lockInput.val().length !== 0 || indefiniteCheckbox.is(':checked');
};

licFuncs.onRgLockClick = function(options){
    options.indefinitely = $("#se-indefinite").is(':checked') ? 'yes' : 'no';
    return options;
};

// TODO check if we can use instead "bank_id_url" on the response and move the append logic on the BE
//  the URL is generated via "bankIdStart" returned by "startLoginVerification" /Paolo
licFuncs.loginVerificationSuccess = function(res) {
    const extUrl = res.result.redirect_url;
    const target = isIframe() ? window.parent : window;
    var width,
          height,
          desktopWidth = 425,
          desktopHeight = 880,
          mobileWidth = '100%',
          mobileHeight = '100vh';

    var visibleWindowSize = getVisibleWindowSize();
    if (visibleWindowSize >= 800) {
        desktopWidth = 800
    }

    if(isMobile()){
        width = mobileWidth;
        height = mobileHeight;
    }else{
        height = getResizedHeight(desktopHeight) + "px";
        width = desktopWidth + "px";
    }

    var extraStyle = {
            width: width,
            height: height,
            baseZIndex: 10008,
            callb: function () {
                document.body.className += ' ' + 'scrolling-disabled';

                // Remove the header dimension from the iframe height.
                var popupHeader = $('.lic-mbox-header');
                if (popupHeader.length > 0) {
                    var iframeHeight = $('.multibox-content iframe').height() - popupHeader.height();
                    if(isIos()) {
                        iframeHeight -= 34;
                    }
                    $('.multibox-content iframe').height(iframeHeight);
                }
            },
            onClose: function () {
                clearInterval(window.pollId);
                return document.body.classList.remove('scrolling-disabled')
            }
        };

    // if (isIos()) {
    //     goTo(extUrl, '_blank');
    // } else {
       iframeAjaxBox('login-bankid', extUrl, 'verification.method', extraStyle, target);
       hideLoader();
    // }
};

/**
 * Show the popup for deposit limits to be displayed in sportsbook
 *
 */
licFuncs.showDepositLimitPromptSportsbook = function (){
    var extraOptions = isMobile() ? {width: '100%'} : {width: 800};
    var params = {
        module: 'Licensed',
        file: 'dep_lim_info_box',
        noRedirect: true
    };
    extBoxAjax('get_raw_html', 'dep-lim-info-box', params, extraOptions, top);
};

licFuncs.showExternalIntermediaryStep1Registration = function (context, skip_validation, allow_close_redirection = true, country, lic_params) {
    if (typeof lic_params !== 'undefined') {
        licFuncs.prepareExternalVerification(lic_params)
    }

    if (context === 'registration' && !validateStep1()) {
        return false;
    }

    licFuncs.startExternalVerification(context);
}

licFuncs.showExternalIntermediaryStep1CCValidation = function (){
    const userDetails = {
        'email': $("#email").val(),
        'mobile': $("#mobile").val(),
        'country_prefix': $("#country_prefix").val()
    };

    const extraOptions = isMobile() ? {width: '100vw', height: '100vh'} : {width: '450px'};

    var params = {
        module: 'DBUserHandler',
        file: 'registration_communication_channel_verification',
        boxid: 'account_verification_popup',
        id: 'communication_channel_verification',
        boxtitle: 'account-verification.popup-title',
        closebtn: 'yes',
        extra_css: 'account-verification__popup',
        user_details: userDetails,
    }

    extBoxAjax('get_html_popup', 'account_verification-box', params, extraOptions);
}

licFuncs.forcePollingCountry = 'SE';

/**
 * Show the BankID loader
 *
 * @param context
 */
licFuncs.startExternalVerification = function(context) {
    doVerify(
        licFuncs.external_verification_params.socket_url,
        context,
        licFuncs.external_verification_params.message,
    )
}

licFuncs.startBankIdRegistration = function(context) {
    const path = window.location.pathname;

    // Check if the URL path starts with /sports/ or /super/
    if (
        path.includes("/sports/") ||
        path.includes("/super/") ||
        path.includes("/mobile/sports/") ||
        path.includes("/mobile/super/")
    ) {
        document.cookie = "redirect_after_login=" + encodeURIComponent(window.location.href) + "; max-age=300; path=/";
    } else if (document.cookie.indexOf('redirect_after_login=') !== -1) {
        document.cookie = "redirect_after_login=; max-age=-1 path=/";
    }
    licFuncs.startExternalVerification(context);
}

licFuncs.validateNid = function(n) {
    return true;
}

/**
 * Show the BankID registration popup
 *
 */
licFuncs.showBankIdRegistrationPopup = function (bankIdData) {
    var bankidRegistrationPopupHandler = licFuncs.bankidRegistrationPopupHandler();
    bankidRegistrationPopupHandler.showBankIdRegistrationPopup('bankid_registration_popup', bankIdData);
}

licFuncs.bankidRegistrationPopupHandler = function (){
    return {
        getCommonPopupParams: function (popup, bankIdData) {
            return {
                module: 'Licensed',
                file: popup,
                boxid: 'bankid_registration_popup',
                boxtitle: 'bankid.registration.form.title',
                closebtn: 'no',
                bank_id_data: JSON.stringify(bankIdData)
            };
        },

        getCommonPopupExtraOptions: function (width) {
            return isMobile() ? {width: '100%'} : {width: width};
        },


        showBankIdRegistrationPopup: function (fileName = 'bankid_registration_popup', bankIdData) {
            var params = this.getCommonPopupParams(fileName, bankIdData),
                extraOptions = this.getCommonPopupExtraOptions(847);
            extBoxAjax('get_html_popup', 'bankid_registration_popup', params, extraOptions);
        },
    }
}

/**
 * Show the BankID account verification popup
 *
 */
licFuncs.showBankIdAccountVerificationPopup = function () {
    var bankidAccountVerificationPopupHandler = licFuncs.bankidAccountVerificationPopupHandler();
    bankidAccountVerificationPopupHandler.showBankIdAccountVerificationPopup('bankid_account_verification_popup');
}

licFuncs.bankidAccountVerificationPopupHandler = function (){
    return {

        userDetails: {
            'email': $('#email').val(),
            'mobile': $('#mobile').val(),
            'country_prefix': $('#country_prefix').val()
        },

        getCommonPopupParams: function (popup) {
            return {
                module: 'Licensed',
                file: popup,
                boxid: 'account_verification_popup',
                closebtn: 'yes',
                extra_css: 'account-verification__popup',
                user_details: this.userDetails,
            };
        },

        getCommonPopupExtraOptions: function (width) {
            return isMobile() ? {width: '100%'} : {width: width};
        },


        // We go in here after accepting QR code from BankId
        showBankIdAccountVerificationPopup: function (fileName = 'bankid_account_verification_popup') {
            var params = this.getCommonPopupParams(fileName),
                extraOptions = this.getCommonPopupExtraOptions(450);
            extBoxAjax('get_html_popup', 'bankid-account-verification-popup', params, extraOptions);
        },
    }
}

// TODO is this needed?
licFuncs.onDocReady();
