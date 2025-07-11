<?php
require_once __DIR__ . '/../../phive/phive.php';

if (isCli()) {
    $GLOBALS['is_cron'] = true;
    $logger = phive('Logger')->getLogger('cron');

    $logger->info("diamondbet/soap/midnight.php Started");

    $method_name = "phive('Bonuses')->failExpiredBonuses()";
    $logger->info("{$method_name} Started");
    try {
        phive('Bonuses')->failExpiredBonuses();
    } catch (Exception $e) {
        $logger->error("{$method_name} Failed", [$e]);
    }
    $logger->info("{$method_name} Finished");

    $c = phive('Cashier');
    $method_name = "clearInactive()";
    $logger->info("{$method_name} Started");
    try {
        $c->clearInactive(date('Y-m-d', strtotime('-13 month')));
    } catch (Exception $e) {
        $logger->error("{$method_name} Failed", [$e]);
    }
    $logger->info("{$method_name} Finished");

    $method_name = "cacheBalances()";
    $logger->info("{$method_name} Started");
    try {
        $c->cacheBalances();
    } catch (Exception $e) {
        $logger->error("{$method_name} Failed", [$e]);
    }
    $logger->info("{$method_name} Finished");

    $method_name = "autoPayStatsEmail()";
    $logger->info("{$method_name} Started");
    try {
        $conf = phive('Config');

        //if((int)date('N') === 1 && $conf->getValue('auto', 'clash') == 'yes')
        //    $c->autoPayStatsEmail(32, "Clash of Spins auto payments");

        if((int)date('N') === 5 && $conf->getValue('auto', 'booster') == 'yes')
            $c->autoPayStatsEmail(31, "Booster auto payments");
    } catch (Exception $e) {
        $logger->error("{$method_name} Failed", [$e]);
    }
    $logger->info("{$method_name} Finished");

    $method_name = "phive('Trophy')->resetCron()";
    $logger->info("{$method_name} Started");
    try {
        phive('Trophy')->resetCron();
    } catch (Exception $e) {
        $logger->error("{$method_name} Failed", [$e]);
    }
    $logger->info("{$method_name} Finished");

    $method_name = "lics('onEveryMidnight')";
    $logger->info("{$method_name} Started");
    try {
        lics('onEveryMidnight');
    } catch (Exception $e) {
        $logger->error("{$method_name} Failed", [$e]);
    }
    $logger->info("{$method_name} Finished");

    $logger->info("diamondbet/soap/midnight.php Finished");
}
