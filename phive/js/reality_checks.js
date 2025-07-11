/**
 * Fallback parameters for RC check (all values are in minutes).
 * This values gets overridden in 2 scenario:
 * - when we do init-reality-check, in case of the first time setup popup. (DB config values)
 * - on pages where RC is needed (Ex. game mode) with the user current setup (RG limit)
 *
 * @type {{
 *  rc_min_interval: number,
 *  rc_max_interval: number,
 *  rc_steps: number,
 *  rc_default_interval: number,
 *  rc_current_interval: number,
 *  rc_to_show_popup: boolean,
 *  onRealityCheckSetComplete: Function,
 *  }}
 */
var rc_params = {
    rc_min_interval: 15,
    rc_max_interval: 480,
    rc_steps: 15,
    rc_default_interval: 240,
    rc_current_interval: 240,
    rc_to_show_popup: false,
    onRealityCheckSetComplete: function() {}
};
var timerInterval = 0
var reality_checks_js = {
    curId: '',
    duration: 0,
    gref: '',
    interval: 1 * 1000,
    network: '',
    lang: 'en',
    show_leave_game_button: true,
    skipTimer: true,
    elapsedTime: 0,
    remainingPlayPause: 0,
    logActionWhenTrigger: false, // we skip triggering log actions on the first call
    rc_createDialog: function(dialog) {
        var self = this;

        if (!self.gref){
            return;
        }

        var postOptions = {
            action: 'init-reality-check',
            'duration': self.duration,
            'network': self.network,
            'show_leave_game_button': self.show_leave_game_button,
            'lang': self.lang,
            'log_action_when_trigger': self.logActionWhenTrigger,
            'ext_game_name': self.gref,
        };
        self.logActionWhenTrigger = true;

        mgAjax(postOptions, function (ret) {
            var json = JSON.parse(ret);
            if (json === 'no user') {
                location.reload();
                return;
            }
            rc_params.steps = json.rc_steps;
            rc_params.rc_min_interval = json.rc_min_interval;
            rc_params.rc_max_interval = json.rc_max_interval;
            rc_params.rc_default_interval = json.rc_default_interval;
            rc_params.rc_to_show_popup = json.to_show_popup;
            self.remainingPlayPause = json.remaining_play_pause;
            var showDialog = json.rc_show_dialog;
            self.elapsedTime = json.rc_elapsedTime;
            var curId = S4();
            self.curId = curId;

            var doOnComplete = function () {
                reality_checks_js.createJqueryBtnHandler();
            };
            var options = {
                id: "rc-msg",
                cls: "dialogWindowContainer",
                type: 'ajax',
                params: {
                    lang: postOptions.lang,
                    show_leave_game_button: postOptions.show_leave_game_button,
                    in_game: self.in_game,
                    ext_game_name: self.gref,
                },
                width: siteType == 'normal' ? '600px' : '100vw',
                height: siteType == 'normal' ? '333px' : 'auto',
                url: document.location.origin + '/diamondbet/html/reality_check.php',
                showClose: false,
                onComplete: doOnComplete
            };

            if (licFuncs.realityCheckConfig) {
                options.width = licFuncs.realityCheckConfig.width ? licFuncs.realityCheckConfig.width : options.width
                options.height = licFuncs.realityCheckConfig.height ? licFuncs.realityCheckConfig.height : options.height
            }

            function getTimeRemaining(only_unit) {
                only_unit = typeof only_unit === 'boolean' ? only_unit : false;
                var time = rc_params.rc_current_interval * 60;
                if (only_unit) {
                    return time;
                }
                if (self.elapsedTime > 0) {
                    time = time - (self.elapsedTime % time);
                }
                return time;
            }

            if (self.skipTimer === '1') {
                // ingame - so we only show the setup popup
                if (showDialog === 'true') {
                    options.params.isSetting = 'true';
                    rc_params.onRealityCheckSetComplete = function () {
                        location.reload()
                    }
                }
            } else if (typeof dialog === 'undefined') {
                options.params.isSetting = 'true';

                if (showDialog === "false" || empty(self.doAfter)) {
                    // This is where the FE interval gets initiated.
                    self.updateTimer(getTimeRemaining() + self.remainingPlayPause);
                    self.mobileContinue();
                }
            } else {
                showDialog = "true"
            }

            if (self.remainingPlayPause > 0) {
                self.logActionWhenTrigger = false;
                self.duration = 0;
                self.updateTimer(getTimeRemaining(true) + self.remainingPlayPause);
                if (self.in_game && showDialog !== 'true') {
                    licFuncs.printGamePlayPaused();
                }
            }

            if (showDialog === 'true') {
                if (rc_params.rc_to_show_popup === true) {
                    if (typeof MessageProcessor !== 'undefined' && typeof MessageProcessor.pauseGame === 'function') {
                        GameCommunicator.pauseGame().then(() => {
                            console.log('game paused');
                        });
                    }
                    $.multibox(options);
                }
                // We've to check if we have MessageProcessor defined, as this could be called from a different context

                if (rc_params.rc_to_show_popup !== true && postOptions.log_action_when_trigger) {
                    location.reload(); // reload the page, for tabs that are out of sync
                }

            }
        });
    },
    createJqueryBtnHandler: function(){
        var self = this;
        $("#rc-msg").on('click', '.reality-check-btn', function(){
            var sum = 0;
            var val = parseInt($("#reality-check-interval").val());
            if(typeof val === 'undefined' || isNaN(val)){
                val = rc_params.rc_default_interval;
            }
            if($(this).hasClass("right") && val < rc_params.rc_max_interval ){ sum = 1 ;};
            if($(this).hasClass("left") && val > rc_params.rc_min_interval){ sum = -1; };
            val = self.normalizeUserInterval((val + rc_params.rc_steps * sum));
            $("#reality-check-interval").val(val);
        });
    },
    validateAndSet: function(){
        rc_params.rc_current_interval = parseInt($("#reality-check-interval").val());
        this.setRealityCheck();
    },
    setRealityCheck: function(){
        var self = this;
        mgAjax({action: 'set-reality-check-duration',reality_check_interval:rc_params.rc_current_interval}, function (ret) {
            var json = JSON.parse(ret);
            if(json.status === 'error'){
                mboxMsg(json.message, true, function () {
                    self.logActionWhenTrigger = false;
                    reality_checks_js.rc_createDialog();
                });
                return false;
            }
            self.duration = '0';
            $.multibox('close', 'rc-msg'); 
            mboxClose('rc-msg');
            self.updateTimer();
            self.mobileContinue();
            if (rc_params.onRealityCheckSetComplete) {
                rc_params.onRealityCheckSetComplete()
            }
        }); 
    },
    updateTimer: function(timeRemaining){
        timeRemaining = typeof timeRemaining === 'undefined' ? rc_params.rc_current_interval * 60 : timeRemaining;
        var self = this;
        self.remainingPlayPause = parseInt(self.remainingPlayPause);
        self.duration = parseInt(self.duration);
        self.timeRemaining = timeRemaining;
        var showRcPopupFromTestParam = document.getElementById('rc-testing-button');
        function timer() {
            self.duration += 1;//check every 60 seconds
            if (self.duration > 0 && (self.duration) % self.timeRemaining === 0) {
                self.timeRemaining = rc_params.rc_current_interval * 60 + self.timeRemaining + self.remainingPlayPause; // restart the timer
                self.rc_createDialog('dialog');
            }
        }

        if (showRcPopupFromTestParam) {
            timer = function () {
                self.rc_createDialog('dialog');
            }
            self.interval = 5000;
        }

        clearInterval(timerInterval);
        timerInterval = setInterval(timer, self.interval);
    },
    mobileContinue: function(){
        var self = this;
        if (typeof playMobileGameShowLoader === 'function') {
            if(!empty(self.doAfter)){
                if(self.doAfter()){
                    playMobileGameShowLoader(self.gref); 
                }
            }else{
                playMobileGameShowLoader(self.gref); 
            }
        }
    },
    // User interval must respect the "steps"
    normalizeUserInterval(val) {
        return parseInt(Math.floor(val / rc_params.rc_steps) * rc_params.rc_steps);
    }
};

var reality_checks_js_mobile = {
    setPluginTime: function(){
        duration++;
        reality_checks_js_mobile.updateTimer();
    },
    updateTimer: function() {
        var dt = new Date();
        var durationHours = String(Math.floor(duration / 3600));
        var durationMinutes = String(Math.floor((duration % 3600) / 60));
        var durationSeconds = String((duration % 3600) % 60);

        if (durationHours.length === 1) {
            durationHours = "0" + durationHours;
        }
        if (durationMinutes.length === 1) {
            durationMinutes = "0" + durationMinutes;
        }
        if (durationSeconds.length === 1) {
            durationSeconds = "0" + durationSeconds;
        }
        var msgStr = "Current time: " + dt.toTimeString().split(" ")[0] + ", session duration: " + durationHours + ":" + durationMinutes + ":" + durationSeconds;
        var params = [{"type": "text", "text": msgStr}];
        try{ showExtraClock(params); }catch(e){ }
        
        if (typeof g.ext_game_name !== 'undefined' && duration > 0 && duration % (rc_params.rc_current_interval * 60) === 0) {
            // Show the RC dialog in game iframe
            mgAjax({action: 'init-reality-check', 'duration': duration, 'ext_game_name': g.ext_game_name}, function (ret) {
               var json      = JSON.parse(ret);
               messageToShow = json.htmlMsg.message;
               realityCheckMsg();
           });
        }
    },
    startRc: function() {
        timer = setInterval(this.setPluginTime, 1000); 
        pluginInit();          
    }
};
