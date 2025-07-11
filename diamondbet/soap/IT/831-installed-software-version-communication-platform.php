<?php

require_once '/var/www/videoslots.it/phive/phive.php';

$user = cu($argv[1] ?? 6624618);

$payload = [
    'game_code'             => 0,
    'game_type'             => 0,
    'cod_element_type'      => 1,
    'cod_element'           => 194,
    'prog_cert_version'     => 8,
    'prog_sub_cert_version' => 0,
    'software_modules'      => [
        [
            'name_critical_module' => 'BMITDC-VS-FW01',
            'hash_critical_module' => '05769b7a844826b2b32d5ca726df449877563d5d'
        ],[
            'name_critical_module' => 'BMITDC-VS-FW02',
            'hash_critical_module' => 'e10f9af38054f04db995e4ba581ef31d4902bcec'
        ],[
            'name_critical_module' => 'Cashier Module',
            'hash_critical_module' => 'c50379550dc526ba1160074ae790f14d086cb963'
        ],[
            'name_critical_module' => 'DB Master - Videoslots',
            'hash_critical_module' => 'df7c777b8131b2e515d793b51fe08145d9e22046'
        ],[
            'name_critical_module' => 'MELITADC-VS-FW01',
            'hash_critical_module' => '5a6abe38ccb895ae3e8157c0f4c1dfb9d29ae1e1'
        ],[
            'name_critical_module' => 'MELITADC-VS-FW02',
            'hash_critical_module' => 'aca8b5ee0e3968c84aeb0755263c83260ce58dcc'
        ],[
            'name_critical_module' => 'Melita5 Dell Poweredge R820',
            'hash_critical_module' => '8b8b701cc7d205f6c6dbccc5762562e22ab42ad7'
        ],[
            'name_critical_module' => 'Melita8 Dell Poweredge R940',
            'hash_critical_module' => 'e20463b309df961fd0f6c71c20ed67296826bde4'
        ],[
            'name_critical_module' => 'Node0 shard Master - Videoslots',
            'hash_critical_module' => '0ec053d8682ea473328c64bebff7e3cc57289886'
        ],[
            'name_critical_module' => 'Node1 shard Master - Videoslots',
            'hash_critical_module' => 'fc66b8a177380397159c243c329a37ec7db1ee60'
        ],[
            'name_critical_module' => 'Node2 shard Master - Videoslots',
            'hash_critical_module' => '6b1d332915ff16e57dfa392291e0d18a0d297444'
        ],[
            'name_critical_module' => 'Node3 shard Master - Videoslots',
            'hash_critical_module' => 'acbf35529fa8e63d6701ba30f3ae864c08f56847'
        ],[
            'name_critical_module' => 'Node4 shard Master - Videoslots',
            'hash_critical_module' => 'f40ede2f11bb5d3debb944b09cad24891d23c85c'
        ],[
            'name_critical_module' => 'Node5 shard Master - Videoslots',
            'hash_critical_module' => '1f00d087e43f2278d129ec3725708a1c06a85d32'
        ],[
            'name_critical_module' => 'Node6 shard Master - Videoslots',
            'hash_critical_module' => '4cb30ff159a1d72feb133b8f8c33683a6a80d006'
        ],[
            'name_critical_module' => 'Node7 shard Master - Videoslots',
            'hash_critical_module' => 'a749126b2220fd3b3c858c8e14f89b20f6150cf2'
        ],[
            'name_critical_module' => 'Node8 shard Master - Videoslots',
            'hash_critical_module' => '06b59f03282e0e9cef12cf89d9580c30a40ca6dc'
        ],[
            'name_critical_module' => 'Node9 shard Master - Videoslots',
            'hash_critical_module' => '97481497e436f926453f8ad89fda55abeaa4418f'
        ],[
            'name_critical_module' => 'User Registration Module',
            'hash_critical_module' => '22c3a54b1d4b45f443df80d2804d778c1a531170'
        ]
    ]
];

$response = lic('installedSoftwareVersionCommunication', [$payload], $user);

if($response['code'] !== 0) {
    print_r($response);
}