<?php

ini_set('max_execution_time', '1800');
require_once __DIR__ . '/../../phive/phive.php';

if (isCli()) {
    $GLOBALS['is_cron'] = true;

    $logger = phive('Logger')->getLogger('cron');

    if (phive()->getSetting('crons.woj_microservice')) {
        $logger->info('sendWheelOfJackpotsToMicroservice started');
        phive()->sendWheelOfJackpotsToMicroservice();
        $logger->info('sendWheelOfJackpotsToMicroservice finished');
    }

    if (phive()->getSetting('crons.jackpots_microservice')) {
        $logger->info('sendJackpotDataToMicroservice started');
        phive()->sendJackpotDataToMicroservice();
        $logger->info('sendJackpotDataToMicroservice finished');
    }
}
