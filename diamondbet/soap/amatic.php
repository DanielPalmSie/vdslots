<?php
$smicro = microtime(true);
require_once __DIR__ . '/../../phive/api.php';
/*
if(phive("Config")->getValue('network-status', 'amatic') == 'off'){
  die("turned off");
}
*/
/**
 * Instance of Amatic class
 * @var $oGp Amatic
 */
$oGp = phive('Amatic');
$oGp
->injectDependency(phive('Currencer'))
->injectDependency(phive('Bonuses'))
->injectDependency(phive('MicroGames'))
->injectDependency(phive('SQL'))
->injectDependency(phive('UserHandler'))
->injectDependency(phive('Localizer'))
->setStart($smicro);


$oGp->preProcess();
$oGp->exec();
