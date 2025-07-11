

// This is for mobile but can easily be overloaded in case different logic is needed for eg tablets.
var deviceHelpers = {
    orientationIsPortrait: function() {
        // Rule to avoid error on the orientation identification on Android
        if (isAndroid()) {
            // Compare the device size
            return (screen.height > screen.width);
        }

        // Compare the window size
        return (window.innerHeight > window.innerWidth);
    },
    onPoupInit: function(){
        this.fixPopupViewport(false);
    },
    onPoupClose: function(){
        this.fixPopupViewport(true);
    },
    onPoupRemove: function(){
        this.fixPopupViewport(true);
    },
    fixPopupViewport: function(onClosing) {
        const viewportSize = isIpad() ? 500 : 400;

        if (deviceHelpers.orientationIsPortrait() || !isPopupDisplayed() || onClosing) {
            document.getElementById('viewport').content = isIphone()
                ? "width=device-width, initial-scale=1, maximum-scale=1"
                : "width=" + viewportSize;
        } else {
            document.getElementById('viewport').content = isIphone()
                ? "width=device-width, initial-scale=1, maximum-scale=1"
                : "width=device-width, initial-scale=1";
        }
    }
};

$(window).on('resize', function () {
    deviceHelpers.fixPopupViewport(false);
});
