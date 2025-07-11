<?php
include __DIR__ . '/00__variables.php';

$user = cu('devtestit002');

$data = [
    'account_code' => $userid_02,
    'email' => 'testpacg05_updated@devtest.com',
    'transaction_id' => time()
];


print_r(lic('updateEmailAccount', [$data], $user));