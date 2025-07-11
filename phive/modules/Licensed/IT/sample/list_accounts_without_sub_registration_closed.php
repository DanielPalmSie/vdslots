<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';


$user = cu('devtestit002');

$data = [
    'date_request' => [
        'day' => '01',
        'month' => '04',
        'year' => '2020'
    ],
    'status' => 3,// [1 => (OPEN), 2 => (SUSPENDED), 3 => (CLOSED)]
    'start' => 1,
    'end' => 100,
    'transaction_id' => time()
];


print_r(lic('listAccountsWithoutSubRegistration', [$data], $user));