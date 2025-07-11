<?php
require_once __DIR__ . '/phive.php';

if(!isCli()){

  if(phive()->moduleExists('IpGuard'))
    phive('IpGuard')->check( phive()->getSetting('domain') );
  
  // Check if admin_header_file exists in phive setup
  if ($file = phive()->getSetting('admin_header_file'))
    require_once __DIR__ . $file;

  global $phAdminPermission;
  if (!isset($phAdminPermission))
    $phAdminPermission = "admin";

  if (phive()->moduleExists('Permission') && !p($phAdminPermission)){
    header('Location: /');
    exit("Restricted");
  }

  if (!headers_sent()){
    header('Content-Type: text/html; charset=utf-8');
  }

}
