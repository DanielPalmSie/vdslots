<?php
include __DIR__ . '/00__variables.php';

$user = cu('devtestit2');  // Double check this user has IT as country in your DB

$data = [
    'transaction_id' => time(), // todo: generate a unique id
    'account_code'   => uid($user),
    'account_holder' => [
        'tax_code'      => 'RSSMLN62A01G224D',
        'surname'       => 'ROSSI',
        'name'          => 'MARIOLINO',
        'gender'        => 'M',
        'email'         => 'pacg03@collaudo.it',
        'pseudonym'     => 'pacg03',
        'birth_data' => [
            'date_of_birth' => [
                'day'   => '01',
                'month' => '01',
                'year'  => '1962',
            ],
            'birthplace'                  => 'PADOVA ',
            'birthplace_province_acronym' => 'PD',
            'country'                     => 'IT'
        ],
        'residence' => [
            'residential_address'          => 'Via Roma 1 ',
            'municipality_of_residence'    => 'ROMA',
            'residential_province_acronym' => 'RM',
            'residential_post_code'        => '00100',
            'country'                      => 'IT'
        ],
        'document'                     => [
            'document_type'     => 1,
            'date_of_issue'     => [
                'day'   => '09',
                'month' => '10',
                'year'  => '2008',
            ],
            'document_number'   => 'AR0000002',  // remove spaces
            'issuing_authority' => 'Comune',
            'where_issued'      => 'ITALY',
        ],
    ],
    'number_of_limits' => 1,
    'limits' => [
        [
            'limit_type' => 2,
            'amount' => 50000,
        ]
    ],
    'personal_data_origin_type' => 1
];


var_dump(lic('onOpenAccountNaturalPerson', [$data], $user));



