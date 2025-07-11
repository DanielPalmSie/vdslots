<?php
include __DIR__ . '/00__variables.php';

$user = cu('devtestit002');

$data = [
    'account_code' => $userid_02,
    'transaction_id' => time()
];


print_r(lic('accountEmailQuery', [$data], $user));