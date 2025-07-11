var GeocomplyGlobalChecks = (function () {
    return {
        polling_interval: 1000,
        gdc: null,
        gdcHelper: null,
        wsURL: null,
        debug: true,
        status: 'active',
        preloader: '',
        reason: 'Check after login',

        forcerequest: function (reason) {
            this.showLoader(this.preloader);
            this.reason = reason;

            $.post('/phive/modules/GeoComply/endpoint.php', {'action': 'force-request'}, function (data) {
            }.bind(this), 'json');
        },

        poll: function () {
            var today = new Date();

            if (!this.gdc.isActive()) {
                this.gdcHelper.log("inactive tab " + today.getSeconds());
                setTimeout(this.poll.bind(this), this.polling_interval);
                return;
            }

            this.gdcHelper.log("polling " + today.getSeconds());

            $.ajax({
                type: "GET",
                url: '/phive/modules/GeoComply/endpoint.php',
                data: {
                    'action': 'poll',
                    'reason': 'SessionIntervalGeo'
                },
                headers: {
                    'Cache-Control': 'private, no-cache, no-store, must-revalidate'
                },
                success: function (data) {
                    if (this.status == 'active') {
                        this.gdcHelper.log(data);
                        if (!data['valid']) {
                            this.gdcHelper.log('Requesting new GeoComply Packet...');
                            this.gdc.request(this.reason);
                        } else {
                            this.hideLoader();
                            setTimeout(this.poll.bind(this), this.polling_interval);
                        }
                    }

                }.bind(this),
                error: function (XMLHttpRequest, textStatus, errorThrown) {
                    this.showLoader(this.preloader);
                    setTimeout(this.poll.bind(this), this.polling_interval);
                }.bind(this),
                dataType: 'json',
                cache: false
            });
        },

        initPreloader: function () {
            if (typeof localStorage['GCPreloader'] === 'undefined') {
                this.gdcHelper.log('init preloader');
                $.get('/phive/modules/GeoComply/html/checking_locate_popup.php', function (result) {
                    this.preloader = result;
                    localStorage['GCPreloader'] = result;
                }.bind(this), 'html');
            } else {
                this.preloader = localStorage['GCPreloader'];
            }
        },

        init: function () {
            this.gdc = new Geocomply();
            this.gdcHelper = new GeocomplyHelper();

            window.addEventListener('beforeunload', function (e) {
                this.gdc.disconnect();
            }.bind(this));

            $.post('/phive/modules/GeoComply/endpoint.php', {
                'action': 'init',
                'reason': 'Verification after login'
            }, function (data) {
                if (data != null) {
                    this.debug = data.debug || false;
                    this.polling_interval = data.polling_interval || this.polling_interval;
                    this.initPreloader.bind(this).call();
                    this.gdc.init(data, this.onConnect.bind(this), this.onError.bind(this), this.onGeoLocation.bind(this));
                    this.gdcHelper.init(data);
                    this.gdcHelper.log(data);
                } else {
                    this.gdcHelper.setDebug(true);
                    this.gdcHelper.log("GeoComply connection error")
                }
            }.bind(this), 'json');
        },


        onConnect: function () {
            this.gdcHelper.log("GeoComply request after login");
            this.poll();
        },


        onGeoLocation: function (packet) {
            this.gdcHelper.log("on geolocation")

            $.post('/phive/modules/GeoComply/endpoint.php', {'action': 'decrypt', 'packet': packet}, function (data) {
                this.closeAllPopups();
                this.gdcHelper.log(data);
                if (data['status']) {
                    this.onGeoLocationSuccess()
                } else {
                    this.onGeoLocationError(data)
                }
            }.bind(this), 'json');
        },


        onGeoLocationSuccess: function () {
            this.gdcHelper.log("GeoComply: Successful verification");
            setTimeout(this.poll.bind(this), this.polling_interval);
        },


        onGeoLocationError: function (data) {
            this.showTroubleshooter(data);
        },


        onError: function (errorCode, errorMessage) {
            this.gdcHelper.log('GeoLocation fatal error. Details: ErrCode=[' + errorCode + ']; ErrMessage=[' + errorMessage + ']');

            var data = {};
            data.troubleshooter = errorMessage;
            this.showTroubleshooter(data);
        },


        closeAllPopups() {
            $.multibox('remove', 'checking_locate_popup');
            $.multibox('remove', 'global-ipverification-troublershooter');
        },

        showLoader(preloader) {
            if (preloader) {
                var options = {};

                var html = '<div class="mbox-msg-container" style="padding: 20px;">' + preloader + '</div>';

                options.height = isMobile()? '100%' : '180px';
                options.width = isMobile()? '100%' : '180px';
                options.id = 'geocomply_mini_loader';
                options.type = 'html';
                options.content = html;
                options.showClose = false;
                options.baseZIndex = 10000;

                setTimeout(function() {
                    //display preloader with a delay to be not hidden by previous poll request
                    $.multibox(options);
                }, 1001);



            } else {
                this.gdcHelper.log('Error: no preloader');
            }

        },

        hideLoader() {
            $.multibox('close', 'geocomply_mini_loader');
        },

        showCheckingGeolocation(callback) {
            this.gdcHelper.log('showCheckingGeolocation');
            // close popup and show geolocation loader
            // on next geolocation close popup
            var popup = 'checking_locate_popup';
            var params = {
                module: 'GeoComply',
                file: 'checking_locate_popup',
                boxtitle: 'verification.in.progress',
                closebtn: 'no',
                callb: callback
            }

            extBoxAjax('get_html_popup', popup, params, this.getCommonPopupExtraOptions());
        },

        showTroubleshooter(data) {
            this.closeAllPopups();

            this.gdcHelper.log('showTroubleshooter');
            var popup = 'global-ipverification-troublershooter';

            if (!data.troubleshooter) {
                this.gdcHelper.log("No troubleshooter data found");
                return false;
            }

            var params = {
                module: 'GeoComply',
                file: 'troubleshooter_popup',
                boxtitle: 'error.details',
                closebtn: 'no',
                context: 'global-check'
            };

            var popup = 'global-ipverification-troublershooter';

            params.callb = function () {
                GeocomplyGlobalChecks.hideLoader();
            };

            Object.assign(params, data);

            extBoxAjax('get_html_popup', popup, params, this.getCommonPopupExtraOptions());

        },

        retryButtonAction: function () {
            this.closeAllPopups();
            this.gdcHelper.log("retryButtonAction");

            this.showCheckingGeolocation(function () {
                this.gdcHelper.log('New Request');
                this.gdc.request('Retry button click');
            }.bind(this));
        },

        getCommonPopupExtraOptions: function () {
            return isMobile() ? {} : {width: 450};
        }
    }
})();


$(document).ready(function () {
    GeocomplyGlobalChecks.init();
});



