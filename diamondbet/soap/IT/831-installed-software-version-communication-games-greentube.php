<?php

require_once '/var/www/videoslots.it/phive/phive.php';
$user = cu($argv[1] ?? 6624618);

$games = [
    [
        'code' => 62833,
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTATSlotServer\2021-03-09_082403_IT-ES-CH-RE-2021\com\greentube\slot\server\definitions\novomatic\bookofra_lordoftheocean.aes',
                'hash' => 'e8c67af51ceba30e58002a4fe54ad596a0957bb4',
            ],
            [

                'name' => 'D:\ServerIT\GTATSlotServer\2021-03-09_082403_IT-ES-CH-RE-2021\gtatslotserver.jar',
                'hash' => 'cd18d9d3ab265d9eacf71411d3e13c57ff2c0944',
            ]
        ]
    ],
    [
        'code' => 62808,
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTATSlotServer\2021-03-09_082403_IT-ES-CH-RE-2021\com\greentube\slot\server\definitions\novomatic\alwayshot.aes',
                'hash' => '8f9e077c8282823a7b91aeb9f3c3bfe912620e16',
            ],
            [
                'name' => 'D:\ServerIT\GTATSlotServer\2021-03-09_082403_IT-ES-CH-RE-2021\gtatslotserver.jar',
                'hash' => 'cd18d9d3ab265d9eacf71411d3e13c57ff2c0944',
            ]
        ]
    ],
    [
        'code' => 62835,
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTATSlotServer\2021-03-09_082403_IT-ES-CH-RE-2021\com\greentube\slot\server\definitions\novomatic\bookofra_lordoftheocean.aes',
                'hash' => 'e8c67af51ceba30e58002a4fe54ad596a0957bb4',
            ],
            [
                'name' => 'D:\ServerIT\GTATSlotServer\2021-03-09_082403_IT-ES-CH-RE-2021\gtatslotserver.jar',
                'hash' => 'cd18d9d3ab265d9eacf71411d3e13c57ff2c0944',
            ]
        ]
    ],
    [
        'code' => 62858,
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTATSlotServer\2021-03-09_082403_IT-ES-CH-RE-2021\com\greentube\slot\server\definitions\novomatic\dolphinspearl.aes',
                'hash' => '5914d94c12e90e7e2ba7826b3dd830e5a8ac44e3',
            ],
            [
                'name' => 'D:\ServerIT\GTATSlotServer\2021-03-09_082403_IT-ES-CH-RE-2021\gtatslotserver.jar',
                'hash' => 'cd18d9d3ab265d9eacf71411d3e13c57ff2c0944',
            ]
        ]
    ],
    [
        'code' => 62986,
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTATSlotServer\2021-03-09_082403_IT-ES-CH-RE-2021\com\greentube\slot\server\definitions\novomatic\luckylady.aes',
                'hash' => 'a545768dd75e18bcddf63c8ab785b34f701a3b4a',
            ],
            [
                'name' => 'D:\ServerIT\GTATSlotServer\2021-03-09_082403_IT-ES-CH-RE-2021\gtatslotserver.jar',
                'hash' => 'cd18d9d3ab265d9eacf71411d3e13c57ff2c0944',
            ]
        ]
    ],
    [
        'code' => 62836,
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\707GamesSlotServer\2021-03-09_080126_IT-ES-CH-RE-2021\707gamesslotserver.jar',
                'hash' => '0320d6f5288811cb960ad74dac9b86127ed24a8f',
            ],
            [
                'name' => 'D:\ServerIT\707GamesSlotServer\2021-03-09_080126_IT-ES-CH-RE-2021\com\greentube\slot\server\novomatic\definitions\bookofradeluxe6.aes',
                'hash' => '29310668a9d9e69bd9a079a34cc50d6b951240af',
            ]
        ]
    ],
    [
        'code' => 63085,
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTUKSlotServer\2021-03-09_084744_IT-ES-CH-RE-2021\com\mazooma\server\definitions\Spectrum.aes',
                'hash' => '323a51cc695284aed9cf869c156bcdb141d5744e',
            ],
            [
                'name' => 'D:\ServerIT\GTUKSlotServer\2021-03-09_084744_IT-ES-CH-RE-2021\gtukslotserver.jar',
                'hash' => '0c9140aab2e15f89a736bacd9d77a2ce136f2998',
            ]
        ]
    ],
    [
        'code' => 63116,
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\RedlineSlotServer\2021-03-09_090351_IT-ES-CH-RE-2021\com\redlinegames\slot\server\definitions\wishuponastar.aes',
                'hash' => '476ea6d1c205100150479a2938f18b9804c5a4c3',
            ],
            [
                'name' => 'D:\ServerIT\RedlineSlotServer\2021-03-09_090351_IT-ES-CH-RE-2021\redlineslotserver.jar',
                'hash' => '4f06cac1f7bb6406e8483051e68b57e2a958b613',
            ]
        ]
    ],
    [
        'code' => 62989,
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTUKSlotServer\2021-03-09_084744_IT-ES-CH-RE-2021\com\mazooma\server\definitions\LuckyLadysCharmDeluxe10.aes',
                'hash' => 'df06722590288fd92275dea5cd0176f7d0faa03d',
            ],
            [
                'name' => 'D:\ServerIT\GTUKSlotServer\2021-03-09_084744_IT-ES-CH-RE-2021\gtukslotserver.jar',
                'hash' => '0c9140aab2e15f89a736bacd9d77a2ce136f2998',
            ]
        ]
    ],
    [
        'code' => 76186,
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTSKSlotServer\2021-07-26_161218_MJ-50\gtskslotserver.jar',
                'hash' => '151a64a29b344634ecf9df5ae64ef2a0e784f946',
            ],
            [
                'name' => 'D:\ServerIT\GTSKSlotServer\2021-07-26_161218_MJ-50\sk\fma\greentube\slot\server\definitions\twinspinnerbookofra.aes',
                'hash' => '23e519cc7433d71986d87ecb640495f6cc45014e',
            ]
        ]
    ],
    [
        'code' => 97477,
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTUKSlotServer\2021-03-09_084744_IT-ES-CH-RE-2021\com\mazooma\server\definitions\DolphinsPearlDeluxe10.aes',
                'hash' => '5acbf808c22a993fb3791ec2bdb09afe490f3ee2',
            ],
            [
                'name' => 'D:\ServerIT\GTUKSlotServer\2021-03-09_084744_IT-ES-CH-RE-2021\gtukslotserver.jar',
                'hash' => '0c9140aab2e15f89a736bacd9d77a2ce136f2998',
            ]
        ]
    ],
    [
        'code' => 97478,
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTUKSlotServer\2022-01-17_113404_MJ-22-01\com\mazooma\server\definitions\BookOfRaDeluxe10WinWaysBuyBonus.aes',
                'hash' => 'dcf3d5ce1656b3db2e336a9eb270715869425eab',
            ],
            [
                'name' => 'D:\ServerIT\GTUKSlotServer\2022-01-17_113404_MJ-22-01\gtukslotserver.jar',
                'hash' => 'c95b51d9b140ce958ae0fd71136b69a609c15282',
            ]
        ]
    ],
    [
        'code' => 97479,
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTUKSlotServer\2021-03-09_084744_IT-ES-CH-RE-2021\com\mazooma\server\definitions\CopsAndRobbersVegasNights.aes',
                'hash' => 'cb8d3286c6994cc2b29c665775ac3f00e5cb7fdd',
            ],
            [
                'name' => 'D:\ServerIT\GTUKSlotServer\2021-03-09_084744_IT-ES-CH-RE-2021\gtukslotserver.jar',
                'hash' => '0c9140aab2e15f89a736bacd9d77a2ce136f2998',
            ]
        ]
    ],
    [
        'code' => 97482,
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTSKSlotServer\2021-09-03_071013_MJ-55\gtskslotserver.jar',
                'hash' => '3f83aea6517efb910ad8301fd19e19c10c3c0db6',
            ],
            [
                'name' => 'D:\ServerIT\GTSKSlotServer\2021-09-03_071013_MJ-55\sk\fma\greentube\slot\server\definitions\mazooma\AFistfulOfWilds.aes',
                'hash' => 'af490c9572ebce04e484e187ed46dcd8623e503f',
            ]
        ]
    ],
    [
        'code' => 97483,
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\EurocoinInteractiveSharedJackpotServer\2021-07-14_183455_MJ-RE-21-07-40WF6\com\jvhgames\slot\server\definitions\fortywildfire6.aes',
                'hash' => 'c73ebd509a8bcb18d639bedca04942eb06a9f0a1',
            ],
            [
                'name' => 'D:\ServerIT\EurocoinInteractiveSharedJackpotServer\2021-07-14_183455_MJ-RE-21-07-40WF6\eurocoininteractivesharedjackpotserver.jar',
                'hash' => 'b00ba56ece7293744c9a01382670a7292d83676f',
            ]
        ]
    ],
    [
        'code' => 97494,
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTUKSlotServer\2021-08-17_112505_MJ-52\com\mazooma\server\definitions\TopOTheMoneyPotsOfWealth.aes',
                'hash' => '91acfb4ba421d92d5f9e1b0dee0bc3b446bf9890',
            ],
            [
                'name' => 'D:\ServerIT\GTUKSlotServer\2021-08-17_112505_MJ-52\gtukslotserver.jar',
                'hash' => '76b8e5aecbb07cf626001586eb47a2ec7ded7f64',
            ]
        ]
    ],
    [
        'code' => 97485,
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTUKSlotServer\2021-08-17_112505_MJ-52\com\mazooma\server\definitions\ApolloGodOfTheSun10WinWaysBuyBonus.aes',
                'hash' => 'b2a89a6d6b2c5126dd54d6e77970b0d28dd55afd',
            ],
            [
                'name' => 'D:\ServerIT\GTUKSlotServer\2021-08-17_112505_MJ-52\gtukslotserver.jar',
                'hash' => '76b8e5aecbb07cf626001586eb47a2ec7ded7f64',
            ]
        ]
    ],
    [
        'code' => '62854',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTSKDiamondLinkSlotServer\2021-03-09_083658_IT-ES-CH-RE-2021\gtskslotserver.jar',
                'hash' => 'fba21778a3999d21bd4b8611296a4e16b832191a',
            ],
            [
                'name' => 'D:\ServerIT\GTSKDiamondLinkSlotServer\2021-03-09_083658_IT-ES-CH-RE-2021\sk\fma\greentube\slot\server\definitions\mightyelephantlinked.aes',
                'hash' => '3869fbbe7b819512a608f24b061089b29facb2b1',
            ],
        ]
    ],
    [
        'code' => '76159',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTATCashConnectionServer\2021-04-29_181847_MJ-46\com\greentube\slot\server\definitions\cashconnectionbookofralinked.aes',
                'hash' => '622559b0552a1fa3bb595116549a7885d6431a57',
            ],
            [
                'name' => 'D:\ServerIT\GTATCashConnectionServer\2021-04-29_181847_MJ-46\gtatcashconnectionserver.jar',
                'hash' => '7a5c96b03d48a567affcdfba15b6324926e5c08c',
            ],
        ]
    ],
    [
        'code' => '76160',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTATCashConnectionServer\2021-03-30_132836_MJ-42\com\greentube\slot\server\definitions\cashconnectionsizzlinghotlinked.aes',
                'hash' => '01fa731bf6297fdc776ec9696c681c0349937361',
            ],
            [
                'name' => 'D:\ServerIT\GTATCashConnectionServer\2021-03-30_132836_MJ-42\gtatcashconnectionserver.jar',
                'hash' => '75e0894d63b99b02d69e0e310896e7d85a0cfea0',
            ],
        ]
    ],
    [
        'code' => '97481',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTATCashConnectionServer\2021-10-29_081326_MJ-58\com\greentube\slot\server\definitions\cashconnectiondolphinspearllinked.aes',
                'hash' => '1b0213baedc0ced5eb5ca55f06cd55828e98b563',
            ],
            [
                'name' => 'D:\ServerIT\GTATCashConnectionServer\2021-10-29_081326_MJ-58\gtatcashconnectionserver.jar',
                'hash' => '1ad47b1b7124407beec3709842e3996457143675',
            ],
        ]
    ],
    [
        'code' => '97486',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTSKDiamondLinkSlotServer\2022-02-14_111011_MJ-22-04\gtskslotserver.jar',
                'hash' => '7e3a9745ced575fad992d8f98cf285ae69215f80',
            ],
            [
                'name' => 'D:\ServerIT\GTSKDiamondLinkSlotServer\2022-02-14_111011_MJ-22-04\sk\fma\greentube\slot\server\definitions\diamondlinkmightybuffalolinked.aes',
                'hash' => '54c2b7ae61746cfa69ccf7f7b8d0cd6ac100124f',
            ],
        ]
    ],
    [
        'code' => '97487',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTSKDiamondLinkSlotServer\2021-03-09_083658_IT-ES-CH-RE-2021\gtskslotserver.jar',
                'hash' => 'fba21778a3999d21bd4b8611296a4e16b832191a',
            ],
            [
                'name' => 'D:\ServerIT\GTSKDiamondLinkSlotServer\2021-03-09_083658_IT-ES-CH-RE-2021\sk\fma\greentube\slot\server\definitions\oasisricheslinked.aes',
                'hash' => 'abc57f27da1ae3f162b9f2eee48c7befee5c1fa0',
            ],
        ]
    ],
    [
        'code' => '97490',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTSKDiamondLinkSlotServer\2021-09-01_094029_MJ-53\gtskslotserver.jar',
                'hash' => '047a05a8ea882975a84f7b673930fbbedc856fb7',
            ],
            [
                'name' => 'D:\ServerIT\GTSKDiamondLinkSlotServer\2021-09-01_094029_MJ-53\sk\fma\greentube\slot\server\definitions\diamondlinkmightysantalinked.aes',
                'hash' => 'b057ac2a33e53e379cb161990fe6eae4699c7570',
            ],
        ]
    ],
    [
        'code' => '97492',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\EurocoinInteractiveSharedJackpotServer\2021-10-29_081523_MJ-58\com\jvhgames\slot\server\definitions\imperialcrown.aes',
                'hash' => 'a6d81eff22b0cb12c9bbe6ad50c892da33b58c4f',
            ],
            [
                'name' => 'D:\ServerIT\EurocoinInteractiveSharedJackpotServer\2021-10-29_081523_MJ-58\eurocoininteractivesharedjackpotserver.jar',
                'hash' => '3bf22d1a841b31c0ad575e8150fc9a345f661f72',
            ],
        ]
    ],
    [
        'code' => '62793',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\EuroCoinInteractiveSlotServer\2021-03-09_081527_IT-ES-CH-RE-2021\com\jvhgames\slot\server\definitions\fiftyfortunefruits.aes',
                'hash' => '05def8efa850b9ea488fd4ea29438f80d9f6da07',
            ],
            [
                'name' => 'D:\ServerIT\EuroCoinInteractiveSlotServer\2021-03-09_081527_IT-ES-CH-RE-2021\eurocoininteractiveslotserver.jar',
                'hash' => '5e536030b88e5f1f6011621705ad41b7e68a6d20',
            ],
        ]
    ],
    [
        'code' => '62802',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\RedlineSlotServer\2021-03-09_090351_IT-ES-CH-RE-2021\redlineslotserver.jar',
                'hash' => '4f06cac1f7bb6406e8483051e68b57e2a958b613',
            ],
            [
                'name' => 'D:\ServerIT\RedlineSlotServer\2021-03-09_090351_IT-ES-CH-RE-2021\com\redlinegames\slot\server\definitions\almightyjackpotsposeidon.aes',
                'hash' => 'bf4af60d5774c0ccdfccda8602c560bd2ed82c9f',
            ],
        ]
    ],
    [
        'code' => '62812',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\707GamesSlotServer\2021-03-09_080126_IT-ES-CH-RE-2021\707gamesslotserver.jar',
                'hash' => '0320d6f5288811cb960ad74dac9b86127ed24a8f',
            ],
            [
                'name' => 'D:\ServerIT\707GamesSlotServer\2021-03-09_080126_IT-ES-CH-RE-2021\com\greentube\slot\server\novomatic\definitions\amazonsdiamonds.aes',
                'hash' => 'fc4a773300de736762c78a7942da5685ed3571ab',
            ],
        ]
    ],
    [
        'code' => '62815',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTUKSlotServer\2021-03-09_084744_IT-ES-CH-RE-2021\gtukslotserver.jar',
                'hash' => '0c9140aab2e15f89a736bacd9d77a2ce136f2998',
            ],
            [
                'name' => 'D:\ServerIT\GTUKSlotServer\2021-03-09_084744_IT-ES-CH-RE-2021\com\mazooma\server\definitions\ApolloGodOfTheSun.aes',
                'hash' => '840f3cbefbe708b548be590c899be3f2e83c4867',
            ],
        ]
    ],
    [
        'code' => '62831',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTATSlotServer\2021-03-09_082403_IT-ES-CH-RE-2021\gtatslotserver.jar',
                'hash' => 'cd18d9d3ab265d9eacf71411d3e13c57ff2c0944',
            ],
            [
                'name' => 'D:\ServerIT\GTATSlotServer\2021-03-09_082403_IT-ES-CH-RE-2021\com\greentube\slot\server\definitions\novomatic\bookofmaya.aes',
                'hash' => '002453a4cf9498054397b4324ca64e7655a7eed4',
            ],
        ]
    ],
    [
        'code' => '62837',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTUKSlotServer\2021-03-09_084744_IT-ES-CH-RE-2021\gtukslotserver.jar',
                'hash' => '0c9140aab2e15f89a736bacd9d77a2ce136f2998',
            ],
            [
                'name' => 'D:\ServerIT\GTUKSlotServer\2021-03-09_084744_IT-ES-CH-RE-2021\com\mazooma\server\definitions\BookOfRaDeluxe10.aes',
                'hash' => 'b6bd516a4a31c1eec932f39da676e502509072e7',
            ],
        ]
    ],
    [
        'code' => '62838',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\707GamesSlotServer\2021-03-09_080126_IT-ES-CH-RE-2021\com\greentube\slot\server\novomatic\definitions\bookoframagic.aes',
                'hash' => '040433a90aa2b972b790dd4565b4ea1c9f3a217b',
            ],
            [
                'name' => 'D:\ServerIT\707GamesSlotServer\2021-03-09_080126_IT-ES-CH-RE-2021\707gamesslotserver.jar',
                'hash' => '0320d6f5288811cb960ad74dac9b86127ed24a8f',
            ],
        ]
    ],
    [
        'code' => '62839',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTATSlotServer\2021-03-09_082403_IT-ES-CH-RE-2021\gtatslotserver.jar',
                'hash' => 'cd18d9d3ab265d9eacf71411d3e13c57ff2c0944',
            ],
            [
                'name' => 'D:\ServerIT\GTATSlotServer\2021-03-09_082403_IT-ES-CH-RE-2021\com\greentube\slot\server\definitions\novomatic\pharaohsring.aes',
                'hash' => 'c788ea2a376e0ceef74e322bb528e8686aa60653',
            ],
        ]
    ],
    [
        'code' => '62844',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTATCashConnectionServer\2021-03-09_082012_IT-ES-CH-RE-2021\gtatcashconnectionserver.jar',
                'hash' => '22def3e28c6702437b5185d760331c5d854c6dda',
            ],
            [
                'name' => 'D:\ServerIT\GTATCashConnectionServer\2021-03-09_082012_IT-ES-CH-RE-2021\com\greentube\slot\server\definitions\CashConnectionCharminglady_Cash_linked.',
                'hash' => 'cf26108079e43a63b90cab9e17613606ac158afa',
            ],
        ]
    ],
    [
        'code' => '62848',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTATSlotServer\2021-03-09_082403_IT-ES-CH-RE-2021\gtatslotserver.jar',
                'hash' => 'cd18d9d3ab265d9eacf71411d3e13c57ff2c0944',
            ],
            [
                'name' => 'D:\ServerIT\GTATSlotServer\2021-03-09_082403_IT-ES-CH-RE-2021\com\greentube\slot\server\definitions\novomatic\columbus.aes',
                'hash' => 'acf67c402050239d59ba78dd6c772eebacc67f42',
            ],
        ]
    ],
    [
        'code' => '62866',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTSKSlotServer\2021-03-09_083128_IT-ES-CH-RE-2021\sk\fma\greentube\slot\server\definitions\bookofragames.aes',
                'hash' => '1c01db4645b287a832886d1d77bcebed1869bbbe',
            ],
            [
                'name' => 'D:\ServerIT\GTSKSlotServer\2021-03-09_083128_IT-ES-CH-RE-2021\gtskslotserver.jar',
                'hash' => '0c0c38d37bc5473567456d7307b05e950e233dc3',
            ],
        ]
    ],
    [
        'code' => '62869',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\RedlineSlotServer\2021-03-09_090351_IT-ES-CH-RE-2021\redlineslotserver.jar',
                'hash' => '4f06cac1f7bb6406e8483051e68b57e2a958b613',
            ],
            [
                'name' => 'D:\ServerIT\RedlineSlotServer\2021-03-09_090351_IT-ES-CH-RE-2021\com\redlinegames\slot\server\definitions\featherfrenzy.aes',
                'hash' => '17048f58f7c416f69e2f4201733ed070644cbf73',
            ],
        ]
    ],
    [
        'code' => '62960',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\RedlineSlotServer\2021-03-09_090351_IT-ES-CH-RE-2021\redlineslotserver.jar',
                'hash' => '4F06CAC1F7BB6406E8483051E68B57E2A958B613',
            ],
            [
                'name' => 'D:\ServerIT\RedlineSlotServer\2021-03-09_090351_IT-ES-CH-RE-2021\com\redlinegames\slot\server\definitions\irishcoins.aes',
                'hash' => '7EEAD00B73463962B505EAB2942B738378BB795F',
            ],
        ]
    ],
    [
        'code' => '62971',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\EuroCoinInteractiveSlotServer\2021-03-09_081527_IT-ES-CH-RE-2021\eurocoininteractiveslotserver.jar',
                'hash' => '5e536030b88e5f1f6011621705ad41b7e68a6d20',
            ],
            [
                'name' => 'D:\ServerIT\EuroCoinInteractiveSlotServer\2021-03-09_081527_IT-ES-CH-RE-2021\com\jvhgames\slot\server\definitions\jokeraction6.aes',
                'hash' => 'afbf3c0a518d694deef77ecc2f268182b7a8f6e0',
            ],
        ]
    ],
    [
        'code' => '62973',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTATSlotServer\2021-03-09_082403_IT-ES-CH-RE-2021\gtatslotserver.jar',
                'hash' => 'cd18d9d3ab265d9eacf71411d3e13c57ff2c0944',
            ],
            [
                'name' => 'D:\ServerIT\GTATSlotServer\2021-03-09_082403_IT-ES-CH-RE-2021\com\greentube\slot\server\definitions\novomatic\justjewels.aes',
                'hash' => 'f47a31bf7fa42093716fbc53b2722066e0a12806',
            ],
        ]
    ],
    [
        'code' => '62982',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\RedlineSlotServer\2021-03-09_090351_IT-ES-CH-RE-2021\redlineslotserver.jar',
                'hash' => '4f06cac1f7bb6406e8483051e68b57e2a958b613',
            ],
            [
                'name' => 'D:\ServerIT\RedlineSlotServer\2021-03-09_090351_IT-ES-CH-RE-2021\com\redlinegames\slot\server\definitions\lonestarjackpots.aes',
                'hash' => 'b9aef31849b9a84b6be2fe5a6722e6ecf255354f',
            ],
        ]
    ],
    [
        'code' => '62984',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\707GamesSlotServer\2021-03-09_080126_IT-ES-CH-RE-2021\com\greentube\slot\server\novomatic\definitions\lordoftheoceanmagic.aes',
                'hash' => 'fea76b02c734dfdaa4e5108a6731f93d6ba82ada',
            ],
            [
                'name' => 'D:\ServerIT\707GamesSlotServer\2021-03-09_080126_IT-ES-CH-RE-2021\707gamesslotserver.jar',
                'hash' => '0320d6f5288811cb960ad74dac9b86127ed24a8f',
            ],
        ]
    ],
    [
        'code' => '62987',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\707GamesSlotServer\2021-03-09_080126_IT-ES-CH-RE-2021\com\greentube\slot\server\novomatic\definitions\luckyladyscharmdeluxe6.',
                'hash' => 'cfb0b936f30db5d5568e8dc568816b13f42d9fee',
            ],
            [
                'name' => 'D:\ServerIT\707GamesSlotServer\2021-03-09_080126_IT-ES-CH-RE-2021\707gamesslotserver.jar',
                'hash' => '0320d6f5288811cb960ad74dac9b86127ed24a8f',
            ],
        ]
    ],
    [
        'code' => '62991',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\RedlineSlotServer\2021-03-09_090351_IT-ES-CH-RE-2021\redlineslotserver.jar',
                'hash' => '4f06cac1f7bb6406e8483051e68b57e2a958b613',
            ],
            [
                'name' => 'D:\ServerIT\RedlineSlotServer\2021-03-09_090351_IT-ES-CH-RE-2021\com\redlinegames\slot\server\definitions\luckystarspins.aes',
                'hash' => 'daa31355b4fdfa092a26fcd516e2ea8fbfd57c5f',
            ],
        ]
    ],
    [
        'code' => '63076',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\RedlineSlotServer\2021-03-09_090351_IT-ES-CH-RE-2021\redlineslotserver.jar',
                'hash' => '4f06cac1f7bb6406e8483051e68b57e2a958b613',
            ],
            [
                'name' => 'D:\ServerIT\RedlineSlotServer\2021-03-09_090351_IT-ES-CH-RE-2021\com\redlinegames\slot\server\definitions\sevenstaxx.aes',
                'hash' => 'b3026c81a006db125c48ea001b73040bf6526f5f',
            ],
        ]
    ],
    [
        'code' => '63078',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\GTUKSlotServer\2021-03-09_084744_IT-ES-CH-RE-2021\gtukslotserver.jar',
                'hash' => '0c9140aab2e15f89a736bacd9d77a2ce136f2998',
            ],
            [
                'name' => 'D:\ServerIT\GTUKSlotServer\2021-03-09_084744_IT-ES-CH-RE-2021\com\mazooma\server\definitions\ShootingStarsSupernova.aes',
                'hash' => '3386e8a813463b6dbd46672038f37dd6525c8f0b',
            ],
        ]
    ],
    [
        'code' => '63082',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'D:\ServerIT\707GamesSlotServer\2021-03-09_080126_IT-ES-CH-RE-2021\com\greentube\slot\server\novomatic\definitions\sizzlinghot6extragold.aes',
                'hash' => '308c68f66bb043b616f660a7925aff79f9a11c54',
            ],
            [
                'name' => 'D:\ServerIT\707GamesSlotServer\2021-03-09_080126_IT-ES-CH-RE-2021\707gamesslotserver.jar',
                'hash' => '0320d6f5288811cb960ad74dac9b86127ed24a8f',
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

