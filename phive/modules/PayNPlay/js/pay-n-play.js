const PayNPlay = {
    // Constants
    LOGIN_WITH_DEPOSIT: 'login_with_deposit',
    LOGIN_WITHOUT_DEPOSIT: 'login_without_deposit',
    STRATEGY_TRUSTLY: 'strategy_trustly',
    STRATEGY_SWISH: 'strategy_swish',

    // Properties
    _isMessageListenerActive: false,
    _loginType: "",
    _depositAmount: 0,
    _strategy: 'strategy_trustly',
    _strategy_step: 1,

    depositStart: function (){
        showLoader( () => {
            this.checkStrategy();
            var step = 1;

            this.deposit(true, this._strategy, step);
        });
    },

    depositFromAccount: function(){
        showLoader( () => {
            this.checkStrategy();
            var step = 1;

            if (this._strategy == this.STRATEGY_SWISH) {
                step = 2;
            }

            this.deposit(true, this._strategy, step);
        });
    },

    deposit: function (check = true, strategy = null, step = null) {
        const container = $('.deposit_popup:visible, .confirm_popup:visible').first();
        if (check && !this.checkInput(container)) {
            return;
        }


        if(strategy){
            this._strategy = strategy;
        } else {
            this.checkStrategy();
        }

        if(step){
            this._strategy_step = step;
        }

        this._loginType = this._depositAmount > 0 ? this.LOGIN_WITH_DEPOSIT : this.LOGIN_WITHOUT_DEPOSIT;

        mgAjax({
            action: 'get_paynplay_iframe_url',
            amount: this._depositAmount,
            'strategy': this._strategy,
            'strategy_step': this._strategy_step
        }, function (ret) {
            const response = JSON.parse(ret);

            if (!response.success) {
                mboxClose('paynplay-box', () => {
                    this.showErrorPopup(response.error);
                });
                return;
            }

            var url = response.result.url;
            var targetDiv = $('main.deposit_bank_details__main_content');

            targetDiv.find('iframe').remove();

            var iframe = $('<iframe>', {
                id: 'paynplay_iframe',
                src: url
            });

            targetDiv.append(iframe);

            this.showPopup("deposit_bank_details_section", true, this._loginType === this.LOGIN_WITHOUT_DEPOSIT ? 'login-title' : null);
        }.bind(this));

        this.initMessageListener(this._loginType);
    },

    initMessageListener: function (loginType) {
        // Listen for postmessage from iframe
        if(!this._isMessageListenerActive){
            window.addEventListener("message", function (event) {
                console.log(event);
                if (event.origin === window.location.origin && event.data.type === "paynplay") {
                    const result = event.data.result;
                    const action = event.data.action;
                    result.type = loginType;
                    if (action === "login") {
                        this.callBackFromBankId(result);
                    } else if (action === "deposit"){
                        this.callBackFromPayNPlay(result);
                    } else if (action === "error") {
                        const result_error = result.error;
                        this.showErrorPopup(result_error);
                    } else if (action === "swish-redirect"){
                        this.swishRedirect(result);
                    } else if (action === "trustly-select-account"){
                        //TODO: for select account we always show success message, because failed transactions can be retried from admin2
                        this.showWithdrawalSuccessPopup();
                    }
                }
            }.bind(this), false);
        }

        this._isMessageListenerActive = true;
    },

    swishRedirect: function (result){
        window.location.href = result.url;
    },

    loginWithoutDeposit: function () {
        showLoader( () => {
            $('input#amount_value').val(0);
            this._strategy_step = 1;
            this._depositAmount = 0;
            this.deposit(false);
        });
    },

    callBackFromBankId: function (result) {
        if (this._strategy !== this.STRATEGY_SWISH) {
            throw new Error('Unsupported strategy');
        }

        if (this._depositAmount) {
            this._strategy_step = 2;

            //user is limited to deposit
            if(result.limit){
                this.showDepositConfirmPopup(result.limit);
                return;
            }

            this.deposit(false);
        } else {
            this.callBackFromPayNPlay(result);
        }
    },

    callBackFromPayNPlay: function (result) {
        if (result.status === "ACCEPT") {

            if(result.limit && result.type == this.LOGIN_WITH_DEPOSIT){
                this.showDepositConfirmPopup(result.limit);
                return;
            }

            mgAjax({action: "login_paynplay_after_redirect", transaction_id: result.transaction_id}, function (ret) {
                const response = JSON.parse(ret);

                if (!response.success) {
                    // this.showErrorPopup(response.error);
                    this.showErrorPopup('blocked');
                    return;
                }

                // if login with deposit
                if(result.type === this.LOGIN_WITH_DEPOSIT) {
                    var userId = response.result.userId;
                    var firstDeposit = response.result.showWelcomeActivationPopup;
                    var myProfileLink = response.result.myProfileLink;

                    this.depositSuccess(userId, firstDeposit, myProfileLink);
                } else {
                    // if login without deposit
                    mboxClose('paynplay-box', () => {
                        this.showErrorPopup("login-success", {
                            boxtitle: 'paynplay.error.login-success.title'
                        });
                    })
                }

                // deposit-response
                $("#deposit_success_section .success-btn").on("click", function () {
                    window.location.href = "";
                });
                this.closePNPPopup();
            }.bind(this));
        } else {
            if (result.message == 'deposit-reached') {
                this.showErrorPopup('deposit-reached');
            } else if (result.message == 'deposit-block') {
                this.showErrorPopup('deposit-block');
            } else if (result.message == 'self-excluded') {
                this.showErrorPopup('self-excluded');
            } else if (result.message === 'Registration without deposit is not allowed') {
                this.showErrorPopup(result.message);
            } else if (result.message === 'blocked') {
                this.showErrorPopup(result.message);
            } else {
                let responseContent = result.message;
                mboxClose('paynplay-box', () => {
                    this.showErrorPopup(responseContent, {
                        resultMessage: responseContent,
                        boxtitle: 'paynplay.deposit'
                    });
                });
            }

        }
    },

    depositConfirm: function (amount) {
        const container = $('.deposit_popup:visible, .confirm_popup:visible').first();

        if(this.checkInput(container)){
            this._strategy = 'strategy_swish';
            this._strategy_step = 2;
            this._depositAmount = amount;
            this.deposit();
            closePopup( 'pnp_confirm_popup', false, false);
        }
    },

    depositSuccess: function (userId, firstDeposit, myProfileLink) {
        var sectionId = (userId && firstDeposit) ? "deposit-notification__first_success-section" : "deposit_success_section";

        this.showPopup(sectionId);

        $("#" + sectionId + " .activate-btn").on("click", function () {
            mgAjax({action: 'activate-welcome-offers', 'user_id': userId}, function () {
                window.location.href = myProfileLink;
            });
        });

        $("#" + sectionId + " .close-btn").on("click", function () {
            window.location.href = "";
        });
    },

    depositFailure: function () {
        this.showPopup("deposit_failure_section");
    },
    showPopup: function (newTab, updateTitle = true, titleText = null) {
        const activeTab = this.getActiveTab();
        $("#" + activeTab).hide();
        $("#" + newTab).show();

        $("#paynplay-box").removeClass(activeTab).addClass(newTab);
        updateTitle && this.updateTitleText("#" + newTab, titleText);
        this.checkStrategy();

        $.multibox('posMiddle', "paynplay-box");
    },

    checkStrategy: function () { console.log('test');
        if ($('#strategy_swish_radio').length && $('#strategy_swish_radio').is(':checked')) {
            this.selectStrategy('swish');
        } else if ($('#strategy_trustly_radio').length && $('#strategy_trustly_radio').is(':checked')) {
            this.selectStrategy('trustly');
        }
    },

    getActiveTab: function () {
        const classArr = $('#paynplay-box').attr("class").split(" ");
        return classArr.map(function (className, index) {
            return (className.match(/(^|\s)deposit_\S+/g) || []);
        }).join(' ').trim();
    },

    updateTitleText: function (selector, titleText) {
        const title = $(selector).attr(titleText ?? 'popup-title');
        $('.lic-mbox-title').text(title);
    },

    changeAmount: function (amountId, amountValue) {
        const container = $('#' + amountId).closest('.deposit_popup__amounts').prev('.deposit_popup__amount');
        container.find('.amount_value').val(amountValue);
        this.checkInput(container);
    },

    withdraw: function () {
        var amount = $("#amount_value").val();

        let $invalidAmount = $('.red.invalid-amount');
        let $invalidAmountPositive = $('.red.invalid-amount-positive');
        let $invalidNumeric = $('.red.invalid-numeric');

        function showErrorMessage(showElement) {
            $invalidAmount.hide();
            $invalidAmountPositive.hide();
            $invalidNumeric.hide();

            if (showElement) {
                showElement.show();
            }
        }

        if (amount !== undefined && amount !== null) {
            if (amount === '') {
                showErrorMessage($invalidAmount);
                return;
            }

            if (!/^-?\d+(\.\d+)?(?![eE])$/.test(amount)) {
                showErrorMessage($invalidNumeric);
                return;
            }

            amount = parseFloat(amount);

            if (amount === 0) {
                showErrorMessage($invalidAmount);
                return;
            } else if (amount < 0) {
                showErrorMessage($invalidAmountPositive);
                return;
            }
        } else {
            showErrorMessage($invalidAmount);
            return;
        }

        let postData;
        this.checkStrategy();
        switch (this._strategy) {
            case "strategy_trustly": {
                postData = {
                    action: 'withdraw',
                    supplier: 'trustly',
                    amount: amount
                };
                break;
            }
            case "strategy_swish": {
                postData = {
                    action: 'withdraw',
                    supplier: 'swish',
                    amount: amount
                };
                break;
            }
        }

        $.ajax({
            type: 'POST',
            url: '/phive/modules/Cashier/html/ebank_start.php',
            data: postData,
        })
        .done(function (res) {
            const response = JSON.parse(res);
            console.log(response);
            if (response.success && response.result == 'ok') {
                PayNPlay.showWithdrawalSuccessPopup();
            } else if (response.success && response.result.url) {
                PayNPlay.selectTrustlyAccount(response.result.url);
            } else {
                PayNPlay.showWithdrawalFailurePopup('withdrawal-failed', {
                    resultMessage: response.errors
                });
            }
        })
        .fail(function (error) {
            PayNPlay.showWithdrawalFailurePopup('withdrawal-failed');
        });
        mboxClose('paynplay-box');
    },

    onErrorPopupLimitIncreaseRequested: function () {
        //Todo Handle limit increase request
    },

    onErrorPopupClosed: function (popup = "") {
        mboxClose('paynplay-box');
        mboxClose('pnp_error_popup');
        if (popup === 'login-success') {
            window.location.href = "";
        }
    },

    onWithdrawalSuccess: function () {
        ajaxRefreshTopBalances().then(() => {
            mboxClose('paynplay-box');
            mboxClose('pnp_success_popup');
        });
    },


    selectStrategy: function (strategy) {
        $('#strategy_swish').removeClass('active');
        $('#strategy_trustly').removeClass('active');

        if (strategy === 'swish') {
            $('#strategy_swish').addClass('active');
            $('#strategy_swish_radio').prop('checked', true);
            $('.swish-fee').show();
            this._strategy = this.STRATEGY_SWISH;
        } else if (strategy === 'trustly') {
            $('#strategy_trustly').addClass('active');
            $('#strategy_trustly_radio').prop('checked', true);
            $('.swish-fee').hide();
            this._strategy = this.STRATEGY_TRUSTLY;
        }
    },

    enableAmountChange: function (event) {
        event.preventDefault();
        var amountInput = $(this).siblings('.amount_value');
        amountInput.trigger("click");
    },

    focusInput: function (e) {
        var container = $(e.currentTarget).closest('.deposit_popup__amount');
        var amountValueElement = container.find('.amount_value');
        amountValueElement.focus();

        amountValueElement.off('change').on('change', function () {
            PayNPlay.checkInput(container);
        });

        //Place cursor at the end of the input value
        setTimeout(function () {
            var value = amountValueElement.val();
            amountValueElement.val('').val(value);  // This resets the value, placing the cursor at the end
        }, 0);
    },

    checkInput: function (container) {
        var inputValue = container.find('.amount_value').val();
        var minValue = container.find('.amount_min').val();
        var maxValue = container.find('.amount_max').val();

        container.find(".error").hide();

        // Check if the value is an integer, not null, not undefined, and not zero
        if (
            inputValue === null ||
            inputValue === undefined ||
            isNaN(parseInt(inputValue)) ||
            !isFinite(inputValue) ||
            parseInt(inputValue) === 0
        ) {
            container.find(".deposit_popup__amount_value").addClass('input-error');
            container.find(".amount-incorrect").show();
            return false;
        }

        if (inputValue * 100 < minValue) {
            container.find(".deposit_popup__amount_value").addClass('input-error');
            container.find(".amount-min").show();
            return false;
        }

        if (inputValue * 100 > maxValue) {
            container.find(".deposit_popup__amount_value").addClass('input-error');
            container.find(".amount-max").show();
            return false;
        }
        this._depositAmount = inputValue;

        container.find(".deposit_popup__amount_value").removeClass('input-error');
        return true;
    },

    showDepositConfirmPopup: function (limit) {
        var extraOptions = isMobile()
            ? {
                width: '100vw',
                height: '100vh',
                containerClass: 'flex-in-wrapper-popup button-fix--mobile',
            }
            : {width: '448px', containerClass: 'flex-in-wrapper-popup'};


        extraOptions.callb = function () {
            $("#pnp_confirm_popup, #multibox-overlay-pnp_confirm_popup").css("z-index", function(index, value) {
                return parseInt(value) + 1;
            });
        }

        var params = {
            module: 'PayNPlay',
            file: 'deposit_confirm_popup',
            boxid: 'pnp_confirm_popup',
            boxtitle: 'paynplay.confirm',
            closebtn: 'no',
            limit: limit,
            deposit_amount: this._depositAmount
        };

        extBoxAjax('get_html_popup', 'pnp_confirm_popup', params, extraOptions);
    },

    closeConfirmationPopup: function (){
        this._strategy_step = 1;
        closePopup('pnp_confirm_popup', true, false);
        closePopup('paynplay-box', true, false);
    },

    selectTrustlyAccount: function (iframe) {
        var extraOptions = isMobile()
            ? {
                width: '100vw',
                height: '100vh',
                containerClass: 'flex-in-wrapper-popup button-fix--mobile',
            }
            : {width: '448px', height: '600px', containerClass: 'flex-in-wrapper-popup'};

        $.multibox({
            url: iframe,
            id: 'paynplay-box',
            name: 'paynplay-box',
            type: 'iframe',
            cls: 'mbox-deposit',
            globalStyle: {overflow: 'auto'},
            baseZIndex: 10000,
            overlayOpacity: 0.7,
            // we don't want to use legacy scrolling attribute for iframe
            // see `phive/js/multibox.js`
            useIframeScrollingAttr: false,
            enableScrollbar: true,
            ...extraOptions,
            onClose: function () {
                top.$.multibox('toggleOverflow', false);
            },
            onComplete: function(){
                $('html').css({height: '100vh', overflow: 'auto'});
                $.multibox('posMiddle', 'paynplay-box');
            }
        });

        this.initMessageListener(this._loginType);
    },

    showWithdrawalPopup: function () {
        var extraOptions = isMobile()
            ? {
                width: '100vw',
                height: '100vh',
                containerClass: 'flex-in-wrapper-popup button-fix--mobile',
            }
            : {width: '450px', containerClass: 'flex-in-wrapper-popup'};

        var params = {
            module: 'PayNPlay',
            file: 'withdrawal_popup',
            boxid: 'base_deposit_popup',
            boxtitle: 'paynplay.withdraw',
            closebtn: 'yes',
        };

        extBoxAjax('get_html_popup', 'paynplay-box', params, extraOptions);
    },

    showWithdrawalSuccessPopup: function () {
        var extraOptions = isMobile()
            ? {
                width: '100vw',
                height: '100vh',
                containerClass: 'flex-in-wrapper-popup button-fix--mobile',
            }
            : {width: '388px', containerClass: 'flex-in-wrapper-popup'};

        var params = {
            module: 'PayNPlay',
            file: 'withdrawal_success',
            boxid: 'base_deposit_popup',
            closebtn: 'yes',
        };

        extBoxAjax('get_html_popup', 'pnp_success_popup', params, extraOptions);
    },
    showWithdrawalFailurePopup: function (popup, attrs = {}) {
        var extraOptions = isMobile()
            ? {
                width: '100vw',
                height: '100vh',
                containerClass: 'flex-in-wrapper-popup pnp-error-popup-wrapper withdrawal-error-popup-wrapper button-fix--mobile',
            }
            : {width: '340px', containerClass: 'flex-in-wrapper-popup pnp-error-popup-wrapper withdrawal-error-popup-wrapper'};

        var params = {
            module: 'PayNPlay',
            file: 'withdrawal_failed',
            boxid: 'withdrawal_failed_popup',
            closebtn: 'yes',
            popup: popup,
            ...attrs
        };

        extBoxAjax('get_html_popup', 'pnp_error_popup', params, extraOptions);
    },

    showErrorPopup: function (popup, attrs = {}) {
        mboxClose('paynplay-box');

        var extraOptions = isMobile()
            ? {
                width: '100vw',
                height: '100vh',
                containerClass: 'flex-in-wrapper-popup pnp-error-popup-wrapper button-fix--mobile',
            }
            : {width: '448px', containerClass: 'flex-in-wrapper-popup pnp-error-popup-wrapper'};

        var params = {
            module: 'PayNPlay',
            file: 'error_popup',
            boxid: 'pnp_error_popup',
            closebtn: 'yes',
            popup: popup,
            ...attrs
        };

        extBoxAjax('get_html_popup', 'pnp_error_popup', params, extraOptions);
    },

    showUserDetailsPopup: function (wishToReceiveMarketing = false) {
        const extraOptions = isMobile() ? {width: '100vw', height: '100vh', containerClass: 'flex-in-wrapper-popup button-fix--mobile'} : {width: '768px'};

        const params = {
            module: 'PayNPlay',
            file: 'user_details_popup',
            boxid: 'pnp_user_details_popup',
            boxtitle: 'paynplay.user-details.popup-title',
            extra_css: 'pnp-user-details__popup',
            closebtn: 'no'
        };

        extBoxAjax('get_html_popup', 'paynplay-box', params, extraOptions);
    },

    initUserDetailsForm: function (boxId) {
        const form = $('#pnp-user-details-form');
        form.validate({
            rules: {
                email: 'required email',
                phone: 'required number',
                nationality: 'required'
            },
            highlight: function (element) {
                $(element).addClass('error');
                const messageId = '#' + $(element).attr('id') + '_msg';
                $(messageId).show();
            },
            unhighlight: function (element) {
                $(element).removeClass('error');
                const messageId = '#' + $(element).attr('id') + '_msg';
                $(messageId).hide();
            },
            errorPlacement: function () {
            },
            submitHandler: form => {
                const userDetails = {
                    email: $('#email').val(),
                    mobile: $('#mobile').val(),
                    nationality: $('#nationality').val(),
                    country_prefix: $('#pnp-user-details-country-prefix-input').val()
                };

                const toupdate = {
                    email: userDetails.email,
                    mobile: userDetails.mobile,
                    nationality: userDetails.nationality,
                    country_prefix: userDetails.country_prefix
                };

                mgAjax({action: 'pnp-update-userinfo', toupdate, 'send-opt': true}, function (res) {
                    const data = JSON.parse(res);
                    if (!data.success) {
                        for (var field in data.messages) {
                            if (data.messages.hasOwnProperty(field)) {
                                var $target = $('#' + field);
                                addClassError($target, false);
                                var selector_msg = "#" + field + "_msg";
                                $(selector_msg).text(data.messages[field]).show();
                                if ($(selector_msg).length == 0) {
                                    if (!isNumber(field)) {
                                        $("#errorZone").html(field.toUpperCase() + " : " + data.messages[field]).show();
                                    } else {
                                        $("#errorZone").html(data.messages[field]).show();
                                    }
                                }
                            }
                        }

                        return;
                    }
                    mboxClose(boxId, () => this.showAccountVerificationPopup(userDetails));
                }.bind(PayNPlay));
            },
        });
    },

    showAccountVerificationPopup: function (userDetails) {
        const extraOptions = isMobile() ? {width: '100vw', height: '100vh'} : {width: '448px'};

        const params = {
            module: 'PayNPlay',
            file: 'account_verification_popup',
            boxid: 'pnp_account_verification_popup',
            boxtitle: 'paynplay.account-verification.popup-title',
            closebtn: 'no',
            extra_css: 'pnp-account-verification__popup',
            user_details: userDetails,
        };

        extBoxAjax('get_html_popup', 'paynplay-box', params, extraOptions);
    },

    //@todo: Remove shouldShowPrivacyConfirmation
    initAccountVerificationForm: function (boxId, shouldShowPrivacyConfirmation) {
        const form = $('#pnp-account-verification-form');
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
                const email_code = $('#email_code').val();
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
                                addClassError($target);
                                $('.field-' + field).find(".info-message").text(data.messages[field]).show();
                            }
                        }
                        return;
                    }

                    jsReloadBase();
                    mboxClose(boxId, () => this.sendWelcomeMail());
                }.bind(PayNPlay))
            },
        });
    },

    sendWelcomeMail: function () {
        mgAjax({action: 'pnp-welcome-mail'}, function () {
        }.bind(PayNPlay))
    },

    codeCallback: function (ret) {
        $("#infotext").html(ret);
    },
    resendVerificationCode: function () {
        mgSecureAjax({action: 'send-sms-code'}, PayNPlay.codeCallback);
        mgAjax({action: 'send-email-code'}, PayNPlay.codeCallback);
        $(this).show();
    },

    //@todo: Remove wishToReceiveMarketing
    navigateBackToUserDetails: function (boxId, wishToReceiveMarketing = false) {
        mboxClose(boxId, () => this.showUserDetailsPopup(wishToReceiveMarketing));
    },

    closePNPPopup: function () {
        $(".lic-mbox-close-box").on("click", function () {
            window.location.href = "";
        });
    },

    closeDepositToPlayPopup: function () {
        mboxClose('paynplay-box');
        mboxClose('pnp_error_popup', showPayNPlayPopupOnDeposit);
    }
}
