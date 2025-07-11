<?php

require_once '/var/www/videoslots.it/phive/phive.php';
$user = cu($argv[1] ?? 6624618);

$games = [
    [
        'code' => 63385,
        'name' => '6TokensOfGold_96.signature',
        'hash' => 'fbf24bf4c3c62bc9e8ad153604fa387030b0a84c',
    ],
    [
        'code' => 63667,
        'name' => 'SpinPlayGames_AmazingLinkZeus.signature',
        'hash' => 'd00f7d02757863045323c96932f04c13c1be1c7b',
    ],
    [
        'code' => 64281,
        'name' => 'MahiGaming_AncientFortunesPoseidon_94.signature',
        'hash' => 'a35ab3555b7ddb1b235a1bde4dcf9f82640ce36b',
    ],
    [
        'code' => 63677,
        'name' => 'MahiGaming_AssassinMoon_96.signature',
        'hash' => 'ceb53b5d9098b0798dbf2ec2d0a121c3389268a4',
    ],
    [
        'code' => 64284,
        'name' => '9MasksOfFire_96.signature',
        'hash' => '8325b10cf5eb065928a5f097d24bd419eb661419',
    ],
    [
        'code' => 63638,
        'name' => 'PearFiction_ChicagoGold.signature',
        'hash' => '01c4978ee755705d93e58a3f667c6547ac233acc',
    ],
    [
        'code' => 63672,
        'name' => 'BreakAwayUltra_96.signature',
        'hash' => 'a36026ed906ed57503e6eeac54eb19d798d52cea',
    ],
    [
        'code' => 63402,
        'name' => 'GoldCollector_96.signature',
        'hash' => '04247e03be1bbb3641291bd9b5e7c24ee4858b48',
    ],
    [
        'code' => 63451,
        'name' => 'MahiGaming_NineMasksOfFire_96.signature',
        'hash' => 'a7d066690032d0c5c40ca00fd17dd3851594b4e3',
    ],
    [
        'code' => 63426,
        'name' => 'CrazyTooth_MegaDeluxe.signature',
        'hash' => 'fb27292aa33132973a1b2a30c57d29df4d7062fc',
    ],
    [
        'code' => 63388,
        'name' => 'AlchemyFortunes_96.signature',
        'hash' => 'c5403b9dbf5e0a109503ca0426d30159970b4d98',
    ],
    [
        'code' => 63452,
        'name' => 'GoldCoinStudios_AnimalsOfAfrica.signature',
        'hash' => '4fef26f0077c239bd68500174e552926904c2c67',
    ],
    [
        'code' => 63642,
        'name' => 'Pulse8.Slots.BasketballStarOnFireV94.signature',
        'hash' => '00f17d0540ab5eaf87d844ff190dad680e607d69',
    ],
    [
        'code' => 63643,
        'name' => 'Pulse8.Slots.CarnavalJackpot_96.signature',
        'hash' => 'aa65cf8868a19c7f0b6abff187c980889371e6ce',
    ],
    [
        'code' => 64282,
        'name' => '9MasksOfFire_96.signature',
        'hash' => '8325b10cf5eb065928a5f097d24bd419eb661419',
    ],
    [
        'code' => 63403,
        'name' => 'IngotsOfCaiShen_96.signature',
        'hash' => '49bacfff8b6fb410c53b1fe07a75f8023c7d3306',
    ],
    [
        'code' => 63417,
        'name' => 'SolarWilds_96.signature',
        'hash' => '18443c53f80d2e931abe86721ba12d86cc089be7',
    ],
    [
        'code' => 63652,
        'name' => 'Rabcat_TropicalWilds.signature',
        'hash' => 'd41f717d38ef7b29a96f309f6ce9ecf1980a4428',
    ],
    [
        'code' => 63479,
        'name' => 'JFTW_SilverbackMultiplierMountain.signature',
        'hash' => '5ed3a918de17c9a5302e0d72407f881f922e69d0',
    ],
    [
        'code' => 64279,
        'name' => 'Game_Service_Signature_File.signature',
        'hash' => '1769e222f0361078e242614d375dadcfd207d1a1',
    ],
    [
        'code' => 63413,
        'name' => 'ShamrockHolmes_96.signature',
        'hash' => '46f87dace0f273b19fd14af8a5e1b15926391df1',
    ],
    [
        'code' => 63633,
        'name' => 'QueenOfAlexandria_94.signature',
        'hash' => '3d64847bfbe8f2eb0e2dd97a93adf16ce24c6afd',
    ],
    [
        'code' => 63389,
        'name' => 'ForgottenIsland_96.signature',
        'hash' => '750541679ea49152b4b82ef0e15a1195a63fbcc4',
    ],
    [
        'code' => 63469,
        'name' => 'JFTW_EmeraldGold.signature',
        'hash' => '10ed43a458c8ac20efb3188e87515e1b5d24fcf8',
    ],
    [
        'code' => 63669,
        'name' => 'SpinPlayGames_DiamondKingJackpots.signature',
        'hash' => '838f8c7ecde71e82b998d9ff0f915a688007dfad',
    ],
    [
        'code' => 63433,
        'name' => 'CrazyTooth_Aureus.signature',
        'hash' => '1c4d75a8dedc22b0342130127ab1d76e73a1b948',
    ],
    [
        'code' => 64278,
        'name' => '8GoldenSkullsOfHollyRogerMegaWays.signature',
        'hash' => 'eddc328209914bb5ede76bd13d6fcfbdf409f8fa',
    ],
    [
        'code' => 64277,
        'name' => 'SpinPlayGames_9BlazingDiamonds.signature',
        'hash' => '6c9db417fb008af5b1b2c97b098bafb87dfb436d',
    ],
    [
        'code' => 56271,
        'name' => 'TerminatorII_97.signature',
        'hash' => 'ef513a9c29aee5ce42e53cb3b4a6fb0f11fb35fa',
    ],
    [
        'code' => 64280,
        'name' => 'ForgottenIsland_94.signature',
        'hash' => '0b987d258715cfade52dc8036d05d7680ec1493e',
    ],
    [
        'code' => 63424,
        'name' => 'CrazyTooth_QueenOfTheCrystalRays.signature',
        'hash' => '65457c032ec3d97da9cd32b5c3e527d92deb9d07',
    ],
    [
        'code' => 63448,
        'name' => 'Foxium_Foxpot.signature',
        'hash' => '7c8f8714bc8354560bf83814f4ac0a5ca44d1cf9',
    ],
    [
        'code' => 63464,
        'name' => 'GongGaming_PiratesQuest.signature',
        'hash' => '8caecff855f3c2871b146ccc1ec450a2fba0829e',
    ],
    [
        'code' => 63635,
        'name' => 'NorthernLightsGaming_GoldenStallion.signature',
        'hash' => 'd7a70235fc996e56d27fc25fb9d9ec208b48e4ed',
    ],
    [
        'code' => 63636,
        'name' => 'EaglesWings_96.signature',
        'hash' => '650a1d561976c2384f16a061da57178cf633be72',
    ],
    [
        'code' => 63654,
        'name' => 'RealDealerEuropeanRoulette.signature',
        'hash' => '85d68bfda237095af05db61c25af475f38e7a22d',
    ],
    [
        'code' => 63655,
        'name' => 'RealDealerEuropeanRoulette.signature',
        'hash' => '85d68bfda237095af05db61c25af475f38e7a22d',
    ],
    [
        'code' => 63656,
        'name' => 'RealDealerEuropeanRoulette.signature',
        'hash' => '85d68bfda237095af05db61c25af475f38e7a22d',
    ],
    [
        'code' => 63658,
        'name' => 'RealDealerEuropeanRoulette.signature',
        'hash' => '85d68bfda237095af05db61c25af475f38e7a22d',
    ],
    [
        'code' => 63664,
        'name' => 'SnowbornGames_TheBounty.signature',
        'hash' => 'febe0d76985339f54f86a35163ea062217749c96',
    ],
    [
        'code' => 113185,
        'cert_ver' => 1,
        'name' => '18762.signature',
        'hash' => '94f1a28306017987c6d572bbc6f2786973c48750',
    ],
    [
        'code' => 113192,
        'cert_ver' => 1,
        'name' => '100280.signature',
        'hash' => '774d20f1e896091a7eb45f7be3b82a881561c969',
    ],
    [
        'code' => 113207,
        'cert_ver' => 1,
        'name' => 'MahiGaming_AncientFortunesPoseidon_94.signature',
        'hash' => 'a35ab3555b7ddb1b235a1bde4dcf9f82640ce36b',
    ],
    [
        'code' => 113208,
        'cert_ver' => 1,
        'name' => '18770.signature',
        'hash' => '85bdbe7d6f417159ad9a124f819b2e858100af0d',
    ],
    [
        'code' => 113210,
        'cert_ver' => 1,
        'name' => '100472.signature',
        'hash' => 'd344ca24785f91719a1a4047bb68f2535a23636b',
    ],
    [
        'code' => 113211,
        'cert_ver' => 1,
        'name' => '100411.signature',
        'hash' => 'eb8035c2e9ec06db5de811d8f13106f7e19331e1',
    ],
    [
        'code' => 113213,
        'cert_ver' => 1,
        'name' => '100494.signature',
        'hash' => '3fb3c76012a659914a8a58dce70be1181ad2de0f',
    ],
    [
        'code' => 113217,
        'cert_ver' => 1,
        'name' => '100091.signature',
        'hash' => '4f85565b31f366178e4f49f3295ef6fc89acdb06',
    ],
    [
        'code' => 113219,
        'cert_ver' => 1,
        'name' => '22879.signature',
        'hash' => 'f26ef59e043fc226e77e288329a8e48f65eb94c0',
    ],
    [
        'code' => 113222,
        'cert_ver' => 1,
        'name' => 'MahiGaming_AncientFortunesPoseidon_94.signature',
        'hash' => 'a35ab3555b7ddb1b235a1bde4dcf9f82640ce36b',
    ],
    [
        'code' => 113230,
        'cert_ver' => 1,
        'name' => '100101.signature',
        'hash' => 'e3f6733dec02b988ccda38b11b2b0946b19a63f7',
    ],
    [
        'code' => 113233,
        'cert_ver' => 1,
        'name' => 'MahiGaming_AncientFortunesPoseidon_94.signature',
        'hash' => 'a35ab3555b7ddb1b235a1bde4dcf9f82640ce36b',
    ],
    [
        'code' => 113235,
        'cert_ver' => 1,
        'name' => '100376.signature',
        'hash' => '5345deee835890b2fda28d52d49dc12c4daf41d0',
    ],
    [
        'code' => 113239,
        'cert_ver' => 1,
        'name' => '18740.signature',
        'hash' => '297618bf3ea00863280d45f40b80ce31ac4a5e6f',
    ],
    [
        'code' => 113241,
        'cert_ver' => 1,
        'name' => '9MasksOfFire_96.signature',
        'hash' => '8325b10cf5eb065928a5f097d24bd419eb661419',
    ],
    [
        'code' => 113247,
        'cert_ver' => 1,
        'name' => '100357.signature',
        'hash' => '95ca59f7ee629311caa7775a12b6b4f025d10686',
    ],
    [
        'code' => 113249,
        'cert_ver' => 1,
        'name' => '22161.signature',
        'hash' => 'e7af2da55b6864fb6a330684bcaa3f8b773aa3f7',
    ],
    [
        'code' => 113253,
        'cert_ver' => 1,
        'name' => '100042.signature',
        'hash' => '3cb51e86f0df9c7b05ebb5b13901e862e9355be8',
    ],
    [
        'code' => 113254,
        'cert_ver' => 1,
        'name' => '100071.signature',
        'hash' => 'ee19c0d2be25c0cabfb2a1a90e3646c6fc0e1c10',
    ],
    [
        'code' => 113257,
        'cert_ver' => 1,
        'name' => '18746.signature',
        'hash' => '9b319b14280d29c9add9e8d88135bf8e6b1f45b6',
    ],
    [
        'code' => 113265,
        'cert_ver' => 1,
        'name' => '18762.signature',
        'hash' => '94f1a28306017987c6d572bbc6f2786973c48750',
    ],
    [
        'code' => 113267,
        'cert_ver' => 1,
        'name' => '18811.signature',
        'hash' => 'f9fb1ae4d2c83678108b1826b2bed64e9baff5d3',
    ],
    [
        'code' => 113270,
        'cert_ver' => 1,
        'name' => '100149.signature',
        'hash' => '9a22363ce665abcb56c06a293cd2beb9afdc39ac',
    ],
    [
        'code' => 113273,
        'cert_ver' => 1,
        'name' => '100044.signature',
        'hash' => 'dcd8ff1697287f9aaa3ea623c389716204fad4b3',
    ],
    [
        'code' => 113275,
        'cert_ver' => 1,
        'name' => '18682.signature',
        'hash' => '6445fdeb182193713204e0b84e6233d05f72209b',
    ],
    [
        'code' => 113277,
        'cert_ver' => 1,
        'name' => '25000Talons_94.signature',
        'hash' => '2bd986eaf6648d32ba1cacf30f7d72c4c04e3a11',
    ],
    [
        'code' => 113279,
        'cert_ver' => 1,
        'name' => '10984.signature',
        'hash' => '4749524a58c3f55ed50916791958cdcbd272a0d1',
    ],
    [
        'code' => 113281,
        'cert_ver' => 1,
        'name' => '18756.signature',
        'hash' => 'c0f655a32486269719d145381df97a3900e0d42a',
    ],
    [
        'code' => 113283,
        'cert_ver' => 1,
        'name' => '18778.signature',
        'hash' => '34cfcefee90f0005e382deeeedd61817cdb3815f',
    ],
    [
        'code' => 113285,
        'cert_ver' => 1,
        'name' => '18821.signature',
        'hash' => 'c9127d91c49252503a52b5f8644ec4d78a6afc8f',
    ],
    [
        'code' => 85292,
        'cert_ver' => 1,
        'name' => '18770.signature',
        'hash' => '85bdbe7d6f417159ad9a124f819b2e858100af0d',
    ],
    [
        'code' => 113292,
        'cert_ver' => 1,
        'name' => 'ImmortalRomance_97.signature',
        'hash' => '5bfe25e41fd609734f21f038bb8bac2c2784e69a',
    ],
    [
        'code' => 113293,
        'cert_ver' => 1,
        'name' => '18795.signature',
        'hash' => '1edb43ef951a9ea3fc565fdbedd82d485fc59121',
    ],
    [
        'code' => 113295,
        'cert_ver' => 1,
        'name' => '22060.signature',
        'hash' => '36d356f6d55d297422f8661355c59563937fc917',
    ],
    [
        'code' => 113297,
        'cert_ver' => 1,
        'name' => '100024.signature',
        'hash' => 'c7dd0d27f0f13009eba106e60f4ebe547fcd1dbc',
    ],
    [
        'code' => 113299,
        'cert_ver' => 1,
        'name' => '100372.signature',
        'hash' => 'e48ca9b0a8a05bd2b03ba2859d6a8c74982eabf7',
    ],
    [
        'code' => 113301,
        'cert_ver' => 1,
        'name' => '100265.signature',
        'hash' => '02722b663b0e355d43c05e6a3bfb6491bc55515c',
    ],
    [
        'code' => 113303,
        'cert_ver' => 1,
        'name' => '22728.signature',
        'hash' => '4d849048a8a1bae7cb639bfd3168421837c43ee4',
    ],
    [
        'code' => 113305,
        'cert_ver' => 1,
        'name' => '100197.signature',
        'hash' => '3c503f36f8bed49c0521eb0b22d6300da7ffda89',
    ],
    [
        'code' => 85296,
        'cert_ver' => 1,
        'name' => '22728.signature',
        'hash' => '4d849048a8a1bae7cb639bfd3168421837c43ee4',
    ],
    [
        'code' => 113309,
        'cert_ver' => 1,
        'name' => '100170.signature',
        'hash' => '7397fb60bcfcfba476b3eae2a92e9c4c11503a82',
    ],
    [
        'code' => 113311,
        'cert_ver' => 1,
        'name' => '18756.signature',
        'hash' => 'c0f655a32486269719d145381df97a3900e0d42a',
    ],
    [
        'code' => 113314,
        'cert_ver' => 1,
        'name' => 'AgentJaneBlondeMaxVolume_94.signature',
        'hash' => '635a4f1eecabeb2be14c8512389d75cb1f95fa3e',
    ],
    [
        'code' => 113317,
        'cert_ver' => 1,
        'name' => '100103.signature',
        'hash' => '7196fd4ac3eea25c881a7a0d791d9ab51a316f96',
    ],
    [
        'code' => 113318,
        'cert_ver' => 1,
        'name' => 'MahiGaming_NineMasksOfFire_94.signature',
        'hash' => 'b0d31582127e058d4a5fe1df465f7a0b063f9154',
    ],
    [
        'code' => 113328,
        'cert_ver' => 1,
        'name' => '100268.signature',
        'hash' => 'd28ab6cf3d4ae380bfb1e585cf0c53ac06781015',
    ],
    [
        'code' => 113330,
        'cert_ver' => 1,
        'name' => '100191.signature',
        'hash' => '1240bed8cc92029940df10a6683eea0f92222e1c',
    ],
    [
        'code' => 113332,
        'cert_ver' => 1,
        'name' => 'QueenOfAlexandria_94.signature',
        'hash' => '3d64847bfbe8f2eb0e2dd97a93adf16ce24c6afd',
    ],
    [
        'code' => 113334,
        'cert_ver' => 1,
        'name' => '18790.signature',
        'hash' => '7198b319be5a30e9261dbfb4dd53021d98f65c70',
    ],
    [
        'code' => 113335,
        'cert_ver' => 1,
        'name' => '100156.signature',
        'hash' => '524b7b8bb72c707a70a15d259ad11037308cf37f',
    ],
    [
        'code' => 113336,
        'cert_ver' => 1,
        'name' => '100262.signature',
        'hash' => '239cb7d7c04fee614a1350abcf254a8728f5b8b9',
    ],
    [
        'code' => 113337,
        'cert_ver' => 1,
        'name' => '11184.signature',
        'hash' => '562b85801f9f08bf83f85d10f6bf8e993c4e651d',
    ],
    [
        'code' => 113339,
        'cert_ver' => 1,
        'name' => '100010.signature',
        'hash' => '00fd1c7ee8a29836d5757c8c23f59e70df5bf3ec',
    ],
    [
        'code' => 113355,
        'cert_ver' => 1,
        'name' => '100135.signature',
        'hash' => '269f9ba1e77e427f4efa00e48372047f55488565',
    ],
    [
        'code' => 113357,
        'cert_ver' => 1,
        'name' => '100000.signature',
        'hash' => 'bf1f64accf48ad9547f6a6fc22fe5c057240c69e',
    ],
    [
        'code' => 113359,
        'cert_ver' => 1,
        'name' => '100034.signature',
        'hash' => 'e35890a41c92731874f00898f4c9f09166716b05',
    ],
    [
        'code' => 113366,
        'cert_ver' => 1,
        'name' => 'JFTW_AdventuresofCaptainBlackjack_96.signature',
        'hash' => 'ae9ef907f9aee4c340625b93800f8f47f75054f2',
    ],
    [
        'code' => 113367,
        'cert_ver' => 1,
        'name' => '11184.signature',
        'hash' => '562b85801f9f08bf83f85d10f6bf8e993c4e651d',
    ],
    [
        'code' => 113368,
        'cert_ver' => 1,
        'name' => '18799.signature',
        'hash' => '0924eca54473772d0499e92a7288d14274b5a2b5',
    ],
    [
        'code' => 113369,
        'cert_ver' => 1,
        'name' => '100085.signature',
        'hash' => '9283bf0aa36804ee4621c020290f22ef6fc244e2',
    ],
    [
        'code' => 113370,
        'cert_ver' => 1,
        'name' => '22520.signature',
        'hash' => 'dcdd0f9835f70fadd203f27651e5a709b8c68e1d',
    ],
    [
        'code' => 113371,
        'cert_ver' => 1,
        'name' => '100450.signature',
        'hash' => '19ff21951a419d6ef8b75a3fed6f83322085062f',
    ],
    [
        'code' => 113373,
        'cert_ver' => 1,
        'name' => '100311.signature',
        'hash' => '65b498a3b66213541f29a8ee1aaf1bf91eac187c',
    ],
    [
        'code' => 113374,
        'cert_ver' => 1,
        'name' => 'JFTW_TheBanditAndTheBaron_96.signature',
        'hash' => 'a68a2dac24a566e9e3e35a301aa5f28cdb26b0d8',
    ],
    [
        'code' => 113375,
        'cert_ver' => 1,
        'name' => '100085.signature',
        'hash' => '9283bf0aa36804ee4621c020290f22ef6fc244e2',
    ],
    [
        'code' => 113376,
        'cert_ver' => 1,
        'name' => '100525.signature',
        'hash' => 'ab11fcfe438db7200cd6addb6c85742cf11d4bd3',
    ],
    [
        'code' => 113377,
        'cert_ver' => 1,
        'name' => '18787.signature',
        'hash' => '371957165f90e349f8fd140c68549e90558fa7d8',
    ],
    [
        'code' => 113380,
        'cert_ver' => 1,
        'name' => 'SpinPlayGames_DiamondKingJackpots.signature',
        'hash' => '838f8c7ecde71e82b998d9ff0f915a688007dfad',
    ],
    [
        'code' => 113385,
        'cert_ver' => 1,
        'name' => '100127.signature',
        'hash' => '0cf3f743ae93ee6463b448aac24df6be891dc0a8',
    ],
    [
        'code' => 63440,
        'cert_ver' => 1,
        'name' => 'AmberSterlingsMysticShrine_96.signature',
        'hash' => '7321a1d4af6f80bbce2ea908e809e9cb9a6cd307',
    ],
    [
        'code' => 56347,
        'cert_ver' => 1,
        'name' => 'JFTW_WesternGold.signature',
        'hash' => '381dbffd1fa453f3b1dc0e4e97ab23fcf68e9514',
    ],
    [
        'code' => 113271,
        'cert_ver' => 1,
        'name' => '100121.signature',
        'hash' => 'ab258e4e7f00ccd92c671ee1bb7b663dfe2cc39e',
    ],
    [
        'code' => 113251,
        'cert_ver' => 1,
        'name' => '100041.signature',
        'hash' => 'ca00c94df972296a9cb887faec75a010d5399a98',
    ],
    [
        'code' => 113237,
        'cert_ver' => 1,
        'name' => '100105.signature',
        'hash' => '0747d7b06f35879cc6347d6e58d059843de4d93a',
    ],
    [
        'code' => 113215,
        'cert_ver' => 1,
        'name' => '100130.signature',
        'hash' => 'c5089c6bf3c1492e2439e6aed4f7f89b77213abe',
    ],
    [
        'code' => 113223,
        'cert_ver' => 1,
        'name' => '100213.signature',
        'hash' => '8a83c44a5496723de7763a99a50e9d0d2417576c',
    ],
    [
        'code' => 113288,
        'cert_ver' => 1,
        'name' => '22670.signature',
        'hash' => '492232165286a6c23401b0b3be30a43a5038e757',
    ],
    [
        'code' => 113319,
        'cert_ver' => 1,
        'name' => 'Foxium_theGreatAlbini2.signature',
        'hash' => 'b2fdc06d8177548cf480da7f97291d97412342a0',
    ],
    [
        'code' => 113383,
        'cert_ver' => 1,
        'name' => '100284.signature',
        'hash' => '4e0245d0b7d74133a53ddb79c71d9e9cc05969f8',
    ],
    [
        'code' => 113378,
        'cert_ver' => 1,
        'name' => '100078.signature',
        'hash' => 'a6c72391b2cbcecfc29f50f4022d248d4d7538f1',
    ],
    [
        'code' => 113372,
        'cert_ver' => 1,
        'name' => '22404.signature',
        'hash' => '78ea44882d80e07e35bbfcd5383b80b5e142d2d1',
    ],
    [
        'code' => 113360,
        'cert_ver' => 1,
        'name' => '100123.signature',
        'hash' => 'b63cab18e4d551ce45c162e9bb067ad1a368122d',
    ],
    [
        'code' => 113362,
        'cert_ver' => 1,
        'name' => '100039.signature',
        'hash' => '4d9025fc2cc82dced9bb971a1cc0161efa03e122',
    ],
    [
        'code' => 113365,
        'cert_ver' => 1,
        'name' => '22652.signature',
        'hash' => '896284f7b5af8ba69648b4e128285ce9f02c9840',
    ],
    [
        'code' => 113358,
        'cert_ver' => 1,
        'name' => '100210.signature',
        'hash' => 'b1ba0b6f8d683c8d51f0741cd89f242173ffb160',
    ],
    [
        'code' => 113356,
        'cert_ver' => 1,
        'name' => '22331.signature',
        'hash' => 'b65ed10bcd9e09ffa94a3bc04d03134f80fafc7d',
    ],
    [
        'code' => 113338,
        'cert_ver' => 1,
        'name' => '100082.signature',
        'hash' => '1489de938552461f52c417d2b80eea6f23264c7c',
    ],
    [
        'code' => 113361,
        'cert_ver' => 1,
        'name' => 'GoldMineStacksV94.signature',
        'hash' => '95483158f25ce8d20ddcce658b3af8d397af3ce3',
    ],
    [
        'code' => 113379,
        'cert_ver' => 1,
        'name' => 'TigersIceV94.signature',
        'hash' => '3e05d580c0028596b3491164341ad970cafcd5aa',
    ],
    [
        'code' => 113387,
        'cert_ver' => 1,
        'name' => 'ArkOfRa.signature',
        'hash' => 'e8ecdfa190dc5c1c8997e705872dbe2e485b67fd',
    ],
    [
        'code' => 113381,
        'cert_ver' => 1,
        'name' => 'BoltXUpV94.signature',
        'hash' => 'dbfcdf03704cf5e437e00ebab333f75db9702372',
    ],
    [
        'code' => 113316,
        'cert_ver' => 1,
        'name' => 'BoltXUpV94.signature',
        'hash' => 'dbfcdf03704cf5e437e00ebab333f75db9702372',
    ],
    [
        'code' => 113226,
        'cert_ver' => 1,
        'name' => 'ThunderstruckStormchaserV94.signature',
        'hash' => 'f8f61cc7ef441910292b961e3231b060a60341b8',
    ],
    [
        'code' => 113327,
        'cert_ver' => 1,
        'name' => 'JurassicParkGold_94.signature',
        'hash' => '39639c35aadb723db14150c29e8693e003ab5d86',
    ],
    [
        'code' => 113228,
        'cert_ver' => 1,
        'name' => 'BookOfFateV94.signature',
        'hash' => '00059f29b8ccfbe85b90b4de4c218b72ac557aed',
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
