<?php

require_once '/var/www/videoslots.it/phive/phive.php';

$date = new DateTime($argv[2]);
$user = cu($argv[3] ?? 6624618);

$data = [
    'game_code' => 0,
    'game_type' => 0,
    'central_system_session_id' => $argv[1] ?? 'M4F5D20155463EJL',
    'end_date_session' => [
        'day' => $date->format('d'),
        'month' => $date->format('m'),
        'year' => $date->format('Y'),
    ],
];

print_r($data);
var_dump(lic('sessionEndDateUpdateRequest', [$data], $user));
