<?php
ini_set('max_execution_time', '30000');
require_once __DIR__ . '/../../phive/phive.php';
if(isCli()){
    $c = phive('Cashier');
    $conf = phive('Config');

    // Turned off for now, we use Clash of Spins now with non monetary prizes.
    //if((int)date('N') === 1 && $conf->getValue('auto', 'clash') == 'yes')
    //    $c->autoPayStatsEmail(32, "Clash of Spins auto payments");
    
    if((int)date('N') === 5 && $conf->getValue('auto', 'booster') == 'yes')
        $c->autoPayStatsEmail(31, "Booster auto payments");
}
