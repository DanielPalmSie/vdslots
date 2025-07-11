<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';


$user = cu('devtestit002');
$data = [
    'account_code' => 'farnezi01',
    'payment_method' => '1',
    'total_bonus_balance_on_account' => '30',
    'bonus_balance_amount' => '30',
    'balance_amount' => '5000',
    'transaction_reason' => '5', //Bonus
    'transaction_amount' => '30',
    'transaction_id' => time(),
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
    'bonus_balance_details' => [
        [
            'gaming_family' => '1',
            'gaming_type' => '1',
            'bonus_amount' => '30',
        ],
    ],

    'bonus_details' => [
        [
            'gaming_family' => '1',
            'gaming_type' => '1',
            'bonus_amount' => '30',
        ],
    ],
];

var_dump(lic('bonusAccountTransactions', [$data], $user));



