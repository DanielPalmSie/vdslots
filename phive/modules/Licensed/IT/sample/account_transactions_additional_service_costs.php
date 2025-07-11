<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';


$user = cu('devtestit002');

$data = [

    'account_code' => 'test02',
    'account_sales_network_id' => '14',
    'account_network_id' => '15427',
    'transaction_reason' => '7', //Top Up
    'transaction_amount' => '5000',
    'balance_amount' => '5000',
    'total_bonus_balance_on_account' => '0',
    'transaction_id' => time(),
    'payment_method' => '2',
    'transaction_datetime' => [
        'date' => [
            'day' => date('d'),
            'month' => date('m'),
            'year' => date('Y')
        ],
        'time' => [
            'hours' => date('H'),
            'minutes' => date('i'),
            'seconds' => date('s')
        ]
    ]
];

print_r(lic('accountTransactions', [$data], $user));



