<?php
include __DIR__ . '/00__variables.php';

$user = cu('devtestit002');  // Double check this user has IT as country in your DB

$data = [
    'transaction_id' => time(), // todo: generate a unique id
    'account_code'   => $userid_02,
    'account_holder' => [
        'tax_code'      => 'DMTNNT79P49H501D',
        'surname'       => 'D\'AMATO',
        'name'          => 'ANTONIETTA',
        'gender'        => 'F',
        'email'         => 'pacg04@test.it',
        'pseudonym'     => 'pacg04',
        'birth_data' => [
            'date_of_birth' => [
                'day'   => '09',
                'month' => '09',
                'year'  => '1979',
            ],
            'birthplace'                  => 'ROMA',
            'birthplace_province_acronym' => 'RM',
            'country'                     => 'Italy'
        ],
        'residence' => [
            'residential_address'          => 'Via Roma 1',
            'municipality_of_residence'    => 'Roma',
            'residential_province_acronym' => 'RM',
            'residential_post_code'        => '00100',
            'country'                      => 'Italy'
        ],
        'document'                     => [
            'document_type'     => 1,
            'date_of_issue'     => [
                'day'   => '09',
                'month' => '10',
                'year'  => '2008',
            ],
            'document_number'   => 'YA5689123',
            'issuing_authority' => 'Ministro Affari Esteri',
            'where_issued'      => 'Roma',
        ],
    ],
    'number_of_limits' => 1,
    'limits' => [
        [
            'limit_type' => 2,
            'amount' => 15000,
        ]

    ]
];


var_dump(lic('onOpenAccountNaturalPerson', [$data], $user));



