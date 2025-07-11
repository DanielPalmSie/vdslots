<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/crud/crud.php';

if($_REQUEST['action'] == 'delete'){
    $str = "DELETE FROM game_tag_con WHERE tag_id = ".(int)$_REQUEST['id'];
    phive('SQL')->query($str);
}


Crud::table('game_tags', true)->renderInterface('id');
