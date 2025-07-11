<?php
require_once '/var/www/videoslots.it/phive/phive.php';

if(!isCli())
    exit;

$user = cu('devtestit2');

$date = new DateTime($argv[2]);
$data = [
    'game_code' => 45014,
    'game_type' => 2,
    'date_session_opened' => [
        'day' => $date->format('d'),
        'month' => $date->format('m'),
        'year' => $date->format('Y'),
    ]
];
var_dump($data);
var_dump(lic('reportedAnomalies', [$data], $user));
