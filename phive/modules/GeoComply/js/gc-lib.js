class Geocomply {
    gcHelper = new GeocomplyHelper();
    settings = {};
    geoClient = {};
    gcUI = {};
    connected = false;
    geolocationResult = '';

    isActive() {
        return this.geoClient.isActive();
    }

    setData(settings) {
        this.geoClient.setLicense(settings.license);
        this.geoClient.setUserId(settings.userId);
        this.geoClient.setReason(settings.reason);
    }

    setUI(UI) {
        this.gcUI = UI;
    }

    verifyUI() {
        var requiredMethods = [
            "GCLoader",
            "GCSuccess",
            "GCError",
            "GCFailed",
            "GCTroubleshooter"
        ];

        for (var i = 0; i < requiredMethods.length; i++) {
            var method = requiredMethods[i];
            if (!(method in this.gcUI && typeof this.gcUI[method] === "function")) {
                this.gcHelper.log("Method " + method + " is missing or not a function in GeoComplyFunctions");
                return false;
            }
        }

        return true;
    }

    load() {
        const self = this;

        $.post('/phive/modules/GeoComply/endpoint.php', {'action': 'init'}, function (data) {

            self.gcHelper.setDebug(true);
            self.gcHelper.log(data);

            if (typeof data === "undefined") {
                self.gcHelper.logger('INITIALISATION_ERROR');
                return;
            }

            if (!data.license) {
                self.gcHelper.logger('MISSING_LICENSE');
                return;
            }

            if (!data.userId) {
                self.gcHelper.logger('MISSING_USERID');
                return;
            }

            if (self.verifyUI()) {
                self.gcHelper.init(data);
                self.gcHelper.log('UI is loaded');

                self.init(data);
            } else {
                self.gcHelper.log('Failure on GC UI');
            }

        }, 'json');
    }

    init(settings, onConnect, onError, onGeoLocation) {
        const self = this;
        this.settings = settings;
        this.gcHelper.init(settings);
        this.gcHelper.log('GeoComply Init');

        this.geoClient = GcHtml5.createClient(null, function (ts, message, level) {
            self.gcHelper.log('[' + ts + '] : ' + JSON.stringify(message));
        });

        this.geoClient.events
            .on('before', function () {
                self.gcHelper.log('[before]');
            })
            .on('abort', function () {
                self.gcHelper.log('[abort]');
            })
            .on('init.success', onConnect ?? self.onConnect.bind(self))
            .on('config.request', function (url, xml) {
                self.gcHelper.log('[config.request] with url = ' + url + '');
            })
            .on('config.success', function (xml) {
                self.gcHelper.log('[config.success]');
            })
            .on('engine.request', function (url, xml) {
                self.gcHelper.log('[engine.request] with url = ' + url);
            })
            .on('engine.success', function (text, xml) {
                self.gcHelper.log('[engine.success]');
            })
            .on('success', onGeoLocation ?? self.onGeoLocation.bind(self))
            .on('*.failed', function (code, message) {
                self.gcHelper.log(
                    '[' +
                    this.event +
                    '] : ' +
                    JSON.stringify({
                        code: code,
                        message: message
                    })
                );
            })
            .on('failed', onError ?? self.onError.bind(self))
            .on('hint', function (reason, hint) {
                self.gcHelper.log('[hint] ' + JSON.stringify(hint));
            })
            .on('browser.success', function (loc) {
                self.gcHelper.log('[browser.success]');
                self.geolocationResult = 'success';

            })
            .on('browser.failed', function (err) {
                self.gcHelper.log('[browser.failed]');
                self.geolocationResult = err;

            })
            .on('**', function () {
                self.gcHelper.log(this.event, arguments);

                var _event = this.event;
                if (_event === 'before') {
                }
                if (_event === 'success') {
                    self.gcHelper.log('browser geolocation result: <b class="browser_geolocation_result">' + self.geolocationResult + '</b>');
                }
            });


        window.addEventListener('beforeunload', function (e) {
            self.disconnect();
        });
    }

    request(reason) {
        this.setData(this.settings);

        if(reason){
            this.geoClient.setReason(reason);
        }

        this.geoClient.allowHint(true);
        this.geoClient.request();
    }

    disconnect() {
        this.geoClient.abort();
    }

    onConnect() {
        this.gcHelper.log('GeoComply Client connected');
        this.gcHelper.logger('CLIENT_CONNECTION');
        this.connected = true;

        let self = this;

        $.post('/phive/modules/GeoComply/endpoint.php', {'action': 'poll'}, function (data) {
            self.gcHelper.log(data);
            if (!data['valid']) {
                self.gcHelper.log('Requesting new GeoComply Packet...');
                self.gcHelper.logger('LOCATION_REQUEST');
                self.request();
            }
        }, 'json');
    }

    onGeoLocation(packet) {
        let self = this;

        $.post('/phive/modules/GeoComply/endpoint.php', {'action': 'decrypt', 'packet': packet}, function (data) {
            self.gcHelper.log(data);
            if (data['status']) {
                self.gcHelper.logger('LOCATION_APPROVED');
                self.gcUI.GCSuccess();
            } else {
                self.gcHelper.logger('LOCATION_FORBIDDEN');
                self.onGeolocationFailure(data);
            }
        }, 'json');
    }

    onError(errorCode, errorMessage) {
        this.gcHelper.log('GeoLocation failed. Details: ErrCode=[' + errorCode + ']; ErrMessage=[' + errorMessage + ']');
        this.gcHelper.logger('CLIENT_FAILURE_DEFAULT');
        this.gcUI.GCError(errorMessage);
    }

    onGeolocationFailure(data){
        let self = this;

        $.post('/phive/modules/GeoComply/endpoint.php', {'action': 'geolocationfailed'}, function (content) {
            self.gcUI.GCFailed(data, content);
        }, 'json');
    }
}
