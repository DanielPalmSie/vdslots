<?php
include __DIR__ . '/00__variables.php';

$user = cu('devtestit002');

$data = [
    'account_code' => $userid_01,
    'balance_amount' => '18000',
    'total_bonus_balance_on_account' => '3000',
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
    'bonus_details' => [  // All the bonuses being present on the account
        [
            'gaming_family' => '6',
            'gaming_type' => '2',
            'bonus_amount' => '3000',  // Bonus being reversed
        ],
    ],
];

print_r(lic('accountBalance', [$data], $user));




