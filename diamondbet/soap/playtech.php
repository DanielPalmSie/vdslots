<?php
$smicro = microtime(true);
require_once __DIR__ . '/../../phive/api.php';

/*
if(phive("Config")->getValue('network-status', 'playtech') == 'off'){
  die("turned off");
}
*/

$oSql = phive('SQL');

/**
 * Instance of Playtech
 * @var $oGp Playtech
 */
$oGp = phive('Playtech');
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
