<?php
require_once '/var/www/videoslots.it/phive/phive.php';
require_once __DIR__ . '/../../phive/modules/Licensed/IT/IT.php';

if(!isCli())
    exit;

$user = cu('devtestit2');

$data = [
    'game_code'             => 0,
    'game_type'             => 0,
    'cod_element_type'      => 2,
    'cod_element'           => 47887,
    'prog_cert_version'     => 1,
    'prog_sub_cert_version' => 0,
    'software_modules'      => [
        [
            'name_critical_module' => 'component.fireflygames.PlaynGO.Modules.Games.Machine60.dll',
            'hash_critical_module' => '59304fc77e9e7bcd04dca0f910559166e7745620'
        ]
    ]
];
$it = new IT();
$it->installedSoftwareVersionCommunication($data);
var_dump(lic('installedSoftwareVersionCommunication', [$data], $user));
