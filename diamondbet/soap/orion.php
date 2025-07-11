<?php
//set_time_limit(20); 
ini_set('max_execution_time', '30');
ini_set('memory_limit', '500M');
require_once __DIR__ . '/../../phive/phive.php';
if(isCli()){
  require_once __DIR__ . '/../../phive/modules/Micro/Orion.php';
  $orion = new Orion();
  //phive('SQL')->debug = true;
  $orion->workQ();
  if(phive('QuickFire')->getSetting('has_ggi') === true)
    $orion->completeQ();
  //phive('SQL')->printDebug(true, false, 'orion');
}
