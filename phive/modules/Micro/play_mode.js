
function playGameDepositCheckBonus(gid, noLoader, gameUrl, show_demo){
    if (typeof show_demo === 'undefined') {
        show_demo = false;
    }
    if(typeof gameUrl === 'undefined') {
        gameUrl = '';
    }
    var func = function () {
        mgAjax({action: 'check_game', game_id: gid, show_demo: show_demo, isBos: top.mpUserId, user_country: top.cur_country}, function (ret) {
            try {
                ret = JSON.parse(ret)
            } catch (e) {
            }
            if (ret['type'] === 'game-session-unique') {
                mboxMsg(ret['message'], true)
                hideLoader();
            } else if (ret === 'ok') {
                if (isMobile()) {
                    playMobileGame(gid);
                } else {
                    playGameDeposit(gid, true, gameUrl, show_demo);
                }
            } else if (ret === 'force_self_assesment_popup' || ret === 'force_deposit_limit') {
                forcePopup(ret, function () {
                    playGameDepositCheckBonus(gid, noLoader, gameUrl);
                });

            } else if (ret.indexOf('registration') !== -1) {
                // We show registration instead (eg no demo play allowed)
                hideLoader();
                if (registration_mode === 'bankid') {
                    licFuncs.startBankIdRegistration('registration');
                } else {
                    showRegistrationBox(registration_step1_url);
                }
            } else if (
                [
                    'game_session_balance_set',
                    'game_session_limit_too_close_new_session_warning',
                    'game_session_temporary_restriction',
                    'game_session_close_existing_and_open_new_session_prompt'
                ].includes(ret)
            ) {
                lic('showExternalGameSessionPopup', [ret, {game_id: gid, show_demo: show_demo, no_loader: noLoader, game_url: gameUrl}]);
                hideLoader();
            } else if (ret === 'show_occupation') { // TODO we are never getting here after cleanup, remove? /Antonio
                hideLoader();
                gotoLang("/?show_occupation=true&gid=" + gid + "&noLoader=" + noLoader + "&gameUrl=" + gameUrl);
            } else if(ret === 'restricted') {
                extBoxAjax('restricted-popup', 'restricted-popup', {msg_title: 'restrict.msg.expired.documents.title'}, {});
            } else if(ret.indexOf('show_source_of_funds') !== -1) {
                showSourceOfFundsBox("/sourceoffunds1/?document_id=" + ret.split(":")[1]);
                hideLoader();
            } else {
                hideLoader();
                mboxMsg(ret);
            }
        });
    };

    if(typeof noLoader === 'undefined' || noLoader === '') {
        showLoader(func, true);
    } else {
        func.call();
    }
}

/**
 * This function is used to call the playGameDepositCheckBonus function using gameCloseRedirection. It is needed
 * here in order for the 60 minute RG popup required for Spain to be displayed properly when the player closes the
 * game session manually
 *
 * @param gameId
 * @param gameUrl
 */
function playGameDepositCheckBonusRef(gameId, gameUrl){
    gameCloseRedirection("playGameDepositCheckBonus('" + gameId + "', '" + gameUrl + "')");
}

function stopGame(){
  $.multibox("close", "mbox-popup");
}

var showDeposit = true;
function playGameDeposit(gid, noLoader, gameUrl, show_demo){
    if (typeof show_demo === 'undefined') {
        show_demo = false;
    }

    if (typeof gameUrl === 'undefined') {
        gameUrl = '';
    }

    var func = function () {
        playGameNow(gid, true, gameUrl, show_demo);
    };

    if (typeof noLoader === 'undefined' || noLoader === '') {
        showLoader(func, true);
    } else {
        func.call();
    }
}

function playGameNow(gid, noLoader, gameUrl, show_demo){
    if (typeof show_demo === 'undefined') {
        show_demo = false;
    }
    func = function(){
        mgAjax({action: 'get_game_mode', game_id: gid}, function(ret){
            $("body").append(ret);
            if ($('#playbox-container').length > 0) {
                hideLoader();
                var target = (typeof window.mw_opento === 'undefined') ? $('#play-box').find('.play-box-inner').not(':visible').first() : window.mw_opento;
                if (target.length < 1)
                    target = $('#play-box').find('.play-box-inner').first();
                var src = '/phive/modules/Micro/play.php?&amp;game_id=' + gid + '&amp;lang=' + cur_lang + (show_demo ? '&amp;show_demo=true' : '');
                target.html('<iframe class="play-box-frame" src="' + src + '" scrolling="no" hspace="0" border="0" frameborder="0" style="height: 100%; width: 100%; overflow: hidden;"></iframe>').css('visibility','visible').show('block');
                target.siblings().hide();
                target.siblings('span').show();
                delete window.mw_opento;
                return false;
            } else {
                gameUrl = (typeof gameUrl === 'undefined' || gameUrl === '') ? curGame.game_url : gameUrl;
                var lang = (cur_lang === default_lang) ? '' : ('/' + cur_lang);
                goTo(lang+'/play/' + gameUrl + (show_demo ? '?show_demo=true' : ''));
                return;
            }
        });
    };

    if(typeof noLoader === 'undefined' || noLoader === '') {
        showLoader(func, true);
    } else {
        func.call();
    }
}
