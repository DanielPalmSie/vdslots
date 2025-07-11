/*Requires prototype library*/
var EDITBOXESPATH = "/phive/modules/BoxHandler/html/editboxes.php";

function boxAction(box_id, action, success_callback) {
    $.ajax({
        url: EDITBOXESPATH
        , data: {action: 'ajax', method: action, box_id: box_id}
        ,
        type: 'POST'
    })
    .done(function (data) {
        if (data.responseText == "error") {
            alert('PHP Error: Could not execute box action');
            return false;
        }
        if (success_callback)
            success_callback();
    })
    .fail(function () {
        alert('Ajax Error: Could not execute box action');
    });
}
function transferBox(box_id, newpage, success_callback) {
    $.ajax({
        url: EDITBOXESPATH
        , data: {action: 'ajax', method: 'transfer', box_id: box_id, newpage: newpage}
        ,
        type: 'POST'
    })
    .done(function (data) {
        if (data.responseText == "error") {
            alert('PHP Error: Could not transfer box');
            return false;
        }
        if (success_callback)
            success_callback();
    })
    .fail(function () {
        alert('Ajax Error: Could not transfer box');
    });
}
function addBox(type, container, page_id, success_callback) {
    $.ajax({
        url: EDITBOXESPATH
        , data: {action: 'ajax', method: 'addbox', container: container, type: type, page_id: page_id}
        ,
        type: 'POST'
    })
    .done(function (data) {
        if (data.responseText == "error") {
            alert('PHP Error: Could not update box attribute');
            return false;
        }
        if (success_callback)
            success_callback();
    })
    .fail(function () {
        alert('Ajax Error: Could not add box');
    });
}
function updateAttribute(box_id, name, value, success_callback) {
    $.ajax({
        url: EDITBOXESPATH
        , data: {action: 'ajax', method: 'updateAttribute', box_id: box_id, name: name, value: value}
        ,
        type: 'POST'
    })
    .done(function (data) {
        if (data.responseText == "error") {
            alert('PHP Error: Could not update box attribute');
            return false;
        }
        if (success_callback)
            success_callback();
    })
    .fail(function () {
        alert('Ajax Error: Could not update box attribute...');
    });
}
function deleteAttribute(box_id, name, success_callback) {
    $.ajax({
        url: EDITBOXESPATH
        , data: {action: 'ajax', method: 'deleteAttribute', box_id: box_id, name: name}
        ,
        type: 'POST'
    })
    .done(function (data) {
        if (data.responseText == "error") {
            alert('PHP Error: Could not delete box attribute');
            return false;
        }
        if (success_callback)
            success_callback();
    })
    .fail(function () {
        alert('Ajax Error: Could not delete box attribute...');
    });
}

function ajaxGetBoxHtml(params, lang, boxid, func) {
    params['lang'] = lang;
    params['boxid'] = boxid;
    params['action'] = 'GetBoxHtml';
    params['juri'] = JURISDICTION;
    params['logged'] = (IS_LOGGED) ? 'true' : 'false';
    if (GAME_TAGS) {
        params['tags'] = GAME_TAGS;
    }
    if (LOBBY_DIR) {
        params['lobby'] = LOBBY_DIR;
    }
    jQuery.get('/phive/modules/BoxHandler/html/ajaxActions.php', params, func);
}

function ajaxGetBoxJson(params, lang, boxid, func) {
    params['lang'] = lang;
    params['boxid'] = boxid;
    params['action'] = 'GetBoxHtml';
    jQuery.get('/phive/modules/BoxHandler/html/ajaxActions.php', params, func, 'json');
}








