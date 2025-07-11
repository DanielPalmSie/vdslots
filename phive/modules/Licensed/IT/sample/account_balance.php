<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';


$user = cu('devtestit002');

$data = [
    'account_code' => 'test02',
    'balance_amount' => '5000',
    'total_bonus_balance_on_account' => '0',
    'transaction_id' => time(),
    'transaction_datetime' => [
        'date' => [
            'day' => '01',
            'month' => '04',
            'year' => '2020'
        ],
        'time' => [
            'hours' => '01',
            'minutes' => '05',
            'seconds' => '00'
        ]
    ],
];

print_r(lic('accountBalance', [$data], $user));




