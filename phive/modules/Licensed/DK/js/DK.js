licFuncs.defaultLogin = function() {
    return licFuncs.showCustomLogin();
};

licFuncs.verificationByMitID = function(email = null, registration = null){
     const emailField = $('.mid-email');
     const verifyBtn = $('#mitid-verify-btn');

    if (!emailField.val() && !email) {
        $("#mid_field_msg").show();
        return;
    }
    var username;
    if (email) {
        username = email;
    } else {
        username = emailField.val();
    }
    var params = {
        action: 'get_external_verification_mitID',
        username: username,
        registration: registration
    };

    if (verifyBtn.attr('disabled')) {
        return;
    }

    verifyBtn.attr('disabled', true);

    mgJson(params, function (res) {
        if (res.success) {
            return window.location.href = res.action;
        } else {
            mboxMsg(res.msg, true,function() {
                window.top.location.reload();
            });
        }

    });
};

licFuncs.validatePersonalNumberOnRegister = function (value) {
    return value.length === 10 && isNumber(value);
};

licFuncs.onRgReady = function(options){
    const lockInput = $('#lock-hours');
    const indefiniteCheckbox = $('#dk-indefinite');

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
    const indefiniteCheckbox = $('#dk-indefinite');

    return lockInput.val().length !== 0 || indefiniteCheckbox.is(':checked');
};

licFuncs.onRgLockClick = function(options){
    options.indefinitely = $("#dk-indefinite").is(':checked') ? 'yes' : 'no';
    return options;
};

licFuncs.isUserHasNoNidLogin = function() {
    return !!licFuncs.getLoginEmail();
}

licFuncs.saveLoginEmail = function () {
    window.sessionStorage.setItem('mitIdLoginEmail', $("#lic-login-username-field").val());
}

licFuncs.getLoginEmail = function () {
    return window.sessionStorage.getItem('mitIdLoginEmail');
}

licFuncs.verifyNid = function(){
    licFuncs.saveLoginEmail();
    $(".lic-mbox-wrapper.version2, #login-box, #multibox-overlay-login-box").remove();
    showLoginBox('registration_mitid', true);
};

licFuncs.startExternalVerification = function (context) {
    const verifyBtn = $('#mitid-verify-btn');
    const nid = $("#nid-field").val();

    if (!licFuncs.validatePersonalNumberOnRegister(nid)) {
        $("#nid_field_msg").show();
        return;
    }

    let email = $("#email").val();
    if (licFuncs.isUserHasNoNidLogin()) {
        email = licFuncs.getLoginEmail();
    }

    var params = {
        action: 'get_match_cpr_mitID',
        nid: $("#nid-field").val(),
        country: $("#country").val(),
        username: email,
        mobile: $("#mobile").val(),
        registration: true
    };

    if (verifyBtn.attr('disabled')) {
        return;
    }

    verifyBtn.attr('disabled', true);

    mgJson(params, function (res) {
        if (res.success) {
            return window.parent.location.href = res.action;
        } else {
            mboxMsg(res.msg, true,function() {
                window.top.location.reload();
            });
        }
    });
}
