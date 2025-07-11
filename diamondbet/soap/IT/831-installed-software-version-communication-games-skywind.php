<?php

require_once '/var/www/videoslots.it/phive/phive.php';
$user = cu($argv[1] ?? 6624618);

$games = [
    [
        'code' => '110168',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'scene.js',
                'hash' => '116308A135B6B9CAE5F1E3AC302C1DE4E009602C',
            ],
            [
                'name' => 'game.js',
                'hash' => '3D31CF79191CE8A0BB7501D07354E5B4BC5746FA',
            ],
            [
                'name' => 'currencyCalculator.js',
                'hash' => '4FBA83A9A6FCF1997A0AAA46260FA36E5871E7FB',
            ],
            [
                'name' => 'levelUpRule.js',
                'hash' => '586EE4C52127E56ABD6CE5A43C31C0E1CA54B0CF',
            ],
            [
                'name' => 'levelReelsSetsAdapter.js',
                'hash' => '7DA1B4083EF78A3AED9FD14F2DFA4AC9AF96360A',
            ],
            [
                'name' => 'freeGamesSlotSceneBehavior.js',
                'hash' => '8A48F3DCE6BA2B17210310F3A8EF9A723F3A2E59',
            ],
            [
                'name' => 'slotScene.js',
                'hash' => '99A429104416AE05672DEE0686873D91DD3C5D3C',
            ],
            [
                'name' => 'ScatterRule.js',
                'hash' => '9C64F8A12148A3E273DE1B46D2A83063D2D8A211',
            ],
            [
                'name' => 'gameManager.js',
                'hash' => 'A88043E4043FC9BF8A72DDE0FA3E1F27930685B7',
            ],
            [
                'name' => 'blankReplacement.js',
                'hash' => 'B406FCD9B4530585C54ADBEAF6C242001E621AD7',
            ],
            [
                'name' => 'game.js(core)',
                'hash' => 'B75F41F84FF66E026AA6BBDB08B4F6A17C8D259C',
            ],
            [
                'name' => 'freeSpinsAdapters.js',
                'hash' => 'BD3ECB03FD7B162B20C9FB51057894BD803682A8',
            ],
            [
                'name' => 'slotUtils.js',
                'hash' => 'BF65F0ADEF23408CE24E33104D02F32E4C9BF73C',
            ],
            [
                'name' => 'wildLineWinCalculator.js',
                'hash' => 'D0FE378DF95B33E26C21773AF6257BF854EDAE27',
            ],
            [
                'name' => 'freeSpinsStartRule.js',
                'hash' => 'F32785428B93666F604279E4809D94BF440CFD52',
            ],
            [
                'name' => 'blankSymbolRule.js',
                'hash' => 'F512C667802C8E8E5D3C139E383AD0C668D75C3D',
            ],
        ]
    ]
    ,
    [
        'code' => '110169',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'bonus.js',
                'hash' => '323E4F062131E4B16A7AA4E78FA209ADB3047020',
            ],
            [
                'name' => 'game.js',
                'hash' => 'BE379ED6B3A750D9617D6F74B0979DBF17F1E061',
            ],
            [
                'name' => 'definitions.js',
                'hash' => 'EDA5954C170134260249676B56856B736CEE7179',
            ],
        ]
    ]
    ,
    [
        'code' => '114760',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'game.js',
                'hash' => '7CAE8989B34F9C3F0F225D51EFC1716901F5CDD8',
            ],
            [
                'name' => 'weights.js',
                'hash' => '81407C14C9AD10368E1E0F18281EC35686525E37',
            ],
            [
                'name' => 'reelSets.js',
                'hash' => 'A2967AD5EC2779F3165FDBAC04E8EBD70599F01F',
            ],
            [
                'name' => 'game.js(sw-slot-game-core)',
                'hash' => 'B75F41F84FF66E026AA6BBDB08B4F6A17C8D259C',
            ],
            [
                'name' => 'constant.js',
                'hash' => 'EDEC415ECB3B9C0BB45AFAD71E6B285C5E26945F',
            ],
        ]
    ]
    ,
    [
        'code' => '114761',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'game.js',
                'hash' => '540AE711FB3A769CEA631A379478E4C9CDD21738',
            ],
            [
                'name' => 'reelSets.js',
                'hash' => '67D68E23E2F90620F1AE5A3E8627D0E577FDFE4A',
            ],
            [
                'name' => 'bonusRule.js',
                'hash' => 'A5841962EAFF07DB46B9270AAC9AFE14A5B95ADC',
            ],
            [
                'name' => 'regularSlotSceneBehavior.js',
                'hash' => 'CF2CC8A14BA87E7F56A8B3CA447FE91B74D460E1',
            ],
        ]
    ]
    ,
    [
        'code' => '114763',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'game.js',
                'hash' => '540AE711FB3A769CEA631A379478E4C9CDD21738',
            ],
            [
                'name' => 'reelSets.js',
                'hash' => '67D68E23E2F90620F1AE5A3E8627D0E577FDFE4A',
            ],
            [
                'name' => 'bonusRule.js',
                'hash' => 'A5841962EAFF07DB46B9270AAC9AFE14A5B95ADC',
            ],
            [
                'name' => 'regularSlotSceneBehavior.js',
                'hash' => 'CF2CC8A14BA87E7F56A8B3CA447FE91B74D460E1',
            ],
        ]
    ]
    ,
    [
        'code' => '114764',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'thunderbolt.js',
                'hash' => '08ccf3853e5f6db37502ee85d42fb30bab01ea49',
            ],
            [
                'name' => 'config.js',
                'hash' => '36a68b5fd25665b7b2e960e63b7c38b9a2c7b207',
            ],
            [
                'name' => 'flamingDynamite.js',
                'hash' => '475e8ea4ae69b02d2d6dbd486084aa60f45bc1d6',
            ],
            [
                'name' => 'featuresMapping.js',
                'hash' => '616346abcab5d8fbc076a784e845c0583e57c869',
            ],
            [
                'name' => 'game.js',
                'hash' => '63e644cc522a0aa212dca5f27b27b547e3783797',
            ],
            [
                'name' => 'eventsUtil.js',
                'hash' => '6f1123f74ec285e86922b84d78bf74a23885bd19',
            ],
            [
                'name' => 'hotLavaBall.js',
                'hash' => '7a5384ae0998b3c3f9fe894a5a81c882f2871873',
            ],
            [
                'name' => 'giantBomb.js',
                'hash' => '991f1335efd744802b769ad8a5b4e7c51f12169c',
            ],
            [
                'name' => 'magicFlusk.js',
                'hash' => '9bd356a5b4176b010c0275524e15113cd658d24d',
            ],
            [
                'name' => 'tornado.js',
                'hash' => 'b4d33594141e1da9641e18072c5725bbddb15cad',
            ],
            [
                'name' => 'game(slot-game-core).js',
                'hash' => 'b75f41f84ff66e026aa6bbdb08b4f6a17c8d259c',
            ],
            [
                'name' => 'paintBucket.js',
                'hash' => 'd30234abaadc5b8a393540cf07ff41a75115f7df',
            ],
            [
                'name' => 'superNova.js',
                'hash' => 'f5ed7e31c2ddf99919e84ae813a45162dd027b0c',
            ],
        ]
    ]
    ,
    [
        'code' => '114765',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'game.js',
                'hash' => '7CAE8989B34F9C3F0F225D51EFC1716901F5CDD8',
            ],
            [
                'name' => 'constant.js',
                'hash' => '9A358282AF81CADC62D5479E37F19E41A23ED345',
            ],
            [
                'name' => 'reelSets.js',
                'hash' => 'A2967AD5EC2779F3165FDBAC04E8EBD70599F01F',
            ],
            [
                'name' => 'weights.js',
                'hash' => 'AA2FAAB8D8A5F21AD0AA0E95F0F57CF53A87061F',
            ],
            [
                'name' => 'game.js(sw-slot-game-core)',
                'hash' => 'B75F41F84FF66E026AA6BBDB08B4F6A17C8D259C',
            ],
        ]
    ]
    ,
    [
        'code' => '114767',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'wayWinCalculator.js(sw-slot-game-core)',
                'hash' => '0FA6C4CFF618E6B8FAB8185E98BEBE6D4D1323C7',
            ],
            [
                'name' => 'scene.js',
                'hash' => '2BEFDC1B10CB98881D831A99FB0322FD77A076F2',
            ],
            [
                'name' => 'wayWinCalculator.js',
                'hash' => '3A24B24CB210CB8E883B4154F3EFCA1EFC3F7951',
            ],
            [
                'name' => 'sceneBehavior.js',
                'hash' => '435529DC0CD78811C690D5F36ECD2EDC34DB707A',
            ],
            [
                'name' => 'freespins.js',
                'hash' => '67EDF65EA224BE753D0A38510137654457FE171B',
            ],
            [
                'name' => 'reels.js',
                'hash' => '74CDC130BE191955E9950F03715475D94B3B8023',
            ],
            [
                'name' => 'reelSets.js',
                'hash' => '7ED665DF8BBEF637B90D3AEEE0047BA8722CE0A1',
            ],
            [
                'name' => 'sceneBehavior.js(sw-slot-game-core)',
                'hash' => '93D334B18538C056F62FC70163CBB3DBFC99A446',
            ],
            [
                'name' => 'freespins.js(sw-slot-game-core)',
                'hash' => 'CD6EF1C9C3258AAD68B66812CA51894DCAF5E676',
            ],
            [
                'name' => 'scatter.js',
                'hash' => 'E115173F9AF660C9EC6AB47B4BFDF70B4B67332A',
            ],
            [
                'name' => 'game.js',
                'hash' => 'F6E835493337CF358FCAB8999357C49E0F249FE8',
            ],
            [
                'name' => 'game.js(core)',
                'hash' => 'FC5021DBEE71D2F1171E6A5EB89E572CE43A0DA2',
            ],
        ]
    ]
    ,
    [
        'code' => '114768',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'reels.js',
                'hash' => '13D0D788D6CA165EB0478C43FA181704B95BB2EA',
            ],
            [
                'name' => 'sceneBehavior.js',
                'hash' => '16C87A42CD6B4B3A26A1E39611B8A4CCD1387799',
            ],
            [
                'name' => 'calculators.js',
                'hash' => '6705A0E46BA3C45AB2278879F9DA2EDCBDC67EB7',
            ],
            [
                'name' => 'game.js',
                'hash' => '6929F5E060588794CE85E8920341C49F14ACFCD5',
            ],
            [
                'name' => 'buyFeatureRule.js',
                'hash' => '6DC774200F013E6D8F2C1F536B3446F60B81940E',
            ],
            [
                'name' => 'constants.js',
                'hash' => '70CDA9B928D63D33E40038E55714ABD16D6F917C',
            ],
            [
                'name' => 'adapter.js',
                'hash' => '7E14DE619DC4BBB6E087FF00D74A08DD9EEEB5AC',
            ],
            [
                'name' => 'weights.js',
                'hash' => 'BB128F999A1D97DA03D5B11E269F9EBFD33874D9',
            ],
            [
                'name' => 'featureBehavior.js',
                'hash' => 'C94217106E197547A4996E09B55EF6F88D2FFB4B',
            ],
            [
                'name' => 'freespins.js',
                'hash' => 'E997EBC093F05421A5AA72D03116096371DAB40F',
            ],
        ]
    ]
    ,
    [
        'code' => '114769',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'respin.js',
                'hash' => '2e48d63d0c33a106cc7ae6a0e8ab54de6c7325bf',
            ],
            [
                'name' => 'lockCalculator.js',
                'hash' => '340ab4b4c9092eaa464b6e21acea83ef7755b6cd',
            ],
            [
                'name' => 'bonusStart.js',
                'hash' => '3861d3304d6e446de0a6828dc6a09a403947a197',
            ],
            [
                'name' => 'game.js',
                'hash' => '40648f0d3711c8275acd7847bc596bd496aa3d60',
            ],
            [
                'name' => 'reelSets.js',
                'hash' => '5d22db9deaa9e1a433ef5f9e8138ecfc2cc0883d',
            ],
            [
                'name' => 'respinAdapter.js',
                'hash' => '691ff29060d15c65a264628bfcfb433fa521d267',
            ],
            [
                'name' => 'bonusSelectionScene.js',
                'hash' => '7675ecd2c0fb7ff6434dc764e5a0e1091aef551d',
            ],
            [
                'name' => 'mainReels.js',
                'hash' => '8146d4de982754d28e27e6b7dcb37e0af8e014f3',
            ],
            [
                'name' => 'lineWinCalculator.js',
                'hash' => 'a1364a229ff406dda443edb0d8bb8dfb00ca1375',
            ],
            [
                'name' => 'avgWinAdapter.js',
                'hash' => 'a8fb898a62337a75836fc57bf45e8f92bf1eb1c1',
            ],
            [
                'name' => 'game.js(slot-game-core)',
                'hash' => 'b75f41f84ff66e026aa6bbdb08b4f6a17c8d259c',
            ],
            [
                'name' => 'bonusWeights.js',
                'hash' => 'e5ba31ee4c8833157b7753ecacb35d46f6d67633',
            ],
            [
                'name' => 'avgWinConfig.js',
                'hash' => 'f4f14e039e3f2aa425b0eb5ef131b683f24a4e6d',
            ],
            [
                'name' => 'lockReels.js',
                'hash' => 'ffdf711274143479265c4b895f8b308ebe182335',
            ],
        ]
    ]
    ,
    [
        'code' => '114770',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'winLineCalculator.js',
                'hash' => '0839E199459DD3614CB7FACE7973431BE4313F35',
            ],
            [
                'name' => 'weights.js',
                'hash' => '0C4EF8787FBF29C1CF0AFCA259154EEC432EEEE6',
            ],
            [
                'name' => 'replaceSymbolsWithBalls.js',
                'hash' => '0E3BAE86BBFEA1489094C66EC5B3CC61080D6177',
            ],
            [
                'name' => 'freeSpinsAddedRule.js',
                'hash' => '1B1958577E6AD2E1DC7F817BA853949072D0B31C',
            ],
            [
                'name' => 'bonusSelectionStartRule.js',
                'hash' => '724C3A8C42DA41BA225C532C4677CAAF6C1618E3',
            ],
            [
                'name' => 'game.js',
                'hash' => '8A1EB2D1AF5009E57F8BA5DC3620DAAF63B31C58',
            ],
            [
                'name' => 'rainBallsRule.js',
                'hash' => '9F04B652F4C958F61A2B5A5FFD2C02C1FA02EB01',
            ],
            [
                'name' => 'game.js(core)',
                'hash' => 'B75F41F84FF66E026AA6BBDB08B4F6A17C8D259C',
            ],
            [
                'name' => 'bonusSelectionScene.js',
                'hash' => 'C9216427847E5B85CFBEF9C932169F8553DB1E87',
            ],
            [
                'name' => 'wildFeature.js',
                'hash' => 'D5B1718F3A0F8F44ABB3F22BDD4AAD9FB4B8911E',
            ],
            [
                'name' => 'rainBallsFreeSpinsAdapter.js',
                'hash' => 'DA5D1B15B75CA33369D4E39047CBB96F3E2394AA',
            ],
            [
                'name' => 'reSpinAdapter.js',
                'hash' => 'DD97B44BE40B2F8AA13DDCF094EE8ED080B56B27',
            ],
            [
                'name' => 'scene.js',
                'hash' => 'F51976E9014935A7E61351E04DCBD0FD67444A56',
            ],
            [
                'name' => 'reelSets.js',
                'hash' => 'F5DE9AA1DFB7B7421356B5B69661113BFDB717B5',
            ],
            [
                'name' => 'freeSpinsStartRule.js',
                'hash' => 'FA84D5478B737B670350DB721E82EB0ED32D0F7B',
            ],
        ]
    ]
    ,
    [
        'code' => '114773',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'game.js',
                'hash' => '0C6B1654854A4B9E065505A47EBCCF168CBAF9A3',
            ],
            [
                'name' => 'scene.js',
                'hash' => '116308a135b6b9cae5f1e3ac302c1de4e009602c',
            ],
            [
                'name' => 'bonusSelectionStrategy.js',
                'hash' => '16182DCBB2DC2360DD6DD3BB6426C47F2BCBB54A',
            ],
            [
                'name' => 'reelSets.js',
                'hash' => '38427ee6b542d8a483518aeb8047eee142712972',
            ],
            [
                'name' => 'bonusRule.js',
                'hash' => '3D7BDAC99EF265D2DF1E5FFAA42ED73791FBFAC6',
            ],
            [
                'name' => 'mysteryWildRule.js',
                'hash' => '9a9848585de6cce82b370eacb153559f038177d0',
            ],
            [
                'name' => 'freeSpinsAdapters.js',
                'hash' => 'BD3ECB03FD7B162B20C9FB51057894BD803682A8',
            ],
            [
                'name' => 'freespins.js',
                'hash' => 'CD6EF1C9C3258AAD68B66812CA51894DCAF5E676',
            ],
            [
                'name' => 'blankReplacementRule.js',
                'hash' => 'E9A45B7E7D70F13851EE27ED59E2FB9B14D0B51E',
            ],
            [
                'name' => 'game.js(sw-slot-game-core)',
                'hash' => 'b75f41f84ff66e026aa6bbdb08b4f6a17c8d259c',
            ],
            [
                'name' => 'slotUtils.js',
                'hash' => 'bf65f0adef23408ce24e33104d02f32e4c9bf73c',
            ],
        ]
    ]
    ,
    [
        'code' => '114777',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'weights.js',
                'hash' => '17DFB032CF983214AA267DCCDE80662A8B7930C8',
            ],
            [
                'name' => 'expandRule.js',
                'hash' => '4B3D2D8605115C5F13FFBCEED5F1B59432C7CF39',
            ],
            [
                'name' => 'freeBehavior.js',
                'hash' => '4B456140DB2C357C145AD0DE7BDF0E3658E21025',
            ],
            [
                'name' => 'mainBehavior.js',
                'hash' => '536D67134B621A4AB7BFB7A7A69CF18B0F6189C7',
            ],
            [
                'name' => 'constant.js',
                'hash' => '54258FA01ADAB92F04753CD9C879109AF9C554B3',
            ],
            [
                'name' => 'freeSpinsStart.js',
                'hash' => '7D39C8D7A7571CB44BAF4970A58922CF3BBC9AA2',
            ],
            [
                'name' => 'reelSets.js',
                'hash' => '824E55C2EE22CBB62A54234687B941B2E69819DB',
            ],
            [
                'name' => 'wayCalculator.js',
                'hash' => 'AF4972AF7A85F0E25E2E534786EA7EF9640FE421',
            ],
            [
                'name' => 'buyFeatureRule.js',
                'hash' => 'DFA217CFF2738E635AD46CD74D6C8C96A7A5C0D2',
            ],
            [
                'name' => 'scene.js',
                'hash' => 'E0CA8D5E694B2A056ACF99BE16E7365C962CB9BD',
            ],
            [
                'name' => 'utils.js',
                'hash' => 'EBBFA5F6D6C4B276806CC9917B2242C8C982A538',
            ],
            [
                'name' => 'randomUtils.js',
                'hash' => 'F0900B8D40EEE4C80A336AA4B5AFCAA0FEDEA555',
            ],
        ]
    ]
    ,
    [
        'code' => '114778',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'regularSlotSceneBehavior.js',
                'hash' => '19CD3173AEB08460DDEAC119BE10A7425388AC66',
            ],
            [
                'name' => 'game.js',
                'hash' => '73C82F021EA325273063470DDC7C0C99E5047021',
            ],
            [
                'name' => 'reSpinSlotSceneBehavior.js',
                'hash' => '87C08B19EA1B5A5120C4078BEDEECFC96B0A908F',
            ],
            [
                'name' => 'reelDefinition.js',
                'hash' => '8E12964BA68F3C5C08B87B5A3FDB60855B0CE1CF',
            ],
            [
                'name' => 'reSpinStartRule.js',
                'hash' => 'A001A309299D5014044BBD7B3C4BB2F01DC15ED9',
            ],
            [
                'name' => 'definition.js',
                'hash' => 'A2727B040E4FB6A75DA8979004F73B1816FDB77D',
            ],
            [
                'name' => 'addWildRule.js',
                'hash' => 'AA8277242126E0BEC7CBB4B7929D401C09EBD27B',
            ],
            [
                'name' => 'game.js(core)',
                'hash' => 'B75F41F84FF66E026AA6BBDB08B4F6A17C8D259C',
            ],
            [
                'name' => 'wildFeature.js',
                'hash' => 'BB96BC30F132F13F87D60A6D71D3C99E7C445BB6',
            ],
            [
                'name' => 'reel.js',
                'hash' => 'C93E5DC5C3BCD3272B90CD491597F3998DC2C86A',
            ],
        ]
    ]
    ,
    [
        'code' => '114781',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'fullScreenWin.js',
                'hash' => '05ee2608d7cb2783bc471042a1fbb70b37e10f87',
            ],
            [
                'name' => 'multiplierRule.js',
                'hash' => '1057ac7a3456883b23763b8fba1835b120b68dae',
            ],
            [
                'name' => 'reelSets.js',
                'hash' => '195d7ba72b16dd156036a8df283323a7e7620612',
            ],
            [
                'name' => 'game.js(sw-getede-common)',
                'hash' => '25770821295cbbbeb6186ef6959ec47dffd49d6d',
            ],
            [
                'name' => 'reSpinRule.js',
                'hash' => '282cf25c15b3832a3fdc4f54d0b8672a7779aad2',
            ],
            [
                'name' => 'GTDFrozenSymbolsReSpinAdapter.js',
                'hash' => '35207073352d502634998390a6048d0eec665356',
            ],
            [
                'name' => 'baseGame.js',
                'hash' => '74d4707d603cf7f9b45cfb7a80b919acf9f271c0',
            ],
            [
                'name' => 'game.js',
                'hash' => '90bf51056617a3b22650fa30393e8f77e8555bde',
            ],
            [
                'name' => 'GTDReels.js',
                'hash' => '92784742828708861624117d11354605a2a828e3',
            ],
            [
                'name' => 'multipliers.js',
                'hash' => 'de7f7649d814d5a359a9149aa31b123ce1835300',
            ],
            [
                'name' => 'reSpinBehavior.js',
                'hash' => 'f35a6cfd9a7cd96a04bcee35baa295ce823fdde3',
            ],
        ]
    ]
    ,
    [
        'code' => '114782',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'freeSpins.js',
                'hash' => '2e83c8ac2742ab613fea67b35b41482f4c3e14fd',
            ],
            [
                'name' => 'reelsDefinition.js',
                'hash' => '37979c7e562b87a6cdfd57e43411b6ca0941f14f',
            ],
            [
                'name' => 'game.js',
                'hash' => '54a84eccbbf68c3aea3015523697d4fb6f723e32',
            ],
            [
                'name' => 'scene.js',
                'hash' => '8ac6b2fb3c73b41c8d9c55e89bea583b7b385a0f',
            ],
            [
                'name' => 'collapsingSlotSceneBehavior.js',
                'hash' => '8ae33d2ff2a8f24c6ad6f785725dd7021d0b967f',
            ],
            [
                'name' => 'freeGamesSlotSceneBehavior.js',
                'hash' => 'e9a343b40016ad8af95f3d1a0119d976b14fe269',
            ],
        ]
    ]
    ,
    [
        'code' => '114783',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'freeGameSlotSceneBehavior.js',
                'hash' => '1598347AB11CBCB3839AC2DE73EE3A484A7DC4D9',
            ],
            [
                'name' => 'game.js',
                'hash' => '2816FB3819CD9CE59431B048A01BE9A096E9E2EE',
            ],
            [
                'name' => 'freeSpinsStartRule.js',
                'hash' => '288E08E26153F8E9C90113C09778C4114DBA0241',
            ],
            [
                'name' => 'reels.js',
                'hash' => '2AD7B23AD6E6C9E879CBDBD5B3BEB06C900A538E',
            ],
            [
                'name' => 'switchToFeatureSceneAction.js',
                'hash' => '33B6BDB1C38E43223AA610FD54E247925A0ED1E0',
            ],
            [
                'name' => 'regularSlotSceneBehavior.js',
                'hash' => '3423766A7FBD74DE3A8CF58CD74023B589CD764D',
            ],
            [
                'name' => 'collapsingSlotSceneBehavior.js',
                'hash' => '3F781017629A840737E39223D346990EB99EA2BB',
            ],
            [
                'name' => 'payTable.js',
                'hash' => '42DDD4BC4AA26AEE8831AD62802008B89ADC0535',
            ],
            [
                'name' => 'reelsImpl.js',
                'hash' => '45F4B3C5BB1CE68117DE10CF51D2C7E0F8E9675B',
            ],
            [
                'name' => 'simulationManager.js',
                'hash' => '6336AD50F96CD53B6ACF2FA00EDEC91472CBB438',
            ],
            [
                'name' => 'buyFeatureRule.js',
                'hash' => '81116A921D448AF45900F5243DA2118DB2994CD9',
            ],
            [
                'name' => 'scatterRule.js',
                'hash' => '8344C723BB47E25F2852B49BC10DF7449FA00CA9',
            ],
            [
                'name' => 'freeSpinsAddRule.js',
                'hash' => '8C542D3A91410765ACFBCDD24726ACFBBB919108',
            ],
            [
                'name' => 'freeSpinsAdapter.js',
                'hash' => '9B919ED7EA8C353AC34CF366BC55323A374C4DA9',
            ],
            [
                'name' => 'weightedRandomReelsSetsAdapter.js',
                'hash' => 'BC0F07CB4D5BBD3D27F79855F5CAD7ABED0D6761',
            ],
            [
                'name' => 'symbols.js',
                'hash' => 'CBB918374DE3424081D567DD59AC571A52905DC7',
            ],
            [
                'name' => 'gameCommon.js',
                'hash' => 'CD36B168ED940C3401103513B625BFFB9752BB2C',
            ],
            [
                'name' => 'scatterRuleAdapter.js',
                'hash' => 'DC07010CE80D24C00FCB6ACEE23239375F161341',
            ],
            [
                'name' => 'scene.js',
                'hash' => 'DDBBD688AFF6E55E39393F00FF20AE30B9894124',
            ],
            [
                'name' => 'manager.js',
                'hash' => 'E5726EE41C52E3D41C8DFE0F81BDE9C02384F375',
            ],
            [
                'name' => 'config.js',
                'hash' => 'F0DB111A46BEF4155A6088BBA19FED43F904803A',
            ],
            [
                'name' => 'wayWinCalculator.js',
                'hash' => 'F9F5804E8811612277AAA81430264D7D471D1B14',
            ],
            [
                'name' => 'constants.js',
                'hash' => 'FA9076EFB51C9DD4EA685EDFACBCDD7633EFA28D',
            ],
        ]
    ]
    ,
    [
        'code' => '114784',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'game.js',
                'hash' => '028127c5ff814edea0f77505d19ddfd6a22491b6',
            ],
            [
                'name' => 'reelSets.js',
                'hash' => '03bc1969e8a1ce23626e3be2f914a19ba8541b77',
            ],
            [
                'name' => 'weights.js',
                'hash' => '0fd855830be6910179342a8a62f3dc1155b850cf',
            ],
            [
                'name' => 'scene.js',
                'hash' => '40b9086b449ef9761dd6610f174a7be482ffacaf',
            ],
            [
                'name' => 'behavior.js',
                'hash' => '54e0670667f9fdfab2d509cbbd110730ff15df71',
            ],
            [
                'name' => 'reel.js',
                'hash' => '56a73e4a68f1663f0246198e68e0005d3e3803ef',
            ],
            [
                'name' => 'replaceMultiplierRule.js',
                'hash' => '5fecc85fd61d0c5cdcec10e0c337f3f702f25261',
            ],
            [
                'name' => 'manager.js',
                'hash' => '817e0c8331c78d567baa2cf3144d7666b3e6641b',
            ],
            [
                'name' => 'calculator.js',
                'hash' => 'c00a0a1c5b0983ecfae59ba1faaa75683229ed05',
            ],
            [
                'name' => 'constant.js',
                'hash' => 'c6ecee5b16868e23135837aa1025acfda84de4d4',
            ],
            [
                'name' => 'game.js(core)',
                'hash' => 'f5c3a89d7e186af083fa6daceaf52b32c0f3d37c',
            ],
        ]
    ]
    ,
    [
        'code' => '114785',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'reelSets.js',
                'hash' => '06B9D2250C02725CB2DC73841D19254FF059FC64',
            ],
            [
                'name' => 'wantedPosterRule.js',
                'hash' => '14001F553AD886B5965FA8FAEADA2035E1F88359',
            ],
            [
                'name' => 'buyFeatureRule.js',
                'hash' => '1F39ED2D0B5E27CFAFA6BF9A9F2D269606E4A71F',
            ],
            [
                'name' => 'slotGame.js',
                'hash' => '304B0FC864DD351BDB9D48BB434542666583E5EB',
            ],
            [
                'name' => 'constants.js',
                'hash' => '43F2677C48E6D090AD1DAC45A49C87759CC8770D',
            ],
            [
                'name' => 'config.js',
                'hash' => '4D441E82E770246007CC9956FB5A0D9E978D28D8',
            ],
            [
                'name' => 'megawaysHeightsWeights.js',
                'hash' => '56D000B7E798D8CEF3CAE871884220E833127166',
            ],
            [
                'name' => 'columnWildRule.js',
                'hash' => '65BCA78A7D49E71E9D69E40BEE6E4295E83F3461',
            ],
            [
                'name' => 'reels.js',
                'hash' => '8E8EAA64DA33A04DCBDA94D27720062219E4E764',
            ],
            [
                'name' => 'collapseBehavior.js',
                'hash' => 'B93532BD25A53FBECAEAF36F7E45405BEE3E490A',
            ],
            [
                'name' => 'bonusScene.js',
                'hash' => 'C3FB3C6B5ABCA6852D8E99BA8765A21A7A29BC3C',
            ],
            [
                'name' => 'collapseAdapter.js',
                'hash' => 'C8F4799F076189D108B2E382C7C2E69B86F48012',
            ],
            [
                'name' => 'randomFeaturesRule.js',
                'hash' => 'D7763F96404E4E5AD84EB426FC5F31A35462C215',
            ],
            [
                'name' => 'wayWinCalculator.js',
                'hash' => 'E00FA9D44D506F33B840B932FAC9A0221D6F9FFB',
            ],
            [
                'name' => 'freeBehavior.js',
                'hash' => 'E623A56C4DE9EE1B8134B6DB71E7771119B631F8',
            ],
        ]
    ]
    ,
    [
        'code' => '114786',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'buyFeatureRule.js',
                'hash' => '0B9ABAC6E14CE1CDDF093B84FEC511644DB13FE0',
            ],
            [
                'name' => 'constats.js',
                'hash' => '179E9F2293E98D156110AFF0B566FD1A08580D0F',
            ],
            [
                'name' => 'game.js(sw_bbd_common)',
                'hash' => '1827EB53EC75D637C884FFA2F38CFE1355195DBB',
            ],
            [
                'name' => 'regularBehavior.js',
                'hash' => '2DB1A635DEBAFFCF0AC753C3D02993902D8B10C7',
            ],
            [
                'name' => 'manager.js',
                'hash' => '2F0B877F265E22AEF17A3844AA3E9CE99A9E7090',
            ],
            [
                'name' => 'scatter.js',
                'hash' => '533A0F222A9201A2A8CF353BED14A4BA676BA27D',
            ],
            [
                'name' => 'reelsAdapter.js',
                'hash' => '57D0E3618DC091AA7366CD9011346B9C6F28CB98',
            ],
            [
                'name' => 'flushClusterWinRule.js',
                'hash' => '5A253C7848024C0C9C3ADF09BB2D5859461862A7',
            ],
            [
                'name' => 'game.js',
                'hash' => '602343564DA8543BC580D0BA9B54E6A348DB1BF3',
            ],
            [
                'name' => 'reels.js',
                'hash' => '684EC5BC368E5C6625FCF81631DC52D25332094A',
            ],
            [
                'name' => 'utils.js',
                'hash' => '6BE67B63C6671D863170A75D1819DE63397673B6',
            ],
            [
                'name' => 'reel.js',
                'hash' => '748329164C1BD265C40B0FB457A03FF91A6218D8',
            ],
            [
                'name' => 'collapsingBehavior.js',
                'hash' => '77AE660EC9F9B734718C0169A05A6AF0EB88C0ED',
            ],
            [
                'name' => 'freespins.js',
                'hash' => '92984E00AED8BE1429B77C1A19681DBD9EA07917',
            ],
            [
                'name' => 'scene.js',
                'hash' => 'ACC1E625207968BD3D6A35B828F5C0FC6482A7FE',
            ],
            [
                'name' => 'handlers.js',
                'hash' => 'AEE8C1C0D51A3A93649AA336BB534471CEA82B60',
            ],
            [
                'name' => 'reelUtils.js',
                'hash' => 'B66EDAE489B6E4147CE7E1EC65A107E297219771',
            ],
            [
                'name' => 'freeGamesBehavior.js',
                'hash' => 'C3C6358C45AE6F2F0E5928BF4EE0936507482055',
            ],
            [
                'name' => 'respinAdapter.js',
                'hash' => 'CD205F11E304AE6B3716A76AA33B0324F485EB8F',
            ],
            [
                'name' => 'wilds.js',
                'hash' => 'EFDB8CB4B22A9B5F77CCBC427DFE1BD43AF5DB61',
            ],
            [
                'name' => 'cluster.js',
                'hash' => 'F6481C3E27BD60431976677DD3BFC89DC2C12C95',
            ],
            [
                'name' => 'reelSets.js',
                'hash' => 'F988AC21DFFC0D624E758626507ABCA301D6FBDA',
            ],
        ]
    ]
    ,
    [
        'code' => '114787',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'constants.js',
                'hash' => '2A030CB2D3E2E4924E9F51BE7FE9347A1D5970BE',
            ],
            [
                'name' => 'scatterRuleAdapter.js',
                'hash' => '37281285CEDA4611373CC8E1ED3C42D85105BBC7',
            ],
            [
                'name' => 'reelSets.js',
                'hash' => '3A126CA9C11A23B96B40A2F70C1299F32320F2CE',
            ],
            [
                'name' => 'freeGameScene.js',
                'hash' => '52173FB28AC682C8859D3E19E034594BC56049D0',
            ],
            [
                'name' => 'freeGamesSlotSceneBehavior.js',
                'hash' => '5287324A6687BA367982438DFAE7B39E0364BCA7',
            ],
            [
                'name' => 'freeGameSelectionScene.js',
                'hash' => '55F2AD58D125625CF93C4F0DA2B7400E0A09DD66',
            ],
            [
                'name' => 'selectionSceneRule.js',
                'hash' => '64EFB5E5E5CC8E6154981141BB4E77BE80EFD718',
            ],
            [
                'name' => 'bonusGameScene.js',
                'hash' => '7D005C68E809FE33FC8C54C75D83DC968D5DE973',
            ],
            [
                'name' => 'game.js',
                'hash' => '7F6AFF1DA94829134C9FE1AC9D5F97370C728C9C',
            ],
            [
                'name' => 'slotGame.js',
                'hash' => '85B2B407A9D8F8216CE56B63889A2C14F2B0FF0B',
            ],
            [
                'name' => 'managers.js',
                'hash' => '89BE4D28A948BB4F20806F3A5C395511C66BBD37',
            ],
            [
                'name' => 'freeSpinsRuleAdapter.js',
                'hash' => '9BE17246834F2F701F7EDCFD32CB55FDAA4397BD',
            ],
            [
                'name' => 'blankReplacementRule.js',
                'hash' => 'A49B0693DB1B82503B735E46046B7ADFF14E3AE4',
            ],
            [
                'name' => 'bonusSelectionStartRule.js',
                'hash' => 'D208C11C0ED22FD6D4EA63A136A37617FF49D6E6',
            ],
        ]
    ]
    ,
    [
        'code' => '114788',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'constants.js',
                'hash' => '3cbd871c3c085f2cb9bc4cbafbae0b329dc5a17c',
            ],
            [
                'name' => 'game.js',
                'hash' => '436f8055353e0cd34dbebb09fce47203e29db816',
            ],
            [
                'name' => 'respinStartRule.js',
                'hash' => '784a0511037913aba94d2e257ab1e53ac174fb4d',
            ],
            [
                'name' => 'superPirzeInputs.js',
                'hash' => '8d9965b4b2f1f4aa21c355dc3017840bdd243d6b',
            ],
            [
                'name' => 'superPrize.js',
                'hash' => '9c71eec1799e8e0f9da6c512574c542687f10d68',
            ],
            [
                'name' => 'scene.js',
                'hash' => 'b133d829583770d88a3888380895bd95b95d6bad',
            ],
            [
                'name' => 'reel.js',
                'hash' => 'c93e5dc5c3bcd3272b90cd491597f3998dc2c86a',
            ],
            [
                'name' => 'superWolfReSpinSlotSceneBehavior.js',
                'hash' => 'd415f7032b54f7e9b0e2ce587f962c141d978ab9',
            ],
            [
                'name' => 'reelSets.js',
                'hash' => 'f880143fe8dbcbb5396b4355b3a382439d34ece6',
            ],
        ]
    ]
    ,
    [
        'code' => '114790',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'game.js(core)',
                'hash' => '0068b7083db503d4924b5ab6359a05c633766c61',
            ],
            [
                'name' => 'game.js',
                'hash' => 'a4beda744660a19da50b950d7932df785a21a688',
            ],
            [
                'name' => 'reel.js',
                'hash' => 'c93e5dc5c3bcd3272b90cd491597f3998dc2c86a',
            ],
        ]
    ]
    ,
    [
        'code' => '114798',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'blankWeights.js',
                'hash' => '007da8943bf15ae827d5c252eb750363edfbd715',
            ],
            [
                'name' => 'game.js (sw_slm_common)',
                'hash' => '01da22eeb52acb4962fcf4c109773a9b6d6e82b1',
            ],
            [
                'name' => 'wildFeatureWeights.js',
                'hash' => '076871cebc5c16dbae77557e09a81699c56bf033',
            ],
            [
                'name' => 'scatterReplacement.js',
                'hash' => '0d4dff31bd4ef36042704d4f1d7439fef6e8bb4b',
            ],
            [
                'name' => 'gambleStart.js',
                'hash' => '115d0700d1a7bc7a97ed4ff26e0c680664f84b99',
            ],
            [
                'name' => 'bonus.js',
                'hash' => '1335b1e1e9d5e87e4589729a9de11ae1565bea33',
            ],
            [
                'name' => 'bonusSelectionStart.js',
                'hash' => '214deaeed4894185a10ef2088d354bfd27f4e3a8',
            ],
            [
                'name' => 'megawaysWeights.js',
                'hash' => '21650b084fb3761147f3b5b71010cb68042a4bcd',
            ],
            [
                'name' => 'scatterLevelWeights.js',
                'hash' => '4456faff077421000c263b2278f6339e6c9132d8',
            ],
            [
                'name' => 'scene.js',
                'hash' => '44f296e2ecf212035f8a8e6e2b79271d1e47949b',
            ],
            [
                'name' => 'blankReplacement.js',
                'hash' => '4525ae7b5aa46889c737d81356cd836c475f1fdd',
            ],
            [
                'name' => 'reels.js',
                'hash' => '5c47fe096e2cd82ca37c6aa210eca403401253d2',
            ],
            [
                'name' => 'game.js',
                'hash' => '73c9ed28debcefbafa0a3330f57e2e06ee03ee37',
            ],
            [
                'name' => 'mysteryWeights.js',
                'hash' => '773cd47ff4d110d64e47c5c88811ce88f11621a4',
            ],
            [
                'name' => 'feature.js',
                'hash' => '7fc22dc5cc7d945d1b0b4210071e07e653a591f4',
            ],
            [
                'name' => 'bonusSelectionAdapter.js',
                'hash' => '8656d8ef3a19564982f1f44fd3d49e20bc3ed89b',
            ],
            [
                'name' => 'superMissWeights.js',
                'hash' => '8a8b921f0df6a038de38233a9772534743ba6197',
            ],
            [
                'name' => 'predictorConfig.js',
                'hash' => '911b5ac61c1ce0b4f62fe014449d2535a93b5ac6',
            ],
            [
                'name' => 'ruleSettings.js',
                'hash' => 'ae94042246e44d649ece40fc7f1f875095226850',
            ],
            [
                'name' => 'blankReplacementAdapter.js',
                'hash' => 'b88a353996a92f24622b10cbcc445ccbd4d53062',
            ],
            [
                'name' => 'switchToFeatureSceneAction.js',
                'hash' => 'bae4d32dc0c479cfe999227ae0da68b79adb0d2e',
            ],
            [
                'name' => 'calcSettings.js',
                'hash' => 'c5bf7625307dff3edf7023df387aa6e88d72216d',
            ],
            [
                'name' => 'reelSetAdapter.js',
                'hash' => 'd0a1f50ccd6708f53be7ac4a1698ad81e8626497',
            ],
            [
                'name' => 'freeSpinsStart.js',
                'hash' => 'd461e65d934dc1f701c1535bee52832eb333c722',
            ],
            [
                'name' => 'constant.js',
                'hash' => 'd87df8aa699b77bb7669fecc2c58c0a1d3711d94',
            ],
            [
                'name' => 'predictorRule.js',
                'hash' => 'd93f4ab2e46f360c1d3c5c41ba52ef49c152ce72',
            ],
            [
                'name' => 'gamble.js',
                'hash' => 'dce894c5b7d8e8ad93982afb19d9e33fb5c059ea',
            ],
            [
                'name' => 'superSet.js',
                'hash' => 'e9329bf05635a7da1714f4343420bcf09d3bd913',
            ],
            [
                'name' => 'freeSpinsAdapter.js',
                'hash' => 'f22f1882058e8a65608c80bce00a1651db40bd5c',
            ],
        ]
    ]
    ,
    [
        'code' => '114800',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'scenario.js',
                'hash' => '056797462E23411E6B108EE3305DC39331AC63B4',
            ],
            [
                'name' => 'game.js',
                'hash' => '07E351846F5941F03E45207DBF1F3533758E3B08',
            ],
            [
                'name' => 'forceSpin.js',
                'hash' => '11373CB3E104E02F633A66AA9615E06CE5C2DE35',
            ],
            [
                'name' => 'respinsCalculator.js',
                'hash' => '12245E4CA7A00615805A65B612CB84D5F502C243',
            ],
            [
                'name' => 'constant.js',
                'hash' => '1C3F0FECEFAEA1E56B05C69C84EE70218A08D948',
            ],
            [
                'name' => 'freeStartRule.js',
                'hash' => '309879F8693714808B42BAC79F0557B9B7A9DC21',
            ],
            [
                'name' => 'wildExpandedRule.js',
                'hash' => '3289D50FF9C65FFB09657371F226B82D401F15AC',
            ],
            [
                'name' => 'switchToFeatureSceneAction.js',
                'hash' => '3984FDE24715DE772313764CBD98631184C63935',
            ],
            [
                'name' => 'reelSets.js',
                'hash' => '554282396D65BC6FC323C79B4D8B3FD0E74DB830',
            ],
            [
                'name' => 'freeBehavior.js',
                'hash' => '662ADF0D1D7FD54B974C98A2A662C6A00CFBDDE4',
            ],
            [
                'name' => 'expWilds.js',
                'hash' => '684D3C184E89340548CB8C8797101E4A9B700127',
            ],
            [
                'name' => 'game.js(sw_cm_common)',
                'hash' => '687EEC2647F3FFC3ACF700B469E62F97550C3A0F',
            ],
            [
                'name' => 'freeAddRule.js',
                'hash' => '69C15B7DA3C40A63FA58E41B054FD2D2A9A7187E',
            ],
            [
                'name' => 'replaceRule.js',
                'hash' => '6C0D1F5B85388907E1799759D30CD4AAF08DB057',
            ],
            [
                'name' => 'frozenAdapter.js',
                'hash' => '6E6A4C4A43E86C43055BB193FC36A6CFDB3249BA',
            ],
            [
                'name' => 'respinBehavior.js',
                'hash' => '7025EC771A1EE2AFF421F056CA86D5082CA7AA0B',
            ],
            [
                'name' => 'config.js',
                'hash' => '796097F0CBC46946FBF6C78F79C12F9E2EAE723D',
            ],
            [
                'name' => 'reels.js',
                'hash' => '84A9B02309702AF84987760E20B49FE4C199E0D7',
            ],
            [
                'name' => 'calcSettings.js',
                'hash' => '8DC25CA622B6AAC2222BCB08BECAF258E1CD258D',
            ],
            [
                'name' => 'collectRule.js',
                'hash' => '97030E9EA70F6B523C68FD779F80FDF681EB0A6A',
            ],
            [
                'name' => 'ruleSettings.js',
                'hash' => '9E9445AC929B4C0A1D5713599518B4ED00F86289',
            ],
            [
                'name' => 'reelsAdapter.js',
                'hash' => 'B4A671C58BF6F3729755838822EF6932C5C868DA',
            ],
            [
                'name' => 'levelUpRule.js',
                'hash' => 'C7251AFD3C62583774F23C35B316A8D775991AED',
            ],
            [
                'name' => 'coinPrize.js',
                'hash' => 'DB840E295DAA36CC088A2A0587DBFC12D562B42A',
            ],
            [
                'name' => 'scene.js',
                'hash' => 'E33DECB470F1406D67C5C1D64E4809438D02BC50',
            ],
            [
                'name' => 'respinStartRule.js',
                'hash' => 'F7244BBF776AE61EB8D79C8624921753A4CA75A7',
            ],
        ]
    ]
    ,
    [
        'code' => '138086',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'lineCalculator.js',
                'hash' => '09D9316BCBCC4E5B48FAE283427B120FBCF06A2F',
            ],
            [
                'name' => 'freeSpinsRule.js',
                'hash' => '0B0092EB7C1B6CDABFF4EC57B9F4A3E7F0B07564',
            ],
            [
                'name' => 'slotGame.js',
                'hash' => '0FACE3C5E41D454690EA9C1CCCACE109059B5E08',
            ],
            [
                'name' => 'scatterRule.js',
                'hash' => '25AA7302B3FEBE6C809C9889B20E67FDAC02AB59',
            ],
            [
                'name' => 'buyFeature2Config.js',
                'hash' => '2B9EF0F5CE39249942FDABFF909873889709328A',
            ],
            [
                'name' => 'freeReelSets.js',
                'hash' => '3CE277223B4E0979D57B1A2F45ABBE4D1F0B646F',
            ],
            [
                'name' => 'expandingSymbolWeights.js',
                'hash' => '4D899EF117C7036BF6895218EF16D1AD601E82F2',
            ],
            [
                'name' => 'expandingSymbolLineCalculator.js',
                'hash' => '59CC86E10AA551A664659E8CD4B692D3DDEE1A33',
            ],
            [
                'name' => 'expandingSymbolRule.js',
                'hash' => '6DC5CB5211684A99FAC02CEEAFBBAE48B2685BAF',
            ],
            [
                'name' => 'game.js',
                'hash' => '772AD6BD4FFD93CB7C85E5280ED63AA5723D0628',
            ],
            [
                'name' => 'buyFeature3Config.js',
                'hash' => '77BA2E597B0F4A63697F16295739C846CDBD6F33',
            ],
            [
                'name' => 'constants.js',
                'hash' => '93B53072FE9EF08F026F40AC0A0CB8365E693756',
            ],
            [
                'name' => 'mainReelSets.js',
                'hash' => '9FADD9D4B727B5C50DF805053E3ADFEF6054830D',
            ],
            [
                'name' => 'buyFeatureRule.js',
                'hash' => 'A12D7AE5C7C37779A0A424068261A69A404924AF',
            ],
            [
                'name' => 'reelToWildReplacement.js',
                'hash' => 'AF42E274A5110A35738C0CB3EC4829529CB9B506',
            ],
            [
                'name' => 'freeReelSetAdapter.js',
                'hash' => 'C8AF69C485C50F80C61E55439C4C181761BD87CD',
            ],
            [
                'name' => 'buyFeature1Config.js',
                'hash' => 'F00FB260B51CC413B9EEBE14DA7E90D45204F3CF',
            ],
            [
                'name' => 'switchToFeatureSceneAction.js',
                'hash' => 'F1114F448D1063962965867DE9B7A4E543F923BE',
            ],
        ]
    ]
    ,
    [
        'code' => '138088',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'scene.js',
                'hash' => '00CFCD1773B188ADE8598EB9B535B594615C5F3E',
            ],
            [
                'name' => 'config.js(sw-ffb-common)',
                'hash' => '0A545B18DEC0A319C1E042A9AEEED39C807C0CBA',
            ],
            [
                'name' => 'weights.js',
                'hash' => '14DE4388F9D68936CEDEB653448F1FF674F4E623',
            ],
            [
                'name' => 'replaceBlanksRule.js',
                'hash' => '164D21E931DD2B0CABD6D469B0D5A2B893EA5A38',
            ],
            [
                'name' => 'freeSpinsStartRule.js',
                'hash' => '2C46E77620913730F1933DD1BB5D30001515B8B8',
            ],
            [
                'name' => 'game.js',
                'hash' => '2F52AFB499C6A6286442FD0C0FFEEDF5CB6265F5',
            ],
            [
                'name' => 'collapsingBehavior.js',
                'hash' => '45AA57DAB2265F89D203B3672AA602D5D5CCF960',
            ],
            [
                'name' => 'calculator.js',
                'hash' => '5FAE502D524D2C90F3CBD2B87970932419B93CA4',
            ],
            [
                'name' => 'buyFeatureRule.js',
                'hash' => '68DFACCE82CB7C192FA27066E1B1109EE1981415',
            ],
            [
                'name' => 'constants.js',
                'hash' => '7035D75F2DA8946D58BE85E8C70137FF292FBAA4',
            ],
            [
                'name' => 'respinAdapter.js',
                'hash' => '7E54587DF1933896283BCF0CE619F9048F168C44',
            ],
            [
                'name' => 'freeReelSetAdapter.js',
                'hash' => 'ABFEB52370AFC6616EA9F1E434F1791114EA3232',
            ],
            [
                'name' => 'interfaces.js',
                'hash' => 'B66EDAE489B6E4147CE7E1EC65A107E297219771',
            ],
            [
                'name' => 'scatterRule.js',
                'hash' => 'B941B96ACB5F7C433D11F59559D534C44A0454DA',
            ],
            [
                'name' => 'fishRule.js',
                'hash' => 'C8529754DECF0EF58663389D1375AD27E1D89185',
            ],
            [
                'name' => 'config.js',
                'hash' => 'CED4661BE2288AE777DBC02CB7D0A0B7F2CF4295',
            ],
            [
                'name' => 'switchToFeatureSceneAction.js',
                'hash' => 'D23B82A44417A9C5222C44929FD44E7F27889E23',
            ],
            [
                'name' => 'slotGame.js',
                'hash' => 'DB16767025CD67994A3E1CF0AABAD9A232687D8E',
            ],
            [
                'name' => 'gameManager.js',
                'hash' => 'F4587BEBD623D1E553B2751A8556D5E8F30D614A',
            ],
    
        ]
    ]
    ,
    [
        'code' => '167557',
        'cert_ver' => '1',
        'software_modules' => [   
            [
                'name' => 'gambleScene.js',
                'hash' => '01081DCCDB71CEE9D1FB7D2D4808A40A0A104B8A',
            ],   
            [
                'name' => 'gambleStartRule.js',
                'hash' => '0F20CBBE774C6FB9FB6700B3E6D032F699872B15',
            ],   
            [
                'name' => 'featuresWeights.js',
                'hash' => '0FB49DC418315E5C40FA834F6EF5FDCCEC19B186',
            ],   
            [
                'name' => 'reels.js',
                'hash' => '264BBAC99E84D84DC253E584FBE7D37B5F37BCF1',
            ],   
            [
                'name' => 'reelSetAdapter.js',
                'hash' => '2B1E1857FEFBA4443E2C085CFC01A8D1D8031D7D',
            ],   
            [
                'name' => 'respinBehavior.js',
                'hash' => '2CB8AC65893DD031BACE3275F22209FCED25362E',
            ],   
            [
                'name' => 'respinRule.js',
                'hash' => '2D88AB08886DBA011816282C2B0409766F8BA2E1',
            ],   
            [
                'name' => 'slotGame.js',
                'hash' => '33124F78838811F070CF805C065D365CE6B94F80',
            ],   
            [
                'name' => 'calculator.js',
                'hash' => '365258B02409B751F8A182823AA701027758CDBE',
            ],   
            [
                'name' => 'frozenAdapter.js',
                'hash' => '3701D462E6D5D4E34ECCBC996157AF0154652300',
            ],   
            [
                'name' => 'scene.js',
                'hash' => '37C11DD40AD52277870940D99A2ECBA375C73465',
            ],   
            [
                'name' => 'constants.js',
                'hash' => '3B73B7D172E96CC12521AA46152A2A7F309CD183',
            ],   
            [
                'name' => 'config.js',
                'hash' => '4ACEC8D14C88D996E5B573FD4B518AE3C8D8E51F',
            ],   
            [
                'name' => 'manager.js',
                'hash' => '728583F1E516EE5FF3CD9C7E0B55981B207E4BDE',
            ],     
            [
                'name' => 'reelSets.js',
                'hash' => '772B8F4D35A437442F6F8D5915DE36174E9824D4',
            ],   
            [
                'name' => 'switchToFeatureSceneAction.js',
                'hash' => '78A588031656AB26C3C44E58229AFF69283FE156',
            ],   
            [
                'name' => 'regularBehavior.js',
                'hash' => '81F42C4EA3EA25F4A3AA0FE21B88F201105EFBB4',
            ],   
            [
                'name' => 'freeBehavior.js',
                'hash' => 'B65DDC992EDA93A66498E18C334F8D022C15D85B',
            ],   
            [
                'name' => 'selectionScene.js',
                'hash' => 'BC5BFFCA4D9DFB6F5046C8C28CE3C982EE39FAFE',
            ],   
            [
                'name' => 'selectionScreenStartRule.js',
                'hash' => 'CFA5C8F77B26BB096881B69858668CB3BC38F4FB',
            ],   
            [
                'name' => 'scatterRule.js',
                'hash' => 'DEE6C8239EFD388BB7518797692EBF95D3391EF0',
            ],   
            [
                'name' => 'game.js',
                'hash' => 'E04EA3E33D127428876FF940AAB8123DE992BD0D',
            ],   
            [
                'name' => 'freeSpinsStartRule.js',
                'hash' => 'E468E8A3ABE2FF810F5C02BE9363ABBDF67E4D1A',
            ],           
        ]
    ]
    ,
    [
        'code' => '108930',
         'cert_ver' => '1',
        'software_modules' => [   
            [
            'name' => 'modifiersConfigFree.js',
            'hash' => '0D50DF523254142E07B1C4DFFB6EF8538FB82D22',
            ],   
            [
            'name' => 'constants.js',
            'hash' => '0DE41B7C72D6D28CD42514CB6787A98226508DB1',
            ],   
            [
            'name' => 'freeSpinsRuIeAdapter.js',
            'hash' => '13879BE1 B87040C7CA761F05F02019B48722653',
            ],   
            [
            'name' => 'wayWinCaIcuIator.js',
            'hash' => '13F085574EF5332A6A0E3B2D38F5A1B970E2258A',
            ],   
            [
            'name' => 'checkModifierRuIe.js',
            'hash' => '1AB5E3A318B22E9D1D4A22DF9E3D0C72EB56D46A',
            ],   
            [
            'name' => 'reeISets.js',
            'hash' => '2F77B1486671C0AF3AA4B0B3013F88B177909DA4',
            ],   
            [
            'name' => 'appIyModifierRuIe.js',
            'hash' => '3564CE923803CFFDAE68517451 F838B5A2116D39',
            ],   
            [
            'name' => 'coIIapseAdapter.js',
            'hash' => '4091F8EAE 1158DC145FD4D5B60DBCB1C2B053C4F',
            ],   
            [
            'name' => 'coIIapseBehavior.js',
            'hash' => '50B3A8EBC2E63970718FC441E0CC720A4D3BF809',
            ],   
            [
            'name' => 'modifierRuIe.js',
            'hash' => '68D401827E7778CAB026B1FD20C9E6CCC3C7902A',
            ],   
            [
            'name' => 'topSymboIsWeights.js',
            'hash' => '71349C7504D9EC7A54C9D6A5606813AB6FDD787F',
            ],   
            [
            'name' => 'buyFeatureRuIe.js',
            'hash' => '94B4BF2177D0C0F1A753620CC8CD4B95A779A613',
            ],   
            [
            'name' => 'config.js',
            'hash' => 'A0ECE3C3CDA546D77B3E1949A24B32DB856BB883',
            ],   
            [
            'name' => 'megawaysHeightsWeights.js',
            'hash' => 'A431EA2D1B278B5C85582A6E6803CA5BBB72AC58',
            ],   
            [
            'name' => 'reels.js',
            'hash' => 'B5BCA882D54E221452B4017480213A1617E6A03A',
            ],   
            [
            'name' => 'modifiersConfigMain.js',
            'hash' => 'C3CDEB195FD8BC77E624F43650FE198FD5C69AF0',
            ],   
            [
            'name' => 'game.js',
            'hash' => 'CAA2554A78BCF2973F2951C7830C913A606116B6',
            ],   
            [
            'name' => 'game.js(sw_btbm_common)',
            'hash' => 'DEB21A7D154E38D9705A1BDAFB66D0CF4E7DBB96',
            ],
        ] 
        ]
        ,
        [
        'code' => '110170',
         'cert_ver' => '1',
        'software_modules' => [   
            [
            'name' => 'reel.js',
            'hash' => '153CF09056990F481BDDE86EC35F9402CB359F9E',
            ],   
            [
            'name' => 'reelSets.js',
            'hash' => '7ED665DF8BBEF637B90D3AEEE0047BA8722CE0A1',
            ],   
            [
            'name' => 'game.js',
            'hash' => '9B20D681A03BCBD9F8A8F1115E6D1637A7B4E852',
            ],   
            [
            'name' => 'game.js(core)',
            'hash' => 'CD1635C816B645A6C508CDFD3004CB2DE1ECEFB9',
            ],
        ]
    ]
    , 
    [
        'code' => '114760',
         'cert_ver' => '1',
        'software_modules' => [   
            [
            'name' => 'game.js',
            'hash' => '7CAE8989B34F9C3F0F225D51EFC1716901F5CDD8',
            ],   
            [
            'name' => 'weights.js',
            'hash' => '81407C14C9AD10368E1E0F18281EC35686525E37',
            ],   
            [
            'name' => 'reelSets.js',
            'hash' => 'A2967AD5EC2779F3165FDBAC04E8EBD70599F01F',
            ],   
            [
            'name' => 'game.js(sw-slot-game-core)',
            'hash' => 'B75F41F84FF66E026AA6BBDB08B4F6A17C8D259C',
            ],   
            [
            'name' => 'constant.js',
            'hash' => 'EDEC415ECB3B9C0BB45AFAD71E6B285C5E26945F',
            ],
        ]    
    ]
    ,
    [
        'code' => '114761',
         'cert_ver' => '1',
        'software_modules' => [   
            [
            'name' => 'game.js',
            'hash' => '540AE711FB3A769CEA631A379478E4C9CDD21738',
            ],   
            [
            'name' => 'reelSets.js',
            'hash' => '67D68E23E2F90620F1AE5A3E8627D0E577FDFE4A',
            ],   
            [
            'name' => 'bonusRule.js',
            'hash' => 'A5841962EAFF07DB46B9270AAC9AFE14A5B95ADC',
            ],   
            [
            'name' => 'regularSlotSceneBehavior.js',
            'hash' => 'CF2CC8A14BA87E7F56A8B3CA447FE91B74D460E1',
            ],
        ]
    ]
    ,
    [
        'code' => '114762',
         'cert_ver' => '1',
        'software_modules' => [   
            [
            'name' => 'jackpot.js',
            'hash' => '635982671FEE1AE907BD34DE2F11C3C231627EC8',
            ],   
            [
            'name' => 'reelSets.js',
            'hash' => '7ED665DF8BBEF637B90D3AEEE0047BA8722CE0A1',
            ],  
            [
            'name' => 'game.js',
            'hash' => 'FCD016FB3BD1B15D37DD9E463986E04C4B18C7CD',
            ],     
        ]
    ]
    , 
    [
        'code' => '114765',
         'cert_ver' => '1',
        'software_modules' => [   
            [
            'name' => 'game.js',
            'hash' => '7CAE8989B34F9C3F0F225D51EFC1716901F5CDD8',
            ],   
            [
            'name' => 'constant.js',
            'hash' => '9A358282AF81CADC62D5479E37F19E41A23ED345',
            ],  
            [
            'name' => 'reelSets.js',
            'hash' => 'A2967AD5EC2779F3165FDBAC04E8EBD70599F01F',
            ],   
            [
            'name' => 'weights.js',
            'hash' => 'AA2FAAB8D8A5F21AD0AA0E95F0F57CF53A87061F',
            ],   
            [
            'name' => 'game.js(sw-slot-game-core)',
            'hash' => 'B75F41F84FF66E026AA6BBDB08B4F6A17C8D259C',
            ],     
        ]
    ]
    , 
    [
        'code' => '114769',
         'cert_ver' => '1',
        'software_modules' => [   
            [
            'name' => 'respin.js',
            'hash' => '2E48D63D0C33A106CC7AE6A0E8AB54DE6C7325BF',
            ],   
            [
            'name' => 'lockCalculator.js',
            'hash' => '340AB4B4C9092EAA464B6E21ACEA83EF7755B6CD',
            ],   
            [
            'name' => 'bonusStart.js',
            'hash' => '3861D3304D6E446DE0A6828DC6A09A403947A197',
            ],   
            [
            'name' => 'game.js',
            'hash' => '40648F0D3711C8275ACD7847BC596BD496AA3D60',
            ],   
            [
            'name' => 'reelSets.js',
            'hash' => '5D22DB9DEAA9E1A433EF5F9E8138ECFC2CC0883D',
            ],   
            [
            'name' => 'respinAdapter.js',
            'hash' => '691FF29060D15C65A264628BFCFB433FA521D267',
            ],   
            [
            'name' => 'bonusSelectionScene.js',
            'hash' => '7675ECD2C0FB7FF6434DC764E5A0E1091AEF551D',
            ],   
            [
            'name' => 'mainReels.js',
            'hash' => '8146D4DE982754D28E27E6B7DCB37E0AF8E014F3',
            ],   
            [
            'name' => 'lineWinCalculator.js',
            'hash' => 'A1364A229FF406DDA443EDB0D8BB8DFB00CA1375',
            ],   
            [
            'name' => 'avgWinAdapter.js',
            'hash' => 'A8FB898A62337A75836FC57BF45E8F92BF1EB1C1',
            ],   
            [
            'name' => 'game.js(slot-game-core)',
            'hash' => 'B75F41F84FF66E026AA6BBDB08B4F6A17C8D259C',
            ],   
            [
            'name' => 'bonusWeights.js',
            'hash' => 'E5BA31EE4C8833157B7753ECACB35D46F6D67633',
            ],   
            [
            'name' => 'avgWinConfig.js',
            'hash' => 'F4F14E039E3F2AA425B0EB5EF131B683F24A4E6D',
            ],   
            [
            'name' => 'lockReels.js',
            'hash' => 'FFDF711274143479265C4B895F8B308EBE182335',
            ],     
        ]
    ]
    , 
    [
        'code' => '114771',
         'cert_ver' => '1',
        'software_modules' => [   
            [
            'name' => 'fgAnteDoubleMegawaysWeightsSetsDictionary.js',
            'hash' => '0B60845B805021D793099A57A04E7ACC8B1B605E',
            ],   
            [
            'name' => 'bonus.js',
            'hash' => '1483702999A17B12C97684C61A47FDB3BD3EA238',
            ],   
            [
            'name' => 'scene.js',
            'hash' => '1CF75D02FD4867489FA7CBD02D759CF55459D430',
            ],   
            [
            'name' => 'wayCalculator.js',
            'hash' => '263150B2F1012EAC02C78F24DC890C8C9DA30255',
            ],   
            [
            'name' => 'scatterRule.js',
            'hash' => '2DB8095FF380F137785AF93B2EFD80579214C30A',
            ],   
            [
            'name' => 'defaultScreens.js',
            'hash' => '2FDE53F898C0CA6497E2A6C3C007A07B87C22F11',
            ],   
            [
            'name' => 'fgAnteTripleMegawaysWeightsSetsDictionary.js',
            'hash' => '30B4D172B88072DF0E7A8180889B4B02995B9035',
            ],   
            [
            'name' => 'mainMegawaysSetsDictionary.js',
            'hash' => '3154B7E0599588451A71F6DFE6AFADA24EAE7A72',
            ],   
            [
            'name' => 'game.js(sw-tigome-common)',
            'hash' => '36AFF86014A534AA36BCFE2E5618039580C8F200',
            ],   
            [
            'name' => 'fgMegawaysWeightsSetsDictionary.js',
            'hash' => '4734279FB252BA35E9492C63E1321524F72CF8D7',
            ],  
            [
            'name' => 'game.js',
            'hash' => '4BB8F18380B3FC45803C9A0365D0474CEBAFB2A2',
            ],   
            [
            'name' => 'megawaysHeightsWeights.js',
            'hash' => '5C0B9DD205A6535FE8CCB9872F6FE63ADBEA20DF',
            ],   
            [
            'name' => 'gambleConfig.js',
            'hash' => '5E4A190BB270D1E3F44EDCF66D53656F857659A2',
            ],   
            [
            'name' => 'reelSets.js',
            'hash' => '6470D4D055FD097051A2BDB3197609FAB2C1EE24',
            ],   
            [
            'name' => 'behavior.js',
            'hash' => '692F83D191073E4E0FA9C9B8EE5F3108B1C618CF',
            ],   
            [
            'name' => 'reels.js',
            'hash' => '898B88407AC3D55D3415D8A07080C30C7A54DC9C',
            ],   
            [
            'name' => 'mysteryBonusConfig.js',
            'hash' => 'A0F70108B6E3E2A75E4D74C195BBC0A6A24748D8',
            ],   
            [
            'name' => 'freeMegawaysSetsDictionary.js',
            'hash' => 'B55E66C336F17FF6120E94C219EE1F3E27C1D955',
            ],   
            [
            'name' => 'gamble.js',
            'hash' => 'B67164498FDCF12E10EDE9BDE7E5CD6EEAE59397',
            ],   
            [
            'name' => 'constants.js',
            'hash' => 'BF6DF61EE68D2F2235E9CF8953D61CA8F1CA0B8D',
            ],   
            [
            'name' => 'goldBonus.js',
            'hash' => 'CD7A1143B34D24C4443E6A5CFD8BE21906113295',
            ],   
            [
            'name' => 'utils.js',
            'hash' => 'DDE858971ED6FE52004352F8376837C77398E60C',
            ],   
            [
            'name' => 'fgBuyMegawaysWeightsSetsDictionary.js',
            'hash' => 'DE7B228E5223E9E470DCCAEFFE1644147AEC2B9F',
            ],   
            [
            'name' => 'fgMysteryMegawaysWeightsSetsDictionary.js',
            'hash' => 'E9F1A05F1CD40477FB0CC21CF092A2E4E237EA38',
            ],   
            [
            'name' => 'anteReelSets.js',
            'hash' => 'EAB7DFA0F741E1E6C35334CE70EFA4FA1E102BC2',
            ],     
        ]
    ]
    ,
    [
        'code' => '114773',
         'cert_ver' => '1',
        'software_modules' => [   
            [
            'name' => 'game.js',
            'hash' => '0C6B1654854A4B9E065505A47EBCCF168CBAF9A3',
            ],   
            [
            'name' => 'scene.js',
            'hash' => '116308A135B6B9CAE5F1E3AC302C1DE4E009602C',
            ],   
            [
            'name' => 'bonusSelectionStrategy.js',
            'hash' => '16182DCBB2DC2360DD6DD3BB6426C47F2BCBB54A',
            ],   
            [
            'name' => 'reelSets.js',
            'hash' => '38427EE6B542D8A483518AEB8047EEE142712972',
            ],   
            [
            'name' => 'bonusRule.js',
            'hash' => '3D7BDAC99EF265D2DF1E5FFAA42ED73791FBFAC6',
            ],   
            [
            'name' => 'mysteryWildRule.js',
            'hash' => '9A9848585DE6CCE82B370EACB153559F038177D0',
            ],   
            [
            'name' => 'game.js(sw-slot-game-core)',
            'hash' => 'B75F41F84FF66E026AA6BBDB08B4F6A17C8D259C',
            ],   
            [
            'name' => 'freeSpinsAdapters.js',
            'hash' => 'BD3ECB03FD7B162B20C9FB51057894BD803682A8',
            ],   
            [
            'name' => 'slotUtils.js',
            'hash' => 'BF65F0ADEF23408CE24E33104D02F32E4C9BF73C',
            ],   
            [
            'name' => 'freespins.js',
            'hash' => 'CD6EF1C9C3258AAD68B66812CA51894DCAF5E676',
            ],   
            [
            'name' => 'blankReplacementRule.js',
            'hash' => 'E9A45B7E7D70F13851EE27ED59E2FB9B14D0B51E',
            ],     
        ]
    ]
    ,
    [
        'code' => '114776',
         'cert_ver' => '1',
        'software_modules' => [   
            [
            'name' => 'jackpotRule.js',
            'hash' => '0AB1553B5B99E97ADB30027FA7101F02AA14204E',
            ],   
            [
            'name' => 'superWolfFrozenSymbolsReSpinAdapter.js',
            'hash' => '14F53332734E8480D075A1FF587124A60E168EB6',
            ],   
            [
            'name' => 'game.js',
            'hash' => '7604521AC6B1D0A5528557AF972C3E31B02DA34A',
            ],  
            [
            'name' => 'jackpot.js',
            'hash' => '76A37B969106BBE009A9270DAAD881D3EA64C067',
            ],   
            [
            'name' => 'respinStartRule.js',
            'hash' => '784A0511037913ABA94D2E257AB1E53AC174FB4D',
            ],   
            [
            'name' => 'constants.js',
            'hash' => '9A5574540A5B81DC1ECFCE1D78E1903DE05D8102',
            ],   
            [
            'name' => 'scene.js',
            'hash' => 'B133D829583770D88A3888380895BD95B95D6BAD',
            ],   
            [
            'name' => 'superWolfReSpinSlotSceneBehavior.js',
            'hash' => 'D415F7032B54F7E9B0E2CE587F962C141D978AB9',
            ],   
            [
            'name' => 'jackpotInputs.js',
            'hash' => 'DCD207032D336A8BBE274CF5FA88D63463F63BE5',
            ],   
            [
            'name' => 'reelSets.js',
            'hash' => 'F880143FE8DBCBB5396B4355B3A382439D34ECE6',
            ],     
        ]
    ]
    , 
    [
        'code' => '114777',
         'cert_ver' => '1',
        'software_modules' => [   
            [
            'name' => 'weights.js',
            'hash' => '17DFB032CF983214AA267DCCDE80662A8B7930C8',
            ],   
            [
            'name' => 'expandRule.js',
            'hash' => '4B3D2D8605115C5F13FFBCEED5F1B59432C7CF39',
            ],   
            [
            'name' => 'freeBehavior.js',
            'hash' => '4B456140DB2C357C145AD0DE7BDF0E3658E21025',
            ],   
            [
            'name' => 'mainBehavior.js',
            'hash' => '536D67134B621A4AB7BFB7A7A69CF18B0F6189C7',
            ],   
            [
            'name' => 'constant.js',
            'hash' => '54258FA01ADAB92F04753CD9C879109AF9C554B3',
            ],  
            [
            'name' => 'freeSpinsStart.js',
            'hash' => '7D39C8D7A7571CB44BAF4970A58922CF3BBC9AA2',
            ],   
            [
            'name' => 'reelSets.js',
            'hash' => '824E55C2EE22CBB62A54234687B941B2E69819DB',
            ],   
            [
            'name' => 'wayCalculator.js',
            'hash' => 'AF4972AF7A85F0E25E2E534786EA7EF9640FE421',
            ],   
            [
            'name' => 'buyFeatureRule.js',
            'hash' => 'DFA217CFF2738E635AD46CD74D6C8C96A7A5C0D2',
            ],   
            [
            'name' => 'scene.js',
            'hash' => 'E0CA8D5E694B2A056ACF99BE16E7365C962CB9BD',
            ],   
            [
            'name' => 'utils.js',
            'hash' => 'EBBFA5F6D6C4B276806CC9917B2242C8C982A538',
            ],   
            [
            'name' => 'randomUtils.js',
            'hash' => 'F0900B8D40EEE4C80A336AA4B5AFCAA0FEDEA555',
            ],     
        ]
    ]
    , 
    [
        'code' => '114779',
         'cert_ver' => '1',
        'software_modules' => [   
            [
            'name' => 'featureBehavior.js',
            'hash' => '0A3F4DFB77271EEA4585955272D46311AFA0B0E6',
            ],   
            [
            'name' => 'scene.js',
            'hash' => '0EDFDD470A9E9F5D4B1AA4BF520D5B72D66F7104',
            ],   
            [
            'name' => 'predictorRule.js',
            'hash' => '1045BBF48791BC0DD855F08FA41BC7A55B32F092',
            ],   
            [
            'name' => 'bonusStartRule.js',
            'hash' => '1631AAA8E0CB0E3E18BAC9C0BD6A26B0EB6DB7DE',
            ],   
            [
            'name' => 'handleBuyFeature.js',
            'hash' => '23140D3CBB0C21865A6CA6D9EF97FFB2122C1DFC',
            ],   
            [
            'name' => 'freeBehavior.js',
            'hash' => '33DADEAFBF99FB4D760D458B9E1FAB68BE4BC3B7',
            ],   
            [
            'name' => 'coIIapseAdapter.js',
            'hash' => '391DD625AD81022A04F9106582B56151118167E9',
            ],  
            [
            'name' => 'collapseBehavior.js',
            'hash' => '3CDB55EF22B5E7E3E9116CDCE25907FCD757F99D',
            ],   
            [
            'name' => 'regularBehavior.js',
            'hash' => '4DEAEC5D4F0FBED285F75371B75B331D3FC0B5AB',
            ],   
            [
            'name' => 'cascadeWeights.js',
            'hash' => '510C1D780949D2CF946EB3AFB54BF1C496A024C3',
            ],   
            [
            'name' => 'game.js',
            'hash' => '5819A147B068A4589324B3333F739993BDCBCE8E',
            ],   
            [
            'name' => 'megaWaysWeights.js',
            'hash' => '704CA632901A19350EE55E71F377795D2B7A4BE7',
            ],   
            [
            'name' => 'bonusScene.js',
            'hash' => '78FBA3AA099CC29387309F84D0C2FD02873B0C15',
            ],   
            [
            'name' => 'weights.js',
            'hash' => '791E3950055761884BC5054660BE610A4C8D152D',
            ],   
            [
            'name' => 'borderWeights.js',
            'hash' => '7E81FE8748C42EF757423C52368F567B135E0236',
            ],   
            [
            'name' => 'buyFeatureRule.js',
            'hash' => '833EE14798F4F129787308A9F9EBF0ABC8F737D7',
            ],   
            [
            'name' => 'predictor.js',
            'hash' => '8A8F313C10690511A87805488EB854D27CCC820D',
            ],   
            [
            'name' => 'reelSets.js',
            'hash' => '9C60909ED9BCD90AE0DE7E4D4B5BAF897629D33A',
            ],   
            [
            'name' => 'gambleConfig.js',
            'hash' => '9F5AA197F57DFA5C2101147AECD3C39077D5657E',
            ],   
            [
            'name' => 'reel.js',
            'hash' => 'ACE275A42B57CEAEFC5E258439DBBEBDEFE8D790',
            ],   
            [
            'name' => 'constant.js',
            'hash' => 'BDE2B35B3E8E8356C6DDDC9C12DE76BB0C71A277',
            ],   
            [
            'name' => 'freeSpinAdapter.js',
            'hash' => 'C5CA4D65F2486A5A863683E33F55173945C70D21',
            ],   
            [
            'name' => 'extraViewCascadeRule.js',
            'hash' => 'C62C160D289F5B16CA64E74A43DD95C374FAA0D9',
            ],   
            [
            'name' => 'freeSpinsStart.js',
            'hash' => 'C7C22CBAC5D876CAE17A0DB950868B3CC05E19D1',
            ],   
            [
            'name' => 'scatterRule.js',
            'hash' => 'D5092DBDE13801EB8A86845CFA9238193026E9F8',
            ],   
            [
            'name' => 'freeSpinsAdd.js',
            'hash' => 'E359EA65CED71E7D8B12DD0E1211025FE7C8B9FB',
            ],   
            [
            'name' => 'topWeights.js',
            'hash' => 'EFDBCC0A285B4DD54B62093E14551A65700D5D22',
            ],   
            [
            'name' => 'reguIarAdapter.js',
            'hash' => 'F30E7FF5D4289C8B29DD8A56D9EB3B603AD976D8',
            ],     
        ]
    ]
    ,
    [
        'code' => '114781',
         'cert_ver' => '1',
        'software_modules' => [   
            [
            'name' => 'fullScreenWin.js',
            'hash' => '05EE2608D7CB2783BC471042A1FBB70B37E10F87',
            ],   
            [
            'name' => 'multiplierRule.js',
            'hash' => '1057AC7A3456883B23763B8FBA1835B120B68DAE',
            ],   
            [
            'name' => 'reelSets.js',
            'hash' => '195D7BA72B16DD156036A8DF283323A7E7620612',
            ],   
            [
            'name' => 'game.js(sw-getede-common)',
            'hash' => '25770821295CBBBEB6186EF6959EC47DFFD49D6D',
            ],   
            [
            'name' => 'reSpinRule.js',
            'hash' => '282CF25C15B3832A3FDC4F54D0B8672A7779AAD2',
            ],   
            [
            'name' => 'GTDFrozenSymbolsReSpinAdapter.js',
            'hash' => '35207073352D502634998390A6048D0EEC665356',
            ],   
            [
            'name' => 'baseGame.js',
            'hash' => '74D4707D603CF7F9B45CFB7A80B919ACF9F271C0',
            ],   
            [
            'name' => 'game.js',
            'hash' => '90BF51056617A3B22650FA30393E8F77E8555BDE',
            ],   
            [
            'name' => 'GTDReels.js',
            'hash' => '92784742828708861624117D11354605A2A828E3',
            ],   
            [
            'name' => 'multipliers.js',
            'hash' => 'DE7F7649D814D5A359A9149AA31B123CE1835300',
            ],   
            [
            'name' => 'reSpinBehavior.js',
            'hash' => 'F35A6CFD9A7CD96A04BCEE35BAA295CE823FDDE3',
            ],     
        ]
    ]
    , 
    [
        'code' => '114784',
         'cert_ver' => '1',
        'software_modules' => [   
            [
            'name' => 'game.js',
            'hash' => '028127C5FF814EDEA0F77505D19DDFD6A22491B6',
            ],   
            [
            'name' => 'reelSets.js',
            'hash' => '03BC1969E8A1CE23626E3BE2F914A19BA8541B77',
            ],   
            [
            'name' => 'weights.js',
            'hash' => '0FD855830BE6910179342A8A62F3DC1155B850CF',
            ],   
            [
            'name' => 'scene.js',
            'hash' => '40B9086B449EF9761DD6610F174A7BE482FFACAF',
            ],   
            [
            'name' => 'behavior.js',
            'hash' => '54E0670667F9FDFAB2D509CBBD110730FF15DF71',
            ],   
            [
            'name' => 'reel.js',
            'hash' => '56A73E4A68F1663F0246198E68E0005D3E3803EF',
            ],   
            [
            'name' => 'replaceMultiplierRule.js',
            'hash' => '5FECC85FD61D0C5CDCEC10E0C337F3F702F25261',
            ],   
            [
            'name' => 'manager.js',
            'hash' => '817E0C8331C78D567BAA2CF3144D7666B3E6641B',
            ],   
            [
            'name' => 'calculator.js',
            'hash' => 'C00A0A1C5B0983ECFAE59BA1FAAA75683229ED05',
            ],   
            [
            'name' => 'constant.js',
            'hash' => 'C6ECEE5B16868E23135837AA1025ACFDA84DE4D4',
            ],   
            [
            'name' => 'game.js(core)',
            'hash' => 'F5C3A89D7E186AF083FA6DACEAF52B32C0F3D37C',
            ],     
        ]
    ]
    , 
    [
        'code' => '114788',
         'cert_ver' => '1',
        'software_modules' => [   
            [
            'name' => 'reelSets.js',
            'hash' => '06B9D2250C02725CB2DC73841D19254FF059FC64',
            ],   
            [
            'name' => 'wantedPosterRule.js',
            'hash' => '14001F553AD886B5965FA8FAEADA2035E1F88359',
            ],   
            [
            'name' => 'buyFeatureRule.js',
            'hash' => '1F39ED2D0B5E27CFAFA6BF9A9F2D269606E4A71F',
            ],   
            [
            'name' => 'slotGame.js',
            'hash' => '304B0FC864DD351BDB9D48BB434542666583E5EB',
            ],   
            [
            'name' => 'constants.js',
            'hash' => '43F2677C48E6D090AD1DAC45A49C87759CC8770D',
            ],   
            [
            'name' => 'config.js',
            'hash' => '4D441E82E770246007CC9956FB5A0D9E978D28D8',
            ],   
            [
            'name' => 'megawaysHeightsWeights.js',
            'hash' => '56D000B7E798D8CEF3CAE871884220E833127166',
            ],   
            [
            'name' => 'columnWildRule.js',
            'hash' => '65BCA78A7D49E71E9D69E40BEE6E4295E83F3461',
            ],   
            [
            'name' => 'reels.js',
            'hash' => '8E8EAA64DA33A04DCBDA94D27720062219E4E764',
            ],   
            [
            'name' => 'collapseBehavior.js',
            'hash' => 'B93532BD25A53FBECAEAF36F7E45405BEE3E490A',
            ],   
            [
            'name' => 'bonusScene.js',
            'hash' => 'C3FB3C6B5ABCA6852D8E99BA8765A21A7A29BC3C',
            ],   
            [
            'name' => 'collapseAdapter.js',
            'hash' => 'C8F4799F076189D108B2E382C7C2E69B86F48012',
            ],   
            [
            'name' => 'randomFeaturesRule.js',
            'hash' => 'D7763F96404E4E5AD84EB426FC5F31A35462C215',
            ],   
            [
            'name' => 'wayWinCalculator.js',
            'hash' => 'E00FA9D44D506F33B840B932FAC9A0221D6F9FFB',
            ],   
            [
            'name' => 'freeBehavior.js',
            'hash' => 'E623A56C4DE9EE1B8134B6DB71E7771119B631F8',
            ],     
        ]
    ]
    , 
    [
        'code' => '114795',
         'cert_ver' => '1',
        'software_modules' => [   
            [
            'name' => 'bonus4.js',
            'hash' => '18B3AAAA79F93DC46F25A3E250A1FC66FC31C63B',
            ],   
            [
            'name' => 'scene.js',
            'hash' => '34ACBCCDC41FC120962AF02BE7478BC2308289EF',
            ],   
            [
            'name' => 'freeSpinsAdapter.js',
            'hash' => '3FA68479D72D848AC0B6FC5FE3E78CDFF730B56E',
            ],   
            [
            'name' => 'bonusSelectionStartRule.js',
            'hash' => '4D704DCFA4009F3072E63719B4B841BCB1CE81A7',
            ],   
            [
            'name' => 'bonusMappings.js',
            'hash' => '4EA24CA72D1E5496A642220D2C279FD36FF76D0A',
            ],   
            [
            'name' => 'bonus2.js',
            'hash' => '51FDFDBA1F5FEF975ADAE6C0CD05E3B0D8BBDF56',
            ],   
            [
            'name' => 'bonus1.js',
            'hash' => '6C01D9608D4DEFCE418911B1E65B12354C9FAE24',
            ],   
            [
            'name' => 'wildFeature.js',
            'hash' => '71739E9881A3E9DA43F51B99AFBC873FFE78FDBD',
            ],   
            [
            'name' => 'game.js(core)',
            'hash' => '83DAE865504AD430019FFA1180B0A1806552C116',
            ],   
            [
            'name' => 'bonusReelsSetsAdapter.js',
            'hash' => '86A709EC5D23FD8862281D6F2560A744A98F8CBB',
            ],   
            [
            'name' => 'bonus3.js',
            'hash' => '90CC16E409E787ABD1B7E067D8F8E8A14B9D877E',
            ],   
            [
            'name' => 'collapsingSlotSceneBehavior.js',
            'hash' => '9B02C62F6F3826EE6F0067C3E8E8AABD2209A182',
            ],   
            [
            'name' => 'game.js',
            'hash' => 'AE352787B24ED074D686B5F11117ADC7E3BFDC4E',
            ],   
            [
            'name' => 'main.js',
            'hash' => 'DF27128D8EAC97EF67F94D358251C2739C27C672',
            ],   
            [
            'name' => 'wayWinCalculator.js',
            'hash' => 'F34249C36BC7784A54FD8F411BAE606872B317EB',
            ],   
            [
            'name' => 'bonusSelectionScene.js',
            'hash' => 'F76C9FF2E8A80EC3707A57FAE7E58B4085AAB21F',
            ],     
        ]
    ]
    ,
    [
        'code' => '114797',
         'cert_ver' => '1',
        'software_modules' => [   
            [
            'name' => 'predictrule.js',
            'hash' => '0997E5BCE88F984D126EAF7DB26E220BFD26B75A',
            ],   
            [
            'name' => 'freespinsstartrule.js',
            'hash' => '1ABF1B848445EECE95427AC85BAD16DD6ECF46F8',
            ],  
            [
            'name' => 'freespinsaddrule.js',
            'hash' => '30592EADB642AF6EECE85E31CF7DB4AD6B3C8491',
            ],   
            [
            'name' => 'collectRule.js',
            'hash' => '3C2E9AB805B4DAB196CD41ADCD911D365CF46E5C',
            ],   
            [
            'name' => 'weights.js',
            'hash' => '3D59BBE0D26BFDE41C4DD0565BF89942EB8904AC',
            ],   
            [
            'name' => 'constants.js',
            'hash' => '88FC2DA126E34C45B35C74D6719D0C4C77D46CBE',
            ],   
            [
            'name' => 'reelcardingweights.js',
            'hash' => 'A077C6C8B3B43576EBB34913F86F2458D36C3DD8',
            ],   
            [
            'name' => 'gambleConfig.js',
            'hash' => 'AB4EC432B11E8010E992EAA3B50C6765BC7B08F3',
            ],   
            [
            'name' => 'gamblescene.js',
            'hash' => 'C5422671F0250640378E6CE31E6464344D943A80',
            ],   
            [
            'name' => 'game.js',
            'hash' => 'CC83AAA2BBE6B3D6D7964A637F4238CE58B9551C',
            ],   
            [
            'name' => 'bonusStartRule.js',
            'hash' => 'DA5C35F2E216AD277B48F1C2E787E597D0751FD3',
            ],   
            [
            'name' => 'reelsets.js',
            'hash' => 'E5AC26CFA8F52447AFD50B67638A0E1853A67E3D',
            ],   
            [
            'name' => 'scene.js',
            'hash' => 'EE5B48995B1FC7514B489DAA5CC2DB86FA2F499C',
            ],   
            [
            'name' => 'game.js (sw-slot-game-core)',
            'hash' => 'F56AFEFFF73BFB40671C9AB7D80979BE612D3DFC',
            ],     
        ]
    ]
    ,
    [
        'code' => '138087',
         'cert_ver' => '1',
        'software_modules' => [   
            [
            'name' => 'wild.js',
            'hash' => '426D31B07C5F58E5ACE812CF426413C710870EF7',
            ],   
            [
            'name' => 'superPrize.js',
            'hash' => '6F545C9103DF944816A4F434FF483756EC4A6A87',
            ],   
            [
            'name' => 'slotGame.js',
            'hash' => 'C3A30E0BAE09A4487A66F87ED6242FE6C1210FF3',
            ],   
            [
            'name' => 'reel.js',
            'hash' => 'C93E5DC5C3BCD3272B90CD491597F3998DC2C86A',
            ],   
            [
            'name' => 'behavior.js',
            'hash' => 'DC3D00E7DA2D747421F3849609431C302E6009AD',
            ],   
            [
            'name' => 'game.js',
            'hash' => 'F4CBF9D2055983C93F8F3B02E58C3E9AF54954C3',
            ],     
        ]
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

