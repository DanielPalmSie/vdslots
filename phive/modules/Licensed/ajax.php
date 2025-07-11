<?php
require_once __DIR__ . '/../../phive.php';
require_once __DIR__ . '/../../../diamondbet/html/display.php';
phive('Localizer')->setFromReq();

/** @var Licensed $default_obj */
$default_obj = phive('Licensed');
$default_obj->forceCountry($_POST['country']);

// websocket_event - is being sent by the websockets with the event which just occurred
if (!empty($_POST['websocket_event'])) {
    $post_data = json_decode(phMget($_POST['websocket_event']), true);
    if (empty($post_data) || !is_array($post_data)) {
        return;
    }
    if ($_POST['event'] === 'open') {
        $post_data['lic_func'] = $post_data['open_lic_func'];
    }
} else {
    $post_data = $_POST;
}
if($_POST['return_format'] === 'json'){
    $res = lic('ajax'.ucfirst($post_data['lic_func']), [$post_data], null, $default_obj, $post_data['country']);
    // JSON already so we just echo and die.
    if(is_string($res))
        die($res);
    echo json_encode($res);
}else{
    // Here we assume that lic() will echo HTML.
    lic('ajax'.ucfirst($post_data['lic_func']), [$post_data], null, $default_obj, $post_data['country']);
}
