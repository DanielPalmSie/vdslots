<?php

/*
   if($_SERVER['SERVER_PORT'] == 80){
   header("HTTP/1.1 301 Moved Permanently"); 
   header("Location: https://www.videoslots.loc".$_SERVER['REQUEST_URI']); 
   header("Connection: close");
   exit;
   }
 */

$redir_links = array(
    'casino/games' => '/casino/',
    "account/signup"		=> "/?signup=true",
    "register"				=> "/poker/client/promotions/",
    "casino/games/hot-hot-volcano" => "/casino/games/volcano-eruption-nyx/",
    "play/hot-hot-volcano" => "/play/volcano-eruption-nyx/"
);

$new_dir = $redir_links[ $_GET['dir'] ];

//if(strpos($_GET['dir'], 'affiliate/') !== false){
//    $ext_url = "https://partner.videoslots.com/maintenance.html";
//}

if(!empty($new_dir) || !empty($ext_url)){
    header("HTTP/1.1 301 Moved Permanently");
    if(!empty($ext_url))
        header("Location: $ext_url");
    else
        header("Location: http://".$_SERVER['HTTP_HOST'].$new_dir); 
    header("Connection: close");
    exit;
}
