<?php
$smicro = microtime(true);
require_once __DIR__ . '/../../phive/api.php';
/*
if(phive("Config")->getValue('network-status', 'isoftbet') == 'off'){
    die("turned off");
}
*/
/**
 * Instance of Isoftbet
 * @var $oGp Isoftbet
 */
$oGp = phive('Isoftbet');
$oGp
->injectDependency(phive('Currencer'))
->injectDependency(phive('Bonuses'))
->injectDependency(phive('MicroGames'))
->injectDependency(phive('SQL'))
->injectDependency(phive('UserHandler'))
->injectDependency(phive('Localizer'))
->setStart($smicro);


$oGp->exec(file_get_contents('php://input'), (empty($_GET) ? array() : $_GET));
