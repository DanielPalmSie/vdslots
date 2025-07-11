<?php
include __DIR__ . '/00__variables.php';

$user = cu('devtestit002');

$data = [
    'account_code' => $userid_01,
    'province' => 'RO',
    'transaction_id' => time()
];


print_r(lic('changeAccountProvinceOfResidence', [$data], $user));