<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';


$user = cu('devtestit002');

$data = [
    'account_code' => 'pacg01',
    'transaction_receipt_id' => '1234567890123456789012345',
    'account_sales_network_id' => '14',
    'account_network_id' => '15427',
    'payment_method' => '3',
    'transaction_description' => '4', //Withdrawal
    'reversal_type' => '1',
    'transaction_amount' => '1',
    'balance_amount' => '1',
    'balance_bonus_amount' => '1',
    'balance_bonus_detail' => [
        [
            'gaming_family' => '1',
            'gaming_type' => '1',
            'bonus_amount' => '30',
        ],
    ],
    'datetime' => [
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
    'transaction_id' => time(),
];


print_r(lic('reversalAccountTransactions', [$data], $user));