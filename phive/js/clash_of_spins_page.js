function updateTimeLeft(id, timerStop) {
    var diff = new Date(timerStop) - new Date();
    if (diff <= 0) {
        document.getElementById(id).innerHTML = "00:00";
    } else {

        diff = Math.floor(diff/1000);
        var s = diff % 60;
        diff = (diff - s) / 60;

        var m = diff % 60;
        var h = (diff - m) / 60;

        s = padZero(s);
        m = padZero(m);
        h = padZero(h);

        document.getElementById(id).innerHTML = (h == '00' ? '' : h + ":") + m + ":" + s;
        var t = setTimeout(function() {
            updateTimeLeft(id, timerStop);
        }, 1000);
    }
}

function padZero(i) {
    if (i < 10) {i = "0" + i};  // add zero in front of numbers < 10
        return i;
}
