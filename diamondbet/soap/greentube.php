<?php
$smicro = microtime(true);
require_once __DIR__ . '/../../phive/api.php';

/*
if(phive("Config")->getValue('network-status', 'greentube') == 'off'){
  die("turned off");
}
*/

/**
 * Instance of Greentube
 * @var $oGp Greentube
 */
$oGp = phive('Greentube');
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
