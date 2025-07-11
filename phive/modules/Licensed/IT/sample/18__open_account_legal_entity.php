<?php
include __DIR__ . '/00__variables.php';

$user = cu('devtestit');  // Double check this user has IT as country in your DB

$data = [
    'transaction_id' => time(),
    'account_code'   => $legal_user_id,
    'account_holder' => [
        'vat_number' => '91405930370',     // Error 1102  Licensee Account holder and VAT number not consistent
        'company_name' => 'Videoslots Ltd',
        'email' => 'info@videoslots.com',
        'company_headquarter' => [
            'residential_address'          => 'Via Roma 1',
            'municipality_of_residence'    => 'Estero',
            'residential_province_acronym' => 'EE',
            'residential_post_code'        => '00100',
            'country'                      => 'Malta'
        ],
        'pseudonym' => 'legal01',
    ],

];


print_r(lic('onOpenAccountLegalEntity', [$data], $user));



