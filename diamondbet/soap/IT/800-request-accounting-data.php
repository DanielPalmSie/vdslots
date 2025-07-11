<?php
require_once '/var/www/videoslots.it/phive/phive.php';

$user = cu($argv[1] ?? 6624618);

$date1 = new DateTime($argv[2]);
$date2 = new DateTime($argv[3]);

$data = [
    'game_code' => 0,
    'game_type' => 0,
    'period_start_date' => [
        'day' => $date1->format('d'),
        'month' => $date1->format('m'),
        'year' => $date1->format('Y'),

    ],
    'period_end_date' => [
        'day' => $date2->format('d'),
        'month' => $date2->format('m'),
        'year' => $date2->format('Y'),
    ]
];

print_r($data);
var_dump(lic('requestFinancialAccounting', [$data], $user));
