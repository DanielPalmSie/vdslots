$(function () {
    $(document).on('change', '#province-select', function () {
        licFuncs.provincePopupHandler().validateOnChange($('#province-select'));
    });

    licFuncs.initNationalityPopup();
})

licFuncs.initNationalityPopup = function () {
    $(document).on('change', '#nationality-select', function () {
        licFuncs.nationalityPopupHandler().validateOnChange($('#nationality-select'));
    });

    $(document).on('change', '#place-of-birth-input', function () {
        licFuncs.nationalityPopupHandler().validateOnChange($('#place-of-birth-input'));
    });
}

licFuncs.showNationalityAndPOBPopup = function () {
    var nationalityPopupHandler = licFuncs.nationalityPopupHandler();
    nationalityPopupHandler.showNationalityPopup();
}

licFuncs.showNationalityPopup = function () {
    var nationalityPopupHandler = licFuncs.nationalityPopupHandler();
    nationalityPopupHandler.showNationalityPopup('update_nationality_popup');
}

licFuncs.showProvincePopup = function () {
    var provincePopupHandler = licFuncs.provincePopupHandler();
    provincePopupHandler.showProvincePopup();
}
licFuncs.showResponsibleGamingMessagePopup = function () {
    var showResponsibleGamingHandle = licFuncs.provincePopupHandler();
    showResponsibleGamingHandle.showResponsibleGamingTool();
}

licFuncs.showSuccessfullIdscanPopup = function () {
    var showIdscanHandle = licFuncs.provincePopupHandler();
    showIdscanHandle.showSuccessfullIdscanPopup();
}

licFuncs.showTwoFactorAuthenticationPopup = function () {
    var provincePopupHandler = licFuncs.provincePopupHandler();
    provincePopupHandler.showTwoFactorAuthenticationPopup();
}

licFuncs.showFailedExpiryDatePopup = function () {
    var provincePopupHandler = licFuncs.provincePopupHandler();
    provincePopupHandler.showFailedExpiryDatePopup();
}

licFuncs.showNDBInfoPopup = function () {
    var provincePopupHandler = licFuncs.provincePopupHandler();
    provincePopupHandler.showNDBInfoPopup();
}

licFuncs.onGeoComplyNDBContinue = function (){
    var provincePopupHandler = licFuncs.provincePopupHandler();
    provincePopupHandler.closeNDBInfoPopup();
}

licFuncs.showLimitConfirmationPopup = function () {
    var setLimitPopupHandler = licFuncs.setLimitPopupHandler();
    setLimitPopupHandler.showGamingLimitConfirmationPopup();
}


licFuncs.showSetLimitPopup = function () {
    saveAccCommon('asknolimits', {}, function (res) {
        if (res.success) {
            mboxClose('gaming-limit-confirmation-box');

            var setLimitPopupHandler = licFuncs.setLimitPopupHandler();
            setLimitPopupHandler.showGamingLimitSetupPopup();
        }
    });
}


licFuncs.rejectEnterLimits = function () {
    saveAccCommon('asknolimits', {}, function (res) {
        if (res.success) {
            closePopup('gaming-limit-confirmation-box', true, false);
        }
    });
}



licFuncs.setLimitPopupHandler = function () {
    return {
        showGamingLimitConfirmationPopup: function () {
            var params = this.getCommonPopupParams('gaming_limits_confirmation_popup'),
                extraOptions = this.getCommonPopupExtraOptions(342);
            params.boxtitle = 'gaming.limit.popup.title';
            params.closebtn = 'no';
            extBoxAjax('get_html_popup', 'gaming-limit-confirmation-box', params, extraOptions);
        },

        showGamingLimitSetupPopup: function () {
            var params = this.getCommonPopupParams('gaming_limits_setup_popup'),
                extraOptions = this.getCommonPopupExtraOptions(800);
            params.boxtitle = 'gaming.limit.popup.title';
            params.closebtn = 'yes';
            extBoxAjax('get_html_popup', 'gaming-limit-setup-box', params, extraOptions);
        },


        populateCalculated: function (timeSpan, type, limit) {
            if (timeSpan !== 'day') {
                return;
            }

            var dailyLimit = getMaxIntValue(limit),
            dailyLimitInput = $('#lp-' + type + '-day'),
            weeklyLimitInput = $('#lp-' + type + '-week'),
            monthLimitInput = $('#lp-' + type + '-month');

            var weeklyLimit = dailyLimit * 7,
                monthlyLimit = dailyLimit * 30;

            dailyLimitInput.closest('div').find('input').removeClass('input-error');

            weeklyLimitInput.val(weeklyLimit);
            weeklyLimitInput.closest('div').find('input').removeClass('input-error');

            monthLimitInput.val(monthlyLimit);
            monthLimitInput.closest('div').find('input').removeClass('input-error');
        },


        setResettableLimits: function () {
            var limitTypes = ['deposit', 'loss', 'wager'];
            var dataToSave = [];

            for (lt of limitTypes) {
                var limitData = this.getPopupLimits(lt);

                if (limitData.length) {
                    dataToSave.push({type: lt, limits: limitData});
                }
            }

            if (!$('.input-error').length) {
                if (!dataToSave.length) {
                    // nothing was set - just close popup
                    mboxClose('gaming-limit-setup-box');
                } else {
                    this.setLimits(dataToSave);
                }
            }
        },


        getPopupLimits: function (type) {
            var rgLimits = [];
            var inputs = [];
            var activeRow = false;
            var emptyValues = false;


            for (tspan of reSpans) {
                var baseSelector = '#lp-' + type + '-' + tspan;

                var curInput = $(baseSelector);
                $(curInput).removeClass('input-error');
                var curLimit = getMaxIntValue(curInput.val());
                inputs.push(curInput);
                rgLimits.push({limit: curLimit, type: type, time_span: tspan});

                // if one value in a row was set then all need to be presented
                if (curInput.val().length !== 0) {
                    activeRow = true;
                }
            }

            if (activeRow) {
                $(inputs).each(function (key, input) {
                    const isEmptyOrZero = empty($(input).val());
                    const isNegative = +$(input).val() < 0;
                    if (isEmptyOrZero || isNegative) {
                        //we have incorrect values in an active row
                        emptyValues = true;
                        $(input).addClass('input-error');
                    }
                })
            }

            //returning limits only for fully filled row
            if (activeRow && !emptyValues) {
                return rgLimits;
            }

            return [];

        },


        setLimits: function (data, iteration = 0) {
            var curdata = data[iteration];
            var pData = JSON.stringify(curdata);

            saveAccCommon('save_resettable', {data: pData}, function (res) {
                var nextInt = iteration + 1;
                var nextdata = data[nextInt];

                if (nextdata) {
                    licFuncs.setLimitPopupHandler().setLimits(data, nextInt);
                } else {
                    //all iterations are done
                    saveAccCommon('asknolimits', {}, function (res) {
                        if (res.success) {
                            jsReloadWithParams();
                        }
                    });
                }
            });
        },


        getCommonPopupParams: function (popup) {
            return {
                module: 'Licensed',
                file: popup,
                boxtitle: 'msg.title',
                closebtn: 'no'
            };
        },

        getCommonPopupExtraOptions: function (width) {
            return isMobile() ? {width: '100%'} : {width: width};
        }

    }
}


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
                case 'place-of-birth-input':
                    this.showhideerror('.place-of-birth-error', val);
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
            var placeOfBirth = this.validateOnChange($('#place-of-birth-input'));

            if(nationality != '' && placeOfBirth != ''){
                saveAccCommon('save_nationality_pob', {nationality: nationality, pob: placeOfBirth}, function (res) {

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
        sendCountrySelected: function () {
            //verification before saving
            var nationality = this.validateOnChange($('#nationality-select'))

            if(nationality != ''){
                saveAccCommon('save_nationality', {nationality: nationality}, function (res) {

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
    }
}


licFuncs.provincePopupHandler = function () {
    return {
        showProvincePopup: function () {
            var params = this.getCommonPopupParams('add_province_popup'),
                extraOptions = this.getCommonPopupExtraOptions(500);
            params.boxtitle = 'province.header';
            extBoxAjax('get_html_popup', 'province-popup-box', params, extraOptions);
        },


        showTwoFactorAuthenticationPopup: function () {
            var params = this.getCommonPopupParams('add_two_factor_authentication_popup'),
                extraOptions = this.getCommonPopupExtraOptions(450);
            params.boxtitle = 'two.factor.authentication.header';
            params.closebtn = 'yes';
            extBoxAjax('get_html_popup', 'get_html_popup', params, extraOptions);
        },

        showResponsibleGamingTool : function (closeGamingMessageBox) {
            if(closeGamingMessageBox) {
                closePopup( 'responsible-gaming-message-box', true, false);
            }
            var params = this.getCommonPopupParams('responsible_gaming_tools_popup'),
                extraOptions = this.getCommonPopupExtraOptions(400);
            params.boxtitle = 'responsible.gaming.popup.header';
            params.closebtn = 'yes';
            extBoxAjax('get_html_popup', 'responsible-gaming-popup-box', params, extraOptions);
        },

        closeResponsibleGamingTool : function () {
            closePopup( 'responsible-gaming-message-box', true, false);
        },

        showTwoFactorAuthenticationPopup: function () {
            var params = this.getCommonPopupParams('add_two_factor_authentication_popup'),
                extraOptions = this.getCommonPopupExtraOptions(450);
            params.boxtitle = 'two.factor.authentication.header';
            params.closebtn = 'yes';
            extBoxAjax('get_html_popup', 'get_html_popup', params, extraOptions);
        },

        showFailedExpiryDatePopup: function () {
            var params = this.getCommonPopupParams('idscan_failed_expire_date'),
                extraOptions = this.getCommonPopupExtraOptions(450);
            params.boxtitle = 'idscan.failed.expiry.date.header';
            params.closebtn = 'no';
            extBoxAjax('get_html_popup', 'expiry_date', params, extraOptions);
        },

        getCommonPopupExtraOptions: function (width) {
            return isMobile() ? {width: '100%'} : {width: width};
        },


        getCommonPopupParams: function (popup) {
            return {
                module: 'Licensed',
                file: popup,
                boxtitle: 'msg.title',
                closebtn: 'no'
            };
        },

        sendSelectedProvince: function () {
            var provinceSelectBoxElement = $('#province-select');
            var selectValue = provinceSelectBoxElement.val();

            this.validateOnChange(provinceSelectBoxElement);

            if (selectValue === '') {
                $('.province-main-popup').find('.province-error').removeClass('hidden');
                return;
            }

            saveAccCommon('save_province', {province: selectValue}, function (res) {
                if (res.success) {
                    mboxClose('province-popup-box', function () {
                        //mboxMsg(res.msg, true, true, 400);

                        if (selectValue == "ON"){
                            licFuncs.showLimitConfirmationPopup();
                            return;
                        }

                        execNextPopup();
                    });
                    return;
                }

                mboxMsg(res.msg, true, function () {
                    jsReloadBase();
                }, 400);
            });
        },

        validateOnChange: function (element) {
            var selector = $('.province-main-popup');
            if (element.val() === '') {
                selector.find('.province-error').removeClass('hidden');
                return;
            }
            selector.find('.province-error').addClass('hidden');
        },

        showResponsibleGamingMessagePopup : function () {
            mgAjax({action: "close-responsible-gaming-popup-box"}, function (response) {
                if (!isMobile()) {
                    closePopup('responsible-gaming-popup-box', true, false);
                }
                var params = this.getCommonPopupParams('responsible_gaming_message_popup'),
                    extraOptions = this.getCommonPopupExtraOptions(700);
                params.closebtn = 'yes';
                extBoxAjax('get_html_popup', 'responsible-gaming-message-box', params, extraOptions);
            }.bind(this));
        },
        showSuccessfullIdscanPopup: function() {
            var params = this.getCommonPopupParams('idscan_success_popup'),
                extraOptions = this.getCommonPopupExtraOptions(700);
            params.boxid = 'idscan_success_popup';
            params.boxtitle = 'idscan.identity.title';
            params.closebtn = 'no';

            extraOptions.containerClass = 'flex-in-wrapper-popup';

            const boxId = isMobile() ? 'mbox-msg' :  'idscan_success_box';
            extBoxAjax('get_html_popup', boxId, params, extraOptions);
        },
        showNDBInfoPopup: function() {
            var params = this.getCommonPopupParams('geocomply_ndb_popup'),
                extraOptions = this.getCommonPopupExtraOptions(450);
            params.boxid = 'geocomply_ndb_popup';
            params.boxtitle = 'geocomply.inform.popup.title';
            params.closebtn = 'yes';

            extraOptions.containerClass = 'flex-in-wrapper-popup';

            const boxId = 'geocomply_ndb_box';
            extBoxAjax('get_html_popup', boxId, params, extraOptions);
        },

        closeNDBInfoPopup : function () {
            mboxClose('geocomply_ndb_box');
        },
    }
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

licFuncs.onRgLockClick = function(options){
    options.num_hours = $("input[name='lock_duration']:checked").val();
    return options;
};

$(function () {
    $(document).on('change', '#industry-select', function () {
        licFuncs.ontarioPopupHandler().validateIndustryOnChange($('#industry-select'));
    });
    $(document).on('change', '#occupation', function(){
        licFuncs.ontarioPopupHandler().validateOccupation($('#occupation'));
    });
})

licFuncs.showOntarioIndustryPopup = function () {
    var industryPopupHandler = licFuncs.ontarioPopupHandler();
    industryPopupHandler.showOntarioIndustryPopup();
}

licFuncs.ontarioPopupHandler = function () {
    return {
        showOntarioIndustryPopup: function () {
            var params = this.getCommonPopupParams('add_ontario_industry_occupation_popup'),
                extraOptions = this.getCommonPopupExtraOptions(550);
            params.boxtitle = 'msg.ontario.popup';
            extBoxAjax('get_html_popup', 'ontario-popup-box', params, extraOptions);
        },

        getCommonPopupExtraOptions: function (width) {
            return isMobile() ? {width: '100%'} : {width: width};
        },
        getCommonPopupParams: function (popup) {
            return {
                module: 'Licensed',
                file: popup,
                boxtitle: 'msg.title',
                closebtn: 'no'
            };
        },
        sendSelectedIndustry: function () {
            var industrySelectBoxElement = $('#industry-select');
            var selectValue = industrySelectBoxElement.val();

            var occupationBoxElement = $('#occupation');
            var occupationValue = occupationBoxElement.val();

            var checkboxes = document.getElementsByName('checkbox');
            var allChecked = true;

            this.validateIndustryOnChange(industrySelectBoxElement);
            this.validateOccupation(occupationBoxElement)

            checkboxes.forEach((node) => allChecked &= node.checked);
            if (!allChecked) {
                $('.industry-main-popup').find('.checkbox-error').removeClass('hidden');
                return;
            }

            if (selectValue === '') {
                $('.industry-main-popup').find('.industry-error').removeClass('hidden');
                return;
            }

            if(occupationValue === ''){
                $('.industry-main-popup').find('.occupation-error').removeClass('hidden');
                return;
            }

            saveAccCommon('save_industry', {industry: selectValue, occupation: occupationValue}, function (res) {

                if (res.success) {
                    mboxClose('ontario-popup-box');
                    licFuncs.showLimitConfirmationPopup();
                    return;
                }

                mboxMsg(res.msg, true, function () {
                    jsReloadBase();
                }, 400);
            });
        },
        validateIndustryOnChange: function (element) {
            var selector = $('.industry-main-popup');
            if (element.val() === '') {
                selector.find('.industry-error').removeClass('hidden');
                return;
            }
            selector.find('.industry-error').addClass('hidden');
        },
        validateOccupation: function(element){
            var selector = $('.industry-main-popup');
            if (element.val() === '') {
                selector.find('.occupation-error').removeClass('hidden');
                return;
            }
            selector.find('.occupation-error').addClass('hidden');
        }
    }
}

licFuncs.onIdScanContinue = function (){
    saveAccCommon('idscancontinue', {}, function (res) {
        if (res.success) {
            closePopup( 'idscan_success_box', true, false);
            execNextPopup();
        }
    });
}
