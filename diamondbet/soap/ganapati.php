<?php
$smicro = microtime(true);
require_once __DIR__ . '/../../phive/api.php';

// require_once __DIR__ . '/../../devutils/devutils.php';
/*
if (phive("Config")->getValue('network-status', 'ganapati') == 'off') {
    die("turned off");
}
*/
/**
 * Instance of Ganapati
 * @var $oGp Ganapati
 */
$oGp = phive('Ganapati');

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
