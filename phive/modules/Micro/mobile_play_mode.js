
var or = window.orientation;
var orFuncs = [];

function addOrFunc(func){
    orFuncs.push(func);
}

function onOrientationChange(){
    if(window.orientation !== or){
        or = window.orientation;
        $.each(orFuncs, function(i, func){
            func.call();
        });
    }
}

window.addEventListener("resize", onOrientationChange, false);
window.addEventListener("orientationchange", onOrientationChange, false);

// (optional) Android doesn't always fire orientationChange on 180 degree turns
setInterval(onOrientationChange, 2000);


function playMobileGameShowLoader(gref, show_demo, loader_timeout = 0){
    if (typeof show_demo === 'undefined') {
        show_demo = false;
    }

    showLoader(function(){
        mgAjax({action: 'check_game', game_ref: gref, show_demo: show_demo}, function(ret){
            try {
                ret = JSON.parse(ret)
            } catch (e) {
            }
            if (ret['type'] === 'game-session-unique') {
                mboxMsg(ret['message'], true)
                hideLoader();
            } else if(ret == 'ok')
                playGameNowByRef(gref, '', '', show_demo);
            else if(ret == 'registration'){
                showMobileRegistrationBox();
            } else if (ret === 'force_self_assesment_popup') {
                forcePopup(ret, function(){
                    playMobileGameShowLoader(gref, show_demo);
                });
            } else if (ret === 'force_deposit_limit') {
                forcePopup(ret, function(){
                    playGameNowByRef(gref, '', '', show_demo);
                });
            } else if (
                [
                    'game_session_balance_set',
                    'game_session_limit_too_close_new_session_warning',
                    'game_session_temporary_restriction',
                    'game_session_close_existing_and_open_new_session_prompt'
                ].includes(ret)
            ) {
                lic('showExternalGameSessionPopup', [ret, {game_ref: gref, show_demo: show_demo}]);
            } else if (ret === 'restricted') {
                extBoxAjax('restricted-popup', 'restricted-popup', {msg_title: 'restrict.msg.expired.documents.title'}, {});
            } else if(ret.indexOf('show_source_of_funds') !== -1) {
                const docId = ret.split(":")[1];
                if (docId) {
                    showSourceOfFundsBox("/sourceoffunds1/?document_id=" + docId);
                    hideLoader();
                } else {
                    hideLoader();
                    playGameNowByRef(gref, '', '', show_demo);
                }
            } else {
                hideLoader();
                mboxMsg(ret);
                /*
                 fancyShow({
                 phpfunc: 'failBonusWrongGame',
                 msg: ret,
                 jsactions: [{sel: "#play-anyway-btn button", action: 'attr', args: ['onclick', "playGameNowByRef('"+gref+"')"]}]
                 });
                 */
            }
        });
    }, true, '', loader_timeout);
}

/**
 * This function is being called whan a user clicks on a game in the mobile website.
 * It is then making an ajax call to /Micro/ajax.php
 * The action 'reality-check-duration' is not only about Reality Checks,
 * but it is also showing the unrelated "Occupation and gambling budget" popup.
 *
 */
function playMobileGame(gref, show_demo = false){
    mgAjax({action: 'reality-check-duration', game_ref: gref, show_demo: show_demo}, function (ret) {

        if(ret == 'no player'){
            playMobileGameShowLoader(gref, show_demo)
            return;
        }

        var res    = JSON.parse(ret);
        var rc_res = res.rc;

        if(!empty(rc_res)){

            if(rc_res.status === 'ok'){

                rc_params.rc_current_interval = rc_res.player_check_interval * 60;
                reality_checks_js.duration            = 0;
                reality_checks_js.gref                = gref;
                reality_checks_js.doAfter             = function(){
                    return true;
                };
                reality_checks_js.rc_createDialog();
                return;
            }else{
                playMobileGameShowLoader(gref, show_demo);
            }


        }else{
            playMobileGameShowLoader(gref, show_demo);
                return;
        }

        return;
    });
}

function goToMobileBattleOfSlots(url, subpage) {

    if(subpage !== undefined) {
        // if we provide a tournamentId we send the player directly in the tournament lobby.
        if(isNumber(subpage)) {
            url += '/tournament/'+subpage;
        }
        if(typeof subpage == 'object') {
            if(subpage.type == 'tournament') {
                url += '/tournament/'+subpage.id;
            }
            if(subpage.type == 'award') {
                mgAjax({action: 'get-tournament-from-ticket-award', award_id: subpage.id}, function(response) {
                    response = JSON.parse(response);

                    if(response.tournament_id != null) {
                        url += '/tournament/'+response.tournament_id;
                    }
                    checkForRgPopupBeforeRedirect(url);
                });
            }
        }
    } else {
        checkForRgPopupBeforeRedirect(url);
    }
}

function checkForRgPopupBeforeRedirect(url) {
    mgAjax({action: 'reality-check-duration', url: url}, function (ret) {
        if(ret == 'no player'){
            gotoLang(url);
            return;
        }

        var res   = JSON.parse(ret);
        gotoLang(url);

        return;
    });
}

function playGameNowByRef(gref, param, gameUrl, show_demo){
    showLoader(function(){
        // TODO See CH22091 for cleanup instruction
        if(enableMobileSplitMode) {
            // NEW GAME PAGE
            mgJson({action: 'get-mobile-game-page-url', game_ref: gref, show_demo: show_demo}, function(ret, status, xhr){
                goTo(llink(ret.url) + (show_demo ? '?show_demo=true' : ''), '_top');
            });
        } else {
            // OLD GAME PAGE
            mgJson({action: 'get_mobile_url', game_ref: gref, ret_url: document.URL, show_demo: show_demo}, function(ret, status, xhr){
                var url = ret.url;
                if(!empty(ret.local_url)){
                    url = ret.local_url+llink('/');
                }

                // TODO, if not the game url we need to show a popup instead, or at least a page that works on mobile. /Henrik
                // CHSID: 2595
                goTo(url, undefined, true);
            });
        }
    }, true);
}

