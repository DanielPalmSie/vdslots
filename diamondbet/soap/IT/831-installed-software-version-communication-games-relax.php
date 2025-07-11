<?php

require_once '/var/www/videoslots.it/phive/phive.php';
$user = cu($argv[1] ?? 6624618);

$games = [
    [
        'code' => 89543,
        'name' => 'config_moneytrain2_94.xml',
        'hash' => '399EAD0602A710805040ECF516F62FDF33A6D4F8',
    ],
    [
        'code' => 101108,
        'name' => 'config_bananatown_94.xml',
        'hash' => '9047F4691C537F58F9EA14FB04E1BD13DF9A7E4E',
    ],
    [
        'code' => 89534,
        'name' => 'config_beastmode.xml',
        'hash' => '2D9B51A683CD97E594B0208497760A783A02A103',
    ],
    [
        'code' => 96356,
        'name' => 'config_blackjackneo.xml',
        'hash' => 'C6250019403C55DED228DDBEAC51EAD3CA9C180D',
    ],
    [
        'code' => 96357,
        'name' => 'config_blenderblitz_94.xml',
        'hash' => '29AC4E82863C4F5297079D13E5D7385AD07CB6F1',
    ],
    [
        'code' => 96358,
        'name' => 'config_cavemanbob.xml',
        'hash' => '4987AC7BE1D3C9F1DA78DC775B14C8F2E75F8015',
    ],
    [
        'code' => 89538,
        'name' => 'config_chipspin.xml',
        'hash' => '8245CF8DB287DD73912A33D86DAAF610F2DD748C',
    ],
    [
        'code' => 89540,
        'name' => 'config_clustertumble_94.xml',
        'hash' => 'AC8EAA51AD14291303C1BE2F5281B7D2D8EAD8B6',
    ],
    [
        'code' => 96359,
        'name' => 'config_deadmanstrail_94.xml',
        'hash' => 'FABF9D8B24569DC5D4B21B5766377F6DD4754256',
    ],
    [
        'code' => 96360,
        'name' => 'config_deadriderstrail_94.xml',
        'hash' => '030AF4FC265F815D670AD7729C20698764B55C75',
    ],
    [
        'code' => 96361,
        'name' => 'config_deepdescent.xml',
        'hash' => 'EF1CD912ACC5A1565C9F0932A5C627601D83F0A6',
    ],
    [
        'code' => 96364,
        'name' => 'config_dragonsawakening.xml',
        'hash' => '5FD6496FF859855B18FE8BB3E3D7ACC817FB921B',
    ],
    [
        'code' => 96372,
        'name' => 'config_emeraldsinfinityreels.xml',
        'hash' => '2803852B969037EE4F9F71F5C0C5D0F5ADB06035',
    ],
    [
        'code' => 96373,
        'name' => 'config_epicjoker.xml',
        'hash' => '57F24E968C263EF9CCAAD5EA03255AB2BC32ECB1',
    ],
    [
        'code' => 96375,
        'name' => 'config_erikthered.xml',
        'hash' => '2C3AA90E0BC4A4FFFDA11E323CDE03CE65270ECD',
    ],
    [
        'code' => 96384,
        'name' => 'config_frequentflyer.xml',
        'hash' => '2E4928786AE6D6BA56BF4726E7BCC4B141F318E6',
    ],
    [
        'code' => 96387,
        'name' => 'config_heliosfury.xml',
        'hash' => 'C5A5F2A9A51558326959EF1779B6B553C204E164',
    ],
    [
        'code' => 96388,
        'name' => 'config_hellcatraz_94.xml',
        'hash' => 'A4C5501C1BF19EEAC1F24B65A510C06363F88D1F',
    ],
    [
        'code' => 96389,
        'name' => 'config_heroesgathering.xml',
        'hash' => '2547D3CBD405C46CD037D51CB44F1D6C44B04E6A',
    ],
    [
        'code' => 96390,
        'name' => 'config_hex.xml',
        'hash' => '6155919605AF1D2958B898DD796D930A68155E4B',
    ],
    [
        'code' => 96391,
        'name' => 'config_ignitethenight.xml',
        'hash' => 'C5866C71B281C38C37B8ED7F66BAACDA1B2DBE58',
    ],
    [
        'code' => 96392,
        'name' => 'config_ironbank_94.xml',
        'hash' => '8412F7280B730D3BBBED631A90FFB6DA35B5B9DE',
    ],
    [
        'code' => 96393,
        'name' => 'config_itstime.xml',
        'hash' => 'B7231252B87854969EC75FAE3E696827C000E3C9',
    ],
    [
        'code' => 96394,
        'name' => 'config_jurassicparty.xml',
        'hash' => '57A4D9A6C05844D4D03575124AD1FEC52530CE6E',
    ],
    [
        'code' => 96395,
        'name' => 'config_lafiesta.xml',
        'hash' => '06930FFAA57D0225B6E56695C76C57FD88C83D82',
    ],
    [
        'code' => 96397,
        'name' => 'config_rumble.xml',
        'hash' => 'DA9A3E787DCEC7386FB4F083E2D5374AEDCBCE54',
    ],
    [
        'code' => 96398,
        'name' => 'config_marchinglegions.xml',
        'hash' => '5495C7694004C1C982D604FF0085FDBE9E8F227A',
    ],
    [
        'code' => 96399,
        'name' => 'config_megaflip.xml',
        'hash' => 'D716611BFC51060F4186281CF3C76DA6681F1549',
    ],
    [
        'code' => 96401,
        'name' => 'config_megamine.xml',
        'hash' => 'BE94F49A4CE810FAEEFE673132BB7C0F89B64255',
    ],
    [
        'code' => 89542,
        'name' => 'config_midnightmarauder_94.xml',
        'hash' => '1B57E399409AAFDD12CC3C1866A053580C26D17F',
    ],
    [
        'code' => 96403,
        'name' => 'config_moneycart.xml',
        'hash' => '11B27F5725B4EDF5A95F6DC81833AA29CF3762EB',
    ],
    [
        'code' => 96404,
        'name' => 'config_moneycart2.xml',
        'hash' => '2B184967177C4E4D872AA75703E95FDEF1E53DCA',
    ],
    [
        'code' => 96405,
        'name' => 'config_moneytrain_94.xml',
        'hash' => 'AAD791F3D53C6C59405E6BAC6BEA7F9AB5157D68',
    ],
    [
        'code' => 96411,
        'name' => 'config_multiplierodyssey.xml',
        'hash' => 'F424EB67348A97C98C8926C75624F8E39CDAA0ED',
    ],
    [
        'code' => 96409,
        'name' => 'config_nekonight_94.xml',
        'hash' => '6F6B09D0E9233B2FA68E555E13A562F3130C4B1B',
    ],
    [
        'code' => 96413,
        'name' => 'config_plunderland.xml',
        'hash' => 'E730144C6BCB352FD20D841B5FDE819DB22F7A79',
    ],
    [
        'code' => 96415,
        'name' => 'config_roulettenouveau.xml',
        'hash' => '8E0C14F58B61AA9655D492106FA9BEB682FA030F',
    ],
    [
        'code' => 96416,
        'name' => 'config_sailsoffortune.xml',
        'hash' => 'DE9A8EF4078BF74058878921AA7B949C730B0956',
    ],
    [
        'code' => 96417,
        'name' => 'config_santasstack.xml',
        'hash' => '22B529CC858B5E92ED7090150F7031F3DEC3F29A',
    ],
    [
        'code' => 96419,
        'name' => 'config_spaceminers.xml',
        'hash' => '8A58345498394F6FED97AB6740315E7D75E4C471',
    ],
    [
        'code' => 96420,
        'name' => 'config_spiritofthebeast.xml',
        'hash' => 'A43819704B186E512CAF46813E098F1074D62866',
    ],
    [
        'code' => 96422,
        'name' => 'config_templartumble_94.xml',
        'hash' => '2D49FDEA7E0CB8A082D3E661796A40C92919F06A',
    ],
    [
        'code' => 89544,
        'name' => 'config_templetumble_94.xml',
        'hash' => '1C27EA46B7A0A11A92802A8B796F491D6AC336B7',
    ],
    [
        'code' => 96423,
        'name' => 'config_templetumble2.xml',
        'hash' => '9E546E1A547DBC7804ADFDE71EF0070EAAC03269',
    ],
    [
        'code' => 96425,
        'name' => 'config_thegreatpigsby.xml',
        'hash' => 'D291445701958D798D67708CFA405B3A38401C73',
    ],
    [
        'code' => 96426,
        'name' => 'config_tigerkingdom.xml',
        'hash' => 'D100519B6F956ECA2BF3D8198F57C63449473D85',
    ],
    [
        'code' => 96428,
        'name' => 'config_topdawg_94.xml',
        'hash' => 'C22DC4B5F298075B5766D1E3F71019B633FBE90B',
    ],
    [
        'code' => 96432,
        'name' => 'config_towertumble.xml',
        'hash' => '1E3DEFEAFB00E751AC1CDDE63CF09A0B3A2B0500',
    ],
    [
        'code' => 96433,
        'name' => 'config_trollsgold.xml',
        'hash' => '9486051000C4E6872289CB2F4DED32269759746D',
    ],
    [
        'code' => 96434,
        'name' => 'config_wildchapo.xml',
        'hash' => 'A1700B8AFE4992F007C49C2F330FC1AA8B052D27',
    ],
    [
        'code' => 96435,
        'name' => 'config_wildchemy.xml',
        'hash' => '0FBFA1517BAA0DFD22C9853384AF580B0B10B00B',
    ],
    [
        'code' => 96436,
        'name' => 'config_zombiecircus.xml',
        'hash' => '0ED66A204EF5D203FE9CCCBD0A952F43DD35134B',
    ],
    [
        'code' => 96386,
        'name' => 'config_hazakuraways.xml',
        'hash' => 'FEC9FC173E512220604D2940916B52CA339AB116',
    ],
    [
        'code' => 89541,
        'name' => 'config_klusterkrystalsmegaclusters.xml',
        'hash' => 'BB6AB13CD5F8E1FE19597690CB9CE39433DD3A60',
    ],
    [
        'code' => 96400,
        'name' => 'config_megamasks.xml',
        'hash' => 'D21C0881E34F501191104A34B5CF665DDEA76888',
    ],
    [
        'code' => 96414,
        'name' => 'config_ramsesrevenge.xml',
        'hash' => 'AECB8B341A3476E677DBD723B4268187B079D3BE',
    ],
    [
        'code' => 96418,
        'name' => 'config_snakearena.xml',
        'hash' => '16A95D02F23B7B8F934B3CFE345C97D774E6678E',
    ],
    [
        'code' => 101111,
        'name' => 'config_tnttumble.xml',
        'hash' => '59733FC34CD72A04332AD64332122CB9160FCD6A',
    ],
    [
        'code' => 89545,
        'name' => 'config_volatilevikings.xml',
        'hash' => '671AE4EDE692E170203962FB8B36231DF584BD2E',
    ],
    [
        'code' => 109893,
        'cert_ver' => 1,
        'name' => 'config_hotrodracers_94.xml',
        'hash' => '54BD836D5F1E797BF72F695C71231656EDEB682A',
    ],
    [
        'code' => 109894,
        'cert_ver' => 1,
        'name' => 'config_templartumble2_94.xml',
        'hash' => '5FA15AEBB2E960E7E8826E00679C2EF682B4FA66',
    ],
    [
        'code' => 109895,
        'cert_ver' => 1,
        'name' => 'config_greatpigsbymegaways_94.xml',
        'hash' => '6BB28E42A7341DD7F6494FD0A20F3F80B3D95CA9',
    ],
    [
        'code' => 109896,
        'cert_ver' => 1,
        'name' => 'config_volatilevikings2_94.xml',
        'hash' => 'B785C93CE1A55DABAC6C11D906210FC6E2C0A135',
    ],
    [
        'code' => 109897,
        'cert_ver' => 1,
        'name' => 'config_wildyield_94.xml',
        'hash' => '23D72FD7B0985044524A8E979961C6EFC54B269A',
    ],
    [
        'code' => 111714,
        'cert_ver' => 1,
        'name' => 'config_bookofpower_94.xml',
        'hash' => 'ABCBA3B8923F0EEA1FFCD641E8BF28B7D4FC00AE',
    ],
    [
        'code' => 111715,
        'cert_ver' => 1,
        'name' => 'config_grimthesplitter_94.xml',
        'hash' => 'BC2BDAF57545B0343B338B665967964BCAF9FB09',
    ],
    [
        'code' => 111716,
        'cert_ver' => 1,
        'name' => 'config_horrorhotel_94.xml',
        'hash' => '224DF00FFCC1C42550AE4B9FF8B80B094032FF3B',
    ],
    [
        'code' => 111717,
        'cert_ver' => 1,
        'name' => 'config_wildchapo2_94.xml',
        'hash' => 'C7140330352D8B5007E8AB209C57042945564963',
    ],
    [
        'code' => 111718,
        'cert_ver' => 1,
        'name' => 'config_wildhike_94.xml',
        'hash' => '24DC851BDD2820B9F50B283A89B3AB569A0A469C',
    ],
    [
        'code' => 111719,
        'cert_ver' => 1,
        'name' => 'config_netgains_94.xml',
        'hash' => '67DA921E165D1407DCB4C2A62BAEDCBBCFFD2F15',
    ],
    [
        'code' => 111720,
        'cert_ver' => 1,
        'name' => 'config_moneytrain3_94.xml',
        'hash' => '3F67BA1AF678E43903C64D75FD6963291DAE790E',
    ],
    [
        'code' => 135889,
        'cert_ver' => 1,
        'name' => 'config_beellionaires_94.xml',
        'hash' => '9044E18BA34637C327062FACAFCE6B03E03AE6F3',
    ],
    [
        'code' => 135890,
        'cert_ver' => 1,
        'name' => 'config_flycats_94.xml',
        'hash' => 'FAC90A48FE041F691601D6395E269CC3EA4C631F',
    ],
    [
        'code' => 135891,
        'cert_ver' => 1,
        'name' => 'config_hellcatraz2_94.xml',
        'hash' => '6D0F98FCC80B148841B8AF40B473C040B47BDF30',
    ],
    [
        'code' => 135892,
        'cert_ver' => 1,
        'name' => 'config_megaheist_94.xml',
        'hash' => '4BA62A1FAF8DD976AB249E9FB3ED226D2975A664',
    ],
    [
        'code' => 135893,
        'cert_ver' => 1,
        'name' => 'config_moneycart3_94.xml',
        'hash' => '7E4D3740DF86A8EBA870AC355AE79F34BF6FD64E',
    ],
    [
        'code' => 135894,
        'cert_ver' => 1,
        'name' => 'config_moneytrain4_94.xml',
        'hash' => 'C11ACA96DD1E925F154E076E60902277BB8DC219',
    ],
    [
        'code' => 135895,
        'cert_ver' => 1,
        'name' => 'config_moneytrainorigins_94.xml',
        'hash' => '07A4737A1B69C75141D81C1C2EDCFDE987DFA0C8',
    ],
    [
        'code' => 135896,
        'cert_ver' => 1,
        'name' => 'config_sharkwash_94.xml',
        'hash' => 'A33BABFD3786F5BA13C8C3B27BC051DF6DCDEADC',
    ],
    [
        'code' => 135897,
        'cert_ver' => 1,
        'name' => 'config_slothtumble_94.xml',
        'hash' => 'B5C41B3FE5B823EC5774FC147D95CB95EAD9651F',
    ],
    [
        'code' => 135898,
        'cert_ver' => 1,
        'name' => 'config_toriitumble_94.xml',
        'hash' => '58FD899701F278C5969B073FF6EB321F3BDB5F68',
    ], 
    [
        'code' => 166191,
        'cert_ver' => 1,
        'name' => 'config_ancienttumble_94.xml',
        'hash' => '672845C12ED31C9A57B6AF02C4B930366CD4283C',
    ], 
    [
        'code' => 166186,
        'cert_ver' => 1,
        'name' => 'config_billandcoin_94.xml',
        'hash' => 'D7BAC264CE8D6AB57F50740D031EF31DBE588468',
    ],
    [
        'code' => 166187,
        'cert_ver' => 1,
        'name' => 'config_firewinsfactory_94.xml',
        'hash' => 'E0EB647C6ECE345BF355C83CFF76254363E93943',
    ],
    [
        'code' => 166188,
        'cert_ver' => 1,
        'name' => 'config_sweetopiaroyale_94.xml',
        'hash' => '90FC97F703FDC1C4A9F16BD2979E0567B581FBC5',
    ],
    [
        'code' => 166189,
        'cert_ver' => 1,
        'name' => 'config_epicdreams_94.xml',
        'hash' => 'F8DDA822EA90BF7052FC6EE5EB87066688C283E2',
    ],
    [
        'code' => 166190,
        'cert_ver' => 1,
        'name' => 'config_sultanspins_94.xml',
        'hash' => 'B1229828658CF6ED0A79EF3D62DC81E489CD87DD',
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

