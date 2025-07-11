<?php
$smicro = microtime(true);
require_once __DIR__ . '/../../phive/api.php';
/*
if (phive("Config")->getValue('network-status', 'spigo') == 'off') {
    die("turned off");
}
*/

/**
 * Instance of Spigo
 * @var $oGp Spigo
 */
$oGp = phive('Spigo');
$oGp
    ->injectDependency(phive('Currencer'))
    ->injectDependency(phive('Bonuses'))
    ->injectDependency(phive('MicroGames'))
    ->injectDependency(phive("SQL"))
    ->injectDependency(phive('UserHandler'))
    ->injectDependency(phive('Localizer'))
    ->setStart($smicro);

$oGp->preProcess();
$oGp->exec();
