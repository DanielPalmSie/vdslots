<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';


$user = cu('devtestit002');

$data = [
    'account_code' => $user->data['id'],
    'account_sales_network_id' => '14',
    'transaction_reason' => '3', //Withdrawal
    'transaction_amount' => '200.00',
    'balance_amount' => '30',
    'total_bonus_balance_on_account' => '30',
    'transaction_id' => time(),
    'payment_method' => '1',
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
    ],
    'bonus_details' => [
        [
            'gaming_family' => '1',
            'gaming_type' => '1',
            'bonus_amount' => '30',
        ]
    ],
];

print_r(lic('accountTransactions', [$data], $user));



