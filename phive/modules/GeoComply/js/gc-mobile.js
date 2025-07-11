function GCMobile() {
    var GC = new Geocomply();
    var GCHelper = new GeocomplyHelper();

    var UI = {
        GCLoader: function(){
            GCHelper.hideGeoComplyPopups();
            GCHelper.updateTitleText("#ipverification-loader");
            $("#ipverification-loader").show();
        },
        GCSuccess: function() {
            GCHelper.hideGeoComplyPopups();
            GCHelper.updateTitleText("#ipverification-success");
            $("#ipverification-success").show();
        },
        GCError: function(errorText) {
            GCHelper.hideGeoComplyPopups();
            GCHelper.updateTitleText("#ipverification-error");

            $("#ipverification-error .troubleshooter").click(function (){
                UI.GCTroubleshooter(errorText);
            });

            $("#ipverification-error").show();
        },
        GCFailed: function(data, content){
            GCHelper.hideGeoComplyPopups();
            GCHelper.updateTitleText("#ipverification-error");

            $("#ipverification-failed").html(content.failure_popup);
            $("#ipverification-troublershooter").html(content.troubleshooter_popup);

            $("#ipverification-failed .troubleshooter").click(function (){
                UI.GCTroubleshooter(data);
            });

            GCHelper.updateTitleText("#ipverification-troublershooter");
            $("#ipverification-failed").show();
        },
        GCTroubleshooter: function(data) {
            GCHelper.hideGeoComplyPopups();

            $('#ipverification-troublershooter .geo_comply__content').html('');

            if(typeof data === 'string'){
                $('#ipverification-troublershooter .geo_comply__content').html(data);
            } else {
                var message = data['troubleshooter']['message'];

                if(typeof message === 'object'){
                    $(message).each(function (k, v){
                        $('#ipverification-troublershooter .geo_comply__content').append(
                            `<div class="geo_comply__content__desc">
                                    ${GCHelper.nl2br(v)}
                            </div>`
                        )
                    });
                } else if(typeof message === 'string') {
                    $('#ipverification-troublershooter .geo_comply__content').html(message);
                }
            }


            GCHelper.updateTitleText("#ipverification-troublershooter");
            $("#ipverification-troublershooter").show();
        }
    };

    function initGeoComply() {
        $("#lic-login-errors").html('');
        UI.GCLoader();
        GC.setUI(UI);
        GC.load();
    }

    function locateClick(){
        if (GC.connected){
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

var GeocomplyModule = GCMobile();
