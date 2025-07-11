<?php
require_once __DIR__ . '/../../phive/phive.php';
if (isCli()) {
    $GLOBALS['is_cron'] = true;

    $yesterday = date('Y-m-d', strtotime('-1 day'));
    lics('onEverydayReporting', [$yesterday]);

    $exec_path = phive()->getSetting('reporting_exec_path');
    $daily_report_output = shell_exec("/usr/bin/env php {$exec_path}/console daily_reports");
    if($daily_report_output === false){
        phive('Logger')->error("Cron - Execution daily report generation failed.");
    }
}
