<?php
require_once __DIR__ . '/../../../admin.php';
require_once __DIR__ . '/../../../api/crud/crud.php';

if(!empty($_GET['username'])){
  $_SESSION['mosms_admin_username'] = $_GET['username'];
}

Crud::table('mosms_check', true)->insertUpdateDelete();

$user = cu($_SESSION['mosms_admin_username']);

if(!empty($user)){
  $uid = $user->getId();
  $str = "SELECT * FROM mosms_check WHERE user_id = $uid";
  $res = phive("SQL")->loadArray($str);
  if(!empty($_REQUEST['id']) && $_REQUEST['action'] == 'updateform')
    $ini = phive()->flatten($res, function($el){ return (int)$el['id'] === (int)$_REQUEST['id']; });
}

?>
<div style="padding: 10px;">
  <strong>Search for SMS codes:</strong>
  <br/>
  <br/>
  <form action="" method="get">
    Username: <?php dbInput('username') ?>
    <?php dbSubmit('Submit') ?>
  </form>
  <br/>
  <br/>
  <?php 
  if(!empty($res))
    Crud::table('mosms_check', true)->renderInterface('id', array(), true, null, $res, false, $ini);
  else if(!empty($_SESSION['mosms_admin_username']))
    echo "No records found for that username.";
  ?>
</div>
