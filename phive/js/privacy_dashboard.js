function showPrivacySettings() {
    const params = {
        file: 'privacy_dash_board_modal',
        boxid: 'privacy-dash-board-modal',
        boxtitle: 'privacy.update.form.title',
        module: 'DBUserHandler',
        extra_css: 'privacy-dashboard__modal',
        closebtn: 'no'
    };
    const extraOptions = isMobile() ? {
        width: '100vw',
        height: '100vh',
        containerClass: 'privacy-dashboard__modal privacy-dashboard__modal--mobile'
    } : {
        width: '740',
        containerClass: 'privacy-dashboard__modal privacy-dashboard__modal--desktop'
    };

    extBoxAjax('get_html_popup', 'privacy-dash-board-modal', params, extraOptions, top);
}


function showMoreInfoBox(title, html){
    mboxMsg(html, false, '', 360, true, true, title);
}

function closePrivacySettingsBox(){
    window.location.reload(true);
}

function privacyAction(action, mobile, post) {
    if (typeof mobile === 'undefined') {
        return;
    }

    if (action === 'close')
        action = 'cancel';

    if (registration_mode != 'onestep' && registration_mode != 'bankid') {
        showLoader(undefined, true);
        mboxClose();
    }

    mgAjax({action: 'update-privacy-settings', privacyaction: action, mobile: mobile}, function (res) {
        var result = JSON.parse(res);
        if (post === 'registration') {
            if (result.status === 'ok') {
                if (registration_mode === 'paynplay') {
                    jsReloadWithParams();
                    return;
                }

                if (registration_mode === 'onestep' || registration_mode === 'bankid') {
                    $('#privacy-confirmation-notification .multibox-close').click();
                    mboxClose('privacy-confirmation-notification');
                    return;
                }

                var language = (cur_lang !== default_lang) ? ('/' + cur_lang) : '';
                parentGoTo(language + '/' + '?show_deposit=true');
                return;
            } else {
                mboxMsg(res.error, true, '', 360, true, true);
            }
        } else if (action === 'accept') {
            goTo(result['link']);
        } else {
            jsReloadWithParams();
        }
    });

}

/**
 * Show a popup with confirmation request for ALL privacy settings:
 * - accept will set all them true
 * - edit:
 *   - on registration - will optout of all of them
 *   - on normal website - will show the full privacy dashboard popup
 * may be later will redirect to privacy-dash board
 * @param mobile - we require to know when request is coming from mobile context to properly redirect to the correct page.
 * @param post - context of the request can be: normal|registration|popup
 * @param width
 */
function showPrivacyConfirmBox(mobile, post, width = 450) {
    if ($('#bankid-account-verification-popup').length > 0) {
        mboxClose('bankid-account-verification-popup');
    }

    if ($('#bankid_registration_popup').length > 0) {
        mboxClose('bankid_registration_popup');
    }

    const params = {
        file: 'privacy_confirmation_popup',
        closebtn: 'no',
        boxid: 'privacy-confirmation-notification',
        boxtitle: 'confirm',
        module: 'DBUserHandler',
        mobile: mobile,
        post: post
    };

    const extraOptions = isMobile()
        ? {
            width: '100vw',
            height: '100vh',
            containerClass: 'flex-in-wrapper-popup button-fix--mobile'
        }
        : {
            width: width,
            containerClass: 'flex-in-wrapper-popup'
        };

    extBoxAjax('get_html_popup', 'privacy-confirmation-notification', params, extraOptions, top);
}

function postPrivacySettings(mobile, skipAllEmptyCheck = false, mode='popup') {

    $('.error-message-table').remove();

    function redirectAfterPrivacySetting() {
        if(mode === 'registration') {
            showPermanentLoader(undefined, null);
            if (registration_mode !== 'bankid') {
                var language = (cur_lang !== default_lang) ? ('/' + cur_lang) : '';
                parentGoTo(language + '/' + '?show_deposit=true');
            } else {
                if (isMobile()) {
                    Registration.submitStep2(top.registration1.Registration.getFormStep2());
                } else {
                    top.registration1.goTo('/' + cur_lang + '/registration-step-2/', '_self', false);
                }
            }
        } else {
            jsReloadBase();
        }
    }

    mgAjax({action: 'update-privacy-settings', params: $("#privacy-settings-form").serializeArray()}, function(res){
        var result   = JSON.parse(res);
        if(result['status'] != 'ok') {
            mboxMsg(result['message'], false, '', 260, true, true, result['title']);
        } else {
            mboxMsg(result['message'], true, redirectAfterPrivacySetting, 260, true, true, result['title'], undefined, undefined, undefined, undefined, 'privacy-confirmation-popup');
        }

    });
}

function setupPrivacy() {
    // Do All
    $('.privacy-box-content #do-all').on('change', function () {
        $('.privacy-box-content input[type="checkbox"]')
            .not(this)
            .not('.opt-out-check input[type="checkbox"]')
            .not('input#privacy-pinfo-hidealias')
            .not('input#privacy-pinfo-hidename')
            .attr('checked', $(this).is(':checked'));

        $('.privacy-box-content .opt-out-check input[type="checkbox"]')
            .attr('checked', !$(this).is(':checked'))
            .change();
    });

    // Individual Checkboxes
    $('.privacy-box-content .opt-in-check input[type="checkbox"]').on('change', function () {
        const $this     = $(this);
        const parent    = $this.parents('.opt-category-row');
        let out_mode    = true;

        parent.find('.opt-in-check input[type="checkbox"]')
            .each(function () {
                if ($(this).is(':checked')) out_mode = false;
            });

        $('.privacy-notification-section[data-group="' + parent.data('group') + '"] .opt-out-check input[type="checkbox"]')
            .attr('checked', out_mode);
    });

    // Opt-Out Checkbox
    $('.privacy-box-content .opt-out-check input[type="checkbox"]').on('change', function () {
        const $this     = $(this);
        const parent    = $this.parents('.privacy-notification-section');

        $('.opt-category-row[data-group="' + parent.data('group') + '"] .opt-in-check input[type="checkbox"]')
            .attr('checked', !$this.is(':checked'))
            .attr('disabled', $this.is(':checked'));
    });
}
