licFuncs.startExternalVerification = function (context) {
    $("#lic-login-errors").html("");

    licJson('verifyRedirectStartWrapper', {
        username: $('#nid-field').val(),
        password: $("#password-field").val(),
        password_required: $("#nid-password").css('display') !== 'none',
        context: context
    }, function (res) {
        if (res.success === false) {
            $("#lic-login-errors").addClass("error").html(res.result);
        } else if (!empty(res.action)) {
            eval('window.top.' + res.action.method).apply(null, res.action.params);
        }
    }, 'json');
};
licFuncs.showPassword = function() {
    $("#nid-field").hide();
    $("#nid-password").show();
};

licFuncs.invalidRegistrationStep2 = function () {
    var $checkbox = $("#honest_player");
    if (!$checkbox.is(":checked")) {
        $checkbox.parent().find('span').addClass('error');
        return true;
    }
    $checkbox.parent().find('span').removeClass('error');

    var $elements = ['#iban', '#citizen_service_number', '#birth_place', '#doc_number'];

    var $isError = false;

    $.each( $elements, function( index, elementID) {
        var element = $(elementID);
        if (element.is(':visible') && element.val() === '') {
            addClassError(element);
            $isError = true;
        }
    });
    return $isError;
};

licFuncs.validateRegistrationStep2 = function () {
    $('#iban').attr('name', 'iban');

    $('#iban').off('blur.NL').on('blur.NL', function () {
        $('#iban').validate();
    });
};

$(function () {
    var logoutLink = 'a[href*="/?signout=true"]',
        popupProceedButtonId = '#rg-login-popup-proceed',
        rgLimitPopupHandler = licFuncs.rgLimitPopupHandler();

    $(document).on('click', logoutLink, function (event) {
        event.preventDefault();
        rgLimitPopupHandler.showGamingExperiencePopup({action: 'logout'});
    });

    $(document).on('click', popupProceedButtonId, function (event) {
        event.preventDefault();
        rgLimitPopupHandler.continueOnRgLoginPopup($(this).data('action'));
    });

    rgLimitPopupHandler.checkPopups();
});

licFuncs.rgLimitPopupHandler = function () {
    return {
        timeSpans: [
            'day',
            'week',
            'month'
        ],
        continueOnRgLoginPopup: function (action) {
            if (action === 'logout') {
                goTo('/?signout=true');
                return;
            }
            closePopup( 'rg-login-box', false, false);
        },
        showGamingExperiencePopup: function (options) {
            extBoxAjax('get_login_rg_info', 'rg-login-box', options);
        },
        checkPopups: function (justSaved) {
            var self = this;
            mgAjax({action: "init-post-registration-popup"}, function (response) {
                if (response.reload && justSaved) {
                    location.reload();
                }
                if (response.show_popup) {
                    self.showLimitPopup(response.popup);
                }
            });
        },
        showLimitPopup: function (popup) {
            var params = this.getCommonPopupParams(popup),
                extraOptions = this.getCommonPopupExtraOptions();

            params.file = popup;
            if (popup === 'register_set_deposit_limit_popup') {
                params.boxtitle = 'registration.set.deposit.limit.header.text';
            } else if (popup === 'register_set_login_limit_popup') {
                params.boxtitle = 'registration.set.login.limit.header.text';
            } else if (popup === 'register_set_balance_limit_popup') {
                params.boxtitle = 'registration.set.account.balance.limit.header.text';
            }

            extBoxAjax('get_html_popup', popup || 'mbox-msg', params, extraOptions);

        },
        saveRgLimit: function (event, type) {
            event.preventDefault();
            if (type === 'balance') {
                this.saveSingleLimit(type);
                return;
            }

            this.setTimeSpanLimit(type);

            return false;
        },
        setTimeSpanLimit: function (type) {
            var self = this,
                rgLimits = [];

            for (var index in this.timeSpans) {
                var timeSpan = this.timeSpans[index],
                    inputElement = $('#popup-' + type + '-limit-' + timeSpan),
                    curLimit = getMaxIntValue(inputElement.val());

                rgLimits.push({limit: curLimit, type: type, time_span: timeSpan});
            }

            if (!this.validateAllLimits(type)) {
                return;
            }

            var dataToSave = {type: type, limits: rgLimits};
            mgAjax({
                action : 'validate-post-registration-limits',
                limit_data : dataToSave
            }, function(response) {
                if(response.status === 'success') {
                    saveAccCommon('save_resettable', {data: JSON.stringify(dataToSave)}, function () {
                            mboxClose('register_set_' + type + '_limit_popup', function () {
                                self.checkPopups(true);
                            });
                        }
                    );

                    return;
                }

                // Show validation errors
                for(let key in response.errors) {
                    var inputElement = $('#popup-' + key);
                    inputElement.addClass('error');
                    inputElement.closest('div').find('span.error').removeClass('hidden').text(response.errors[key]);
                }
            })
        },
        validateAllLimits: function (type) {
            /**
             * Validate each limit
             * 1. Limit cannot be empty or 0
             * 2. Weekly limit cannot be smaller than daily limit
             * 3. Weekly limit cannot be greater than monthly limit
             * 4. Monthly limit cannot be smaller than daily or weekly limit
             */
            var dailyLimitInput = $('#popup-' + type + '-limit-day'),
                weeklyLimitInput = $('#popup-' + type + '-limit-week'),
                monthlyLimitInput = $('#popup-' + type + '-limit-month'),
                dailyLimit = parseInt(dailyLimitInput.val()),
                weeklyLimit = parseInt(weeklyLimitInput.val()),
                monthlyLimit = parseInt(monthlyLimitInput.val()),
                validationError = false;

            if (isNaN(dailyLimit) || dailyLimit <= 0) {
                dailyLimitInput.addClass('error');
                dailyLimitInput.closest('div').find('span.error').removeClass('hidden');

                validationError = true;
            }

            if (isNaN(weeklyLimit) || weeklyLimit <= 0) {
                weeklyLimitInput.addClass('error');
                weeklyLimitInput.closest('div').find('span.error').removeClass('hidden');

                validationError = true;
            }

            if (isNaN(monthlyLimit) || monthlyLimit <= 0) {
                monthlyLimitInput.addClass('error');
                monthlyLimitInput.closest('div').find('span.error').removeClass('hidden');

                validationError = true;
            }

            if (weeklyLimit < dailyLimit || weeklyLimit > monthlyLimit) {
                weeklyLimitInput.addClass('error');
                weeklyLimitInput.closest('div').find('span.error').removeClass('hidden');

                validationError = true;
            }

            if (monthlyLimit < dailyLimit || monthlyLimit < weeklyLimit) {
                monthlyLimitInput.addClass('error');
                monthlyLimitInput.closest('div').find('span.error').removeClass('hidden');

                validationError = true;
            }

            return !validationError;
        },
        saveSingleLimit: function (type) {
            var inputElement = $('#popup-' + type + '-limit-na'),
                curLimit = getMaxIntValue(inputElement.val()),
                self = this;

            if (!curLimit) {
                inputElement.addClass('error');
                inputElement.closest('div').find('span.error').removeClass('hidden');

                return;
            }

            var dataToSave = JSON.stringify({
                limit: curLimit,
                type: type,
                time_span: 'ma'
            });
            saveAccCommon('save_' + type, {data: dataToSave}, function (res) {
                mboxClose('register_set_' + type + '_limit_popup', function () {
                    mboxMsg(res.msg, true, function () {
                        self.checkPopups(true);
                    });
                })
            });
        },
        populateCalculated: function (timeSpan, type, limit) {
            if (timeSpan !== 'day') {
                return;
            }

            var dailyLimit = getMaxIntValue(limit),
                dailyLimitInput = $('#popup-' + type + '-limit-day')
                weeklyLimitInput = $('#popup-' + type + '-limit-week'),
                monthLimitInput = $('#popup-' + type + '-limit-month');

            var weeklyLimit = dailyLimit * 7,
                monthlyLimit = dailyLimit * 30;

            dailyLimitInput.closest('div').find('span.error').addClass('hidden');
            dailyLimitInput.closest('div').find('input').removeClass('error');

            weeklyLimitInput.val(weeklyLimit);
            weeklyLimitInput.closest('div').find('span.error').addClass('hidden');
            weeklyLimitInput.closest('div').find('input').removeClass('error');

            monthLimitInput.val(monthlyLimit);
            monthLimitInput.closest('div').find('span.error').addClass('hidden');
            monthLimitInput.closest('div').find('input').removeClass('error');
        },
        validateLimit: function (timeSpan, type, limit) {
            var currentLimit = this.parseLimit(limit),
                weeklyLimitInput = $('#popup-' + type + '-limit-week'),
                monthLimitInput = $('#popup-' + type + '-limit-month');

            if (type === 'login') {
                if (timeSpan === 'day' && currentLimit > 24) {
                    return 24;
                }

                if (timeSpan === 'week' && currentLimit > (24 * 7)) {
                    return 24 * 7;
                }

                if (timeSpan === 'month' && currentLimit > (24 * 30)) {
                    return 24 * 30;
                }
            }

            var error = false;
            if(timeSpan !== 'day') {
                error = !this.validateAllLimits(type);
            }

            if(error) {
                return currentLimit;
            }


            weeklyLimitInput.closest('div').find('span.error').addClass('hidden');
            weeklyLimitInput.closest('div').find('input').removeClass('error');
            monthLimitInput.closest('div').find('span.error').addClass('hidden');
            monthLimitInput.closest('div').find('input').removeClass('error');

            return currentLimit;
        },
        parseLimit: function (limit) {
            return limit.replace(/[^0-9]/g, '').replace(/(\..*)\./g, '$1');
        },
        getCommonPopupExtraOptions: function () {
            return isMobile() ? {} : {width: 776};
        },
        getCommonPopupParams: function (popup) {
            return {
                module: 'Licensed',
                file: popup,
                boxtitle: 'msg.title',
                closebtn: 'no'
            };
        }
    }
};

licFuncs.showBalanceLimitPopup = function (options) {
    var extraOptions = isMobile() ? {width: '100%'} : {width: 450};
    var params = {
        module: 'Licensed',
        file: 'balance_limit_popup',
        noRedirect: true,
        action: !!options.action ? options.action : 'deposit',
        amount: !!options.amount ? options.amount : ''
    };

    extBoxAjax('get_raw_html', 'balance_limit_popup', params, extraOptions, top);
};

