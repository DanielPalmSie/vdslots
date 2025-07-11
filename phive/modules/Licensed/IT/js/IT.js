licFuncs.showDepositLimitPrompt = function () {
    return false;
};

licFuncs.gameSessionHistoryPopup = function (session_id) {
    var params = {
        module: "Licensed",
        file: "game_session_details_view",
        boxtitle: "session.balance.popup.header",
        closebtn: "yes",
        session_id: session_id,
    };
    extBoxAjax("get_html_popup", "mbox-msg", params);
};

licFuncs.showExternalGameSessionPopup = function (popup, data) {
    var extSessHandler = licFuncs.extSessHandler();
    extSessHandler.showSessionBalancePopup(popup, data);
};

licFuncs.extSessHandler = function () {
    return {
        activeSessions: [],
        initialized: false,
        gameBusy: false,
        pendingBalanceReload: false,
        pendingPopup: false,
        $gameContainer: null,
        tabId: null,

        init: function (config, notUsed) {
            this.initialized = true;
            this.tabId = config.tabId;
            doWs(
                config["wsURL"],
                function (e) {
                    var data = JSON.parse(e.data);
                    if (data.ping) {
                        licJson(
                            "extGameSessWsStarted",
                            { tab_id: this.tabId },
                            function (res) {}
                        );
                        return;
                    }
                    if (data.ingame_new_session) {
                        this.displayTopBarSessionInfo({
                            ext_session_id: data.ext_session_id,
                            participation_id: data.participation_id,
                        });
                        return;
                    }
                    if (!data.token) {
                        if (!this.gameBusy) {
                            this.showSessionBalancePopup(data["popup"], {
                                msg: data.msg,
                            });
                        } else {
                            this.pendingPopup = {
                                popup: data["popup"],
                                options: { msg: data.msg },
                            };
                        }
                        return;
                    }
                    var session_info = this.getSessionByToken(data.token);
                    if (session_info) {
                        if (
                            data.popup &&
                            this.networkSupportsAction(data.popup, session_info)
                        ) {
                            var options = {
                                token: data.token,
                                game_id: session_info.game_id,
                            };
                            if (!this.gameBusy) {
                                this.showSessionBalancePopup(
                                    data["popup"],
                                    options
                                );
                            } else {
                                this.pendingPopup = {
                                    popup: data["popup"],
                                    options: options,
                                };
                            }
                        } else if (data.ext_session_id) {
                            session_info = this.updateSession(data.token, {
                                ext_session_id: data.ext_session_id,
                                participation_id: data.participation_id,
                            });
                            this.displayTopBarSessionInfo(session_info);
                        }
                    }
                }.bind(this)
            );

            this.addEventListeners();

            this.newSession(config);

            $(document).trigger("extSessionHandlerLoaded");
        },

        newSession: function (config) {
            var sessionIndex = this.addToExtSess(config);
            if (!this.initialized) {
                return;
            }
            if (
                this.activeSessions[sessionIndex].startMode === "splitOrDirect"
            ) {
                if (
                    typeof this.activeSessions[sessionIndex].balance_set ===
                    "undefined"
                ) {
                    this.showSessionBalancePopup("game_session_balance_set", {
                        game_id: this.activeSessions[sessionIndex].game_id,
                        sessionIndex: sessionIndex,
                    });
                    this.activeSessions[sessionIndex].balance_set = true;
                }
            } else {
                this.loadGame(sessionIndex);
            }
        },

        loadGame: function (sessionIndex) {
            var gameSession = this.activeSessions[sessionIndex];
            if (!isMobile() && this.activeSessions.length === 1) {
                GameLoader.init(
                    DefaultMessageProcessor,
                    document.getElementById("mbox-iframe-play-box"),
                    gameSession["gameUrl"]
                );
            } else {
                this.$gameContainer.trigger("continue-game-load");
            }
            this.setRebuyHandler(sessionIndex);
        },

        submitBalance: function (
            token,
            max_limit_balance,
            total_balance,
            game_ref,
            callback
        ) {
            var input = $("#set-game-session-balance");
            var balance = input.val();
            if (
                !isNumber(balance) ||
                balance <= 0 ||
                balance > total_balance / 100
            ) {
                $(".set-session-balance-over-limit").removeClass("hidden");
                input.val(Math.min(total_balance / 100, max_limit_balance));
                input.addClass("error");
                if (typeof callback === "function") callback();
                return;
            }
            if (balance > max_limit_balance) {
                input.val(max_limit_balance);
                input.addClass("error");
                if (typeof callback === "function") callback();
                return;
            }

            var postData = {
                balance: balance,
                game_ref: game_ref,
                tab_id: this.tabId,
            };

            var csrf_token =
                document.querySelector('meta[name="csrf_token"]').content || "";
            if (csrf_token.trim() !== "") {
                postData.csrf_token = csrf_token;
            }

            if (token !== 0) {
                postData.token = token;
            }
            licJson(
                "updateExternalGameSessionBalance",
                postData,
                function (res) {
                    if (!res.success) {
                        input.addClass("error");
                        return;
                    }
                    if (typeof res["newToken"] !== "undefined") {
                        this.newSession({ token: res["newToken"] });
                    }

                    var newBalance = null;
                    var result = res.result || {};
                    if (result.newBalance) {
                        newBalance = result.newBalance;
                    }

                    this.reloadBalance(
                        res["newToken"] ? res["newToken"] : postData.token,
                        newBalance
                    );

                    if (typeof callback === "function") {
                        callback();
                    }
                    this.closePopup();
                }.bind(this)
            );
        },

        addEventListeners: function () {
            this.$gameContainer = isMobile()
                ? $("#vs-games-container")
                : $("#play-box");
            this.$gameContainer
                .on(
                    "before-load-game",
                    function (event, network, game_id) {
                        this.newSession({
                            token: null,
                            network: network,
                            game_id: game_id,
                        });
                    }.bind(this)
                )
                .on(
                    "game-loaded",
                    function (event, network, game_id) {}.bind(this)
                )
                .on(
                    "game-changed",
                    function (event, network, game_id) {
                        this.endSession(game_id);
                    }.bind(this)
                )
                .on(
                    "game-closed",
                    function (event, network, game_id) {
                        this.endSession(game_id);
                    }.bind(this)
                );
        },
        addToExtSess: function (config) {
            for (var i = 0; i < this.activeSessions.length; i++) {
                if (
                    config.token !== null &&
                    this.activeSessions[i].token == null
                ) {
                    this.activeSessions[i].token = config.token;
                    return i;
                }
            }
            config.startMode = empty(config.token)
                ? "splitOrDirect"
                : "lobbyLaunch";
            return this.activeSessions.push(config) - 1;
        },
        updateSession: function (token, config) {
            for (var i = 0; i < this.activeSessions.length; i++) {
                if (this.activeSessions[i].token === token) {
                    var session = this.activeSessions[i];
                    Object.assign(session, config);
                    this.activeSessions[i] = session;
                    return session;
                }
            }
        },
        getSessionByToken: function (token) {
            return this.activeSessions.find(function (x) {
                return x.token === token;
            });
        },

        getSessionByNetwork: function (network) {
            return this.activeSessions.find(function (x) {
                return x.network === network;
            });
        },

        getSessionByGameId: function (game_id) {
            return this.activeSessions.find(function (x) {
                return x.game_id === game_id;
            });
        },
        removeFromSessionByGameId: function (game_id) {
            var removed;
            for (var i = this.activeSessions.length - 1; i >= 0; i--) {
                if (
                    typeof this.activeSessions[i].game_id !== "undefined" &&
                    this.activeSessions[i].game_id === game_id
                ) {
                    if ("rebuyHandler" in this.activeSessions[i]) {
                        this.activeSessions[
                            i
                        ].rebuyHandler.removeEventListeners();
                    }
                    removed = this.activeSessions.splice(i, 1);
                    return removed;
                }
            }
        },

        showSessionBalancePopup: function (popup, data) {
            var params = {
                module: "Licensed",
            };
            var close_selector = "";
            var callback;

            if (popup === "game_session_balance_set") {
                params.file = "game_session_balance_set_popup";
                params.boxtitle = "rg.info.game-session-balance.set.title";
                params.closebtn = "no";
                close_selector = ".set-session-balance-button";
            } else if (popup === "balance_addin_popup") {
                params.file = "game_session_balance_addin_popup";
                params.boxtitle = "rg.info.game-session-balance.set.title";
                params.closebtn = "yes";
                close_selector = ".set-session-balance-button";
            } else if (popup === "game_session_add_stake_popup") {
                params.file = "game_session_add_stake_popup";
                params.boxtitle = "rg.info.game-session-balance.set.title";
                params.closebtn = "yes";
                close_selector = ".set-session-balance-button";
            } else if (popup === "balance_session_reached_popup") {
                params.file = "game_session_balance_reached_popup";
                params.boxtitle = "rg.info.game-session-balance.reached.title";
                params.closebtn = "no";
                close_selector = ".set-session-balance-button";
            } else if (popup === "error_starting_session") {
                mboxMsg(
                    data.msg,
                    false,
                    function () {
                        goTo(llink("/"));
                    },
                    260
                );
                var el = isMobile()
                    ? extSessHandler.$gameContainer
                    : $("#mbox-iframe-play-box");
                licFuncs.unloadGame(el);
                return;
            } else if (popup === "balance_session_freespins_finished") {
                mboxMsg(
                    data.msg,
                    true,
                    function () {
                        jsReloadBase();
                    },
                    260
                );
                // we reload the page after 5 seconds if no user action
                window.setTimeout(jsReloadBase, 5000);

                return;
            } else {
                return;
            }

            params.callb = function () {
                $(document).off("click", close_selector);
                if (typeof callback == "function") {
                    $(document).on("click", close_selector, function () {
                        setTimeout(callback, 500);
                    });
                }
            };

            Object.assign(params, data);

            var csrf_token =
                document.querySelector('meta[name="csrf_token"]').content || "";
            if (csrf_token.trim() !== "") {
                params.csrf_token = csrf_token;
            }

            if (isMobile() && this.initialized) {
                mobileGameBoxAjax(params);
            } else {
                extBoxAjax("get_html_popup", "mbox-msg", params);
            }
        },

        closePopup: function () {
            if (isMobile() && this.initialized) {
                this.$gameContainer.trigger("close-external-popup");
            } else {
                $.multibox("close", "mbox-msg");
            }
        },
        networkSupportsAction: function (action, session_info) {
            if (
                action === "balance_addin_popup" &&
                session_info.network === "microgaming"
            ) {
                return false;
            }
            return true;
        },

        setRebuyHandler: function (sessionIndex) {
            setTimeout(
                function () {
                    this.startRebuyHandler(sessionIndex);
                }.bind(this),
                10000
            );
        },

        startRebuyHandler: function (sessionIndex) {
            var network = this.activeSessions[sessionIndex].network;
            switch (network) {
                case "playngo":
                    this.activeSessions[sessionIndex].rebuyHandler =
                        this.getPlayngoRebuyHandler();
                    break;
                case "netent":
                    this.activeSessions[sessionIndex].rebuyHandler =
                        this.getNetentRebuyHandler();
                    break;
                case "microgaming":
                    this.activeSessions[sessionIndex].rebuyHandler =
                        this.getMicrogamingRebuyHandler();
                    break;
                case "pragmatic":
                    this.activeSessions[sessionIndex].rebuyHandler =
                        this.getPragmaticRebuyHandler();
                    break;
                case "skywind":
                    this.activeSessions[sessionIndex].rebuyHandler =
                        this.getSkywindRebuyHandler();
                    break;
                case "wazdan":
                    this.activeSessions[sessionIndex].rebuyHandler =
                        this.getWazdanRebuyHandler();
                    break;
                case "nyx":
                    this.activeSessions[sessionIndex].rebuyHandler =
                        this.getNyxRebuyHandler();
                    break;
                default:
                    return;
            }
            this.activeSessions[sessionIndex].rebuyHandler.init(
                this,
                this.activeSessions[sessionIndex].game_id
            );
        },

        reloadBalance: function (token, newBalance) {
            var sessionData = this.getSessionByToken(token);
            if (!("rebuyHandler" in sessionData) || !this.initialized) {
                return;
            }
            sessionData.rebuyHandler.reloadBalance(newBalance);
        },

        getIframeTargetByNetwork: function (network) {
            return $("[data-game-network='" + network + "']").find("iframe")[0];
        },

        getIframeTargetByGameId: function (game_id) {
            return $("[data-game-id='" + game_id + "']").find("iframe")[0];
        },
        getSkywindRebuyHandler: function () {
            return {
                source: null,
                parentContext: null,
                game_id: null,
                init: function (parentContext, game_id) {
                    this.parentContext = parentContext;
                    this.game_id = game_id;
                    var iframe = this.parentContext.getIframeTargetByGameId(
                        this.game_id
                    );
                    if (typeof iframe === "undefined") {
                        return;
                    }
                    this.source = iframe.contentWindow;
                    this.addGameEventListeners();
                },
                addGameEventListeners: function () {
                    window.addEventListener(
                        "message",
                        function (event) {
                            if (!event.data) return;
                            var data = JSON.parse(event.data);

                            switch (data.msgId) {
                                case "sw2opRound":
                                    if (data.state === "started") {
                                        this.parentContext.gameRoundStarted();
                                        break;
                                    } else if (data.state === "ended") {
                                        this.parentContext.gameRoundEnded();
                                    }
                            }
                        }.bind(this)
                    );
                },
                reloadBalance: function () {
                    var iframe = this.parentContext.getIframeTargetByGameId(
                        this.game_id
                    );
                    try {
                        iframe.contentWindow.postMessage(
                            JSON.stringify({ msgId: "op2swUpdateBalance" }),
                            "*"
                        );
                    } catch (e) {
                        saveFELogs(
                            "game_providers",
                            "error",
                            "Skywind::getSkywindRebuyHandler",
                            {
                                message: e,
                                iframe: typeof iframe,
                                gameId: this.game_id,
                            }
                        );
                    }
                },
            };
        },
        getWazdanRebuyHandler: function () {
            return {
                source: null,
                parentContext: null,
                game_id: null,
                init: function (parentContext, game_id) {
                    this.parentContext = parentContext;
                    this.game_id = game_id;
                    var iframe = this.parentContext.getIframeTargetByGameId(
                        this.game_id
                    );
                    if (typeof iframe === "undefined") {
                        return;
                    }
                    this.source = iframe.contentWindow;
                    this.addGameEventListeners();
                },
                addGameEventListeners: function () {
                    window.addEventListener(
                        "message",
                        function (event) {
                            if (!event.data) return;

                            switch (event.data.method) {
                                case "WGEAPI.roundStarted":
                                    this.parentContext.gameRoundStarted();
                                    break;
                                case "WGEAPI.roundEnded":
                                    this.parentContext.gameRoundEnded();
                                    break;
                                // case 'WGEAPI.insufficientCredits':
                                //     this.parentContext.gameRoundEnded();
                                //     break;
                            }
                        }.bind(this)
                    );
                },
                reloadBalance: function () {
                    var iframe = this.parentContext.getIframeTargetByGameId(
                        this.game_id
                    );
                    try {
                        iframe.contentWindow.postMessage(
                            { method: "WGEAPI.updateBalance" },
                            "*"
                        );
                    } catch (e) {
                        saveFELogs(
                            "game_providers",
                            "error",
                            "Wazdan::getWazdanRebuyHandler",
                            {
                                message: e,
                                iframe: typeof iframe,
                                gameId: this.game_id,
                            }
                        );
                    }
                },
            };
        },
        getNetentRebuyHandler: function () {
            return {
                source: null,
                parentContext: null,
                game_id: null,
                postMessageListener: null,
                eventSender: null,

                init: function (parentContext, game_id) {
                    this.parentContext = parentContext;
                    this.game_id = game_id;
                    if (typeof gameFi !== "undefined") {
                        this.eventSender = gameFi;
                    }
                    this.addGameEventListeners();
                },
                addGameEventListeners: function () {
                    if (!isMobile()) {
                        this.eventSender.addEventListener(
                            "gameRoundStarted",
                            function () {
                                parent.extSessHandler.gameRoundStarted.call(
                                    this.parentContext
                                );
                            }.bind(this)
                        );
                        this.eventSender.addEventListener(
                            "gameRoundEnded",
                            function () {
                                parent.extSessHandler.gameRoundEnded.call(
                                    this.parentContext
                                );
                            }.bind(this)
                        );
                    } else {
                        this.postMessageListener = window.addEventListener(
                            "message",
                            function (event) {
                                if (
                                    !event.data ||
                                    typeof event.data[1] !== "string"
                                )
                                    return false;
                                switch (event.data[1]) {
                                    case "gameRoundStarted":
                                        this.parentContext.gameRoundStarted();
                                        break;
                                    case "gameRoundEnded":
                                        this.parentContext.gameRoundEnded();
                                        break;
                                }
                            }.bind(this)
                        );
                    }
                },
                removeEventListeners: function () {
                    window.removeEventListener(
                        "message",
                        this.postMessageListener
                    );
                },
                reloadBalance: function () {
                    if (isMobile()) {
                        var iframe = this.parentContext.getIframeTargetByGameId(
                            this.game_id
                        );
                        try {
                            iframe.contentWindow.frames[0].postMessage(
                                { rebuySuccess: true },
                                "*"
                            );
                        } catch (e) {}
                    } else {
                        if (!this.parentContext.gameBusy) {
                            this.eventSender.call(
                                "reloadBalance",
                                [],
                                function () {},
                                function () {}
                            );
                        } else {
                            this.parentContext.pendingBalanceReload =
                                this.game_id;
                        }
                    }
                },
            };
        },

        getPlayngoRebuyHandler: function () {
            return (function () {
                return {
                    iframe: null,
                    source: null,
                    eventListener: null,
                    parentContext: null,
                    game_id: null,
                    postMessageListener: null,

                    init: function (parentContext, game_id) {
                        this.parentContext = parentContext;
                        this.game_id = game_id;
                        var iframe = this.parentContext.getIframeTargetByGameId(
                            this.game_id
                        );
                        if (typeof iframe === "undefined") {
                            return;
                        }

                        var events = [
                            "roundStarted",
                            "roundEnded",
                            "backToLobby",
                        ];
                        this.source = iframe.contentWindow;
                        for (var i = 0; i < events.length; i++) {
                            this.source.postMessage(
                                {
                                    messageType: "addEventListener",
                                    eventType: events[i],
                                },
                                "*"
                            );
                        }
                        this.eventListener = this.messageListener.bind(this);
                        this.postMessageListener = window.addEventListener(
                            "message",
                            this.eventListener
                        );
                    },
                    messageListener: function (event) {
                        var type = event.data.type || event.data;
                        switch (type) {
                            case "spinEnded":
                            case "roundEnded":
                                this.parentContext.gameRoundEnded();
                                break;
                            case "spinStarted":
                            case "roundStarted":
                                this.parentContext.gameRoundStarted();
                                break;
                            case "backToLobby":
                                goTo(llink("/"));
                                break;
                        }
                    },
                    removeEventListeners: function () {
                        window.removeEventListener(
                            "message",
                            this.postMessageListener
                        );
                    },
                    reloadBalance: function () {
                        if (this.parentContext.gameBusy) {
                            this.parentContext.pendingBalanceReload =
                                this.game_id;
                            return;
                        }
                        if (isMobile()) {
                            this.source.postMessage(
                                {
                                    messageType: "request",
                                    request: "refreshBalance",
                                },
                                "*"
                            );
                        } else {
                            if (typeof gameFi === "undefined") {
                                return;
                            }
                            gameFi.toFrame("reloadBalance");
                        }
                    },
                };
            })();
        },
        getMicrogamingRebuyHandler: function () {
            return {
                source: null,
                parentContext: null,
                game_id: null,
                eventSender: null,

                init: function (parentContext, game_id) {
                    this.parentContext = parentContext;
                    this.game_id = game_id;
                    var iframe = this.parentContext.getIframeTargetByGameId(
                        this.game_id
                    );
                    if (typeof iframe === "undefined") {
                        console.log("iframe undefined");
                        return;
                    }
                    this.source = iframe.contentWindow;
                    this.addGameEventListeners();
                },
                addGameEventListeners: function () {
                    window.addEventListener(
                        "message",
                        function (event) {
                            if (event.source !== this.source || !event.data)
                                return;
                            var data;
                            try {
                                data = JSON.parse(event.data);
                            } catch (e) {
                                return;
                            }
                            switch (data.event) {
                                case "gameBusy":
                                    this.parentContext.gameRoundStarted();
                                    break;
                                case "gameNotBusy":
                                    this.parentContext.gameRoundEnded();
                                    break;
                            }
                        }.bind(this)
                    );
                },
            };
        },

        getNyxRebuyHandler: function () {
            return {
                source: null,
                parentContext: null,
                game_id: null,
                eventSender: null,

                init: function (parentContext, game_id) {
                    this.game_id = game_id;
                    this.parentContext = parentContext;
                    var iframe = this.parentContext.getIframeTargetByGameId(
                        this.game_id
                    );

                    if (typeof iframe !== "undefined") {
                        this.source = iframe.contentWindow;
                        this.addGameEventListeners();
                    }
                },
                addGameEventListeners: function () {
                    window.addEventListener(
                        "message",
                        function (event) {
                            var eventData = JSON.parse(event.data);

                            if (!eventData || !eventData.gcmevent) {
                                return;
                            }

                            switch (eventData.gcmevent) {
                                case "gameAnimationStart":
                                    this.parentContext.gameRoundStarted();
                                    break;
                                case "gameAnimationComplete":
                                    this.parentContext.gameRoundEnded();
                                    break;
                            }
                        }.bind(this)
                    );
                },
                reloadBalance: function (balance) {
                    if (typeof balance === "string") {
                        balance = parseFloat(balance);
                    }

                    // need to do this bullshit to work with nyx library (nyx_gcm.js)
                    if (nyxGcm !== undefined) {
                        setTimeout(function () {
                            var retries = 3;
                            for (var i = 0; i < retries; i++) {
                                nyxGcm.updateBalance(balance);
                            }
                        }, 5000);
                    } else {
                        saveFELogs(
                            "game_providers",
                            "error",
                            "Nyx::getNyxRebuyHandler",
                            {
                                message: "Nyx GCM library not initilized. ",
                                iframe: typeof iframe,
                                gameId: this.game_id,
                            }
                        );
                    }
                },
            };
        },

        getPragmaticRebuyHandler: function () {
            return {
                source: null,
                parentContext: null,
                game_id: null,
                init: function (parentContext, game_id) {
                    this.parentContext = parentContext;
                    this.game_id = game_id;
                    var iframe = this.parentContext.getIframeTargetByGameId(
                        this.game_id
                    );
                    if (typeof iframe === "undefined") {
                        saveFELogs(
                            "game_providers",
                            "error",
                            "Pragmatic::getPragmaticRebuyHandler",
                            {
                                iframe: typeof iframe,
                                gameId: game_id,
                                message:
                                    "error initializing pragmatic rebuy handler",
                            }
                        );
                        return;
                    }
                    this.source = iframe.contentWindow;
                    this.addGameEventListeners();
                },
                addGameEventListeners: function () {
                    window.addEventListener(
                        "message",
                        function (event) {
                            if (!event.data) return;

                            switch (event.data.name) {
                                case "gameRoundStarted":
                                    this.parentContext.gameRoundStarted();
                                    break;
                                case "gameRoundEnded":
                                    this.parentContext.gameRoundEnded();
                                    break;
                                default:
                            }
                        }.bind(this)
                    );
                },
                reloadBalance: function () {
                    var iframe = this.parentContext.getIframeTargetByGameId(
                        this.game_id
                    );
                    try {
                        iframe.contentWindow.postMessage("updateBalance", "*");
                    } catch (e) {
                        saveFELogs(
                            "game_providers",
                            "error",
                            "Pragmatic::getPragmaticRebuyHandler",
                            {
                                message: e,
                                iframe: typeof iframe,
                                gameId: this.game_id,
                            }
                        );
                    }
                },
            };
        },

        gameRoundStarted: function () {
            this.gameBusy = true;
        },
        gameRoundEnded: function () {
            this.gameBusy = false;
            if (this.pendingBalanceReload !== false) {
                var session = this.getSessionByGameId(
                    this.pendingBalanceReload
                );
                if (
                    session.rebuyHandler[this.pendingBalanceReload] &&
                    typeof session.rebuyHandler[
                        this.pendingBalanceReload
                    ].reloadBalance() === "function"
                ) {
                    session.rebuyHandler[
                        this.pendingBalanceReload
                    ].reloadBalance();
                }
                this.pendingBalanceReload = false;
            } else if (this.pendingPopup !== false) {
                this.showSessionBalancePopup(
                    this.pendingPopup.popup,
                    this.pendingPopup.options
                );
                this.pendingPopup = false;
            }
        },

        sendEndSession: function (token) {
            var fd = new FormData();
            fd.append("lic_func", "finishExternalGameSession");
            fd.append("token", JSON.stringify(token));
            fd.append("user_id", JSON.stringify(userId));
            navigator.sendBeacon(licUrl, fd);
        },

        endSession: function (game_id) {
            var endingSession = this.removeFromSessionByGameId(game_id);
            if (endingSession && typeof endingSession.token !== "undefined") {
                this.sendEndSession(endingSession.token);
            }
        },

        displayTopBarSessionInfo: function (session_info) {
            $("#top-bar-participation-id-value").html(
                session_info.participation_id
            );
            $("#top-bar-ext-session-id-value").html(
                session_info.ext_session_id
            );
            if (isMobile()) {
                $(
                    ".vs-licensing-strip__item-container__img-container"
                ).removeClass("hidden");
            } else {
                $("#top-bar-add-funds button").removeClass("hidden");
            }
        },
    };
};

licFuncs.rgSubmitDepositLimitsDuringRegistration = function (
    rg_login_info_callback,
    enforcedNonEmptyValidation,
    closeSelf
) {
    var toPost = [];
    var validation = false;

    // IDs need to be generated like this in PHP: "resettable-{$type}-{$tspan}"
    $('[id^="resettable-"]').each(function () {
        el = $(this);
        tmp = el.attr("id").split("-");
        toPost.push({ type: tmp[1], time_span: tmp[2], limit: el.val() });
    });

    toPost = _.groupBy(toPost, function (el) {
        return el.type;
    });

    _.each(toPost, function (limits, type) {
        // reset all validation error before validate again
        _.each(limits, function (limit) {
            $("#resettable-" + type + "-" + limit.time_span)
                .addClass("discreet-border required-input")
                .removeClass("input-error");
        });

        // validate limit
        validation = licFuncs.rgValidateResettable(limits, type, true);

        if (validation !== true) {
            _.each(validation, function (tspan) {
                $("#resettable-" + type + "-" + tspan)
                    .removeClass("discreet-border required-input")
                    .addClass("input-error");
            });

            return false;
        }
    });

    if (validation !== true) {
        return false;
    }

    limits = toPost.deposit;

    // post save ...
    licJson("saveRegistrationDepositLimits", toPost, function (res) {
        if (!res.success) {
            input.addClass("error");
            return;
        }

        $.multibox("close", "rg-login-box");
    });
};

/**
 * @param params
 * @param form
 * @param callback
 */
licFuncs.xhrRegPostCall = function (params, callback) {
    let url = "/phive/modules/DBUserHandler/xhr/registration.php";
    $.post(url, params, function (data) {
        let data_json = JSON.parse(data);
        callback(data_json);
    });
};

licFuncs.xhrRegGetCall = function (params, callback) {
    let url = "/phive/modules/DBUserHandler/xhr/registration.php";
    $.get(url, params).done(function (jsonData) {
        let data = JSON.parse(jsonData);
        callback(data);
    });
};

/**
 * @param main_country
 */
licFuncs.controlProvinceResidenceFields = function (main_country) {
    let country_code = $(main_country).val();
    let main_province = $("#main_province");
    let main_city = $("#main_city");

    if (country_code !== "IT" && country_code !== "") {
        main_province.find('option[value=""]').attr("selected", true);
        main_province.prop("disabled", "disabled");
        main_city.find('option[value=""]').attr("selected", true);
        main_city.prop("disabled", "disabled");
        addClassValid($("#main_province"));
        addClassValid($("#main_city"));
        addClassStyledSelectValid(main_province);
        addClassStyledSelectValid(main_city);
        return;
    } else {
        addClassError($("#main_province"));
        addClassError($("#main_city"));
        addClassStyledSelectError(main_province);
        addClassStyledSelectError(main_city);
    }
    main_province.prop("disabled", false);
    main_city.prop("disabled", false);
};

/**
 * @param birth_country
 */
licFuncs.controlProvinceBirthFields = function (birth_country) {
    let country_code = $(birth_country).val();
    let birth_province = $("#birth_province");
    let birth_city = $("#birth_city");

    if (country_code !== "IT") {
        birth_province.find('option[value=""]').attr("selected", true);
        birth_province.prop("disabled", "disabled");
        birth_city.find('option[value=""]').attr("selected", true);
        birth_city.prop("disabled", "disabled");
        return;
    }
    birth_province.prop("disabled", false);
    birth_city.prop("disabled", false);
};

licFuncs.zipCodeValidation = function () {
    let zipcode = $("#zipcode");
    let main_country = $("#main_country");

    if (hasEmoji(zipcode.val())) {
        return;
    }

    if (zipcode.val().length != 5 && main_country.val() === "IT") {
        addClassError("#zipcode");
        zipcode.val("");
    } else {
        addClassValid("#zipcode");
    }
};

/**
 * Overwriting Licensed.js method
 *
 * @param arr
 * @param enforcedNonEmptyValidation
 * @returns {boolean|*[]}
 */
licFuncs.rgValidateResettableCommon = function (
    arr,
    enforcedNonEmptyValidation
) {
    var map = _.reduce(
        arr,
        function (result, el, index) {
            if (
                empty(el.limit) ||
                isNaN(el.limit) ||
                parseInt(el.limit) <= 0 ||
                parseInt(el.limit) > Number.MAX_SAFE_INTEGER
            ) {
                result.push(el.time_span);
            }

            if (index > 0) {
                for (var i = 0; i < index; i++) {
                    if (parseInt(el.limit) < parseInt(arr[i].limit)) {
                        result.push(el.time_span);
                        break;
                    }
                }
            }

            return result;
        },
        []
    );

    return empty(map) ? true : map;
};

licFuncs.handleAddFunds = function () {
    var session = window.extSessHandler.activeSessions[0];
    window.extSessHandler.showSessionBalancePopup(
        "game_session_add_stake_popup",
        { token: session.token }
    );
};

$(document).ready(function () {
    $("#fiscal_code").blur(function () {
        if ($(this).val() !== "") {
            let tax_code = $(this);
            let param = {
                extract_tax_code: true,
                country: regPrePops["country"],
                ajax_context: true,
                tax_code: $(this).val(),
            };
            licFuncs.xhrRegPostCall(param, function (data_json) {
                addClassValid(tax_code);
                if (data_json.code === 200) {
                    let tax_code_return = data_json.tax_code;
                    if (tax_code_return.birthDate !== undefined) {
                        [year, month, day] =
                            tax_code_return.birthDate.split("-");
                        $("#birthyear").val(year);
                        $("#birthmonth").val(month);
                        $("#birthdate").val(day);
                    }

                    if (
                        tax_code_return.municipal_territorial_unit !==
                            undefined &&
                        tax_code_return.municipal_territorial_unit.length > 0
                    ) {
                        $("#birth_country").val("IT").change();
                        $("#birth_province")
                            .val(tax_code_return.automotive_code)
                            .change();
                    }
                    if (
                        tax_code_return.denomination !== undefined &&
                        tax_code_return.denomination.length > 0
                    ) {
                        setTimeout(function () {
                            $("#birth_city").val(tax_code_return.denomination);
                        }, 350);
                    }
                    if (
                        tax_code_return.gender !== undefined &&
                        tax_code_return.gender.length > 0
                    ) {
                        if (tax_code_return.gender === "M") {
                            $("#male").prop("checked", true);
                        } else {
                            $("#female").prop("checked", true);
                        }
                    }
                } else {
                    addClassError(tax_code);
                    $(tax_code).val("");
                }
            });
        }
    });

    $("#main_province").change(function () {
        let province_value = $(this).val();
        let main_city = $("#main_city");
        if (province_value !== undefined && province_value.length > 0) {
            let param = {
                get_municipality_by_province_list: true,
                province: province_value,
                country: regPrePops["country"],
                ajax_context: true,
            };
            licFuncs.xhrRegGetCall(param, function (data_json) {
                let option = main_city.find("option")[0];
                main_city.empty().append(option);
                $.each(data_json, function (index, value) {
                    main_city.append($("<option />").val(index).text(value));
                });
            });
        }
    });

    $("#birth_country").change(function () {
        licFuncs.controlProvinceBirthFields(this);
        var birthCountry = $("#birth_country option:selected").val();
        var otherDoc = $("#doc_type option[value='10']");

        $("#doc_type option:selected").removeAttr("selected");
        $("#doc_type option:first-child").prop("selected", true);
        if (birthCountry === "IT") {
            if (!$(otherDoc).parent().is("span")) $(otherDoc).wrap("<span>");
            otherDoc.parent().hide();
        } else {
            if ($(otherDoc).parent().is("span")) $(otherDoc).unwrap();
            otherDoc.prop("selected", false);
        }
    });

    $("#main_country").change(function () {
        licFuncs.controlProvinceResidenceFields(this);
    });

    $("#birth_province").change(function () {
        let province_value = $(this).val();
        let birth_city = $("#birth_city");
        if (province_value !== undefined && province_value.length > 0) {
            let param = {
                get_all_municipality_by_province_list: true,
                province: province_value,
                country: regPrePops["country"],
                ajax_context: true,
            };
            licFuncs.xhrRegGetCall(param, function (data_json) {
                let option = birth_city.find("option")[0];
                birth_city.empty().append(option);
                $.each(data_json, function (index, value) {
                    birth_city.append($("<option />").val(index).text(value));
                });
            });
        }
    });

    $("#doc_type").change(function () {
        let doc_type_id = $(this).val();

        if (doc_type_id !== undefined && doc_type_id.length > 0) {
            let param = {
                get_issuing_authority_list: true,
                doc_type: doc_type_id,
                country: regPrePops["country"],
                ajax_context: true,
            };
            licFuncs.xhrRegGetCall(param, function (data_json) {
                let option = $("#doc_issued_by").find("option")[0];
                $("#doc_issued_by").empty().append(option);
                $.each(data_json, function (index, value) {
                    $("#doc_issued_by").append(
                        $("<option />").val(index).text(value)
                    );
                });
            });
        }
    });

    $("#birth_country").change();
    $("#zipcode").blur(licFuncs.zipCodeValidation);
    $("#main_country").blur(licFuncs.zipCodeValidation);

    $(document).on(
        "triggerAddFunds",
        debounce(function () {
            var sessionToken = window.extSessHandler.activeSessions[0].token;
            if (sessionToken !== null) {
                licFuncs.handleAddFunds();
            }
        }, 150)
    );
});
