class GeocomplyHelper {
    settings = [];
    debug = false;

    init(settings) {
        this.settings = settings;
        this.debug = settings.debug;
    }

    setDebug(debugMode){
        this.debug = debugMode;
    }

    updateTitleText(selector) {
        const title = $(selector).attr('popup-title');
        $('.lic-mbox-title').text(title);
    }

    nl2br (str, is_xhtml) {
        var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';
        return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
    }

    hideGeoComplyPopups(){
        this.resetClasses();
        $("#lic-mbox-login-ipverification > div").hide();
    }

    resetModalInMiddle() {
        $.multibox('posMiddle', 'login-box');
        $.multibox('posMiddle', 'mbox-loader');
    }


    resetClasses() {
        $('.lic-mbox-wrapper').removeClass (function (index, className) {
            return (className.match (/(^|\s)gc--\S+/g) || []).join(' ');
        });

        $('.lic-mbox-container').removeClass (function (index, className) {
            return (className.match (/(^|\s)gc--\S+/g) || []).join(' ');
        });

        $('#lic-mbox-login-ipverification').removeClass (function (index, className) {
            return (className.match (/(^|\s)gc--\S+/g) || []).join(' ');
        });
    }

    log(data){
        if(this.debug){
            console.log(data);
        }
    }

    logger(tag){
        const self = this;

        $.post('/phive/modules/GeoComply/endpoint.php', {'action': 'log', 'tag': tag}, function (response) {
            if(self.debug){
                console.log(response);
            }
        }, 'json');
    }
}
