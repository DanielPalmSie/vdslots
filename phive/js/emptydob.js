function setupEmptyDobBox() {
    $('#birthyear, #birthdate, #birthmonth').change(function() {
        if(empty($(this).val())) {
            addClassError(this);
            $(this).parent().addClass('styled-select-error');
            $(this).parent().removeClass('styled-select-valid');
        } else {
            addClassValid(this);
            $(this).parent().addClass('styled-select-valid');
            $(this).parent().removeClass('styled-select-error');
        }
    });
}

function setupPersonalNumberBox() {
    $('#personal_number').blur(function() {
        var self = $(this);
        var is_valid = false;

        // jurisdiction wise validation for NID
        if (!empty(licFuncs.validatePersonalNumberOnRegister)) {
            is_valid = licFuncs.validatePersonalNumberOnRegister(self.val());
        } else {
            is_valid = !empty(self.val());
        }

        if(is_valid) {
            self.removeClass('error');
            self.addClass('valid');
        } else {
            self.removeClass('valid');
            self.addClass('error');
            showInfoMessage(this);
        }
    });
}

/**
 * This function is only executed the first time the iframe get's opened.
 */
function showEmptyDobBox(url) {
    var iframename = 'emptydob1';

    if(typeof url == 'undefined')
        url = '/emptydob1/';

    url = llink(url);
    $.multibox({
        url: url,
        id: 'emptydob-box',
        name: iframename,
        type: 'iframe',
        width: '400px',
        height: '325px',
        cls: 'mbox-deposit',
        globalStyle: {overflow: 'hidden'},
        overlayOpacity: 0.7
    });
}

function validateDob() {
    valid = true;
    if($('#birthyear').val() == '') {
        valid = false;
        addClassError($('#birthyear'));
        $('#birthyear').parent().addClass('styled-select-error');
    } else {
        $('#birthyear').removeClass('error');
        addClassValid($('#birthyear'));
        $('#birthyear').parent().addClass('styled-select-valid');
        $('#birthyear').parent().removeClass('styled-select-error');
    }
    if($('#birthdate').val() == '') {
        valid = false;
        addClassError($('#birthdate'));
        $('#birthdate').parent().addClass('styled-select-error');
    }
    if($('#birthmonth').val() == '') {
        valid = false;
        addClassError($('#birthmonth'));
        $('#birthmonth').parent().addClass('styled-select-error');
    }

    return valid;
}

function submitPersonalNumber(){
    var data = {
        "nid": $('#personal_number').val()
    };
    submitFillInCommon(data);
}

function submitZipcode() {
    var data = {
        "zipcode": $('#zipcode').val()
    };
    submitFillInCommon(data);
}

function submitFillInCommon(data) {
    showLoader(function () {
        $.post("/phive/modules/Micro/emptydob.php?lang=" + cur_lang, data, function (data) {
            if (data.status != 'err') {
                // note: desktop and mobile are the same
                parent.$.multibox('close', 'emptydob-box');
                if (httpGet('redirect')) {
                    window.top.location.href = '/';
                }
            } else {
                // remove element to prevent showing double errors
                $('#errorZone').remove();
                $('#submit_step_1').before(data.info);  // this adds the errorZone div

                parent.$('#emptydob-box').css("height", 600);
                hideLoader();
            }

        }, "json");
    }, true);
}

function submitDob(){

    if(validateDob()) {

        var data = {
            "dob":		$('#birthyear').val()+"-"+$('#birthmonth').val()+"-"+$('#birthdate').val(),
            "birthyear":	$('#birthyear').val(),
            "birthmonth":	$('#birthmonth').val(),
            "birthdate":	$('#birthdate').val()
        };

        showLoader(function() {
            postDob(data);
        }, true);
    }
}

function postDob(data) {
    $.post("/phive/modules/Micro/emptydob.php?lang="+cur_lang, data, function(data){
        if(data.status != 'err') {
            // note: desktop and mobile are the same
            parent.$.multibox('close', 'emptydob-box');
            if (httpGet('redirect')) {
                window.top.location.href = '/';
            }
        } else {
            // remove element to prevent showing double errors
            $('#errorZone').remove();
            $('#submit_step_1').before(data.info);  // this adds the errorZone div

            parent.$('#emptydob-box').css("height", 400);
            hideLoader();
        }

    }, "json");
}

/**
 * Used to start the process of getting the nid for users who's nid field is empty
 *
 * @param lang
 * @param nid
 */
function goToVerify(lang, nid) {
    if (typeof licFuncs === 'undefined') {
        window.location.href = "/" + lang + '/?goto=verify&nid=' + nid;
    } else {
        if (licFuncs.verifyNid) {
            licFuncs.verifyNid();
        } else {
            licFuncs.showCustomLogin();
        }
    }
}

function goToOtp(description) {
    if (typeof licFuncs === 'undefined') {
        window.location.href = '/?goto=otp';
    } else {
        licFuncs.showOtpLogin(description);
    }
}

function goToResetPassword() {
    if (typeof licFuncs === 'undefined') {
        window.location.href = '/?goto=reset_password';
    } else {
        licFuncs.showResetPassword();
    }
}

function goToIpVerification() {
    if (typeof licFuncs === 'undefined') {
        window.location.href = '/?goto=ipverification';
    } else {
        licFuncs.showIpVerificationLogin();

    }
}

function showDefaultLogin(clearFields = false) {
    licFuncs?.showDefaultLogin(clearFields);
}

function showLoginCaptcha(image_src) {
    if (typeof licFuncs === 'undefined') {
        window.location.href = '/?goto=login_captcha';
    } else {
        licFuncs.showLoginCaptcha(image_src);
    }
}

function showLoginOTPCaptcha(image_src) {
    licFuncs?.showLoginOTPCaptcha(image_src);
}

/**
 * Display a popup to request if the customer want to import his data from the other brand
 * Will pass an extra param on login process on affirmative answer, or send to registration if false.
 *
 * @param title
 * @param description
 * @param okLabel
 * @param cancelLabel
 * @param registrationUrl
 */
function showImportFromBrand(title, description, okLabel, cancelLabel, registrationUrl) {
    var onOk = function () {
        importFromBrand = 1;
        doLogin();
        mboxClose();
    };
    mboxDialog(description, "gotoLang('"+registrationUrl+"')", cancelLabel, onOk, okLabel, null, 400, false, '', title, 'good-green');
    // for some reason passing the single function handler is not working so we remove all mousedown events (atm there were none except this one)
    // $(document).off('mousedown', hideMultiboxOnOutsideClick);
    $(document).off('mousedown');
}
