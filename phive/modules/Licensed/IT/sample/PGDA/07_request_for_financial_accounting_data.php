<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/../load_test.php';

$user = cu($argv[1] ?? 'devtestit002');

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

var_dump($data);
var_dump(lic('requestFinancialAccounting', [$data], $user));