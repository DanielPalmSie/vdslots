<?php
$smicro = microtime(true);
require_once __DIR__ . '/../../phive/api.php';

/**
 * Instance of Pushgaming
 * @var $oGp Pushgaming
 */
$oGp = phive('Pushgaming');
$oGp
    ->injectDependency(phive('Currencer'))
    ->injectDependency(phive('Bonuses'))
    ->injectDependency(phive('MicroGames'))
    ->injectDependency(phive('SQL'))
    ->injectDependency(phive('UserHandler'))
    ->injectDependency(phive('Localizer'))
    ->setStart($smicro);

$res = $oGp->setDefaults()->preProcess();
if(is_string($res)){
    // We have an error
    echo $res;
} else {
    $res = $oGp->exec();
    echo $res;
}
