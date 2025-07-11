var cashierOverrideReturnUrlType = 'ingame-iframe-box';

// Magic script to handle scaling the iframe content on the 400px (500px ipad) meta viewport hardcoded condition we have on the current mobile layout
var isIpad = navigator.userAgent.match(/iPad/i) != null;

/* START of viewport amendment logic*/
$(document).ready(function() {
    function resizeElements(scale, width) {
        $('html').css({
            width: width
        });

        // keep natural container size if the scale is bigger than 1
        if (scale > 1)
            scale = 1;

        // otherwise apply the scale via transform
        $('body').css({
            transform: "scale(" + scale + ")",
            "transform-origin": "0 0",
        });
    };

    function receiveIframeDimensions (e) {
        if (e.data == null || e.data == '')
            return;

        // Note: The commented solution below was replaced by the use of VisualViewport.width.
        // var contentWidth = $('body')[0].getBoundingClientRect().width;
        // TODO @paolo: for now we keep the hardcoded version as we only have 2 possible width
        //  the solution with getBoundingClientRect() doesn't seems to return the correct width in 100% of scenarios

        var clientContentWidth = window.visualViewport.width;

        // TODO
        // Remove 500 used for iPads and replace it with clientContentWidth
        // This will need intensive testing
        var contentWidth = isIpad ? 500 : clientContentWidth;
        var width = e.data.width;
        var scale = (width/contentWidth);
        scale = fixScaleForPopup(scale, width);

        resizeElements(scale, width);
    }

    /**
     * Popups inside the iframe must have a scale of 1 or they will display incorrectly.
     * Triggering a callback on "close" to resize the content properly, as when no popup is present.
     *
     * @param scale
     * @param width
     * @return {number}
     */
    function fixScaleForPopup(scale, width) {
        var popup = $('#mbox-msg');
        if(!popup.length) {
            return scale;
        }

        var prevScale = scale;
        setTimeout(function() {
            popup.find('.undo-withdrawals__table').css('width', width);
        }, 150)
        popup.on('click', '.lic-mbox-close-box, .multibox-close, .btn', function() {
            resizeElements(prevScale, width);
        });

        return 1;
    }

    // get all scaling related data from the iframe's container with every resize event on the container
    window.addEventListener("message", receiveIframeDimensions, false);

    // set extra class to body for iframe in popup styling
    $('body').addClass('iframe-in-popup');
});
/* END of viewport amendment logic */


/**
 * This shall apply for "Deposits" page only.
 *
 * We need to prevent the normal link behaviour after a deposit is complete
 * otherwise the page wil be loaded inside the iframe, so we preventDefault()
 * and send the event to the parent frame. (using event delegation)
 */
$(document).ready(function() {
    var depositMainDiv = '#cashierDepositWithdraw';
    // elements containing the links to Account & Bonuses after successful deposit.
    var selector = '.cashierBoxInsert .btn-small, #deposit_complete .btn-small';

    $(depositMainDiv).on('click', selector, function(e) {
        e.preventDefault();
        window.top.location.href = e.target.href;
    });
});