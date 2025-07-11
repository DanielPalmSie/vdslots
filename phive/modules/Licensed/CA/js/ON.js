$(function () {
    licFuncs.initExtraCheckOnLogin();
})

licFuncs.initExtraCheckOnLogin = function () {

    const activeClass = 'lic-mbox-btn-active';
    const disabledClass = 'lic-mbox-btn-inactive btn-disabled'
    const loginBtn = "#login-btn";

    $(document).on('change', '#fitForPlay', function () {
        if(this.checked) {
            $(loginBtn).removeClass(disabledClass).addClass(activeClass);
        }else {
            $(loginBtn).addClass(disabledClass).removeClass(activeClass);
        }
    });

    $(document).on('click', '.fit_for_play__info-icon', function () {
        const box_id = $(".fit_for_play .login_box_id").val();
        $(".fit_for_play__description").toggle();
        $.multibox('posMiddle', box_id);
    });
}

licFuncs.onRgReady = function(options){
    const lockRadio = $("input[name='lock_duration']");
    const otherRadio = $('#ca-other');
    const lockInput = $('#lock-hours');

    lockRadio.click(function (){
        $("#ca-lock-txt-holder").hide();
        $("#ca-other").prop("checked",false);
    });

    otherRadio.click(function(){
        $("#ca-lock-txt-holder").show();
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

licFuncs.beforeDeposit = function (){
    if (!window.top.GeocomplyGlobalCheck) {
        return;
    }

    window.top.GeocomplyGlobalChecks.initPreloader();
    window.top.GeocomplyGlobalChecks.forcerequest('Check before deposit');

}

licFuncs.beforeWithdraw = function () {
    if (!window.top.GeocomplyGlobalCheck) {
        return;
    }

    window.top.GeocomplyGlobalChecks.initPreloader();
    window.top.GeocomplyGlobalChecks.forcerequest('Check before withdraw');
}


licFuncs.showPrepaidDepositLimitPopup = function (total_prepaid_deposits) {
    var prepaidDepositPopupHandler = licFuncs.prepaidPopupHandler();
    prepaidDepositPopupHandler.showPrepaidLimitPopup(
        'prepaid_deposit_limit_reached_popup',
        'prepaid.deposit.limit.title',
        'prepaid.deposit.limit.description',
        {
            total_prepaid_deposits: total_prepaid_deposits,
        }
    );
}

licFuncs.showPrepaidMethodUsageLimitPopup = function () {
    var prepaidMethodUsageLimitPopupHandler = licFuncs.prepaidPopupHandler();
    prepaidMethodUsageLimitPopupHandler.showPrepaidLimitPopup(
        'prepaid_method_usage_limit_reached_popup',
        'prepaid.method.usage.limit.reached.title',
        'prepaid.method.usage.limit.reached.description'
    );
}

licFuncs.prepaidPopupHandler = function (){
    return {
        getCommonPopupParams: function (popup, title, description, params) {
            return {
                module: 'Licensed',
                file: popup,
                boxtitle: 'msg.title',
                closebtn: 'no',
                top_left_icon: true,
                title: title,
                description: description,
                ...params
            };
        },

        getCommonPopupExtraOptions: function (width) {
            return isMobile() ? {width: '100%', containerClass: 'flex-in-wrapper-popup button-fix--mobile'} : {width: width, containerClass: 'flex-in-wrapper-popup'};
        },

        showPrepaidLimitPopup: function (popup, title, description, params = {}) {
            var boxParams = this.getCommonPopupParams(popup, title, description, params),
                extraOptions = this.getCommonPopupExtraOptions(450);
            extBoxAjax('get_html_popup', 'prepaid-limit-popup-box', boxParams, extraOptions, top);
        },

        submitHandler: function () {
            top.$.multibox('close', 'prepaid-limit-popup-box');
        }
    }
}

licFuncs.showCompanyDetailsPopup = function (fromDeposit = false) {
    const params = {
        file: 'company_details_popup',
        boxtitle: 'company-details-popup.title',
        module: 'Licensed',
        redirect_on_mobile: 'no',
        from_deposit: fromDeposit,
    };

    const extraOptions = {
        width: isMobile() ? '100%' : '465px',
        height: isMobile() ? '100%' : '550px',
        onClose: () => {
            saveAccCommon('set_company_details_popup_shown_flag', {}, execNextPopup);
        },
    };

    extBoxAjax('get_html_popup', 'company-details-popup', params, extraOptions)
}
