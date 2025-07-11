<?php
require_once __DIR__ . '/../../phive/phive.php';
require_once __DIR__ . '/../../phive/vendor/autoload.php';
require_once __DIR__ . '/../../phive/modules/Cashier/Fraud.php';

/**
 * Optional Arguments:
 * start_date:
 * -s2021-01-01
 * end_date:
 * -e2021-01-01
 */

if(isCli()){
    $sql = phive('SQL');

    $start_date_arg = 's';
    $end_date_arg = 'e';
    $options = getopt("$start_date_arg:$end_date_arg:");

    $start_date = new DateTime($options[$start_date_arg] ?? 'yesterday');
    $end_date = new DateTime($options[$end_date_arg] ?? 'yesterday');

    if ($start_date > $end_date) {
        throw new InvalidArgumentException('Wrong period selected');
    }

    echo sprintf('Calculate stats for period: %s - %s', $start_date->format('Y-m-d'), $end_date->format('Y-m-d'));

    $end_date->setTime(0,0,1);
    $period = new DatePeriod(
        $start_date,
        new DateInterval('P1D'),
        $end_date
    );

    foreach ($period as $key => $value) {
        $date 	= $value->format('Y-m-d');
        $sdate 	= $date.' 00:00:00';
        $edate 	= $date.' 23:59:59';

        if($sql->isSharded('users_daily_stats_sports')){
            $sql->loopShardsSynced(function($db) use($sdate, $edate, $date){
                phive('Micro/Sportsbook')->calcSportsBookDailyStats($sdate, $edate, $date, $db);
            });
            phive('UserHandler')->aggregateUserStatsTbl('users_daily_stats_sports', $date);
        }else {
            phive('Micro/Sportsbook')->calcSportsBookDailyStats($sdate, $edate, $date);
        }
    }
}
