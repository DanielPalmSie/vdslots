<?php
require_once $_SERVER['DOCUMENT_ROOT'].'/phive/phive.php';
?>
<script type="text/javascript">
    var MessageProcessor = (function (){

        return {
            test: true,
            process: function (event) {
                const data = this.getEventData(event);

                switch (data._type) {
                    case "ucip.basic.g2wInitializationRequest":
                        var response = JSON.stringify({
                            features: [],
                            version: data.version,
                            _type: "ucip.basic.w2gInitializationResponse"
                        });

                        this.request(response);
                        break;
                    case "ucip.basic.g2wCloseGameFrameCommand":
                        window.location.href = '/';
                        break;
                    default:
                        // process other events if necessary
                        break;
                }
            },

            getEventData: function (event) {
                if (!event.data) return;

                if(!this.isJsonParsable(event.data)) {
                    return event.data;
                }

                return JSON.parse(event.data);

            },

        };
    } )();
</script>
