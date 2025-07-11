<?php

//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';

$user = cu('devtestit002');

$data = [
    'transaction_id' => time(),
    'date'=> [
        'day' => '15',
        'month' => '06',
        'year' => '2020'
    ],

];

print_r(lic('summaryOfServiceOperations', [$data], $user));



