<?php
include __DIR__ . '/00__variables.php';

$user = cu('devtestit002');

$data = [
    'account_code' => $userid_02,
    'balance_amount' => '0',
    'balance_bonus_amount' => '0',
    'transaction_id' => time()
];


print_r(lic('subregistration', [$data], $user));