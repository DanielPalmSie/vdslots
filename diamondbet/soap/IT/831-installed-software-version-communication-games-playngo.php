<?php

require_once '/var/www/videoslots.it/phive/phive.php';
$user = cu($argv[1] ?? 6624618);

$games = [
    [
        'code' => 47887,
        'name' => 'PlaynGO.Modules.Games.Machine60.dll',
        'hash' => '59304fc77e9e7bcd04dca0f910559166e7745620'
    ],
    [
        'code' => 47872,
        'name' => 'PlaynGO.Modules.Games.Machine120.dll',
        'hash' => 'eef515ef78b4f050f49004f0752ce73591b9c8bc'
    ],
    [
        'code' => 47871,
        'name' => 'PlaynGO.Modules.Games.Machine14.dll',
        'hash' => '63853e490d1830a4ef23b25c5c7a0ab9b354de59'
    ],
    [
        'code' => 47870,
        'name' => 'PlaynGO.Modules.Games.Machine12.dll',
        'hash' => 'e034fe8912afc9d0f20a97469da3aed4b40b7d90'
    ],
    [
        'code' => 47868,
        'name' => 'PlaynGO.Modules.Games.Machine29.dll',
        'hash' => 'ec95f84a16b06ef3b1926b1ca7904366d7f0b715'
    ],
    [
        'code' => 47867,
        'name' => 'PlaynGO.Modules.Games.Machine58.dll',
        'hash' => 'ce8617748899910bb9fdb00abf964eef6945e59f'
    ],
    [
        'code' => 47866,
        'name' => 'PlaynGO.Modules.Games.Machine61.dll',
        'hash' => '7dff8204de8415dbdd6d9092696b0e7582c33973'
    ],
    [
        'code' => 47865,
        'cert_ver' => 2,
        'name' => 'PlaynGO.Modules.Games.Machine99.dll',
        'hash' => '23B7FE1A7A4B6EA43028AF760F4D0785ECDBDF43'
    ],
    [
        'code' => 47864,
        'name' => 'PlaynGO.Modules.Games.Machine13.dll',
        'hash' => '63b83b35d3fcfe6da0929fc81d46a4869242d5d5'
    ],
    [
        'code' => 47863,
        'name' => 'PlaynGO.Modules.Games.Machine57.dll',
        'hash' => 'f005b5062b9bf87510940722369548b0ffc71023'
    ],
    [
        'code' => 47862,
        'name' => 'PlaynGO.Modules.Games.Machine55.dll',
        'hash' => 'fa321b0ab84293285df6511305a8775a04b371cb'
    ],
    [
        'code' => 47860,
        'name' => 'PlaynGO.Modules.Games.Machine83.dll',
        'hash' => 'f0cda7baef1cce38fae426efff617da9d50203bd'
    ],
    [
        'code' => 47856,
        'name' => 'PlaynGO.Modules.Games.Machine125.dll',
        'hash' => '215796cd53a80215d98ca5e431da80b3134a2d0d'
    ],
    [
        'code' => 47852,
        'name' => 'PlaynGO.Modules.Games.Machine16.dll',
        'hash' => '677cad6a3ee6cba2a7fdfa0d83b11e4a0aa363b5'
    ],
    [
        'code' => 47851,
        'name' => 'PlaynGO.Modules.Games.Machine65.dll',
        'hash' => '876ae900680d2e31d464ae41a957123db0b4416d'
    ],
    [
        'code' => 47850,
        'name' => 'PlaynGO.Modules.Games.Machine97.dll',
        'hash' => '627a0945388b8b4e6a075b96ae23985fa8200392'
    ],
    [
        'code' => 47848,
        'name' => 'PlaynGO.Modules.Games.Machine13.dll',
        'hash' => '63b83b35d3fcfe6da0929fc81d46a4869242d5d5'
    ],
    [
        'code' => 47844,
        'name' => 'PlaynGO.Modules.Games.Machine56.dll',
        'hash' => '57efa26dec2a519518f6a956db42638d98bf7cdb'
    ],
    [
        'code' => 47839,
        'name' => 'PlaynGO.Modules.Games.Machine87.dll',
        'hash' => '2d818e25fe41d3bbb462ad9d2401872160eb3cb4'
    ],
    [
        'code' => 47838,
        'name' => 'PlaynGO.Modules.Games.Machine87.dll',
        'hash' => '2d818e25fe41d3bbb462ad9d2401872160eb3cb4'
    ],
    [
        'code' => 47836,
        'name' => 'PlaynGO.Modules.Games.Machine69.dll',
        'hash' => 'bf358e5f81a4f7968471465f97c40d36da16726b'
    ],
    [
        'code' => 47833,
        'name' => 'PlaynGO.Modules.Games.Machine110.dll',
        'hash' => '9a668389e197ee8a9916ea26f4c122c3a89e6a53'
    ],
    [
        'code' => 47832,
        'name' => 'PlaynGO.Modules.Games.Machine76.dll',
        'hash' => 'c4f38ab34f887f0a4f0c6861acf2893c1eef746e'
    ],
    [
        'code' => 47825,
        'name' => 'PlaynGO.Modules.Games.Machine71.dll',
        'hash' => 'f70af951c7f7d15d216ab49b0eea2e97e36af447'
    ],
    [
        'code' => 47818,
        'name' => 'PlaynGO.Modules.Games.Machine52.dll',
        'hash' => 'cc26d1b5e86f8c8fbaa414bc28da43a5d402c99f'
    ],
    [
        'code' => 47815,
        'name' => 'PlaynGO.Modules.Games.Machine72.dll',
        'hash' => 'a987da283ccf4ea11e5a560bb66c28fbce7bf17a'
    ],
    [
        'code' => 47811,
        'name' => 'PlaynGO.Modules.Games.Machine95.dll',
        'hash' => '59f469acabc24cd0860e5cdbce17d90afbca9abc'
    ],
    [
        'code' => 47801,
        'name' => 'PlaynGO.Modules.Games.Machine34.dll',
        'hash' => '5777bc5d45f17e94a357268487db6528f9c2dac0'
    ],
    [
        'code' => 47800,
        'name' => 'PlaynGO.Modules.Games.Machine35.dll',
        'hash' => '3e929448dc29e771a041a2c3da8ae080986b0397'
    ],
    [
        'code' => 47797,
        'name' => 'PlaynGO.Modules.Games.Machine48.dll',
        'hash' => 'a1c1c0854981669d97b4b27fe99ea21770c6f35a'
    ],
    [
        'code' => 47795,
        'name' => 'PlaynGO.Modules.Games.TrollHunters.dll',
        'hash' => 'ca97936f94bfc72f7e3f000329fbd9d271e9f6d8'
    ],
    [
        'code' => 47793,
        'name' => 'PlaynGO.Modules.Games.Machine35.dll',
        'hash' => '3e929448dc29e771a041a2c3da8ae080986b0397'
    ],
    [
        'code' => 47790,
        'name' => 'PlaynGO.Modules.Games.Machine36.dll',
        'hash' => '7a9d289abc1bfd41c42031b510d70fd12f0345e8'
    ],
    [
        'code' => 47788,
        'name' => 'PlaynGO.Modules.Games.Machine45.dll',
        'hash' => '1d339dd70edd4df9071a108f84b25884707190d0'
    ],
    [
        'code' => 47785,
        'name' => 'PlaynGO.Modules.Games.Machine12.dll',
        'hash' => 'e034fe8912afc9d0f20a97469da3aed4b40b7d90'
    ],
    [
        'code' => 47784,
        'name' => 'PlaynGO.Modules.Games.Machine66.dll',
        'hash' => '8a0e970c9deb58fe546b060bb97be652b2062854'
    ],
    [
        'code' => 47771,
        'name' => 'PlaynGO.Modules.Games.Machine33.dll',
        'hash' => 'edabbc16864b44611806785d01414864850faa39'
    ],
    [
        'code' => 47770,
        'name' => 'PlaynGO.Modules.Games.Machine89.dll',
        'hash' => 'ab86d3aded018129d014356fa6ce76309ef33505'
    ],
    [
        'code' => 47768,
        'name' => 'PlaynGO.Modules.Games.Machine39.dll',
        'hash' => 'cf549bde651931241628bd21159d6a692cf5cca3'
    ],
    [
        'code' => 47756,
        'name' => 'PlaynGO.Modules.Games.Machine127.dll',
        'hash' => 'e4682b0c97965103ce4e391dcf79c664a9edf30c'
    ],
    [
        'code' => 47968,
        'name' => 'PlaynGO.Modules.Games.Machine35.dll',
        'hash' => '3e929448dc29e771a041a2c3da8ae080986b0397'
    ],
    [
        'code' => 47967,
        'name' => 'PlaynGO.Modules.Games.Machine29.dll',
        'hash' => 'ec95f84a16b06ef3b1926b1ca7904366d7f0b715'
    ],
    [
        'code' => 47966,
        'name' => 'PlaynGO.Modules.Games.Machine40.dll',
        'hash' => 'f98ec644a0f5b33ace5cd0c71186d7a984c0f37a'
    ],
    [
        'code' => 47965,
        'name' => 'PlaynGO.Modules.Games.Machine111.dll',
        'hash' => '66398e99ff1a6dc7f582cdb54c9bba72dbae9165'
    ],
    [
        'code' => 47964,
        'name' => 'PlaynGO.Modules.Games.Machine104.dll',
        'hash' => '290d3ff61013ac9d9432c2b5db19141f58e2befa'
    ],
    [
        'code' => 47963,
        'name' => 'PlaynGO.Modules.Games.Machine36.dll',
        'hash' => '7a9d289abc1bfd41c42031b510d70fd12f0345e8'
    ],
    [
        'code' => 47923,
        'name' => 'PlaynGO.Modules.Games.Machine121.dll',
        'hash' => '981150820e49cca4c4e2a5f0a333e97956cade15'
    ],
    [
        'code' => 47921,
        'cert_ver' => 2,
        'name' => 'PlaynGO.Modules.Games.Machine5.dll',
        'hash' => '07B8F70F507CA130102406011E06D30A2E0956EB'
    ],
    [
        'code' => 47920,
        'name' => 'PlaynGO.Modules.Games.Machine54.dll',
        'hash' => '1767b45e248ad88a14e74c24757ac2cf780ad283'
    ],
    [
        'code' => 47918,
        'name' => 'PlaynGO.Modules.Games.Machine114.dll',
        'hash' => 'eb0ec35436ff9cbdd35bfdfd9dbc1ac6e41dd316'
    ],
    [
        'code' => 47916,
        'name' => 'PlaynGO.Modules.Games.Machine32.dll',
        'hash' => 'e92829d19e8eaa21823195344cc7fd9e9da48083'
    ],
    [
        'code' => 47915,
        'name' => 'PlaynGO.Modules.Games.Machine94.dll',
        'hash' => '9BDC0700DF945A781375DDED3C6E09CA86B1B2CC'
    ],
    [
        'code' => 47914,
        'name' => 'PlaynGO.Modules.Games.Machine96.dll',
        'hash' => 'f03ccb4416a37b2e3f7c72952b2637723ac669f3'
    ],
    [
        'code' => 47912,
        'name' => 'PlaynGO.Modules.Games.Machine123.dll',
        'hash' => 'd7188976d16fb07f78faf849dead8a269d18debf'
    ],
    [
        'code' => 47910,
        'name' => 'PlaynGO.Modules.Games.Machine63.dll',
        'hash' => 'ae706052837260d8bdd00771458bb3408345aeeb'
    ],
    [
        'code' => 47909,
        'name' => 'PlaynGO.Modules.Games.Machine62.dll',
        'hash' => '9187e5bbd891f6796316b8abed8b981cc6fffd4d'
    ],
    [
        'code' => 47907,
        'name' => 'PlaynGO.Modules.Games.Machine37.dll',
        'hash' => '7a50defa5cda9e27ef926dbf32d0336d439f4035'
    ],
    [
        'code' => 47906,
        'name' => 'PlaynGO.Modules.Games.Machine75.dll',
        'hash' => 'd465bd223a3f64acebab34af44969f23d7333f5f'
    ],
    [
        'code' => 47905,
        'name' => 'PlaynGO.Modules.Games.Machine129.dll',
        'hash' => '3111464905c2c4ccea8f42a816fe9cd2dc403da9'
    ],
    [
        'code' => 47904,
        'name' => 'PlaynGO.Modules.Games.Machine67.dll',
        'hash' => 'e43ad001990ed2f716ba1c6dd1e305dd62eab41a'
    ],
    [
        'code' => 47903,
        'name' => 'PlaynGO.Modules.Games.Machine39.dll',
        'hash' => 'cf549bde651931241628bd21159d6a692cf5cca3'
    ],
    [
        'code' => 47901,
        'name' => 'PlaynGO.Modules.Games.Machine9.dll',
        'hash' => 'e49a39ada7840bbc261050264a103d13c8995124'
    ],
    [
        'code' => 47900,
        'name' => 'PlaynGO.Modules.Games.Machine79.dll',
        'hash' => '181f9dbdd95ff93167d6ce624b970f3ad093ce22'
    ],
    [
        'code' => 47899,
        'name' => 'PlaynGO.Modules.Games.Machine59.dll',
        'hash' => '27fb4270c0ed599db016f304210c9294e0cd45ec'
    ],
    [
        'code' => 47898,
        'name' => 'PlaynGO.Modules.Games.Machine13.dll',
        'hash' => '63b83b35d3fcfe6da0929fc81d46a4869242d5d5'
    ],
    [
        'code' => 47897,
        'name' => 'PlaynGO.Modules.Games.Machine44.dll',
        'hash' => 'e88c5c57e668085ab4fa13f267bc9a9910bd4a14'
    ],
    [
        'code' => 47896,
        'name' => 'PlaynGO.Modules.Games.Machine98.dll',
        'hash' => 'bcfa64df4fee828c3729bb9aa3b8ef1247551ddc'
    ],
    [
        'code' => 47895,
        'name' => 'PlaynGO.Modules.Games.Machine34.dll',
        'hash' => '5777bc5d45f17e94a357268487db6528f9c2dac0'
    ],
    [
        'code' => 47894,
        'name' => 'PlaynGO.Modules.Games.Machine61.dll',
        'hash' => '7dff8204de8415dbdd6d9092696b0e7582c33973'
    ],
    [
        'code' => 47893,
        'name' => 'PlaynGO.Modules.Games.Machine97.dll',
        'hash' => '627a0945388b8b4e6a075b96ae23985fa8200392'
    ],
    [
        'code' => 47892,
        'name' => 'PlaynGO.Modules.Games.Machine66.dll',
        'hash' => '8a0e970c9deb58fe546b060bb97be652b2062854'
    ],
    [
        'code' => 47891,
        'name' => 'PlaynGO.Modules.Games.Machine128.dll',
        'hash' => '4ab5d1ab746d2c0ccbfd058c4bffeddc70ab376c'
    ],
    [
        'code' => 47890,
        'name' => 'PlaynGO.Modules.Games.Machine103.dll',
        'hash' => '1f89a489380d035e44b16cc601dc0aa07e8d4fe5'
    ],
    [
        'code' => 47889,
        'name' => 'PlaynGO.Modules.Games.Machine11.dll',
        'hash' => '01791adb53a106ba70e372e5e6743ad012fc9f7a'
    ],
    [
        'code' => 47888,
        'name' => 'PlaynGO.Modules.Games.Machine130.dll',
        'hash' => 'd0396318af7363a9b7f4989c55ffa9af0a6a8d51'
    ],
    [
        'code' => 47885,
        'name' => 'PlaynGO.Modules.Games.Machine107.dll',
        'hash' => '37aa5cbfe6a873d662b78369b3d2e1c09538b8de'
    ],
    [
        'code' => 47883,
        'name' => 'PlaynGO.Modules.Games.Machine17.dll',
        'hash' => 'd738c7ef3e03163058e58069ea61b33c4c40d867'
    ],
    [
        'code' => 47881,
        'name' => 'PlaynGO.Modules.Games.Machine67.dll',
        'hash' => 'e43ad001990ed2f716ba1c6dd1e305dd62eab41a'
    ],
    [
        'code' => 47880,
        'name' => 'PlaynGO.Modules.Games.Machine53.dll',
        'hash' => 'dd59f739abb4b07cfda628fa5c2077e683eb049e'
    ],
    [
        'code' => 47879,
        'name' => 'PlaynGO.Modules.Games.Machine70.dll',
        'hash' => '5508dfa316c59f591261c8e9201deb4e7084df6c'
    ],
    [
        'code' => 47878,
        'name' => 'PlaynGO.Modules.Games.Machine34.dll',
        'hash' => '5777bc5d45f17e94a357268487db6528f9c2dac0'
    ],
    [
        'code' => 47877,
        'name' => 'PlaynGO.Modules.Games.Machine9.dll',
        'hash' => 'e49a39ada7840bbc261050264a103d13c8995124'
    ],
    [
        'code' => 47876,
        'name' => 'PlaynGO.Modules.Games.Machine91.dll',
        'hash' => 'c833834c67d04cd831e111b73fad3eef86fc9824'
    ],
    [
        'code' => 47875,
        'name' => 'PlaynGO.Modules.Games.Machine93.dll',
        'hash' => '6d13a652f0dc9e9c9b9bc1019079b3a36584c2df'
    ],
    [
        'code' => 47874,
        'name' => 'PlaynGO.Modules.Games.Machine31.dll',
        'hash' => '2a647c44219d4bfaaa0a95ea54a8dbdafaaf8c48'
    ],
    [
        'code' => 47873,
        'name' => 'PlaynGO.Modules.Games.Machine12.dll',
        'hash' => 'e034fe8912afc9d0f20a97469da3aed4b40b7d90'
    ],
    [
        'code' => 47686,
        'name' => 'PlaynGO.Modules.Games.Machine51.dll',
        'hash' => 'fcba1a00c0337e173e3fd6532aa91d4874c3ef6b'
    ],
    [
        'code' => 47687,
        'name' => 'PlaynGO.Modules.Games.Machine122.dll',
        'hash' => '1333aeb0a7cf3712afd08a6c97c313b8fde0a078'
    ],
    [
        'code' => 47690,
        'name' => 'PlaynGO.Modules.Games.Machine9.dll',
        'hash' => 'e49a39ada7840bbc261050264a103d13c8995124'
    ],
    [
        'code' => 47695,
        'name' => 'PlaynGO.Modules.Games.Machine94.dll',
        'hash' => '9bdc0700df945a781375dded3c6e09ca86b1b2cc'
    ],
    [
        'code' => 47696,
        'name' => 'PlaynGO.Modules.Games.Machine41.dll',
        'hash' => 'f3ef77e9bce3768703a95cff44504128c05a66ff'
    ],
    [
        'code' => 47697,
        'name' => 'PlaynGO.Modules.Games.Machine105.dll',
        'hash' => '9077325be2e94b68bd7aeb73b02398594c17ef8c'
    ],
    [
        'code' => 47698,
        'name' => 'PlaynGO.Modules.Games.Machine43.dll',
        'hash' => '1977d8163a674b321749683d3afede7079858707'
    ],
    [
        'code' => 47701,
        'name' => 'PlaynGO.Modules.Games.Machine115.dll',
        'hash' => 'e5cef80f5781f98c8a64657137cbbdaa83c695fe'
    ],
    [
        'code' => 47703,
        'name' => 'PlaynGO.Modules.Games.CatsAndCash.dll',
        'hash' => 'cb887c6c5b87a564ad320b7db04eceed7d522a4d'
    ],
    [
        'code' => 47706,
        'name' => 'PlaynGO.Modules.Games.Machine140.dll',
        'hash' => 'ed507b4d4844c8c751b09e6653115cc5fe119bf2'
    ],
    [
        'code' => 47717,
        'name' => 'PlaynGO.Modules.Games.Machine14.dll',
        'hash' => '63853e490d1830a4ef23b25c5c7a0ab9b354de59'
    ],
    [
        'code' => 47718,
        'name' => 'PlaynGO.Modules.Games.Machine108.dll',
        'hash' => '31d738453715924e156b5cef075b2c3cb1e979cd'
    ],
    [
        'code' => 47719,
        'name' => 'PlaynGO.Modules.Games.CloudQuest.dll',
        'hash' => 'f2289989c047e074c11b0fdab0a2eb2d19c29e6d'
    ],
    [
        'code' => 47720,
        'name' => 'PlaynGO.Modules.Games.Machine80.dll',
        'hash' => '0f99278352b772ca8d10c45ea8aa74e087e602e7'
    ],
    [
        'code' => 47721,
        'name' => 'PlaynGO.Modules.Games.CopsAndRobbers.dll',
        'hash' => '7ebd8e17714c877ad576de3f0c953c57af7b5f96'
    ],
    [
        'code' => 47723,
        'cert_ver' => 2,
        'name' => 'PlaynGO.Modules.Games.Machine5.dll',
        'hash' => '07B8F70F507CA130102406011E06D30A2E0956EB'
    ],
    [
        'code' => 47724,
        'name' => 'PlaynGO.Modules.Games.Machine92.dll',
        'hash' => '57a0970664a1c7809f209296bef5543fe437cd84'
    ],
    [
        'code' => 47733,
        'name' => 'PlaynGO.Modules.Games.Machine112.dll',
        'hash' => '74c88e5a88ddae19d38fb2b03df0481067a198e8'
    ],
    [
        'code' => 47735,
        'name' => 'PlaynGO.Modules.Games.Machine43.dll',
        'hash' => '1977d8163a674b321749683d3afede7079858707'
    ],
    [
        'code' => 47737,
        'name' => 'PlaynGO.Modules.Games.Machine11.dll',
        'hash' => '01791adb53a106ba70e372e5e6743ad012fc9f7a'
    ],
    [
        'code' => 47738,
        'name' => 'PlaynGO.Modules.Games.Machine16.dll',
        'hash' => '8DC5289C32843A13E26B36319438A222EA53ABA7'
    ],
    [
        'code' => 47739,
        'name' => 'PlaynGO.Modules.Games.Machine30.dll',
        'hash' => '93bf38dd2d1ce9ac41aab65e62fd75d52a9957d9'
    ],
    [
        'code' => 47740,
        'cert_ver' => 2,
        'name' => 'PlaynGO.Modules.Games.Machine5.dll',
        'hash' => '07B8F70F507CA130102406011E06D30A2E0956EB'
    ],
    [
        'code' => 47741,
        'name' => 'PlaynGO.Modules.Games.TrollHunters.dll',
        'hash' => 'ca97936f94bfc72f7e3f000329fbd9d271e9f6d8'
    ],
    [
        'code' => 47743,
        'name' => 'PlaynGO.Modules.Games.Machine38.dll',
        'hash' => '252e09f556a68c7b72efb3fe342349bfd7f023eb'
    ],
    [
        'code' => 47745,
        'name' => 'PlaynGO.Modules.Games.Machine41.dll',
        'hash' => 'f3ef77e9bce3768703a95cff44504128c05a66ff'
    ],
    [
        'code' => 47747,
        'name' => 'PlaynGO.Modules.Games.Machine85.dll',
        'hash' => 'd5799f88052a322936eb0892e6eef46f63b53b35'
    ],
    [
        'code' => 47754,
        'name' => 'PlaynGO.Modules.Games.Machine9.dll',
        'hash' => 'e49a39ada7840bbc261050264a103d13c8995124'
    ],
    [
        'code' => 45023,
        'name' => 'PlaynGO.Modules.Games.Machine14.dll',
        'hash' => '63853e490d1830a4ef23b25c5c7a0ab9b354de59'
    ],
    [
        'code' => 47685,
        'name' => 'PlaynGO.Modules.Games.5xMagic.dll',
        'hash' => 'd27c09c6ae390a8c60fe0273fef757ec64bea8ed'
    ],
    [
        'code' => 47688,
        'name' => 'PlaynGO.Modules.Games.Machine106.dll',
        'hash' => 'BB8564A53A28C314D95D8F103F5A4F257A2CE73C'
    ],
    [
        'code' => 47691,
        'name' => 'PlaynGO.Modules.Games.Machine73.dll',
        'hash' => 'a914bd2fb8e11a0e8b711dc88249d4702ba66e06'
    ],
    [
        'code' => 47692,
        'name' => 'PlaynGO.Modules.Games.Machine68.dll',
        'hash' => 'fccc2eb2619363d33968436986b8f106c53049d6'
    ],
    [
        'code' => 47693,
        'name' => 'PlaynGO.Modules.Games.Machine78.dll',
        'hash' => '955829937b122a53c09c625cd401130b3b5f8d5e'
    ],
    [
        'code' => 47702,
        'name' => 'PlaynGO.Modules.Games.Machine90.dll',
        'hash' => '936be6ec4c72f29a308a9029dd2b815441801480'
    ],
    [
        'code' => 47722,
        'name' => 'PlaynGO.Modules.Games.Machine113.dll',
        'hash' => 'b397f2797b708f8ed8e1eea42299ec8574386f8c'
    ],
    [
        'code' => 47732,
        'name' => 'PlaynGO.Modules.Games.Machine124.dll',
        'hash' => '0f88becdf1914b16d076724ab30d194d67cbfcbd'
    ],
    [
        'code' => 47734,
        'name' => 'PlaynGO.Modules.Games.Machine81.dll',
        'hash' => '5787a03b40ff3880afac44f66a69ccd70a701270'
    ],
    [
        'code' => 47841,
        'name' => 'PlaynGO.Modules.Games.5xMagic.dll',
        'hash' => 'd27c09c6ae390a8c60fe0273fef757ec64bea8ed'
    ],
    [
        'code' => 47858,
        'name' => 'PlaynGO.Modules.Games.Slots.dll',
        'hash' => '839c54a9127a9d28c60af0096d186fbe6fb5e2b5'
    ],
    [
        'code' => 47869,
        'name' => 'PlaynGO.Modules.Games.Machine64.dll',
        'hash' => '52bc3dd5bf3bc61d2156c21f1ecbca3e03561d0a'
    ],
    [
        'code' => 47886,
        'name' => 'PlaynGO.Modules.Games.Machine135.dll',
        'hash' => '69091020bbe810efda18b1369c953b34850af607'
    ],
    [
        'code' => 47913,
        'name' => 'PlaynGO.Modules.Games.Machine131.dll',
        'hash' => 'a80c11af99ada6d2804db3a8d02c5a9dbf94b76f'
    ],
    [
        'code' => 47917,
        'name' => 'PlaynGO.Modules.Games.TrollHunters.dll',
        'hash' => 'ca97936f94bfc72f7e3f000329fbd9d271e9f6d8'
    ],
    [
        'code' => 47961,
        'name' => 'PlaynGO.Modules.Games.Machine116.dll',
        'hash' => 'd7d2cc0c5b16c8448aa45feff9f07f3731a4d958'
    ],
    [
        'code' => 47962,
        'name' => 'PlaynGO.Modules.Games.Slots.dll',
        'hash' => '839c54a9127a9d28c60af0096d186fbe6fb5e2b5'
    ],
    [
        'code' => 57914,
        'name' => 'PlaynGO.Modules.Games.Machine163.dll',
        'hash' => '3ec302ca08769a1f67d335b0161a3e84ae337103',
    ],
    [
        'code' => 71882,
        'name' => 'PlaynGO.Modules.Games.Machine205.dll',
        'hash' => '7eb19e7a808c07d368e411f1592d1a99746cba69',
    ],
    [
        'code' => 70379,
        'name' => 'PlaynGO.Modules.Games.Machine197.dll',
        'hash' => '2ca005b4c8381c047634047835f359f0e4d9e176',
    ],
    [
        'code' => 60542,
        'name' => 'PlaynGO.Modules.Games.Machine172.dll',
        'hash' => '951950e08c6f6a2db54426ab53497fe14a8a0ebd',
    ],
    [
        'code' => 71881,
        'name' => 'PlaynGO.Modules.Games.Machine200.dll',
        'hash' => 'f0bc586e4766a2013db91fe13a0d571bcff70bd1',
    ],
    [
        'code' => 71883,
        'name' => 'PlaynGO.Modules.Games.Machine198.dll',
        'hash' => '109db3d44d9a8fe4817197245809bcf48c351318',
    ],
    [
        'code' => 60540,
        'name' => 'PlaynGO.Modules.Games.Machine165.dll',
        'hash' => '497536c23aa4063d82fe540aa6632c0830c43baf',
    ],
    [
        'code' => 71884,
        'name' => 'PlaynGO.Modules.Games.Machine199.dll',
        'hash' => '142b43eb6ddda7d5f5d80f4a11a08cff753ff6e1',
    ],
    [
        'code' => 60544,
        'name' => 'PlaynGO.Modules.Games.Machine173.dll',
        'hash' => '64d87775d64353bd59e918042afd130c25100e9a',
    ],
    [
        'code' => 64835,
        'name' => 'PlaynGO.Modules.Games.Machine185.dll',
        'hash' => 'ec601f56cd941c6e93fd36549288f981e71f78f1',
    ],
    [
        'code' => 71885,
        'name' => 'PlaynGO.Modules.Games.Machine203.dll',
        'hash' => 'a76eb623396a4b86649b862e29487b77c6afb646',
    ],
    [
        'code' => 57911,
        'name' => 'PlaynGO.Modules.Games.Machine166.dll',
        'hash' => '64af4f0c274d101dd63717965d5caff7726bcc06',
    ],
    [
        'code' => 64833,
        'name' => 'PlaynGO.Modules.Games.Machine181.dll',
        'hash' => 'a8e809632b603d95aa9cb7e706fcdb4efea44b25',
    ],
    [
        'code' => 64830,
        'name' => 'PlaynGO.Modules.Games.Machine182.dll',
        'hash' => '4fd4ac97fab06afc9867720ab63b23d219ee2c9b',
    ],
    [
        'code' => 71886,
        'name' => 'PlaynGO.Modules.Games.Machine192.dll',
        'hash' => '568d7542d6321b76372bf3a9c4dcb1283eaeb019',
    ],
    [
        'code' => 60549,
        'name' => 'PlaynGO.Modules.Games.Machine180.dll',
        'hash' => 'e652152775835b68e9e3dc9799613d3d88f15ac2',
    ],
    [
        'code' => 71480,
        'cert_ver' => 2,
        'name' => 'PlaynGO.Modules.Games.Machine209.dll',
        'hash' => '2500992C1CD5079AC992084E75B33201390DDB78',
    ],
    [
        'code' => 57910,
        'name' => 'PlaynGO.Modules.Games.Machine167.dll',
        'hash' => '036B55D31A61FAF99883D370466C36B388B63E67',
    ],
    [
        'code' => 60543,
        'name' => 'PlaynGO.Modules.Games.Machine187.dll',
        'hash' => 'd62fb0c51980aceb01768ae9865b8ce4202b7611',
    ],
    [
        'code' => 60541,
        'name' => 'PlaynGO.Modules.Games.Machine174.dll',
        'hash' => '74021c56bf600a4b5b2ec10c74be6e2969d9721d',
    ],
    [
        'code' => 57915,
        'name' => 'PlaynGO.Modules.Games.Machine170.dll',
        'hash' => '79e822115c686f3d138803cb604863bac4d4b0bb',
    ],
    [
        'code' => 71887,
        'name' => 'PlaynGO.Modules.Games.Machine201.dll',
        'hash' => '0273c17e2b734495e55be026f03a97f78dfea146',
    ],
    [
        'code' => 71888,
        'name' => 'PlaynGO.Modules.Games.Machine191.dll',
        'hash' => '746aaaf811acd647c5edb0aa3f9e42a77c73681f',
    ],
    [
        'code' => 57909,
        'name' => 'PlaynGO.Modules.Games.Machine171.dll',
        'hash' => '58bf5a0107dd194cb9cb07050bdfccd4f14d1b6e',
    ],
    [
        'code' => 71889,
        'name' => 'PlaynGO.Modules.Games.Machine196.dll',
        'hash' => 'e251e956fa6595eafa5896e9ee4bbe7a86753d2c',
    ],
    [
        'code' => 57913,
        'name' => 'PlaynGO.Modules.Games.Machine164.dll',
        'hash' => '70f2603e2c760022acc0db4209507bdac3c683b5',
    ],
    [
        'code' => 64822,
        'name' => 'PlaynGO.Modules.Games.Machine194.dll',
        'hash' => 'f00db1357a9f550cb6fe3235b3b7cffc1b120e0d',
    ],
    [
        'code' => 71890,
        'name' => 'PlaynGO.Modules.Games.Machine208.dll',
        'hash' => '747cdbfb45f0bec2c3b41ad594ca21b4e67c3c9e',
    ],
    [
        'code' => 60547,
        'name' => 'PlaynGO.Modules.Games.Machine175.dll',
        'hash' => '80ac97f4f1d828eb5fea780c356e7d5b4719302a',
    ],
    [
        'code' => 60548,
        'name' => 'PlaynGO.Modules.Games.Machine183.dll',
        'hash' => '9eeb3dba7699b1c0165fe2e6f58e680814cc108a',
    ],
    [
        'code' => 60546,
        'name' => 'PlaynGO.Modules.Games.Machine178.dll',
        'hash' => '5c9f144d4f83d4b13974b8cb893d21326c39389d',
    ],
    [
        'code' => 60545,
        'name' => 'PlaynGO.Modules.Games.Machine176.dll',
        'hash' => 'c51887b824564e7289b09e3db365bd646c43046f',
    ],
    [
        'code' => 71891,
        'name' => 'PlaynGO.Modules.Games.Machine204.dll',
        'hash' => '87e8e02efbc4c2369b77819babb8c5348f003d01',
    ],
    [
        'code' => 78950,
        'name' => 'PlaynGO.Modules.Games.Machine117.dll',
        'hash' => 'd5d8ec953f20d81492cf243db77c9a765b685f14',
    ],
    [
        'code' => 78951,
        'name' => 'PlaynGO.Modules.Games.Machine207.dll',
        'hash' => 'd56990c99784020e3f0f658db9966d5c261977c6',
    ],
    [
        'code' => 78953,
        'name' => 'PlaynGO.Modules.Games.Machine221.dll',
        'hash' => '1c0f4bc631af3944140bd3eb0ac93298c71a5d86',
    ],
    [
        'code' => 78954,
        'name' => 'PlaynGO.Modules.Games.Machine216.dll',
        'hash' => 'c37cc66186c9850adfc6f57ded6561f70b5bc2af',
    ],
    [
        'code' => 78955,
        'name' => 'PlaynGO.Modules.Games.Machine208.dll',
        'hash' => '747cdbfb45f0bec2c3b41ad594ca21b4e67c3c9e',
    ],
    [
        'code' => 78956,
        'name' => 'PlaynGO.Modules.Games.Machine219.dll',
        'hash' => '96ac9bb76f797a9a7ee80e46e0eb94a3f9f4ec00',
    ],
    [
        'code' => 78957,
        'name' => 'PlaynGO.Modules.Games.Machine223.dll',
        'hash' => '7c307e9ce3815c70972406aa5f91f8f9ef56c7bc',
    ],
    [
        'code' => 78958,
        'name' => 'PlaynGO.Modules.Games.Machine227.dll',
        'hash' => 'e8e23296b7b4b955f096c3bc3ba9924e84e90995',
    ],
    [
        'code' => 78959,
        'name' => 'PlaynGO.Modules.Games.Machine212.dll',
        'hash' => 'b6b9ce10d6bc7fc91895b654522c94d0d2cd40a3',
    ],
    [
        'code' => 77670,
        'name' => 'PlaynGO.Modules.Games.Machine215.dll',
        'hash' => '7d601bfe54a8322e1c15d0f13656cb9a892a2556',
    ],
    [
        'code' => 77672,
        'name' => 'PlaynGO.Modules.Games.Machine224.dll',
        'hash' => '72768365bd61939332b98c3ebc7c7d4f12d7963e',
    ],
    [
        'code' => 77674,
        'name' => 'PlaynGO.Modules.Games.Machine214.dll',
        'hash' => '675ac14eed1387c772d4214c50bbfacc7480ae5d',
    ],
    [
        'code' => 77676,
        'name' => 'PlaynGO.Modules.Games.Machine226.dll',
        'hash' => 'adbd8da40608b76822822882ae0e0052f835d3f7',
    ],
    [
        'code' => 64800,
        'name' => 'PlaynGO.Modules.Games.Machine186.dll',
        'hash' => '5ee80ad063e7269f3c3647f9d835fb941659a34a',
    ],
    [
        'code' => 64849,
        'name' => 'PlaynGO.Modules.Games.Machine126.dll',
        'hash' => '409ad256e1874c5ebb4651ecdd96692e72bbef7e',
    ],
    [
        'code' => 64845,
        'name' => 'PlaynGO.Modules.Games.Slots.dll',
        'hash' => '839c54a9127a9d28c60af0096d186fbe6fb5e2b5',
    ],
    [
        'code' => 47689,
        'name' => 'PlaynGO.Modules.Games.Machine133.dll',
        'hash' => 'c6bcd157b7893c4d9d1a3952611c1544dc420278',
    ],
    [
        'code' => 64847,
        'name' => 'PlaynGO.Modules.Games.Machine157.dll',
        'hash' => '890449ae99c0bfcb76d6a6fdfcb48d3b47fe6508',
    ],
    [
        'code' => 54894,
        'name' => 'PlaynGO.Modules.Games.Machine160.dll',
        'hash' => '510ff08a39846838618d047391a0ba3a95b7101c',
    ],
    [
        'code' => 64843,
        'name' => 'PlaynGO.Modules.Games.Machine142.dll',
        'hash' => 'dacb285414cee72dab2b829baf6aa34731a421c6',
    ],
    [
        'code' => 64841,
        'name' => 'PlaynGO.Modules.Games.Machine169.dll',
        'hash' => '3eb3d3c0d840fed25a184ab0f5514788a1e6a6b8',
    ],
    [
        'code' => 64839,
        'name' => 'PlaynGO.Modules.Games.Machine134.dll',
        'hash' => 'c239baeeba01dd901bd86ea0ec6efbe7be379529',
    ],
    [
        'code' => 54891,
        'name' => 'PlaynGO.Modules.Games.Machine143.dll',
        'hash' => '845457a6a78c18be68c0fe793d86093dbff56bf9',
    ],
    [
        'code' => 54912,
        'name' => 'PlaynGO.Modules.Games.Machine162.dll',
        'hash' => 'ee1c0c2d13e01404254de96803822933b23e46fa',
    ],
    [
        'code' => 47736,
        'name' => 'PlaynGO.Modules.Games.Machine77.dll',
        'hash' => '04d732a32232d0ee8a8269fca4c00fc35acf2eff',
    ],
    [
        'code' => 64834,
        'name' => 'PlaynGO.Modules.Games.Roulette.dll',
        'hash' => 'ef57e4cfae4238c2497ae5f2f2b7c259378115e8',
    ],
    [
        'code' => 54909,
        'name' => 'PlaynGO.Modules.Games.Machine132.dll',
        'hash' => '5b94999d0aff370db3418daf1c100a9181c478ad',
    ],
    [
        'code' => 62601,
        'name' => 'PlaynGO.Modules.Games.Machine179.dll',
        'hash' => '01cc6acf827f48d4810cf960b7b66a6caf794306',
    ],
    [
        'code' => 54886,
        'name' => 'PlaynGO.Modules.Games.Machine139.dll',
        'hash' => 'f6a9fdcab876609d883dc751fbe7025a63afa9fb',
    ],
    [
        'code' => 64831,
        'name' => 'PlaynGO.Modules.Games.Machine161.dll',
        'hash' => '563d5f2bf075796ab4f2899372625fb592172e51',
    ],
    [
        'code' => 54911,
        'name' => 'PlaynGO.Modules.Games.Machine114.dll',
        'hash' => 'eb0ec35436ff9cbdd35bfdfd9dbc1ac6e41dd316',
    ],
    [
        'code' => 54918,
        'name' => 'PlaynGO.Modules.Games.Machine138.dll',
        'hash' => 'ad544910a1efbf4e9dc5e5833d911cfbc79ab616',
    ],
    [
        'code' => 54921,
        'name' => 'PlaynGO.Modules.Games.Machine177.dll',
        'hash' => 'cc454c3f84d966160ad576dba263aeb5e0b17a07',
    ],
    [
        'code' => 47843,
        'name' => 'PlaynGO.Modules.Games.Machine42.dll',
        'hash' => '449b4a38aaee8cf6bf92abe406b715bea09dded8',
    ],
    [
        'code' => 47847,
        'name' => 'PlaynGO.Modules.Games.JollyRoger.dll',
        'hash' => '510b3da8799ea0a4e607454ba870f00f2f78a0a9',
    ],
    [
        'code' => 54889,
        'name' => 'PlaynGO.Modules.Games.Machine156.dll',
        'hash' => '429650e812b12cb07509ed8227b008411dc740a3',
    ],
    [
        'code' => 54916,
        'name' => 'PlaynGO.Modules.Games.Machine150.dll',
        'hash' => 'd058655c4a5f9e01e2ea998dc5ab85c13bb761a3',
    ],
    [
        'code' => 64828,
        'name' => 'PlaynGO.Modules.Games.Machine155.dll',
        'hash' => 'a7ef12693978d71351785b0143859662d2e2f20a',
    ],
    [
        'code' => 64825,
        'name' => 'PlaynGO.Modules.Games.Machine84.dll',
        'hash' => '82164636bfa06314e235511ea86d3b115adeb066',
    ],
    [
        'code' => 54919,
        'name' => 'PlaynGO.Modules.Games.Machine159.dll',
        'hash' => '5e5fa8d3f55beae99b1c7872d961d8ff09205888',
    ],
    [
        'code' => 47884,
        'name' => 'PlaynGO.Modules.Games.Machine82.dll',
        'hash' => 'a6660fc9cb44a470e0e456e45e32e56bccaf6951',
    ],
    [
        'code' => 54920,
        'name' => 'PlaynGO.Modules.Games.Machine144.dll',
        'hash' => '1cee859f53cac643dce3fa8e8b22324cebab2a9c',
    ],
    [
        'code' => 64823,
        'name' => 'PlaynGO.Modules.Games.Machine190.dll',
        'hash' => '799aa7c44ad2d035739275e23cc8f14bc98fb3a0',
    ],
    [
        'code' => 54884,
        'name' => 'PlaynGO.Modules.Games.Machine145.dll',
        'hash' => '3403d95b79ad200ea1cdfa6882d8c15d1650f075',
    ],
    [
        'code' => 54892,
        'name' => 'PlaynGO.Modules.Games.Machine137.dll',
        'hash' => '1057763148144a0e19cd4062f0b0ea0773083036',
    ],
    [
        'code' => 64819,
        'name' => 'PlaynGO.Modules.Games.Machine184.dll',
        'hash' => 'bf07ef7de03c391b543181aed21b7ac5d8cc9a2d',
    ],
    [
        'code' => 47919,
        'name' => 'PlaynGO.Modules.Games.Machine136.dll',
        'hash' => 'c49c64b5035170f5b52739e73d99022d60cf06a8',
    ],
    [
        'code' => 47924,
        'name' => 'PlaynGO.Modules.Games.Machine74.dll',
        'hash' => '07697b4d57577cf1b699ff13d32c5c17ddedc64b',
    ],
    [
        'code' => 54887,
        'name' => 'PlaynGO.Modules.Games.Machine141.dll',
        'hash' => 'c3e17da53adebf905f48e2360c6caf6cac6eb4a0',
    ],
    [
        'code' => 81190,
        'name' => 'PlaynGO.Modules.Games.Machine218.dll',
        'hash' => 'decc4a6b10d85f900477d9cafb2c9f37b811e651',
    ],
    [
        'code' => 81192,
        'name' => 'PlaynGO.Modules.Games.Machine222.dll',
        'hash' => 'ecfe66e600c564838be926c57cca9a53c153553e',
    ],
    [
        'code' => 81191,
        'name' => 'PlaynGO.Modules.Games.Machine244.dll',
        'hash' => '5e9eb654b190849bd4492a108c72b3ba4334a7a4',
    ],
    [
        'code' => 81189,
        'name' => 'PlaynGO.Modules.Games.Machine211.dll',
        'hash' => '346085465c1a50ace450a946525b636e1d319870',
    ],
    [
        'code' => 93379,
        'name' => 'PlaynGO.Modules.Games.Machine250.dll',
        'hash' => '42BC417F97FFFD7FAE961C7E6013A383FFD66B6B',
    ],
    [
        'code' => 94979,
        'name' => 'PlaynGO.Modules.Games.Machine206.dll',
        'hash' => '44F55585C2E6DF3A7BA87C0ACFF7B2147592E1F6',
    ],
    [
        'code' => 94980,
        'name' => 'PlaynGO.Modules.Games.Machine245.dll',
        'hash' => 'F5465A452483322F04F292E36812AC0ADE5AF63F',
    ],
    [
        'code' => 94981,
        'name' => 'PlaynGO.Modules.Games.Machine188.dll',
        'hash' => '81B8360DD6E16632CC285299530A277B37461404',
    ],
    [
        'code' => 94983,
        'name' => 'PlaynGO.Modules.Games.Machine263.dll',
        'hash' => '4A626FF99AA933D69926D49E4304C1D5F6AB86F1',
    ],
    [
        'code' => 94984,
        'name' => 'PlaynGO.Modules.Games.Machine247.dll',
        'hash' => 'AD0D46970998D86F2DDA1AFD59A885EBEB35EC8B',
    ],
    [
        'code' => 94985,
        'name' => 'PlaynGO.Modules.Games.Machine243.dll',
        'hash' => 'B82219008AD5F612D71DE9DF92C99EDD3BC7AAC6',
    ],
    [
        'code' => 94986,
        'name' => 'PlaynGO.Modules.Games.Machine210.dll',
        'hash' => '6333FA742F942C68656FB0C4EC12FB8F996308F6',
    ],
    [
        'code' => 94987,
        'name' => 'PlaynGO.Modules.Games.Machine261.dll',
        'hash' => '324AF2752C9D4DC0D2283B0D581E637EDD455E38',
    ],
    [
        'code' => 94990,
        'name' => 'PlaynGO.Modules.Games.Machine258.dll',
        'hash' => 'B83B78634902CDED30A7F7E38C72B71EEF31388B',
    ],
    [
        'code' => 94992,
        'name' => 'PlaynGO.Modules.Games.Machine239.dll',
        'hash' => '79BEB860C7C2284A841A337ED6E41FEF78306FDE',
    ],
    [
        'code' => 94993,
        'name' => 'PlaynGO.Modules.Games.Machine252.dll',
        'hash' => 'B5CAA29E6808DB08283C10CCB1EC292C511698D2',
    ],
    [
        'code' => 94994,
        'name' => 'PlaynGO.Modules.Games.Machine241.dll',
        'hash' => '52D66FF8D0F278E46B6096717BDB58FEDA901522',
    ],
    [
        'code' => 94996,
        'name' => 'PlaynGO.Modules.Games.Machine228.dll',
        'hash' => '91D71AA0538D13CBC6A0B8A7E683B207A1E36CA3',
    ],
    [
        'code' => 95030,
        'name' => 'PlaynGO.Modules.Games.Machine257.dll',
        'hash' => '43E58F496C3B06F571992BD63D8FDBC8BF353B52',
    ],
    [
        'code' => 95017,
        'name' => 'PlaynGO.Modules.Games.Machine260.dll',
        'hash' => 'D7D7676E52C3209F3205595E51E0CD12909250BA',
    ],
    [
        'code' => 95020,
        'name' => 'PlaynGO.Modules.Games.Machine240.dll',
        'hash' => 'DEF95257EC2B5135BF2F59F6A185E2EDAC689D52',
    ],
    [
        'code' => 95025,
        'name' => 'PlaynGO.Modules.Games.Machine238.dll',
        'hash' => '779F99B61BC072A48126137077126F4C2565EDD3',
    ],
    [
        'code' => 95026,
        'name' => 'PlaynGO.Modules.Games.Machine213.dll',
        'hash' => '09E69ECC5D8BDF87BE16D3755AE7D9860550A84A',
    ],
    [
        'code' => 95029,
        'name' => 'PlaynGO.Modules.Games.Machine249.dll',
        'hash' => 'B0371A4A244248ACB56340B893692000701B7A84',
    ],
    [
        'code' => 64848,
        'software_modules' => [
            [
                'name' => 'PlaynGO.Firefly.GameHelpers.dll',
                'hash' => '51ac8c00ed2cd193955bd10de0e3bffca870214f',
            ],
            [
                'name' => 'PlaynGO.Modules.Games.Blackjack.dll',
                'hash' => 'B10FBABD2B90DFA7968C90A1577AAFC53F849BA4',
            ]
        ]
    ],
    [
        'code' => 64844,
        'software_modules' => [
            [
                'name' => 'PlaynGO.Firefly.GameHelpers.dll',
                'hash' => '51ac8c00ed2cd193955bd10de0e3bffca870214f',
            ],
            [

                'name' => 'PlaynGO.Modules.Games.CasinoHoldem.dll',
                'hash' => '9f170926b574f07df06a5881d85a806a46721291',
            ]
        ]
    ],
    [
        'code' => 64838,
        'software_modules' => [
            [
                'name' => 'PlaynGO.Firefly.GameHelpers.dll',
                'hash' => '51AC8C00ED2CD193955BD10DE0E3BFFCA870214F',
            ],
            [
                'name' => 'PlaynGO.Modules.Games.Blackjack.dll',
                'hash' => 'B10FBABD2B90DFA7968C90A1577AAFC53F849BA4',
            ]
        ]
    ],
    [
        'code' => 64837,
        'software_modules' => [
            [
                'name' => 'PlaynGO.Firefly.GameHelpers.dll',
                'hash' => '51AC8C00ED2CD193955BD10DE0E3BFFCA870214F',
            ],
            [
                'name' => 'PlaynGO.Modules.Games.Blackjack.dll',
                'hash' => 'B10FBABD2B90DFA7968C90A1577AAFC53F849BA4',
            ]
        ]
    ],
    [
        'code' => 47845,
        'software_modules' => [
            [
                'name' => 'PlaynGO.Firefly.GameHelpers.dll',
                'hash' => '51ac8c00ed2cd193955bd10de0e3bffca870214f',
            ],
            [
                'name' => 'PlaynGO.Modules.Games.Machine13.dll',
                'hash' => '63b83b35d3fcfe6da0929fc81d46a4869242d5d5',
            ]
        ]
    ],
    [
        'code' => 94982,
        'name' => 'PlaynGO.Modules.Games.Machine202.dll',
        'hash' => '78B85537C756B3A398A5D0390F91059A74BB5C59',
    ],
    [
        'code' => 95344,
        'name' => 'PlaynGO.Modules.Games.Machine255.dll',
        'hash' => 'EBED92B3308F1E08F951D55771170959DBAF111E',
    ],
    [
        'code' => 94988,
        'name' => 'PlaynGO.Modules.Games.Machine264.dll',
        'hash' => '4382FDCF08BE244643A3418F34D22031646B185B',
    ],
    [
        'code' => 94989,
        'name' => 'PlaynGO.Modules.Games.Machine242.dll',
        'hash' => '54DA696CE1197869238D4100FDFB7F6D3D0FD48D',
    ],
    [
        'code' => 94995,
        'name' => 'PlaynGO.Modules.Games.Machine225.dll',
        'hash' => '3332DA5811167C73D9D658C75E54B36F7CBA1BD9',
    ],
    [
        'code' => 95342,
        'name' => 'PlaynGO.Modules.Games.Machine231.dll',
        'hash' => '5C95045EE044DF3FAB4986FE98C5456BC760FB81',
    ],
    [
        'code' => 95343,
        'name' => 'PlaynGO.Modules.Games.Machine251.dll',
        'hash' => '0B20B0D34685853EE7C6A95043930832DE875B4A',
    ],
    [
        'code' => 78952,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine195.dll',
        'hash' => '1327ADF8D147C83196E4011DAAB9E85B823E7228',
    ],
    [
        'code' => 95033,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine229.dll',
        'hash' => 'CFB03FDA59A4A363160AEC45506DD51785F7A986',
    ],
    [
        'code' => 95036,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine246.dll',
        'hash' => '7E7435DB186A31B4ABC8A8375275D1CBD24FE196',
    ],
    [
        'code' => 94991,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine253.dll',
        'hash' => '64DDA801D10ED0761D20A77E0702F42A1BB22626',
    ],
    [
        'code' => 57910,
        'cert_ver' => 2,
        'name' => 'PlaynGO.Modules.Games.Machine167.dll',
        'hash' => '1F09D2896102B8A74790F0F2F6D6D0B123CDC113',
    ],
    [
        'code' => 47754,
        'cert_ver' => 2,
        'name' => 'PlaynGO.Modules.Games.Machine9.dll',
        'hash' => 'C211B14923A205FCAC96EC69EC6D22443F5F6921',
    ],
    [
        'code' => 47738,
        'cert_ver' => 2,
        'name' => 'PlaynGO.Modules.Games.Machine16.dll',
        'hash' => '8DC5289C32843A13E26B36319438A222EA53ABA7',
    ],
    [
        'code' => 47688,
        'cert_ver' => 2,
        'name' => 'PlaynGO.Modules.Games.Machine106.dll',
        'hash' => '135697c1c1f8eb7aa1b6fa67d540f9306c0d845e',
    ],
    [
        'code' => 47690,
        'cert_ver' => 2,
        'name' => 'PlaynGO.Modules.Games.Machine9.dll',
        'hash' => 'C211B14923A205FCAC96EC69EC6D22443F5F6921',
    ],
    [
        'code' => 47877,
        'cert_ver' => 2,
        'name' => 'PlaynGO.Modules.Games.Machine9.dll',
        'hash' => 'C211B14923A205FCAC96EC69EC6D22443F5F6921',
    ],
    [
        'code' => 47838,
        'cert_ver' => 2,
        'name' => 'PlaynGO.Modules.Games.Machine87.dll',
        'hash' => '174264A05AAD7C4F63CCBBB77F861C42CF4E02AC',
    ],
    [
        'code' => 47839,
        'cert_ver' => 2,
        'name' => 'PlaynGO.Modules.Games.Machine87.dll',
        'hash' => '174264A05AAD7C4F63CCBBB77F861C42CF4E02AC',
    ],
    [
        'code' => 47901,
        'cert_ver' => 2,
        'name' => 'PlaynGO.Modules.Games.Machine9.dll',
        'hash' => 'C211B14923A205FCAC96EC69EC6D22443F5F6921',
    ],
    [
        'code' => 78950,
        'cert_ver' => 2,
        'name' => 'PlaynGO.Modules.Games.Machine217.dll',
        'hash' => 'D5D8EC953F20D81492CF243DB77C9A765B685F14',
    ],
    [
        'code' => 104426,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine239.dll',
        'hash' => '79BEB860C7C2284A841A337ED6E41FEF78306FDE',
    ],
    [
        'code' => 104427,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine232.dll',
        'hash' => '89147B888261F118BD547A0CCB4DDF4424893C3E',
    ],
    [
        'code' => 104428,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine298.dll',
        'hash' => '8F5D5C967DC62EC8C003B613848D5B98FDB8BC54',
    ],
    [
        'code' => 104429,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine282.dll',
        'hash' => '4723C5470A217ED1FA2E5067BD77615FFEFB5F82',
    ],
    [
        'code' => 104430,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine275.dll',
        'hash' => '42F9C09EDD38CFA27D3CD549A6F5BD9F2CC80118',
    ],
    [
        'code' => 104431,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine266.dll',
        'hash' => '4622879E0289472D7D34033CEE5153F5977D574B',
    ],
    [
        'code' => 104432,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine270.dll',
        'hash' => '2E5D2889FC6634B5901866252FC5EA10BE2289B6',
    ],
    [
        'code' => 104433,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine277.dll',
        'hash' => 'DF4372A5B38429242AB8B6CF928F1CDAB62CEE82',
    ],
    [
        'code' => 104434,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine259.dll',
        'hash' => 'CB162BC21F3F172193FA26EC468036FE023901DA',
    ],
    [
        'code' => 95340,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine271.dll',
        'hash' => 'FAA7644C5CBBA6FF286B2F325A7012AC71C5F9C5',
    ],
    [
        'code' => 111821,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine233.dll',
        'hash' => '8F25964012F7DB62B873EA81E30BFC79D0D868C8',
    ],
    [
        'code' => 111823,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine321.dll',
        'hash' => '2FD4009B87C02E7BF9AF89FC22356316187A1250',
    ],
    [
        'code' => 111825,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine194.dll',
        'hash' => 'F00DB1357A9F550CB6FE3235B3B7CFFC1B120E0D',
    ],
    [
        'code' => 111827,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine269.dll',
        'hash' => '726FFE9AB9D75E6DCDCBCCBCE86D26E7CD3ABAAD',
    ],
    [
        'code' => 111829,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine281.dll',
        'hash' => '5371743B8EF80589315BA42BCA0C1D8D7F145C08',
    ],
    [
        'code' => 111831,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine272.dll',
        'hash' => 'AFCDB42B0E89E5A1AC5FEA08943A9D3AB6411EE7',
    ],
    [
        'code' => 111832,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine299.dll',
        'hash' => 'CD01472CEFE52480841EE5DA713260266ABAD7C2',
    ],
    [
        'code' => 111834,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine278.dll',
        'hash' => 'EED581040033675370E04CDE91D0DB8728B2AA16',
    ],
    [
        'code' => 111836,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine297.dll',
        'hash' => '7A1D2ABE030A2D82BADCF08E3CE6C7C11D427AC9',
    ],
    [
        'code' => 111839,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine306.dll',
        'hash' => 'F9F1BBF8688ACB73756F330A69D62B4D3ECD5EC2',
    ],
    [
        'code' => 111842,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine223.dll',
        'hash' => '7C307E9CE3815C70972406AA5F91F8F9EF56C7BC',
    ],
    [
        'code' => 111843,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine278.dll',
        'hash' => 'EED581040033675370E04CDE91D0DB8728B2AA16',
    ],
    [
        'code' => 111840,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine355.dll',
        'hash' => '47FDB8D04810C61976831825F695C2A5AC110B3F',
    ],
    [
        'code' => 111841,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine279.dll',
        'hash' => '49382D80CE2CAF6C8E6CDCCB6DF1561FCB0B3D48',
    ],
    [
        'code' => 119186,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine325.dll',
        'hash' => 'A2E64BBDD89A40A27D4E6F489F86D2EDBA808A4A',
    ],
    [
        'code' => 119189,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine280.dll',
        'hash' => '7F12278DEB8B72B3563EB04A64AECE4100E4CF0E',
    ],
    [
        'code' => 119191,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine322.dll',
        'hash' => 'A618A136566D2209294DE746AFE856407FA3CAFB',
    ],
    [
        'code' => 119192,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine237.dll',
        'hash' => '36135149619D9D09342DBBA76B0B892C4B68C67D',
    ],
    [
        'code' => 119193,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine267.dll',
        'hash' => '2E29CC2F959CBAE86B11FEF5460B0A0B8DF9C45F',
    ],
    [
        'code' => 119197,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine343.dll',
        'hash' => '74F2CCB89A04DABB2DB77082AEFE5ACED931FF3A',
    ],
    [
        'code' => 119199,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine356.dll',
        'hash' => '5697FEF2593E194EA8286965BD5A73D021A2F2F0',
    ],
    [
        'code' => 119200,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine345.dll',
        'hash' => '14547307D0D4EF94C93E95D0F5403C60972C7BFD',
    ],
    [
        'code' => 112286,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine303.dll',
        'hash' => 'E3A3916EBD64804C54926F5D9A6F6DD91C109B52',
    ],
    [
        'code' => 131582,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine273.dll',
        'hash' => 'D319939534CBAF35DB37E407D3E0C5AB7419C876',
    ],
    [
        'code' => 131585,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine334.dll',
        'hash' => '70B55A936091E0C4E60D81CFE3B8C1F7BF69E79D',
    ],
    [
        'code' => 131588,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine323.dll',
        'hash' => 'FD1000C71FE7188566D44FAD6904741D9CB81179',
    ],
    [
        'code' => 131589,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine287.dll',
        'hash' => 'DCCA010325E0EC78D538FF583EE13FA203D721C6',
    ],
    [
        'code' => 131592,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine284.dll',
        'hash' => 'E743F2E8CE866A0350640A2AFD65B58F0FDF09CF',
    ],
    [
        'code' => 131594,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine274.dll',
        'hash' => 'CB756151EB4EBC84E4BFD2DC4B385FBF8FC7C491',
    ],
    [
        'code' => 131598,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine194.dll',
        'hash' => 'F00DB1357A9F550CB6FE3235B3B7CFFC1B120E0D',
    ],
    [
        'code' => 131602,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine358.dll',
        'hash' => '443B42122C980AAE6E92072A3BEDC86908F68443',
    ],
    [
        'code' => 131604,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine239.dll',
        'hash' => '79BEB860C7C2284A841A337ED6E41FEF78306FDE',
    ],
    [
        'code' => 131607,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine335.dll',
        'hash' => 'CA8BD95C3C7781C78472E4DEF15AEFA33D88E479',
    ],
    [
        'code' => 131611,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine327.dll',
        'hash' => '7DA11618975ECE3A2C4B7D29938704CF82EB3363',
    ],
    [
        'code' => 131614,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine288.dll',
        'hash' => '413A16A4ADA7A108228A13EEA4C61DA3954BD072',
    ],
    [
        'code' => 162550,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine218.dll',
        'hash' => 'DECC4A6B10D85F900477D9CAFB2C9F37B811E651',
    ],
    [
        'code' => 162551,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine186.dll',
        'hash' => '5EE80AD063E7269F3C3647F9D835FB941659A34A',
    ],
    [
        'code' => 162554,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine126.dll',
        'hash' => '409AD256E1874C5EBB4651ECDD96692E72BBEF7E',
    ],
    [
        'code' => 162555,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine73.dll',
        'hash' => 'A914BD2FB8E11A0E8B711DC88249D4702BA66E06',
    ],
    [
        'code' => 95341,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine230.dll',
        'hash' => '202C9C41C66A70F7714679A6E1CFAC89508F82E3',
    ],
    [
        'code' => 162862,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine88.dll',
        'hash' => '6A44ECC95327D2F0E118B86A3874E169DEF9D4F3',
    ],
    [
        'code' => 162867,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine110.dll',
        'hash' => '9A668389E197EE8A9916EA26F4C122C3A89E6A53',
    ],
    [
        'code' => 162873,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine314.dll',
        'hash' => '63A80A4FD1834B8F6407599501F78EB80B1CC89E',
    ],
    [
        'code' => 47854,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine47.dll',
        'hash' => '38B087A63C350B68BEB9F99BCE56F4FFC5CDFBDB',
    ],
    [
        'code' => 162897,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine218.dll',
        'hash' => 'DECC4A6B10D85F900477D9CAFB2C9F37B811E651',
    ],
    [
        'code' => 162899,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine348.dll',
        'hash' => 'D9001FCC7EB15C59F8DA0824C78B4C7DA9B424D0',
    ],
    [
        'code' => 162905,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine285.dll',
        'hash' => 'C1B0688F0620BE5C7B36C2CB0E986C0505685431',
    ],
    [
        'code' => 162916,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine339.dll',
        'hash' => '24F21CD842CF9EF6877816A868E9200C2AB2D409',
    ],
    [
        'code' => 163023,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine131.dll',
        'hash' => 'A80C11AF99ADA6D2804DB3A8D02C5A9DBF94B76F',
    ],
    [
        'code' => 163041,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine333.dll',
        'hash' => '8D233FD1FE5035C98B01A9E1DD30EEDBC081CC51',
    ],
    [
        'code' => 162552,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine304.dll',
        'hash' => '106D2D28162694B213FCCFC26BE5A6FBE67DE3AA',
    ],
    [
        'code' => 162560,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine336.dll',
        'hash' => '182719D70FAF93E8D7F4E2FCC88370D1E2DA69DB',
    ],
    [
        'code' => 162567,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine157.dll',
        'hash' => '890449AE99C0BFCB76D6A6FDFCB48D3B47FE6508',
    ],
    [
        'code' => 162570,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine289.dll',
        'hash' => '2800C162C060A0F4BFAD307666AC9AB73DD1E53B',
    ],
    [
        'code' => 162579,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine142.dll',
        'hash' => 'DACB285414CEE72DAB2B829BAF6AA34731A421C6',
    ],
    [
        'code' => 162584,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine352.dll',
        'hash' => '8EBD06F73BA77E05C3DDA571EFC6EE0839DA393F',
    ],
    [
        'code' => 162594,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine134.dll',
        'hash' => 'C239BAEEBA01DD901BD86EA0EC6EFBE7BE379529',
    ],
    [
        'code' => 162596,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine331.dll',
        'hash' => '67398BD590201CAC5BE309E1417F3FC6742F9AFE',
    ],
    [
        'code' => 162823,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine198.dll',
        'hash' => '109DB3D44D9A8FE4817197245809BCF48C351318',
    ],
    [
        'code' => 162829,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine347.dll',
        'hash' => 'D3993BF68FC112042285541E1ACFCDDBD957A0D0',
    ],
    [
        'code' => 119190,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine296.dll',
        'hash' => '313747E64091550512CFCA0405EA7C7D97279A47',
    ],
    [
        'code' => 162839,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine228.dll',
        'hash' => '91D71AA0538D13CBC6A0B8A7E683B207A1E36CA3',
    ],
    [
        'code' => 162853,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine362.dll',
        'hash' => '61ED30952BA8223A8825BCBE2CDB2602FBFC25A8',
    ],
    [
        'code' => 162882,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine235.dll',
        'hash' => '05413C7CD99DF497B235F5BA562100CA13403DF7',
    ],
    [
        'code' => 162887,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine291.dll',
        'hash' => '307F68035F9118F6EE44EFED054AF2AF05C56499',
    ],
    [
        'code' => 162900,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine297.dll',
        'hash' => '7A1D2ABE030A2D82BADCF08E3CE6C7C11D427AC9',
    ],
    [
        'code' => 162923,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine303.dll',
        'hash' => 'E3A3916EBD64804C54926F5D9A6F6DD91C109B52',
    ],
    [
        'code' => 162928,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine310.dll',
        'hash' => 'BB889D84C17F2A3A94A19B950282F6D651F58610',
    ],
    [
        'code' => 135774,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine363.dll',
        'hash' => '3A5B87B83F3A2A05EF58AC8BB713533D28B5F8AC',
    ],
    [
        'code' => 162952,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine360.dll',
        'hash' => 'B0CB527E2F64E3A738AD7BDF9680A48EE4E019EA',
    ],
    [
        'code' => 162963,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine189.dll',
        'hash' => '8E14B8E6D4AF1B72A8F93221C3818CA6250F5DA2',
    ],
    [
        'code' => 141180,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine337.dll',
        'hash' => 'AD9BC5084FF74A011CCB59378F1344A36DA10DF6',
    ],
    [
        'code' => 162980,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine148.dll',
        'hash' => 'D601B042C1AD9A56E9B23EC03E5EED91AAFBCA45',
    ],
    [
        'code' => 137803,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine293.dll',
        'hash' => '6D73263B1779D2EABDECBC331BBE38D45B61A541',
    ],
    [
        'code' => 162984,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine353.dll',
        'hash' => '491C3974C719FB995AB01740CCD1416EB2D405EF',
    ],
    [
        'code' => 162990,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine359.dll',
        'hash' => '0F90A03A924F874B338DFA921AD8B7CF7CCFF848',
    ],
    [
        'code' => 135772,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine268.dll',
        'hash' => '8ED2440486B9FE450EBD19957CACDD6F85AB91A6',
    ],
    [
        'code' => 162995,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine294.dll',
        'hash' => '9AD2437534AEFDCDF55F7FC26DCF7538C2B0A0CD',
    ],
    [
        'code' => 163005,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine301.dll',
        'hash' => 'DE3A702EA2088B09F8D67D78E58080E6124D1735',
    ],
    [
        'code' => 163010,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine149.dll',
        'hash' => '7393A5EE5FC6669F6E97DF6004C636C4E7328E72',
    ],
    [
        'code' => 163018,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine295.dll',
        'hash' => '44C96D5470A102AAE2D889469821BADBDDBCDE51',
    ],
    [
        'code' => 135773,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine313.dll',
        'hash' => 'D3E3F819962B290CE166E65667A59D5B4C39FC68',
    ],
    [
        'code' => 64821,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine158.dll',
        'hash' => '3A1000306A71413F2F1BE4A2340FBE9E27BF5487',
    ],
    [
        'code' => 163029,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine290.dll',
        'hash' => '730F0A48A31975B262A1EC58B762ADFBE8EF1E57',
    ],
    [
        'code' => 163033,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine236.dll',
        'hash' => 'CE54AAE41A3995F48F2F209C789DDC1F721C6D2D',
    ],
    [
        'code' => 163035,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine318.dll',
        'hash' => '857A5E0730888EE3ACB596811DD7C421395A0099',
    ],
    [
        'code' => 163036,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine239.dll',
        'hash' => '79BEB860C7C2284A841A337ED6E41FEF78306FDE',
    ],
    [
        'code' => 163037,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine168.dll',
        'hash' => '4FC9A820997053D3FED0C44169671B8A38151B84',
    ],
    [
        'code' => 170785,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine357.dll',
        'hash' => '8063262A9A75FD743F40A076FF99370CC3D8EE44',
    ], 
    [
        'code' => 162859,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine330.dll',
        'hash' => 'C8557976024244F087C12DDEBF1AC20A3BEB2382',
    ],
    [
        'code' => 170786,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine382.dll',
        'hash' => 'C1F0B3106B06ABDFD55301239E939AAE00AE8630',
    ],
    [
        'code' => 177815,
        'cert_ver' => 1,
        'name' => 'PlaynGO.Modules.Games.Machine319.dll',
        'hash' => 'BC79CC0EE20C3039FAE63FC7956DFC68385E7328',
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

