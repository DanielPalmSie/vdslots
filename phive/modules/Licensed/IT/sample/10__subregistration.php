<?php
include __DIR__ . '/00__variables.php';

$user = cu('devtestit002');

$data = [
    'account_code' => 6303706,
    'balance_amount' => '11000',
    'balance_bonus_amount' => '4000',
    'transaction_id' => time()
];


print_r(lic('subregistration', [$data], $user));
