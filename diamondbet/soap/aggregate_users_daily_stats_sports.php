<?php
require_once __DIR__ . '/../../phive/phive.php';
require_once __DIR__ . '/../../phive/vendor/autoload.php';
require_once __DIR__ . '/../../phive/modules/Cashier/Fraud.php';

/**
 *
 * This is a one time use script for aggregating users_daily_stats_sports data from shards to master DB
 * story id: 199682
 *
 * Optional Arguments:
 * start_date:
 * -s2021-01-01
 * end_date:
 * -e2021-01-01
 */

if (isCli()) {
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

    $end_date->setTime(0, 0, 1);
    $period = new DatePeriod(
        $start_date,
        new DateInterval('P1D'),
        $end_date
    );

    foreach ($period as $key => $value) {
        $date = $value->format('Y-m-d');
        phive('UserHandler')->aggregateUserStatsTbl('users_daily_stats_sports', $date);
    }
}
