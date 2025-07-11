<?php
include __DIR__ . '/00__variables.php';

$user = cu('devtestit002');


// I noticed we are not able to subregister an account that is suspended.
// Maybe the response 1233 Unauthorised is the correct response
$data = [
    'account_code' => $userid_02,
    'balance_amount' => '0',
    'balance_bonus_amount' => '0',
    'transaction_id' => time()
];


print_r(lic('subregistration', [$data], $user));