<?php

require_once '/var/www/videoslots.it/phive/phive.php';
$user = cu($argv[1] ?? 6624618);

$games = [
    [
        'code' => 91986,
        'name' => 'gamelib_bookofadventure2_10.52.7_release.port',
        'hash' => '0abc7c14bf54597635ab12e14eca29b48628412f',
    ],
    [
        'code' => 95679,
        'name' => 'gamelib_classicfortiesquattro_10.46.3_release.port',
        'hash' => 'de5e5f6c13b9e925e3ccec74016f88db4de6caa4',
    ],
    [
        'code' => 95445,
        'name' => 'gamelib_bookofcleopatra2_10.52.7_release.port',
        'hash' => '02e4a2f33d5fa931b5db4831c6d86623a7ea5945',
    ],
    [
        'code' => 95446,
        'name' => 'gamelib_bookofleoquattro_7.25.2_release.port',
        'hash' => '258882cfef81f3d807eef4509c59ad7a6ccf5545',
    ],
    [
        'code' => 95434,
        'name' => 'gamelib_9pyramidsoffortune_10.60.3_release.port',
        'hash' => '548fbda55d7d63f9791587ba6703c153de529a04',
    ],
    [
        'code' => 95435,
        'name' => 'gamelib_apesofdoom_10.68.3_release.port',
        'hash' => '85528392a8c68bb161be2b801043dba7439004bb',
    ],
    [
        'code' => 95437,
        'name' => 'gamelib_atlantisgold_10.75.6_release.port',
        'hash' => 'd89e43a9850ed1ec4bf365969a4e33f304e97dd2',
    ],
    [
        'code' => 95450,
        'name' => 'gamelib_candywildbonanzaholdandspin_11.6.5_release.port',
        'hash' => '3fe32647dc4edeb6b87cab48f160dd570732e58c',
    ],
    [
        'code' => 95453,
        'name' => 'gamelib_celticspirit_10.16.8_release.port',
        'hash' => 'bf7cbada226dae75d363deb3b39ad676f0249130',
    ],
    [
        'code' => 95688,
        'name' => 'gamelib_dracula_10.51.6_release.port',
        'hash' => 'd509e924d59b12f991f2421c1ece2d4d5294f914',
    ],
    [
        'code' => 95691,
        'name' => 'gamelib_elpatronwilddrop_10.81.5_release.port ',
        'hash' => '75a577447d7ab6115b9b1ca7959772bee1c83eeb',
    ],
    [
        'code' => 95693,
        'name' => 'gamelib_encharmedquattro_10.46.3_release.port',
        'hash' => '152b971631baf7331757285894a4b7a04f7e10dc',
    ],
    [
        'code' => 95702,
        'name' => 'gamelib_fruitsgonewilddeluxe_10.56.3_release.port',
        'hash' => 'e9b8a33b6139d359fbbc10286bdada6d29e20d72',
    ],
    [
        'code' => 95704,
        'name' => 'gamelib_giantsgoldmegaways_10.42.2_release.port',
        'hash' => 'f674f10cf4c9c945f5d960352629a0b8c5fc7a40',
    ],
    [
        'code' => 95709,
        'name' => 'gamelib_greatwars_7.17.6_release.port',
        'hash' => '4ab55d76aaf7d281ce392b00576355aac38bb32e',
    ],
    [
        'code' => 95715,
        'name' => 'gamelib_hercules_10.16.3_release.port',
        'hash' => '0276d20f252d366ec9f123cd53d043c838dcb22e',
    ],
    [
        'code' => 95732,
        'name' => 'gamelib_luckygoldenpot_10.52.2_release.port ',
        'hash' => '36bc7e87cf8cade3be3a6ecc043e6c3bf13d5d3a',
    ],
    [
        'code' => 95441,
        'name' => 'gamelib_blackgold2megaways_10.51.6_release.por',
        'hash' => 'b814ac816fe2c4c308c082daec3015a41b6075f4',
    ],
    [
        'code' => 95448,
        'name' => 'gamelib_burningscatters_10.74.2_release.port',
        'hash' => 'bfa091b70af99e14f03257ad75e72d75f60899c1',
    ],
    [
        'code' => 95452,
        'name' => 'gamelib_candywaysbonanza2megaways_10.75.3_release.port',
        'hash' => '031a631276333560e1c059f12a2a7ba910b6c097',
    ],
    [
        'code' => 95698,
        'name' => 'gamelib_mentalbreakdownfixedsymbols_11.1.2_release.port',
        'hash' => '3364ab7a56935d799680ab24718b1452faf4089b',
    ],
    [
        'code' => 95717,
        'name' => 'gamelib_heroclash_10.52.4_release.port',
        'hash' => '712c8123077e87f63d08d78854b1af2cb9ec07e8',
    ],
    [
        'code' => 95864,
        'name' => 'gamelib_holafrutas_11.13.3_release.port',
        'hash' => '688e325dedabe9dc21bc5cf1187a20ed25466a64',
    ],
    [
        'code' => 95719,
        'name' => 'gamelib_hot7holdandspin_10.52.5_release.port',
        'hash' => 'd56497e2c4bd5d41218c0387967ffb64cb62f6ca',
    ],
    [
        'code' => 95720,
        'name' => 'gamelib_hotfortiesquattro_10.57.3_release.port',
        'hash' => '62dc648e95d41e7103b0b911cedd99f9191ebc68',
    ],
    [
        'code' => 95721,
        'name' => 'gamelib_hotfruitsdeluxequattro_10.78.3_release.port',
        'hash' => '5e6d1aa3c175752418bf9ad8dd710281c4bce0d7',
    ],
    [
        'code' => 95723,
        'name' => 'gamelib_midaswilds_10.57.2_release.port',
        'hash' => '465433d66060df9ce5f25debe2722e35db9cd925',
    ],
    [
        'code' => 95724,
        'name' => 'gamelib_jewelofthejungle_10.73.6_release.port',
        'hash' => 'd26a317d6cc24872eb9bd465dc9c48f944758472',
    ],
    [
        'code' => 95725,
        'name' => 'gamelib_johndoe_10.55.6_release.port',
        'hash' => 'aafae76e940fda417b6c3c3a5eb7602cc1dedd9d',
    ],
    [
        'code' => 95861,
        'name' => 'gamelib_jokerwildrespin_11.11.7_release.port',
        'hash' => '4ef81bef226f49c69d7b187e91213949ee461329',
    ],
    [
        'code' => 95728,
        'name' => 'gamelib_kingbambam_5.91.2_release.port',
        'hash' => '3977a4c35bf0165883151be4caa686d450c9315f',
    ],
    [
        'code' => 95731,
        'name' => 'gamelib_eldorado_10.73.5_release.port',
        'hash' => 'ab33dbfc3f23f9a6e3eed74d1e741c996475ff54',
    ],
    [
        'code' => 95765,
        'name' => 'gamelib_mightofzeus_10.60.4_release.port',
        'hash' => '1b9d48d117beb43409cef40a7ce30e272ff4fe0a',
    ],
    [
        'code' => 95766,
        'name' => 'gamelib_mysterydrop_10.53.5_release.port',
        'hash' => 'ac4e0073580ded3208b32b52b86070e23b35a5fb',
    ],
    [
        'code' => 95768,
        'name' => 'gamelib_oldfellow_10.62.4_release.port',
        'hash' => '25b31e13011583c68c58bb32b1d789f9d94b1773',
    ],
    [
        'code' => 95769,
        'name' => 'gamelib_phoenixqueen_10.79.4_release.port',
        'hash' => 'b03f10311cd40208e4d34da5f58ccbe26d66ae50',
    ],
    [
        'code' => 95770,
        'name' => 'gamelib_pyramidstrike_10.61.1_release.port',
        'hash' => '808c94eb4c28be60215b5ae6204b60632b0bd465',
    ],
    [
        'code' => 95771,
        'name' => 'gamelib_ragingbison_10.53.3_release.port',
        'hash' => '276e0932a485ba58a1eb992771f078f71432574d',
    ],
    [
        'code' => 95774,
        'name' => 'gamelib_seagod_10.46.4_release.port',
        'hash' => '28c7c3a6cb3aa16995fa6481b62a92c823455f85',
    ],
    [
        'code' => 95781,
        'name' => 'gamelib_spartus_5.91.4_release.port',
        'hash' => '8e859d01c609e2052d325f8c24af3da685d491cf',
    ],
    [
        'code' => 95782,
        'name' => 'gamelib_superwildblaster_10.51.5_release.port',
        'hash' => '778e3f4db7cea87051ca74c633fe7c8261d84e9c',
    ],
    [
        'code' => 95792,
        'name' => 'gamelib_thebook_10.57.6_release.port',
        'hash' => '5cbf2499d3f6d4cafc5e6d516e60940d1d3b29a9',
    ],
    [
        'code' => 95795,
        'name' => 'gamelib_ultrajoker_10.51.7_release.port',
        'hash' => '2d93c678eacfef7b69e3edc1d07847a0aee0e3a4',
    ],
    [
        'code' => 95853,
        'name' => 'gamelib_wildbounty_10.55.4_release.port',
        'hash' => '3a5402b9597c0136547f62ca44a3ab4e29849684',
    ],
    [
        'code' => 95863,
        'name' => 'gamelib_lastchancesaloon_11.10.4_release.port',
        'hash' => 'cb1cdb066758d8d44d37153aacc7eef8f3c07410',
    ],
    [
        'code' => 95856,
        'name' => 'gamelib_wildstocking_10.73.3_release.port',
        'hash' => 'a57846a187df923bf6d9f916e25e749b8fa92476',
    ],
    [
        'code' => 95857,
        'name' => 'gamelib_wildwildbass_11.7.6_release.port',
        'hash' => '42728520e6edebc2bfe08d5e9c8e545a95aaf122',
    ],
    [
        'code' => 95858,
        'name' => 'gamelib_wolfreelsrapidlink_10.55.5_release.port',
        'hash' => '0adececc1c340e326081c07a5e762a6489190808',
    ],
    [
        'code' => 95859,
        'name' => 'gamelib_wonderlandwilds_10.57.4_release.port',
        'hash' => '184091d420a666c070f968f2972e85e3451cfcf1',
    ],
    [
        'code' => 95767,
        'name' => 'gamelib_mysticalsantamegaways_10.46.6_release.port',
        'hash' => '62ac7d600271782869c48126f3275d37697df407',
    ],
    [
        'code' => 95860,
        'name' => 'gamelib_dynamitestrike_11.1.2_release.port',
        'hash' => 'a5d2f2d54f1a4cf57a79cb37419e3478ddf76e9c',
    ],
    [
        'code' => 95436,
        'cert_ver' => 1,
        'name' => 'gamelib_arizonadiamondsquattro_10.69.7_release.port',
        'hash' => '67d8703d697edf257de7e4eb51cc59a903043a29',
    ],
    [
        'code' => 95439,
        'cert_ver' => 1,
        'name' => 'gamelib_bankorprank_10.55.10_release.port',
        'hash' => '3608b928e343e947e4ee9dd26de367f5ee31cb33',
    ],
    [
        'code' => 95440,
        'cert_ver' => 1,
        'name' => 'gamelib_big5junglejackpots_10.53.7_release.port',
        'hash' => '95cd421cd86aa97040267dee19a425d128d99709',
    ],
    [
        'code' => 95442,
        'cert_ver' => 1,
        'name' => 'gamelib_blackgoldmegaways_11.10.7_release.port',
        'hash' => 'eac8084c8c073f4358dbd8ec1a230b9bd5dbcee5',
    ],
    [
        'code' => 95443,
        'cert_ver' => 1,
        'name' => 'gamelib_bookofadventure3_10.52.6_release.port',
        'hash' => 'dc7b4f665c673abd9e5dc3a6beec8fcd62488ea4',
    ],
    [
        'code' => 95444,
        'cert_ver' => 1,
        'name' => 'gamelib_bookofanubis_10.57.8_release.port',
        'hash' => '2664d97cf5911f5161cc725cd11a0f92f368799a',
    ],
    [
        'code' => 95451,
        'cert_ver' => 1,
        'name' => 'gamelib_candywaysbonanza_10.55.5_release.port',
        'hash' => '36089e792aba41679e542bb2542b9c8ae1ad568d',
    ],
    [
        'code' => 95682,
        'cert_ver' => 1,
        'name' => 'gamelib_classicjoker6reels_10.62.11_release.port',
        'hash' => 'eca9449c793b9a549197e2a1e2c2b59eb623c08e',
    ],
    [
        'code' => 95685,
        'cert_ver' => 1,
        'name' => 'gamelib_devils_5.91.4_release.port',
        'hash' => '4b40eb2b91cb1fa744e156ae7b9acf31bc602d7b',
    ],
    [
        'code' => 95696,
        'cert_ver' => 1,
        'name' => 'gamelib_flappers_9.8.4_release.port',
        'hash' => 'd2b14ff0ace9c3951fa4dde3d8f8b962956b8d42',
    ],
    [
        'code' => 95700,
        'cert_ver' => 1,
        'name' => 'gamelib_fruitstorm_11.0.7_release.port',
        'hash' => '97f47e2abf2e38b08db7282a9779dac5de40ee38',
    ],
    [
        'code' => 95701,
        'cert_ver' => 1,
        'name' => 'gamelib_fruitsgonewild_10.57.5_release.port',
        'hash' => '4c04a196a7c8865239c71242300994335496be9f',
    ],
    [
        'code' => 95703,
        'cert_ver' => 1,
        'name' => 'gamelib_fruitsgonewildsupreme_10.78.8_release.port',
        'hash' => '9903c64c5d48ff08b809f09da7847c4ddd2cb932',
    ],
    [
        'code' => 95706,
        'cert_ver' => 1,
        'name' => 'gamelib_godsofdeath_9.17.8_release.port',
        'hash' => '7595b90ae03447189588fb8d6a4a0ca48b104f0c',
    ],
    [
        'code' => 95708,
        'cert_ver' => 1,
        'name' => 'gamelib_godsofsecrecy_11.10.5_release.port',
        'hash' => '696f9987ff6f84c41bfefb59cfbb1962494b8b68',
    ],
    [
        'code' => 95729,
        'cert_ver' => 1,
        'name' => 'gamelib_legendrising_8.6.2_release.port',
        'hash' => '428d954eb1197d7494f65095474c44f04f22bc19',
    ],
    [
        'code' => 95730,
        'cert_ver' => 1,
        'name' => 'gamelib_liongold_10.59.4_release.port',
        'hash' => '8a6ebb8168d91a3f17dc8132e2b08441b4e490f0',
    ],
    [
        'code' => 95733,
        'cert_ver' => 1,
        'name' => 'gamelib_mariachi_5.91.4_release.port',
        'hash' => '464a98c632572642c66b0e7abfde5c7550079ab5',
    ],
    [
        'code' => 95764,
        'cert_ver' => 1,
        'name' => 'gamelib_midaswilds_10.57.2_release.port',
        'hash' => '465433d66060df9ce5f25debe2722e35db9cd925',
    ],
    [
        'code' => 95772,
        'cert_ver' => 1,
        'name' => 'gamelib_rambo_9.26.4_release.port',
        'hash' => 'ab08e6e3d7970c797d4b18edcf1d43cfb5110ce6',
    ],
    [
        'code' => 95773,
        'cert_ver' => 1,
        'name' => 'gamelib_runnerrunnermegaways_9.25.2_release.port',
        'hash' => '2786933fec3beb01bcebf51930ab1ebf68d8c7a2',
    ],
    [
        'code' => 95862,
        'cert_ver' => 1,
        'name' => 'gamelib_runnerrunnerpopwins_11.11.4_release.port',
        'hash' => 'e2f8343f55e3848e4aab2834832f3e58105c1cfc',
    ],
    [
        'code' => 95775,
        'cert_ver' => 1,
        'name' => 'gamelib_serengetiwilds_10.55.5_release.port',
        'hash' => 'b7a10e8aac12293a1dff1f93bbaeb2ddf2a4cbae',
    ],
    [
        'code' => 95776,
        'cert_ver' => 1,
        'name' => 'gamelib_skyofthunder_8.8.7_release.port',
        'hash' => '772e43ecdd524a6893a103ddb47425833dcfaa1f',
    ],
    [
        'code' => 95777,
        'cert_ver' => 1,
        'name' => 'gamelib_sorcerersofthenight_7.17.2_release.port',
        'hash' => 'f23b329c46aa30c1c8df8ea84ef47f9d3ac7d39e',
    ],
    [
        'code' => 95780,
        'cert_ver' => 1,
        'name' => 'gamelib_spartania_7.6.9_release.port',
        'hash' => '863ffa4f867a5bf3a77b4d564680a90c94017818',
    ],
    [
        'code' => 95789,
        'cert_ver' => 1,
        'name' => 'gamelib_superwildblaster_10.51.5_release.port',
        'hash' => '778e3f4db7cea87051ca74c633fe7c8261d84e9c',
    ],
    [
        'code' => 95794,
        'cert_ver' => 1,
        'name' => 'gamelib_tropicaladventure_5.91.5_release.port',
        'hash' => 'b755badd7be46e778aa4d0c5bdf10450cba0b208',
    ],
    [
        'code' => 95796,
        'cert_ver' => 1,
        'name' => 'gamelib_valkyriesofodin_5.91.3_release.port',
        'hash' => '6b9f4c0f50dd4f3e52fadff948edebd53cfdfc91',
    ],
    [
        'code' => 95848,
        'cert_ver' => 1,
        'name' => 'gamelib_volcano_10.4.6_release.port',
        'hash' => '39d078756f4dab88721e4359076cf394093c27e5',
    ],
    [
        'code' => 95851,
        'cert_ver' => 1,
        'name' => 'gamelib_voodooreels_10.56.10_release.port',
        'hash' => 'b3a3677d0e2a1b2ebd7dc32387bc7cb0fdd5c8fd',
    ],
    [
        'code' => 95449,
        'cert_ver' => 1,
        'name' => 'gamelib_candylinksbonanza_11.10.9_release.port',
        'hash' => '7a39221486f9ba28d4231ecdf1a27ceb3f525f96',
    ],
    [
        'code' => 95865,
        'cert_ver' => 1,
        'name' => 'gamelib_hotchilliways_11.18.8_release.port',
        'hash' => 'c4010982c646ac001924c70d7285a671df4587b2',
    ],
    [
        'code' => 95727,
        'cert_ver' => 1,
        'name' => 'gamelib_superwildblaster_10.51.5_release.port',
        'hash' => '778e3f4db7cea87051ca74c633fe7c8261d84e9c',
    ],
    [
        'code' => 95737,
        'cert_ver' => 1,
        'name' => 'gamelib_mayanwildmystery_11.10.7_release.port',
        'hash' => '8da3cbee098405622d26a3f37937212d60044465',
    ],
    [
        'code' => 95722,
        'cert_ver' => 1,
        'name' => 'gamelib_hotjoker_10.52.3_release.port',
        'hash' => '79ef7168dd6d274a7591f10b1f7bb00c233fb17e',
    ],
    [
        'code' => 95726,
        'cert_ver' => 1,
        'name' => 'gamelib_jokerdrop_10.69.5_release.port',
        'hash' => '0e5b38d7091a67780636b1507c72dcd70c84766d',
    ],
    [
        'code' => 95694,
        'cert_ver' => 1,
        'name' => 'gamelib_extrememegaways_10.57.4_release.port',
        'hash' => 'c371ccbd205ce8415590ba635bae8125259c60a3',
    ],
    [
        'code' => 101068,
        'cert_ver' => 1,
        'name' => 'gamelib_powerjoker_10.56.12_release.port',
        'hash' => '8261a9018852466e26e0231a11ec831d63739489',
    ],
    [
        'code' => 95438,
        'cert_ver' => 1,
        'name' => 'gamelib_banditsthunderlink_10.37.8_release.port',
        'hash' => 'c4c0b4f17def16d83731b85cc5dcd06ccf73dde0',
    ],
    [
        'code' => 95689,
        'cert_ver' => 1,
        'name' => 'gamelib_dragonsandmagic_9.35.7_release.port',
        'hash' => '77d971defad2106e8a1423b7897ad0ecdcc675de',
    ],
    [
        'code' => 95734,
        'cert_ver' => 1,
        'name' => 'gamelib_marlincatch_10.78.3_release.port',
        'hash' => '9f04cdb00fac1c3d852ebbf99b1306c079abfa8f',
    ],
    [
        'code' => 95736,
        'cert_ver' => 1,
        'name' => 'gamelib_mayanrush_10.46.3_release.port',
        'hash' => '0c222d7adb55ce500bbfcde6c5ae41143b3ab17c',
    ],
    [
        'code' => 95783,
        'cert_ver' => 1,
        'name' => 'gamelib_superjokermegaways_10.29.9_release.port',
        'hash' => '360c5d698a4c8cdaae2ae0b7ed5efa402f8f90bc',
    ],
    [
        'code' => 95789,
        'cert_ver' => 1,
        'name' => 'gamelib_superwildblaster_10.51.5_release.port',
        'hash' => '778e3f4db7cea87051ca74c633fe7c8261d84e9c',
    ],
    [
        'code' => 95971,
        'cert_ver' => 1,
        'name' => 'gamelib_superwildmegaways_10.54.6_release.port',
        'hash' => '3e6668539e3284a643b9f4c9a6cc00ba0f29f849',
    ],
    [
        'code' => 166059,
        'cert_ver' => 1,
        'name' => 'gamelib_celticspirit_10.16.8_release.port',
        'hash' => 'bf7cbada226dae75d363deb3b39ad676f0249130',
    ],
    [
        'code' => 166060,
        'cert_ver' => 1,
        'name' => 'gamelib_classicjoker6reels_10.62.11_release.port',
        'hash' => 'eca9449c793b9a549197e2a1e2c2b59eb623c08e',
    ],
    [
        'code' => 166065,
        'cert_ver' => 1,
        'name' => 'gamelib_extrememegaways_10.57.4_release.port',
        'hash' => 'c371ccbd205ce8415590ba635bae8125259c60a3',
    ],
    [
        'code' => 166067,
        'cert_ver' => 1,
        'name' => 'gamelib_giantsgoldmegaways_10.42.2_release.port',
        'hash' => 'f674f10cf4c9c945f5d960352629a0b8c5fc7a40',
    ],
    [
        'code' => 95793,
        'cert_ver' => 1,
        'name' => 'gamelib_expendablesmegaways_9.14.8_release.port',
        'hash' => '36b323bed0fe02acfc7f59ba0b6c16768d9e12ab',
    ],
    [
        'code' => 95797,
        'cert_ver' => 1,
        'name' => 'gamelib_vikingssmash_10.46.3_release.port',
        'hash' => 'afa834e35a110072f4d973ae057a638d214707fc',
    ],
    [
        'code' => 166106,
        'cert_ver' => 1,
        'name' => 'gamelib_rambo_9.26.4_release.port',
        'hash' => 'ab08e6e3d7970c797d4b18edcf1d43cfb5110ce6',
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

