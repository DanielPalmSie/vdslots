<?php

//require_once __DIR__ . '/../autoload.php';

//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';


$user = cu('devtestit002');  // Double check this user has IT as country in your DB

// NOTE: this file should return 10 validation errors:

$data = [
    'transaction_id' => time(), // todo: generate a unique id
    'account_code'   => $user->data['id'],
    'account_holder' => [
//        'tax_code'      => $user->getSetting('fiscal_code_it'),
        'tax_code'      => 1234,
        'surname'       => $user->data['lastname'],
        'name'          => $user->data['firstname'],
        'gender'        => 'M',
        'email'         => $user->data['email'],
        'pseudonym'     => $user->data['username'],
        'birth_data' => [
            'date_of_birth' => [
                'day'   => '123',
                'month' => '999',
                'year'  => 'year'
            ],
            'birthplace'                  => $user->getSetting('birthplace'),
//            'birthplace_province_acronym' => $user->getSetting('birthplace_province_acronym'),  // exactly 2 characters
            'birthplace_province_acronym' => 'ABC',
        ],
        'residence' => [
            'residential_address'          => $user->data['address'],
            'municipality_of_residence'    => $user->data['city'],
            'residential_province_acronym' => $user->getSetting('main_province'),
//            'residential_post_code'        => $user->data['zipcode'],  // exactly 5 characters
            'residential_post_code'        => '1234',
            'country'                      => 'Italy',
        ],
        'document'                     => [
            'document_type'     => '99',
            'date_of_issue'     => [
                'day'   => date('d', strtotime($user->getSetting('document_date_of_issue'))),
                'month' => date('m', strtotime($user->getSetting('document_date_of_issue'))),
                'year'  => date('Y', strtotime($user->getSetting('document_date_of_issue'))),
            ],
            'document_number'   => $user->getSetting('document_number'),
            'issuing_authority' => $user->getSetting('document_issuing_authority'),
            'where_issued'      => $user->getSetting('document_where_issued'),
        ],
    ],
    'number_of_limits' => 1,
    'limits' => [
        [
            'limit_type' => 't',
            'amount' => 'gsfga',
        ]

    ]
];

var_dump($data);


print_r(lic('onOpenAccountNaturalPerson', [$data], $user));



