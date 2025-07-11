<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';


$user = cu('devtestit002');

$data = [
    'account_code' => $user->data['id'],
    'date_dormant' => [
        'day' => '01',
        'month' => '04',
        'year' => '2020'
    ],
    'balance_amount' => '6500',
    'transaction_id' => time()
];


print_r(lic('dormantAccount', [$data], $user));