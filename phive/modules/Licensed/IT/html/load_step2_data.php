let url = "/phive/modules/DBUserHandler/xhr/registration.php?load_step2_data=true&ajax_context=true&country=IT";
let form = {};
$.post(url, form, function (data) {
    let data_json = JSON.parse(data);

    // show dep lim popup
    if (data_json.action) {
        if (isMobile()) {
            window[data_json.action.method].apply(null, data_json.action.params);
        } else {
            window.top[data_json.action.method].apply(null, data_json.action.params);
        }
    }
});
