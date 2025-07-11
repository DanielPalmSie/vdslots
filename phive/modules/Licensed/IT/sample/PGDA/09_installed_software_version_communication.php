<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/../load_test.php';

$user = cu($argv[1] ?? 'devtestit002');

$data = [
    'game_code' => 0,
    'game_type' => 0,
    'cod_element_type' => 2,
    'cod_element' => 45014,
    'prog_cert_version' => 1,
    'prog_sub_cert_version' => 0,
    'software_modules'=> [
        [
            'name_critical_module' => 'Modulo1',
            'hash_critical_module' => '1111111111111111111111111111111111111111'
        ],
        [
            'name_critical_module' => 'Modulo2',
            'hash_critical_module' => '2222222222222222222222222222222222222222'
        ],
        [
            'name_critical_module' => 'Modulo3',
            'hash_critical_module' => '3333333333333333333333333333333333333333'
        ],
        [
            'name_critical_module' => 'Modulo4',
            'hash_critical_module' => '4444444444444444444444444444444444444444'
        ]
    ]

];

var_dump($data);
var_dump(lic('installedSoftwareVersionCommunication', [$data], $user));