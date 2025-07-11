GameCommunicator.setEventParser(function (event) {
    try {
        var eventData = JSON.parse(event.data);
        switch(eventData._type){
            case "ucip.basic.g2wInitializationRequest":
                var response = JSON.stringify({
                    features: [],
                    version: eventData.version,
                    _type: "ucip.basic.w2gInitializationResponse"
                });

                var iframe = document.getElementById('vs-game-container__iframe-1');
                if (iframe) {
                    iframe.contentWindow.postMessage(response, event.origin);
                }
                break;
            case "ucip.basic.g2wCloseGameFrameCommand":
                window.location.href = '/';
                break;
            default:
                break;
        }
    } catch (e) {
        return false;
    }
});
