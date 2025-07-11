<?php
require_once __DIR__ . '/../../phive/api.php';
/*
if(phive("Config")->getValue('network-status', 'swintt') == 'off'){
  die("turned off");
}
*/
$module = phive('Swintt');
$module->execute();