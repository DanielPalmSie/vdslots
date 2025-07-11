/**
 * Contains all the registration specific logic
 *
 * @type {{getFormStep1: (function(): {}), submitStep1: Registration.submitStep1, validateStep1: (function(): boolean)}}
 */
var Registration = {
    /**
     * Return an object containing the filled in form
     *
     * @returns {{}}
     */
    getFormStep1: function () {
        var form = {};
        $(".form-item").each(function () {
            var $input = $(this);
            var value = $input.val();
            if ($input.attr('type') === 'checkbox') {
                value = $input.is(':checked') ? 1 : 0;
            }
            form[$input.attr('id')] = value;
        });
        return form;
    },

    /**
     * Resize the #registration-box to the given dimensions
     *
     * @param {String} width The container width as 'NNNNpx'
     * @param {String} height The container height as 'NNNNpx'
     *
     * @returns {Object} The container jQuery box
     */
    resizeContainer: function(width, height) {
        var box = top.$("#registration-box");
        box.css("width", width);
        box.css("height", height);
        $.multibox('resize', 'registration-box', width, height, 'registration-box', false);
        box.center(false, false);
        return box;
    },

    /**
     * Does the FE validation of the registration step 1 form
     *
     * @returns {boolean}
     */
    validateStep1: function () {
        validateArray('#validation_step1', $('#validation_step1 :input'));

        $('span.styled-select').addClass('styled-select-valid');

        $('#country_prefix').addClass('country_code_color');

        doPostCheckForMobile('#validation_step1', '#mobile', 'check_mobile', '', '#country_prefix');

        return $('#validation_step1').valid() && validateSingle($('#country'), ['#country']);
    },
    /**
     * Server side validation of the form followed by redirect to the appropriate step
     *
     * @param form
     */
    submitStep1: function (form) {
        $.post("/phive/modules/DBUserHandler/xhr/registration.php?step=1&lang=" + cur_lang, form, function (data) {
            hideLoader();

            $("#errorZone").html('');
            // show error messages
            if (!data.success) {
                if (data.messages.login_context) {
                    return loginCallback(data.messages);
                }

                if(data && data.messages && data.messages.captcha && data.messages.captcha == 'show') {
                    var params = {
                        module: 'DBUserHandler',
                        file: 'registration_captcha',
                        id: 'registration_captcha',
                        boxtitle: 'registration.captcha.header.text',
                        additional_fields: data.messages.additional_fields ? data.messages.additional_fields : {}
                    }
                    extBoxAjax('get_html_popup', 'mbox-msg', params, {
                        onClose : function () {
                            hideLoader();
                        },
                    });
                    return;
                }

                for (var field in data.messages) {
                    if (data.messages.hasOwnProperty(field)) {
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
                resizeRegistrationbox(1);
                return;
            }

            // redirect to the appropriate step suggested by the BE
            if (data.action) {
                dynamicCall(window, data.action.method, data.action.params);
                return;
            }

            if (typeof mobileStep != 'undefined') {
                window.location.href = mobileStep;
            } else {
                if(registration_mode === 'onestep'){
                    top.$.multibox('hide', 'registration-box');
                }

                top.$.multibox('toggleOverflow', false);
                goTo('/' + cur_lang + '/registration-step-2/', '_self', false);
            }
        }, "json")
        .fail(function(response) {
            const isForbidden = response.status === 403;

            if (isForbidden) {
                hideLoader();
                $('#errorZone').html(response.responseText).show();
            }
        });
    },

    /**
     * Does the FE validation of the registration step 2 form
     *
     * @returns {boolean}
     */
    validateStep2: function () {
        validateArray('#validation_step2', $('#validation_step2 input:not(.skip-validation):not([type=hidden])'));

        var valid = validateAllDropDowns();
        var $other_ocupation = $('#other_occupation');
        var $email_code = $('#email_code');

        if ($other_ocupation.is(':visible') && $other_ocupation.val() === '') {
            addClassError($other_ocupation);
            valid = false;
        }

        if ($email_code.is(':visible') && $email_code.val() === '') {
            addClassError($email_code);
            this.logStep2ErrorMessages();

            return false;
        }

        // moved out the lic checks to show error messages immediately
        if (lic('invalidRegistrationStep2')) {
            this.logStep2ErrorMessages();
            return false;
        }

        /** Start Registration frontend validation for [sc-162039]
         *  This change is temporary to validate the fields correctly
         * */

        var all_filled = true;
        //possible alternative: something like $('input.required')
        $('#lastname, #address, #city, #zipcode, #personal_number, #occupation, #building').each(function(){ //if personal_number doesn't exists, the array is just shorter
            all_filled = $(this).val() !== '';
            return all_filled; //cut loop on the first empty
        });

        if(!all_filled){
            this.logStep2ErrorMessages();
            return false;
        }

        /** End Registration frontend validation for [sc-162039] */

        if (!$('#validation_step2').valid() || !valid) {
            this.logStep2ErrorMessages();
            return false;
        }

        return true;
    },
    /**
     * Return an object containing the filled in form
     *
     * @returns {{}}
     */
    getFormStep2: function () {
        var form = {};

        $(".form-item").each(function () {
            var $input = $(this);
            var value = $input.val();
            if ($input.attr('type') === 'checkbox' || $input.attr('type') === 'radio') {
                value = $input.is(':checked') ? 1 : 0;
            }
            form[$input.attr('id')] = value;
        });

        form["over18"] = $('#eighteen').is(':checked') ? 1 : 0;
        form["sex"] = form['male'] === 1 ? "Male" : "Female";
        form["dob"] = form['birthyear'] + "-" + form['birthmonth'] + "-" + form['birthdate'];
        form['alias'] = $('#alias').val();
        form['country'] = $('#country-step2').val();

        return form;
    },
    /**
     * Server side validation of the form followed by redirect to the appropriate step
     *
     * @param form
     */
    submitStep2: function (form) {
        var uid = httpGet('uid');
        uid = uid ? ("&uid=" + uid) : '';

        $(".info-message").text('').hide();
        $.post("/phive/modules/DBUserHandler/xhr/registration.php?step=2&lang=" + cur_lang + uid, form, function (data) {
            hideLoader();
            // show error messages
            if (!data.success) {
                for (var field in data.messages) {
                    if (data.messages.hasOwnProperty(field)) {
                        var $target = $('#' + field);
                        if ($target.length === 0) {
                            $target = $("#general_error");
                            $target.text(data.messages[field]).show()
                        }
                        addClassError($target);
                        if ($target && $target[0].tagName === 'SELECT') {
                            addClassStyledSelectError($target);
                        }
                        $('.field-'+field).find(".info-message").text(data.messages[field]).show();
                    }
                }

                if(registration_mode === 'onestep' || registration_mode === 'bankid'){
                    parent.$.multibox('show', 'registration-box');
                    $('.step2').removeClass('hidden');
                    top.$.multibox('close', "mbox-loader");
                }

                resizeRegistrationbox(2);
                resizeLightBox();

                return;
            }

            // redirect to the appropriate step suggested by the BE
            if (data.action) {
                window[data.action.method].apply(null, data.action.params);
                return;
            }

            var mobile = typeof mobileStep !== 'undefined' ? "mobile/" : "";
            language = (cur_lang !== default_lang) ? ('/' + cur_lang) : '';
            data = data.data;

            if (data) {
                if (typeof data.experian_msg !== 'undefined') {
                    parentMboxMsg(data.experian_msg, true, function () {
                        parentGoTo(data.llink);
                    }, 500, undefined, true);
                    return;
                }

                if (typeof data.fraud_msg != 'undefined') {
                    // show fraud message, and redirect to homepage after the user clicks OK
                    var z_index = empty(mobile) ? parseInt(top.$("#registration-box").css('z-index')) : undefined;
                    parentMboxMsg(data.fraud_msg, true, function () {
                        parentGoTo(language + '/' + mobile);
                    }, 500, undefined, true, undefined, z_index);
                    return;
                }

                if (typeof data.already_registered != 'undefined') {
                    parentGoTo(language + '/' + mobile);
                    return;
                }

                if (typeof data.migrated != 'undefined') {
                    parentGoTo(language + '/' + mobile + '?show_deposit=true');
                    return;
                }
            }

            parentGoTo(language + '/' + mobile + '?show_deposit=true');

        }, "json");
    },

    getStep2ErrorMessages() {
        const shownMessages = $('.info-message').filter(function() {
            return $(this).css('display') !== 'none';
        });

        return shownMessages.map(function() {
            const id = $(this).attr('id');
            return {[id]: $(this).text()};
        }).get();
    },

    /**
     * Logs the error messages from step 2 to user's actions
     */
    logStep2ErrorMessages() {
        const errors = this.getStep2ErrorMessages();
        mgSecureAjax({action: 'log-action', tag: 'error', data: errors});
    }
};

function handleBankIdRegistration(bankIdData) {
    showLoader(function () {
        licFuncs.showBankIdRegistrationPopup(bankIdData);
    }, true)
}

/**
 * Control the registration step 1 process
 *
 * @param extra // append data to the request body, for example response received from NemID
 */
function handleRegistrationStep1(extra) {
    var form = Registration.getFormStep1();

    if (!Registration.validateStep1()) {
        if ($('#email').val() === 'E-mail taken') {
            $('#email_msg').hide();
            $('#email_taken_msg').show();
        }
        return;
    }

    if (typeof extra !== "undefined") {
        for (var key in extra) {
            if (extra.hasOwnProperty(key)) {
                form[key] = extra[key];
            }
        }
    }

    showLoader(function () {
        Registration.submitStep1(form);
    }, true);
}

/**
 * Control the registration step 2 process
 *
 */
function handleRegistrationStep2() {
    if (!Registration.validateStep2()) {
        return;
    }

    showLoader(function () {
        Registration.submitStep2(Registration.getFormStep2());
    });
}

/**
 * Save the nid value in localstorage and show it to the user
 */
function handleRememberNid() {
    var $nid_field = $("#nid-field");
    var $remember_checkbox = $("#remember_nid");
    var local_nid = localStorage.getItem('nid')
    $nid_field.val(local_nid);

    if ((local_nid || '').toString().length > 0) {
        $remember_checkbox.attr('checked', 'checked');
    }

    $remember_checkbox.click(function () {
        if (!$(this).is(":checked")) {
            localStorage.removeItem('nid');
            return;
        }

        var curNid = $nid_field.val().toString();
        if (licFuncs.validateNid(curNid)) {
            localStorage.setItem('nid', curNid);
        }
    });

    var http_nid = httpGet('nid');
    if (!empty(http_nid)) {
        $nid_field.val(http_nid);
        if ($remember_checkbox.is(":checked")) {
            $remember_checkbox.click();
        }
    }
}

function goToIpVerification(){
    top.$.multibox('close', 'registration-box');
    parent.showLoginBox('ipverification');
}
