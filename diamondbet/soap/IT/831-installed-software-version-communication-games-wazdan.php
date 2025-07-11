<?php

require_once '/var/www/videoslots.it/phive/phive.php';
$user = cu($argv[1] ?? 6624618);

$games = [
    [
        'code' => '67697',
        'name' => 'game395.so',
        'hash' => '4ce414b640bfdd9112529070a7235856c4500a28',
    ],
    [
        'code' => 95189,
        'name' => 'game401.so',
        'hash' => 'fd03430b6baee2b9808c9fd35544a28c859af07d',
    ],
    [
        'code' => 95191,
        'name' => 'game402.so',
        'hash' => '201f4cea14d87e7e3ff4075dffd7560860fc43da',
    ],
    [
        'code' => 67691,
        'name' => 'game397.so',
        'hash' => '08b4f43eeb80e40bbb932dfa2e33f53bb2747034',
    ],
    [
        'code' => 95199,
        'name' => 'game360.so',
        'hash' => '799dd5aaca651141b28063efc12b673e3a824bbe',
    ],
    [
        'code' => 67692,
        'name' => 'game367.so',
        'hash' => '316e5f5450adc83f3b39abe498d9e94ab892e301',
    ],
    [
        'code' => 95206,
        'name' => 'game403.so',
        'hash' => '4c7fa3d0a578fa9e7122d29e46e1a8ce714873e5',
    ],
    [
        'code' => 67696,
        'name' => 'game396.so',
        'hash' => '861c0819cdb2b9af4f54695f52947fd4265addca',
    ],
    [
        'code' => 67689,
        'name' => 'game394.so',
        'hash' => '380b991f042c3b6006d62fecbb05921685d5b638',
    ],
    [
        'code' => 95210,
        'name' => 'game392.so',
        'hash' => '6c3469b4f16e1296c4419f80f8f830072b9eafe6',
    ],
    [
        'code' => 95212,
        'name' => 'game387.so',
        'hash' => 'd8f7a73636578c63518c87be383fd1706bea9751',
    ],
    [
        'code' => 95217,
        'name' => 'game377.so',
        'hash' => '2aa018f62a9f4645bfdbfc0d8b147c4f5e661906',
    ],
    [
        'code' => 95220,
        'name' => 'game391.so',
        'hash' => '5154ffd65a938eb5b1f0bf86d5dbce77ed974db3',
    ],
    [
        'code' => 95224,
        'name' => 'game336.so',
        'hash' => 'ee5887cbefb082c2aab32cd34cba97f6c68b0983',
    ],
    [
        'code' => 95226,
        'name' => 'game352.so',
        'hash' => 'e6003875f9e161eb6eee93e86e799c8552dc66fc',
    ],
    [
        'code' => 95235,
        'name' => 'game352.so',
        'hash' => 'e6003875f9e161eb6eee93e86e799c8552dc66fc',
    ],
    [
        'code' => 95239,
        'name' => 'game353.so',
        'hash' => '4830089030cd2a7119b0168a2e54a243c105e91f',
    ],
    [
        'code' => 95242,
        'name' => 'game360.so',
        'hash' => '799dd5aaca651141b28063efc12b673e3a824bbe',
    ],
    [
        'code' => 95246,
        'name' => 'game368.so',
        'hash' => 'ab27704668139681c61e5c7813f585ec61f230ea',
    ],
    [
        'code' => 67688,
        'name' => 'game361.so',
        'hash' => '38c772bddd3dc05c4c5bf49716ad7030b03eea3d',
    ],
    [
        'code' => 67690,
        'name' => 'game379.so',
        'hash' => '911de5a5bfb473b71012a7b2ff563062a4cb32f5',
    ],
    [
        'code' => 95248,
        'name' => 'game381.so',
        'hash' => 'b1722dbacc30a57a610741170124f9ec2cf67248',
    ],
    [
        'code' => 67693,
        'name' => 'game389.so',
        'hash' => '8cfe49cc48ffe06f9b627418db827310e3cacea5',
    ],
    [
        'code' => 67694,
        'name' => 'game337.so',
        'hash' => 'df4a564b92106721ab0026ddd66a8685357a4e71',
    ],
    [
        'code' => 95251,
        'name' => 'game341.so',
        'hash' => 'a049bb67c185a6d4943ccf4b19068781789b25a7',
    ],
    [
        'code' => 95253,
        'name' => 'game386.so',
        'hash' => '5cc36ead657e08b6cdafd258bacaee64891a7ede',
    ],
    [
        'code' => 95252,
        'name' => 'game388.so',
        'hash' => 'aa05239f6b0f1d160e3e2cc996a777ff56905c04',
    ],
    [
        'code' => 95250,
        'name' => 'game359.so',
        'hash' => 'b479b4cfaefdaaa8d53175f8fa40ef128ecd729d',
    ],
    [
        'code' => 95249,
        'name' => 'game384.so',
        'hash' => '44a14bd4af9fee0473eea9fd43164273beafbea3',
    ],
    [
        'code' => 95244,
        'name' => 'game369.so',
        'hash' => '3e97297b5668f00c139f777bdb72209da662c978',
    ],
    [
        'code' => 95240,
        'name' => 'game350.so',
        'hash' => 'd63cc1d7721a24162557872ebc9c3db47c2f7a36',
    ],
    [
        'code' => 95238,
        'name' => 'game339.so',
        'hash' => 'cf9abe2dc2739e667b7782080a10cc3ae72a3d77',
    ],
    [
        'code' => 95234,
        'name' => 'game357.so',
        'hash' => '1bb472cf180130759a350f7d8ea3d5e186fd6de9',
    ],
    [
        'code' => 95222,
        'name' => 'game348.so',
        'hash' => 'a9acc3d0c923fa403b41e30a1a88a8f780eff5eb',
    ],
    [
        'code' => 95218,
        'name' => 'game344.so',
        'hash' => '8dfbce9d6b684964d1d4a9a846b7e3738415f159',
    ],
    [
        'code' => 67695,
        'name' => 'game373.so',
        'hash' => '3c06da19b4047871a40568bb712bb17b6b11e653',
    ],
    [
        'code' => 95211,
        'name' => 'game372.so',
        'hash' => 'd17f786df26e01cb584386e05d1b44b291309f69',
    ],
    [
        'code' => 95198,
        'name' => 'Game359.so',
        'hash' => 'b479b4cfaefdaaa8d53175f8fa40ef128ecd729d',
    ],
    [
        'code' => 67687,
        'name' => 'game390.so',
        'hash' => '59213914b3b201a0d8e6a76e562c90bd3274dda8',
    ],
    [
        'code' => 89844,
        'name' => 'game352.so',
        'hash' => 'e6003875f9e161eb6eee93e86e799c8552dc66fc',
    ],
    [
        'code' => 104569,
        'cert_ver' => 1,
        'name' => 'game394.so',
        'hash' => '380b991f042c3b6006d62fecbb05921685d5b638',
    ],
    [
        'code' => 104570,
        'cert_ver' => 1,
        'name' => 'game412.so',
        'hash' => '8a6159acd2b79118413710ad059302952ee93de8',
    ],
    [
        'code' => 104571,
        'cert_ver' => 1,
        'name' => 'game411.so',
        'hash' => '882650aacb843eae1a1a9c4d8892342438210628',
    ],
    [
        'code' => 104572,
        'cert_ver' => 1,
        'name' => 'game420.so',
        'hash' => '2488416dfa90a4aac92d6ce5a90c6ee7bed0a6cd',
    ],
    [
        'code' => 104580,
        'cert_ver' => 1,
        'name' => 'game399.so',
        'hash' => 'b934ee5c6601694c0feae5eb9636cdfd7b55724c',
    ],
    [
        'code' => 104584,
        'cert_ver' => 1,
        'name' => 'game360.so',
        'hash' => '799dd5aaca651141b28063efc12b673e3a824bbe',
    ],
    [
        'code' => 104586,
        'cert_ver' => 1,
        'name' => 'game417.so',
        'hash' => 'b6e92736c7706a67444e4fa40a79c66b8cdd533c',
    ],
    [
        'code' => 104589,
        'cert_ver' => 1,
        'name' => 'game416.so',
        'hash' => '07c2ab6fe83885110e787a91ec704bb9b9af210f',
    ],
    [
        'code' => 104590,
        'cert_ver' => 1,
        'name' => 'game407.so',
        'hash' => 'ac6a8897c8d6cf1bfc40628a2176b82833150ef0',
    ],
    [
        'code' => 104591,
        'cert_ver' => 1,
        'name' => 'game413.so',
        'hash' => 'f67857cd3847ed4c51eb030942f64e6e46dab6e1',
    ],
    [
        'code' => 104592,
        'cert_ver' => 1,
        'name' => 'game408.so',
        'hash' => '3271dfb28a610b77562d2d96202ca39f7e229423',
    ],
    [
        'code' => 104593,
        'cert_ver' => 1,
        'name' => 'game404.so',
        'hash' => 'b854bc36338e4cd6dc38660bd739d93f4ac8278d',
    ],
    [
        'code' => 104594,
        'cert_ver' => 1,
        'name' => 'game406.so',
        'hash' => '279dacdfee823a836e2d381a731072e13a54108a',
    ]
];

foreach ($games as $game) {
    $i = 0;
    if (!empty($game['software_modules'])) {
        foreach ($game['software_modules'] as $module) {
            $software_modules[$i]['lun_nome_modulo_critico'] = strlen($game['software_modules'][$i]['name']);
            $software_modules[$i]['name_critical_module'] = $game['software_modules'][$i]['name'];
            $software_modules[$i]['hash_critical_module'] = $game['software_modules'][$i]['hash'];
            $i++;
        }


    } else {
        $software_modules = [
            [
                'name_critical_module' => $game['name'],
                'hash_critical_module' => $game['hash']
            ]
        ];
    }
    if (!empty($game['cert_ver'])) {
        $cert_vers = $game['cert_ver'];
    } else {
        $cert_vers = 1;
    }

    $payload = [
        'game_code' => 0,
        'game_type' => 0,
        'cod_element_type' => 2,
        'cod_element' => $game['code'],
        'prog_cert_version' => $cert_vers,
        'prog_sub_cert_version' => 0,
        'ctr_lista_moduli_sw' => count($software_modules),
        'software_modules' => $software_modules
    ];

    unset($software_modules);

    $response = lic('installedSoftwareVersionCommunication', [$payload], $user);

    if ($response['code'] !== 0) {
        print_r($payload);
        print_r($response);
    }

    sleep(2);
}

echo "Script complete";

