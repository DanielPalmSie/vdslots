function cancelFullScreen(el) {
  var requestMethod = el.cancelFullScreen||el.webkitCancelFullScreen||el.mozCancelFullScreen||el.exitFullscreen;
  if (requestMethod) {
    requestMethod.call(el);
  } else if (typeof window.ActiveXObject !== "undefined") { 
    var wscript = new ActiveXObject("WScript.Shell");
    if (wscript !== null) {
      wscript.SendKeys("{F11}");
    }
  }
}

function requestFullScreen(el) {
  var requestMethod = el.requestFullScreen || el.webkitRequestFullScreen || el.mozRequestFullScreen || el.msRequestFullScreen;

  if (requestMethod) { 
    requestMethod.call(el);
  } else if (typeof window.ActiveXObject !== "undefined") { 
    var wscript = new ActiveXObject("WScript.Shell");
    if (wscript !== null) {
      wscript.SendKeys("{F11}");
    }
  }
  return false
}

function getFullScreenStatus(){
  return (document.fullScreenElement && document.fullScreenElement !== null) ||  (document.mozFullScreen || document.webkitIsFullScreen);
}

function toggleFull(func){
  var elem = document.body; 
  var isInFullScreen = getFullScreenStatus();

  if (isInFullScreen) {
    cancelFullScreen(document);
    func.call(this, 'min');
  } else {
    requestFullScreen(elem);
    func.call(this, 'max');
  }
  return false;
}

function canFull(){
  var el = document.body;
  var requestMethod = el.requestFullScreen || el.webkitRequestFullScreen || el.mozRequestFullScreen || el.msRequestFullScreen;
  return requestMethod ? true : false;
}
