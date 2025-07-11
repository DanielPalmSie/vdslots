function setupLoginbox() {
    $('#password, #remember_me-span').blur(function () {
        if (empty($(this).val())) {
            addClassError(this);
            return;
        }
        addClassValid(this);
    });
}

/**
 * This function is only executed the first time the iframe get's opened.
 *
 * large_box use cases:
 *  1. value == false : we only have to show the nemid form
 *  2. value == true : we show the nemid form AND 30 days login section
 *
 */
function showDKLoginBox(qUrl, large_box) {

    var iframename = 'login-dk';
    var width = '320px';
    var height = '450px';

    if (large_box) {
        var width = '700px';
    }

    $.multibox({
        url: qUrl,
        id: 'login-dk',
        name: iframename,
        type: 'iframe',
        width: width,
        height: height,
        cls: 'mbox-deposit',
        globalStyle: {overflow: 'hidden'},
        overlayOpacity: 0.7
    });

}
