<?php
require_once __DIR__ . '/../../phive/phive.php';

if(isCli()){
    $GLOBALS['is_cron'] = true;
    phive('Localizer')->monthlyCron();
    phive('CasinoCashier')->balanceClearanceForInactivePlayers();

    if (phive()->getSetting('enable_monthly_snapshot', false)) {
        $fixed_start_date = '2021-11-15';
        $end_date = date('Y-m-d', strtotime('yesterday'));
        $export_folder = phive()->getSetting('snapshot_export_folder');
        $exec_path = phive()->getSetting('snapshot_exec_path');
        $log_path = phive()->getSetting('snapshot_log_folder');
        $log_file = "{$log_path}/{$end_date}_monthly_snapshot.log";

        $output = shell_exec("/usr/bin/env php {$exec_path}/console export:ics ES {$fixed_start_date} {$end_date} --dir={$export_folder} >{$log_file} 2>&1");
        if($output === false){
            phive('Logger')->error("Cron - Execution monthly snapshot failed, check log file {$log_file}.");
        }
    }

    $year = date('Y', strtotime(date('Y-m')." -1 month"));
    $month = date('m', strtotime("-1 month"));
    $monthly_validation = (new ES\ICS\Validation\Report(phive('Licensed/ES/ES')->getAllLicSettings()))->createReport($year, $month);
    phive('Logger')->info("Cron - Execution monthly validation report {$monthly_validation}.");
}
