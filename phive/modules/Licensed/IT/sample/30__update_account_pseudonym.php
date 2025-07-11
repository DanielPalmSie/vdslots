<?php
include __DIR__ . '/00__variables.php';

$user = cu('devtestit002');

$data = [
    'account_code' => $userid_02,
    'pseudonym' => 'pacg04@test.it',
    'transaction_id' => time()
];


print_r(lic('updateAccountPseudonym', [$data], $user));