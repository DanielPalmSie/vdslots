
var theCashier = {};

const banks = [
    // TODO: find better way to determine if supplier is type of bank
    'trustly',
    // GB
    'barclays',
    'halifax',
    'lloydsbank',
    'santander',
    'natwest',
    'hsbcuk',
    'nationwide',
    'monzo',
    'tsbbank',
    'revolut',
    'royalbankofscotland',
    'bankofscotland',
    // SE
    'lansforsakringar',
    'seb',
    'handelsbanken',
    'icabanken',
    'nordea',
    'skandia',
    'swedbank',
    'sparbanken',
    'danske-bank',
    // FI
    'aktia',
    'alandsbanken',
    'danske-bank',
    'handelsbanken',
    'saastopankki',
    'nordea',
    'op-pohjola',
    'omasp',
    'poppankki',
    's-pankki',
];

function shouldShowTrustlyErrorPopup(supplier, action, error) {
    if (action !== 'fail') {
        return false;
    }

    if (banks.includes(supplier)) {
        return false;
    }

    // TODO: (sc-287783) It will work only for en for now! We should consider key, not translated values here,
    //  but there is a problem that we are getting translated values from the backend. It need to be changed!
    if ([
        "<p>You have deposited more money than you are allowed to during a given period.</p>\n<p>Please check back in a few days.</p>" , //deposits.over.limit.html
        "The amount exceeds the limit", //err.toomuch
        "The amount is too small.", //err.toolittle
        "Error - Can't be empty", //err.empty
        "Incorrect amount." //cashier.error.amount
    ].includes(error)) {
        return false;
    }

    if (cashier.returnInfo.fallback_deposit === 'trustly' && supplier && supplier !== 'trustly') {
        return true;
    }

    return false;
}

var cashier = {
    handleEnd: function(isSuccessful){
        var webviewParams = cashier.getWebviewParams();
        if(!isMobile()){
            // Desktop
            var getArgs = (isSuccessful ? '?end=true' : '?end=true&status=failed') + webviewParams;
            if(cashier.isFastDeposit){
                // We show normal deposit box in order to display alternative deposit options and the error message / popup.
                parent.mboxDeposit(llink('/cashier/deposit/' + getArgs ));
            } else {
                jsReload(getArgs);
            }
        } else {
            if (cashier.isMobileApp) {
                return sendToFlutter({
                    type: 'close',
                    data: {},
                    trigger_id: 'deposit',
                    debug_id: 'cashier.handleEnd'
                });
            }
            return gotoLang('/mobile/cashier/deposit/?end=true' + (isSuccessful ? '' : '&status=failed'));
        }
    },
    handleSelectAccountResponse: function () {
        const params = new URLSearchParams(window.location.search);
        const { action, success, redirected } = Object.fromEntries(params);

        if (redirected !== 'true' && action === 'select_account' && success === 'true') {
            params.set('redirected', 'true');
            window.history.replaceState({}, '', `${window.location.pathname}?${params}`);

            mboxClose('mbox-msg', function() {
                showGeneralInfoPopup('select.account.success.description', true);
            });

            return true;
        }

        if (action === 'select_account' && success === 'false') {
            mboxClose('mbox-msg', function() {
                showGeneralInfoPopup('select.account.fail.description');
            });
            return true;
        }

        return false;
    },
    getTargetFrame: function(){
        // This is used when we always want to do stuff in the parent frame in case we're in an iframe, otherwise not.
        return isIframe() ? window.parent : window;
    },
    onClickHooks: {},
    infoBonusUrl: "/phive/modules/Bonuses/ajax/infobonus.php",
    doIframe: function(psp, res, network){
        if(!empty(res.result) && !empty(res.result.iframe) && res.result.iframe == 'no'){
            return false;
        }

        var setting  = cashier.getSetting(psp);

        if(!setting.hasOwnProperty('iframe') &&!empty(network)) {
            // We don't have an iframe flag explicitly set so we check the network setting instead.
            if(setting.type == 'ccard'){
                if(cashier.ccConfigs[network].hasOwnProperty('iframe')){
                    return cashier.ccConfigs[network].iframe;
                }
            } else {
                // TODO
            }
        } else {
            if (setting.hasOwnProperty('iframe_overrides') && setting.iframe_overrides[cashier.userData.country]) {
                return setting.iframe_overrides[cashier.userData.country];
            }

            if(setting['iframe'] === false){
                return false;
            }
        }

        if(!isIframe()){
            // If the context is actully not in an iframe we need to return false here as we can't continue
            // with logic that assumes an iframe if we don't have one.
            return false;
        }
        return true;
    },
    showBonusCode: function(){
        $('.bonus-code-field').show();
    },
    /**
     * For showing messages and results
     *
     * This function is used both on desktop and mobile to display results and errors
     * in popup boxes.
     *
     * @param string msg The message to display.
     *
     * @return void
     */
    showPaymentMsg: function (msg, onClose, fireGoogleEvent = false, isSuccess = false) {
        // We close potentially existing message boxes.
        var newParent = parent;
        mboxClose(undefined, function () {
            var $iframeElement = $(parent.document).find('#mbox-iframe-cashier-box');

            var statusImage = '';
            if (!is_old_design) {
                statusImage = isSuccess ? 'deposit_success.svg' : 'failed.png';
            }

            function showMessage() {
                top.mboxMsg(
                    msg,
                    true,
                    onClose,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    null,
                    'show-payment-msg',
                    null,
                    statusImage);
            }

            fireGoogleEvent ? sendToGoogle(showMessage) : showMessage();

            newParent.$('.cashier2-close').show();

            function handleSuccessClick() {
                closeQuickDeposit();

                const currentUrl = top.window.location.href;
                const url = new URL(currentUrl);
                const newUrl = url.origin + url.pathname;

                top.window.history.replaceState({}, '', newUrl);

                if (isMobile()) {
                    gotoLang('/mobile/');
                }
            }

            function handleFailureClick() {
                if ($iframeElement.length) {
                    $iframeElement.removeClass('blur-iframe');
                }
            }

            $(parent.document).off('click', '.mbox-ok-btn');

            if (isSuccess) {
                $(parent.document).on('click', '.mbox-ok-btn, .multibox-close', handleSuccessClick);
            } else {
                $(parent.document).on('click', '.mbox-ok-btn', handleFailureClick);
            }

            if ($iframeElement.length) {
                $iframeElement.addClass('blur-iframe');
            }

            // Needed to resize popup with success/fail message properly inside mobile game deposit page.
            if (isIframe()) {
                window.postMessage({ width: window.outerWidth }, '*');
            }
        });
    },
    returnInfo: {isSuccess: false, is_return: false, msg: '', isFirstDeposit: false},
    returnPopup: function(onClose){
        if(cashier.handleSelectAccountResponse()) {
            return;
        }

        if (!cashier.returnInfo.is_return) {
            if (!hasEndParameter()) {
                return;
            }
        }

        var urlParams = new URLSearchParams(window.location.search);
        var supplier = urlParams.get('supplier');

        if (cashier.isMobileApp) {
            sendToFlutter({
                type: 'html',
                success: cashier.returnInfo.isSuccess,
                data: cashier.returnInfo.msg,
                trigger_id: 'returnPopup',
                debug_id: 'cashier.returnPopUp'
            });
        } else {
            // Check if it's the user's first deposit. If so, display a success popup and return.
            if (cashier.returnInfo.isFirstDeposit) {
                closeQuickDeposit();
                firstDepositSuccessPopup();
                return;
            }

            theCashier.showPaymentMsg(cashier.returnInfo.msg, onClose, false, cashier.returnInfo.isSuccess);
        }

        if (shouldShowTrustlyErrorPopup(supplier, urlParams.get('action'))) {
            if (cashier.isMobileApp) {
                sendToFlutter({
                    type: 'popup',
                    data: {
                        header: 'trustly.deposit.info.title',
                        body: ['trustly.deposit.problem', 'trustly.deposit.description'],
                        actions: ['trustly.deposit.button', 'try.again']
                    },
                    trigger_id: 'showTrustlyDepositErrorPopup',
                    debug_id: 'cashier.showTrustlyDepositErrorPopup.error',
                });
            } else {
                if (isMobile()) {
                    showTrustlyDepositErrorPopup(supplier);
                } else {
                    addToPopupsQueue(function ()  {
                        showTrustlyDepositErrorPopup(supplier);
                    });
                }
            }
        }

        if (cashier.isMobileApp) {
            sendToFlutter({
                type: 'close',
                data: {},
                trigger_id: 'returnPopup',
                debug_id: 'cashier.returnPopUp'
            });
        }
    },
    /**
     * Getting psp override by way of the psp_overrides config.
     *
     * Currently doing mc -> ccard for deposits.
     *
     * @param string psp The PSP to work with, eg mc.
     *
     * @return string The override name, eg ccard.
     */
    getOverridePspName: function(psp){
        psp = empty(psp) ? cashier.currentPsp : psp;
        return empty(this.overrideConfig[psp]) ? psp : this.overrideConfig[psp];
    },
    /**
     * Helper to get config setting(s)
     *
     * @param string psp The PSP, this param is obligatory.
     * @param string action Optional action.
     * @param string mainSetting Optional extra key to drill even deeper.
     * @param strinb subSetting Optional extra key to drill even deeper.
     *
     * @return object The config settings.
     */
    getSetting: function(psp, action, mainSetting, subSetting){

        var config = cashier.psps[psp];

        // PSP doesn't exist in current context (deposit or withdrawal) so we just return false.
        if(empty(config)){
            return false;
        }

        // We don't have an action so we just return the whole config.
        if(empty(action)){
            return config;
        }

        // PSP isn't configured for context in question so we just return false.
        if(!empty(action) && empty(config[action])){
            return false;
        }

        // Further arguments do not exist so we return the whole PSP config for this context.
        if(empty(mainSetting)){
            return config[action];
        }

        // We want to drill down further but the setting does not exist (eg deposit -> included countries) so we return false.
        if(!empty(mainSetting) && empty(config[action][mainSetting])){
            return false;
        }

        // We have a main setting but no subsetting which means the caller wants the main setting so we return it.
        if(empty(subSetting)){
            return config[action][mainSetting];
        }

        // We have a sub setting but no config for it so we return false.
        if(!empty(subSetting) && empty(config[action][mainSetting][subSetting])){
            return false;
        }

        // We have everything so we return the sub setting.
        return config[action][mainSetting][subSetting];
    },
    getNetwork: function(psp, action){
        var setting = cashier.getSetting(psp);
        if(empty(setting)){
            return psp;
        }
        var network = setting['network'];
        if(!empty(network)){
            return network;
        }

        var viaConfig = cashier.getSetting(psp, 'via');
        // The whole via section is empty or inactive so we just assume a standalone / direct integration and return the psp name
        if(empty(viaConfig) || empty(viaConfig.active)){
            return psp;
        }

        // Not an array so it's the network.
        if(typeof viaConfig.network == 'string'){
            return viaConfig.network;
        }

        // An array so we try to get the routing for the country in question and if not found rest of the world.
        network = viaConfig.network[cashier.userData.country];
        return empty(network) ? viaConfig.network['ROW'] : network;
    },
    getPspOverride: function(psp){
        // Currently does not support withdrawal action, only deposit.
        var setting = cashier.getSetting(psp);
        if(empty(setting)){
            return psp;
        }
        var override = setting['logo_for'];
        if(!empty(override)){
            return override;
        }
        return psp;
    },
    getEndpoint: function(psp){
        var endpoint =  empty(theCashier.endpoints[psp]) ? theCashier.endpoints['default'] : theCashier.endpoints[psp];
        var webviewParams = cashier.getWebviewParams();

        return "/phive/modules/Cashier/html/" + endpoint + ".php?lang=" + cur_lang + webviewParams;
    },
    getWebviewParams: function() {
        const urlParams = new URLSearchParams(window.location.search);
        const displayMode = urlParams.get('display_mode') || '';

        return displayMode ? `&display_mode=${displayMode}` : '';
    },
    ccGetPubKey: function(psp){
        if(!empty(cashier.ccConfigs[psp]['pub_key_overrides'])){
            var conf = _.find(cashier.ccConfigs[psp]['pub_key_overrides'], function(conf){
                return _.contains(conf.countries, cashier.userData.country);
            });

            if(!empty(conf)){
                return conf.key;
            }
        }
        return cashier.ccConfigs[psp]['pub_key'];
    }
};

cashier.withdraw = {
    endpoints: {
        ccard:      'deposit_start',
        applepay:   'deposit_start',
        default:    'ebank_start'
    },
    addCheckBonusProfit: function(){
        $.post(cashier.infoBonusUrl, {"action": "bonus_profit", "transaction_type" : 'withdraw', lang: cur_lang }, function(data){
            if (data.status == 'fail' && withdrawal_forfeit_brands.includes(brand_name)) {
                theCashier.showPaymentMsg(data.html);
            }
        }, 'json');
    },
    /**
     * The basic initialization function for withdrawals.
     *
     * The main thing here is setting up all forms (since we have all the PSPs at once in the DOM on the withdraw pages),
     * we loop the forms and add click event handlers to all their withdraw buttons.
     *
     * @return void
     */
    init: function() {
        $.validator.addMethod(
            "regex",
            function(value, element, regexp) {
                if (regexp.constructor != RegExp) {
                    regexp = new RegExp(regexp);
                } else if (regexp.global) {
                    regexp.lastIndex = 0;
                }

                return this.optional(element) || regexp.test(value);
            },
            "Please check your input."
        );

        cashier.withdraw.addCheckBonusProfit();
        $('.withdrawForm').each(function() {
            var wf = $(this);

            var validationSettings = wf.data('validation') || {};
            validationSettings.errorPlacement = function(error, element) {
                if (element.is('select') && element.next('.select2-container').length) {
                    error.insertAfter(element.next('.select2-container'));
                } else {
                    error.insertAfter(element);
                }
            };

            wf.validate(validationSettings);

            function handleSubmit(ev) {
                ev.preventDefault();

                if (!wf.valid()) {
                    return;
                }

                // Check if custom action is defined on the form
                const customAction = wf.data('custom-action');

                if (customAction && typeof cashier.withdraw[customAction] === 'function') {
                    cashier.withdraw[customAction](wf);
                } else {
                    theCashier.postTransaction(wf);
                }
            }

            wf.on('submit', handleSubmit);
            wf.find('.withdrawPost').click(handleSubmit);
        });

        initPayAnyBankWithdrawForm();

        theCashier.returnPopup(function(){
            if (cashier.isMobileApp) {
                sendToFlutter({
                    data: 'Redirect mobile returnPopup /account/',
                    trigger_id: 'returnPopup',
                    debug_id: 'cashier.withdraw.init.returnPopup'
                })
            } else {
                gotoLang('/account/');
            }
        });
    },
    /**
     * Posting a withdrawal.
     *
     * The main logic that happens when a user wants to make a withdrawal:
     * * 1.) We first check if the form is valid.
     * * 2.) Then we loop all intputs and selects to get the POST data.
     * * 3.) Finally we post the data and invoke the appropriate hooks depending on if we get error or success.
     *
     * @param object wf A jQuery object containing the form to be posted.
     *
     * @return xxx
     */
    postTransaction: function(wf){

        // 1
        var result = wf.valid();

        if(!result){
            return false;
        }

        var psp     = wf.attr('id').split('-').pop();
        var network = theCashier.getNetwork(psp);

        var options = {
            action:   'withdraw',
            supplier: psp,
            network:  network
        };

        // 2
        wf.find('input, select').each(function(i){
            var el        = $(this);
            var name      = el.attr('name');
            options[name] = el.val();
        });

        // 3
        showPermanentLoader(function(){
            $.post(theCashier.getEndpoint(psp), options, function(res){
                hideLoader(function(){
                    var error   = res.errors;
                    if(!empty(error)) {
                        if (!empty(error.action) && error.action === 'get_raw_html') {
                            extBoxAjax('get_raw_html', 'mbox-msg', error.params);
                        }else if (!empty(error.action) && error.action === 'get_html_popup') {
                            extBoxAjax('get_html_popup', 'mbox-msg', error.params);
                        } else {
                            // Note that we pass in error to default but res to the potential override in order for the
                            // override to have as much flexibility as possible.
                            !empty(getHookVar(psp, 'error')) ? pspHooks[psp].error(res, options) : pspHooks.error(error, options);
                        }
                    } else {
                        var execKey = isMobile() ? 'mobWithdrawRes' : 'pcWithdrawRes';

                        if(!empty(getHookVar(psp, execKey))){
                            pspHooks[psp][execKey].call(pspHooks[psp], res);
                        } else {
                            cashier.withdraw.withdrawComplete(res);
                        }
                    }
                });

            }, "json");
        });
    },
    withdrawComplete: function(res){
        if (cashier.isMobileApp) {
            sendToFlutter({
                type: 'popup',
                data: {
                    header: 'withdraw.complete.headline',
                    body: 'withdraw.complete.body',
                    onClose: 'redirect account',
                },
                trigger_id: 'withdrawal',
                debug_id: 'withdrawComplete'
            });
        } else {
            mboxMsg($("#withdraw_complete").html(), true, function(){ gotoLang('/account/'); });
        }
        // We are looking at a successfully pending withdrawal.
        ajaxRefreshTopBalances();
    },
    /**
     * When Other Bank Account is clicked we do this.
     *
     * We replace the bank accounts drop down with a text field.
     *
     * @param string psp The PSP.
     *
     * @return bool False because we want to prevent form submission.
     */
    otherBankAccount: function (psp){
        $('.cashier-init-hidden').show();
        var attrs  = {"type": "text", "required": "true"};
        var pspBox = $('#' + psp + 'Box');
        var el     = pspBox.find("select[name='accnumber']");
        // We don't have the account number field so we're looking at a SEPA transfer.
        if(el.length == 0){
            var el = pspBox.find("select[name='iban']")
        }

        // We take all the attributes and use them to replace the drop down with a text field
        $.each(el[0].attributes, function(idx, attr) {
            attrs[attr.nodeName] = attr.nodeValue;
        });

        el.replaceWith(function () {
            return $("<input />", attrs);
        });

        pspBox.find('.bank-other-account-btn').hide();

        return false;
    },

    bankSelectAccount: function (wf) {
        showPermanentLoader(function () {
            const supplier = wf.serializeArray().find(item => item.name === 'supplier').value;

            $.post("/phive/modules/Cashier/html/select_account.php", {supplier: supplier}, function (response) {
                hideLoader(function () {
                    if (response.success === true && response.data.url) {
                        goTo(response.data.url);
                    } else {
                        mboxClose('mbox-msg', function () {
                            showGeneralInfoPopup('select.account.fail.description');
                        });
                    }
                });

            }, "json");
        })
    },
    handleAddBankAccount: function (wf) {
        const formData = wf.serialize();
        const requiredData = {clearing_house: "", bank_number: "", account_number: ""};

        Object.entries(Object.fromEntries(new URLSearchParams(formData)))
            .forEach(([key, value]) => {
                if (key in requiredData) requiredData[key] = value;
            });

        showPermanentLoader(function () {
            $.post("/phive/modules/Cashier/html/add_bank_account.php", {data: requiredData, action: 'addBankAccount'}, function (response) {
                hideLoader(function () {
                    if (response.success === true) {
                        mboxClose('mbox-msg', function(){
                            showVerifyDocumentPopup();
                        });
                    }
                });

            }, "json");
        })
    }
};

cashier.withdraw.desktop = {
    init: function(){
        lic('beforeWithdraw');
        cashier.withdraw.init();
    },
    action: 'withdraw'
};

cashier.withdraw.mobile = {
    init: function(){
        lic('beforeWithdraw');
        cashier.withdraw.init();
        theCashier.hideAllPsps();
    },
    logoClick: function(psp){
        theCashier.hideAllPsps();
        $('#' + psp + 'Box').show();
    },
    hideAllPsps: function(){
        $('#cashierDepositWithdraw > .cashierBox').hide();
    },
    action: 'withdraw'
};

cashier.deposit = {
    selectSubPspExtras: {},
    depositOverrides: {},
    showNormals: {},
    endpoints: {
        ccard:      'deposit_start',
        default:    'ebank_start'
    },
    getPredefHtml: function(predefs){
        if(empty(predefs)){
            return '';
        }
        var chunksContext = {pairs: []};
        _.each(_.chunk(_.keys(predefs).reverse(), 2), function(chunk){
            var chunkContext = {amounts: []};
            _.each(chunk, function(amount){
                // If the amount is larger than 100k we need to reduce the font size in order for the amount to fit within
                // the boxes.
                chunkContext.amounts.push(cashier.templates.predefAmountHb({display_amount: formatCashierAmount(amount), amount: amount, size: amount >= 100000 ? 22 : 24}));
            });
            chunksContext.pairs.push(cashier.templates.predefAmountChunkHb(chunkContext));
        });

        return cashier.templates.predefAmountsHb(chunksContext);
    },
    cardSwitchToNormal: function(){
        $('#card-repeats').hide();
        $('#card-normal-form').show();
        $('#fast-deposit-action-area').show();
        $('.cashierBtnOuter, #deposit-amount, #deposit-amount-label').show();
        return false;
    },
    repeatCardDeposit: function(repeatId, network, amount){
        if ($("#cvc").length > 0 && empty($("#cvc").val())) {
            $("#cvc").css("border-color", "#f00");
        } else {
            mboxClose();
            theCashier.postTransaction('repeat', repeatId, amount);
        }
    },
    showCVCBox: function(repeatId, network, amount, title, cvv_length){
        const $cardRepeatDepositButtons = $("#card-repeats button");

        for ($cardRepeatDepositButton of $cardRepeatDepositButtons) {
            if (document.activeElement === $cardRepeatDepositButton) {
                var cvcHtml = cashier.templates.cvcOneclickPopupHb({repeatId: repeatId, network: network, amount: amount});
                mboxMsg(cvcHtml, false, undefined, 200, true, undefined, title);
                theCashier.updateCvvInput(cvv_length);

                break;
            }
        }
    },
    updateCvvInput: function (cvv_length){
        $('[name="cv2"]').attr("minLength", cvv_length).attr("maxLength", cvv_length).on('blur keydown', function(){
            $(this).val($(this).val().replace(/\D/g, ''));
        });
    },
    addCheckBonusCode: function (id, site_type){
        $.post(cashier.infoBonusUrl, {"site_type": site_type, "action": "bonus_code", "bonus_code": $(id).val(), lang: cur_lang}, function(data){
            if(data.status == 'fail'){
                $(id).focus(function() { $(id).val(""); });
                $(id).val(data.error);
            }else{
                if (cashier.isMobileApp) {
                    sendToFlutter({
                        type: "html",
                        data: data.html,
                        trigger_id: 'addCheckBonusCode',
                        debug_id: 'cashier.deposit.addCheckBonusCode'
                    });
                } else {
                    theCashier.showPaymentMsg(data.html, null, false, true);
                }
            }
        }, "json");
    },
    cvcInfo: function(){
        if(this.cvcInfoIsShowing){
            $('.infoBoxCvc').hide();
            this.cvcInfoIsShowing = false;
        }else{
            $('.infoBoxCvc').show();
            var pos = $('.infoCvc').offset();
            pos.left += 30;
            pos.top  -= 95;
            $('.infoBoxCvc').offset(pos);
            this.cvcInfoIsShowing = true;
        }
    },
    verifyUrl: '/phive/modules/Mosms/ajax/verify.php',
    submitSmsCode: function(){
        $.post(theCashier.verifyUrl, {
            action: "validate-sms",
            code:   $("#verification-code").val(),
            lang:   cur_lang
        }, function(res){
            if(res.success == false){
                $("#verify-code .errors").html(res.error);
            } else {
                mboxClose();
                theCashier.smsSuccess = true;
                theCashier.postTransaction('deposit');
            }
        }, 'json');
    },
    verifyPhone: function(){
        mboxMsg($('#smsVerifyStartHb').html(), false, false, undefined, true);
    },
    /**
     * SMS validation logic.
     *
     * We need SMS validation of new non 3D cards, this takes care of that.
     *
     * @return void
     */
    verifyCode: function(){

        if(cashier.submittingSms){
            return;
        }

        cashier.submittingSms = true;

        showPermanentLoader(function(){
            var params       = getCardParams(null, getCardData());
            params['action'] = 'check_card';
            params['amount'] = $('#deposit-amount').val();

            $.post("/phive/modules/Cashier/html/deposit_start.php", params, function(ret){
                cashier.submittingSms = false;
                hideLoader(function(){
                    if(!ret.success) {
                        var msg = "<p><img src='/file_uploads/ccard.png'/></p><span style='font-size:18px;color:red'><b>Card Error</b></span><hr>" + ret.errors;
                        if (cashier.isMobileApp) {
                            return sendToFlutter({
                                type: 'html',
                                success: false,
                                data: msg,
                                trigger_id: "verifyCode",
                                debug_id: 'cashier.deposit.verifyCode'
                            });
                        }
                        mboxClose(undefined, function(){ mboxMsg(msg); });
                    } else {
                        if(ret.card_status){
                            theCashier.postTransaction('deposit');
                        } else {

                            $.post(theCashier.verifyUrl, {
                                action: "send-sms",
                                mobile: $("#mobile-verification").val(),
                                lang:   cur_lang
                            }, function(res){
                                if(typeof res == 'string'){
                                    if (cashier.isMobileApp) {
                                        return sendToFlutter({
                                            type: 'html',
                                            success: false,
                                            data: res,
                                            trigger_id: "verifyCode",
                                            debug_id: 'cashier.deposit.verifyCode2'
                                        });
                                    }
                                    mboxMsg(res, true);
                                } else if(res.success == false) {
                                    if (cashier.isMobileApp) {
                                        return sendToFlutter({
                                            type: 'html',
                                            success: false,
                                            data: res.error,
                                            trigger_id: "verifyCode",
                                            debug_id: 'cashier.deposit.verifyCode3'
                                        });
                                    }
                                    mboxMsg(res.error, true);
                                } else {
                                    mboxClose(undefined, function(){
                                        mboxMsg($('#smsSentHb').html(), false, false, undefined, true);
                                    });
                                }
                            }, 'json');
                        }
                    }
                });
            }, 'json');
        });
    },

    postRepeat: function(rId, amount){
        return this.postTransaction('repeat', rId, amount / 100);
    },

    /**
     * Wrapper around postTransaction.
     *
     * This function was needed when postTransaction handled both withdrawals and deposits but that was later refactored
     * into the separate withdraw / deposit objects so this could actually be folded into postTransaction.
     *
     * The main logic here is related to cards, if we're looking at a deposit with a non 3D card we do SMS validation.
     *
     * @param string action The action, repeat or deposit.
     * @param int rId The repeat id in case of repeat.
     * @param int amount Possible override of the amount.
     *
     * @return void
     */
    postDeposit: function(action, rId, amount){
        if(empty(action)){
            action = 'deposit';
        }

        // Validate here because on mobile, init does not trigger validation for some reason.
        $('#deposit-form').each(function() {
            let df = $(this);

            df.validate(df.data('validation'));
        });

        var psp = this.getOverridePspName();

        if(!empty(this.depositOverrides[psp]) && theCashier.smsSuccess == false){
            return this[ this.depositOverrides[psp] ]();
        }

        return this.postTransaction(action, rId, amount);
    },
    smsSuccess: false,
    preSelect: function(){
        var urlParams = new URLSearchParams(window.location.search);
        var supplier = urlParams.get('supplier');
        if (!supplier) {
            this.logoClick(this.preSelected.psp, this.preSelected.sub_psp);

            return;
        }

        if (banks.includes(supplier)) {
            this.logoClick('bank', supplier)

            return;
        }

        try {
            this.logoClick(supplier)
        } catch (error) {
            this.logoClick(this.preSelected.psp, this.preSelected.sub_psp);
        }
    },
    init: function(){
        cashier.returnPopup();
        if(theCashier.licDepLimitShow && !cashier.returnInfo.is_return){
            addToPopupsQueue(function ()  {

                theCashier.getTargetFrame().extBoxAjax('get_raw_html', 'global-deposit-limit', {module: 'Licensed', file: 'global_deposit_limit', isFastDeposit: cashier.isFastDeposit});
            });
        }
    },
    /**
     * The main logic that gets executed when the user presses the deposit button.
     *
     * Here we:
     * * 1.) Get the current psp and a potential pre send hook, if the hook overrides this function we call it and return right away.
     * * 2.) Validate the form with jQuery validate, if it isn't validating properly we return false, we also validate the amount field
     * separately, as it's not part of the main form jQuery validate can not validate it.
     * * 3.) We set the network from the configs, this is what will control if Sofort goes via Adyen or Skrill for instance.
     * * 4.) Unfortunately the card logic is so complex that we need a control statement here to deal with collecting the card data.
     * If we're not looking at a card deposit we just loop the form and get the name -> value pairs into the POST options.
     * * 5.) Finally we do the post and show the loader until we get the return, upon return we call the error or success hooks.
     *
     * @param string action The action, repeat or deposit.
     * @param int rId The repeat id in case of repeat.
     * @param int amount Possible override of the amount.
     * @param string psp Possible override of the psp.
     *
     * @return mixed False if failure, undefined if success.
     */
    postTransaction: function(action, rId, amount, psp, depositType, parentId, extraOptions) {
        // 1
        psp  = typeof psp == 'undefined' ? cashier.currentPsp : psp;
        var override = cashier.getSetting(psp, 'deposit', 'override', cashier.userData.country);

        if(!empty(override) && !empty(override.action)){
            // We have an action override, eg various providers that should be treated as ccard.
            psp = override.action;
        } else {
            psp = cashier.getPspOverride(psp);
        }

        if (empty(amount)) {
            amount = $('#deposit-amount').val()

            if (depositType !== 'undo') {
                // 2
                var result = $("#deposit-form").valid();
                if(!result){
                    return false;
                }
            }
        }

        const d = new Date();
        d.setTime(d.getTime() + (15*60*1000));
        document.cookie = "fallback_amount=" + amount + ";" + "expires=" + d.toUTCString() + ";path=/";

        var hook = getHookVar(psp, 'deposit');
        if(!empty(hook) && !empty(hook.postTransaction)){
            return hook.postTransaction(action, rId, amount);
        }

        if(empty(amount)){
            $('#deposit-amount').addClass('input-error');
            return false;
        }
        $('#deposit-amount').removeClass('input-error');

        if(cashier.postingTransaction){
            return false;
        }

        if (cashier.showTrustlyDepositPopup[psp] && !cashier.isFastDeposit) {
            cashier.showTrustlyDepositPopup[psp] = false;

            if (cashier.isMobileApp) {
                sendToFlutter({
                    type: 'popup',
                    data: {
                        header: 'trustly.deposit.info.title',
                        body: 'deposit.with.trustly.description',
                        actions: ['trustly.deposit.button', 'continue.with.paypal'],
                        amount: amount
                    },
                    trigger_id: 'showTrustlyDepositPopup',
                    debug_id: 'cashier.showTrustlyDepositPopup'
                });
            } else {
                showTrustlyDepositPopup(amount);
            }

            return false;
        }

        cashier.postingTransaction = true;

        // 3
        // TODO Network should be removed and routing take place exclusively on the BE side.
        var network    = theCashier.getNetwork(psp);
        var endpoint   = null;

        var options = {
            amount: amount,
            action: action
        };

        if(!empty(depositType)){
            options.deposit_type = depositType;
            options.parent_id = parentId;
        }

        // This is to delay the post in case we have to wait for fingerprint flows to terminate before we continue.
        var sleep = 0;

        // 4
        if (cashier.getSetting(psp)['type'] === 'ccard') {
            var cardData = getCardData();

            //BAN-11313 credorax token reuse for normal deposit and reroute it to quick deposit.
            if (cuCcPsp === 'credorax' && !rId && this.repeatCards) {
                const matchedTransaction = Object.values(this.repeatCards).find(({ card_num, expiry_month, expiry_year }) =>
                    card_num === cardData.cardHash &&
                    expiry_month.toString().padStart(2, '0') === cardData.expiryMonth &&
                    expiry_year.toString() === cardData.expiryYear
                );

                if (matchedTransaction) {
                    rId = matchedTransaction.id;
                    action = 'repeat';
                    options.action = 'repeat';
                }
            }

            endpoint         = theCashier.getEndpoint('ccard');
            network          = rId ? this.repeatCards[rId].supplier : cuCcPsp;
            options.ccSubSup = psp;
            psp              = 'ccard';
            var cardParams   = getCardParams(rId, cardData);
            cardParams.card_id = rId ? this.repeatCards[rId].card_id : null;

            var conf = cashier.ccConfigs[network];
            if(conf && conf.hasOwnProperty('sleep')){
                sleep = conf.sleep;
            }

            if(!cardParams){
                // Something went wrong with the encryption of card data, we abort with error.
                mboxTranslate('ccard.encryption.error');
                cashier.postingTransaction = false;
                return false;
            }
            _.each(cardParams, function(val, key){
                options[key] = val;
            });
            options.bin = $('.dc_cardnumber').val().substring(0, 6);

            options.browserInfo = getBrowserInfo();
        } else {
            endpoint = theCashier.getEndpoint(psp);
            // We don't parse the form if we're looking at a repeat, if that's the case we've got all the info we need already.
            if(action == 'deposit'){
                $('#deposit-cashier-box').find('input').each(function(i){
                    var el   = $(this);
                    var name = el.attr('name');
                    if(name == 'subs'){
                        return;
                    }
                    if(!empty(amount) && name == 'amount'){
                        return;
                    }
                    options[name] = el.val();
                });
            }
        }

        options.supplier = psp;
        options.network  = network;

        // Handle special cases for return_url where we need to return to a non default page.
        if (typeof cashierOverrideReturnUrlType !== 'undefined') {
            options.override_return_url_type = cashierOverrideReturnUrlType;
        }

        // Here we grab extra options if a definition exists
        if(!empty(getHookVar(psp, 'extraOptions', false))){
            options = pspHooks[psp].extraOptions(options);
        }

        if(!empty(rId)){
            options.repeat_id = rId;
        }

        hook = getHookVar(network, 'deposit');
        if (!empty(hook) && !empty(hook.postTransaction)) {
            return hook.postTransaction(action, rId, amount, options, endpoint);
        }

        $.extend(options, extraOptions);
        showPermanentLoader(function() {
            setTimeout(function() {
                cashier.deposit.makeDepositRequest(endpoint, action, psp, network, options, rId);
            }, sleep);
        });

        return true;
    },
    makeDepositRequest: function(endpoint, action, psp, network, options, rId) {
        saveFELogs(network, 'debug', 'makeDepositRequest', {'endpoint': endpoint, 'action': action, 'psp': psp, 'network': network, 'options': options, 'rId': rId}, {}, cashier.ccConfigs.worldpay !== undefined && cashier.ccConfigs.worldpay.js_logging);
        var options_parameters = options;
        if (!empty(getHookVar(network, 'beforeSubmit', null))) {
            options = pspHooks[network].beforeSubmit(options);
        }

        if (!empty(getHookVar(psp, 'beforeSubmit', null))) {
            options = pspHooks[psp].beforeSubmit(options);
        }

        // 5
        $.post(endpoint, options, function(res) {
            cashier.postingTransaction = false;

            hideLoader(function() {
                if (cashier.isMobileApp) {
                    sendToFlutter({
                        type: 'response',
                        data: res,
                        trigger_id: 'makeDepositRequest',
                        debug_id: 'cashier.makeDepositRequest.post.response'
                    });
                }

                error = res['errors'];

                if (Array.isArray(error)) {
                    error = error.join(' ');
                }

                if (!empty(error)) {
                    if (!empty(error.action) && error.action === 'show-net-deposit-limit-message') {
                        // Added this to fix the height of the cashier box when the net deposit limit popup is shown.
                        $(parent.document).find('#cashier-box').css('height', '630px');
                        $(parent.document).find('#mbox-iframe-cashier-box').css('height', '600px');
                        var extraOptions = isMobile() ? {width: '100%'} : {width: '520px'};
                        var params = {
                            module:   'Licensed',
                            file:     'net_deposit_info_box',
                            boxid:    'net-deposit-info-box',
                            boxtitle: 'net.deposit.limit.info.title',
                        };
                        extBoxAjax('get_raw_html', 'net-deposit-info-box', params, extraOptions);
                    } else if(!empty(error.action) && error.action === 'prepaid_deposits') {
                        lic('showPrepaidDepositLimitPopup', [
                            res.params.total_prepaid_deposits,
                        ]);
                    } else if (!empty(error.action) && error.action === 'customer_net_deposit') {
                        showConfirmationPopupOnCNDLExceed(res.params.available_limit, res.params.currency, res.params.till_date)
                            .then(function(result){
                                if (result) {
                                    options_parameters.amount = res.params.available_limit;
                                    mboxClose('confirm_processing_deposit_on_ndl_exceeded_popup');
                                    showPermanentLoader(function() {
                                        setTimeout(function() {
                                            theCashier.makeDepositRequest(endpoint, action, psp, network, options_parameters, rId);
                                        }, 0);
                                    });
                                }
                            });
                        return true;
                    } else if(!empty(error.action) && error.action === 'prepaid_cards') {
                        lic('showPrepaidMethodUsageLimitPopup');
                    } else if(!empty(error.action) && error.action === 'will-exceed-balance-limit') {
                        licFuncs.showBalanceLimitPopup({action: 'deposit', amount: amount});
                    } else {
                        // We don't support repeats for failover atm.
                        if (!empty(res['failover']) && action === 'deposit') {
                            cashier.currentPsp = res.failover.psp;
                            cashier.psps[cashier.currentPsp] = res.failover.config;
                            return theCashier.postTransaction(action, rId, options.amount);
                        }

                        var supplier = options.supplier === 'ccard' ? options.ccSubSup : options.supplier;

                        if (!shouldShowTrustlyErrorPopup(supplier, 'fail', error)) {
                            !empty(getHookVar(psp, 'error')) ? pspHooks[psp].error(res, options) : pspHooks.error(error, options);

                            if (cashier.isMobileApp) {
                                sendToFlutter({
                                    type: 'close',
                                    data: {},
                                    trigger_id: 'makeDepositRequest',
                                    debug_id: 'cashier.makeDepositRequest.return'
                                });
                            }

                            return;
                        }

                        if (cashier.isMobileApp) {
                            sendToFlutter({
                                type: 'popup',
                                data: {
                                    header: 'trustly.deposit.info.title',
                                    showError: res['show_error'],
                                    error: error,
                                    body: ['trustly.deposit.problem', 'trustly.deposit.description'],
                                    actions: ['trustly.deposit.button', 'try.again']
                                },
                                trigger_id: 'showTrustlyDepositErrorPopup',
                                debug_id: 'cashier.showTrustlyDepositErrorPopup.error'
                            });
                        } else {
                            showTrustlyDepositErrorPopup(supplier, res['show_error'] ? error : '');
                        }
                    }
                } else {
                    if (isMobile()) {
                        if (!empty(getHookVar(network, 'mobRes'))) {
                            pspHooks[network].mobRes(res, options);
                        } else if (!empty(getHookVar(psp, 'mobRes'))) {
                            pspHooks[psp].mobRes(res, options);
                        } else {
                            pspHooks.mobRes(res, options);
                        }
                    } else  { // Desktop
                        if (!empty(getHookVar(network, 'pcRes'))) {
                            pspHooks[network].pcRes(res, options);
                        } else if (!empty(getHookVar(psp, 'pcRes'))) {
                            pspHooks[psp].pcRes(res, options);
                        } else {
                            pspHooks.pcRes(res, options);
                        }
                    }
                }
            });
        }, "json")
            .fail(function() {
                cashier.deposit.resetRequestState()
            });
    },
    resetRequestState: function() {
        cashier.postingTransaction = false;
        hideLoader();
    },
    /**
     * Amounts setting function.
     *
     * Since we have only one PSP present in the DOM at any given point in time when we're looking at
     * deposits (as opposed to withdrawals) we can use direct DOM ids.
     *
     *
     * @return void
     * @param amount
     */
    setAmountCommon: function(amount){
        $(".cashier-predefined-amount").removeClass('gradient-default gradient-text-default');
        $('#cashier-predefined-amount-' + amount).addClass('gradient-default gradient-text-default');

        let debitAmount = cashier.currentPredefs[amount] ? cashier.currentPredefs[amount] : amount;
        let depositAmountInput = $('#deposit-amount');
        if (depositAmountInput.val() !== amount) {
            depositAmountInput.val(amount);
        }

        $('#credit-amount').html(amount + ' ' + cur_cur);
        $('#debit-amount').html(debitAmount + ' ' + cur_cur);
        if(debitAmount == amount){
            $('#fee-percentage').html('0%');
        } else {
            $('#fee-percentage').html(Math.round(((debitAmount - amount) / amount) * 100) + '%');
        }
    },
    getRepeatsHtml: function(psp){
        return empty(cashier && cashier.repeats && cashier.repeats[psp]) ? '' :  cashier.templates.repeatsHb({repeats: cashier.repeats[psp]});

    },
    mobileAppHook: function(psp, customContent){
        var target = cashier.getTargetFrame();
        if(typeof customContent == 'undefined'){
            customContent = pspHooks[psp].loaderMsg;
        }
        target.showPermanentLoader(function(){
            doWs(
                cashierWs,
                function(e){
                    var wsRes = JSON.parse(e.data);
                    target.hideLoader();
                    target.mboxClose('mbox-msg', function(){
                        target.mboxMsg(wsRes.msg, true);
                    });
                }
            );
        },  customContent);
    },

    initMobileIframe: function(pspName, url, config = {}, onClosePopupCallback) {
        const extraStyle = {
            width: config.width || '100%',
            height: config.height || '100vh',
            callb: () => {
                document.body.classList.add('scrolling-disabled');

                const popupHeader = document.querySelector('.lic-mbox-header');
                const iframeElem = document.querySelector('.multibox-content iframe');

                if (popupHeader && iframeElem) {
                    const iframeHeight = iframeElem.offsetHeight - popupHeader.offsetHeight;
                    iframeElem.style.height = `${iframeHeight}px`;
                }

                if (typeof onClosePopupCallback === 'function') {
                    const closeButton = document.querySelector('.lic-mbox-close-box');
                    if (closeButton) {
                        closeButton.onclick = function (event) {
                            onClosePopupCallback(event);
                        }
                    }
                }
            },
            onClose: () => {
                document.body.classList.remove('scrolling-disabled');
            }
        };

        const iframeHeadline = config.psp_headline ? `${pspName}.deposit.iframe.headlines` : `deposit`;
        iframeAjaxBox(`${pspName}-deposit-iframe`, url, iframeHeadline, extraStyle);
    }
};

cashier.tpls      = [];
cashier.templates = {};

cashier.deposit.desktop = {
    action: 'deposit',
    /**
     * Left logo click event handler.
     *
     * Since the desktop deposit logic groups banks in the middle we need special logic to handle
     * that behaviour:
     * * 1.) We first get the real PSP config, when clicking for instance VISA we will have ccard as PSP and VISA as subPsp.
     * After that we unset the subPsp in order to avoid the cards getting displayed in the middle when clicking a card, ie
     * triggering logic related to the grouping of banks in the mdiddle.
     * * 2.) Next we get the override, eg mc -> ccard.
     * * 3.) Next we get the override, eg mc -> ccard, note how the override **completely** replaces the original config.
     * * 4.) In case we're looking at a alternative which in itself is a group, ie BANK we render the radios in the middle.
     * * 5.) Then we setup the Handlebars context and render the PSP.
     *
     * @param string psp The PSP to work with (eg ccard).
     * @param string subPsp The sub PSP to work with (eg visa or seb). In case of SEB in this case we select that sub
     * radio button.
     *
     * @return void
     */
    logoClick: function(psp, subPsp){
        // in fast deposit we cannot select other providers (prevents JS errors)x.
        if(cashier.isFastDeposit) {
            return;
        }

        // We have for instance (ccard, visa) so we need to hardwire visa in this desktop context.
        if(!empty(theCashier.overrideConfig[subPsp])){
            psp    = subPsp;
            subPsp = undefined;
        } else if(psp == 'ccard' && empty(subPsp)){
            // Initially no deposits and default to ccard, ccard doesn't exist on desktop
            _.each(cashier.overrides, function(override, overridePsp){
                // We can't override with a card type that should not exist in this context
                // ex: Dankort for a Swede.
                if(override.type == 'ccard' && !empty(cashier.psps[overridePsp])){
                    psp = overridePsp;
                    return;
                }
            });
        }

        var config = empty(cashier.overrides[psp]) ? cashier.psps[psp] : cashier.overrides[psp];

        // -------- Radio sub alternatives ----------
        var radiosHtml = '';
        let pspConf = (psp in cashier.psps) ? cashier.psps[psp] : null;

        if (pspConf && ((pspConf.display_under_methods === true) || (cashier.groups.indexOf(psp) !== -1))) {
            var radios = '';
            _.each(cashier.psps, function(conf, subPsp){
                if ((conf.type !== pspConf.type) || (subPsp === psp) || (conf.display_under_methods === true) || conf.display === false) {
                    return;     // same as 'continue'
                }

                var display_under_psp = conf.display_under_psp;
                if (conf.hasOwnProperty('display_under_psp_overrides') && (conf.display_under_psp_overrides[cashier.userData.country])) {
                    display_under_psp = conf.display_under_psp_overrides[cashier.userData.country];
                }

                if (pspConf.display_under_methods === true) {
                    if (display_under_psp !== psp) {
                        return;
                    }
                } else if (conf.hasOwnProperty('display_under_psp') && (display_under_psp !== psp)) {
                    return;
                }

                var img = subPsp;
                radios += cashier.templates.subRadioHb({psp: subPsp, img: img});
            });
            radiosHtml = cashier.templates.subRadioContainerHb({radios: radios});
        }

        // -------- Repeat transactions ----------
        var repeatsHtml = '';
        if(!empty(cashier.repeats) && !empty(cashier.repeats[psp])){
            repeatsHtml = cashier.templates.repeatsHb({repeats: cashier.repeats[psp]});
        }

        var context = {pspName: config.display_name, pspInfo: config.info_text, radios: radiosHtml, repeats: this.getRepeatsHtml(psp)};

        // this will apply the ui changes in the deposit cashier middle section
        if (config.ui_adjustments_needed) {
            replaceClass('deposit-cashier-box', '-cashier-middle', `${psp}-cashier-middle`);
        }

        var html = cashier.templates.basicDepositHb(context);
        $('#deposit-cashier-box').html(html);

        // We're looking at a sub section without a prior preselect so we need to select one of the subs
        if(cashier.groups.indexOf(psp) !== -1 && psp === subPsp){

            var subPsp = null;
            var preSel = cashier.preselConfig[ cashier.userData.country ];

            // We get all PSPs of the type, eg bank
            for(iPsp in cashier.psps){
                var config = cashier.psps[ iPsp ];
                if(config.type === psp){

                    subPsp = iPsp;

                    if(subPsp === preSel || config['preselect'] === true){
                        // The PSP currently in the loop is either preferred default or preferred in the category / type.
                        break;
                    }
                }
            }
        }

        $('[id^="cashier-left-alt-"]').removeClass('logo-select-border');
        $('#cashier-left-alt-' + psp).addClass('logo-select-border');


        if(!empty(subPsp) && subPsp != psp){
            // Only happens if user's previous deposit is with a grouped sub like SEB.
            this.selectSubPsp(subPsp);
        } else {
            this.selectPsp(psp);
        }
    },
    /**
     * Selecting a grouped PSP in the middle, eg a bank radio.
     *
     * This is only needed on first load in case we need to preselect for instance Nordea.
     *
     * @param string psp The PSP.
     *
     * @return void
     */
    selectSubPsp: function(psp){
        var toSelect = $('#' + psp + '-subradio');
        // If we can't find a proper alternative we just pick the first one.
        if(toSelect.length == 0){
            toSelect = $('[id$="-subradio"]').first();
            // We need to hijack psp for the psp select logic called below.
            psp = toSelect.attr('id').split('-').shift();
        }
        toSelect.prop('checked', true);
        this.selectPsp(psp, cashier.deposit.selectSubPspExtras);
    },
    /**
     * The main desktop desposit PSP switching logic.
     *
     * Here we:
     * 1.) First get potential predefined amounts and their HTML by way of Handlebars.
     * 2.) Hide the custom amount input field and "label" if we got custom predefs, if not we show default predefs and
     * do **not** hide the custom section.
     * 3.) Get PSP configs, in thie case to be able to display correct min and max deposit amounts.
     * 4.) Get the potential override, we use that override to display correct extra fields / elements, eg clicking mc, visa
     * and maestro will show the ccard elements.
     *
     * @param string psp The selected PSP.
     *
     * @return void
     */
    selectPsp: function(psp, extraFuncs){
        this.clearAmount();

        var predefs          = cashier.getSetting(psp, 'deposit', 'amounts', cur_cur);
        var hideCustomAmount = false;
        if(predefs === false){
            predefs = this.defaultPredefAmounts;
        } else {
            // We have custom amounts which means we want to hide the Other button plus free text field.
            hideCustomAmount = true;
        }

        var predefHtml = theCashier.getPredefHtml(predefs);

        $('#deposit-predefined-amounts-container').html(predefHtml);

        if(hideCustomAmount){
            $('#predef-amount-other').hide();
            $('#deposit-amount').hide();
            $('.cashier-right-content-wrap > .expense-info').hide();
        } else {
            $('#predef-amount-other').show();
            $('#deposit-amount').show();
            $('.cashier-right-content-wrap > .expense-info').show();
        }

        cashier.currentPsp     = psp;
        cashier.currentPredefs = predefs;
        var depositSetting     = cashier.getSetting(psp, 'deposit');

        $('#cashier-min-amount').html(nfCents(depositSetting.min_amount, true));
        $('#cashier-max-amount').html(nfCents(depositSetting.max_amount, true));

        // Extra fields
        // We reset the extra fields to nothing, we always do this to clear out prior data.
        $('#extra-fields').html('');

        var fieldsPsp = theCashier.getOverridePspName(psp);

        // We get extra fields HTML, no need for Handlebars here.
        var extraFieldsHtml = $('#' + fieldsPsp + '-fields').html();
        if(!empty(extraFieldsHtml)){
            $('#extra-fields').html(extraFieldsHtml);
        }
        _.each(extraFuncs, function(func){
            func(psp);
        });
        this.handleAmountChange();
    },
    setAmount: function(amount){
        if(empty(amount)){
            theCashier.clearAmount();
            $('#deposit-amount').val('');
        } else {
            cashier.deposit.setAmountCommon(amount);
        }
    },
    /**
     * Change the class when the user clicks "Other" in deposit box
     */
    clickOther: function () {
        var amount = $('#deposit-amount').val();
        this.setAmount(amount);
        $(".cashier-predefined-amount").removeClass('gradient-default gradient-text-default'); // clear the other selected inputs
        $("#predef-amount-other").addClass('gradient-default gradient-text-default');
    },

    /**
     * Clearing the amounts section.
     *
     * This is a typical desktop deposit issue and its solution. Since the amounts section with the debit, credit and fee amounts
     * is always showing we need to clear it out when we switch PSPs in order to avoid confusion. Keeping the amounts as they were
     * until a new amount is entered or selected in the newly displayed PSP could make people believe that we apply a 25% fee to
     * card deposits for instance.
     *
     * @return void
     */
    clearAmount: function(){
        $(".cashier-predefined-amount").removeClass('gradient-default gradient-text-default');
        $('#deposit-amount').val(0);
        $('#credit-amount').html(0 + ' ' + cur_cur);
        $('#debit-amount').html(0 + ' ' + cur_cur);
        $('#fee-percentage').html('0%');
    },
    init: function(){
        lic('beforeDeposit');
        cashier.deposit.init();
        theCashier.preSelect();
        theCashier.handleAmountChange();

        if(cashier.returnInfo.is_return) {
            resizeCashier(cashierWidth, cashierHeight);
        }
    },
    /**
     * Setting the deposit amount when amount input changes.
     */
    handleAmountChange(){
        $('#deposit-amount').on('change input', function(){
            var amount = $(this).val();
            if(amount === ''){
                theCashier.setAmount(0);
                return;
            }
            if(!isNumber(amount)){
                return;
            }
            theCashier.setAmount(amount);
            $(".cashier-predefined-amount").removeClass('gradient-default gradient-text-default');
            $("#predef-amount-other").addClass('gradient-default gradient-text-default');
        });
    },
    active: false,
    backToPspForm: function(){
        theCashier.selectPsp(cashier.currentPsp);
    }

};

cashier.deposit.mobile = {
    action: 'deposit',
    active: false,
    setAmount: function(amount){
        cashier.deposit.setAmountCommon(amount);
    },
    /**
     * The main logic handling the switching of PSPs.
     *
     * Here we:
     * * 1.) First get the appropriate config, note that we don't have any overrides here atm, this is because we don't
     * display any of the card logos / buttons directly on mobile anyway, it's just one ccard button that doest not need
     * an override.
     * * 2.) Populate the Handlebars context with the necessary information for display like properly formatted amounts.
     * * 3.) Fetch potential predefined amounts.
     * * 4.) Get the Handlerbars template HTML and use jQuery to popuplate the target div.
     * * 5.) Finally we get the extra fields by way of standard jQuery copy paste from a hidden object. The reason we can't
     * use Handlebars for the extra fields / elements is that they sometimes contain script tags which screws up the
     * Handlebars parsing.
     *
     * @param string psp The PSP to select.
     *
     * @return void
     */
    selectPsp: function(psp){
        var config  = cashier.psps[psp];


        var context = {
            pspName: config.display_name,
            pspInfo: config.info_text,
            max: nfCents(config.deposit.max_amount, true),
            min: nfCents(config.deposit.min_amount, true),
            psp: psp,
            repeats: this.getRepeatsHtml(psp),
            logo: empty(config.logo_override_base_name) ? psp : config.logo_override_base_name
        };

        var predefs = cashier.getSetting(psp, 'deposit', 'amounts', cur_cur);
        if(!empty(predefs)){
            context.forcedAmounts = theCashier.getPredefHtml(predefs);
            cashier.currentPredefs = predefs;
        } else {
            cashier.currentPredefs = false;
        }

        cashier.currentPsp = psp;

        var html = cashier.templates.pspBoxHb(context);

        $('#deposit-cashier-box').html(html);

        //var fieldsPsp = empty(config['force_fields_override']) ? psp : config['force_fields_override'];
        var fieldsPsp = theCashier.getOverridePspName(psp);
        $('#deposit-form').html( $('#' + fieldsPsp + '-fields').html() );

        if(!empty(theCashier.onClickHooks[psp])){
            theCashier.onClickHooks[psp].call();
        }
    },
    logoClick: function(psp){
        theCashier.selectPsp(psp);
    },
    init: function(){
        lic('beforeDeposit');
        if(isIframe()){
            // we don't want to show the deposit limit when we have success/fail message on load.
            if(theCashier.licDepLimitShow && !cashier.returnInfo.is_return){
                // Popup with info and progress of the limit etc.
                theCashier.getTargetFrame().extBoxAjax('get_raw_html', 'global-deposit-limit', {module: 'Licensed', file: 'global_deposit_limit', isFastDeposit: cashier.isFastDeposit});
            }
            cashier.returnPopup(function(){
                // We're in the iframe on mobile so we close the cashier iframe overlay
                // to go back to the game.
                parent.$('.vs-popup-overlay__header-closing-button').click();
            });
        } else {
            cashier.deposit.init();
        }

        theCashier.preSelect();
    }
};


var pspHooks = {};

pspHooks.getRedirectType = function(res){
    if(empty(res.result)){
        return 'end';
    }

    if(!empty(res.result.form)){
        return 'form';
    }

    if(!empty(res.result.url)){
        return 'goto';
    }

    return 'end';
};

/**
 * The default error hook / handler.
 *
 * Description
 *
 * @param error
 * @param options
 *
 * @return void
 */
pspHooks.error = function(error, options, onClose){
    if(typeof error == 'object'){
        var errStr = '';
        _.each(error, function(val, key){
            errStr += val + ' ';
        });
        error = errStr;
    }

    if(empty(onClose) && options.action != 'withdraw'){
        // We don't have an onClose override so we just call handleEnd() as a default, for repeat and deposit as it's there that
        // we have to deal with the added complexity of the fast deposit context.
        onClose = function(){
            cashier.handleEnd(false);
        };
    }

    if(typeof error == 'string'){
        if(!empty(cashier.templates.baseErrorHeadline)){
            var displayName = cashier.getSetting(options.supplier).display_name;
            if (cashier.isMobileApp) {
                sendToFlutter({
                    type: "html",
                    data: cashier.templates.baseErrorHeadline({displayName: displayName}) + error,
                    trigger_id: 'pspHooks.error',
                    debug_id: 'pspHooks.error.baseErrorHealine'
                });
            } else {
                theCashier.showPaymentMsg(cashier.templates.baseErrorHeadline({displayName: displayName}) + error, onClose);
            }
        } else {
            // A withdrawal error.
            if (cashier.isMobileApp) {
                sendToFlutter({
                    type: 'html',
                    success: false,
                    data: error,
                    trigger_id: 'pspHooks.error',
                    debug_id: 'pspHooks.error.else'
                });
            } else {
                theCashier.showPaymentMsg(error, onClose);
            }
        }
    } else {
        // We're looking at an array.
        switch(error[0]){
            case 'source_of_funds':
                showSourceOfFundsBox("/sourceoffunds1/?document_id=" + error[1].id);
                break;
        }
    }
};

/**
 * Default mobile result handler.
 *
 * This is a bit simpler than the desktop handler as we don't have to deal with iframe sizes.
 *
 * @param objct res The inital BE result.
 * @param object options The deposit options.
 * @return void
 */
pspHooks.mobRes = function(res, options){
    var noIframe = !cashier.doIframe(options.supplier, res);
    if(!isIframe()){
        // We can't break out of an iframe if we're not inside one.
        noIframe = false;
    }

    switch (this.getRedirectType(res)) {
        case 'form':
            var target = noIframe ? parent : window;
            target.$.redirectPost(res.result.form.url, res.result.form.fields);
            break;

        case 'goto':
            if (cashier.isMobileApp && options.supplier === 'swish') {
                // The redirect to the Swish app to be handled by the mobile app
                return sendToFlutter({
                    type: 'close',
                    data: {},
                    trigger_id: 'mobRes',
                    debug_id: 'pspHooks.mobRes'
                });
            }
            toExtGo(res.result.url, noIframe ? '_parent' : '_self');
            break;

        case 'end':
            jsReloadWithParams({end: 'true'});
            break;
    }

    if (options.supplier === 'swish') {
        cashier.deposit.mobileAppHook(options.supplier, '');
    }
};

/**
 * Default desktop result handler.
 *
 * The default handler for handling desktop deposits, this is the logic tha is being run after the initial
 * call to the PSP, typically in order to take the user to the remote site in order to complete the deposit.
 * Some PSPs don't have the standard go to us flow, in that case we just display
 * the result right away.
 *
 * @param object res The inital BE result.
 * @param object options The deposit options.
 *
 * @return void
 */
pspHooks.pcRes = function(res, options){

    var psp      = options.supplier;
    var noIframe = !cashier.doIframe(psp, res);
    var network  = options.network;
    var iframeWh = null;
    var iframeW  = 600;
    var iframeH  = 1000;

    if(!noIframe){
        if(pspHooks.networkOverrides[network] && pspHooks.networkOverrides[network].iframeWh){
            iframeWh = pspHooks.networkOverrides[network].iframeWh;
        } else {
            iframeWh = getHookVar(psp, 'iframeWh');
        }

        if(!empty(iframeWh)){
            var iframeW = iframeWh[0];
            var iframeH = iframeWh[1];
        }
    }

    switch(this.getRedirectType(res)){
        case 'form':
            // We need to post to a remote form
            if(!empty(iframeWh)){
                resizeCashier(iframeW, iframeH);
            }
            var target = noIframe ? parent : window;
            target.$.redirectPost(res.result.form.url, res.result.form.fields);
            break;
        case 'goto':
            // We just redirect inside the iframe
            window.parent.$('#mbox-iframe-cashier-box').css('background-color', '#fff');
            noIframe ? window.top.location.href = res.result.url : toExtGo(res.result.url, '_self', iframeW, iframeH);
            break;
        case 'end':
            // We display result right away.
            jsReloadWithParams({end: 'true'});
            break;
    }
};

pspHooks.siru = {
    pcRes: function(res){
        setIframeColor('white');
        toExtGo(res.result.url, '_self', 1000, 900);
    }
}

pspHooks.ecopayz = {
    iframeWh: [900, 800]
};

pspHooks.flykk = {
    depositIframeUrl: function (response, options) {
        var noIframe = !cashier.doIframe(options.supplier, response);
        const base = getCashierBaseUrl()+'flykk/';

        const params = {
            no_iframe: noIframe,
            tr_id: response.result.uid,
            return_urls: response.result.return_urls,
            embedded_script_source: response.result.embedded_script_source
        };

        const queryParams = new URLSearchParams(params).toString();
        return queryParams ? `${base}?${queryParams}` : base;
    },

    mobRes: function (res, options) {
        const onClosePopupCallback = (event) => {
            gotoDepositCashier();
        };

        cashier.deposit.initMobileIframe('flykk', pspHooks.flykk.depositIframeUrl(res, options), {}, onClosePopupCallback);
    },

    pcRes: function (res, options) {
        res.result.url = pspHooks.flykk.depositIframeUrl(res, options);
        pspHooks.pcRes(res, options);
    }
};

pspHooks.swish = {
    /**
     * Swish mobile hook, we need to redirect to the Swish app on the same phone.
     *
     * We use the request token in order to tell the Swish app which context to immediately load (ie the deposit to Casino context).
     *
     * @param object res The Swish BE result.
     *
     * @return void
     */
    mobRes: function(res){

        goTo(res.result.url, undefined, isIframe());

        /*
        // This is the direct integration logic, we keep it for now.
        var baseUrl     = jsGetBase() + '?show_msg=deposit.return.msg';
        var callBackUrl = isIframe() ? jsGetBase(window.top.location.href) + '?iframeUrl=' + encodeURI(baseUrl) : baseUrl;
        goTo('swish://paymentrequest?token='+res.result.request_token+'&callbackurl='+encodeURI(callBackUrl), undefined, isIframe());
        */
    },
    /**
     * Swish PC hook, we need to show a loader to open the Swish app on a phone.
     *
     * Once the deposit is done we get the result via websockets to avoid having to do polling.
     *
     * @param object res The Swish BE result.
     *
     * @return void
     */
    pcRes: function(res){
        cashier.deposit.mobileAppHook('swish', '<div style="margin-left: -36px; margin-top: -45px; font-size: 12px;">' + pspHooks.swish.loaderMsg + '<br clear="all"/><br clear="all"/><img style="object-fit: contain; max-width: 100%; max-height: 100%; height: auto; margin-bottom: -15px;" src="' + res.result.qrcode + '" alt="QR Code"></div>');
    }
};


pspHooks.siirto = {
    mobRes: function(res){
        cashier.deposit.mobileAppHook('siirto');
    },
    pcRes: function(res){
        cashier.deposit.mobileAppHook('siirto');
    }
};


// TODO move iframe size to config instead
pspHooks.interac = {
    extraOptions: function(options){
        options.method = 'combined';
        return options;
    },
    iframeWh: [820, 1200]
};

pspHooks.interaconline = {
    extraOptions: function(options){
        options.method = 'online';
        return options;
    },
    iframeWh: [800, 1240]
};

pspHooks.instadebit = {
    iframeWh: [850, 650]
};

pspHooks.mobilepay = {
    iframeWh: [410, 650]
};

pspHooks.citadel = {
    iframeWh: [850, 1050]
};



// Sofort via Adyen
/*
pspHooks.sofort = {
    iframeWh: [600, 1000]
};
*/

pspHooks.pspGetSkrillConfig = function(){
    return {
        iframeWh: [449, 859]
    };
};

pspHooks.skrill = pspHooks.pspGetSkrillConfig();
pspHooks.rapid  = pspHooks.pspGetSkrillConfig();

/**
 * Card overrides.
 *
 * TODO if the BE return logic is refactored to comply with the "standard" return format I believe we could remove this. /Henrik
 *
 */
pspHooks.ccard = {
    resCommon: function(res, channel, options){

        var network = options.network;
        var psp     = options.supplier;
        var subSup  = options.ccSubSup;

        if(res.success !== true){
            if (cashier.isMobileApp) {
                sendToFlutter({
                    type: 'html',
                    success: false,
                    data: res.errors ,
                    trigger_id: 'resCommon.error',
                    debug_id: 'pspHooks.ccard.resCommon'
                });
            } else {
                theCashier.showPaymentMsg(res.errors, false);
            }

            return false;
        }

        if(res.result && res.result.url) {
            var doIframe = cashier.doIframe(subSup, res, network);

            if(channel == 'desktop' && doIframe){
                if(pspHooks.networkOverrides[network] && pspHooks.networkOverrides[network].iframeWh){
                    var iframeW = pspHooks.networkOverrides[network].iframeWh[0];
                    var iframeH = pspHooks.networkOverrides[network].iframeWh[1];
                } else {
                    var iframeW = this.iframeWh[0];
                    var iframeH = this.iframeWh[1];
                }
                window.parent.$('#mbox-iframe-cashier-box').css('overflow', 'auto');
                resizeCashier(iframeW, iframeH);
            }

            // Some CC suppliers do not have form params, in that case we just GET the URL.
            if(empty(res.result.post_params)){
                toExtGo(res.result.url, doIframe ? '_self' : '_top');
            } else {
                var target = doIframe ? window : parent;
                target.$.redirectPost(res.result.url, res.result.post_params);
            }

        } else {
            if (cashier.isMobileApp) {
                sendToFlutter({
                    type: 'html',
                    success: true,
                    data: theCashier.successMsg,
                    trigger_id: 'deposit',
                    debug_id: 'pspHooks.ccard.resCommon.else.app'
                });
            } else {
                theCashier.showPaymentMsg(theCashier.successMsg, null, true, true);
            }
        }
    },
    pcRes: function(res, options){
        this.resCommon(res, 'desktop', options);
    },
    mobRes: function(res, options){
        this.resCommon(res, 'mobile', options);
    },
    iframeWh: [840, 660],
    error: function(res, options){

        var errorOptions = options;
        errorOptions.supplier = empty(options['ccSubSup']) ? options.supplier : options.ccSubSup;

        // We reload the page to trigger a new CC PSP in the FE
        pspHooks.error(res.errors, errorOptions, function(){
            cashier.handleEnd(false);
        });
    }
};

pspHooks.mifinity = {
    resCommon: function(res, options, start, fail, success){
        const multiboxID= '#mifinity-iframe-box';
        $.multibox({
            id: multiboxID.replace('#', ''),
            type: 'html',
            width: '100%',
            content: '<div id="mifinity-iframe"></div>',
            onComplete: function(){
                start();
                var widget = showPaymentIframe("mifinity-iframe", {
                    token: res.result.token,
                    complete: function(){

                    },
                    fail: fail,
                    success: success
                });

                const maxModalHeight = Math.min($(window).height(), $(multiboxID).height());
                $(multiboxID).css({overflow: 'auto', 'max-height': maxModalHeight + 'px'});
            }
        });
    },
    pcRes: function(res, options){
        this.resCommon(
            res,
            options,
            function(){
                resizeCashier(375, 700);
            },
            function(){
                resizeCashier(cashierWidth, cashierHeight);
                cashier.handleEnd(false);
                // jsReload('?end=true&status=failed');
            },
            function(){
                resizeCashier(cashierWidth, cashierHeight);
                cashier.handleEnd(true);
                // jsReload('?end=true');
            }
        );
    },
    mobRes: function(res, options){
        this.resCommon(
            res,
            options,
            function(){},
            function(){
                cashier.handleEnd(false);
            },
            function(){
                cashier.handleEnd(true);
            }
        );
    }
};

pspHooks.networkOverrides = {
    emp: {
        iframeWh: [900, 900]
    },
    zimplerbank: {
        iframeWh: [500, 700]
    }
};

pspHooks.credorax = {
    beforeSubmit: function(options){
        if(document.credoraxFingerPrintDone === true){
            if(options.hasOwnProperty('credorax_encrypted_data')){
                options.credorax_encrypted_data['3ds_compind'] = 'Y';
            }

            if(options.hasOwnProperty('cvc')){
                options.cvc['3ds_compind'] ='Y';
            }
        }
        return options;
    },
    encryptCvc: function(cvc, rId){
        const conf = cashier.ccConfigs['credorax'];
        var token = false;
        if(!empty(theCashier.repeatCards[rId])){
            token = theCashier.repeatCards[rId].ext_id;
        } else {
            return false;
        }

        var res = pspHooks.credorax.commonEncrypt(tokenKeyCreation, [
            conf.mid[cashier.userData.country] ?? conf.mid['ROW'] ?? conf.mid,
            conf.static_key[cashier.userData.country] ?? conf.static_key['ROW'] ?? conf.static_key,
            cvc,
            token
        ]);

        if(parseInt(res['z2']) != 0){
            return false;
        }

        return res;
    },
    commonEncrypt: function (func, args) {
        var res = JSON.parse(func.apply(null, args));
        if (res['z2'] != '0') {
            $.post(
                "/phive/modules/Micro/ajax.php",
                {
                    action: 'log_error',
                    tag: 'card-encryption-error',
                    data: res
                }
            );

            return false;
        }

        if(!empty(res['3ds_method']) && !empty(res['3ds_trxid'])){
            const conf = cashier.ccConfigs['credorax'];
            var fingerprintObj = {
                threeDSMethodNotificationURL: conf.fingerprint_notification_url,
                threeDSServerTransID: res['3ds_trxid']
            };

            var doSubmit = true;
            var iframe = $('<iframe width="1" height="1" id="credorax-fingerprint"></iframe>');
            var form = $('<form action="' + res['3ds_method'] + '" method="POST"><input type="text" name="threeDSMethodData" value="' + utf8ToB64( JSON.stringify(fingerprintObj) ) + '" /></form>');

            iframe.on('load', function(){
                if(doSubmit){
                    $(this).contents().find('body').append(form);
                    form.submit();
                    doSubmit = false;
                }
            });

            $('body').append(iframe);


        }

        return res;
    }
}

pspHooks.adyen = {
    encryptCvc: function(cvc, rId){
        var pubKey            = cashier.ccGetPubKey('adyen');
        var obj               = {};
        var cseInstance       = adyen.encrypt.createEncryption(pubKey, obj);
        obj.cvc              = cvc;
        obj.generationtime = $("input[extra-attr='adyen-generationtime']").val();
        return cseInstance.encrypt(obj);
    }
};

pspHooks.worldpay = {
    resCommon: function(res, options) {
        saveFELogs('worldpay', 'debug', 'resCommon', {'res': res, 'challenge_url': pspHooks.worldpay.deposit.getChallengeUrl()}, {}, cashier.ccConfigs.worldpay !== undefined && cashier.ccConfigs.worldpay.js_logging);
        pspHooks.worldpay.deposit.startIframe(pspHooks.worldpay.deposit.getChallengeUrl(), [
            {name: 'JWT', value: res.result.challenge.token},
            {name: 'MD', value: res.result.reference_id}
        ], false);
    },
    pcRes: function(res, options) {
        if (!empty(res.result['challenge'])) {
            setIframeColor('white');
            this.resCommon(res, options);
        } else {
            pspHooks.pcRes(res, options);
        }
    },
    mobRes: function(res, options) {
        if (!empty(res.result['challenge'])) {
            this.resCommon(res, options);
        } else {
            pspHooks.mobRes(res, options);
        }
    },
    deposit: {
        action: 'deposit',
        endpoint: '',
        listenerEnabled: false,
        options: {},
        rId: '',
        enableListener: function() {
            saveFELogs('worldpay', 'debug', 'enableListener', {}, {}, cashier.ccConfigs.worldpay !== undefined && cashier.ccConfigs.worldpay.js_logging);
            if (this.listenerEnabled) {
                return;
            }

            this.listenerEnabled = true;

            let supplier = this;
            let url = new URL(supplier.getDdcUrl());

            window.addEventListener("message", function(event) {
                saveFELogs('worldpay', 'debug', 'Received a notification that DDC is complete. DDC returns a JavaScript message event, containing a SessionId.', {'event' : JSON.stringify(event)}, {}, cashier.ccConfigs.worldpay !== undefined && cashier.ccConfigs.worldpay.js_logging);
                if (event.origin === url.origin) {
                    supplier.options.session_id = '';

                    var data = JSON.parse(event.data);
                    if (data && data !== undefined && data.Status && data.SessionId) {
                        saveFELogs('worldpay', 'debug', 'Received session ID from DDC event message.', {'session_id': data.SessionId}, {}, cashier.ccConfigs.worldpay !== undefined && cashier.ccConfigs.worldpay.js_logging);
                        supplier.options.session_id = data.SessionId;
                    } else {
                        saveFELogs('worldpay', 'debug', 'WorldPay DDC Outcome: Session ID from the DDC event message is not available so the payment will downgrade to 3DS1.', {}, {}, cashier.ccConfigs.worldpay !== undefined && cashier.ccConfigs.worldpay.js_logging);
                    }

                    supplier.initialPaymentRequest();
                }
            }, false);
        },
        getBin: function(rId) {
            if (rId && !empty(theCashier.repeatCards[rId])) {
                return theCashier.repeatCards[rId].card_bin;
            }

            return $('.dc_cardnumber').val().substr(0, 9);
        },
        getChallengeUrl: function() {
            return cashier.ccConfigs.worldpay.challenge_url;
        },
        getDdcUrl: function() {
            return cashier.ccConfigs.worldpay.ddc_url;
        },
        initialPaymentRequest: function() {
            saveFELogs('worldpay', 'debug', 'initialPaymentRequest', {}, {}, cashier.ccConfigs.worldpay !== undefined && cashier.ccConfigs.worldpay.js_logging);
            let supplier = this;
            cashier.deposit.makeDepositRequest(supplier.endpoint, supplier.action, 'ccard', 'worldpay', supplier.options, supplier.rId);
        },
        postTransaction: function (action, rId, amount, options, endpoint) {
            saveFELogs('worldpay', 'debug', 'postTransaction',
                {
                    'action': action,
                    'rId': rId,
                    'amount': amount,
                    'options': options,
                    'endpoint': endpoint
                },
                {
                    'obfuscation': false,
                    'obfuscating_keys' : []
                },
                cashier.ccConfigs.worldpay !== undefined && cashier.ccConfigs.worldpay.js_logging
            );

            this.rId = rId;
            this.action = action;
            this.endpoint = endpoint;
            this.options = options;
            var supplier = this;

            showPermanentLoader(function () {
                $.post("/phive/modules/Cashier/html/worldpay_deposit_init.php", {amount: amount}, function (response) {
                    saveFELogs('worldpay', 'debug', 'Received token for DDC', {response, 'ddc_url': supplier.getDdcUrl()}, {}, cashier.ccConfigs.worldpay !== undefined && cashier.ccConfigs.worldpay.js_logging);
                    supplier.enableListener();
                    supplier.startIframe(
                        supplier.getDdcUrl(),
                        [
                            {name: 'Bin', value: supplier.getBin(rId)},
                            {name: 'JWT', value: response.data.token},
                        ]);
                }, "json").fail(function () {
                    cashier.deposit.resetRequestState();

                    pspHooks.error('error msg', {supplier: 'worldpay'});
                });
            });
        },
        startIframe: function(url, inputs, isIframe = true) {
            saveFELogs('worldpay', 'debug', 'Creating an iframe for DDC', {'url': url, 'inputs': inputs}, {}, cashier.ccConfigs.worldpay !== undefined && cashier.ccConfigs.worldpay.js_logging);
            let $iframe = $(isIframe ? '<iframe>' : '<div>');

            let $form = $('<form>', {
                action: url,
                method: 'post',
            });

            for (let input of inputs) {
                $form.append($('<input>', input));
            }

            if (isIframe) {
                $iframe.on('load', function () {
                    $(this).contents().find('body').append($form);
                    saveFELogs('worldpay', 'debug', 'Before Submitting DDC iframe', {'form_data' : JSON.stringify($form)}, {}, cashier.ccConfigs.worldpay !== undefined && cashier.ccConfigs.worldpay.js_logging);
                    $form.submit();
                    saveFELogs('worldpay', 'debug', 'After Submitting DDC iframe', {'form_data' : JSON.stringify($form)}, {}, cashier.ccConfigs.worldpay !== undefined && cashier.ccConfigs.worldpay.js_logging);
                });

                $('body:first').append($iframe.hide());
            } else {
                $form.appendTo($('body')).submit();
            }
        }
    },
    encryptCvc: function(cvc, rId){
        return cvc;
    }
};

pspHooks.pspGetPaymentiqConfig = function(psp) {
    return {
        encryptCvc: function(cvc, rId){
            var pubKey = cashier.ccGetPubKey(psp);
            return encryptData(cvc, pubKey);
        }
    };
};

pspHooks.bambora      = pspHooks.pspGetPaymentiqConfig('bambora');
pspHooks.epaysolution = pspHooks.pspGetPaymentiqConfig('epaysolution');
pspHooks.kluwp        = pspHooks.pspGetPaymentiqConfig('kluwp');
pspHooks.cleanpay     = pspHooks.pspGetPaymentiqConfig('cleanpay');
pspHooks.cardeye      = pspHooks.pspGetPaymentiqConfig('cardeye');

pspHooks.trustly = {
    withdrawCommon: function(res, w, h){
        hideLoader();

        if (!empty(res.url)) {
            goTo(res.url);
        } else {
            cashier.withdraw.withdrawComplete(res);
        }
    },
    mobWithdrawRes: function(res){
        this.withdrawCommon(res, 400, 600);
    },
    pcWithdrawRes: function(res){
        this.withdrawCommon(res, 600, 600);
    },
    mobRes: function(res, options){
        if(isIframe() && cashier.doIframe('trustly', res)){
            res.result.url += '&NotifyParent=1';
        }

        pspHooks.mobRes(res, options);
    }
};

pspHooks.googlepay = {
    depositCommon: function(options, onReturn) {
        options.supplier = 'googlepay';
        options.browserInfo = getBrowserInfo();

        showPermanentLoader(function() {
            $.post(theCashier.getEndpoint('googlepay'), options, function(res) {
                cashier.postingTransaction = false;

                hideLoader(function() {
                    var error = res['errors'] || res['error'];
                    if (res['fallback_deposit'] === 'trustly') {
                        showTrustlyDepositErrorPopup('googlepay');
                    } else {
                        onReturn(res, error);
                    }
                });
            }, "json");
        });
    },
    deposit: {
        postTransaction: function(action, rId, amount) {
            mgAjax({action: 'check-psp-limits', amount: amount, psp: 'googlepay'}, function(ret){
                if (ret !== 'ok') {
                    pspHooks.error(ret, {action: 'deposit', supplier: 'googlepay'});

                    return;
                }

                let environment = cashier.env === 'prod' ? 'PRODUCTION' : 'TEST';
                let googlePayDepositMids = cashier.googlePayConfig.mid.deposit['worldpay'];
                let googlePayMid = googlePayDepositMids[cashier.userData.country] ?? googlePayDepositMids['ROW'];
                let countryCode = cashier.userData.country;
                let currencyCode = cashier.userData.currency;
                let userHasRg65 = cashier.userData.hasRg65;
                amount = toTwoDec(amount);

                let allowCreditCards = true;
                let creditCardExcludedCountries = cashier.googlePayConfig.deposit['credit_card_excluded_countries'];
                if ((creditCardExcludedCountries && creditCardExcludedCountries.includes(countryCode))
                    || (userHasRg65 && countryCode === 'ES'))
                {
                    allowCreditCards = false;
                }

                GooglePay.init(environment, googlePayMid, allowCreditCards);
                GooglePay.initializeDeposit(countryCode, currencyCode, amount, cur_domain)
                    .then(paymentToken => {
                        let options = {
                            action: 'deposit',
                            amount: amount,
                            payment_token: paymentToken,
                            countryCode: countryCode,
                            currencyCode: currencyCode
                        };

                        pspHooks.googlepay.depositCommon(options, function(res, error) {
                            if (!empty(error)) {
                                pspHooks.error(error, options);
                            } else {
                                theCashier.showPaymentMsg(theCashier.successMsg, null, true, true);
                            }
                        });
                    })
                    .catch(err => console.error("Google Pay Error:", err));
            });
        }
    }
};

pspHooks.applepay = {
    depositCommon: function(options, onReturn){

        options.supplier = 'applepay';
        options.browserInfo = getBrowserInfo();

        showPermanentLoader(function(){
            $.post(theCashier.getEndpoint('applepay'), options, function(res){

                cashier.postingTransaction = false;

                hideLoader(function(){
                    var error = res['errors'] || res['error'];
                    if (res['fallback_deposit'] === 'trustly') {
                        showTrustlyDepositErrorPopup('applepay');
                    } else {
                        onReturn(res, error);
                    }
                });

            }, "json");
        });
    },
    deposit: {
        postTransaction: function(action, rId, amount){
            amount = toTwoDec(amount);

            var request = {
                countryCode: cashier.userData.country,
                currencyCode: cashier.userData.currency,
                supportedNetworks: ['visa', 'masterCard', 'amex', 'discover'],
                merchantCapabilities: ['supports3DS'],
                // TODO merchant name needs to be configurable and amount needs proper validation.
                total: { label: cur_domain, amount: amount}
            };

            if(lic('disallowsCreditCards')){
                request.merchantCapabilities.push('supportsDebit');
            }

            // Testing logic start
            /*
            var options = {
                extUrl: 'https://apple.com',
                action: 'validatemerchant'
            };

            pspHooks.applepay.depositCommon(options, function(res, error){
                if(!empty(error)) {
                    pspHooks.error(error, options);
                } else {

                    options = {
                        amount: amount,
                        action: 'deposit',
                        token: {
                            'paymentData': {
                                'header': {
                                    'ephemeralPublicKey': 'swfwefwdsfwef',
                                    'publicKeyHash': 'sdfsdfsfsdfsf',
                                    'transactionId': 324253423452354
                                },
                                'signature': 'wgrwregfwefwef',
                                'version': '1.1',
                                'data': 'sdfwrgwrwergfwefwefewf'
                            }
                        }
                    };

                    pspHooks.applepay.depositCommon(options, function(res, error){
                        if(!empty(error)) {
                            pspHooks.error(error, options);
                        } else {
                            isMobile() ? pspHooks.mobRes(res, options) : pspHooks.pcRes(res, options);
                        }
                    });
                }
            });
            return;
            //*/
            // Testing logic end

            var session = new ApplePaySession(3, request);

            session.onvalidatemerchant = function(event){

                var options = {
                    extUrl: event.validationURL,
                    action: 'validatemerchant'
                };

                pspHooks.applepay.depositCommon(options, function(res, error){
                    if(!empty(error)) {
                        pspHooks.error(error, options);
                    } else {
                        session.completeMerchantValidation(res.result);
                    }
                });

            };

            session.onpaymentauthorized = function(event){

                var options = {
                    amount: amount,
                    action: 'deposit',
                    payment_token: event.payment.token
                };

                pspHooks.applepay.depositCommon(options, function(res, error){
                    if(!empty(error)) {
                        pspHooks.error(error, options);
                        session.completePayment(ApplePaySession.STATUS_FAILURE);
                    } else {
                        session.completePayment(ApplePaySession.STATUS_SUCCESS);
                        if (cashier.isMobileApp) {
                            sendToFlutter({
                                type: 'html',
                                success: true,
                                data: theCashier.successMsg,
                                trigger_id: 'deposit',
                                debug_id: 'postTransaction.success.app'
                            });
                        } else {
                            theCashier.showPaymentMsg(theCashier.successMsg, null, true, true);
                        }
                    }
                });

            };

            session.begin();
        }
    }
};

pspHooks.zimpler = {
    iframeWh: [500, 700]
};

pspHooks.pix = {
    iframeWh: [630, 2400]
};

pspHooks.zimplerbank = {
    iframeWh: [500, 700],
    withdrawCommon: function(res){
        if(!empty(res.url)){
            toExtGo(res.url, '_self');
        } else {
            cashier.withdraw.withdrawComplete(res);
        }
        // mboxIframe(res.url, w, h, function(){ ajaxRefreshTopBalances(); }, '0px');
    },
    mobWithdrawRes: function(res){
        //this.withdrawCommon(res, 400, 600);
        this.withdrawCommon(res);
    },
    pcWithdrawRes: function(res){
        //this.withdrawCommon(res, 600, 600);
        this.withdrawCommon(res);
    }
};

/**
 * Convenience function for getting PSP hooks.
 *
 * Since some PSPs have really special flows we cater to them via hooks rather than cramming all the logic into the
 * postTransaction function. We instead call the hooks in it via this function.
 *
 * @param string psp The PSP.
 * @param string key The key to retrieve the hook with.
 * @param mixed defVal The default value, typically the default function / logic to execute if the PSP does not
 * have any special hooks at all.
 *
 * @return mixed The return value, typically a function.
 */
function getHookVar(psp, key, defVal){
    if(empty(pspHooks[psp])){
        return defVal;
    }

    if(empty(pspHooks[psp][key])){
        return defVal;
    }

    return pspHooks[psp][key];
}

function getCardData() {
    var exp1 = $('.dc_exp1').val();
    if(exp1){
        exp1 = exp1.length == 1 ? '0' + exp1 : exp1;
    }

    var cardData = {
        "cardnumber":  $('.dc_cardnumber').val(),
        "expirydate":  exp1 + "/" + $('.dc_exp2').val(),
        "expiryYear":  '20' + $('.dc_exp2').val(),
        "expiryMonth": exp1,
        "cv2":         $('.dc_cv2').val()
    };

    // We always send the obfuscated card number as we always need it in order to correctly route
    // depending on which issuer for instance.
    var cnum = cardData.cardnumber.replace(/ /g,'');
    cardData.cardHash = cnum.substr(0, 4) + ' ' + cnum.substr(4, 2) + '** **** ' + cnum.substr(cnum.length - 4);

    return cardData;
}

/**
 * Getting card data.
 *
 * Since we don't actually post any forms we need to get data via JS and this is the CC part.
 *
 * @return object The card data.
 */
function getCardParams(rId, cardData){
    var encryptionFailed = false;

    // We check if we're looking at a oneclick, if not we encrypt normally.
    if(!empty(rId)){
        // We need to encrypt the CVC in case of oneclick but not for Worldpay
        var cvc = $("#cvc").val() || cardData.cv2;
        var supplier = theCashier.repeatCards[rId].supplier;
        if(!empty(cvc) && !empty(pspHooks[supplier]['encryptCvc'])){
            var encryptedCvc = pspHooks[supplier].encryptCvc(cvc, rId);
            if(!encryptedCvc){
                encryptionFailed = true;
                return false;
            }
            cardData.cvc = encryptedCvc;
        }
    } else {
        // We need to loop all possible CC PSPs even the ones that are not the currently chosen one
        // since we need to encrypt data for them too in case the current one fails.
        _.each(cashier.ccConfigs, function(conf, ccPsp){
            if(!empty(conf['pub_key']) || !empty(conf['js_urls'])){
                var encryptedData = pspEncrypt(cardData, ccPsp);
                if(!encryptedData){
                    encryptionFailed = true;
                    // Something went wrong with the encryption;
                    return false;
                }

                cardData[ccPsp + '_encrypted_data'] = encryptedData;
            }
        });
    }

    if(encryptionFailed){
        return false;
    }

    // We are using a PCI compliant FE JS encryption scheme so we do not send card number and cvv.
    if(pciCompliant == true){
        delete cardData.cardnumber;
        delete cardData.cv2;
    }

    return cardData;
}

/**
* Does FE encryption of card numbers.
*
* Encrypts card data according to specs from the PSP in question.
*
* @param object obj The object to get the data to encrypt from.
* @param string psp The PSP to work with.
*
* @return string The encrypted data.
*/
function pspEncrypt(obj, psp){
    if(empty(psp)){
        psp = cuCcPsp;
    }

    cData = {
        expiryMonth: obj.expiryMonth,
        expiryYear:  obj.expiryYear,
        cvc:         obj.cv2
    };

    var holderName = $("input[extra-attr='adyen-holdername']").val();

    var conf = cashier.ccConfigs[psp];

    var pubKey = cashier.ccGetPubKey(psp);

    switch(psp){
        case 'credorax':
            // If we already have a prior deposit we never have to do this again, we just use the stored token.
            // When we have the token a normal deposit is just with 3D, and a repeat without 3D.
            if (typeof keyCreation === "function") {
                // keyCreation("M", "RequestId", "staticKey", "b1", "b3", "b4", "b5", "c1");
                var res = pspHooks.credorax.commonEncrypt(keyCreation, [
                    conf.mid[cashier.userData.country] ?? conf.mid['ROW'] ?? conf.mid,
                    Uuid(),
                    conf.static_key[cashier.userData.country] ?? conf.static_key['ROW'] ?? conf.static_key,
                    obj.cardnumber,
                    obj.expiryMonth,
                    obj.expiryYear.substring(2),
                    obj.cv2,
                    cashier.userData.firstname + ' ' + cashier.userData.lastname]);

                if(empty(res['PKey'])){
                    // Something went wrong.
                    return false;
                }

                res.expiryMonth = cData.expiryMonth;
                res.expiryYear = cData.expiryYear;
                return res;
            } else {
                return '';
            }
            break;
        case 'adyen':
            var options          = {};
            var cseInstance      = adyen.encrypt.createEncryption(pubKey, options);
            cData.holderName     = holderName.trim();
            cData.number         = obj.cardnumber;
            cData.generationtime = $("input[extra-attr='adyen-generationtime']").val();
            return cseInstance.encrypt(cData);
        case 'worldpay':
            Worldpay.setPublicKey(pubKey);
            cData.cardHolderName = holderName.trim();
            cData.cardNumber     = obj.cardnumber;
            return Worldpay.encrypt(cData, function(errorCodes){
                console.log(['Worldpay error code:', errorCodes]);
            });
        case 'cardeye':
        case 'cleanpay':
        case 'kluwp':
        case 'bambora':
        case 'epaysolution':
            cData.number = encryptData(obj.cardnumber, pubKey);
            cData.cvc    = empty(obj.cvc) ? encryptData(obj.cv2, pubKey) : obj.cvc;
            return $.toJSON(cData);
        case 'hexopay':
            var begateway = new BeGatewayCSE(pubKey);
            cData = _.mapObject(cData, function(val, key){
                return begateway.encrypt(val.trim());
            });

            cData.cardHolderName = begateway.encrypt(holderName.trim());
            cData.cardNumber     = begateway.encrypt(obj.cardnumber.trim());
            return $.toJSON(cData);
    }
}

/**
 * Resizes the iframe, does nothing if we're not in a iframe.
 *
 * @param int width The width of the iframe.
 * @param int height The height of the iframe.
 *
 * @return bool True if we're in an iframe, false otherwise.
 */
function resizeCashier(width, height){
    var windowHeight = window.parent.innerHeight;
    if(height >= windowHeight){
        var scrolling = true;
        height = windowHeight;
    }

    if(width && height && parent.$.multibox){
        _.each({"mbox-iframe-cashier-box": 'cashier-box', "mbox-iframe-fast-deposit-box": "fast-deposit-box"}, function(elId, iframeId){
            var iframe = parent.document.getElementById(iframeId);
            if(iframe){
                parent.$.multibox('resize', elId, width, height, true, scrolling);
                var scrollWidth = iframe.contentWindow.innerWidth - $(iframe.contentWindow).width(); // this gets width of the scroll.
                width += scrollWidth;
                parent.$.multibox('resize', elId, width, height, true, scrolling);
                var topBarHeight =  parent.$(".rg-top__container").height() || 0;
                var bottomBarHeight = (parent.$('.games-footer').height() || 0 ) + parseInt(parent.$('.games-footer').css('bottom') || 0);
                // remove from available space the top/bottom bars, if popup doesn't fit we reduce height.
                var windowHeightWithoutBars = windowHeight - (topBarHeight + bottomBarHeight);
                if(height >= windowHeightWithoutBars) {
                    height -= (topBarHeight + bottomBarHeight);
                }
                // we center the position only if it fits on the page, else on top.
                var topPosition = windowHeightWithoutBars >= height ? ((windowHeightWithoutBars - height) / 2) + topBarHeight : 0;
                parent.$('#' + elId).css({height: height, top: topPosition});
                parent.$('#' + iframeId).height(height - 30);
            }
        });
        return true;
    }
    return false;
}
/**
 * The blobal function respnosible for redirecting the user to the external deposit interface hosted by the PSP. Shows a
 * loader in case we're in an iframe.
 *
 * @param string link The link to go to.
 * @param string target Same as the href target attribute.
 * @param int width The width to resize the iframe to.
 * @param int height The height to resize the iframe to.
 *
 * @return undefined
 */
function toExtGo(link, target, width, height){
    target = empty(target) ? '_blank' : target;
    if(resizeCashier(width, height)){
        showLoader();
    }

    if(target == '_self'){
        window.location.href = link;
    } else {
        window.top.location.href = link;
    }
}

function mobileSpecificIframeRedirect(returnUrl, noIframeFlag) {
    const target = isMobile() || noIframeFlag ? '_parent' : '_self';
    toExtGo(returnUrl, target);
}

/**
 * Changes the bakground color of the cashier, needed for some PSPs in order to get black on white for instance
 *
 * @param string c The color, ex: white
 *
 * @return undefined
 */
function setIframeColor(c){
    $(".mbox-deposit-content", window.parent.document).css("background-color", c);
    $(".mbox-deposit-outer", window.parent.document).css('background-color', c);
    $(".mbox-deposit-wrap", window.parent.document).css('background-color', c);
    $("#mbox-iframe-fast-deposit-box", window.parent.document).css('background-color', c);
}

/**
 * The trivial and homegrown class handling.
 *
 * Let's take desktop deposits as an example:
 * * 1.) Here we first loop all the attributes of the global **cashier** object and add them to the global theCashier object.
 * * 2.) Then we do the same with the **cashier.deposit** object.
 * * 3.) Finally we loop the **cashier.withdraw.desktop** object.
 *
 * The end result is that we end up with a kind of class hierarchy where the children can override parent methods.
 *
 * @param object obj The final child object, in this case **cashier.withdraw.desktop**.
 *
 * @return void
 */
function setTheCashier(obj){
    var action = obj.action;

    // The base class
    _.each(cashier, function(val, key){
        if(key != 'tpls' || key != 'templates'){
            theCashier[key] = val;
        }
    });

    // The action class
    _.each(cashier[action], function(val, key){
        theCashier[key] = val;
    });

    // The channel object
    _.each(obj, function(val, key){
        if(key != action){
            theCashier[key] = val;
        }
    });
}

/**
 * Replaces a CSS class on an HTML element.
 *
 * @param string $elementId The ID of the HTML element.
 * @param string $classNameToRemove The class name to be removed after matching the containing string.
 * @param string $newClassName The new class name to be added.
 * @return void
 */
function replaceClass(elementId, classNameToRemove, newClassName) {
    const element = document.getElementById(elementId);
    element.classList.forEach((currentClassName) => {
        if (currentClassName.includes(classNameToRemove)) {
            element.classList.remove(currentClassName);
        }
    });
    element.classList.add(newClassName);
}

function formatCashierAmount(amount, currencySymbol = '', delimiter = ' ') {
    const formattedAmount = amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, delimiter);
    return `${currencySymbol ? currencySymbol + ' ' : ''}${formattedAmount}`;
}

/**
 * Handles initialization of event handlers for edge case PayAnyBank withdrawals with GB IBAN and EUR user currency.
 * swift_bic field with "disabled" attribute indicates that the above-mentioned edge case should be handled.
 *
 * @return void
 */
function initPayAnyBankWithdrawForm() {
    let payAnyBankForm = $('#withdrawForm-payanybank');

    if (payAnyBankForm) {
        let swiftBicInput = payAnyBankForm.find('input[name="swift_bic"]');

        if (swiftBicInput.prop('disabled')) {
            let swiftBicLabel = swiftBicInput.prev('div');
            let swiftBicRequiredLabel;

            swiftBicInput.hide();
            swiftBicLabel.hide();

            $(document).on('input', payAnyBankForm.find('input[name="iban"]'), function() {
                let countryCode = $(this).val().replace(/\s/g, '').slice(0,2);

                if (countryCode.toUpperCase() === 'GB') {
                    swiftBicInput.show();
                    swiftBicInput.attr('disabled', false);
                    swiftBicInput.attr('required', true);

                    swiftBicLabel.show();
                    if (swiftBicRequiredLabel) {
                        swiftBicRequiredLabel.show();
                    }
                } else {
                    swiftBicInput.hide();
                    swiftBicInput.val('');
                    swiftBicInput.attr('disabled', true);
                    swiftBicInput.attr('required', false);

                    swiftBicLabel.hide();
                    swiftBicRequiredLabel = $('label[for="swift_bic"]');
                    swiftBicRequiredLabel.hide();
                }
            });
        }
    }
}

function getBrowserInfo() {
    return {
        javaEnabled: navigator.javaEnabled() || false,
        language: navigator.language || "",
        colourDepth: screen.colorDepth || 0,
        screenHeight: screen.height || 0, // For viewportHeight: window.innerHeight
        screenWidth: screen.width ||  0, // For viewportWidth: window.innerWidth
        javaScriptEnabled: true, // Since this function runs, JavaScript is enabled
        timeZone: new Date().getTimezoneOffset() || 0
    };
}

function hasEndParameter() {
    const urlParams = new URLSearchParams(top.window.location.search);
    let hasEnd = urlParams.has('end');

    if (!hasEnd) {
        const showUrl = urlParams.get('show_url');
        if (showUrl) {
            const showUrlParams = new URLSearchParams(new URL(decodeURIComponent(showUrl)).search);
            hasEnd = showUrlParams.has('end');
        }
    }

    return hasEnd;
}

$(document).ready(function(){

    let isFastDeposit = false;
    let iframe = $('#fast-deposit-box', parent.document);

    if (iframe.length > 0) {
        let iframeContent = iframe.contents();
        let elementInsideIframe = iframeContent.find('#mbox-iframe-fast-deposit-box');
        isFastDeposit = elementInsideIframe.length > 0;
    }

    if (!isMobile() && !isFastDeposit) {
        resizeCashier(cashierWidth, cashierHeight);
    }

    Handlebars.registerHelper('nfCents', nfCents);

    _.each(cashier.tpls, function(id){
        cashier.templates[id] = Handlebars.compile($('#'+id).html());
    });

    theCashier.init();
});
