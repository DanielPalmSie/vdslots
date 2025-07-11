<?php
include __DIR__ . '/00__variables.php';

$user = cu('devtestit002');

$data = [
    'account_code' => $userid_02,
    'transaction_id' => time(),
    'document' => [
        'document_type' => 1,
        'date_of_issue'=> [
            'day' => '01',
            'month' => '04',
            'year' => '2020'
        ],
        'document_number'   => 'YA1231235',
        'issuing_authority' => 'Ministero Affari Esteri',
        'where_issued'      => 'Roma',
    ],
];

print_r(lic('updatingOwnerIdDocumentDetails', [$data], $user));



