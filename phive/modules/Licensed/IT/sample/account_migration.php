<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';


$user = cu('devtestit002');

$data = [
    'account_code' => $user->data['id'],
    'account_sales_network_id_destination' => '321',
    'account_network_id_destination' => '14',
    'account_code_destination' => '3212',
    'tax_code' => 'TSTDSA85P57F205D',
    'balance_amount' => '1',
    'balance_bonus_amount' => '1',
    'balance_bonus_detail' => [
        [
            'gaming_family' => '1',
            'gaming_type' => '1',
            'bonus_amount' => '1'
        ]
    ],
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
    'transaction_id' => time(),
];

print_r(lic('accountMigration', [$data], $user));