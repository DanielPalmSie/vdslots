<?php
$smicro = microtime(true);
require_once __DIR__ . '/../../phive/api.php';
/*
if(phive("Config")->getValue('network-status', 'endorphina') == 'off'){
  die("turned off");
}
*/
$oSql = phive('SQL');

/**
 * Instance of Isoftbet
 * @var $oGp Endorphina
 */
$oGp = phive('Endorphina');

if ($oGp->getSetting('network-status') === 'off') {
    die();
}

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
