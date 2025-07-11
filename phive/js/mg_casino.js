
/*
   asp = aspect ration (width / height)
   gW = global width, parent width or more likely, window width
   gH = global height
   dynamic = can the game be resized to be bigger than the specs?
   oW = original width as set in the game
   oW = original height
   wD = width padding
   hD = height padding
 */

// Indicates whether to show the image in popups, based on whether it's a new brand.
var showNewDesignImage = !is_old_design;

function calcDims(asp, gW, gH, dynamic, oW, oH, wD, hD){
    if(typeof wD == 'undefined'){
        wD = 150;
        hD = 210;
    }

    if(!dynamic && gW > oW && gH > oH){
        w = oW;
        h = oH;
    }else{
        if(gW >= gW - wD){
            w = gW - wD;
            h = w / asp;
        }

        h = typeof h == 'undefined' ? gH : h;

        if(h >= gH - hD){
            h = gH - hD;
            w = h * asp;
        }
    }

    return [w, h];
}

function lic(func, args){
    if(typeof licFuncs == 'undefined' || typeof licFuncs[func] == 'undefined'){
        return false;
    }

    args == typeof args == 'undefined' ? [] : args;

    return licFuncs[func].apply(undefined, args);
}


function fbGetBaseOptions(){
    var options = {
        hideOnContentClick: false,
        hideOnOverlayClick: false,
        overlayColor: "#000",
        overlayOpacity: 0.8,
        margin: 20
    };
    return options;
}

// TODO refactor away this one, change to mboxMsg on all invocations.
function fancyShow(html, dim, callbf){
    if(typeof siteType == 'undefined')
        siteType = 'normal';

    /*
  if(siteType == 'mobile' && window.location.href.match(/\/mobile\/message\//)){
    return;
  }

  if(siteType == 'mobile'){
    if(typeof html == 'string'){
      html = html.replace(/(\r\n|\n|\r)/gm,"");
      var json = encodeURIComponent(html);
      var name = 'plainmsg';
    }else{
      if(!empty(html.phpfunc)){
        var name = 'msg';
      }else{
        html.msg = html.msg.replace(/(\r\n|\n|\r)/gm,"");
        var name = html.action == 'showmsg' ? 'showmsg' : 'msg';
      }
      var json = JSON.stringify(html);
    }
    var csrf_meta = document.querySelector('meta[name="csrf_token"]');
    var csrf_token = csrf_meta ? csrf_meta.content : '';
    $('body').append('<form style="display: hidden;" action="/'+cur_lang+'/mobile/message/" method="POST" id="mob-msg-form"><input type="hidden" name="'+name+'" value=""/><input type="hidden" name="token" value="'+csrf_token+'"/></form>');
    $("#mob-msg-form").find('input').val(json);
    $("#mob-msg-form").submit();

    return;
  }
    */

    var width = typeof dim == 'undefined' ? undefined : dim[0];
    if(empty(width))
        width = undefined;

    mboxMsg(html, false, callbf, width, true);
    return;
}

function fancyShowReload(html, dim){
    fancyShow(html, dim, function(){ jsReloadBase(); });
}

function fbClose(){
    mboxClose();
}

function fancyIframe(iframe, dim, complCallb){
    var options 	= fbGetBaseOptions();
    options.type 	= 'iframe';
    options.href	= iframe;
    options.autoScale = false;

    if(typeof complCallb != 'undefined'){
        options.onComplete	= function(){
            complCallb.call();
            fbResetStyle();
        };
    }else
        options.onComplete = fbResetStyle;

    if (!empty(dim)) {
        options.width 	= dim[0];
        options.height 	= dim[1];
    }
}

function fancyAjax(url, dim, complCallb){
    var options 	= fbGetBaseOptions();
    options.type 	= 'ajax';
    options.href	= url;

    if(typeof complCallb != 'undefined')
        options.onStart	= complCallb;

    if (!empty(dim)) {
        options.autoDimensions = false;
        options.width 	= dim[0];
        if(dim[1] != 0)
            options.height 	= dim[1];
    }
}

/**
 * This function is only executed the first time the iframe get's opened.
 */
function showRegistrationBox(qUrl) {

    if (!is_auth_allowed) {
        showAccessBlockedPopup();
        return;
    }

    if(registration_mode === 'paynplay') {
        showPayNPlayPopupOnLogin();
        return;
    }

    var is_step2 = qUrl.indexOf(registration_step2_url) >= 0;
    const isIdScanPopup = qUrl.indexOf(idscan_desktop_url) >=0;

    parent.$.multibox('close', 'login-box');

    $.multibox({
        url: llink(typeof qUrl == 'undefined' ? registration_step1_url : qUrl),
        id: 'registration-box',
        containerClass: is_step2 ? 'registration-box-step2' : '',
        name: !is_step2 ? 'registration1' : 'registration2',
        type: 'iframe',
        width: isMobile() ? '100%' : '906px',
        height: isMobile() ? '100%' : (is_step2 ? '830px' : '585px'),
        cls: 'mbox-deposit',
        globalStyle: {overflow: 'hidden'},
        baseZIndex: 10000,
        overlayOpacity: 0.7,
        // we don't want to use legacy scrolling attribute for iframe
        // see `phive/js/multibox.js`
        useIframeScrollingAttr: false,
        enableScrollbar: true,
        onClose: function () {
            isIdScanPopup && (top.$.multibox('toggleOverflow', false));
        },
        onComplete: function(){
            isIdScanPopup && (top.$.multibox('toggleOverflow', true));
            $('html').css({height: '100vh', overflow: 'hidden'});
            $.multibox('posMiddle', 'registration-box');
        }
    });
}

/**
 * This function is use to open registration box in mobile.
 */
function showMobileRegistrationBox() {
    if (!is_auth_allowed) {
        showAccessBlockedPopup();
        return;
    }

    if (registration_mode === 'paynplay') {
        showPayNPlayPopupOnLogin();
        return;
    } else if (registration_mode === 'bankid') {
        licFuncs.startBankIdRegistration('registration');
        return;
    }
    gotoLang('/mobile/register/');
}

/**
 * Show access blocked popup when user tries to register or login from blocked country or province
 */
function showAccessBlockedPopup() {

    var params = {
        module: 'IpBlock',
        file: 'access_blocked_popup',
        boxtitle: 'blocked.access.restricted',
        closebtn: 'yes'
    };

    var extraOptions = {
        width: isMobile() ? '100%' : 'auto',
        height: isMobile() ? '100%' : 560
    };

    extBoxAjax('get_html_popup', 'mbox-msg', params, extraOptions);
}

function mboxIframe(url, w, h, onClose, contentPadding){
    var iframe = $('<iframe></iframe>');
    var attrs  = {src: url};
    var css    = {};
    if(!empty(w)){
        css.width = w + 'px';
    }
    if(!empty(h)){
        css.height = h + 'px';
    }
    iframe.attr(attrs);
    iframe.css(css);
    mboxMsg(iframe[0].outerHTML, undefined, onClose, w, true, undefined, undefined, undefined, undefined, h, contentPadding);
}

function fbCloseLogOut(){
    window.location.href = '?signout=true';
}

function updateTopBalances(normal, bonus){
    if(typeof normal != 'undefined' && normal != null){
        $("#top-balance").html(normal.toFixed(2));
        $("#mobile-left-menu-balance").html(normal.toFixed(2));
    }

    if(typeof bonus != 'undefined' && normal != null){
        $("#top-bonus-balance").html(bonus.toFixed(2));
        $("#mobile-left-menu-bonus-balance").html(bonus.toFixed(2));
    }
}

async function ajaxRefreshTopBalances() {
    try {
        const ret = await mgJson({ action: "get_balances" });

        sendToGoogle(function () {
            updateTopBalances(parseInt(ret.cash_balance) / 100, (parseInt(ret.bonus_balance) + parseInt(ret.casino_wager)) / 100);
        });

    } catch (error) {
        console.error('Error fetching balances:', error);
    }
}

var loadSuccess = function(){
    $.multibox('close', "mbox-loader");
}

var iframeLoad = function(){
    $.multibox('close', "mbox-loader");
}

function mboxLoader(customMsg, target){
    var content = '<div class="mbox-loader">';
    customMsg = !empty(customMsg) ? '<div class="loader-msg">' + customMsg + '</div><br clear="all"/><br clear="all"/>' : '';

    var sCoins = '<ul>';
    _(5).times(function(n){ sCoins += '<li><div class="circle"></div><div class="ball"></div></li>'; });
    sCoins += '</ul>';

    content += customMsg + sCoins + '</div>';

    var multiboxTarget = target ? target.$.multibox : $.multibox;

    multiboxTarget({
        content: content,
        id: "mbox-loader",
        width:  '100%',
        height: '100%',
        type: 'html',
        cls: 'mboxloader',
        overlayOpacity: 0.9,
        boxType: 'loader'
    });
}

function showPermanentLoader(callb, customMsg, target){

    mboxLoader(customMsg, target);

    if(typeof callb != 'undefined'){
        callb.call();
    }
}
var ndlExceedPopupResolver = null;
function showConfirmationPopupOnCNDLExceed(available_limit, currency, till_date) {
    return new Promise(function(resolve){
        ndlExceedPopupResolver = resolve;
        var popupExtraOptions = isMobile()
            ? {
                width: '100vw',
                height: '100vh',
                containerClass: 'flex-in-wrapper-popup pnp-error-popup-wrapper button-fix--mobile',
            }
            : {width: '474px', containerClass: 'flex-in-wrapper-popup pnp-error-popup-wrapper'};

        var popupParams = {
            module: 'Licensed',
            file: 'confirm_processing_deposit_on_ndl_exceeded_popup',
            boxid: 'confirm_processing_deposit_on_ndl_exceeded_popup',
            closebtn: 'yes',
            depositAmount: available_limit,
            currency: currency,
            tillDate: till_date,
            boxtitle: 'customer_net_deposit.box.top.headline'
        };

        extBoxAjax(
            'get_html_popup',
            'confirm_processing_deposit_on_ndl_exceeded_popup',
            popupParams,
            popupExtraOptions
        );
    });
}

function showLoader(callb, stay, customMsg, timeout = 0){
    var closing = false;
    if(typeof $.multibox_vars != 'undefined')
        closing = $.multibox_vars['mbox-loader']['closing'];

    if($('#mbox-loader').length == 0 || closing){
        $(document).off('ajaxSuccess', loadSuccess);
        $('#cashier-deposit iframe').off('load', iframeLoad);

        setTimeout(() => mboxLoader(customMsg), timeout);

        if(typeof callb != 'undefined')
            setTimeout(callb, 200);

        if(stay)
            return;

        $(document).on('ajaxSuccess', loadSuccess);
        $('#cashier-deposit iframe').one('load', iframeLoad);
    }else{
        if(typeof callb != 'undefined')
            callb.call();
    }
}

function hideLoader(callb){
    $.multibox('close', "mbox-loader", callb);
}

function mboxAjax(url, params){
    var options = {
        id: "mbox-msg",
        type: 'ajax',
        url: url,
        params: params,
        lang: cur_lang,
        showClose: true
    };
    $.multibox(options);
}

function parentGetPhoneUsForm() {
    if (isIframe()) {
        top.getPhoneUsForm();
        return;
    }
    getPhoneUsForm();
}

function getPhoneUsForm(){
    mboxAjax('/phive/modules/Micro/ajax.php', {action: 'get-phone-us', lang: cur_lang});
}

var mgUrl = '/phive/modules/Micro/ajax.php';
const mgSecureUrl = '/phive/modules/Micro/ajax_secure.php';

function mgAjax(options, func){
    options.lang = cur_lang;
    options.site_type = siteType;
    return $.post('/phive/modules/Micro/ajax.php', options, func);
}

function mgSecureAjax(options, func){
    options.lang = cur_lang;
    options.site_type = siteType;
    return $.post(mgSecureUrl, options, func);
}

function mgJson(options, func){
    options.lang = cur_lang;
    return $.post(mgUrl, options, func, 'json');
}

function mgSecureJson(options, func){
    options.lang = cur_lang;
    return $.post(mgSecureUrl, options, func, 'json');
}

function VerifyPhone(action, complCallb, showCancel){
    // This is to make it compatible with the PSP logic
    if(typeof complCallb == 'string'){
        psp        = complCallb;
        complCallb = undefined;
    }else{
        psp        = '';
    }

    var showCancel = empty(showCancel) ? 'no' : 'yes';
    var vurl       = '/phive/modules/Mosms/ajax/verify.php';

    if(typeof cashierVersion == 'undefined')
        cashierVersion = '1';

    if (action == 'withdraw')
        PostDcPayment(action);

    else if(action == 'deposit' && quickOrMob()){
        $.get(vurl, {
            show_cancel: showCancel,
            lang: cur_lang,
            "cashier-version": cashierVersion,
            psp: psp
        }, function(ret){
            showPaymentMsg(ret);
            $("#msgBox").find('.cashierBoxInsert').show();
            $("#dcBox").hide();
            curCashierBox = $("#dcBox");
            $("#verify-start").find(".cashierDefaultBtnInner").attr("onclick", "").click(function(){ VerifyCode(action, psp); });
        });
    } else {
        showLoader(function(){
            fancyAjax(vurl, [], complCallb);
        });
    }
}

function hideBoxes(){
    $("#box-content").hide();
}

function showBoxes(){
    $("#box-content").show();
}

function setupCasinoSearch(func, func2, sel, sfield, finFunc){
    sel = typeof(sel) == 'undefined' ? "#search-result" : sel;
    sfield = typeof(sfield) == 'undefined' ? "#search_str" : sfield;
    $(sfield).click(function(){ $(this).val(''); });
    $(sfield).keyup(debounce(function(event){
        var cur = $(this);
        if(cur.val().length > 2){
            $.get("/phive/modules/Micro/json/game_search.php", {search_str: cur.val()}, function(res){
                if(res){
                    res = eval( '(' + res + ')' );
                    $(sel).html('');
                    $.each(res, func);
                    $("#search-result-holder").show();
                    if(typeof(finFunc) != 'undefined')
                        finFunc.call();
                }
            });
        }else if(func2 != undefined)
            func2.call();
    }, 500));
}

function setupLogin(){
    $("#login_username").focus(function(){
        $(this).val('');
        $(this).keydown(function(event){
            if (event.keyCode == 9 || event.keyCode == 11) {
                $("#login_password").val('');
            }
        });
    });

    $("#login_password").click(function(){
        $(this).val('');
    });
}

function closeQuickDeposit() {
    if (typeof top !== 'undefined' && top.$ && typeof top.$.multibox === 'function') {
        top.$.multibox('close', 'cashier-box');
    } else if ($ && typeof $.multibox === 'function') {
        $.multibox('close', 'cashier-box');
    }
}

function playBonus(bonus_id){
    mgJson({bid: bonus_id, action: 'get-bonus', site_type: siteType}, function(bonus){
        if ((typeof extSessHandler !== 'undefined') && (typeof mpUserId === 'undefined') && (cur_country === 'ES')) {
            gameCloseRedirection("playGameDepositCheckBonus(\'" + bonus.game_id + "\')");
        }
        else {
            playGameDepositCheckBonus(bonus.game_id);
        }
    });
}

function showChatDisabledPopup() {
    var params = {
        file: 'get_chat_offline',
        boxtitle: 'chat.offline.message.title',
        closebtn: 'yes',
        module: 'DBUserHandler'
    };

    var extraOptions = {
        width: isMobile() ? '100%' : 480,
        height: isMobile() ? '100%' : 605
    };

    extBoxAjax('get_html_popup', 'mbox-msg', params, extraOptions);
}

function showFastDepositPopup(psp, extraContent, context){
    var options = {
        module: 'Cashier',
        file:   'fast_deposit',
        psp:    psp
    };

    if(!empty(extraContent)){
        options.extraContent = extraContent;
    }

    if(!empty(context)){
        options.context = context;
    }

    var type = isMobile() ? 'ajax' : 'iframe';

    $depositLimitPopup = mgAjax({action: "check_deposit_limit"}, function (ret) {
        const response = ret;
        if (response != false) {
            options['transaction_error'] = response;
        }
        extBoxAjax('get_raw_html', 'fast-deposit-box', options, undefined, undefined, type, {width: '400px', height: '600px'});
    });
}

function showTrustlyDepositPopup(amount){
    var extraOptions = isMobile() ? {width: '100%'} : {width: '500px'};

    var params = {
        module: 'Cashier',
        file: 'trustly_deposit_popup',
        boxid: 'trustly_deposit_popup',
        boxtitle: 'trustly.deposit.info.title',
        closebtn: 'yes',
        amount: amount
    };
    extBoxAjax('get_html_popup', 'trustly_deposit_popup-box', params, extraOptions);
}

function showTrustlyWithdrawalPopup(){
    var extraOptions = isMobile() ? {width: '100%'} : {width: '432px'};

    var params = {
        module: 'Cashier',
        file: 'trustly_withdrawal_popup',
        boxid: 'trustly_withdrawal_popup',
        boxtitle: 'trustly.withdrawal.info.title',
        closebtn: 'yes'
    };
    extBoxAjax('get_html_popup', 'trustly_withdrawal_popup-box', params, extraOptions);
}

function showPayNPlayLogin(showLogin = true) {
    var extraOptions = isMobile() ? {width: '100vw', height: '100vh', containerClass: 'flex-in-wrapper-popup button-fix--mobile'} : {width: '448px'};
    var boxTitle = showLogin === true ? 'start.playing' : 'paynplay.deposit'
    if (!showLogin) {
        extraOptions.callb = function () {
            $("#paynplay-box .login-no-deposit").remove();
        }
    }

    var params = {
        module: 'PayNPlay',
        file: 'deposit_base_popup',
        boxid: 'base_deposit_popup',
        boxtitle: boxTitle,
        closebtn: 'yes',
    };

    extBoxAjax('get_raw_html', 'paynplay-box', params, extraOptions);

    $("#paynplay-box").addClass("deposit_popup_section");
}

function depositLimitMessage(timespan, quickDeposit = false){
    var width = quickDeposit ? '400px' : '600px'
    var extraOptions = isMobile() ? {width: '100vw', height: '100vh'} : {width: width,baseZIndex: 10000};
    var params = {
        module: 'DBUserHandler',
        file: 'deposit_limit_message',
        timespan: timespan,
        closebtn: 'yes',
        show_header: !isIframe() ? 'yes' : 'no',
    };

    extBoxAjax('get_html_popup', 'deposit_limit_message', params, extraOptions);
}

function showTrustlyDepositErrorPopup(supplier, reason = ''){
    var extraOptions = isMobile() ? {width: '100%'} : {width: '500px'};
    var supplierDisplay = theCashier.getSetting(supplier).display_name ?? 'Card';

    var params = {
        module:   'Cashier',
        file:     'trustly_deposit_error',
        boxid:    'trustly_deposit_error',
        boxtitle: 'trustly.deposit.info.title',
        closebtn: 'yes',
        redirect_on_mobile: 'no',
        supplier: supplierDisplay,
        reason: reason,
    };
    extBoxAjax('get_html_popup', 'trustly_deposit_error-box', params, extraOptions);
}

function showVerifyDocumentPopup(){
    var extraOptions = isMobile() ? {width: '100%'} : {width: '400px'};

    var params = {
        module:   'Cashier',
        file:     'verify_document_popup',
        boxid:    'verify_document_popup',
        boxtitle: 'verify.document.popup.title',
        closebtn: 'no',
    };

    extBoxAjax('get_html_popup', 'verify_document_popup-box', params, extraOptions);
}

function showGeneralInfoPopup(descriptionAlias, reloadPage = false, textReplacements = {}, extraClasses = '') {
    var extraOptions = {
        width: isMobile() ? '100%' : '500px',
        containerClass: extraClasses
    };

    var params = {
        module: 'Cashier',
        file: 'general_info_popup',
        boxid: 'general_info_popup',
        boxtitle: 'Message',
        boxDescription: descriptionAlias,
        textReplacements,
        reloadPage,
        closebtn: 'yes'
    };

    extBoxAjax('get_html_popup', 'general_info_popup-box', params, extraOptions);
}

function mboxDeposit(qUrl, onClose, closeFancy){

    // In case of PNP registration mode
    if (registration_mode === 'paynplay' && ( qUrl === '/mobile/cashier/deposit/' || qUrl === '/cashier/deposit/' ) ) {
        goTo('/');
        return;
    }

    // We close the fast deposit box before we open the normal box.
    mboxClose('fast-deposit-box');

    if(siteType != 'normal'){
        goTo('/' + cur_lang + '/mobile/cashier/deposit/');
        return;
    }

    if ($('#confirm-message-content-popup').length > 0) {
        return;
    }

    mgAjax({action: 'check-over-limits'}, function(ret){
        if(ret !== 'ok') {
            if (ret === 'show-net-deposit-limit-message') {
                var extraOptions = isMobile() ? {width: '100%'} : {width: '422px'};
                var params = {
                    module:   'Licensed',
                    file:     'net_deposit_info_box',
                    boxid:    'net-deposit-info-box',
                    boxtitle: 'net.deposit.limit.info.title',
                };
                extBoxAjax('get_raw_html', 'net-deposit-info-box', params, extraOptions);
            } else if(ret === 'will-exceed-balance-limit') {
                licFuncs.showBalanceLimitPopup({action: 'deposit', amount: 0});
            } else {
                mboxMsg(ret, true);
            }
        } else {
            mboxClose('mbox-msg');

            mgAjax({action: 'deposit-pending'}, function(ret){
                mgAjax({action: "check_deposit_limit"}, function (ret) {
                    const response = ret;
                    if (response != false) {
                        depositLimitMessage(response);
                    }
                });
                $.multibox({
                    url: qUrl,
                    id: "cashier-box",
                    type: 'iframe',
                    width: cashierWidth + 'px',
                    height: cashierHeight + 'px',
                    globalStyle: {overflow: 'hidden'},
                    cls: 'mbox-deposit',
                    overlayOpacity: 0.7,
                    onComplete: function(){
                        var topBar = $("#quick-cashier-top-bar").clone().attr('id', 'quick-cashier-top-bar-top');
                        topBar.append(ret);
                        $("#cashier-box").find(".mbox-deposit-outer").prepend(topBar);
                        topBar.show();
                        if(ret != ' '){
                            $('.cancel-pending-dbox').click(function(){
                                mgAjax({action: 'cancel-pending', id: $(this).attr('id').split('-')[1]}, function(ret){
                                    $('#dbox-cancel-section').hide();
                                    mgAjax({action: "get_pretty_total"}, function(ret){
                                        $('#pending-balance').html(ret);
                                        $('.gpage-balance').hide();
                                    });
                                    ajaxRefreshTopBalances();
                                });
                            });
                        }
                    },
                    onClose: onClose ? onClose : function(){ ajaxRefreshTopBalances(); }
                });
            });
        }

    });


}

var mboxCloseFunc;


function mboxMsg(msg, showOk, onClose, width, showClose, gotoParent, title, text, full, height, contentPadding, extraContainerClass, baseZIndex, imageName = undefined){
    //var html = '<div style="padding: 0px 20px 20px 10px;">' + msg + '</div>';
    if(empty(msg))
        return;
    if(empty(text))
        text='OK';
    if(empty(title))
        title = mboxMsgTitle;
    if(empty(extraContainerClass))
        extraContainerClass = '';

    var titleHtml = '';

    if(!empty(title)){
        var titleHtml = $('<div class="mbox-msg-title-bar">' + title + '</div>');
        contentPadding = empty(contentPadding) ? '20px 20px 20px 20px' : contentPadding;
    } else {
        contentPadding = empty(contentPadding) ? '20px 20px 20px 10px' : contentPadding;
    }

    var html = $('<div class="mbox-msg-container" style="padding:' + contentPadding + ';"></div>');

    if(typeof msg == 'string') {
        if (imageName) {
            var newDesignImage = '<div><img class="login-popup__image" src="/diamondbet/images/' + brand_name + '/' + imageName + '"></div>';
            msg = "<div class='text-container'>" + newDesignImage + msg + "</div>";
        } else {
            msg = "<div class='text-container'>" + msg + "</div>";
        }

        html.html(msg);
    } else {
        html.append(msg.detach());
    }

    if (showOk){
        html.append('<button onclick="mboxClose()" class="mbox-ok-btn btn btn-l btn-default-l">'+text+'</button>');
    }

    var options = {
        width: undefined,
        height: undefined
    };

    if (typeof baseZIndex != 'undefined') {
        options.baseZIndex = baseZIndex;
    }
    if (siteType != 'normal') {
        options.width = '100%';
    } else if(typeof width != 'undefined'){
        // If on desktop we might want to control the width.
        options.width = width + 'px';
    }

    if (typeof height != 'undefined'){
        options.height = height + 'px';
        // If we have explicit height we want to fill it up with the content (iframe)
        html.css({width: '100%', height: '100%'});
    }

    html = html[0].outerHTML;

    if(!empty(titleHtml)){
        html = titleHtml[0].outerHTML + html;
    }

    options.id        = 'mbox-msg';
    options.type      = 'html';
    options.content   = html;
    options.showClose = typeof showClose === 'undefined' ? true : showClose;
    options.containerClass = extraContainerClass;

    if (typeof onClose == 'function'){
        options.onClose = onClose;
    }

    if(gotoParent)
        parent.$.multibox(options);
    else
        $.multibox(options);

    // Wait for all images to load before positioning the message box
    waitForImagesToLoad(options.id).done(function() {
        if (gotoParent) {
            parent.$.multibox('posMiddle', options.id);
        } else {
            $.multibox('posMiddle', options.id);
        }
    });
}

function parentMboxMsg(msg, showOk, onClose, width, showClose, gotoParent, title, baseZIndex) {
    mboxMsg(msg, showOk, onClose, width, showClose, gotoParent, title, undefined, undefined, undefined, undefined, undefined, baseZIndex);
}

function mobileMsgShow(msg){
    var msgArea = $('#msgBox');
    if(msgArea.length == 0){
        $('#mobile-top').after('<div id="msgBox" class="simple-box left margin-ten-left pad-bottom margin-five-bottom" style="margin-bottom: 20px;"></div>');
    }
    $('#msgBox').show().html(msg);
    $("html,body").animate({ scrollTop: 0 }, "slow");
}

function mboxDialog(msg, onCancel, cancelLabel, onOk, okLabel, onClose, width, showClose, extraCancelClass, title, extraAcceptClass, extraContainerClass, imageName = undefined){

    if(typeof onOk === 'function'){
        doOk = onOk;
        onOk = '';
    }

    if(empty(title))
        title = mboxDialogTitle;

    var titleHtml = '';
    var contentPadding = '0px 10px 20px 10px';
    extraCancelClass = empty(extraCancelClass)? "" : extraCancelClass;
    extraAcceptClass = empty(extraAcceptClass)? "" : extraAcceptClass;

    if(!empty(title)){
        var titleHtml = '<div class="mbox-msg-title-bar">' + title + '</div>';
        contentPadding = '20px 20px 20px 20px';
    }

    var curId = S4();

    var imagePopup = imageName ? '<div><img class="login-popup__image" src="/diamondbet/images/' + brand_name + '/' + imageName + '"></div>' : '';

    var html = titleHtml +
        '<div class="mbox-msg-container" style="padding: ' + contentPadding + '">' +
        '<div class="mbox-msg-content">' + imagePopup + msg + '</div>' +
        '<div class="mbox-button-strip flex-center">' +
        '<button id="' + curId + '" onclick="' + onOk + '" class="btn btn-l btn-default-l w-125 ' + extraAcceptClass + '">' + okLabel + '</button>' +
        '<button onclick="' + onCancel + '" class="btn btn-l btn-default-l w-125 ' + extraCancelClass + '">' + cancelLabel + '</button>' +
        '</div>' +
        '</div>';

    if(typeof doOk === 'function'){
        var doOnComplete = function(){
            $('#'+curId).removeAttr('onclick').click(function(res){
                doOk.call();
            });
        };
    }else{
        var doOnComplete = function(){};
    }

    var options = {
        id: "mbox-msg",
        type: 'html',
        content: html,
        showClose: typeof showClose === 'undefined' ? true : showClose,
        onComplete: doOnComplete,
        containerClass: extraContainerClass
    };

    if(siteType != 'normal'){
        options.width = '100%';
        options.height = '100%';
        //mobileMsgShow(html);
        //doOnComplete.call();
    }else{
        if(typeof width != 'undefined')
            options.width = width+'px';
    }

    if(typeof onClose == 'function')
        options.onClose = onClose;

    $.multibox(options);
}

function mboxClose(id, callback){
    id = typeof id == 'undefined' ? 'mbox-msg' : id;
    if($('#' + id).length != 0){
        // Only if we actually have a box.
        $.multibox('close', id, callback);
    } else if(!empty(callback)){
        // We want to execute the callback no matter what.
        callback.call();
    }
}

function okBtn(onClick){
    return $('<br/></br><center><button onclick="'+onClick+'" class="btn btn-l btn-default-l w-125 neg-margin-top-25 margin-ten-bottom">OK</button></center>');
}

//parts = [day, hour, min]
//Should only be called every minute, ie when seconds is 0
function handleCdownParts(curSecs, parts){
    var curDay = parseInt(parts[0]), curHour = parseInt(parts[1]), curMins = parseInt(parts[2]);

    //1d, 0h, 0m
    //0d, 1h, 0m
    //0d, 0h, 1m

    //1d, 1h, 1m
    //1d, 1h, 0m
    //0d, 0h, 0m

    //0d, 1h, 1m

    if(curMins == 0 && curHour != 0){
        curMins = 60;
        curHour--;
        //curHour = Math.max(0, curHour);
    }else if(curMins != 0)
        curMins--;

    if(curHour == 0 && curDay != 0){
        curHour = 24;
        curDay--;
    }

    return [curDay, curHour, curMins];
}

var minuteCdownIntv;
function minuteCdown(){
    clearInterval(minuteCdownIntv);
    if($('span[class*=minute-]').length == 0 && $('.ymd-cdown').length == 0)
        return;
    minuteCdownIntv = setInterval(function(){
        var curSecs = cur_time.getSeconds();

        if(curSecs % 60 == 0){

            if($('span[class*=minute-]').length != 0){
                $('span[class*=minute-]').each(function(i){
                    var el         = $(this);
                    var tick       = el.attr('class') == 'minute-cup' ? 1 : -1;
                    var curContent = el.html();
                    var curMins = parseInt(el.html());
                    if(curMins != 0)
                        el.html(curMins + tick);
                });
            }

            if($('.ymd-cdown').length != 0){
                $('.ymd-cdown').each(function(i){
                    var el         = $(this);
                    var curContent = el.html();
                    var parts      = _.filter(curContent.split(' '), function(part){
                        return !isNaN(parseInt(part));
                    });
                    var curs = handleCdownParts(curSecs, parts);
                    var parts = _.map(curContent.split(' '), function(part){
                        return isNaN(parseInt(part)) ? part : curs.shift();
                    });
                    el.html(parts.join(' '));
                });
            }

        }
        cur_time.setSeconds(curSecs + 1);
    }, 1000);
}

var clockH = 0;
var clockM = 0;
var didClock = false;
var didFullClock = false;

function setClock(cH, cM){
    $('.digital-clock .min').html( cM < 10 ? '0' + cM : cM );
    $('.digital-clock .hour').html(( cH < 10 ? '0' + cH : cH ) + ":");
}

function setFullClock(fcH, fcM, fcS){
    $('.digital-full-clock .min').html( (fcM < 10 ? '0' + fcM : fcM) + ":" );
    $('.digital-full-clock .hour').html(( fcH < 10 ? '0' + fcH : fcH ) + ":");
    $('.digital-full-clock .sec').html(( fcS < 10 ? '0' + fcS : fcS ));
}

function setupFullClock(fcH, fcM, fcS){
    if (didFullClock) {
        return;
    }
    didFullClock = true;
    // If we pass date we use the client side "Date" obj to display the right time (same as setupClock)
    if(fcH === 'client_date') {
        var d = new Date;
        fcH = d.getHours();
        fcM = d.getMinutes();
        fcS = d.getSeconds();
        setFullClock(fcH, fcM, fcS);
    } else {
        fcH = typeof fcH == 'undefined' ? parseInt($('.digital-full-clock .hour').html()) : fcH;
        fcM = typeof fcM == 'undefined' ? parseInt($('.digital-full-clock .min').html()) : fcM;
        fcS = typeof fcS == 'undefined' ? parseInt($('.digital-full-clock .sec').html()) : fcS;
    }

    updateClock(fcH,fcM,fcS,setFullClock)
}

function updateClock(hour, min, sec, callback) {
    setInterval(function() {
        sec++;
        if (sec === 60) {
            sec = 0;
            min++;
            if (min === 60) {
                min = 0;
                hour++;
                if(hour === 24){
                    hour = 0;
                    min = 0;
                    sec = 0;
                }
            }
        }
        callback(hour,min,sec);
    }, 1000);
}

function setupClock(clockH, clockM){
    if(didClock)
        return;
    didClock = true;
    d = new Date;
    clockH = typeof clockH == 'undefined' ? d.getHours() : clockH;
    clockM = typeof clockM == 'undefined' ? d.getMinutes() : clockM;
    setClock(clockH, clockM);
    setInterval(function(){
        clockM++;
        if(clockM == 60){
            clockM = 0;
            clockH++;
            if(clockH == 24){
                clockH = 0;
                clockM = 0;
            }
        }
        //clockH = d.getHours();
        //clockM = d.getMinutes();
        setClock(clockH, clockM);
    }, 1000 * 60);
}

function setPlayStatus(status){
    mgAjax({action: 'set_play_status', "status": status}, function(ret){});
}

function ifNotPlaying(func){
    mgAjax({action: 'get_play_status'}, function(ret){
        if(ret == "false")
            func.call();
        else
            fancyShow(ret);
    });
}

function distributedAccount(uid){
    mgAjax({action: 'distributed-account', uid: uid}, function(ret){
        goTo(ret);
    });
}

/*
function getPlayCheckHtml(){
    mgAjax({action: 'get-play-check-html'}, function(ret){
        if(!empty(ret)){
            mboxMsg(ret);
        }
    });
}
*/

function mboxTranslate(alias){
    extBoxAjax('get_html_popup', 'mbox-msg', {module: 'Localizer', file: 'translate', lang: cur_lang, alias: alias, boxtitle: 'error'});
}

function extBoxAjax(boxAction, id, params, extraOptions, browserTarget, type, iframeOptions){
    if (empty(params)) {
        params = {};
    }

    params.box_action = boxAction;
    params.lang       = cur_lang;
    params.box_id     = id;
    params.mbox_type  = type;

    var url = '/phive/modules/BoxHandler/html/ajaxBoxes.php';

    if(type == 'iframe'){

        // console.log(url + '?' + $.param(params));

        var baseIframeOptions = {
            url: url + '?' + $.param(params),
            id: id,
            type: 'iframe',
            overlayOpacity: 0.7,
            globalStyle: {overflow: 'hidden'},
            width: '500px',
            height: '500px'
        };

        $.multibox(_.extend(baseIframeOptions, iframeOptions));

    } else {
        var baseOptions   = {
            id:        id,
            type:      'ajax',
            url:       url,
            params:    params,
            lang:      cur_lang,
            showClose: false,
        };

        if(!empty(extraOptions)){
            if(!empty(baseOptions.callb)){
                baseOptions.callb = extraOptions.callb;
            }
            var options = _.extend(baseOptions, extraOptions);
        } else {
            var options = baseOptions;
        }

        empty(browserTarget) ? $.multibox(options) : browserTarget.$.multibox(options);
    }


}

function iframeAjaxBox(id, url, alias, extraStyle, target){
    target = typeof target == 'undefined' ? window : target;
    target.extBoxAjax(
        'iframe_with_headline',
        id,
        _.extend({
                headline_alias: alias,
                iframe_src: url
            },
            {style: JSON.stringify(extraStyle)}),
        _.extend({globalStyle: {overflow: 'scroll'}}, extraStyle)
    );
}

function mobileGameBoxAjax(params) {
    var callback_fn = function (ret) {
        $("#vs-games-container").trigger("show-external-popup", ['', ret, 'ext-popup', 'no-header']);
        if (typeof (params.callb) != 'undefined') {
            params.callb(ret);
        }
    }.bind(this);
    params.box_action = 'get_html_popup';
    params.extra_css = 'lic-mbox-wrapper-mobile-game';
    $.post('/phive/modules/BoxHandler/html/ajaxBoxes.php', params, callback_fn);
}

/**
 * Show login box will show different boxes based on context
 *
 * @param context
 * @param {boolean} allow_close_redirection
 * @param {boolean} skip_validation - is used on DK to show the nid box configured for registration context
 * @param country
 * @param lic_params
 * @returns {boolean}
 */
function showLoginBox(context, skip_validation, allow_close_redirection = true, country, lic_params){
    if (typeof skip_validation === 'undefined') {
        skip_validation = false;
    }

    if (typeof lic_params !== 'undefined') {
        licFuncs.prepareExternalVerification(lic_params)
    }

    var extraOptions = {
        width: 500,
        height: 450,
        containerClass: JURISDICTION ? `login-box--${JURISDICTION}` : ''
    };
    var params = {
        context: context
    };

    if((context == 'registration' || context == 'registration_mitid') && !skip_validation && !validateStep1()){
        return false;
    }

    if (context == 'verify') {
        params.context = 'login';
        params.error = 'blocked.needs-nid.html';
        extraOptions.callb = function() {
            licFuncs.showCustomLogin();
        };
    }

    if (context == 'otp') {
        params.context = 'login';
        params.error = 'blocked.needs-nid.html';
        extraOptions.callb = function() {
            licFuncs.showOtpLogin();
        };
    }

    if (context == 'ipverification') {
        params.context = 'login';
        extraOptions.callb = function() {
            licFuncs.showIpVerificationLogin();
        };
    }


    params.module = 'DBUserHandler';
    params.file   = 'get_login';
    params.country= country ? country : $('#country').val();
    params.allow_close_redirection = allow_close_redirection;
    extBoxAjax('get_raw_html', 'login-box', params, extraOptions);
    return false;
}

function mitIdAction(email) {
    licFuncs.verificationByMitID(email, true);
}

function loginCallback(res, error_callback = null) {
    if (res.result.action) {
        window.top[res.result.action.method].apply(null, res.result.action.params);
        $.multibox('posMiddle', 'login-box');
        return;
    }
    if (res.success) {
        // TODO @Paolo see CH40828 for further improvements
        // Removed sendToGoogle as there is no need to get it via ajax, event will be fired on page load.
        if (!empty(res.result.redirect_url)) {
            window.top.location.href = res.result.redirect_url;
        } else {
            jsReloadBase();
        }

    } else {
        if (typeof error_callback == 'function') {
            return error_callback(res);
        }
        if (res.result['goto-custom']) {
            goToVerify(res.result['goto-custom'], res.result['legacy_nid']);
        }
        $("#lic-login-errors").addClass("error").show().html(res.result.msg);
        lic('onLoginError', [res]);
    }
}

function doLogin(){
    showLoader(function(){
        var uname = $("#lic-login-username-field").val();
        var pwd = $("#lic-login-password-field").val();

        if(empty(uname) || empty(pwd)){
            if(empty(pwd)){
                $("#lic-login-password-field").addClass('input-error');
            }
            if(empty(uname)){
                $("#lic-login-username-field").addClass('input-error');
            }
            hideLoader();
            return false;
        }

        var options = {action: 'uname-pwd-login', username: uname, password: pwd, lang: cur_lang};

        if ($("#lic-mbox-login-captcha").css('display') !== 'none') {
            options.login_captcha = $("#login-captcha").val()
        }
        // this will be set on showImportFromBrand based on user answer.
        if (typeof importFromBrand !== 'undefined') {
            options.import_from_brand = importFromBrand;
        }

        mgSecureJson(options, loginCallback);
    }, false);
}

function doOtpLogin() {
    showLoader(function () {
        const otpInput = $("#lic-login-otp-field");
        const captchaInput = $("#login-otp-captcha");
        const isOtpCaptchaEnabled = $("#lic-mbox-login-otp-captcha").is(":visible");

        otpInput.removeClass('input-error');
        captchaInput.removeClass('input-error');

        if (!isOtpCaptchaEnabled && empty(otpInput.val())) {
            otpInput.addClass('input-error');
            hideLoader();
            return false;
        }

        if (isOtpCaptchaEnabled && empty(captchaInput.val())) {
            captchaInput.addClass('input-error');
            hideLoader();
            return false;
        }

        const options = {
            action: 'otp-login',
            otp: otpInput.val(),
            lang: cur_lang,
            username: $("#lic-login-username-field").val(),
            password: $("#lic-login-password-field").val()
        };

        if (isOtpCaptchaEnabled) {
            options.login_captcha = captchaInput.val()
        }

        mgSecureJson(options, loginCallback);
    }, false);
}

function doResetPasswordLogin() {
    const password = $("#new-password-field");
    const passwordConfirmation = $("#new-password-field-confirmation");

    const setError = function(error = '') {
        password.addClass('input-error');
        passwordConfirmation.addClass('input-error');
        if (error) {
            const errorHtml = '<p>' + error + '</p>'
            $('#lic-login-errors').addClass('error').html(errorHtml).show();
        }
        hideLoader();
    };

    const clearError = function() {
        password.removeClass('input-error');
        passwordConfirmation.removeClass('input-error');
        $('#lic-login-errors').hide();
    };

    showLoader(function () {
        clearError();

        if (password.val() !== passwordConfirmation.val()) {
            return setError();
        }

        mgAjax({action: 'new-pwd-on-login', pwd: password.val()}, function(response){
            if (response === 'expired') {
                return jsReloadWithParams();
            }

            if (response !== 'ok') {
                return setError(response);
            }

            mgSecureJson({
                action: 'uname-pwd-login',
                lang: cur_lang,
                username: $("#lic-login-username-field").val(),
                password: password.val()
            }, loginCallback);
        });

    }, false);
}

function doGeoComplyLogin(){
    var uname = $("#lic-login-username-field").val();
    var pwd = $("#lic-login-password-field").val();

    //used on login
    if(!empty(uname) && !empty(pwd)){
        doLogin();
        return;
    }

    //used on registration
    showLoader(function(){
        var options = {};
        options.action = 'login';

        $.post("/phive/modules/GeoComply/endpoint.php", options, function(data){
            mgSecureJson({
                action: 'uname-pwd-login',
                lang: cur_lang,
                username: data['username'],
                password: data['password']
            }, loginCallback);
        }, 'json');
    });
}

function saveAccCommon(action, options, func){
    if(empty(options))
        options = {};

    mboxClose();

    options.lang   = cur_lang;
    options.action = action;

    showLoader(function(){
        $.post("/phive/modules/DBUserHandler/actions.php", options, function(ret){
            // we need to handle "no user" case and redirect to home page when session is expired.
            if(ret === 'no user') {
                gotoLang('/');
            } else {
                ret = JSON.parse(ret);
            }
            func.call(this, ret);
        }, 'text');
    });
}

function forcePopup(force_type, callback){
    var params = {
        module: 'Licensed'
    };
    var close_selector = '';

    if (force_type === 'force_self_assesment_popup') {
        params.file = 'self_assessment_test_popup';
        params.boxtitle = 'gamtest.box.headline';
        params.closebtn = 'yes';
        close_selector = '.lic-mbox-close-box';
    } else if (force_type === 'force_deposit_limit') {
        params.file = 'deposit_limits_popup'; // TODO see if we can change this into dep_lim_info_box and remove deposit_limits_popup
        params.boxtitle = 'rg.info.limits.set.title';
        params.closebtn = 'no';
        close_selector = '.positive-action-btn';
    }

    params.callb = function () {
        $(document).on('click', close_selector, function(){
            setTimeout(callback, 500);
        });
    };

    extBoxAjax('get_html_popup', 'mbox-msg', params);
}

/**
 * this function will check for pending events for the user
 * and add them to google dataLayer to fired them.
 * (Ex. after a completed deposit)
 * https://developer.mozilla.org/en-US/docs/Web/API/Window/parent
 * window.parent: If a window does not have a parent, its parent property is a reference to itself.
 */
function sendToGoogle(callback) {
    if(callback === undefined) {
        callback = function(){};
    }
    $.post('/diamondbet/html/external_tracking_body.php', {'action': 'get-pending-events'}, function (data) {
        if(typeof dataLayer == 'undefined'){
            // If we don't have a dataLayer we execute the callback immediately and return before we hit a
            // fatal error.
            callback();
            return null;
        }

        if((data || []).length) {
            var res = JSON.parse(data);
            res.forEach(function(value) {
                window.parent.google_datalayer(value);
            });
        }
        callback();
    });
}

/**
 * Will create a popup with 2 buttons (Yes/No), filling the content with the requested file.
 * Callbacks can be defined as functions, by default they will close the popup.
 *
 * @param action
 * @param yesCallback
 * @param noCallback
 * @param box_id
 * @param extra {boxtitle: 'localized.strings.alias', closebtn: 'yes|no', btntype: 'yes_no|ok|none'} TODO check if other params are supported /Paolo
 */
function rgDialogPopup(action, yesCallback, noCallback, box_id = 'mbox-msg', extra = {}) {
    var wrapperCallback = function(callback, action, answer) {
        $.multibox('close',box_id);
        saveAccCommon(action,{answer: answer},function(res) {
            // console.log('Logged User clicked '+answer+' for action '+action);
            if(typeof callback == 'function') {
                setTimeout(function(){
                    callback(res);
                }, 1000);
            } else {
                // console.log('no callback specified we just close the popup');
            }
        });
    };

    yesCallback = wrapperCallback.bind(this, yesCallback, action, 'yes');
    noCallback = wrapperCallback.bind(this, noCallback, action, 'no');

    // security check if empty param is passed (Ex. null)
    if(empty(box_id)) {
        box_id = 'mbox-msg';
    }

    var params = {
        action: action,
        btntype: ['yes_no', 'ok', 'none'].includes(extra.btntype) ? extra.btntype : 'yes_no',
        closebtn: extra.closebtn === 'no' ? 'no' : 'yes',
        // onClose callback ??? /TODO verify what this callb does.. /Paolo
        // callb: function() {
        // }
    }
    if(!empty(extra.boxtitle)) {
        params.boxtitle = extra.boxtitle;
    }

    extBoxAjax('get_html_rg_popup_dialog', box_id, params);

    $(document)
        .off('click')
        .on('click', '#dialog__button--yes, #dialog__button--ok', yesCallback)
        .on('click', '#dialog__button--no', noCallback);
}


function rgSimplePopup(action, closeCallback, box_id = 'mbox-msg', extra = {}) {
    var wrapperCallback = function(callback) {
        $.multibox('close', box_id);
        callback();
    };

    closeCallback = wrapperCallback.bind(this, closeCallback);

    var params = {
        action: action,
        // callb: closeCallback // TODO check if this work
        closebtn: extra.closebtn === 'no' ? 'no' : 'yes',
        btntype: ['yes_no', 'ok', 'none'].includes(extra.btntype) ? extra.btntype : 'ok',
        boxtype: extra.boxtype,
    }

    if(!empty(extra.boxtitle)) {
        params.boxtitle = extra.boxtitle;
    }

    // security check if empty param is passed (Ex. null)
    if(empty(box_id)) {
        box_id = 'mbox-msg';
    }

    extBoxAjax('get_html_rg_popup_dialog', box_id, params);

    $(document)
        .off('click')
        .on('click', '#dialog__button--ok', closeCallback);
}

function setResettableLimit(type, skipDialog = false){
    var rgLimits = [];
    var timeSpans = window[type + '_reSpans'];
    for (var i = 0; i < timeSpans.length; i++) {
        tspan = timeSpans[i];

        var baseSelector = '#' + type + '-' + tspan;
        var curInput     = $(baseSelector);
        var curRemaining = $(baseSelector + '-' + 'remaining').val();
        var curLimit     = getMaxIntValue(curInput.val());

        // Limits such as `net_deposit` has an optional time-spans (see config)
        if((curInput.length && empty(curInput.val())) || curLimit >  Number.MAX_SAFE_INTEGER){
            curInput.addClass('input-error');
            return false;
        } else {
            curInput.removeClass('input-error');
        }

        rgLimits.push({limit: curLimit, type: type, time_span: tspan});
    }

    var data = {type: type, limits: rgLimits};
    // To support the param on all type of limits we need to inject it into pData.
    if($('#cross-brand-limit-'+type).length) {
        data['cross-brand-limit-'+type] = $('#cross-brand-limit-'+type).prop('checked');
    }

    var pData = JSON.stringify(data);

    if(skipDialog) {
        saveAccCommon('save_resettable', {data: pData}, function(res){mboxMsg(res.msg, true);});
    } else {
        accSaveRgLimit('save_resettable', pData);
    }
}


function accSaveRgLimit(action, pData){
    accSave(action, {data: pData}, function(res){
        var parsedData = JSON.parse(pData);
        var type = parsedData.type;
        var typeClass = `rg-${type}-limit`;
        if (res.msg && typeof res.msg.action !== "undefined") {
            lic(res.msg.action);
            return;
        }

        if(!empty(res.msg)){

            if (res.msg === 'show-customer-net-deposit-limit-message') {
                var extraOptions = isMobile() ? {width: '100%'} : {width: '422px'};
                var params = {
                    module:   'Licensed',
                    file:     'customer_net_deposit_info_box',
                    boxid:    'customer-net-deposit-info-box',
                    boxtitle: 'customer.net.deposit.limit.info.title'
                };
                extBoxAjax('get_raw_html', 'customer-net-deposit-info-box', params, extraOptions);
                return;
            }

            // Some kind of error etc.
            // NOTE: we also get here on success, when we have a success message, so we need to reload the page
            if(window.location !== window.parent.location) {
                // We are inside a iframe, but we need to reload the parent window
                mboxMsg(res.msg, true, function(){ parentGoTo(window.parent.location); });
            } else {
                var rglImage = showNewDesignImage ? 'max-bet-limit-reached.png' : '';
                mboxMsg(res.msg, true, function(){ jsReloadBase(); }, ...Array(8), typeClass, undefined, rglImage);
            }

        } else {
            // Success so we reload the page to display the new limits etc.
            jsReloadBase();
        }
    });
}


function accSave(action, options, func) {
    if (action != 'reality-check-interval') {
        accSaveDialog(action, options, func);
    }
}

function setSingleLimit(type, skipDialog = false){
    var tspan = type == 'betmax' ? $("#rg-duration-form input[type='radio']:checked").val() : 'na';  // this is missing
    var baseSelector = '#' + type;
    var curInput     = $(baseSelector);
    var curLimit     = getMaxIntValue(curInput.val());

    // We're looking at adding a non-existing limit, in that case all time spans need to be set.
    if(empty(curLimit) || curLimit >  Number.MAX_SAFE_INTEGER){
        curInput.addClass('input-error');
        return false;
    } else {
        curInput.removeClass('input-error');
    }

    var pData = JSON.stringify({limit: curLimit, type: type, time_span: tspan});
    var action = 'save_'+type;
    if(skipDialog) {
        saveAccCommon(action, {data: pData}, function(res){mboxMsg(res.msg, true);});
    } else {
        accSaveRgLimit(action, pData);
    }
}


jQuery(document).ready(function(){
    setupLogin();

    if (typeof registration_mode !== 'undefined') {
        if(registration_mode === 'paynplay') {
            $('#vs-game-info-strip').off('click', '#vs-button__deposits');
            $('#vs-button__deposits').click(function (){
                showPayNPlayPopupOnDeposit();
            });

        }
    }

});

goToUniversalLink = function(url) {
    window.location.href= url;
}

/**
 * We check if the number overflow the max value
 * by default we check "x100" cause we store cents
 *
 * @param value
 * @param multiplier
 * @return {number}
 */
function getMaxIntValue(value, multiplier = 100) {
    value = value * multiplier;
    if(value > Number.MAX_SAFE_INTEGER) {
        value = Number.MAX_SAFE_INTEGER;
    }
    return value / multiplier;
}

/**
 * Extract number from value.
 * Examples:
 *  1. 'test123test' -> '123'
 *  2. '123,456,789' -> '123.456789'
 *  3. '123,' -> '123.'
 *
 * @param {string} value
 * @param {boolean} allowNegative
 * @return {string}
 */
function getClearedNumber(value, allowNegative = false) {
    // replace commas with dots, remove multiple leading zeros
    let clearedNumber = value.toString()
        .replace(/,/g, '.')
        .replace(/^0+/, '0');

    const isNegative = clearedNumber.startsWith('-');

    // remove non-number characters
    clearedNumber = clearedNumber.replace(/[^\d.]/g, '');

    const splitted = clearedNumber.split('.');
    const isDecimal = splitted.length > 1;

    // if we are dealing with decimal number - leave only first dot
    clearedNumber = splitted.shift() + (isDecimal ? '.' + splitted.join('') : '');

    // if number starts with dot - add leading zero
    if (clearedNumber.startsWith('.')) {
        clearedNumber = '0' + clearedNumber;
    }

    if (allowNegative && isNegative) {
        clearedNumber = '-' + clearedNumber;
    }

    if (clearedNumber === '-') {
        return '0';
    }

    return clearedNumber;
}

function gameMsgSetup(wsUrl, extraArgs){
    if(lgaRealityCheck == true){
        if(!hasWs()){
            lgaFunc.call();
            lgaLimitsId = setInterval(lgaFunc, 10000);
        }else{
            doWs(wsUrl, function(e) {
                var res = JSON.parse(e.data);
                if($("#mbox-popup").length > 0)
                    return;
                if(res.msg.search('___') != -1){
                    var func = res.msg.split('___')[0];
                    var args = res.msg.split('___')[1];
                    if(!empty(args)){
                        args = args.split(',');
                    }
                    window[func].apply(null, [].concat(empty(args) ? [] : args, !empty(extraArgs) && extraArgs[func] ? extraArgs[func] : []));
                } else {
                    var goHome = res.gohome == 'yes' ? true : false;
                    if (typeof showPopup == 'undefined') {
                        onCloseFn = function () {
                            if (goHome) {
                                gotoLang('/');
                            } else if(res.source == 'lgatime.reached.html'){
                                goTo('/?signout=true');
                            } else {
                                mboxClose();
                            }
                        };
                        var gamePlayImage = showNewDesignImage ? 'max-bet-limit-reached.png' : '';
                        mboxMsg(res.msg, true, onCloseFn, ...Array(8), 'game-msg-container', undefined, gamePlayImage);
                    } else {
                        showPopup(res.msg, goHome, res.game_ref, res.tournament, res.source, res.eid);
                    }
                }
            });
        }
    }
}

var popupsQueue = [];

function addToPopupsQueue(fun) {
    popupsQueue.push(fun);
}

function execNextPopup() {
    if (popupsQueue.length > 0) {
        (popupsQueue.shift())();
    }
}

/**
 * Install list of scripts
 *
 * @param scripts
 */
function reloadLicFuncs(scripts) {
    window.licFuncs = {};
    scripts.forEach(function (script) {
        if (script !== null) {
            $("head").append('<script src="' + script + '" ></script>');
        }
    });
}

/**
 * Wrapper for view more button AJAX logic, it will grab the HTML from the backend and append it after the existing data.
 *
 * @param appendToSelector - element where we want to append the HTML to
 * @param boxClass - Class that will contain the function
 * @param boxFunction - function that will generate the HTML
 * @param amountToLoad - by default we load the next 3 items, if 0 or false values are passed we fallback to default
 */
function viewMore(appendToSelector, boxClass, boxFunction, amountToLoad = 3) {
    var buttonSelector = '#view-more';
    var offset = $(buttonSelector).data("offset");
    var new_offset = offset + amountToLoad;

    params = {
        func: boxFunction,
        offset: offset
    };

    ajaxGetBoxHtml(params, cur_lang, boxClass, function(result) {
        // Hide the view.more button when we dont have more records
        if(result === '') {
            $(buttonSelector).hide();
        }

        // Add the found records to the table
        $(appendToSelector).after(result);

        // update data.offset
        $(buttonSelector).data("offset", new_offset);
    });
}

/**
 * This method is used to save the Javascript logs into the Logger file on the server side (phive) via post call.
 *
 * @param logger (string) - logger
 * @param type (string) - this is the type of the logs e.g. warning, debug
 * @param message (string) - this is the message for the specific log (identifier)
 * @param context (object) default -> {} - this dictionary contains the extra data that we need to log
 * @param context_obfuscation (object) default -> {} - this dictionary contains th obfuscation options/settings
 * @param enable_logging (boolean) default -> true - this flag is used to enable/disable saving the current log
 *
 * @return string|boolean
 *
 * EXAMPLE:
 * saveFELogs('worldpay', 'debug', 'postTransaction',
 *     {
 *         'action': action,
 *         'rId': rId,
 *         'amount': amount,
 *         'options': options,
 *         'endpoint': endpoint
 *     },
 *     {
 *         'obfuscation': true, // by default obfuscation is disabled. Inorder to enable you need to set true and vice versa
 *         'obfuscating_keys' : ['options', 'endpoint', 'options.repeat_id'] // by default every element in the context array will be obfuscate. Inorder to ofbuscate specific values update the array. This array could be empty or not present. For more information about the obfuscation please check the obfuscateTrait in phive.
 *     },
 *     true
 * );
 */
function saveFELogs(logger, type, message, context = {}, context_obfuscation = {}, enable_logging = true)
{
    if (!enable_logging) return false;

    var param_validation_errors = [];
    var logger_levels = ['info','notice', 'warning', 'error', 'critical', 'alert', 'emergency', 'debug'];
    if (!logger) param_validation_errors.push("Logger should not be empty!");
    if (!type || !logger_levels.includes(type)) param_validation_errors.push("type should not be empty and type should be one from the following: " + logger_levels.toString());
    if (!message) param_validation_errors.push("message should not be empty!");

    if (param_validation_errors.length != 0) {
        console.log('Params are wrong: ', param_validation_errors);
        return false;
    }

    var data = {
        'logger'  : logger,
        'type'    : type,
        'message' : message,
        'context' : context,
    };

    console.log(JSON.stringify(data));
    data.context_obfuscation = context_obfuscation;

    $.post(mgUrl, {
        'action'  : 'js_logs',
        'data': data
    }, function (response) {
        console.log('Response (saveFELogs): ', response);
    }, "json").fail(function () {
        console.log('FE logs not saved, something went wrong');
    });

    return true;
}

function showRGInfoPopup (rgType = '', attrs = {}, onSuccessCallback = null, responseData) {
    var extraOptions = isMobile()
        ? {
            width: '100vw',
            height: '100vh',
            containerClass: 'flex-in-wrapper-popup pnp-error-popup-wrapper button-fix--mobile',
        }
        : {width: '474px', containerClass: 'flex-in-wrapper-popup pnp-error-popup-wrapper'};

    var params = {
        module: 'Licensed',
        file: 'rg_info_popup',
        boxid: 'rg_info_popup',
        closebtn: 'no',
        rgType: rgType,
        boxtitle: 'rg.info.popup.title',
        data: responseData,
        ...attrs
    };

    extBoxAjax('get_html_popup', 'rg_info_popup', params, extraOptions);

    if (onSuccessCallback instanceof Function) {
        onSuccessCallback(rgType);
    }
}

function rgPopupShown(trigger_name){
    $.post(
        "/phive/modules/Micro/ajax.php",
        {action: 'rg-popup-shown', trigger: trigger_name},
        function(ret){}
    );
}

function showRgPopup()
{
    const url_params = new URLSearchParams(window.location.search);

    if(url_params.has('display_mode') && url_params.has('auth_token')) {
        return;
    }

    function ping(delay){
        $.post(
            "/phive/modules/Micro/ajax.php",
            {action: 'check-rg-popups'},
            function(ret){
                try {
                    var ret = JSON.parse(ret);
                } catch (e) {
                    if (e instanceof SyntaxError) {
                        console.log("check-rg-popups request returned unexpected response.");
                    }
                }

                if (ret.success) {
                    showRGInfoPopup(ret.trigger, {}, rgPopupShown, ret.data);
                } else if (ret.error == 'user_logged_out') {
                    return;
                }
                setTimeout(ping, delay, delay);
            });
    }
    ping(60000);
}

function showIntensiveGamblerPopup(boxPopupID)
{
    function ping(boxPopupID, delay){
        $.post(
            "/phive/modules/Micro/ajax.php",
            {action: 'check-intensive-gambler-popup'},
            function(ret){
                try {
                    var ret = JSON.parse(ret);
                } catch (e) {
                    if (e instanceof SyntaxError) {
                        console.log("check-intensive-gambler-popup request returned unexpected response.");
                    }
                }

                if (ret.success) {
                    var extraOptions = isMobile() ? {} : {width: 800};
                    extBoxAjax('get_login_rg_info', boxPopupID, {
                        rg_login_info: true,
                        intensive_gambler: true
                    }, extraOptions);
                } else if (ret.error == 'user_logged_out') {
                    return;
                }
                setTimeout(ping, delay, boxPopupID, delay);
            });
    }
    ping(boxPopupID, 60000);
}

function animateJackpotBadge(badgeClassName) {
    const badge = $(`.${badgeClassName}`);
    const badgeSpan = badge.find('span');
    badge.show();

    const number = badgeSpan.length ? badgeSpan[0] : badge[0];

    (new CountUpAnimation()).animateCountUp(number);
}

/**
 * Displays the first deposit success popup.
 * This function retrieves the HTML content for the welcome offer activation popup,
 * and displays it in a modal window.
 * @function firstDepositSuccessPopup
 * @returns {void}
 */
function firstDepositSuccessPopup() {
    var params = {
        file: 'welcome_offer_activation_popup',
        closebtn: 'yes',
        boxid:'deposit-notification-popup',
        boxtitle: 'deposit',
        module: 'Licensed',
    };

    var extraOptions = isMobile()
        ? {
            width: '100vw',
            height: '100vh',
            containerClass: 'flex-in-wrapper-popup button-fix--mobile',
        }
        : {width: '450px', containerClass: 'flex-in-wrapper-popup'};

    extBoxAjax('get_html_popup', 'deposit-notification-popup', params, extraOptions, top);

}


function initAccountVerificationForm (boxId, shouldShowPrivacyConfirmation) {
    const form = $('#account-verification-form');
    form.validate({
        rules: {
            code: 'required number',
        },
        highlight: function (element) {
            $(element).addClass('error');
            const messageId = '#' + $(element).attr('id') + '-message';
            $(messageId).show();
        },
        unhighlight: function (element) {
            $(element).removeClass('error');
            const messageId = '#' + $(element).attr('id') + '-message';
            $(messageId).hide();
        },
        errorPlacement: function () {
        },
        submitHandler: function (form) {
            const email_code = $('#email_code_validation', form).val();
            mgAjax({action: 'validate-code', email_code}, function (data) {
                data = JSON.parse(data);
                if (!data.success) {
                    for (var field in data.messages) {
                        if (data.messages.hasOwnProperty(field)) {
                            var $target = $('#' + field);
                            if ($target.length === 0) {
                                $target = $("#general_error");
                                $target.text(data.messages[field]).show()
                            }
                            addClassError($('#email_code_validation'));
                            $('.field-' + field).find(".account-validation-message").text(data.messages[field]).show();
                        }
                    }
                    return;
                }

                const box_id = registration_mode === 'bankid' ? 'bankid-account-verification-popup' : 'account-verification_box';
                mboxClose(box_id);

                if (shouldShowPrivacyConfirmation) {
                    showPrivacyConfirmBox(isMobile() ? 1 : 0, 'registration');
                    $('#privacy-confirmation-notification .multibox-close').on('click', function () {
                        showPermanentLoader(undefined, null);
                        if (registration_mode !== 'bankid') {
                            handleRegistrationStep1();
                        } else {
                            if (isMobile()) {
                                Registration.submitStep2(top.registration1.Registration.getFormStep2());
                            } else {
                                top.registration1.goTo('/' + cur_lang + '/registration-step-2/', '_self', false);
                            }
                        }
                    });
                }
            })
        },
    });
}
