function GCDesktop() {
    var GC = new Geocomply();
    var GCHelper = new GeocomplyHelper();

    var UI = {
        GCLoader: function () {
            GCHelper.hideGeoComplyPopups();
            GCHelper.updateTitleText("#ipverification-loader");
            $("#ipverification-loader").show();

            GCHelper.resetModalInMiddle();
        },
        GCSuccess: function () {
            GCHelper.hideGeoComplyPopups();
            $('.lic-mbox-wrapper').addClass('gc--geo_comply_wrapper gc--geo_comply_grid');
            $('#lic-mbox-login-ipverification').addClass('gc--height-100 gc--flex gc--flex-column');

            GCHelper.updateTitleText("#ipverification-success");
            $("#ipverification-success").show();

            GCHelper.resetModalInMiddle();
        },
        GCError: function (errorText) {
            GCHelper.hideGeoComplyPopups();
            $('.lic-mbox-wrapper').addClass('gc--geo_comply_wrapper gc--geo_comply_grid');
            $('#lic-mbox-login-ipverification').addClass('gc--height-100 gc--flex gc--flex-column');

            GCHelper.updateTitleText("#ipverification-error");

            $("#ipverification-error .troubleshooter").click(function () {
                UI.GCTroubleshooter(errorText);
            });

            $("#ipverification-error").show();

            GCHelper.resetModalInMiddle();
        },
        GCFailed: function (data, content) {
            GCHelper.hideGeoComplyPopups();
            $('.lic-mbox-wrapper').addClass('gc--geo_comply_wrapper gc--geo_comply_grid');
            $('#lic-mbox-login-ipverification').addClass('gc--height-100 gc--flex gc--flex-column');
            GCHelper.updateTitleText("#ipverification-failed");

            $("#ipverification-failed").html(content.failure_popup);
            $("#ipverification-troublershooter").html(content.troubleshooter_popup);

            $("#ipverification-failed .troubleshooter").click(function () {
                UI.GCTroubleshooter(data);
            });

            $("#ipverification-failed").show();

            GCHelper.resetModalInMiddle();
        },
        GCTroubleshooter: function (data) {
            GCHelper.hideGeoComplyPopups();

            $('.lic-mbox-wrapper').addClass('gc--geo_comply_wrapper gc--geo_comply__scroll_wrapper');
            $('.lic-mbox-container').addClass('gc--flex gc--flex-1 gc--overflow-scroll gc--flex-column');
            $('#lic-mbox-login-ipverification').addClass('gc--flex gc--flex-1 gc--overflow-scroll gc--flex-column');

            $('#ipverification-troublershooter .geo_comply__content').html('');

            if (typeof data === 'string') {
                $('#ipverification-troublershooter .geo_comply__content').html(data);
            } else {
                var message = data['troubleshooter']['message'];

                if (typeof message === 'object') {
                    $(message).each(function (k, v) {
                        $('#ipverification-troublershooter .geo_comply__content').append(
                            `<div class="geo_comply__content__desc">
                        ${GCHelper.nl2br(v)}
                </div>`
                        )
                    });
                } else if (typeof message === 'string') {
                    $('#ipverification-troublershooter .geo_comply__content').html(message);
                }
            }

            GCHelper.updateTitleText("#ipverification-troublershooter");
            $("#ipverification-troublershooter").show();

            GCHelper.resetModalInMiddle();
        }
    };

    function initGeoComply() {
        $("#lic-login-errors").html('');
        UI.GCLoader();
        GC.setUI(UI);
        GC.load();
    }

    function locateClick() {
        if (GC.connected) {
            UI.GCLoader();
            GC.request();
        } else {
            UI.GCLoader();
            GC.load();
        }
    }

    return {
        initGeoComply: initGeoComply,
        locateClick: locateClick
    };
}

var GeocomplyModule = GCDesktop();
