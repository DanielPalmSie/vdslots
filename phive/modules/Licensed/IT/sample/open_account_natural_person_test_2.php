<?php

//require_once __DIR__ . '/../autoload.php';
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';


$user = cu('devtestit001');  // Double check this user has IT as country in your DB

$data = [
    'transaction_id' => time(), // todo: generate a unique id
    'account_code'   => $user->data['id'],
    'account_holder' => [
        'tax_code'      => $user->getSetting('fiscal_code_it'),
        'surname'       => $user->data['lastname'],
        'name'          => $user->data['firstname'],
        'gender'        => 'F',
        'email'         => $user->data['email'],
        'pseudonym'     => $user->data['username'],
        'birth_data' => [
            'date_of_birth' => [
                'day'   => date('d', strtotime($user->data['dob'])),
                'month' => date('m', strtotime($user->data['dob'])),
                'year'  => date('Y', strtotime($user->data['dob'])),
            ],
            'birthplace'                  => $user->getSetting('birthplace'),
            'birthplace_province_acronym' => $user->getSetting('birthplace_province_acronym'),
            'country'                     => 'Italy'
        ],
        'residence' => [
            'residential_address'          => $user->data['address'],
            'municipality_of_residence'    => $user->data['city'],
            'residential_province_acronym' => $user->getSetting('main_province'),
            'residential_post_code'        => $user->data['zipcode'],
            'country'                      => 'Italy'
        ],
        'document'                     => [
            'document_type'     => $user->getSetting('document_type'),
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
            'limit_type' => 3,
            'amount' => 20000,
        ]

    ]
];

var_dump($data);


print_r(lic('onOpenAccountNaturalPerson', [$data], $user));



