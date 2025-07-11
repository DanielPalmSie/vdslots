<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/../load_test.php';

$user = cu($argv[1] ?? 'devtestit002');
$date = new DateTime($argv[3]);

$data = [
    'game_code' => 0,
    'game_type' => 0,
    'central_system_session_id' => $argv[2] ?? 'M4F5D20155463EJL',
    'end_date_session' => [
        'day' => $date->format('d'),
        'month' => $date->format('m'),
        'year' => $date->format('Y'),
    ],
];

var_dump($data);
var_dump(lic('sessionEndDateUpdateRequest', [$data], $user));