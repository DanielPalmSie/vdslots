<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';


$user = cu('devtestit');  // Double check this user has IT as country in your DB

$data = [
    'transaction_id' => time(), // todo: generate a unique id
    'account_code'   => 'pacg02',
    'account_holder' => [
        'tax_code'      => 'RSUNIO63D02Z140S',
        'surname'       => 'RUSU',
        'name'          => 'ION',
        'gender'        => 'M',
        'email'         => 'pacg02@collaudo.it',
        'pseudonym'     => 'pacg02',
        'birth_data' => [
            'date_of_birth' => [
                'day'   => '02',
                'month' => '04',
                'year'  => '1963',
            ],
            'birthplace'                  => 'Estero',
            'birthplace_province_acronym' => 'EE',
            'country'                     => 'MOLDAVIA'
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
            'document_number'   => 'AR0000002',
            'issuing_authority' => 'Comune',
            'where_issued'      => 'Roma',
        ],
    ],
    'number_of_limits' => 1,
    'limits' => [
        [
            'limit_type' => 2,
            'amount' => 50000,
        ]

    ]
];


print_r(lic('onOpenAccountNaturalPerson', [$data], $user));



