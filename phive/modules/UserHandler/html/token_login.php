<?php
require_once __DIR__ . '/../../../phive.php';
phive()->sessionStart();
$uid = phMget($_GET['token']);
$login_res = phive('UserHandler')->loginWithToken($_GET['token']);
$args      = [];
if($login_res == 'go_to_step_two'){
    $url = '/?show_reg_step_2=true';
    $_SESSION['reg_uid'] = $uid;
} else if(is_string($login_res)) {
    $args = ['show_msg' => "blocked.$login_res.html"];
    $url = '/';
}
phive('Redirect')->to($url, $_GET['lang'], false, '301 Moved Permanently', [], $args);

