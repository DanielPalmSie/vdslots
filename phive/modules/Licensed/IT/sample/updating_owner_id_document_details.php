<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';



$user = cu('devtestit002');

$data = [
    'account_code' => 'farnezi01',
    'transaction_id' => time(),
    'document' => [
        'document_type' => 1,
        'date_of_issue'=> [
            'day' => '01',
            'month' => '04',
            'year' => '2020'
        ],
        'document_number' => 'ABC123',
        'issuing_authority' => 'issuing authority',
        'where_issued' => 'whereissued',
    ],
];

print_r(lic('updatingOwnerIdDocumentDetails', [$data], $user));



