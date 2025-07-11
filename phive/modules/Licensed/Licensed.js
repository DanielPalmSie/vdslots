
var licUrl = '/phive/modules/Licensed/ajax.php';

var licFuncs = {
    external_verification_params: {},
    limits: {
        has_default_deposit_limits : false
    },
    doGamTest: function(extUrl){
        var width,
          height,
          desktopWidth = 800,
          desktopHeight = 760,
          mobileWidth = '100%',
          mobileHeight = '100vh';

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
                return document.body.classList.remove('scrolling-disabled')
            }
        };

        if (isIos()) {
            goTo(extUrl, '_blank');
        } else {
            iframeAjaxBox('gamtest-box', extUrl, 'gamtest.box.headline', extraStyle);
        }
    },
    onRgReady: function(options){
        const lockInput = $('#lock-hours');

        lockInput.on('input', function() {
            options.hideError();
        });
    },
    isRgLockDataEntered: function() {
        const lockInput = $('#lock-hours');

        return lockInput.val().length !== 0;
    },
    onRgLockClick: function(options){
        return options;
    },
    showBasedOnTheCookie: function() {
        var favorite_login = getCookieValue('favorite_login');

        if($('#lic-mbox-login-custom').children().length && (favorite_login==='showCustomLogin'|| favorite_login==='showDefaultLogin')) { // favorite
            licFuncs[favorite_login]();
        } else if(licFuncs.hasOwnProperty('defaultLogin')) { // country default
            licFuncs.defaultLogin();
        } else { // fallback
            licFuncs.showDefaultLogin();
        }
    },
    onVerifySuccessReg: function(){
        submitStep1();
    },
    showVerifyIdentityReminder: function () {
        var params = {
            file: 'account_verification_reminder',
            boxtitle: 'verification-reminder.popup.title',
            closebtn: 'no',
            module: 'Licensed'
        };

        var extraOptions = {
            width: isMobile() ? '100%' : 500,
            height: isMobile() ? '100%' : 335
        };

        extBoxAjax('get_html_popup', 'mbox-msg', params, extraOptions);
    },
    showAccountVerificationOvertime: function () {
        var params = {
            file: 'account_verification_overtime',
            boxtitle: 'message',
            closebtn: 'no',
            module: 'Licensed'
        };

        extBoxAjax('get_html_popup', 'mbox-msg', params);
    },
    showDepositLimitPrompt: function (){
        return false;
    },
    showCompanyDetailsPopup: function() {
        return false;
    },
    showMessageOnCashier: function (){
        return false;
    },
    showCustomLogin: function(){
        $("#lic-mbox-login-default").hide();
        $("#lic-mbox-login-otp").hide();
        $("#lic-mbox-login-custom").show();
        $("#lic-mbox-login-custom-info").show();
        $("#lic-mbox-btn-show-custom").addClass("lic-mbox-btn-active").removeClass("lic-mbox-btn-inactive");
        $("#lic-mbox-btn-show-default").addClass("lic-mbox-btn-inactive").removeClass("lic-mbox-btn-active");
        licFuncs.setFavoriteLoginCookie('showCustomLogin');
    },
    changeToCustomMitIdLogin: function () {
        if (!$("#lic-mbox-btn-show-custom").data('mitIdActive')) {
            return;
        }

        licFuncs.showCustomLogin();
    },
    showDefaultLogin: function(clearFields = false){
        $("#lic-login-errors").hide();
        $("#lic-mbox-login-captcha").hide();
        $("#lic-mbox-login-captcha-reset").hide();
        $("#lic-mbox-login-default").show();
        $('.login-popup__top-section').show();
        $("#lic-mbox-login-otp").hide();
        $("#lic-mbox-login-custom").hide()
        $("#lic-mbox-login-custom-info").hide();
        $("#lic-mbox-btn-show-custom").removeClass("lic-mbox-btn-active").addClass("lic-mbox-btn-inactive");
        $("#lic-mbox-btn-show-default").removeClass("lic-mbox-btn-inactive").addClass("lic-mbox-btn-active");

        if (clearFields) {
            $('#lic-login-username-field').val('');
            $('#lic-login-password-field').val('');
        }

        licFuncs.setFavoriteLoginCookie('showDefaultLogin');
    },
    showOtpLogin: function(description){
        $("#lic-login-errors").hide();
        $("#lic-mbox-login-otp").show();
        $("#lic-mbox-login-otp .otp-description").html(description);
        $('.login-popup__top-section').hide();
        $("#lic-mbox-login-default").hide();
        $("#lic-mbox-login-custom").hide();
        $("#lic-mbox-login-custom-dk-mitt-id").hide();
        $("#lic-mbox-login-custom-info").hide();
        $("#lic-mbox-login-otp-captcha").hide();
        $("#lic-mbox-login-otp-captcha-reset").hide();
        $("#otp-header").show();
        $("#otp-description").show();
        $("#otp-label").show();
        $("#otp-bottom-link").show();
        $("#lic-mbox-btn-show-custom").removeClass("lic-mbox-btn-active").addClass("lic-mbox-btn-inactive");
        $("#lic-mbox-btn-show-default").removeClass("lic-mbox-btn-inactive").addClass("lic-mbox-btn-active");
        $('#lic-login-otp-field').val('');
    },
    showResetPassword: function(){
        $(".lic-mbox-container > div").hide();
        $("#lic-login-errors").hide();
        $("#lic-mbox-login-reset-password").show();
    },
    showIpVerificationLogin: function(){
        $(".lic-mbox-container > div").hide();
        $("#lic-mbox-login-ipverification").show();
        GeocomplyModule.initGeoComply();
    },
    showLoginCaptcha: function(image_src){
        $("#lic-mbox-login-captcha img").attr('src', image_src);
        $("#lic-mbox-login-captcha").show();
        $("#lic-mbox-login-captcha-reset").show();
    },
    showLoginOTPCaptcha: function(image_src) {
        licFuncs.showOtpLogin();

        $('#login-otp-captcha').val('');

        $("#lic-login-errors").hide();

        $("#otp-header").hide();
        $("#otp-description").hide();
        $("#otp-label").hide();
        $("#otp-bottom-link").hide();

        $("#lic-mbox-login-otp-captcha img").attr('src', image_src);
        $("#lic-mbox-login-otp-captcha").show();
        $("#lic-mbox-login-otp-captcha-reset").show();
    },
    resetCaptcha: function(requested_captcha){
        var requested = '_CAPTCHA';
        var captcha_img = $("#captcha_img");
        var registration_captcha_input = $("#registration_captcha_input");
        if (registration_captcha_input.length) {
            registration_captcha_input.val('');
        }

        if (typeof requested_captcha !== 'undefined') {
            requested = '_CAPTCHA&requested=' + requested_captcha;
            captcha_img = $("#captcha_img_" + requested_captcha);
        }

        var image_src = captcha_img.attr('src');
        var new_src = image_src.slice(0, image_src.lastIndexOf('_CAPTCHA')) + requested + '&reset=true&t=0.' + randInt(10000000, 99999999) + '+' + randInt(100000000, 999999999);
        captcha_img.attr('src', new_src);
    },
    showJurisdictionalNotice: function() {
        const url_params = new URLSearchParams(window.location.search);

        if(url_params.has('display_mode') && url_params.has('auth_token')) {
            return;
        }

        var params = {
            file: 'jurisdictional_notice_prompt',
            closebtn: 'no',
            boxtitle: 'jurisdictional.notice',
            module: 'Licensed'
        };

        options = {
            width: isMobile() ? '100%' : '856px',
            height: isMobile() ? '100%' : '500px'
        };

        extBoxAjax('get_html_popup', 'mbox-msg', params, options)
    },
    setFavoriteLoginCookie: function(value) {
        $(document).ready(function() {
            sCookie('favorite_login', value);
        });
    },
    onLoginReady: function(){
        $("#lic-mbox-login-custom-top").show();
        $("#lic-mbox-login-separator").show();
        licFuncs.showBasedOnTheCookie();
        $("#lic-mbox-btn-show-default").click(licFuncs.showDefaultLogin);
        $("#lic-mbox-btn-show-custom").click(licFuncs.showCustomLogin);
        $("#lic-mbox-btn-show-custom").click(licFuncs.changeToCustomMitIdLogin);
    },
    onDocReady: function(){
        $(document).ready(function(){
            $('#top-login-form').submit(function (evt) {
                evt.preventDefault();
            });
        });
    },
    rgValidateResettableCommon: function(arr, enforcedNonEmptyValidation){
        enforcedNonEmptyValidation = enforcedNonEmptyValidation || false;
        var ret = [];
        var tspanCount = arr.length;
        _.each(arr, function(el){
            //console.log([el.time_span, el.limit]);
            if(empty(el.limit) || (parseInt(el.limit)) > Number.MAX_SAFE_INTEGER){
                ret.push(el.time_span);
            }
        });

        // We enforce values to be set, all empty scenario is not valid in this case.
        if(enforcedNonEmptyValidation) {
            return empty(ret) ? true : ret;
        }
        // All is well if they are all empty, we want all or nothing.
        return (empty(ret) || ret.length == tspanCount) ? true : ret;
    },
    rgValidateResettable: function(arr, type, enforcedNonEmptyValidation){
        enforcedNonEmptyValidation = enforcedNonEmptyValidation || false;
        return licFuncs.rgValidateResettableCommon(arr, enforcedNonEmptyValidation);
    },
    rgSubmitAllResettable: function(rg_login_info_callback, enforcedNonEmptyValidation, closeSelf, target, closeMobileGameOverlay){
        enforcedNonEmptyValidation = enforcedNonEmptyValidation || false;
        closeSelf = closeSelf || null;
        target = target || window; // window|parent
        closeMobileGameOverlay = closeMobileGameOverlay || false;

        var toPost = [];
        var crossBrandFlags = {};

        // IDs need to be generated like this in PHP: "resettable-{$type}-{$tspan}"
        $('[id^="resettable-"]').each(function(index){
            el = $(this);
            tmp = el.attr('id').split('-');
            var limitData = {type: tmp[1], time_span: tmp[2], limit: el.val()};
            // If there is an item with ID "autorevert-{$type}-{$tspan}" and value = 1 we add autorevert param to that single limit
            var autorevertField = $('#autorevert-' + tmp[1] + '-' + tmp[2]);
            if (autorevertField.length && autorevertField.val()) {
                limitData.autorevert = 1;
            }
            toPost.push(limitData);
        });

        toPost = _.groupBy(toPost, function(el){
            return el.type;
        });

        for (var type in toPost) {
            // we need to restore the standard status before checking the error, otherwise it will never reset
            _.each(toPost[type], function(el){
                $('#resettable-' + el.type + '-' + el.time_span).addClass('discreet-border required-input').removeClass('input-error');
            });

            var validationRes = licFuncs.rgValidateResettable(toPost[type], type, enforcedNonEmptyValidation);

            if(validationRes !== true){
                _.each(validationRes, function(tspan){
                    $('#resettable-' + type + '-' + tspan).removeClass('discreet-border required-input').addClass('input-error');
                });
                return false;
            }

            // To support the param on all type of limits we need to inject it into pData.
            if($('#cross-brand-limit-'+type).length) {
                crossBrandFlags[type] = $('#cross-brand-limit-'+type).prop('checked');
            }
        }

        pData = JSON.stringify({limits: toPost, crossBrandFlags: crossBrandFlags});

        saveAccCommon('save_resettables', {data: pData}, function (res) {
            if (!empty(res.result) && res.result === 'ok') {
                var msg = !empty(res.msg) ? res.msg : 'OK';
                mboxMsg(msg, true, function () {
                    if (parent.$('#vs-popup-overlay__iframe').length && closeMobileGameOverlay === true) {
                        parent.$('.vs-popup-overlay__header-closing-button').click();
                    }

                    if (!empty(closeSelf)) {
                        target.$.multibox('close', closeSelf);
                    } else if (!empty(rg_login_info_callback)) {
                        window.location.href = rg_login_info_callback;
                    } else {
                        jsReloadBase();
                    }
                });
            } else {
                var msg = !empty(res.msg) ? res.msg : 'ERROR';
                mboxMsg(msg, true);
            }
        }.bind(rg_login_info_callback));
    },
    validateNid: function(nid){
        return true;
    },
    onLoginError: function(res){
        if(!empty(res.result.legacy_nid)){
            $("#nid-field").val(res.result.legacy_nid);
            licFuncs.showCustomLogin();
        }
    },
    assistOnLimitsChange: function(type) {
        var daily_input = '#' + type + '-day';
        var weekly_input = '#' + type + '-week';
        var monthly_input = '#' + type + '-month';

        $(daily_input).on('keyup', function () {
            const clearedValue = getClearedNumber($(this).val());

            $(this).val(clearedValue);
            $(weekly_input).val(getMaxIntValue( clearedValue * 7));
            $(monthly_input).val(getMaxIntValue( clearedValue * 30));
        });

        $(weekly_input).on('keyup', function () {
            const clearedValue = getClearedNumber($(this).val());
            $(this).val(clearedValue);
        });

        $(monthly_input).on('keyup', function () {
            const clearedValue = getClearedNumber($(this).val());
            $(this).val(clearedValue);
        });

        if (type !== 'resettable-deposit' || !this.limits.has_default_deposit_limits) {
            $("#resettable-deposit-day").trigger('keyup');
        }
    },
    setTopMobileLogos: function (value) {
        window.topMobileLogos = value;
        return value;
    },
    topMobileLogos: function () {
        return window.topMobileLogos;
    },
    assistOnLoginLimitsChange: function(type) {
        var daily_input = '#' + type + '-day';
        var weekly_input = '#' + type + '-week';
        var monthly_input = '#' + type + '-month';

        $(daily_input).on('keyup', function () {
            let clearedValue = getClearedNumber($(this).val());

            if (+clearedValue > 24) {
                clearedValue = 24;
            }

            $(this).val(clearedValue);

            $(weekly_input).val(getMaxIntValue(clearedValue * 7));
            $(monthly_input).val(getMaxIntValue(clearedValue * 30));
        });
        $(weekly_input).on('keyup', function () {
            let clearedValue = getClearedNumber($(this).val());

            if (+clearedValue > 24 * 7) {
                clearedValue = 24 * 7;
            }

            $(this).val(clearedValue);
        });
        $(monthly_input).on('keyup', function () {
            let clearedValue = getClearedNumber($(this).val());

            if (+clearedValue > 24 * 30) {
                clearedValue = 24 * 30;
            }

            $(this).val(clearedValue);
        });
    },
    startExternalVerification: function(context) {},
    prepareExternalVerification: function(params) {
        licFuncs.external_verification_params = params;
    },
    appendExtraJurisdictionalInformations: function (postData) {
        return postData;
    },
    validateMobileLength: function(field){
        return true;
    },
    validatePersonalNumberOnRegister: function(value) {
        return value.length === 12 && isNumber(value)
    },
    unloadGame: function(el) {
        if(!isMobile())
            el.attr('src', '');
        else
            el.remove();

        return true;
    }
};


licFuncs.showNationalityPopup = function () {
    var nationalityPopupHandler = licFuncs.nationalityPopupHandler();
    nationalityPopupHandler.showNationalityPopup('update_nationality_popup');
}

licFuncs.nationalityPopupHandler = function (){
    return {
        getCommonPopupParams: function (popup) {
            return {
                module: 'Licensed',
                file: popup,
                boxtitle: 'msg.title',
                closebtn: 'no',
                top_left_icon: true
            };
        },

        getCommonPopupExtraOptions: function (width) {
            return isMobile() ? {width: '100%'} : {width: width};
        },


        showNationalityPopup: function (fileName = 'add_nationality_popup') {
            var params = this.getCommonPopupParams(fileName),
                extraOptions = this.getCommonPopupExtraOptions(500);
            extBoxAjax('get_html_popup', 'nationality-popup-box', params, extraOptions);
        },

        validateOnChange: function (element) {
            var id = element.attr('id');
            var val = element.val();

            switch (id) {
                case 'nationality-select':
                    this.showhideerror('.nationality-error', val);
                    return val;
                case 'birthcountry-select':
                    this.showhideerror('.birthcountry-error', val);
                    return val;
            }
        },

        showhideerror: function (elementid, show){
            var selector = $('.nationality-main-popup');

            if(!show){
                selector.find(elementid).removeClass('hidden');
            } else {
                selector.find(elementid).addClass('hidden');
            }
        },

        sendSelected: function () {
            //verification before saving
            var nationality = this.validateOnChange($('#nationality-select'))
            var birthcountry = this.validateOnChange($('#birthcountry-select'));

            if(nationality != '' & birthcountry != ''){
                saveAccCommon('save_nationality_pob', {nationality: nationality, birthcountry: birthcountry}, function (res) {

                    if (res.success) {
                        mboxClose('nationality-popup-box', function () {
                            mboxMsg(res.msg, true, function(){ execNextPopup(); }, 400);
                        });
                        return;
                    }

                    mboxMsg(res.msg, true, function () {
                        jsReloadBase();
                    }, 400);
                });
            }

        },
        sendCountrySelected: function (callback) {
            //verification before saving
            var nationality = this.validateOnChange($('#nationality-select'))

            if(nationality != ''){
                saveAccCommon('save_nationality', {nationality: nationality}, function (res) {

                    if (res.success) {
                        if (typeof callback !== "undefined") {
                            callback();
                            return;
                        }
                        mboxClose('nationality-popup-box', function () {
                            mboxMsg(res.msg, true, function(){ execNextPopup(); }, 400);
                        });
                        return;
                    }

                    mboxMsg(res.msg, true, function () {
                        jsReloadBase();
                    }, 400);
                });
            }

        },
    }
}


function licJson(licFunc, options, func, returnFormat){
    options.lang          = cur_lang;
    options.site_type     = siteType;
    options.lic_func      = licFunc;
    returnFormat          = empty(returnFormat) ? 'json' : returnFormat;
    options.return_format = returnFormat;
    $.post(licUrl, options, func, returnFormat);
}

window.pollId = null;
function onVerifySuccess(res){
    console.log(res);
    top.$.multibox('close', 'login-bankid');
    clearInterval(window.pollId);

    switch(res.result.context){
        case 'login':
        case 'verification':
            mgSecureJson({action: 'token_login', token: res.result.login_token, lang: cur_lang}, function(res){
                if (res.result.action) {
                    return dynamicCall(window, res.result.action.method, res.result.action.params);
                }
                if(res.success){
                    if (!empty(res.result.redirect_url)){
                        var isFacebookWebView = navigator.userAgent.indexOf('FBAN') > -1 || navigator.userAgent.indexOf('FBAV') > -1;
                        if (isFacebookWebView && isAndroid()) {
                            fbWebViewReload(res.result.redirect_url);
                        } else {
                            window.top.location.href = res.result.redirect_url;
                        }
                    } else {
                        jsReloadBase();
                    }
                } else {
                    $("#lic-login-errors").addClass("error").html(res.result.msg).show();
                    lic('onLoginError', [res]);
                }
            });
            break;
        case 'registration':
            if (registration_mode === 'bankid') {
                handleBankIdRegistration(res.result.lookup_res);
            } else {
                licFuncs.onVerifySuccessReg.call();
            }
            break;
    }
}

/*
* It's hack for Android Facebook webview
* TODO need to fix this
* */
function fbWebViewReload(url) {
    setTimeout(function () {
        (function () {
            const form = document.createElement('form');
            const csrf_token = document.querySelector('meta[name="csrf_token"]')?.content;

            form.method = 'POST';
            form.action = url || window.location.href;
            form.innerHTML = `
                <input type="hidden" name="_t" value="' + Date.now() + '">
                <input type="hidden" name="csrf_token" value="${csrf_token}">
            `;
            document.body.appendChild(form);
            form.submit();
        })();
    }, 100);
}

function onVerifyFail(res){
    console.log(res);
    hideLoader();
    clearInterval(window.pollId);
    top.$.multibox('close', 'login-bankid');

    const errorHtml = '<p>' + res.result + '</p>'
    $('#lic-login-errors').addClass('error').html(errorHtml).show();
}

function clearValidationError() {
    $("#nid-field").removeClass('input-error').off('change keyup');
    $("#nid-field-error").hide();
}

function doVerify(socketUrl, context, msg){
    var nid = $("#nid-field").val();

    // licFuncs.validateNid() should return the NID in case of success, false otherwise.
    nid = licFuncs.validateNid(nid);
    if(!nid){
        $("#nid-field").addClass('input-error');
        $("#nid-field-error").show();
        $("#nid-field").on("change keyup", function () {
            if (licFuncs.validateNid($(this).val())) {
                clearValidationError();
            }
        });
        return false;
    }

    showLoader(licJson('startLoginVerification', {nid: nid, context: context, country: $("#country").val()}, function(res){
        clearValidationError();

        if(res.success){

            if (!empty(licFuncs.loginVerificationSuccess)) {
                licFuncs.loginVerificationSuccess(res);
            }

            // Our polling function that will be used in case websockets are off the table for some reason.
            var pollFunc = function(){
                var pollCountry = $("#country").val() || licFuncs.forcePollingCountry || "";
                pollId = setInterval(function(){// Verification Session ID
                    licJson('pollLoginVerification', {id: res.result.id, country: pollCountry}, function(pollRes){
                        if(pollRes.success){
                            onVerifySuccess(pollRes);
                        } else if(pollRes.result != 'waiting_for_result') {
                            onVerifyFail(pollRes);
                        }
                    });
                }, 3000);
            };

            // Do we want to try to do this via websockets?
            if(typeof socketUrl == 'string' && res.result.supports_callback){
                console.log(socketUrl);
                doWs(
                    socketUrl,
                    function(e){
                        var wsRes = JSON.parse(e.data);
                        wsRes.success ? onVerifySuccess(wsRes) : onVerifyFail(wsRes);
                    },
                    pollFunc // In case the websocket connection breaks we start polling.
                );
            }else{
                // If we don't want to use websockets we start polling right away.
                pollFunc.call();
            }

        } else {
            // Fail on start
            onVerifyFail(res);
        }
    }), true, msg);
}

licFuncs.industryOnChange = function (selector, boxId, autoCompleteSelector) {
    $(selector).on('change', function() {
        const parentElement = $("#"+boxId).length > 0 ? "#"+boxId : '.lic-mbox-wrapper';
        const $occupationInput = $(autoCompleteSelector);
        const $occupationError = $('#occupationError');
        const $jobTitleSection = $(parentElement + " .job-title-section");

        if($jobTitleSection.hasClass('hidden-force')) {
            $jobTitleSection.removeClass('hidden-force');
            $.multibox('posMiddle', boxId);
        }

        $('#industryError').text('');

        $occupationInput
            .val('')
            .prop('disabled', true)
            .addClass('input-loading')
            .attr('placeholder', $('#occupation-loading-job-titles').val());

        $occupationError.text('');

        const industry = $(this).find(":selected").val();
        licFuncs.initializeOccupationAutoComplete(autoCompleteSelector, parentElement, []);

        mgAjax({
            action: 'get-occupation-list',
            'industry': industry
        }, function(result) {
            const res = JSON.parse(result);
            if(res.success) {
                $occupationInput
                    .prop('disabled', false)
                    .removeClass('input-loading')
                    .attr('placeholder', 'Job Title');

                licFuncs.initializeOccupationAutoComplete(autoCompleteSelector, parentElement, res.occupation);
            } else {
                $occupationInput
                    .prop('disabled', true)
                    .removeClass('input-loading')
                    .attr('placeholder', $('#occupation-error-loading').val());

                $occupationError.text($('#occupation-error-loading-try-again').val());
            }
        });
    });
}

function positionAutoComplete(selector, parentElement) {
    var $input = $(selector);
    var $autocomplete = $(".ui-autocomplete");

    if ($input.length === 0 || $autocomplete.length === 0) return;

    var offset = $input.offset();
    var height = $input.outerHeight();
    var width = $input.outerWidth();

    let top = offset.top + height;
    let left = offset.left;

    // Check if inside an iframe
    var isInIframe = window.location !== window.parent.location;
    if(!isInIframe) {
        var rect = $input[0].getBoundingClientRect();
        const $popup = $(parentElement);
        var popupRect = $popup[0].getBoundingClientRect();

        // Calculate position relative to the popup
        top = rect.top - popupRect.top + $popup.scrollTop() + rect.height;
        left = rect.left - popupRect.left + $popup.scrollLeft();
    }

    // Apply styles
    $autocomplete.css({
        top: top + "px",
        left: left + "px",
        width: width + "px",
        position: "absolute",
        zIndex: 11000,
        margin: 0
    });
}

function handleScroll(selector, parentElement) {
    let ticking = false;

    $("#sourceoffundsbox-wrapper .registration-container, #spending-amount-box .lic-mbox-container").on("scroll", function () {
        if (!ticking) {
            requestAnimationFrame(() => {
                if ($(".ui-autocomplete").is(":visible")) {
                    positionAutoComplete(selector, parentElement);
                }
                ticking = false;
            });
            ticking = true;
        }
    });
}

licFuncs.initializeOccupationAutoComplete = function (selector, appendTo, dataList, callback= null) {
    let userInitiated = false;
    var noMatch = false;
    $(selector).autocomplete({
        source: function(request, response) {
            var term = request.term.trim();

            // Default minLength condition (for normal searches)
            if (!noMatch && term.length < 2) return;

            // reset noMatch
            noMatch = false;

            var filteredList = $.grep(dataList, function(item) {
                return item.toLowerCase().indexOf(term.toLowerCase()) !== -1;
            });

            if (filteredList.length === 0) {
                noMatch = true; // No match found
            } else {
                noMatch = false; // Reset error state
            }

            response(filteredList);
        },
        appendTo: $(appendTo).length > 0 ? $(appendTo): $('.lic-mbox-wrapper'),
        position: {
            my: "left top",
            at: "left bottom",
            collision: "none"
        },
        minLength: 2,
        select: function(event, ui) {
            $('#occupationError').text('');
            $(this).removeClass('error');
        },
        search: function(event, ui) {
            if (userInitiated) {
                updateErrorMsg(this, dataList);
            }
        },
        change: function(event, ui) {
            // This event fires when the value of the input changes
            if (!ui.item) {
                updateErrorMsg(this, dataList);
            } else {
                // If an item from the autocomplete list was selected
            }
        },
        open: function (event) {
            if (!userInitiated) {
                $(this).autocomplete("close");
                return;
            }
            $(appendTo +" .job-title-section .job-title-input-container").addClass('select-wrapper');
        },
        close: function (event) {
            $(appendTo +" .job-title-section .job-title-input-container").removeClass('select-wrapper');
        }
    }).focus(function (e) {
        if (e.originalEvent) {
            userInitiated = true;
            $(this).autocomplete("search");
        }
    }).click(function() {
        userInitiated = true;
        if (noMatch) {
            $(this).autocomplete("search", "  ");
        }
    });
    if(!!$(selector).val()) {
        setTimeout(function() {
            $(selector).autocomplete("search");
        }, 1000);
    }
    handleScroll(selector, appendTo);

    // callback if needed
    callback && callback();
}


function updateErrorMsg (obj, dataList) {
    const input = $(obj).val();
    let exists = input && $.inArray(input, dataList);
    if(exists <= 0) {
        let type = empty(input) ? 'empty-job-title' : 'valid-job-title';
        let errorMsg = $("#" + $(obj).attr('id') + '-' + type).val();
        $('#occupationError').text(errorMsg);
    }

    /*
    * If an autocomplete list open with single value and matched with user typed value
    * */
    const results = $.ui.autocomplete.filter(dataList, input);
    if(results.length === 1) {
        $('#occupationError').text('');
    }
}
