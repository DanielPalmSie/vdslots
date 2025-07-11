<?php
require_once '/var/www/videoslots.it/phive/phive.php';

if(!isCli())
    exit;

$user = cu('devtestit2');
$data = [
    'start' => 1,
    'end' => 100,
    'date_request' => [
        'day' => '23',
        'month' => '12',
        'year' => '2020'
    ],
    'transaction_id' => time()
];
print_r(lic('listDormantAccounts', [$data], $user));
