<?php
require_once __DIR__ . '/../../phive/phive.php';
if (isCli()) {
    $GLOBALS['is_cron'] = true;

    phive()->fire('cron', 'cronTimeoutSessionsEvent', [], 0, function () {
        phive('Events/CronEventHandler')->onCronTimeoutSessionsEvent();
    });

    phive()->fire('cron', 'cronUpdateJpValuesEvent', [], 0, function () {
        phive('DBUserHandler/JpWheel')->updateJpValues();
    });

    /*
      // Turned off for now becuase of Redis optimizations
    $last_bet = phMget('netent-betstamp');
    $diff = time() - $last_bet;
    if(!empty($last_bet) && $diff > 60 && hasMp()){
        if(phive('Tournament')->getSetting('pause_calcs') === true)
            phive('Tournament')->pausePrizeCalc();
    }
    */

    phive('Cashier/Arf')->invoke('onEveryMinCron');

    lics('onEveryMin');

    if (!phive()->getSetting('has_dedicated_reporting_cron', false)) {
        lics('onEveryMinReporting');
    }

    phive('Site/Publisher')->cronJobSinglePublisherRetry();
}
