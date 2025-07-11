<?php

require_once '/var/www/videoslots.it/phive/phive.php';
$user = cu($argv[1] ?? 6624618);

$games = [
    [
        'code' => '45022',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-game-s1-g18-core-2.0.0-RELEASE.jar',
                'hash' => 'CDE2A5DC66805E23E87CF94F1515FD6F2AD0C88E',
            ],
            [
                'name' => 'gp-game-s1-g18',
                'hash' => 'E0D59194F18C87E7C39A7A24E597279A7A2E8FCE',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
        ]
    ],
    [
        'code' => '48073',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g30-core-1.0.0-RELEASE.jar',
                'hash' => '7AB5BECB348B0374757592184CC44C72A8820471',
            ],
            [
                'name' => 'gp-game-s1-g30',
                'hash' => '7B80623C0488579FCF13B6B35E58876DA4A54BF1',
            ],
        ]
    ],
    [
        'code' => '48074',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-sf-vanilla-v1-games-1.2.0-RELEASE.jar',
                'hash' => '03D283971D5AA98EDF2ED1C1AFA376245BE5BB56',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'game.slot.vanilla.birds-a',
                'hash' => '3B5CF821F2C9DBE80FFDC267D8618FEB0C8A3045',
            ],
        ]
    ],
    [
        'code' => '48078',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g21-core-2.0.0-RELEASE.jar',
                'hash' => '1F7DE4BAE5A865AE981896D493FAF001029D3CAB',
            ],
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-game-s1-g21',
                'hash' => '4E81E8AFA973BFE1166AAA9E5B77FF7A2CCA21A8',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
        ]
    ],
    [
        'code' => '48080',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g23-core-2.0.0-RELEASE.jar',
                'hash' => '29201583B8DB8B47E7896584163C399148737913',
            ],
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-game-s1-g23',
                'hash' => 'BE46218462EDA74112C940F5E8323CE217B839C1',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
        ]
    ],
    [
        'code' => '48081',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g27-core-1.0.0-RELEASE.jar',
                'hash' => 'CBA91B603D399DA06BD87562D3CD33F74D11F4CA',
            ],
            [
                'name' => 'gp-game-s1-g27',
                'hash' => 'C73DE742200815C73FE247423BC52937D405209B',
            ],
        ]
    ],
    [
        'code' => '48083',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-sf-vanilla-v1-games-1.2.0-RELEASE.jar',
                'hash' => '03D283971D5AA98EDF2ED1C1AFA376245BE5BB56',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'game.slot.vanilla.esqueleto-a',
                'hash' => 'C0537E0B1DDF9C8E94472AE257F2168227F7A873',
            ],
        ]
    ],
    [
        'code' => '48085',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-game-s1-g10',
                'hash' => '8D1CF536A75EDF4AA7FFB8F25CC6DE8834C96200',
            ],
            [
                'name' => 'gp-game-s1-g10-core-2.0.0-RELEASE.jar',
                'hash' => '966115F7AC2DBBF8C1DA5D3AB278770B06E26C40',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
        ]
    ],
    [
        'code' => '48087',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-sf-vanilla-v1-games-1.2.0-RELEASE.jar',
                'hash' => '03D283971D5AA98EDF2ED1C1AFA376245BE5BB56',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'game.slot.vanilla.starvector',
                'hash' => '58AD3629CB44C82D1208F1AAD26006045F5C3722',
            ],
        ]
    ],
    [
        'code' => '48089',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g8',
                'hash' => '1FF0B5A155EFBA2E3AC8039BB4E45F05CB6354A7',
            ],
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-game-s1-g8-core-2.0.0-RELEASE.jar',
                'hash' => 'D1DFDD5E71460A29A145ECC3DEB595DD347BCF3F',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
        ]
    ],
    [
        'code' => '48094',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g12-core-2.0.0-RELEASE.jar',
                'hash' => '038B9FCD67562AAA2EE808B10E5D6754D289CF73',
            ],
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-game-s1-g12',
                'hash' => '916450AAEAEC94258B3D5DA98099BC84A8404450',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
        ]
    ],
    [
        'code' => '48097',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-game-s1-g19',
                'hash' => '4E2A021DBA5D46B5F08525AD6332C0B1FBCADC00',
            ],
            [
                'name' => 'gp-game-s1-g19-core-2.0.0-RELEASE.jar',
                'hash' => 'B5744DC7EEFB41A9FF3429D080CD920E63223F26',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
        ]
    ],
    [
        'code' => '48099',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-game-s1-g11-core-2.0.0-RELEASE.jar',
                'hash' => '578953B064C80AF43ED609171F2313440628CFB9',
            ],
            [
                'name' => 'gp-game-s1-g11',
                'hash' => '83A70CD9685D81024B4A5BD4FA5F16424FD98A7C',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
        ]
    ],
    [
        'code' => '48100',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-sf-vanilla-v1-games-1.2.0-RELEASE.jar',
                'hash' => '03D283971D5AA98EDF2ED1C1AFA376245BE5BB56',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'game.slot.vanilla.magicians-a',
                'hash' => '711D9806526C83DE7746A0C13543EF54B7A70045',
            ],
        ]
    ],
    [
        'code' => '48101',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-game-s1-g22',
                'hash' => '813C96106CC961A065C24F03BB52641B1F590E7E',
            ],
            [
                'name' => 'gp-game-s1-g22-core-2.0.1-RELEASE.jar',
                'hash' => 'C2D3CC296312941F6431CC6A64358249FF0E75ED',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
        ]
    ],
    [
        'code' => '48102',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g14',
                'hash' => '2327AE28D0FF1D43AD40D342C0453F23ABF3C0D2',
            ],
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-game-s1-g14-core-2.0.0-RELEASE.jar',
                'hash' => '7313C32FC2EF09CC81BFDC29EEFE6E9FC53DD479',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
        ]
    ],
    [
        'code' => '48104',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-game-s1-g13-core-2.0.0-RELEASE.jar',
                'hash' => '7CDDAF0524971C23F0AAD944C2F09CD71C881F8B',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-game-s1-g13',
                'hash' => 'D86343F0A649D5DDD700843903E460C09DC26AEC',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
        ]
    ],
    [
        'code' => '48106',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g24-core-1.0.0-RELEASE.jar',
                'hash' => '96D9E958A22F074FFCAD99EFE520AF449391C109',
            ],
            [
                'name' => 'gp-game-s1-g24',
                'hash' => 'C0FF323F1C58073867928AA37481DFAC5A1D5281',
            ],
        ]
    ],
    [
        'code' => '48107',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g25-core-1.0.0-RELEASE.jar',
                'hash' => '60CBFD805DCA4D15B6EBC295A6B0AA92AF79960F',
            ],
            [
                'name' => 'gp-game-s1-g25',
                'hash' => 'A5C84ABD048B3B933A58D8771CD9DBBA0B481556',
            ],
        ]
    ],
    [
        'code' => '48108',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-game-s1-g20-core-2.0.0-RELEASE.jar',
                'hash' => '9A0A400E85EF556AE414B1DE9DFF90C6C4DA425A',
            ],
            [
                'name' => 'gp-game-s1-g20',
                'hash' => 'C70BD4723D9757CECD0021BDC5EE50AED4F7B06F',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
        ]
    ],
    [
        'code' => '48109',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-game-s1-g6-core-6.0.0-RELEASE.jar',
                'hash' => 'BA257ED6AAA7DF520A0BCE7E3816EB8A039DA806',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'gp-game-s1-g6',
                'hash' => 'F3239657818EC7DE913684798788CFAFAD87E557',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
        ]
    ],
    [
        'code' => '48110',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-game-s1-g3',
                'hash' => '9A6BD060CCD009BBB65A60F9D76B87FC279A599F',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'gp-game-s1-g3-core-5.0.0-RELEASE.jar',
                'hash' => 'F28D2A426E9F16169E2F05CC7F430039844C5EEB',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
        ]
    ],
    [
        'code' => '48112',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g15',
                'hash' => '16CF1A15B733C99C43172D05439C87D782E68A88',
            ],
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-game-s1-g15-core-2.0.0-RELEASE.jar',
                'hash' => '9CB12A993DE0B0F9E7EDE00D8CE4C1E44301EFF5',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
        ]
    ],
    [
        'code' => '48113',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-game-s1-g1-core-4.0.0-RELEASE.jar',
                'hash' => '6CD972A365A5A24F87B3A4303E622F5EF78CE1F6',
            ],
            [
                'name' => 'gp-game-s1-g1',
                'hash' => '9E2ABD4B2B61575042D7079C4B7D6F6B83480C4C',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
        ]
    ],
    [
        'code' => '48116',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-game-s1-g7',
                'hash' => 'B3C1D945038237BF49C06ADE71EC7AA53ED44F6E',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'gp-game-s1-g7-core-2.0.0-RELEASE.jar',
                'hash' => 'EB975C8B147E10083EA9B90C164D72D2334E115E',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
        ]
    ],
    [
        'code' => '48117',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-game-s1-g9',
                'hash' => '62582D15BDE79D0567F70FFB37A3829141785A80',
            ],
            [
                'name' => 'gp-game-s1-g9-core-2.0.0-RELEASE.jar',
                'hash' => '8F4A29D5C3084DC07933EF284E0B1B2937A57DD6',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
        ]
    ],
    [
        'code' => '48118',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g16',
                'hash' => '1E5AF923AE43935347BBF42568076392A4FD3399',
            ],
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-game-s1-g16-core-2.0.0-RELEASE.jar',
                'hash' => '6F0B98FE3AB74E3785EB8328F7247C1E0838B474',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
        ]
    ],
    [
        'code' => '48119',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g17',
                'hash' => '2100B72E9DA083C578E8544112B73208F0627D77',
            ],
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-game-s1-g17-core-2.0.0-RELEASE.jar',
                'hash' => '833729D0A2F7566F33D2DA8F33BB8EC0619113F2',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
        ]
    ],
    [
        'code' => '48120',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-rng-fortuna-wrapper-1.0.0-RELEASE.jar',
                'hash' => '451AD63DF35D233ECE6647C38733792B4A05CEAF',
            ],
            [
                'name' => 'gp-game-s1-g4-core-5.0.0-RELEASE.jar',
                'hash' => '52BCF60D83A5A0D2AA315893EC971E002D2D2DC2',
            ],
            [
                'name' => 'gp-game-s1-g4',
                'hash' => '5BDA97F5EC12ED6953009E93E7008CA01184AF51',
            ],
            [
                'name' => 'gp-rng-fortuna-1.0.0-RELEASE.jar',
                'hash' => 'C9E68EFB7A53EA90F22B577BB043D8DCFA4AE51C',
            ],
            [
                'name' => 'gp-rng-api-3.5.0-RELEASE.jar',
                'hash' => 'EA01386BE069745202580BD50C3DE32C33F3553F',
            ],
            [
                'name' => 'gp-digest-utilities-1.0.0-RELEASE.jar',
                'hash' => '3C993353A35E427E9683852EEA0E405F121A2AB1',
            ],
        ]
    ],
    [
        'code' => '57920',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g41-core-1.0.0-RELEASE.jar',
                'hash' => '50600741AAFE5C2C0E72FAE2539973BDC3AEE97E',
            ],
            [
                'name' => 'gp-game-s1-g41',
                'hash' => '936447231CE884F9AFC5CF3B1875A7632C44FE84',
            ],
        ]
    ],
    [
        'code' => '57921',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g34-core-1.0.0-RELEASE.jar',
                'hash' => '438AEFDDA0780D2E2171F9B5A0852079A5D3D5F4',
            ],
            [
                'name' => 'gp-game-s1-g34',
                'hash' => '4950B29B36DF53FF7EF87CDF7386FF4812F8F5CA',
            ],
        ]
    ],
    [
        'code' => '57922',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g40-core-1.0.2-RELEASE.jar',
                'hash' => 'F98A9BEF5CC2A4F10539BF35D9BAF039F9138BEC',
            ],
            [
                'name' => 'gp-game-s1-g40',
                'hash' => '17EBEEA473FB18CD73BA76CF1C4092690BC82322',
            ],
        ]
    ],
    [
        'code' => '57923',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g35-core-1.0.0-RELEASE.jar',
                'hash' => '952ADD1AE7919129856ADC93F8C733049CE53E20',
            ],
            [
                'name' => 'gp-game-s1-g35',
                'hash' => '8EE53A90233C46C85333689DA8105DE8880AB5F8',
            ],
        ]
    ],
    [
        'code' => '58103',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g32-core-1.0.0-RELEASE.jar',
                'hash' => '91966A84F4BA76200DCA942930E6711FF61FE568',
            ],
            [
                'name' => 'gp-game-s1-g32',
                'hash' => '248CFDB3706B85FF2B9EF98BA0146659984DD3A6',
            ],
        ]
    ],
    [
        'code' => '58104',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g31-core-1.0.0-RELEASE.jar',
                'hash' => '4B7D34F8240C4FD9CE012D4A1B1FDAF5C995DF84',
            ],
            [
                'name' => 'gp-game-s1-g31',
                'hash' => 'BD93AE4391686DFF56E9125D58AF8A6A33EFA0AC',
            ],
        ]
    ],
    [
        'code' => '58105',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g33-core-1.0.0-RELEASE.jar',
                'hash' => '22D9257FCD2047ED8A337B1A1C48ABE5ACF19B57',
            ],
            [
                'name' => 'gp-game-s1-g33',
                'hash' => '69D25BA10B3BD813D4E834C6059078BFF36F48E2',
            ],
        ]
    ],
    [
        'code' => '60552',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g36-core-1.0.0-RELEASE.jar',
                'hash' => '166C21A341DD94D217E94344532C705082F84CD5',
            ],
            [
                'name' => 'gp-game-s1-g36',
                'hash' => '8FEDF47B7104DC67F906103D746D09EA9BB30540',
            ],
        ]
    ],
    [
        'code' => '96862',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g49-core-1.0.3-RELEASE.jar',
                'hash' => 'B0375E7F463209B4346298F74A43D07E885C5105',
            ],
            [
                'name' => 'gp-game-s1-g49-94',
                'hash' => 'A1B9A3E03EC32A9F1057C28A40E13AF53245905E',
            ],
        ]
    ],
    [
        'code' => '96864',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g45-core-1.0.0-RELEASE.jar',
                'hash' => '0C0125ABA3AB3CDCB0479E9C10DE9B683AC9E30C',
            ],
            [
                'name' => 'gp-game-s1-g45',
                'hash' => '80BC1A65209BC03A2D203F48ED0D567AEDC60475',
            ],
        ]
    ],
    [
        'code' => '96869',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g46-core-1.0.0-RELEASE.jar',
                'hash' => '98F74FF5E38660DCBE163DBFDBC82651828C3F3B',
            ],
            [
                'name' => 'gp-game-s1-g46',
                'hash' => '3A83324FEB21E49B239405D12D3CCD415325FA6F',
            ],
        ]
    ],
    [
        'code' => '96870',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g37-core-1.0.0-RELEASE.jar',
                'hash' => 'D8A0DA2B9407EC7478DE61F03CD1736EDC6CB88A',
            ],
            [
                'name' => 'gp-game-s1-g37',
                'hash' => '378369981854232AC4675B47A87DDA88A8D3D17B',
            ],
        ]
    ],
    [
        'code' => '96871',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g38-core-1.0.2-RELEASE.jar',
                'hash' => 'C314D5AE81D0258F7B9241EC0109B9F7B258F1FF',
            ],
            [
                'name' => 'gp-game-s1-g38',
                'hash' => 'FC73A8F701EB34A56A4C30655C55B33F60E55DED',
            ],
        ]
    ],
    [
        'code' => '96872',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g39-core-1.0.2-RELEASE.jar',
                'hash' => '600C8A563CE87CB9CA18696AB372C71715276B04',
            ],
            [
                'name' => 'gp-game-s1-g39-94',
                'hash' => 'FC795ACC911FA8A37D22D9F14E1021872DC5E408',
            ],
        ]
    ],
    [
        'code' => '96874',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g42-core-1.0.0-RELEASE.jar',
                'hash' => 'BE934D246ABB53E87E7DD29890C9A48EA732EA19',
            ],
            [
                'name' => 'gp-game-s1-g42',
                'hash' => '29DA215180102EEE8DD0AD657072131B960C7995',
            ],
        ]
    ],
    [
        'code' => '96875',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g47-core-1.0.0-RELEASE.jar',
                'hash' => 'C7FE689586EF6B52DC2B1D42F0FADB044B8155B9',
            ],
            [
                'name' => 'gp-game-s1-g47-94',
                'hash' => '039A7EBE6C3966537A2847CF51A5EC2D3B1D1429',
            ],
        ]
    ],
    [
        'code' => '99632',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'tk-s1-g50-94-fb-cfg',
                'hash' => '6CFAEB3DB05283B7B9A4B9D25D3F40FFD84E658F',
            ],
            [
                'name' => 'gp-game-s1-g50-core-1.0.0-RELEASE.jar',
                'hash' => 'E7B6509C5FC2D993A0586BE9E8C8022BA9862380',
            ],
            [
                'name' => 'gp-game-s1-g50-94',
                'hash' => 'EE06DFABB6BB8CCA5BDA4CAD0102522B51F89F83',
            ],
        ]
    ],
    [
        'code' => '99633',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'gp-game-s1-g48-core-1.0.2-RELEASE.jar',
                'hash' => '3C823A3B62F43A77EBF547B19B730C775D0A1F72',
            ],
            [
                'name' => 'gp-game-s1-g48-94',
                'hash' => '16F1558C4DA585E493553F3C647E4DF0C412A485',
            ],
        ]
    ],
    [
        'code' => '99634',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'tk-s1-g51-94-fb-cfg',
                'hash' => 'C57D55095B3E1A950C3420CCA29E650BC155DDD7',
            ],
            [
                'name' => 'gp-game-s1-g51-core-1.0.2-RELEASE.jar',
                'hash' => 'E1FC2C5CAE5552A62D92785CC86A3EEF2B5A41A4',
            ],
            [
                'name' => 'gp-game-s1-g51-94',
                'hash' => '98D1D73AD9775AB3DC8B1D92AE02677AC9262129',
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

    if ($response['code'] != 0) {
        print_r($payload);
        print_r($response);
    }

    usleep(500);
}

echo "Script complete";

