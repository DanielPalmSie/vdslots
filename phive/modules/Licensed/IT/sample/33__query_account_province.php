<?php
include __DIR__ . '/00__variables.php';

$user = cu('devtestit002');  // Double check this user has IT as country in your DB

$data = [
    'transaction_id' => time(), // todo: generate a unique id
    'account_code'   => $userid_02,

];


print_r(lic('onAccountProvinceQuery', [$data], $user));







