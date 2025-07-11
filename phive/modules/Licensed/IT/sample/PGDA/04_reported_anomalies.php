<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/../load_test.php';

$user = cu($argv[1] ?? 'devtestit002');
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