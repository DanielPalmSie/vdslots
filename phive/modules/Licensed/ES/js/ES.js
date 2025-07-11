const INCREASE_DEPOSIT_LIMIT_TITLE = 'increase-deposit.limit.title';
const REMOVE_DEPOSIT_LIMIT_TITLE = 'remove-deposit.limit.title';

let isRemoveDepositLimit = false
let blockReminderPopup = false

licFuncs.increaseDepositLimitTest = function() {
    isRemoveDepositLimit = false
    var params = {
        file: 'increase_deposit_limit_request',
        boxtitle: INCREASE_DEPOSIT_LIMIT_TITLE,
        closebtn: 'yes',
        module: 'Licensed'
    };

    var extraOptions = {
        width: isMobile() ? '100%' : 535,
        height: isMobile() ? '100%' : 260
    };

    extBoxAjax('get_html_popup', 'increase_deposit_limit-popup', params, extraOptions);
};


licFuncs.removeDepositLimitTest = function() {
    isRemoveDepositLimit = true;
    var params = {
        file: 'remove_deposit_limit_request',
        boxtitle: REMOVE_DEPOSIT_LIMIT_TITLE,
        closebtn: 'yes',
        module: 'Licensed'
    };

    var extraOptions = {
        width: isMobile() ? '100%' : 535,
        height: isMobile() ? '100%' : 260
    };

    extBoxAjax('get_html_popup', 'increase_deposit_limit-popup', params, extraOptions);
};

var DNI = {
    isNIE: function (v) {
        return /^[XYZ]{1}[0-9]{7}[TRWAGMYFPDXBNJZSQVHLCKE]{1}$/i.test(v)
    },
    isNIF: function (v) {
        return /^[0-9]{8}[TRWAGMYFPDXBNJZSQVHLCKE]{1}$/i.test(v)
    }
}

licFuncs.validatePersonalNumberOnRegister = function (value) {
    var dni = !value ? "" : value;

    if (dni.length !== 9 && !DNI.isNIE(dni) && !DNI.isNIF(dni)) {
        return false;
    }

    var f = "xyzXYZ".indexOf(dni[0]) % 3;
    var s = f.toString();

    if (f === -1) {
        s = dni[0];
    }

    var i = +(s + dni.slice(1, 8)) % 23;

    return "trwagmyfpdxbnjzsqvhlcket".indexOf(dni[8].toLowerCase()) === i;
};
licFuncs.invalidRegistrationStep2 = function () {
    var personal_number = $("#personal_number");
    var second_last_name = $("#lastname_second");

    second_last_name.trigger('blur');

    if (DNI.isNIF(personal_number.val())) {
        second_last_name.removeClass('skip-validation');
        return second_last_name.val() === '';
    }

    second_last_name.addClass('skip-validation');
    return false;
};
licFuncs.validateRegistrationStep2 = function () {
    var personal_number = $("#personal_number");
    var second_last_name = $('#lastname_second');

    personal_number.blur(function () {
        second_last_name.trigger('blur');
    });
    second_last_name.off('blur').blur(function () {
        $(this).removeClass('valid').removeClass('error');
        if (!DNI.isNIF(personal_number.val())) {
            return;
        }

        if (empty($(this).val())) {
            addClassError(this);
        } else {
            addClassValid(this);
        }
    });
};
// see if this can be centralized a bit, as logic is mostly duplicate on other jurisdictions too
licFuncs.showDepositLimitPrompt = function () {
    var extraOptions = isMobile() ? {width: '100%'} : {width: 800};
    var params = {
        module: 'Licensed',
        file: 'dep_lim_info_box',
        noRedirect: true
    };
    extBoxAjax('get_raw_html', 'dep-lim-info-box', params, extraOptions, top);
};

licFuncs.showExternalGameSessionPopup = function (popup, data) {
    var extSessHandler = window.extSessHandler || licFuncs.extSessHandler();
    extSessHandler.showSessionBalancePopup(popup, data)
};

licFuncs.showGameSummary = function(func, session = ''){
    var extSessHandler = window.extSessHandler || licFuncs.extSessHandler();
    extSessHandler.showGameSummary(func, session);
};

licFuncs.depositLimitPopupsHandler = function () {
    return {
        showDepositLimitPopup: function () {
            initializeDepositLimit();
            closePopup( 'increase_deposit_limit-popup', false, false);

            var params = {
                file: 'deposit_limit_questionnaire',
                boxtitle: isRemoveDepositLimit ? REMOVE_DEPOSIT_LIMIT_TITLE : INCREASE_DEPOSIT_LIMIT_TITLE,
                closebtn: 'yes',
                module: 'Licensed'
            };

            var extraOptions = {
                width: isMobile() ? '100%' : 580,
                height: isMobile() ? '100%' : 360
            };

            extBoxAjax('get_html_popup', 'increase-deposit-limit-questionnaire-popup' || 'mbox-msg', params, extraOptions);
        },

        showTestCompletePopup: function () {
            closePopup( 'increase-deposit-limit-questionnaire-popup', false, false);
            var params = {
                file: 'test_complete_popup',
                boxtitle: isRemoveDepositLimit ? REMOVE_DEPOSIT_LIMIT_TITLE : INCREASE_DEPOSIT_LIMIT_TITLE,
                closebtn: 'yes',
                module: 'Licensed'
            };

            var extraOptions = {
                width: isMobile() ? '100%' : 580,
                height: isMobile() ? '100%' : 360
            };

            extBoxAjax('get_html_popup', 'test-complete-popup' || 'mbox-msg', params, extraOptions);
        },

        sendRGResponseStatus: function(rgTestResult) {
            closePopup( 'test-complete-popup', false, false);
            if(rgTestResult === '') {
                rgTestResult = 'pass';
            }
            licJson('saveRgTestResponse', {result: rgTestResult, type: isRemoveDepositLimit ? 'remove' : 'increase'} , function (res) {
                if(res.success){
                    mboxMsg(res.msg, true);
                    if(rgTestResult === 'fail'){
                        $("#deposit-day").prop("disabled", true);
                        $("#deposit-week").prop("disabled", true);
                        $("#deposit-month").prop("disabled", true);
                        $("#rg-limits-action-button").prop("disabled", true);
                    }
                } else {
                    // Success so we reload the page to display the new limits etc.
                    jsReloadBase();
                }
            });
        }
    }
}

licFuncs.disableDepositFields = function(rg_test_created_date) {
    if(rg_test_created_date !== '') {
        var aWeekLater = new Date(rg_test_created_date);
        aWeekLater.setDate(aWeekLater.getDate() + 7);

        var currentDate = Date.now();

        var datesEqual = (currentDate > aWeekLater) ? false : true;

        $("#deposit-day").prop("disabled", datesEqual);
        $("#deposit-week").prop("disabled", datesEqual);
        $("#deposit-month").prop("disabled", datesEqual);
        $("#rg-limits-action-button").prop("disabled", datesEqual);
        $("#rg-limits-remove-button").prop("disabled", datesEqual);
    }
}

licFuncs.extSessHandler = function () {
    return {
        activeSessions: [],
        initialized: false,
        gameBusy: false,
        lastWonAmount: "0.00",
        currentBalance: 0,
        popupLock: false,
        pendingPopup: false,
        $gameContainer: null,
        isLimitReached: false,
        timeLeft: 0,
        min_bet: 0,
        highlightTimeWarning: false,
        sessionLimits: {
            initialStake: 0, // cent
            gameTimeLimit: 0, // minutes
            reminderFrequency: 0, // minutes
        },
        canTriggerBalanceBelowThresholdPopup: true,
        extGameSessionWsConnection: null,
        gameLoaded: false,

        init: function (config, popup) {
            this.initialized = true;
            if (config.isBos) {
                return;
            }

            this.prefillInitialData(config.data);

            var keep_alive_interval = null;
            this.extGameSessionWsConnection = doWs(config["wsURL"], this.onWs.bind(this), null, null, function(conn) {
                clearInterval(keep_alive_interval)
                // send ping every 30 seconds to ensure that the connection is not getting closed by browser
                keep_alive_interval = setInterval(function() {
                    conn.send(JSON.stringify({ping: true}))
                }, 30000)
            })

            this.addEventListeners();
            this.newSession(config, popup);
        },
        onWs: function (e) {
                var data = JSON.parse(e.data);

                if (data.ping) {
                    licJson('extGameSessWsStarted', { tab_id : this.tabId}, function (res) {});
                    return;
                }

                if (data.popup === 'closed_by_new_session') {
                    this.extGameSessionWsConnection.close(WS_PREVENT_RECONNECT)
                }

                if(data.popup === 'game_session_manually_closed'){
                    this.showGameSummary("showDuplicateSessionClosePopup()", data.session);
                    return;
                }

                this.prefillInitialData(data.msg);

                if (data && data.msg && data.msg.first_load) {
                    this.popUpTracker();
                }

                var freeSpins = data.msg.free_spins || 0;
                if (data.target === 'session_balance' && !freeSpins) {

                    if(data.msg.not_enough_money){
                        // player tried to bet over their current balance, we just return so that the GP can show a
                        // popup and user can place a lower bet
                        return;
                    }

                    this.updateTopBar(data.msg);

                    this.currentBalance = data.msg.balance || 0; // can be updated while round ends by a win postMessage

                    if (!this.popupLock) {
                        this.popupLock = true; // to avoid checking twice the balance on the same round (often we receive 2 balances updates per round, on bet and win)
                        GameCommunicator.waitRoundFinished(function() {
                            // spend limit reached
                            if (this.currentBalance <= 0 || this.min_bet > this.currentBalance) {
                                this.fireReachedPopup('wager');
                                this.isLimitReached = true;
                            }

                            // spend limit almost reached
                            if (!this.isLimitReached && this.isBalanceBelowThreshold(this.currentBalance)) {
                                // TODO GameCommunicator.pauseGame();
                                this.fireAlmostReachedPopup(this.timeLeft, this.highlightTimeWarning);
                            }
                            this.popupLock = false;
                        }.bind(this));
                    }

                    return;
                }

                if (!data.token) {
                    this.showSessionBalancePopup(data['popup'], {msg: data.msg});
                    return;
                }
                var session_info = this.getSessionBy('token', data.token);
                if (session_info) {
                    var options = {token: data.token, game_id: session_info.game_id};
                    if (!this.gameBusy) {
                        this.showSessionBalancePopup(data['popup'], options);
                    } else {
                        this.pendingPopup = {popup: data['popup'], options: options};
                    }
                }
            },

        updateTopBar: function (data) {
            var eventData = {
                balance: nfCents(data.account_balance !== undefined ? data.account_balance : 0),
                wagered: nfCents(data.wagered !== undefined ? data.wagered : 0),
                won: nfCents(data.won !== undefined ? data.won : 0)
            };
            if (eventData.won !== this.lastWonAmount) { // only update the topbar on next spin
                this.lastWonAmount = eventData.won;
                return;
            }

            this.doUpdateTopBar(eventData);
        },
        doUpdateTopBar: function (eventData) {

            if (isMobile()) {
                $(document).trigger('vsevent.extgamesess.updateSessionBalance', eventData);
            } else {
                $('#session_won').html(eventData.won);
                $('#session_wagered').html(eventData.wagered);
                $('#session_balance').html(eventData.balance);
            }
        },

        /**
         * Make session limit values available across the whole instance.
         * - on "init" if session limits are initialized before coming to the game page
         * - via WS if the limits are set on game page
         * @param data
         */
        prefillInitialData: function (data) {
            // avoid overriding initial limits if already set or empty
            if (!empty(this.sessionLimits.initialStake) || data === null || data.real_stake === undefined) {
                return;
            }
            // Store the game min bet to be able to show a limit popup if the balance goes below it
            if (data.min_bet) {
                this.min_bet = parseInt(data.min_bet);
            }
            // prefill initial values defined on the popup
            this.sessionLimits.initialStake = parseInt(data.real_stake);
            this.sessionLimits.gameTimeLimit = parseInt(data.game_limit);
            this.sessionLimits.reminderFrequency = parseInt(data.set_reminder);
            this.timeLeft = parseInt(data.game_limit);
            this.updateTopBar({balance: data.real_stake});
        },

        isBalanceBelowThreshold: function (currentBalance, threshold) {
            currentBalance = currentBalance || 0;
            threshold = threshold || 10;

            var spendPercentage = Math.round((currentBalance / this.sessionLimits.initialStake) * 100);

            // !==0 to avoid triggering on limit reached.
            if(spendPercentage <= threshold && spendPercentage !== 0) {
                // used to avoid triggering more than once the "almost reached" popup when below threshold.
                if(this.canTriggerBalanceBelowThresholdPopup) {
                    this.canTriggerBalanceBelowThresholdPopup = false;
                    return true;
                }
            } else {
                // if balance goes back above threshold we can trigger again.
                this.canTriggerBalanceBelowThresholdPopup = true;
            }
            return false;
        },
        newSession: function (config, popup) {
            popup = popup || 'game_session_balance_set';
            var sessionIndex = this.addToExtSess(config);
            if (!this.initialized) {
                return;
            }

            if (this.activeSessions[sessionIndex].startMode === 'splitOrDirect') {
                if (typeof this.activeSessions[sessionIndex].balance_set === 'undefined') {
                    this.showSessionBalancePopup(popup, {
                        game_id: this.activeSessions[sessionIndex].game_id,
                        sessionIndex: sessionIndex
                    });
                    this.activeSessions[sessionIndex].balance_set = true;
                }
            } else {
                this.loadGame(sessionIndex);
            }
        },

        popUpTracker: function () {
            var this_instance = this;
            var timeLeft = this.sessionLimits.gameTimeLimit;
            var gameTimeLimit = this.sessionLimits.gameTimeLimit * 60;

            var reminderFrequency = this.sessionLimits.reminderFrequency * 60;
            var elapsedTime = 0;

            var popUpTrackerInterval = setInterval(function () {
                elapsedTime++;

                if ((elapsedTime % (60)) === 0 ) {
                    this_instance.timeLeft = --timeLeft;
                }

                // We stop if current date is greater that the created time and the session limit time
                if (elapsedTime >= gameTimeLimit) {
                    clearInterval(popUpTrackerInterval);
                    this_instance.fireReachedPopup('time');
                    return;
                }

                var time_percentage = Math.round(((gameTimeLimit - elapsedTime) / gameTimeLimit) * 100);

                if (time_percentage <= 10 && !this_instance.highlightTimeWarning) {
                    this_instance.highlightTimeWarning = true;
                    this_instance.fireAlmostReachedPopup(timeLeft, this_instance.highlightTimeWarning);
                    return;
                }

                if (((elapsedTime % reminderFrequency) === 0) && (blockReminderPopup === false)) {
                    if (!this_instance.isLimitReached) {
                        this_instance.fireReminderPopup(timeLeft);
                    }
                }
            }, 1000);
        },

        fireReminderPopup: function(timeLeft) {
            var extraOptions = this.getCommonPopupExtraOptions();
            extraOptions = _.extend(extraOptions, {replaceSame: true});
            var popup = 'game_session_limit_reminder_popup';
            var options = this.getCommonPopupParams(popup);
            options = _.extend(options, {
                time_left: timeLeft,
            });
            this.closeAllPopups();
            this.showPopup(popup, options, extraOptions);
        },

        fireAlmostReachedPopup: function(timeLeft, highlightTimeWarning) {
            var extraOptions = this.getCommonPopupExtraOptions();
            extraOptions = _.extend(extraOptions, {replaceSame: true});
            var popup = 'game_session_almost_reached_limit_popup';
            var options = this.getCommonPopupParams(popup);
            options = _.extend(options, {
                time_left: timeLeft,
                time_warning: highlightTimeWarning,
            });
            this.closeAllPopups();
            this.showPopup(popup, options, extraOptions);
        },

        fireReachedPopup: function(type){
            var extraOptions = this.getCommonPopupExtraOptions();
            var popup = 'game_session_limit_reached_popup';
            var options = this.getCommonPopupParams(popup);
            options = _.extend(options, {
                show_time_reached: type === 'time'
            });

            // If there is any reminder popup open we close it first and then we show the reached popup.
            this.closeAllPopups();
            this.showPopup(popup, options, extraOptions);
        },
        showPopup: function(popup, options, extraOptions) {
            var triggerPopup = function () {
                if (isMobile() && this.initialized) {
                    mobileGameBoxAjax(options);
                } else {
                    extBoxAjax('get_html_popup', popup || 'mbox-msg', options, extraOptions);
                }
            }.bind(this);
            if (typeof GameCommunicator !== 'undefined') {
                GameCommunicator.pauseGame().then(triggerPopup());
            } else {
                triggerPopup();
            }
        },

        closePopup: function(box_id, callback) {
            if (isMobile() && this.initialized) {
                this.$gameContainer.trigger("close-external-popup");
            } else {
                $.multibox('close', box_id || 'mbox-msg', callback);
            }

            if (typeof GameCommunicator !== 'undefined') {
                GameCommunicator.resumeGame().then(function () {
                    if (callback) {
                        callback();
                    }
                });
            } else {
                if (callback) {
                    callback();
                }
            }
        },

        closeAllPopups: function () {
            this.closePopup('game_session_limit_reminder_popup');
            this.closePopup('game_session_almost_reached_limit_popup');
        },

        loadGame: function (sessionIndex) {
            if(this.gameLoaded) return;
            this.gameLoaded = true;
            var gameSession = this.activeSessions[sessionIndex];
            if (!isMobile() && this.activeSessions.length === 1) {
                GameLoader.init(DefaultMessageProcessor, document.getElementById('mbox-iframe-play-box'), gameSession["gameUrl"]);
            } else {
                this.$gameContainer.trigger("continue-game-load");
            }
        },

        submitBalance: function (token, gameLimit, spendLimit, setReminder, restrictFutureSessions, game_ref, callback) {
            if (!isNumber(gameLimit) || Number(gameLimit) <= 0) {
                $(".set-session-balance-time-limit").removeClass('hidden');
            }

            if (!isNumber(setReminder) || Number(setReminder) <= 0) {
                $(".set-session-set-reminder-error").removeClass('hidden');
            }

            if (!isNumber(spendLimit) || Number(spendLimit) <= 0) {
                $(".set-session-balance-spend-limit-insufficient").removeClass('hidden');
            }

            if(!isNumber(gameLimit) || Number(gameLimit) <= 0
                || !isNumber(setReminder) || Number(setReminder) <= 0
                || !isNumber(spendLimit) || Number(spendLimit) <= 0) {
                return;
            }

            if (restrictFutureSessions && (!isNumber(restrictFutureSessions) || Number(restrictFutureSessions) < 0)) {
                $(".set-session-restrict-future-session-error").removeClass('hidden');
                return;
            }

            var self = this
            var args = arguments
            var postData = {
                gameLimit: gameLimit,
                balance: spendLimit,
                game_ref: game_ref,
                setReminder: setReminder,
                restrictFutureSessions: restrictFutureSessions
            };

            if (token !== 0) {
                postData.token = token;
            }

            if (self.has_open_session) {
                postData.has_open_session = true;
            }

            licJson('setExternalGameSessionBalance', postData, function (res) {
                if (!res.success) {
                    if (!res.popup) {
                        saveFELogs('game_providers', 'error', 'ES::setExternalGameSessionBalance', {
                            post_data: postData,
                            result: res,
                            game_ref: game_ref,
                        })

                        if(Number(res['ext_session_fail']['user_balance']) < Number(postData['balance'])*100){
                            return $('.set-session-balance-spend-limit-over-balance').removeClass('hidden');
                        }

                        return $('.set-session-create-session-generic-error').removeClass('hidden');
                    }

                    this.closePopup('game_session_balance_set');

                    // prepare to attempt the same request when player requests new session
                    window.submitNewGameSession = function() {
                        self.has_open_session = true
                        self.submitBalance(...args)
                    }
                    return this.showSessionBalancePopup(res.popup, {submit_new_game_session: true});
                }
                this.closePopup('game_session_balance_set', callback);

                if (typeof res["newToken"] !== "undefined") {
                    this.newSession({token: res["newToken"], timeLimit: gameLimit});
                }
            }.bind(this));
        },

        getGameId: function () {
            return this.activeSessions[0].game_id;
        },

        addEventListeners: function () {
            this.$gameContainer = isMobile() ? $("#vs-games-container") : $("#play-box");
            this.$gameContainer
                .on('before-load-game', function (event, network, game_id) {
                    this.newSession({token: null, network: network, game_id: game_id});
                }.bind(this))
                .on('game-changed', function (event, network, game_id) {
                    this.endSession(game_id);
                }.bind(this))
                .on('game-closed', function (event, network, game_id) {
                    this.endSession(game_id);
                }.bind(this));
        },
        addToExtSess: function (config) {
            for (var i = 0; i < this.activeSessions.length; i++) {
                if (config.token !== null && this.activeSessions[i].token == null) {
                    this.activeSessions[i].token = config.token;
                    return i;
                }
            }

            // we use these to identify if we should launch the popup on new session
            // or launch the game
            config.startMode = empty(config.token) ? 'splitOrDirect' : 'lobbyLaunch';
            return this.activeSessions.push(config) - 1;
        },

        getSessionBy: function (field, value) {
            return this.activeSessions.find(function (x) {
                return x[field] === value;
            });
        },

        removeFromSessionByGameId: function (game_id) {
            var removed;
            for (var i = this.activeSessions.length - 1; i >= 0; i--) {
                if (typeof this.activeSessions[i].game_id !== 'undefined' && this.activeSessions[i].game_id === game_id) {
                    removed = this.activeSessions.splice(i, 1);
                    return removed;
                }
            }
        },

        getCommonPopupExtraOptions: function() {
            return isMobile() ? {} : { width: 450 };
        },

        getCommonPopupParams: function(popup) {
            return {
                module: 'Licensed',
                file: popup,
                boxtitle: 'msg.title',
                closebtn: 'no'
            };
        },

        /**
         * popups (extBoxAjax):
         * - game_session_limit_too_close_new_session_warning
         * - game_session_balance_set
         * - game_session_close_existing_and_open_new_session_prompt
         * - game_session_limit_reached_popup
         * - game_session_temporary_restriction
         *
         * messages (mboxMsg):
         * - error_starting_session
         * - closed_by_new_session
         *
         * @param popup
         * @param data
         */
        showSessionBalancePopup: function (popup, data) {
            var params = this.getCommonPopupParams(popup);
            var extraOptions = this.getCommonPopupExtraOptions();

            if (popup === 'game_session_balance_set') {
                extraOptions = isMobile() ? {} : { width: 775 };
                params.file = 'game_session_limit_set_popup';
                params.boxtitle = 'rg.info.game-session-limit.title';
                params.closebtn = 'yes';
                params.extra_css = 'lic-mbox-minimal';
                if (data && data.close_old_session) {
                    params.has_open_session = true;
                }
            } else if (popup === 'game_session_limit_reached_popup') {
                params.boxtitle = 'rg.info.game-session-balance.reached.title';
                params.show_time_reached = data.msg === 'true'; // coming via WS from hasExceededTimeLimit check
            } else if (['error_starting_session', 'closed_by_new_session'].includes(popup)) {
                // WS event was fired before the game_session_balance_set finished loading
                if (window.top.popup_show_in_progress != null) {
                    return;
                }
                var el = isMobile() ? extSessHandler.$gameContainer : $('#mbox-iframe-play-box');
                licFuncs.unloadGame(el);

                mboxMsg(data.msg, true, function () {
                    gotoLang('/');
                }, 260);
                var el = isMobile() ? extSessHandler.$gameContainer : $('#mbox-iframe-play-box');
                licFuncs.unloadGame(el);
                return;
            } else if (popup === 'game_session_restart'){
                jsReloadBase();
                return;
            } else if (popup === 'balance_session_freespins_finished') {
                mboxMsg(data.msg, false, function () {
                    jsReloadBase();
                }, 260);
                // we reload the page after 5 seconds if no user action
                window.setTimeout(jsReloadBase, 5000);

                return;

            }

            if (!popup) {
                return
            }
            Object.assign(params, data);

            this.showPopup(popup, params, extraOptions);
        },

        showGameSummary: function(func, session = ''){
            blockReminderPopup = true; //we block the game session limit reminder to stop it from appearing when this popup is triggerred
            var options = this.getCommonPopupParams();
            var extraOptions = this.getCommonPopupExtraOptions();

            options = _.extend(options, {
                time_left: this.timeLeft,
                session: session,
                redirect_func: func
            });

            options.file = 'game_session_manually_closed';
            options.boxtitle = 'message';

            this.showPopup('manually_closed_session', options, extraOptions);
        },

        showClosedByNewSessionPopup: function(){
            blockReminderPopup = true;
            const popupData = {
                 data: JSON.stringify({
                    popup: 'closed_by_new_session',
                    msg: 'Esta sesión de juego se cerró, ya que comenzaste una nueva en otra página.'
                })
            };
            this.onWs(popupData)
        },

        getIframeTargetByNetwork: function (network) {
            return $("[data-game-network='" + network + "']").find('iframe')[0];
        },

        getIframeTargetByGameId: function (game_id) {
            return $("[data-game-id='" + game_id + "']").find('iframe')[0];
        },

        sendEndSession: function () {
            licJson('finishExternalGameSession', {
                user_id : JSON.stringify(userId),
                device_type : JSON.stringify(isMobile()),
            }, function (res) {});
        },

        endSession: function (game_id) {
            var endingSession = this.removeFromSessionByGameId(game_id);
            if (endingSession && typeof endingSession[0].token !== 'undefined') {
                this.sendEndSession()
            }
        }
    };
};

licFuncs.downloadAccountHistory = function() {
    let url = "/phive/modules/Licensed/ES/xhr/actions.php?action=downloadaccounthistory";
    window.location.href = url;
};
