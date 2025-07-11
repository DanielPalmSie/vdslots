<script type="text/javascript">
var MessageProcessor = (function (){
    return {
        test: false,
        animation: false,
        animationStartTime: 0,
        pendingNotifications: [],
        bosMapping:{
            "animations_start"    : "gameRoundStarted",
            "animations_end"      : "gameRoundEnded",
        },
        process: function (event) {
            const type = this.getEventType(event);

            switch (type) {
                case 'animations_start':
                    this.animation = true;
                    this.animationStartTime = new Date();
                    break;
                case 'animations_end':
                    this.animation = false;
                    this.showPendingNotifications();
                    break;
                default:
                    break;
            }
        },
        bosReloadBalance : function(){
            reloadIframe($('#mbox-iframe-play-box'))
        },
        getEventType: function (event) {
            if (!event.data) return;
            return event.data;
        },
        pauseNotifications() {
            if (this.animation){
                var timeNow = new Date();
                var timeDiff = timeNow - this.animationStartTime;
                timeDiff /= 1000; // strip the ms
                var elapsedSeconds = Math.round(timeDiff);
                if (elapsedSeconds > 10){
                    return true;
                }
            }
            return false;
        },
        handleNotifications: function (content, callback) {
            if (this.pauseNotifications()) {
                this.pendingNotifications.push({
                    "content": content,
                    "callback": callback
                });
            } else {
                callback.call(this, content);
            }
        },
        showPendingNotifications: function () {
            while (this.pendingNotifications.length) {
                let notification = this.pendingNotifications.shift();
                notification.callback.call(this, notification.content);
            }
        }
    };
} )();
</script>
