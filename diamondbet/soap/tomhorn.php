<?php
require_once __DIR__ . '/../../phive/api.php';
/*
if(phive("Config")->getValue('network-status', 'tomhorn') == 'off'){
  die("turned off");
}
*/
/**
 * Instance of TomHorn
 * @var $tom_horn
 */
$tom_horn = phive('Tomhorn');
$tom_horn->execute();