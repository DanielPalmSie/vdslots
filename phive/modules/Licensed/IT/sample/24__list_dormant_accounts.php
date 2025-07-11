<?php

//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';

$user = cu('devtestit002');

$data = [
    'start' => 1,
    'end' => 100,
    'date_request' => [
        'day' => '01',
        'month' => '04',
        'year' => '2020'
    ],
    'transaction_id' => time()
];


print_r(lic('listDormantAccounts', [$data], $user));