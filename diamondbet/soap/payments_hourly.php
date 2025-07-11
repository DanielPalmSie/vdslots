<?php
ini_set('max_execution_time', '30000');
require_once __DIR__ . '/../../phive/phive.php';

if(isCli()){
    $GLOBALS['is_cron'] = true;
    /** @var CasinoCashier $c */
    $c = phive('Cashier');

    /** @var Booster $b */
    $b = phive('DBUserHandler/Booster');

    /** @var Config $conf */
    $conf = phive('Config');

    // Current day as integer; 1 = Monday, 2 = Tuesday, ...
    $day = (int) date('N');

    if($day === 1 && $conf->getValue('auto', 'clash') == 'yes') {
        if (phive('Race')->getSetting('clash_of_spins'))
            phive('Race')->payAwards();
        else
            $c->payQdTransactions(32, '', true);
    }

    phive('MailHandler2')->notifyException(static function() use ($b, $c, $conf, $day) {
        if ($day === 5 && $conf->getValue('auto', 'booster') === 'yes') {
            $c->payQdTransactions(31, '', true);
            $b->releaseBySchedule();
        }
    }, "Booster Vault Release");
}
