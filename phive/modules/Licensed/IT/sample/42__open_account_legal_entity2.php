<?php
include __DIR__ . '/00__variables.php';

$user = cu('devtestit');  // Double check this user has IT as country in your DB

/*
 * 1 - Gaming accounts for the special shared betting of the sports forecast contests
 * 2 - Gaming accounts for functional checks in the real environment
 */
$account_type = 2;

$data = [
    'transaction_id' => time(),
    'account_code'   => $legal_user_id,
    'account_type' => $account_type,
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
