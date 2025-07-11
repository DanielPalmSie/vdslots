/**
 * NYX custom postmessage library, recommended way to use postMessages with them.
 * */

function NyxGcmProxy() {
    this.gcm = null;
}

NyxGcmProxy.prototype.setup = function() {
    var self = this;
    mgJson({ action: 'nyx_gcm_url', csrf_token: document.currentScript.getAttribute('token')})
        .then(function(res) {
            return self.loadScript(res);
        })
        .then(function() {
            var gcmConfig = {
                'cui': self,
                'errorHandler': function(err) {
                    saveFELogs('game_providers', 'debug', 'NyxGcmProxy::errorHandlerConfig', {
                        message: err,
                    });
                }.bind(self)
            };
            self.gcm = new sgdigital.casino.GcmProxy();
            self.gcm.init(gcmConfig);
        })
        .catch(function(e) {
            saveFELogs('game_providers', 'error', 'NyxGcmProxy::setup', {
                message: e,
            });
        });
};


NyxGcmProxy.prototype.onGcmInit = function() {
    let eventCallbacks = [
        {"id": this.gcm.callbacks.ANIMATION_COMPLETE}
    ];
    this.gcm.registerCallbacks(eventCallbacks, false);
};

NyxGcmProxy.prototype.updateBalance = function(amount) {
    if (typeof amount === 'undefined') {
        amount = 0.00;
    }

    var balObj = { 'CASH': amount, 'BONUS': 0.00 };

    this.gcm.balancesUpdate(balObj);
};

NyxGcmProxy.prototype.gameAnimationComplete = function (params) {
    this.gcm.resume()
}

NyxGcmProxy.prototype.loadScript = function(scriptUrl) {
    return new Promise(function(resolve, reject) {
        var script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = scriptUrl;
        script.addEventListener('load', function() {
            resolve(script);
            document.head.removeChild(script);
        }, false);
        script.addEventListener('error', function() {
            reject(script);
        }, false);
        document.head.appendChild(script);
    });
};

var nyxGcm = new NyxGcmProxy();

nyxGcm.setup();
