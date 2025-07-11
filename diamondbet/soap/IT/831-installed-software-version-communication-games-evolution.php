<?php

require_once '/var/www/videoslots.it/phive/phive.php';
$user = cu($argv[1] ?? 6624618);

$games = [
    [
        'code' => '62499',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'live-roulette/BetCode.class',
                'hash' => '12E8AF341F8EE82138F1B1D19E09BDE1967DB288',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-roulette/RouletteGame.class',
                'hash' => '9531EBAC4884CAF6D5D0652107DFB7B4B2FED8A1',
            ],
            [
                'name' => 'live-roulette/SbrBetCode.class',
                'hash' => 'AE714B20D7A0908E0C45B6660C09F0484192A901',
            ],
            [
                'name' => 'live-roulette/RouletteWinMultipliers.class',
                'hash' => 'E2E1B183DCE06A097388542C7A5A7A2917F7C5B6',
            ],
            [
                'name' => 'live-roulette/WinMultiplier.class',
                'hash' => 'FACD97862C74D6F401A3514DCE693D00595D8EC3',
            ],
        ]
    ],
    [
        'code' => '62500',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'live-roulette/BetCode.class',
                'hash' => '12E8AF341F8EE82138F1B1D19E09BDE1967DB288',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-roulette/RouletteGame.class',
                'hash' => '9531EBAC4884CAF6D5D0652107DFB7B4B2FED8A1',
            ],
            [
                'name' => 'live-roulette/SbrBetCode.class',
                'hash' => 'AE714B20D7A0908E0C45B6660C09F0484192A901',
            ],
            [
                'name' => 'live-roulette/RouletteWinMultipliers.class',
                'hash' => 'E2E1B183DCE06A097388542C7A5A7A2917F7C5B6',
            ],
            [
                'name' => 'live-roulette/WinMultiplier.class',
                'hash' => 'FACD97862C74D6F401A3514DCE693D00595D8EC3',
            ],
        ]
    ],
    [
        'code' => '62501',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'live-roulette/BetCode.class',
                'hash' => '12E8AF341F8EE82138F1B1D19E09BDE1967DB288',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-roulette/RouletteGame.class',
                'hash' => '9531EBAC4884CAF6D5D0652107DFB7B4B2FED8A1',
            ],
            [
                'name' => 'live-roulette/SbrBetCode.class',
                'hash' => 'AE714B20D7A0908E0C45B6660C09F0484192A901',
            ],
            [
                'name' => 'live-roulette/RouletteWinMultipliers.class',
                'hash' => 'E2E1B183DCE06A097388542C7A5A7A2917F7C5B6',
            ],
            [
                'name' => 'live-roulette/WinMultiplier.class',
                'hash' => 'FACD97862C74D6F401A3514DCE693D00595D8EC3',
            ],
        ]
    ],
    [
        'code' => '62502',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'live-roulette/WinMultiplier.class',
                'hash' => 'FACD97862C74D6F401A3514DCE693D00595D8EC3',
            ],
            [
                'name' => 'live-roulette/RouletteGame.class',
                'hash' => '9531EBAC4884CAF6D5D0652107DFB7B4B2FED8A1',
            ],
            [
                'name' => 'live-roulette/AmericanBetCode.class',
                'hash' => '9448A80FA030258827FBBAB405F2ED1F700391F2',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-roulette/RouletteWinMultipliers.class',
                'hash' => 'E2E1B183DCE06A097388542C7A5A7A2917F7C5B6',
            ],
            [
                'name' => 'live-roulette/BetCode.class',
                'hash' => '12E8AF341F8EE82138F1B1D19E09BDE1967DB288',
            ],
        ]
    ],
    [
        'code' => '62503',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'live-roulette/BetCode.class',
                'hash' => '12E8AF341F8EE82138F1B1D19E09BDE1967DB288',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-roulette/RouletteGame.class',
                'hash' => '9531EBAC4884CAF6D5D0652107DFB7B4B2FED8A1',
            ],
            [
                'name' => 'live-roulette/DbrBetCode.class',
                'hash' => 'BE1AC17B8EF1DC5CC740E35ABA4CADBEDF91F973',
            ],
            [
                'name' => 'live-roulette/RouletteWinMultipliers.class',
                'hash' => 'E2E1B183DCE06A097388542C7A5A7A2917F7C5B6',
            ],
            [
                'name' => 'live-roulette/WinMultiplier.class',
                'hash' => 'FACD97862C74D6F401A3514DCE693D00595D8EC3',
            ],
        ]
    ],
    [
        'code' => '62506',
        'cert_ver' => '4',
        'software_modules' => [
            [
                'name' => 'rng-service/RngService.class',
                'hash' => '06242EA1D62E620707E0CFB9F3BD77C01FAC8183',
            ],
            [
                'name' => 'live-roulette/BetCode.class',
                'hash' => '12E8AF341F8EE82138F1B1D19E09BDE1967DB288',
            ],
            [
                'name' => 'rng-service-extras/WeightedChoice.class',
                'hash' => '3FAABB401B97C9CD8A07B5EF82BB151483F1D727',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'rng-service-extras/FloatRngService.class',
                'hash' => '4B5B324C80D0E64E9317B7E23795372EF3C8FD6D',
            ],
            [
                'name' => 'rng-service-extras/ShufflingRngService.class',
                'hash' => '50741B2C3B26F063FAD62F530978EEF53144C2B2',
            ],
            [
                'name' => 'live-roulette/LuckyNumbersState.class',
                'hash' => '50B0BD80576038735ABFC7F9C5E31501B1527DC1',
            ],
            [
                'name' => 'rng-service-extras/WeightedRngService.class',
                'hash' => '584681BBB6CEB7FA7EB6DFA800E0DF5E0CD2B111',
            ],
            [
                'name' => 'live-roulette/RouletteGame.class',
                'hash' => '9531EBAC4884CAF6D5D0652107DFB7B4B2FED8A1',
            ],
            [
                'name' => 'live-roulette/LuckyNumbersServiceImpl.class',
                'hash' => '96F6664E4C8EE82CC452DECA115E3D7D2947EE50',
            ],
            [
                'name' => 'live-roulette/SbrBetCode.class',
                'hash' => 'AE714B20D7A0908E0C45B6660C09F0484192A901',
            ],
            [
                'name' => 'rng-service-extras/RngServiceExtras.class',
                'hash' => 'C567DED9539043425AB362564F0282BA95FFE8A3',
            ],
            [
                'name' => 'live-roulette/RouletteWinMultipliers.class',
                'hash' => 'E2E1B183DCE06A097388542C7A5A7A2917F7C5B6',
            ],
            [
                'name' => 'live-roulette/WinMultiplier.class',
                'hash' => 'FACD97862C74D6F401A3514DCE693D00595D8EC3',
            ],
            [
                'name' => 'rng-service-extras/Weighted.class',
                'hash' => 'FB855DC1DE72CC935BCC1A793E1B5ABB22A76CB5',
            ],
        ]
    ],
    [
        'code' => '62508',
        'cert_ver' => '4',
        'software_modules' => [
            [
                'name' => 'live-blackjack/BlackjackUnseatHandler.class',
                'hash' => 'B28A93B89E8140E20348A808F04A0782786C2292',
            ],
            [
                'name' => 'live-blackjack/BlackjackContext.class',
                'hash' => 'B3B972983C8B0913BFE7EC4E7B53934C1AAF24F2',
            ],
            [
                'name' => 'live-blackjack/EarlyCashOutPayoutRates.class',
                'hash' => '2F88B3B68769DBD0FCD724E67E4EFB08FA77B821',
            ],
            [
                'name' => 'live-blackjack/DecisionService.class',
                'hash' => '66B6889A35BCE88D973CBE9769CAE8589554AF6D',
            ],
            [
                'name' => 'live-blackjack/BJSideBetCombination.class',
                'hash' => 'BE3CD09FCF4C2731CD5BAD0CB1994CACE90C8E0F',
            ],
            [
                'name' => 'live-blackjack/Hand.class',
                'hash' => '00CEDC8B4E017A62B48F8BD6AC0BEDF25CDE16FC',
            ],
            [
                'name' => 'live-blackjack/Bet.class',
                'hash' => '85612EE09FD435FD1A63D41615B12E469A07E574',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-blackjack/BlackjackPlayerSettingsHandler.class',
                'hash' => '391A7E5BA80FD7A3567E3334280E77259175402A',
            ],
            [
                'name' => 'live-blackjack/Cards.class',
                'hash' => '59AF26B0DD274C538529FB1F2B8112BAC975BAD9',
            ],
            [
                'name' => 'live-blackjack/BlackjackGame.class',
                'hash' => 'EE8851FF342C47341478DE6D4A28109591389886',
            ],
            [
                'name' => 'live-blackjack/InsuranceService.class',
                'hash' => '4EDF96DB13A3BA96F59633348F985574F551BA06',
            ],
            [
                'name' => 'live-blackjack/ClassicPayoutRate.class',
                'hash' => '55F17A2D1CD7A0FEE7FF60EA565F6754E88C38C9',
            ],
            [
                'name' => 'live-blackjack/ClassicBlackjackContext.class',
                'hash' => '99093004801C4485E38D4080FEA8FCFE871A6BE4',
            ],
            [
                'name' => 'live-blackjack/BlackjackResult.class',
                'hash' => '299E15E6D35E5B663A1E9749D8A90A2157C7C976',
            ],
        ]
    ],
    [
        'code' => '62512',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'live-baccarat/BaccaratGame.class',
                'hash' => '1DA36380967151D61FF67C08BAE4F5E8A945A44A',
            ],
            [
                'name' => 'baccarat-domain/BaccaratModel.class',
                'hash' => '581FE6B52492790D3B7CC4068B93B2CD10C02498',
            ],
            [
                'name' => 'baccarat-domain/BaccaratPayouts.class',
                'hash' => '042E3BC890A305299369586A38B968F445A98D5C',
            ],
            [
                'name' => 'baccarat-domain/Hand.class',
                'hash' => '6C3DB7F86687E529EC3F90655DAB4836FA28DFA8',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
        ]
    ],
    [
        'code' => '62515',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'baccarat-domain/BaccaratPayouts.class',
                'hash' => '042E3BC890A305299369586A38B968F445A98D5C',
            ],
            [
                'name' => 'live-baccarat/BaccaratGame.class',
                'hash' => '1DA36380967151D61FF67C08BAE4F5E8A945A44A',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'baccarat-domain/BaccaratModel.class',
                'hash' => '581FE6B52492790D3B7CC4068B93B2CD10C02498',
            ],
            [
                'name' => 'baccarat-domain/Hand.class',
                'hash' => '6C3DB7F86687E529EC3F90655DAB4836FA28DFA8',
            ],
        ]
    ],
    [
        'code' => '62526',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'live-dragontiger/DragonTigerWinner.class',
                'hash' => '0053BAEFE6CF7672134C435F3ADD6C9453A2065D',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-dragontiger/DragonTigerPayout.class',
                'hash' => '4E854E824E750B617865543F446765D583B21F44',
            ],
            [
                'name' => 'live-dragontiger/DragonTigerGame.class',
                'hash' => 'D9DF4715420BAF420DD4B09AA19EA5998197E19A',
            ],
        ]
    ],
    [
        'code' => '62527',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'live-poker/Unique5Lookup.class',
                'hash' => '223DD503697C9CE0FF75E3D9545314B98F1258C1',
            ],
            [
                'name' => 'live-poker/PokerHandEvaluation.class',
                'hash' => 'F38FC4A7C9E8863952F9E1EA9A28AE2BE621A373',
            ],
            [
                'name' => 'live-poker/TexasPayTable.class',
                'hash' => 'C3F17A8C91825AF0184F015B389DF53324C2F752',
            ],
            [
                'name' => 'live-poker/HashLookup.class',
                'hash' => '650DF1920B6E9CBA649CE5769F170CC81662C816',
            ],
            [
                'name' => 'live-poker/FlushLookup.class',
                'hash' => 'BE99C11925091417CEEE964B4A9F5B391D244077',
            ],
            [
                'name' => 'live-poker/HashAdjust.class',
                'hash' => 'F086133F64CC8BB893928F0BABBCC2FDD23AB2E4',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-poker/HoldemEvaluation.class',
                'hash' => '9A929E3947207C0B90A76DC40016FC99D6F9446C',
            ],
            [
                'name' => 'live-poker/Payouts.class',
                'hash' => 'A1496F8CA33BE3E295EE7AED5EAC953E88141077',
            ],
            [
                'name' => 'live-poker/CasinoHoldemGame.class',
                'hash' => '2855B31A9E02DEEE4D780D00FB8E9A8947A2164A',
            ],
            [
                'name' => 'live-poker/PokerOutcomeEvaluated.class',
                'hash' => '10BF5D7C60DBB1B5B449AD4AF18A507098AAFB5E',
            ],
        ]
    ],
    [
        'code' => '62528',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'live-poker/ThreeCardHandEvaluation.class',
                'hash' => '13F8C1FF9F7D35CD4E70174E11E9B9F8C178BBB1',
            ],
            [
                'name' => 'live-poker/Unique5Lookup.class',
                'hash' => '223DD503697C9CE0FF75E3D9545314B98F1258C1',
            ],
            [
                'name' => 'live-poker/ThreeCardPayouts.class',
                'hash' => '4381F351564DECC46BD22B0DF1E0473128244A8E',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-poker/HashLookup.class',
                'hash' => '650DF1920B6E9CBA649CE5769F170CC81662C816',
            ],
            [
                'name' => 'live-poker/TcpGameResult.class',
                'hash' => '7A5489E7C39D9FB64AB3B01502B23A1C835EE356',
            ],
            [
                'name' => 'live-poker/TcpGame.class',
                'hash' => 'BADA2F3AAFFD4010678C02920E7BB74097C147E7',
            ],
            [
                'name' => 'live-poker/FlushLookup.class',
                'hash' => 'BE99C11925091417CEEE964B4A9F5B391D244077',
            ],
            [
                'name' => 'live-poker/ThreeCardEvaluation.class',
                'hash' => 'E65EF553A22107261BC37FB9C22229E01BC405CE',
            ],
            [
                'name' => 'live-poker/HashAdjust.class',
                'hash' => 'F086133F64CC8BB893928F0BABBCC2FDD23AB2E4',
            ],
            [
                'name' => 'live-poker/PokerHandEvaluation.class',
                'hash' => 'F38FC4A7C9E8863952F9E1EA9A28AE2BE621A373',
            ],
        ]
    ],
    [
        'code' => '62529',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'live-poker/TexasPayTable.class',
                'hash' => 'C3F17A8C91825AF0184F015B389DF53324C2F752',
            ],
            [
                'name' => 'live-poker/HashLookup.class',
                'hash' => '650DF1920B6E9CBA649CE5769F170CC81662C816',
            ],
            [
                'name' => 'live-poker/Payouts.class',
                'hash' => 'A1496F8CA33BE3E295EE7AED5EAC953E88141077',
            ],
            [
                'name' => 'live-poker/HashAdjust.class',
                'hash' => 'F086133F64CC8BB893928F0BABBCC2FDD23AB2E4',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-poker/FlushLookup.class',
                'hash' => 'BE99C11925091417CEEE964B4A9F5B391D244077',
            ],
            [
                'name' => 'live-poker/Unique5Lookup.class',
                'hash' => '223DD503697C9CE0FF75E3D9545314B98F1258C1',
            ],
            [
                'name' => 'live-poker/PokerHandEvaluation.class',
                'hash' => 'F38FC4A7C9E8863952F9E1EA9A28AE2BE621A373',
            ],
            [
                'name' => 'live-poker/CaribbeanStudGame.class',
                'hash' => '9679E67902888B9B346460C882A818EA50460177',
            ],
        ]
    ],
    [
        'code' => '62530',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'live-poker/PokerOutcomeEvaluated.class',
                'hash' => '10BF5D7C60DBB1B5B449AD4AF18A507098AAFB5E',
            ],
            [
                'name' => 'live-poker/Unique5Lookup.class',
                'hash' => '223DD503697C9CE0FF75E3D9545314B98F1258C1',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-poker/HashLookup.class',
                'hash' => '650DF1920B6E9CBA649CE5769F170CC81662C816',
            ],
            [
                'name' => 'live-poker/HoldemEvaluation.class',
                'hash' => '9A929E3947207C0B90A76DC40016FC99D6F9446C',
            ],
            [
                'name' => 'live-poker/Payouts.class',
                'hash' => 'A1496F8CA33BE3E295EE7AED5EAC953E88141077',
            ],
            [
                'name' => 'live-poker/FlushLookup.class',
                'hash' => 'BE99C11925091417CEEE964B4A9F5B391D244077',
            ],
            [
                'name' => 'live-poker/TexasPayTable.class',
                'hash' => 'C3F17A8C91825AF0184F015B389DF53324C2F752',
            ],
            [
                'name' => 'live-poker/HashAdjust.class',
                'hash' => 'F086133F64CC8BB893928F0BABBCC2FDD23AB2E4',
            ],
            [
                'name' => 'live-poker/PokerHandEvaluation.class',
                'hash' => 'F38FC4A7C9E8863952F9E1EA9A28AE2BE621A373',
            ],
        ]
    ],
    [
        'code' => '62531',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'live-poker/PokerOutcomeEvaluated.class',
                'hash' => '10BF5D7C60DBB1B5B449AD4AF18A507098AAFB5E',
            ],
            [
                'name' => 'live-poker/TexasBonusGame.class',
                'hash' => '19B7C421E375CEF7A7D0F0D73565F9996F9B1739',
            ],
            [
                'name' => 'live-poker/Unique5Lookup.class',
                'hash' => '223DD503697C9CE0FF75E3D9545314B98F1258C1',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-poker/HashLookup.class',
                'hash' => '650DF1920B6E9CBA649CE5769F170CC81662C816',
            ],
            [
                'name' => 'live-poker/HoldemEvaluation.class',
                'hash' => '9A929E3947207C0B90A76DC40016FC99D6F9446C',
            ],
            [
                'name' => 'live-poker/Payouts.class',
                'hash' => 'A1496F8CA33BE3E295EE7AED5EAC953E88141077',
            ],
            [
                'name' => 'live-poker/FlushLookup.class',
                'hash' => 'BE99C11925091417CEEE964B4A9F5B391D244077',
            ],
            [
                'name' => 'live-poker/TexasPayTable.class',
                'hash' => 'C3F17A8C91825AF0184F015B389DF53324C2F752',
            ],
            [
                'name' => 'live-poker/HashAdjust.class',
                'hash' => 'F086133F64CC8BB893928F0BABBCC2FDD23AB2E4',
            ],
            [
                'name' => 'live-poker/PokerHandEvaluation.class',
                'hash' => 'F38FC4A7C9E8863952F9E1EA9A28AE2BE621A373',
            ],
        ]
    ],
    [
        'code' => '62533',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'live-dragontiger/DragonTigerWinner.class',
                'hash' => '0053BAEFE6CF7672134C435F3ADD6C9453A2065D',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-dragontiger/DragonTigerPayout.class',
                'hash' => '4E854E824E750B617865543F446765D583B21F44',
            ],
            [
                'name' => 'live-dragontiger/DragonTigerCard.class',
                'hash' => '79FEC6717E22D9D446C7EA8E8C23DDDE45AE6229',
            ],
            [
                'name' => 'live-dragontiger/DragonTigerGame.class',
                'hash' => 'D9DF4715420BAF420DD4B09AA19EA5998197E19A',
            ],
        ]
    ],
    [
        'code' => '62534',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'live-moneywheel/MoneyWheelConfig.class',
                'hash' => '02E82241B741AF18F3871FB1D38932C8EE800804',
            ],
            [
                'name' => 'live-moneywheel/BetCode.class',
                'hash' => '064BFEFDD0D6AF33ADBCF9268F03E70F2047C3F9',
            ],
            [
                'name' => 'live-moneywheel/MoneyWheelTable.class',
                'hash' => '194CBE5E0E7E5FD33B9A673C48F666F081F8EFA9',
            ],
            [
                'name' => 'live-moneywheel/MoneyWheelResult.class',
                'hash' => '3379493ED6C09FB2366EB860C2D467B71041BEF1',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'game-common/WheelGame.class',
                'hash' => '4EB11EACC43BE055AE78753C1E3E05FC32E87EF5',
            ],
            [
                'name' => 'game-common/MaxPayoutLimit.class',
                'hash' => '9E47EB0BD4365B7F3503F5AF7819F994FF9BE27E',
            ],
            [
                'name' => 'live-moneywheel/ResultCode.class',
                'hash' => 'A57FCC665AA4B0D889B7D03691F95F8189F58B2D',
            ],
        ]
    ],
    [
        'code' => '62535',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'live-sbj/SideBetCombination.class',
                'hash' => '1F934526426010C9BDBE2343D2B8F4EFC424A536',
            ],
            [
                'name' => 'live-sbj/Cards.class',
                'hash' => '244D2AF4451DE3801CC3C9C63DCEC9A4165AC210',
            ],
            [
                'name' => 'live-sbj/GameResultEvaluation.class',
                'hash' => '3006C6E8E9731BFF6ABFCD3170C4AB380373EA5A',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-sbj/PlayerHands.class',
                'hash' => '97E4DC036B6BC70AA48321128CB2B4B06E36D027',
            ],
            [
                'name' => 'live-sbj/ScalableBlackjackGame.class',
                'hash' => 'B7C1E4711F4BF74C9A902B0AAB1124E1F0C005C1',
            ],
            [
                'name' => 'live-sbj/PayoutEvaluation.class',
                'hash' => 'C0AB917DCAE4253232256F3F73B178E35053D7AA',
            ],
            [
                'name' => 'live-sbj/BettingProcessor.class',
                'hash' => 'EDBDB4E8FD1ADA908E6C916CE82D8CD4AA163B6D',
            ],
            [
                'name' => 'live-sbj/Rules.class',
                'hash' => 'F439F5D119760F8A588EB24FE7EB4283D6C1939D',
            ],
        ]
    ],
    [
        'code' => '62536',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'rng-service/RngService.class',
                'hash' => '06242EA1D62E620707E0CFB9F3BD77C01FAC8183',
            ],
            [
                'name' => 'live-monopoly/ChanceResults.class',
                'hash' => '2ADFE4FCDBDAC1A2E0640756688966799819E38D',
            ],
            [
                'name' => 'live-moneywheel/MoneyWheelResult.class',
                'hash' => '3379493ED6C09FB2366EB860C2D467B71041BEF1',
            ],
            [
                'name' => 'rng-service-extras/WeightedChoice.class',
                'hash' => '3FAABB401B97C9CD8A07B5EF82BB151483F1D727',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'rng-service-extras/FloatRngService.class',
                'hash' => '4B5B324C80D0E64E9317B7E23795372EF3C8FD6D',
            ],
            [
                'name' => 'wheel-game-common/WheelGame.class',
                'hash' => '4EB11EACC43BE055AE78753C1E3E05FC32E87EF5',
            ],
            [
                'name' => 'rng-service-extras/ShufflingRngService.class',
                'hash' => '50741B2C3B26F063FAD62F530978EEF53144C2B2',
            ],
            [
                'name' => 'rng-service-extras/WeightedRngService.class',
                'hash' => '584681BBB6CEB7FA7EB6DFA800E0DF5E0CD2B111',
            ],
            [
                'name' => 'live-monopoly/WheelChance.class',
                'hash' => '785D0C0C656B930CF1C2D51966626D1E862FCACB',
            ],
            [
                'name' => 'live-monopoly/Square.class',
                'hash' => '8522DB213C01A976F220B44F77C24C0B3AC0D4AA',
            ],
            [
                'name' => 'live-monopoly/MonopolyBoard.class',
                'hash' => '9E0159E388BBA9EB13AF7E91069869497EA30ABA',
            ],
            [
                'name' => 'wheel-game-common/MaxPayoutLimit.class',
                'hash' => '9E47EB0BD4365B7F3503F5AF7819F994FF9BE27E',
            ],
            [
                'name' => 'rng-service-extras/RngServiceExtras.class',
                'hash' => 'C567DED9539043425AB362564F0282BA95FFE8A3',
            ],
            [
                'name' => 'live-monopoly/MonopolyTable.class',
                'hash' => 'CBF5A548621F9E0A3A14AB1300E41C1D055AA4E2',
            ],
            [
                'name' => 'live-monopoly/PropertyInfo.class',
                'hash' => 'DA6D909DE98BAB2A04F81FD6850E8E52EC5493D8',
            ],
            [
                'name' => 'live-monopoly/MonopolyWheelResult.class',
                'hash' => 'DEAA4558626E40B0A7275D42BEBDD7FA98EDB37F',
            ],
            [
                'name' => 'live-monopoly/UpgradeInfo.class',
                'hash' => 'DF1912A69A34355A8D0AE59242DD831D50909580',
            ],
            [
                'name' => 'live-monopoly/ChanceCard.class',
                'hash' => 'E533B9AA039236E33C7AD10EF69905AEF037AA4C',
            ],
            [
                'name' => 'rng-service-extras/Weighted.class',
                'hash' => 'FB855DC1DE72CC935BCC1A793E1B5ABB22A76CB5',
            ],
        ]
    ],
    [
        'code' => '62537',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'rng-service/RngService.class',
                'hash' => '06242EA1D62E620707E0CFB9F3BD77C01FAC8183',
            ],
            [
                'name' => 'live-dice/LuckyNumbersService.class',
                'hash' => '1D9C855079BA8705B516078A9D18E633CAA76188',
            ],
            [
                'name' => 'rng-service-extras/WeightedChoice.class',
                'hash' => '3FAABB401B97C9CD8A07B5EF82BB151483F1D727',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-dice/CloseBetsHandler.class',
                'hash' => '4AA13601734EA85A4279F768599D6DDCC428C34C',
            ],
            [
                'name' => 'rng-service-extras/FloatRngService.class',
                'hash' => '4B5B324C80D0E64E9317B7E23795372EF3C8FD6D',
            ],
            [
                'name' => 'rng-service-extras/ShufflingRngService.class',
                'hash' => '50741B2C3B26F063FAD62F530978EEF53144C2B2',
            ],
            [
                'name' => 'live-dice/LuckyNumberGeneratedHandler.class',
                'hash' => '5737D66F860AB6D619C5DCA6E3CB194C783E4323',
            ],
            [
                'name' => 'rng-service-extras/WeightedRngService.class',
                'hash' => '584681BBB6CEB7FA7EB6DFA800E0DF5E0CD2B111',
            ],
            [
                'name' => 'dice-domain/LuckyNumbersState.class',
                'hash' => '98859F3AAB174AD0A3F1D4004D939FC619177720',
            ],
            [
                'name' => 'dice-domain/BetCode.class',
                'hash' => 'A33BBBE9EBC3488E6089CD7B828B5B4BC46AF521',
            ],
            [
                'name' => 'live-dice/DiceGame.class',
                'hash' => 'B2635FE4B4960FCEE0332A3818EC43A1BE116861',
            ],
            [
                'name' => 'dice-domain/DiceWinMultipliers.class',
                'hash' => 'BB5361502DFAB0EEA263F05AB721FC6C53E0E43D',
            ],
            [
                'name' => 'rng-service-extras/RngServiceExtras.class',
                'hash' => 'C567DED9539043425AB362564F0282BA95FFE8A3',
            ],
            [
                'name' => 'rng-service-extras/Weighted.class',
                'hash' => 'FB855DC1DE72CC935BCC1A793E1B5ABB22A76CB5',
            ],
        ]
    ],
    [
        'code' => '62538',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'rng-service/RngService.class',
                'hash' => '06242EA1D62E620707E0CFB9F3BD77C01FAC8183',
            ],
            [
                'name' => 'dealnodeal-game-messages/SpinRingsBoughtCount.class',
                'hash' => '0EAFD0CD7D874E3DF14FBFBBA9902A391752FE5B',
            ],
            [
                'name' => 'dealnodeal-game-messages/PlayerOffer.class',
                'hash' => '2ADA608ACA49ED529302028AF77C6771EAE16C1C',
            ],
            [
                'name' => 'rng-service-extras/WeightedChoice.class',
                'hash' => '3FAABB401B97C9CD8A07B5EF82BB151483F1D727',
            ],
            [
                'name' => 'dealnodeal-game-messages/GameBoxes.class',
                'hash' => '401FDF0C13AA64F4F3C93646E04BCF42A679F109',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'rng-service-extras/FloatRngService.class',
                'hash' => '4B5B324C80D0E64E9317B7E23795372EF3C8FD6D',
            ],
            [
                'name' => 'rng-service-extras/ShufflingRngService.class',
                'hash' => '50741B2C3B26F063FAD62F530978EEF53144C2B2',
            ],
            [
                'name' => 'dealnodeal-game-messages/DealNoDealRandomService.class',
                'hash' => '57EC3CE53085B9EAA68FBE9937C107857730BBF4',
            ],
            [
                'name' => 'rng-service-extras/WeightedRngService.class',
                'hash' => '584681BBB6CEB7FA7EB6DFA800E0DF5E0CD2B111',
            ],
            [
                'name' => 'dealnodeal-game-messages/DealNoDealRandomServiceImpl.class',
                'hash' => '7DDE5267DA73500711318882331B60113648EF52',
            ],
            [
                'name' => 'dealnodeal-game-messages/TopUpAmount.class',
                'hash' => 'B3275E671B7ECFB64B0CB095456CD1C67ACDBF15',
            ],
            [
                'name' => 'live-dealnodeal/PredictableDealNoDealRandomService.class',
                'hash' => 'C32EE2280DCCA21048DA4D5A1164EBE70DD95539',
            ],
            [
                'name' => 'dealnodeal-game-messages/BoxNumber.class',
                'hash' => 'C53A4756E80F7C57D48ACCE2B27AC5586E3F6440',
            ],
            [
                'name' => 'rng-service-extras/RngServiceExtras.class',
                'hash' => 'C567DED9539043425AB362564F0282BA95FFE8A3',
            ],
            [
                'name' => 'dealnodeal-game-messages/PlayerBoxes.class',
                'hash' => 'DE80E0B1E262098D96709C862A6D75A081F64428',
            ],
            [
                'name' => 'dealnodeal-game-messages/DealNoDealMath.class',
                'hash' => 'ECEDA2C011E8054A4937EBB9185ACE621FB3B424',
            ],
            [
                'name' => 'rng-service-extras/Weighted.class',
                'hash' => 'FB855DC1DE72CC935BCC1A793E1B5ABB22A76CB5',
            ],
        ]
    ],
    [
        'code' => '62539',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'live-poker/Payouts.class',
                'hash' => 'A1496F8CA33BE3E295EE7AED5EAC953E88141077',
            ],
            [
                'name' => 'live-poker/HashAdjust.class',
                'hash' => 'F086133F64CC8BB893928F0BABBCC2FDD23AB2E4',
            ],
            [
                'name' => 'live-poker/HoldemEvaluation.class',
                'hash' => '9A929E3947207C0B90A76DC40016FC99D6F9446C',
            ],
            [
                'name' => 'live-poker/HashLookup.class',
                'hash' => '650DF1920B6E9CBA649CE5769F170CC81662C816',
            ],
            [
                'name' => 'live-poker/FlushLookup.class',
                'hash' => 'BE99C11925091417CEEE964B4A9F5B391D244077',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-poker/PokerHandEvaluation.class',
                'hash' => 'F38FC4A7C9E8863952F9E1EA9A28AE2BE621A373',
            ],
            [
                'name' => 'live-poker/PokerOutcomeEvaluated.class',
                'hash' => '10BF5D7C60DBB1B5B449AD4AF18A507098AAFB5E',
            ],
            [
                'name' => 'live-poker/Unique5Lookup.class',
                'hash' => '223DD503697C9CE0FF75E3D9545314B98F1258C1',
            ],
            [
                'name' => 'live-poker/DoubleHandHoldemGame.class',
                'hash' => '5A89AC58469CFF00D0FC0FE82761B82726F07E9A',
            ],
            [
                'name' => 'live-poker/TexasPayTable.class',
                'hash' => 'C3F17A8C91825AF0184F015B389DF53324C2F752',
            ],
        ]
    ],
    [
        'code' => '62540',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'live-sbj/SideBetCombination.class',
                'hash' => '1F934526426010C9BDBE2343D2B8F4EFC424A536',
            ],
            [
                'name' => 'live-sbj/Cards.class',
                'hash' => '244D2AF4451DE3801CC3C9C63DCEC9A4165AC210',
            ],
            [
                'name' => 'live-sbj/GameResultEvaluation.class',
                'hash' => '3006C6E8E9731BFF6ABFCD3170C4AB380373EA5A',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-sbj/PlayerHands.class',
                'hash' => '97E4DC036B6BC70AA48321128CB2B4B06E36D027',
            ],
            [
                'name' => 'live-sbj/ScalableBlackjackGame.class',
                'hash' => 'B7C1E4711F4BF74C9A902B0AAB1124E1F0C005C1',
            ],
            [
                'name' => 'live-sbj/PayoutEvaluation.class',
                'hash' => 'C0AB917DCAE4253232256F3F73B178E35053D7AA',
            ],
            [
                'name' => 'live-sbj/BettingProcessor.class',
                'hash' => 'EDBDB4E8FD1ADA908E6C916CE82D8CD4AA163B6D',
            ],
            [
                'name' => 'live-sbj/Rules.class',
                'hash' => 'F439F5D119760F8A588EB24FE7EB4283D6C1939D',
            ],
        ]
    ],
    [
        'code' => '62541',
        'cert_ver' => '4',
        'software_modules' => [
            [
                'name' => 'rng-service/RngService.class',
                'hash' => '06242EA1D62E620707E0CFB9F3BD77C01FAC8183',
            ],
            [
                'name' => 'live-dice/LuckyNumbersService.class',
                'hash' => '1D9C855079BA8705B516078A9D18E633CAA76188',
            ],
            [
                'name' => 'rng-service-extras/WeightedChoice.class',
                'hash' => '3FAABB401B97C9CD8A07B5EF82BB151483F1D727',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-dice/CloseBetsHandler.class',
                'hash' => '4AA13601734EA85A4279F768599D6DDCC428C34C',
            ],
            [
                'name' => 'rng-service-extras/FloatRngService.class',
                'hash' => '4B5B324C80D0E64E9317B7E23795372EF3C8FD6D',
            ],
            [
                'name' => 'rng-service-extras/ShufflingRngService.class',
                'hash' => '50741B2C3B26F063FAD62F530978EEF53144C2B2',
            ],
            [
                'name' => 'live-dice/LuckyNumberGeneratedHandler.class',
                'hash' => '5737D66F860AB6D619C5DCA6E3CB194C783E4323',
            ],
            [
                'name' => 'rng-service-extras/WeightedRngService.class',
                'hash' => '584681BBB6CEB7FA7EB6DFA800E0DF5E0CD2B111',
            ],
            [
                'name' => 'dice-domain/LuckyNumbersState.class',
                'hash' => '98859F3AAB174AD0A3F1D4004D939FC619177720',
            ],
            [
                'name' => 'dice-domain/BetCode.class',
                'hash' => 'A33BBBE9EBC3488E6089CD7B828B5B4BC46AF521',
            ],
            [
                'name' => 'live-dice/DiceGame.class',
                'hash' => 'B2635FE4B4960FCEE0332A3818EC43A1BE116861',
            ],
            [
                'name' => 'dice-domain/DiceWinMultipliers.class',
                'hash' => 'BB5361502DFAB0EEA263F05AB721FC6C53E0E43D',
            ],
            [
                'name' => 'rng-service-extras/RngServiceExtras.class',
                'hash' => 'C567DED9539043425AB362564F0282BA95FFE8A3',
            ],
            [
                'name' => 'rng-service-extras/Weighted.class',
                'hash' => 'FB855DC1DE72CC935BCC1A793E1B5ABB22A76CB5',
            ],
        ]
    ],
    [
        'code' => '62542',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'sidebetcity/SidebetCityPayouts.class',
                'hash' => '096E91CC6208D685AF158CD4C5EED813129A6F26',
            ],
            [
                'name' => 'live-poker/ThreeCardHandEvaluation.class',
                'hash' => '13F8C1FF9F7D35CD4E70174E11E9B9F8C178BBB1',
            ],
            [
                'name' => 'sidebetcity/HandEval.class',
                'hash' => '166EA3BC304A1366A57485B198C48266ABB6291F',
            ],
            [
                'name' => 'live-poker/Unique5Lookup.class',
                'hash' => '223DD503697C9CE0FF75E3D9545314B98F1258C1',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-poker/HashLookup.class',
                'hash' => '650DF1920B6E9CBA649CE5769F170CC81662C816',
            ],
            [
                'name' => 'sidebetcity/PayTable.class',
                'hash' => 'BCB51F46C21F7B897C53BD31BE23FB258F243DFD',
            ],
            [
                'name' => 'live-poker/FlushLookup.class',
                'hash' => 'BE99C11925091417CEEE964B4A9F5B391D244077',
            ],
            [
                'name' => 'live-poker/HashAdjust.class',
                'hash' => 'F086133F64CC8BB893928F0BABBCC2FDD23AB2E4',
            ],
            [
                'name' => 'live-poker/PokerHandEvaluation.class',
                'hash' => 'F38FC4A7C9E8863952F9E1EA9A28AE2BE621A373',
            ],
        ]
    ],
    [
        'code' => '62543',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'baccarat-domain/BaccaratPayouts.class',
                'hash' => '042E3BC890A305299369586A38B968F445A98D5C',
            ],
            [
                'name' => 'rng-service/RngService.class',
                'hash' => '06242EA1D62E620707E0CFB9F3BD77C01FAC8183',
            ],
            [
                'name' => 'baccarat-domain/LightningConstants.class',
                'hash' => '0F07CB1E089A2F8B749B399C31BE7B658FC98BAC',
            ],
            [
                'name' => 'live-baccarat/BaccaratGame.class',
                'hash' => '1DA36380967151D61FF67C08BAE4F5E8A945A44A',
            ],
            [
                'name' => 'baccarat-domain/LightningMultipliersGenerator.class',
                'hash' => '252B047038EED4420A66A518B36466B738B8DB27',
            ],
            [
                'name' => 'rng-service-extras/WeightedChoice.class',
                'hash' => '3FAABB401B97C9CD8A07B5EF82BB151483F1D727',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-baccarat/BaccaratEngine.class',
                'hash' => '4739EF199E8116A3591A6538D8664D83B691B10D',
            ],
            [
                'name' => 'rng-service-extras/FloatRngService.class',
                'hash' => '4B5B324C80D0E64E9317B7E23795372EF3C8FD6D',
            ],
            [
                'name' => 'rng-service-extras/ShufflingRngService.class',
                'hash' => '50741B2C3B26F063FAD62F530978EEF53144C2B2',
            ],
            [
                'name' => 'baccarat-domain/BaccaratModel.class',
                'hash' => '581FE6B52492790D3B7CC4068B93B2CD10C02498',
            ],
            [
                'name' => 'rng-service-extras/WeightedRngService.class',
                'hash' => '584681BBB6CEB7FA7EB6DFA800E0DF5E0CD2B111',
            ],
            [
                'name' => 'baccarat-domain/Hand.class',
                'hash' => '6C3DB7F86687E529EC3F90655DAB4836FA28DFA8',
            ],
            [
                'name' => 'live-baccarat/PlayerBetting.class',
                'hash' => 'B454CB0BC7B5DD00AE2122D3D53C38EB4581A976',
            ],
            [
                'name' => 'rng-service-extras/RngServiceExtras.class',
                'hash' => 'C567DED9539043425AB362564F0282BA95FFE8A3',
            ],
            [
                'name' => 'live-baccarat/LightningHandler.class',
                'hash' => 'E952FE348415E902F758619413F33A8FE2ED76EE',
            ],
            [
                'name' => 'rng-service-extras/Weighted.class',
                'hash' => 'FB855DC1DE72CC935BCC1A793E1B5ABB22A76CB5',
            ],
        ]
    ],
    [
        'code' => '62544',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'rng-service/RngService.class',
                'hash' => '06242EA1D62E620707E0CFB9F3BD77C01FAC8183',
            ],
            [
                'name' => 'megaball-shared-domain/MegaBallMath.class',
                'hash' => '08FA42FF4A936631D6259E717348F987F70DAD65',
            ],
            [
                'name' => 'live-megaball/MegaBallCardsGenerator.class',
                'hash' => '1304AC3E9500E7BE14576908D2FF297BAA389AA5',
            ],
            [
                'name' => 'live-megaball/MegaBallGeneratorsFactory.class',
                'hash' => '3197464287658AA78E809998C4C8F862183BB38F',
            ],
            [
                'name' => 'megaball-shared-domain/MegaBallCard.class',
                'hash' => '358A88EE469805CAC680590E3256CFC6828183B6',
            ],
            [
                'name' => 'megaball-shared-domain/MissingBallsNumber.class',
                'hash' => '3B5734A85E63ED1397DF7614C12CEECB73FBDB6A',
            ],
            [
                'name' => 'rng-service-extras/WeightedChoice.class',
                'hash' => '3FAABB401B97C9CD8A07B5EF82BB151483F1D727',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'megaball-shared-domain/PowerMultiplier.class',
                'hash' => '459560DBC6F4EF40F06C2C551742AC402196191D',
            ],
            [
                'name' => 'rng-service-extras/FloatRngService.class',
                'hash' => '4B5B324C80D0E64E9317B7E23795372EF3C8FD6D',
            ],
            [
                'name' => 'rng-service-extras/ShufflingRngService.class',
                'hash' => '50741B2C3B26F063FAD62F530978EEF53144C2B2',
            ],
            [
                'name' => 'megaball-shared-domain/MegaBallCombination.class',
                'hash' => '52A76C73CA811E95E838448A79027426C027000A',
            ],
            [
                'name' => 'megaball-shared-domain/PowerMultipliersCount.class',
                'hash' => '539A9D211B4702DE42888C3D8570DD0ED20C0F1D',
            ],
            [
                'name' => 'megaball-shared-domain/PayoutLimiter.class',
                'hash' => '57D1D28051448D32AE4A881CECE62AB4211C8917',
            ],
            [
                'name' => 'rng-service-extras/WeightedRngService.class',
                'hash' => '584681BBB6CEB7FA7EB6DFA800E0DF5E0CD2B111',
            ],
            [
                'name' => 'megaball-shared-domain/CellIndex.class',
                'hash' => '5D7A1B9A8617AA9F73EEAC23DA159FD02199C90A',
            ],
            [
                'name' => 'megaball-shared-domain/SharedCardsGenerator.class',
                'hash' => '6419D8BF798112947AB6CE1099E74C2AB0472000',
            ],
            [
                'name' => 'megaball-shared-domain/CardPayoutCalculator.class',
                'hash' => '8FD91BE38B442E6B85CB8CFC102F322F68C931A1',
            ],
            [
                'name' => 'megaball-shared-domain/SharedUniqueCardsGenerator.class',
                'hash' => 'A4F66641E83A8370362BC4C5688BC9BA459FAEBB',
            ],
            [
                'name' => 'megaball-shared-domain/BallDrawer.class',
                'hash' => 'A89D6849D1C72F7BE27E13F36ACC8B783B5928D8',
            ],
            [
                'name' => 'rng-service-extras/RngServiceExtras.class',
                'hash' => 'C567DED9539043425AB362564F0282BA95FFE8A3',
            ],
            [
                'name' => 'megaball-shared-domain/MegaBallBall.class',
                'hash' => 'D86C96DEE0A2187B4B7177B6A58533F29C22BADE',
            ],
            [
                'name' => 'megaball-shared-domain/PowerMultipliersGenerator.class',
                'hash' => 'E459F5ECAE34EA3A3282C53A45166F7875369F79',
            ],
            [
                'name' => 'live-megaball/UniqueMegaBallCardsGenerator.class',
                'hash' => 'E96EEE2B94A87A7073EAF17EC78F79274EA7FD3F',
            ],
            [
                'name' => 'megaball-shared-domain/PowerBall.class',
                'hash' => 'F79D8E79D1265C0B4FE01DAD748DBC0C33BEDC4D',
            ],
            [
                'name' => 'rng-service-extras/Weighted.class',
                'hash' => 'FB855DC1DE72CC935BCC1A793E1B5ABB22A76CB5',
            ],
        ]
    ],
    [
        'code' => '62545',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'live-sbj/SideBetCombination.class',
                'hash' => '1F934526426010C9BDBE2343D2B8F4EFC424A536',
            ],
            [
                'name' => 'live-sbj/Cards.class',
                'hash' => '244D2AF4451DE3801CC3C9C63DCEC9A4165AC210',
            ],
            [
                'name' => 'live-sbj/GameResultEvaluation.class',
                'hash' => '3006C6E8E9731BFF6ABFCD3170C4AB380373EA5A',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-sbj/PlayerHands.class',
                'hash' => '97E4DC036B6BC70AA48321128CB2B4B06E36D027',
            ],
            [
                'name' => 'live-sbj/ScalableBlackjackGame.class',
                'hash' => 'B7C1E4711F4BF74C9A902B0AAB1124E1F0C005C1',
            ],
            [
                'name' => 'live-sbj/PayoutEvaluation.class',
                'hash' => 'C0AB917DCAE4253232256F3F73B178E35053D7AA',
            ],
            [
                'name' => 'live-sbj/BettingProcessor.class',
                'hash' => 'EDBDB4E8FD1ADA908E6C916CE82D8CD4AA163B6D',
            ],
            [
                'name' => 'live-sbj/Rules.class',
                'hash' => 'F439F5D119760F8A588EB24FE7EB4283D6C1939D',
            ],
        ]
    ],
    [
        'code' => '62546',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'live-instantroulette/SbrBetCode.class',
                'hash' => '39F665131E2F815E8A71022BE70CFCE3C5443001',
            ],
            [
                'name' => 'live-instantroulette/InstantRouletteWinMultipliers.class',
                'hash' => '3B337ADAA75ED4E985B77F2F65184F4992AF6042',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-instantroulette/BetCode.class',
                'hash' => '9ED68322C78114A8D65FDB7154E8DE1FEC0DD84E',
            ],
            [
                'name' => 'live-instantroulette/WheelHelper.class',
                'hash' => 'B3A2DAA6CAA91A5285DB11AA3553D42ACAA6BE61',
            ],
        ]
    ],
    [
        'code' => '62547',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'live-crazytime/SlotPack.class',
                'hash' => '025B44B7C2FB62227DDB15007F37E977966144AA',
            ],
            [
                'name' => 'rng-service/RngService.class',
                'hash' => '06242EA1D62E620707E0CFB9F3BD77C01FAC8183',
            ],
            [
                'name' => 'live-crazytime/Landing.class',
                'hash' => '127516778CED9759F10610A136466687609866E8',
            ],
            [
                'name' => 'live-crazytime/CrazyTimeGame.class',
                'hash' => '184BDA3B4FB31687F8B7DBB5B81E047E5F4E9649',
            ],
            [
                'name' => 'live-crazytime/CrazyTimeWheelResult.class',
                'hash' => '1BBA95418E2F1BFD3F0035ECED7B67DDC469C71F',
            ],
            [
                'name' => 'live-crazytime/SlotRng.class',
                'hash' => '1BDF203F3A62A408CA443F472D61BD23CB4357F6',
            ],
            [
                'name' => 'live-crazytime/CoinFlipRescue.class',
                'hash' => '2912912F471AE2E24C6F816EBC6241441D968462',
            ],
            [
                'name' => 'live-crazytime/OnPachinkoCommand.class',
                'hash' => '2D385F445CEC12BA0C8128C047B19ED2C2CD7899',
            ],
            [
                'name' => 'live-crazytime/OnCoinFlipCommand.class',
                'hash' => '334B9F9A5891E4BE068AE8C3971EA3E78309A40F',
            ],
            [
                'name' => 'live-crazytime/OnCashHuntCommand.class',
                'hash' => '35394846DFE5522D708CEFABA2834C71077882EC',
            ],
            [
                'name' => 'live-crazytime/CrazyTimeEngine.class',
                'hash' => '3B49E03B9EFB4D4857842FDD78158019B66410D4',
            ],
            [
                'name' => 'rng-service-extras/WeightedChoice.class',
                'hash' => '3FAABB401B97C9CD8A07B5EF82BB151483F1D727',
            ],
            [
                'name' => 'live-crazytime/PrizePack.class',
                'hash' => '435EFBAC6E33ED7AAC1F0D8995CBEA3C94C1FEF2',
            ],
            [
                'name' => 'live-crazytime/CrazyTimeWheelLayout.class',
                'hash' => '46461D8E954CAE56DBA75EBC4D1ADF06B68994F7',
            ],
            [
                'name' => 'rng-service-extras/FloatRngService.class',
                'hash' => '4B5B324C80D0E64E9317B7E23795372EF3C8FD6D',
            ],
            [
                'name' => 'rng-service-extras/ShufflingRngService.class',
                'hash' => '50741B2C3B26F063FAD62F530978EEF53144C2B2',
            ],
            [
                'name' => 'live-crazytime/CrazyBonus.class',
                'hash' => '5816DC9408259DBD97134650F3F0D72E29A5B38B',
            ],
            [
                'name' => 'rng-service-extras/WeightedRngService.class',
                'hash' => '584681BBB6CEB7FA7EB6DFA800E0DF5E0CD2B111',
            ],
            [
                'name' => 'live-crazytime/Zone.class',
                'hash' => '93B0B34E78D96345D6613112488A077AD0F2F511',
            ],
            [
                'name' => 'live-crazytime/OnCrazyTimeCommand.class',
                'hash' => 'B2DB275ED0E973FF4F2478A9DCFFDED7D78F7345',
            ],
            [
                'name' => 'live-crazytime/OnCrazyTimeWheelCommand.class',
                'hash' => 'B54796E7D33252161D3B339AFFB1AB85E11ADA08',
            ],
            [
                'name' => 'live-crazytime/Pachinko.class',
                'hash' => 'BD6BE8EB311F0FC15D5D0695080AB394BA50246D',
            ],
            [
                'name' => 'rng-service-extras/RngServiceExtras.class',
                'hash' => 'C567DED9539043425AB362564F0282BA95FFE8A3',
            ],
            [
                'name' => 'live-crazytime/PachinkoBuckets.class',
                'hash' => 'CCFF4351F47E9A5A6567BD25EC9CF184818958B8',
            ],
            [
                'name' => 'live-crazytime/CoinFlipMultipliers.class',
                'hash' => 'D885D6A82527923941D5E495304FE250EC3BE350',
            ],
            [
                'name' => 'live-crazytime/WheelLayout.class',
                'hash' => 'DC059D5DEFFBEC191C3C2A0DBBE8D31041E9BE5E',
            ],
            [
                'name' => 'live-crazytime/PachinkoRng.class',
                'hash' => 'E14CBBDAB7DA96C254E2C9B7D84906BB721ECC7D',
            ],
            [
                'name' => 'live-crazytime/CoinFlipRng.class',
                'hash' => 'E6DFE91DAF2C0C919A34278AABAF45C4A025D061',
            ],
            [
                'name' => 'live-crazytime/OnCrazyBonusCommand.class',
                'hash' => 'EA33EFC62FF0B2F109714B39636220D4910F7F7F',
            ],
            [
                'name' => 'live-crazytime/CrazyBonusRng.class',
                'hash' => 'ECEA7A8E90E9A0187332A745DF29D07E8A8981E0',
            ],
            [
                'name' => 'rng-service-extras/Weighted.class',
                'hash' => 'FB855DC1DE72CC935BCC1A793E1B5ABB22A76CB5',
            ],
            [
                'name' => 'live-crazytime/CoinFlip.class',
                'hash' => '5F967A34B537A73FEE406AD6C4E24A31978E0ACA',
            ],
            [
                'name' => 'live-crazytime/CashHuntRng.class',
                'hash' => '0593B102F212D6F20FDC1BF70E0D9D6977074D13',
            ],
            [
                'name' => 'live-crazytime/CashHunt.class',
                'hash' => '1192CE729C08BD88AE385E773E75A26D2027975A',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-crazytime/BetCode.class',
                'hash' => '66AC8BC32BCF34D397CAAACA2BA22FE83BF56D5A',
            ],
        ]
    ],
    [
        'code' => '62548',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'craps-shared-domain/RollResultService2.class',
                'hash' => '4374564D54D115853ACE33ACA2CEA6B41C3F3665',
            ],
            [
                'name' => 'craps-shared-domain/Multipliers.class',
                'hash' => '4DB9F7084E88C7CE24A84D462A612CFDC484FFD4',
            ],
            [
                'name' => 'live-craps/CrapsBetAutorefundPayout.class',
                'hash' => '084874A02816691667E80ED48B807555A1514629',
            ],
            [
                'name' => 'live-craps/CrapsBetAutorefundPayout2.class',
                'hash' => '3A5CFB32A9415781191A98F130A2ACACC9E6CD5D',
            ],
            [
                'name' => 'craps-shared-domain/BetSettings.class',
                'hash' => 'BA00A0B1040CFF40FB2E6B17CC9E88A0302394C5',
            ],
            [
                'name' => 'craps-shared-domain/PayoutCoefficient.class',
                'hash' => 'C482BCFC2FA5E6BEACB03C6C975E1CFF86903F79',
            ],
            [
                'name' => 'craps-shared-domain/PayoutAmount.class',
                'hash' => '82D1E7EB0841757D7DD5896E5A90F49A2C1015AD',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'craps-shared-domain/BetValidation2.class',
                'hash' => '2409551489085C38ECE3BDE912888E20EBD66BFF',
            ],
        ]
    ],
    [
        'code' => '62592',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'rng-service/RngService.class',
                'hash' => '06242EA1D62E620707E0CFB9F3BD77C01FAC8183',
            ],
            [
                'name' => 'rng-blackjack-v2/RuleDescription.class',
                'hash' => '19695AF1E143B3390241BB5584D7B426BC1EC434',
            ],
            [
                'name' => 'rng-blackjack-v2/SideBet.class',
                'hash' => '2C413B940DABB0DBB40A925DEA152017171AF949',
            ],
            [
                'name' => 'rng-service-extras/WeightedChoice.class',
                'hash' => '3FAABB401B97C9CD8A07B5EF82BB151483F1D727',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'rng-ub/RngWeighted.class',
                'hash' => '4476047A69D24CBCD0D7E32714352612DB958550',
            ],
            [
                'name' => 'rng-service-extras/FloatRngService.class',
                'hash' => '4B5B324C80D0E64E9317B7E23795372EF3C8FD6D',
            ],
            [
                'name' => 'rng-service-extras/ShufflingRngService.class',
                'hash' => '50741B2C3B26F063FAD62F530978EEF53144C2B2',
            ],
            [
                'name' => 'rng-ub/RngInt.class',
                'hash' => '53162A5EA1AB89E93FF12742757BC38F83926D97',
            ],
            [
                'name' => 'rng-ub/RngShuffle.class',
                'hash' => '579732894BD5428E2538599470373EB5E03037D7',
            ],
            [
                'name' => 'rng-service-extras/WeightedRngService.class',
                'hash' => '584681BBB6CEB7FA7EB6DFA800E0DF5E0CD2B111',
            ],
            [
                'name' => 'rng-ub/RngShuffleImpl.class',
                'hash' => '6E19FCDA545AF027F403B25FE5DA5084A0AF2B04',
            ],
            [
                'name' => 'rng-ub/RngIntImpl.class',
                'hash' => '76BBE7CDFC507D54D588D92B91AB808E67D0AD1B',
            ],
            [
                'name' => 'rng-blackjack-v2/Outcome.class',
                'hash' => '8051F85D2413C3388A56BAD7F48D705C2FB1B584',
            ],
            [
                'name' => 'rng-blackjack-v2/DealingService.class',
                'hash' => '817369E468527F37231665C62E5B1FD4C5541825',
            ],
            [
                'name' => 'rng-blackjack-v2/BetsValidationLogic.class',
                'hash' => '98A82C47DEA14046EC1FB153B6D40DE39C63C502',
            ],
            [
                'name' => 'rng-ub/RngWeightedImpl.class',
                'hash' => 'A5D11515B73AE003B22B672329563F25604D805C',
            ],
            [
                'name' => 'rng-blackjack-v2/PayoutCalculation.class',
                'hash' => 'A67214362D83AACCA82F0B87E6951E8085CC1B71',
            ],
            [
                'name' => 'rng-blackjack-v2/Payout.class',
                'hash' => 'ADAD8EBA9D19D117AC4BA8939A593B46D3B134BB',
            ],
            [
                'name' => 'rng-service-extras/RngServiceExtras.class',
                'hash' => 'C567DED9539043425AB362564F0282BA95FFE8A3',
            ],
            [
                'name' => 'rng-blackjack-v2/BlackjackTableConfig.class',
                'hash' => 'C6624009FB07988C30FBC2AB92DAB2D89299EA47',
            ],
            [
                'name' => 'rng-blackjack-v2/GameMetaData.class',
                'hash' => 'CB6F0AEDD2AF2F419DCB620064148CED13D34E59',
            ],
            [
                'name' => 'rng-blackjack-v2/GameAggregate.class',
                'hash' => 'CDC0C2A56103C500DDA8BC7513DF3029C2F7CCB9',
            ],
            [
                'name' => 'rng-blackjack-v2/PlayerAggregate.class',
                'hash' => 'E830CF6BD03AFD4B080AA7610A53472D9CA16042',
            ],
            [
                'name' => 'rng-service-extras/Weighted.class',
                'hash' => 'FB855DC1DE72CC935BCC1A793E1B5ABB22A76CB5',
            ],
            [
                'name' => 'rng-blackjack-v2/BlackjackGame.class',
                'hash' => 'FF5B09DA418A4271F882CD51F150C0DA21856983',
            ],
        ]
    ],
    [
        'code' => '62593',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'rng-roulette-v2/BetsValidation.class',
                'hash' => '00479F967B7731FDCDA6DDC07A21A815B0FC3811',
            ],
            [
                'name' => 'rng-service/RngService.class',
                'hash' => '06242EA1D62E620707E0CFB9F3BD77C01FAC8183',
            ],
            [
                'name' => 'live-roulette/BetCode.class',
                'hash' => '12E8AF341F8EE82138F1B1D19E09BDE1967DB288',
            ],
            [
                'name' => 'rng-service-extras/WeightedChoice.class',
                'hash' => '3FAABB401B97C9CD8A07B5EF82BB151483F1D727',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'rng-ub/RngWeighted.class',
                'hash' => '4476047A69D24CBCD0D7E32714352612DB958550',
            ],
            [
                'name' => 'rng-service-extras/FloatRngService.class',
                'hash' => '4B5B324C80D0E64E9317B7E23795372EF3C8FD6D',
            ],
            [
                'name' => 'rng-service-extras/ShufflingRngService.class',
                'hash' => '50741B2C3B26F063FAD62F530978EEF53144C2B2',
            ],
            [
                'name' => 'rng-ub/RngInt.class',
                'hash' => '53162A5EA1AB89E93FF12742757BC38F83926D97',
            ],
            [
                'name' => 'rng-ub/RngShuffle.class',
                'hash' => '579732894BD5428E2538599470373EB5E03037D7',
            ],
            [
                'name' => 'rng-service-extras/WeightedRngService.class',
                'hash' => '584681BBB6CEB7FA7EB6DFA800E0DF5E0CD2B111',
            ],
            [
                'name' => 'rng-roulette-v2/RouletteGame.class',
                'hash' => '60956F202134AA4C371BA461B16C01B8A8BAEF5A',
            ],
            [
                'name' => 'rng-ub/RngShuffleImpl.class',
                'hash' => '6E19FCDA545AF027F403B25FE5DA5084A0AF2B04',
            ],
            [
                'name' => 'rng-roulette-v2/PlayerAggregate.class',
                'hash' => '74C6B0FD51AF2699D62052852EAE17E1070E0C5A',
            ],
            [
                'name' => 'rng-ub/RngIntImpl.class',
                'hash' => '76BBE7CDFC507D54D588D92B91AB808E67D0AD1B',
            ],
            [
                'name' => 'live-roulette/RouletteGame.class',
                'hash' => '9531EBAC4884CAF6D5D0652107DFB7B4B2FED8A1',
            ],
            [
                'name' => 'rng-ub/RngWeightedImpl.class',
                'hash' => 'A5D11515B73AE003B22B672329563F25604D805C',
            ],
            [
                'name' => 'live-roulette/SbrBetCode.class',
                'hash' => 'AE714B20D7A0908E0C45B6660C09F0484192A901',
            ],
            [
                'name' => 'rng-roulette-v2/ApplyEvent.class',
                'hash' => 'B09634ADFD98A62EF9B03842F47FF91923FE78D1',
            ],
            [
                'name' => 'rng-roulette-v2/TableConfig.class',
                'hash' => 'BF65F36D356CED2812C4A2E38820EACD4D3B6CBC',
            ],
            [
                'name' => 'rng-roulette-v2/GameAggregate.class',
                'hash' => 'C28E50A8514231774833BC37B469EFEA0EDA5804',
            ],
            [
                'name' => 'rng-service-extras/RngServiceExtras.class',
                'hash' => 'C567DED9539043425AB362564F0282BA95FFE8A3',
            ],
            [
                'name' => 'rng-roulette-v2/RouletteApp.class',
                'hash' => 'D38F3422170CF9C169F3A1A904C239374749F7DF',
            ],
            [
                'name' => 'rng-roulette-v2/OutcomeGenerator.class',
                'hash' => 'D8DC71D1BEC546713224D4C41567C8543CCF2925',
            ],
            [
                'name' => 'live-roulette/RouletteWinMultipliers.class',
                'hash' => 'E2E1B183DCE06A097388542C7A5A7A2917F7C5B6',
            ],
            [
                'name' => 'rng-roulette-v2/PayoutCalculator.class',
                'hash' => 'F8C6D1275686FA6917C1E06EEBED9FCA841E2B7A',
            ],
            [
                'name' => 'live-roulette/WinMultiplier.class',
                'hash' => 'FACD97862C74D6F401A3514DCE693D00595D8EC3',
            ],
            [
                'name' => 'rng-service-extras/Weighted.class',
                'hash' => 'FB855DC1DE72CC935BCC1A793E1B5ABB22A76CB5',
            ],
        ]
    ],
    [
        'code' => '62594',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'rng-moneywheel/BetValidation.class',
                'hash' => '00AE1BCE34CCAD3428988197B4E54BAE440E9FA2',
            ],
            [
                'name' => 'rng-service/RngService.class',
                'hash' => '06242EA1D62E620707E0CFB9F3BD77C01FAC8183',
            ],
            [
                'name' => 'live-moneywheel/BetCode.class',
                'hash' => '064BFEFDD0D6AF33ADBCF9268F03E70F2047C3F9',
            ],
            [
                'name' => 'rng-moneywheel/PlayerAggregate.class',
                'hash' => '146A1FB46C3F6DFC292820078B94DEFD177B8291',
            ],
            [
                'name' => 'rng-moneywheel/TableConfig.class',
                'hash' => '1DB23EACC414E8700AA611160A3EFC3031B78111',
            ],
            [
                'name' => 'live-moneywheel/MoneyWheelResult.class',
                'hash' => '3379493ED6C09FB2366EB860C2D467B71041BEF1',
            ],
            [
                'name' => 'rng-service-extras/WeightedChoice.class',
                'hash' => '3FAABB401B97C9CD8A07B5EF82BB151483F1D727',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'rng-ub/RngWeighted.class',
                'hash' => '4476047A69D24CBCD0D7E32714352612DB958550',
            ],
            [
                'name' => 'rng-service-extras/FloatRngService.class',
                'hash' => '4B5B324C80D0E64E9317B7E23795372EF3C8FD6D',
            ],
            [
                'name' => 'rng-service-extras/ShufflingRngService.class',
                'hash' => '50741B2C3B26F063FAD62F530978EEF53144C2B2',
            ],
            [
                'name' => 'rng-ub/RngInt.class',
                'hash' => '53162A5EA1AB89E93FF12742757BC38F83926D97',
            ],
            [
                'name' => 'rng-ub/RngShuffle.class',
                'hash' => '579732894BD5428E2538599470373EB5E03037D7',
            ],
            [
                'name' => 'rng-service-extras/WeightedRngService.class',
                'hash' => '584681BBB6CEB7FA7EB6DFA800E0DF5E0CD2B111',
            ],
            [
                'name' => 'live-moneywheel/MoneyWheelLayout.class',
                'hash' => '68D991A220B4D90BA62633E8B474FCC823BB19D6',
            ],
            [
                'name' => 'rng-ub/RngShuffleImpl.class',
                'hash' => '6E19FCDA545AF027F403B25FE5DA5084A0AF2B04',
            ],
            [
                'name' => 'rng-ub/RngIntImpl.class',
                'hash' => '76BBE7CDFC507D54D588D92B91AB808E67D0AD1B',
            ],
            [
                'name' => 'rng-moneywheel/GameAggregate.class',
                'hash' => '8A96B03531C0E6D6885368D5F53C143DE6906897',
            ],
            [
                'name' => 'rng-moneywheel/MoneywheelGame.class',
                'hash' => 'A4A945D82DCB875F9E510A8F61B422FC2A472DB2',
            ],
            [
                'name' => 'live-moneywheel/ResultCode.class',
                'hash' => 'A57FCC665AA4B0D889B7D03691F95F8189F58B2D',
            ],
            [
                'name' => 'rng-ub/RngWeightedImpl.class',
                'hash' => 'A5D11515B73AE003B22B672329563F25604D805C',
            ],
            [
                'name' => 'rng-moneywheel/PayoutCalculator.class',
                'hash' => 'AFD626F061A5655FA3F688517E5D97AF3F4C324D',
            ],
            [
                'name' => 'rng-service-extras/RngServiceExtras.class',
                'hash' => 'C567DED9539043425AB362564F0282BA95FFE8A3',
            ],
            [
                'name' => 'rng-moneywheel/OutcomeGenerator.class',
                'hash' => 'E76EE472931FB076B2787729B79253C3E67B2CFD',
            ],
            [
                'name' => 'rng-service-extras/Weighted.class',
                'hash' => 'FB855DC1DE72CC935BCC1A793E1B5ABB22A76CB5',
            ],
        ]
    ],
    [
        'code' => '62595',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'rng-roulette-v2/BetsValidation.class',
                'hash' => '00479F967B7731FDCDA6DDC07A21A815B0FC3811',
            ],
            [
                'name' => 'rng-service/RngService.class',
                'hash' => '06242EA1D62E620707E0CFB9F3BD77C01FAC8183',
            ],
            [
                'name' => 'live-roulette/BetCode.class',
                'hash' => '12E8AF341F8EE82138F1B1D19E09BDE1967DB288',
            ],
            [
                'name' => 'rng-service-extras/WeightedChoice.class',
                'hash' => '3FAABB401B97C9CD8A07B5EF82BB151483F1D727',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'rng-ub/RngWeighted.class',
                'hash' => '4476047A69D24CBCD0D7E32714352612DB958550',
            ],
            [
                'name' => 'rng-service-extras/FloatRngService.class',
                'hash' => '4B5B324C80D0E64E9317B7E23795372EF3C8FD6D',
            ],
            [
                'name' => 'rng-service-extras/ShufflingRngService.class',
                'hash' => '50741B2C3B26F063FAD62F530978EEF53144C2B2',
            ],
            [
                'name' => 'live-roulette/LuckyNumbersState.class',
                'hash' => '50B0BD80576038735ABFC7F9C5E31501B1527DC1',
            ],
            [
                'name' => 'rng-ub/RngInt.class',
                'hash' => '53162A5EA1AB89E93FF12742757BC38F83926D97',
            ],
            [
                'name' => 'rng-ub/RngShuffle.class',
                'hash' => '579732894BD5428E2538599470373EB5E03037D7',
            ],
            [
                'name' => 'rng-service-extras/WeightedRngService.class',
                'hash' => '584681BBB6CEB7FA7EB6DFA800E0DF5E0CD2B111',
            ],
            [
                'name' => 'rng-roulette-v2/RouletteGame.class',
                'hash' => '60956F202134AA4C371BA461B16C01B8A8BAEF5A',
            ],
            [
                'name' => 'rng-ub/RngShuffleImpl.class',
                'hash' => '6E19FCDA545AF027F403B25FE5DA5084A0AF2B04',
            ],
            [
                'name' => 'rng-roulette-v2/PlayerAggregate.class',
                'hash' => '74C6B0FD51AF2699D62052852EAE17E1070E0C5A',
            ],
            [
                'name' => 'rng-ub/RngIntImpl.class',
                'hash' => '76BBE7CDFC507D54D588D92B91AB808E67D0AD1B',
            ],
            [
                'name' => 'live-roulette/RouletteGame.class',
                'hash' => '9531EBAC4884CAF6D5D0652107DFB7B4B2FED8A1',
            ],
            [
                'name' => 'rng-ub/RngWeightedImpl.class',
                'hash' => 'A5D11515B73AE003B22B672329563F25604D805C',
            ],
            [
                'name' => 'live-roulette/SbrBetCode.class',
                'hash' => 'AE714B20D7A0908E0C45B6660C09F0484192A901',
            ],
            [
                'name' => 'rng-roulette-v2/ApplyEvent.class',
                'hash' => 'B09634ADFD98A62EF9B03842F47FF91923FE78D1',
            ],
            [
                'name' => 'rng-roulette-v2/TableConfig.class',
                'hash' => 'BF65F36D356CED2812C4A2E38820EACD4D3B6CBC',
            ],
            [
                'name' => 'rng-roulette-v2/GameAggregate.class',
                'hash' => 'C28E50A8514231774833BC37B469EFEA0EDA5804',
            ],
            [
                'name' => 'rng-service-extras/RngServiceExtras.class',
                'hash' => 'C567DED9539043425AB362564F0282BA95FFE8A3',
            ],
            [
                'name' => 'rng-roulette-v2/RouletteApp.class',
                'hash' => 'D38F3422170CF9C169F3A1A904C239374749F7DF',
            ],
            [
                'name' => 'rng-roulette-v2/OutcomeGenerator.class',
                'hash' => 'D8DC71D1BEC546713224D4C41567C8543CCF2925',
            ],
            [
                'name' => 'live-roulette/RouletteWinMultipliers.class',
                'hash' => 'E2E1B183DCE06A097388542C7A5A7A2917F7C5B6',
            ],
            [
                'name' => 'rng-roulette-v2/PayoutCalculator.class',
                'hash' => 'F8C6D1275686FA6917C1E06EEBED9FCA841E2B7A',
            ],
            [
                'name' => 'live-roulette/WinMultiplier.class',
                'hash' => 'FACD97862C74D6F401A3514DCE693D00595D8EC3',
            ],
            [
                'name' => 'rng-service-extras/Weighted.class',
                'hash' => 'FB855DC1DE72CC935BCC1A793E1B5ABB22A76CB5',
            ],
        ]
    ],
    [
        'code' => '62596',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'rng-service/RngService.class',
                'hash' => '06242EA1D62E620707E0CFB9F3BD77C01FAC8183',
            ],
            [
                'name' => 'megaball-shared-domain/MegaBallMath.class',
                'hash' => '08FA42FF4A936631D6259E717348F987F70DAD65',
            ],
            [
                'name' => 'megaball-shared-domain/MegaBallCard.class',
                'hash' => '358A88EE469805CAC680590E3256CFC6828183B6',
            ],
            [
                'name' => 'megaball-shared-domain/MissingBallsNumber.class',
                'hash' => '3B5734A85E63ED1397DF7614C12CEECB73FBDB6A',
            ],
            [
                'name' => 'rng-service-extras/WeightedChoice.class',
                'hash' => '3FAABB401B97C9CD8A07B5EF82BB151483F1D727',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'rng-ub/RngWeighted.class',
                'hash' => '4476047A69D24CBCD0D7E32714352612DB958550',
            ],
            [
                'name' => 'megaball-shared-domain/PowerMultiplier.class',
                'hash' => '459560DBC6F4EF40F06C2C551742AC402196191D',
            ],
            [
                'name' => 'rng-service-extras/FloatRngService.class',
                'hash' => '4B5B324C80D0E64E9317B7E23795372EF3C8FD6D',
            ],
            [
                'name' => 'rng-service-extras/ShufflingRngService.class',
                'hash' => '50741B2C3B26F063FAD62F530978EEF53144C2B2',
            ],
            [
                'name' => 'megaball-shared-domain/MegaBallCombination.class',
                'hash' => '52A76C73CA811E95E838448A79027426C027000A',
            ],
            [
                'name' => 'rng-ub/RngInt.class',
                'hash' => '53162A5EA1AB89E93FF12742757BC38F83926D97',
            ],
            [
                'name' => 'megaball-shared-domain/PowerMultipliersCount.class',
                'hash' => '539A9D211B4702DE42888C3D8570DD0ED20C0F1D',
            ],
            [
                'name' => 'rng-ub/RngShuffle.class',
                'hash' => '579732894BD5428E2538599470373EB5E03037D7',
            ],
            [
                'name' => 'megaball-shared-domain/PayoutLimiter.class',
                'hash' => '57D1D28051448D32AE4A881CECE62AB4211C8917',
            ],
            [
                'name' => 'rng-service-extras/WeightedRngService.class',
                'hash' => '584681BBB6CEB7FA7EB6DFA800E0DF5E0CD2B111',
            ],
            [
                'name' => 'megaball-shared-domain/CellIndex.class',
                'hash' => '5D7A1B9A8617AA9F73EEAC23DA159FD02199C90A',
            ],
            [
                'name' => 'megaball-shared-domain/SharedCardsGenerator.class',
                'hash' => '6419D8BF798112947AB6CE1099E74C2AB0472000',
            ],
            [
                'name' => 'rng-ub/RngShuffleImpl.class',
                'hash' => '6E19FCDA545AF027F403B25FE5DA5084A0AF2B04',
            ],
            [
                'name' => 'rng-ub/RngIntImpl.class',
                'hash' => '76BBE7CDFC507D54D588D92B91AB808E67D0AD1B',
            ],
            [
                'name' => 'rng-megaball/GameResultProcessor.class',
                'hash' => '8CB82681EC11B1893192123AE21EE2A8F5F7DD8E',
            ],
            [
                'name' => 'megaball-shared-domain/CardPayoutCalculator.class',
                'hash' => '8FD91BE38B442E6B85CB8CFC102F322F68C931A1',
            ],
            [
                'name' => 'megaball-shared-domain/SharedUniqueCardsGenerator.class',
                'hash' => 'A4F66641E83A8370362BC4C5688BC9BA459FAEBB',
            ],
            [
                'name' => 'rng-ub/RngWeightedImpl.class',
                'hash' => 'A5D11515B73AE003B22B672329563F25604D805C',
            ],
            [
                'name' => 'rng-megaball/UniqueRngMegaBallCardsGenerator.class',
                'hash' => 'A88EEBD0E10FD7ECAC7A02FB45411B7A0965167E',
            ],
            [
                'name' => 'megaball-shared-domain/BallDrawer.class',
                'hash' => 'A89D6849D1C72F7BE27E13F36ACC8B783B5928D8',
            ],
            [
                'name' => 'rng-service-extras/RngServiceExtras.class',
                'hash' => 'C567DED9539043425AB362564F0282BA95FFE8A3',
            ],
            [
                'name' => 'megaball-shared-domain/MegaBallBall.class',
                'hash' => 'D86C96DEE0A2187B4B7177B6A58533F29C22BADE',
            ],
            [
                'name' => 'megaball-shared-domain/PowerMultipliersGenerator.class',
                'hash' => 'E459F5ECAE34EA3A3282C53A45166F7875369F79',
            ],
            [
                'name' => 'rng-megaball/MegaBallRngGenerator.class',
                'hash' => 'EC191B139AB5DBDF7FDF146591C2AECC5E5C8B09',
            ],
            [
                'name' => 'rng-megaball/RngMegaBallCardsGenerator.class',
                'hash' => 'EF575FDD141BD6A684982E8B3D6C708311F17BE7',
            ],
            [
                'name' => 'megaball-shared-domain/PowerBall.class',
                'hash' => 'F79D8E79D1265C0B4FE01DAD748DBC0C33BEDC4D',
            ],
            [
                'name' => 'rng-service-extras/Weighted.class',
                'hash' => 'FB855DC1DE72CC935BCC1A793E1B5ABB22A76CB5',
            ],
        ]
    ],
    [
        'code' => '62597',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'rng-service/RngService.class',
                'hash' => '06242EA1D62E620707E0CFB9F3BD77C01FAC8183',
            ],
            [
                'name' => 'craps-shared-domain/BetValidation2.class',
                'hash' => '2409551489085C38ECE3BDE912888E20EBD66BFF',
            ],
            [
                'name' => 'rng-service-extras/WeightedChoice.class',
                'hash' => '3FAABB401B97C9CD8A07B5EF82BB151483F1D727',
            ],
            [
                'name' => 'craps-shared-domain/RollResultService2.class',
                'hash' => '4374564D54D115853ACE33ACA2CEA6B41C3F3665',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'rng-ub/RngWeighted.class',
                'hash' => '4476047A69D24CBCD0D7E32714352612DB958550',
            ],
            [
                'name' => 'rng-service-extras/FloatRngService.class',
                'hash' => '4B5B324C80D0E64E9317B7E23795372EF3C8FD6D',
            ],
            [
                'name' => 'craps-shared-domain/Multipliers.class',
                'hash' => '4DB9F7084E88C7CE24A84D462A612CFDC484FFD4',
            ],
            [
                'name' => 'rng-service-extras/ShufflingRngService.class',
                'hash' => '50741B2C3B26F063FAD62F530978EEF53144C2B2',
            ],
            [
                'name' => 'rng-craps/ResultGenerator.class',
                'hash' => '528FDED4401D177FB4D8667E52B5C9B8852DBA1B',
            ],
            [
                'name' => 'rng-ub/RngInt.class',
                'hash' => '53162A5EA1AB89E93FF12742757BC38F83926D97',
            ],
            [
                'name' => 'rng-ub/RngShuffle.class',
                'hash' => '579732894BD5428E2538599470373EB5E03037D7',
            ],
            [
                'name' => 'rng-service-extras/WeightedRngService.class',
                'hash' => '584681BBB6CEB7FA7EB6DFA800E0DF5E0CD2B111',
            ],
            [
                'name' => 'rng-ub/RngShuffleImpl.class',
                'hash' => '6E19FCDA545AF027F403B25FE5DA5084A0AF2B04',
            ],
            [
                'name' => 'rng-ub/RngIntImpl.class',
                'hash' => '76BBE7CDFC507D54D588D92B91AB808E67D0AD1B',
            ],
            [
                'name' => 'craps-shared-domain/PayoutAmount.class',
                'hash' => '82D1E7EB0841757D7DD5896E5A90F49A2C1015AD',
            ],
            [
                'name' => 'rng-ub/RngWeightedImpl.class',
                'hash' => 'A5D11515B73AE003B22B672329563F25604D805C',
            ],
            [
                'name' => 'craps-shared-domain/BetSettings.class',
                'hash' => 'BA00A0B1040CFF40FB2E6B17CC9E88A0302394C5',
            ],
            [
                'name' => 'craps-shared-domain/PayoutCoefficient.class',
                'hash' => 'C482BCFC2FA5E6BEACB03C6C975E1CFF86903F79',
            ],
            [
                'name' => 'rng-service-extras/RngServiceExtras.class',
                'hash' => 'C567DED9539043425AB362564F0282BA95FFE8A3',
            ],
            [
                'name' => 'rng-service-extras/Weighted.class',
                'hash' => 'FB855DC1DE72CC935BCC1A793E1B5ABB22A76CB5',
            ],
        ]
    ],
    [
        'code' => '62598',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'live-dragontiger/DragonTigerWinner.class',
                'hash' => '0053BAEFE6CF7672134C435F3ADD6C9453A2065D',
            ],
            [
                'name' => 'rng-service/RngService.class',
                'hash' => '06242EA1D62E620707E0CFB9F3BD77C01FAC8183',
            ],
            [
                'name' => 'rng-service-extras/WeightedChoice.class',
                'hash' => '3FAABB401B97C9CD8A07B5EF82BB151483F1D727',
            ],
            [
                'name' => 'rng-dragontiger/DragonTigerGame.class',
                'hash' => '40643688561659DD12658998D11D5CD935922DF9',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'rng-ub/RngWeighted.class',
                'hash' => '4476047A69D24CBCD0D7E32714352612DB958550',
            ],
            [
                'name' => 'rng-service-extras/FloatRngService.class',
                'hash' => '4B5B324C80D0E64E9317B7E23795372EF3C8FD6D',
            ],
            [
                'name' => 'live-dragontiger/DragonTigerPayout.class',
                'hash' => '4E854E824E750B617865543F446765D583B21F44',
            ],
            [
                'name' => 'rng-service-extras/ShufflingRngService.class',
                'hash' => '50741B2C3B26F063FAD62F530978EEF53144C2B2',
            ],
            [
                'name' => 'rng-ub/RngInt.class',
                'hash' => '53162A5EA1AB89E93FF12742757BC38F83926D97',
            ],
            [
                'name' => 'rng-ub/RngShuffle.class',
                'hash' => '579732894BD5428E2538599470373EB5E03037D7',
            ],
            [
                'name' => 'rng-service-extras/WeightedRngService.class',
                'hash' => '584681BBB6CEB7FA7EB6DFA800E0DF5E0CD2B111',
            ],
            [
                'name' => 'rng-dragontiger/TableConfig.class',
                'hash' => '5CD799D3E6E99CA2B9BC5649B6CDA05443658AC8',
            ],
            [
                'name' => 'rng-ub/RngShuffleImpl.class',
                'hash' => '6E19FCDA545AF027F403B25FE5DA5084A0AF2B04',
            ],
            [
                'name' => 'rng-ub/RngIntImpl.class',
                'hash' => '76BBE7CDFC507D54D588D92B91AB808E67D0AD1B',
            ],
            [
                'name' => 'live-dragontiger/DragonTigerCard.class',
                'hash' => '79FEC6717E22D9D446C7EA8E8C23DDDE45AE6229',
            ],
            [
                'name' => 'rng-dragontiger/ShoeState.class',
                'hash' => '9A2B5B4530DDBC6658344B9B7781E14919A3547F',
            ],
            [
                'name' => 'rng-dragontiger/Domain.class',
                'hash' => '9DB6D3B4F970363990D4AE3D1F7ACA0CB924ABAE',
            ],
            [
                'name' => 'rng-ub/RngWeightedImpl.class',
                'hash' => 'A5D11515B73AE003B22B672329563F25604D805C',
            ],
            [
                'name' => 'rng-dragontiger/GameAggregate.class',
                'hash' => 'B426DAC18EC707162B9EECD0266418C9ABD51AB3',
            ],
            [
                'name' => 'rng-service-extras/RngServiceExtras.class',
                'hash' => 'C567DED9539043425AB362564F0282BA95FFE8A3',
            ],
            [
                'name' => 'rng-dragontiger/BetsValidation.class',
                'hash' => 'DA5A9BA67BFA9DD6EEF75A95F8595D0EB5D681DE',
            ],
            [
                'name' => 'rng-dragontiger/PayoutsCalculation.class',
                'hash' => 'E1FCDF88414CEB7F010EDA394D07E02E87ABB6EC',
            ],
            [
                'name' => 'rng-dragontiger/PlayerAggregate.class',
                'hash' => 'EF02A3EE4BE4996746E71218AFDB330A87E3DA40',
            ],
            [
                'name' => 'rng-service-extras/Weighted.class',
                'hash' => 'FB855DC1DE72CC935BCC1A793E1B5ABB22A76CB5',
            ],
        ]
    ],
    [
        'code' => '62599',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'live-dragontiger/DragonTigerWinner.class',
                'hash' => '0053BAEFE6CF7672134C435F3ADD6C9453A2065D',
            ],
            [
                'name' => 'rng-service/RngService.class',
                'hash' => '06242EA1D62E620707E0CFB9F3BD77C01FAC8183',
            ],
            [
                'name' => 'rng-service-extras/WeightedChoice.class',
                'hash' => '3FAABB401B97C9CD8A07B5EF82BB151483F1D727',
            ],
            [
                'name' => 'rng-dragontiger/DragonTigerGame.class',
                'hash' => '40643688561659DD12658998D11D5CD935922DF9',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'rng-ub/RngWeighted.class',
                'hash' => '4476047A69D24CBCD0D7E32714352612DB958550',
            ],
            [
                'name' => 'rng-service-extras/FloatRngService.class',
                'hash' => '4B5B324C80D0E64E9317B7E23795372EF3C8FD6D',
            ],
            [
                'name' => 'live-dragontiger/DragonTigerPayout.class',
                'hash' => '4E854E824E750B617865543F446765D583B21F44',
            ],
            [
                'name' => 'rng-service-extras/ShufflingRngService.class',
                'hash' => '50741B2C3B26F063FAD62F530978EEF53144C2B2',
            ],
            [
                'name' => 'rng-ub/RngInt.class',
                'hash' => '53162A5EA1AB89E93FF12742757BC38F83926D97',
            ],
            [
                'name' => 'rng-ub/RngShuffle.class',
                'hash' => '579732894BD5428E2538599470373EB5E03037D7',
            ],
            [
                'name' => 'rng-service-extras/WeightedRngService.class',
                'hash' => '584681BBB6CEB7FA7EB6DFA800E0DF5E0CD2B111',
            ],
            [
                'name' => 'rng-dragontiger/TableConfig.class',
                'hash' => '5CD799D3E6E99CA2B9BC5649B6CDA05443658AC8',
            ],
            [
                'name' => 'rng-ub/RngShuffleImpl.class',
                'hash' => '6E19FCDA545AF027F403B25FE5DA5084A0AF2B04',
            ],
            [
                'name' => 'rng-ub/RngIntImpl.class',
                'hash' => '76BBE7CDFC507D54D588D92B91AB808E67D0AD1B',
            ],
            [
                'name' => 'live-dragontiger/DragonTigerCard.class',
                'hash' => '79FEC6717E22D9D446C7EA8E8C23DDDE45AE6229',
            ],
            [
                'name' => 'rng-dragontiger/ShoeState.class',
                'hash' => '9A2B5B4530DDBC6658344B9B7781E14919A3547F',
            ],
            [
                'name' => 'rng-dragontiger/Domain.class',
                'hash' => '9DB6D3B4F970363990D4AE3D1F7ACA0CB924ABAE',
            ],
            [
                'name' => 'rng-ub/RngWeightedImpl.class',
                'hash' => 'A5D11515B73AE003B22B672329563F25604D805C',
            ],
            [
                'name' => 'rng-dragontiger/GameAggregate.class',
                'hash' => 'B426DAC18EC707162B9EECD0266418C9ABD51AB3',
            ],
            [
                'name' => 'rng-service-extras/RngServiceExtras.class',
                'hash' => 'C567DED9539043425AB362564F0282BA95FFE8A3',
            ],
            [
                'name' => 'rng-dragontiger/BetsValidation.class',
                'hash' => 'DA5A9BA67BFA9DD6EEF75A95F8595D0EB5D681DE',
            ],
            [
                'name' => 'rng-dragontiger/PayoutsCalculation.class',
                'hash' => 'E1FCDF88414CEB7F010EDA394D07E02E87ABB6EC',
            ],
            [
                'name' => 'rng-dragontiger/PlayerAggregate.class',
                'hash' => 'EF02A3EE4BE4996746E71218AFDB330A87E3DA40',
            ],
            [
                'name' => 'rng-service-extras/Weighted.class',
                'hash' => 'FB855DC1DE72CC935BCC1A793E1B5ABB22A76CB5',
            ],
        ]
    ],
    [
        'code' => '62600',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'baccarat-domain/BaccaratPayouts.class',
                'hash' => '042E3BC890A305299369586A38B968F445A98D5C',
            ],
            [
                'name' => 'rng-service/RngService.class',
                'hash' => '06242EA1D62E620707E0CFB9F3BD77C01FAC8183',
            ],
            [
                'name' => 'rng-baccarat/TableConfig.class',
                'hash' => '19ADFE0B6A3DFF620445864BDCAE76FDD7AEB73F',
            ],
            [
                'name' => 'rng-baccarat/ShoeState.class',
                'hash' => '21A977189160210A28C89517F491D55888F28598',
            ],
            [
                'name' => 'rng-service-extras/WeightedChoice.class',
                'hash' => '3FAABB401B97C9CD8A07B5EF82BB151483F1D727',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'rng-ub/RngWeighted.class',
                'hash' => '4476047A69D24CBCD0D7E32714352612DB958550',
            ],
            [
                'name' => 'rng-service-extras/FloatRngService.class',
                'hash' => '4B5B324C80D0E64E9317B7E23795372EF3C8FD6D',
            ],
            [
                'name' => 'rng-service-extras/ShufflingRngService.class',
                'hash' => '50741B2C3B26F063FAD62F530978EEF53144C2B2',
            ],
            [
                'name' => 'rng-ub/RngInt.class',
                'hash' => '53162A5EA1AB89E93FF12742757BC38F83926D97',
            ],
            [
                'name' => 'rng-ub/RngShuffle.class',
                'hash' => '579732894BD5428E2538599470373EB5E03037D7',
            ],
            [
                'name' => 'baccarat-domain/BaccaratModel.class',
                'hash' => '581FE6B52492790D3B7CC4068B93B2CD10C02498',
            ],
            [
                'name' => 'rng-service-extras/WeightedRngService.class',
                'hash' => '584681BBB6CEB7FA7EB6DFA800E0DF5E0CD2B111',
            ],
            [
                'name' => 'baccarat-domain/Hand.class',
                'hash' => '6C3DB7F86687E529EC3F90655DAB4836FA28DFA8',
            ],
            [
                'name' => 'rng-ub/RngShuffleImpl.class',
                'hash' => '6E19FCDA545AF027F403B25FE5DA5084A0AF2B04',
            ],
            [
                'name' => 'rng-ub/RngIntImpl.class',
                'hash' => '76BBE7CDFC507D54D588D92B91AB808E67D0AD1B',
            ],
            [
                'name' => 'rng-baccarat/ShoeAggregate.class',
                'hash' => '7D8B2E6CB502EC2F605516567946D5A594AC70E4',
            ],
            [
                'name' => 'rng-baccarat/BaccaratGame.class',
                'hash' => '87DBE89D58E3F7DA43BD266887C2EC52F2DF47A2',
            ],
            [
                'name' => 'rng-ub/RngWeightedImpl.class',
                'hash' => 'A5D11515B73AE003B22B672329563F25604D805C',
            ],
            [
                'name' => 'baccarat-domain/ShoeUtil.class',
                'hash' => 'BCBEDB8C53176E47E53D8D0F4D1FFA7B71344F56',
            ],
            [
                'name' => 'rng-service-extras/RngServiceExtras.class',
                'hash' => 'C567DED9539043425AB362564F0282BA95FFE8A3',
            ],
            [
                'name' => 'rng-service-extras/Weighted.class',
                'hash' => 'FB855DC1DE72CC935BCC1A793E1B5ABB22A76CB5',
            ],
        ]
    ],
    [
        'code' => '70856',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'live-fantan-shared-domain/FanTanPlayerBets.class',
                'hash' => '0B2FCC02D8BCCF7E38D85A55E1BF4F6D99F6FB27',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-fantan-shared-domain/FanTanResult.class',
                'hash' => '4822B0B3DD17E5996CB73ED1CC64C565B3F858FD',
            ],
            [
                'name' => 'live-fantan-shared-domain/FanTanBetCode.class',
                'hash' => '918CA860B7BE1E40E278A0F349FC9FFA38E2CFF8',
            ],
            [
                'name' => 'live-fantan-shared-domain/FanTanPayout.class',
                'hash' => '9DA84018568030AAC8E29AFE26568E43EAFAF9CD',
            ],
        ]
    ],
    [
        'code' => '70857',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'cash-or-crash-shared-domain/PlayerDecisionOutcome.class',
                'hash' => '0144334FBD19CB91820D640DB964F2471FA562BB',
            ],
            [
                'name' => 'cash-or-crash-shared-domain/CashOrCrashMath.class',
                'hash' => '2014A3277ACDBD9BAE8E258BDA19C840145CFB94',
            ],
            [
                'name' => 'cash-or-crash-shared-domain/PlayerDecision.class',
                'hash' => '341CEA9E49D29CBB6A4CDA11CBF979977ADF3F32',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'cash-or-crash-shared-domain/ShieldStrength.class',
                'hash' => '4F6D42BB811BFA73C2A773E4EC6DFDABE9BFA7F5',
            ],
            [
                'name' => 'cash-or-crash-shared-domain/CalculatePayoutFor.class',
                'hash' => '63BB944CE13B201E95D9F9475F7AEADE3126A290',
            ],
            [
                'name' => 'cash-or-crash-shared-domain/CashOrCrashDecisions.class',
                'hash' => '6F9E965B6AEBC523D6F447991D1123461C8D7405',
            ],
            [
                'name' => 'cash-or-crash-shared-domain/LimitPayout.class',
                'hash' => '769B596EE8116BB83D3DFE441FF9FD5129875F3E',
            ],
            [
                'name' => 'cash-or-crash-shared-domain/RemainingBalls.class',
                'hash' => '86C467ED5326D11A4FE3D352E7FFB29FE2AF224F',
            ],
            [
                'name' => 'cash-or-crash-shared-domain/Amounts.class',
                'hash' => '8C0D066F3DBC6BDE516BC6E6C49DD2EE35AEF55D',
            ],
            [
                'name' => 'cash-or-crash-shared-domain/PayoutLevel.class',
                'hash' => 'B7F6301F8AB714F0C3852C2BD1B044BC902BBFB5',
            ],
            [
                'name' => 'cash-or-crash-shared-domain/CashOrCrashBall.class',
                'hash' => 'D547977724250F41B69FBB6605197B8750CB7F18',
            ],
            [
                'name' => 'cash-or-crash-shared-domain/PayoutLadderMultiplier.class',
                'hash' => 'E4D32D584F830672F05CC218B5933C1ED55A3E82',
            ],
            [
                'name' => 'cash-or-crash-shared-domain/HandleBallDraw.class',
                'hash' => 'FCAB004B7DA825997520D07112C76A8BFBE232CA',
            ],
        ]
    ],
    [
        'code' => '75941',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'rng-service/RngService.class',
                'hash' => '06242EA1D62E620707E0CFB9F3BD77C01FAC8183',
            ],
            [
                'name' => 'live-sbj/SideBetCombination.class',
                'hash' => '1F934526426010C9BDBE2343D2B8F4EFC424A536',
            ],
            [
                'name' => 'live-sbj/Cards.class',
                'hash' => '244D2AF4451DE3801CC3C9C63DCEC9A4165AC210',
            ],
            [
                'name' => 'live-sbj/GameResultEvaluation.class',
                'hash' => '3006C6E8E9731BFF6ABFCD3170C4AB380373EA5A',
            ],
            [
                'name' => 'rng-service-extras/WeightedChoice.class',
                'hash' => '3FAABB401B97C9CD8A07B5EF82BB151483F1D727',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'rng-service-extras/FloatRngService.class',
                'hash' => '4B5B324C80D0E64E9317B7E23795372EF3C8FD6D',
            ],
            [
                'name' => 'rng-service-extras/ShufflingRngService.class',
                'hash' => '50741B2C3B26F063FAD62F530978EEF53144C2B2',
            ],
            [
                'name' => 'rng-service-extras/WeightedRngService.class',
                'hash' => '584681BBB6CEB7FA7EB6DFA800E0DF5E0CD2B111',
            ],
            [
                'name' => 'live-sbj/PlayerHands.class',
                'hash' => '97E4DC036B6BC70AA48321128CB2B4B06E36D027',
            ],
            [
                'name' => 'live-sbj/BettingService.class',
                'hash' => 'A1628F4ECD4F070FF59D013CAA3F7282C29222D3',
            ],
            [
                'name' => 'live-sbj/ScalableBlackjackGame.class',
                'hash' => 'B7C1E4711F4BF74C9A902B0AAB1124E1F0C005C1',
            ],
            [
                'name' => 'live-sbj/PayoutEvaluation.class',
                'hash' => 'C0AB917DCAE4253232256F3F73B178E35053D7AA',
            ],
            [
                'name' => 'live-sbj/Paytable.class',
                'hash' => 'C12DE09A89207C3BECEA068948ABC28E3D1384F8',
            ],
            [
                'name' => 'rng-service-extras/RngServiceExtras.class',
                'hash' => 'C567DED9539043425AB362564F0282BA95FFE8A3',
            ],
            [
                'name' => 'live-sbj/BettingProcessor.class',
                'hash' => 'EDBDB4E8FD1ADA908E6C916CE82D8CD4AA163B6D',
            ],
            [
                'name' => 'live-sbj/Rules.class',
                'hash' => 'F439F5D119760F8A588EB24FE7EB4283D6C1939D',
            ],
            [
                'name' => 'live-sbj/LightningService.class',
                'hash' => 'F949418C83813273286630D9D6545C0B11C7596E',
            ],
            [
                'name' => 'rng-service-extras/Weighted.class',
                'hash' => 'FB855DC1DE72CC935BCC1A793E1B5ABB22A76CB5',
            ],
        ]
    ],
    [
        'code' => '75953',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'baccarat-domain/BaccaratPayouts.class',
                'hash' => '042E3BC890A305299369586A38B968F445A98D5C',
            ],
            [
                'name' => 'rng-service/RngService.class',
                'hash' => '06242EA1D62E620707E0CFB9F3BD77C01FAC8183',
            ],
            [
                'name' => 'baccarat-domain/LightningConstants.class',
                'hash' => '0F07CB1E089A2F8B749B399C31BE7B658FC98BAC',
            ],
            [
                'name' => 'live-baccarat/BaccaratGame.class',
                'hash' => '1DA36380967151D61FF67C08BAE4F5E8A945A44A',
            ],
            [
                'name' => 'rng-service-extras/WeightedChoice.class',
                'hash' => '3FAABB401B97C9CD8A07B5EF82BB151483F1D727',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'live-baccarat/BaccaratEngine.class',
                'hash' => '4739EF199E8116A3591A6538D8664D83B691B10D',
            ],
            [
                'name' => 'rng-service-extras/FloatRngService.class',
                'hash' => '4B5B324C80D0E64E9317B7E23795372EF3C8FD6D',
            ],
            [
                'name' => 'rng-service-extras/ShufflingRngService.class',
                'hash' => '50741B2C3B26F063FAD62F530978EEF53144C2B2',
            ],
            [
                'name' => 'baccarat-domain/BaccaratModel.class',
                'hash' => '581FE6B52492790D3B7CC4068B93B2CD10C02498',
            ],
            [
                'name' => 'rng-service-extras/WeightedRngService.class',
                'hash' => '584681BBB6CEB7FA7EB6DFA800E0DF5E0CD2B111',
            ],
            [
                'name' => 'baccarat-domain/Hand.class',
                'hash' => '6C3DB7F86687E529EC3F90655DAB4836FA28DFA8',
            ],
            [
                'name' => 'baccarat-domain/GoldenWealthMultipliersGenerator.class',
                'hash' => 'ACAA5EC489B4AF714EE0D33FACE57C7C7DC5130B',
            ],
            [
                'name' => 'live-baccarat/PlayerBetting.class',
                'hash' => 'B454CB0BC7B5DD00AE2122D3D53C38EB4581A976',
            ],
            [
                'name' => 'rng-service-extras/RngServiceExtras.class',
                'hash' => 'C567DED9539043425AB362564F0282BA95FFE8A3',
            ],
            [
                'name' => 'live-baccarat/LightningHandler.class',
                'hash' => 'E952FE348415E902F758619413F33A8FE2ED76EE',
            ],
            [
                'name' => 'rng-service-extras/Weighted.class',
                'hash' => 'FB855DC1DE72CC935BCC1A793E1B5ABB22A76CB5',
            ],
        ]
    ],
    [
        'code' => '75958',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'rng-service/RngService.class',
                'hash' => '06242EA1D62E620707E0CFB9F3BD77C01FAC8183',
            ],
            [
                'name' => 'live-surprise/RemoteRngService.class',
                'hash' => '170195EF1FB7BF70B6E7E1FEECD153E5487BE7D0',
            ],
            [
                'name' => 'gonzo-domain/BonusRound.class',
                'hash' => '26F8F5D969CFE880671FECEC71B31943A246961C',
            ],
            [
                'name' => 'live-surprise/OnMakeAutoChoicesCommand.class',
                'hash' => '311A86D7DE8609E7D16CBFF5DAF9EC1F5E6D0D8A',
            ],
            [
                'name' => 'gonzo-domain/Multiplier.class',
                'hash' => '336FA507E997A3DBC206125A78E142792DFF3E37',
            ],
            [
                'name' => 'rng-service-extras/WeightedChoice.class',
                'hash' => '3FAABB401B97C9CD8A07B5EF82BB151483F1D727',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'rng-service-extras/FloatRngService.class',
                'hash' => '4B5B324C80D0E64E9317B7E23795372EF3C8FD6D',
            ],
            [
                'name' => 'rng-service-extras/ShufflingRngService.class',
                'hash' => '50741B2C3B26F063FAD62F530978EEF53144C2B2',
            ],
            [
                'name' => 'rng-service-extras/WeightedRngService.class',
                'hash' => '584681BBB6CEB7FA7EB6DFA800E0DF5E0CD2B111',
            ],
            [
                'name' => 'gonzo-rng/application.conf',
                'hash' => '68AA826D889A6B4C515201AA0BD5247B232247FA',
            ],
            [
                'name' => 'live-surprise/RemoteRngMessages.class',
                'hash' => '68AB21A648D7AF109FEA655D8DBE635A71E3DB97',
            ],
            [
                'name' => 'live-surprise/RemoteRngServiceConfig.class',
                'hash' => '6C4D5A07248452D315440CC52FC7CF238AE17632',
            ],
            [
                'name' => 'gonzo-domain/MaxPayoutLimit.class',
                'hash' => '76B91CC919709D9AD77BC9E1E1602F580206FBD6',
            ],
            [
                'name' => 'gonzo-rng/GonzoRng.class',
                'hash' => '8616CE85107AE4DB1730027688378AD34726E091',
            ],
            [
                'name' => 'gonzo-rng/CreateBonusRound.class',
                'hash' => '9D1BCEB404D7BB1E8F3B79502F2273FE19A3E7E9',
            ],
            [
                'name' => 'live-surprise/application.conf',
                'hash' => 'BECF234B0781D448369BC3CE9C84AD3FF040A1C1',
            ],
            [
                'name' => 'gonzo-rng/HttpServer.class',
                'hash' => 'C239DF9CEBB21AE32882BC62CB2723B9A1C1584A',
            ],
            [
                'name' => 'rng-service-extras/RngServiceExtras.class',
                'hash' => 'C567DED9539043425AB362564F0282BA95FFE8A3',
            ],
            [
                'name' => 'rng-service-extras/Weighted.class',
                'hash' => 'FB855DC1DE72CC935BCC1A793E1B5ABB22A76CB5',
            ],
            [
                'name' => 'gonzo-domain/PayOutBets.class',
                'hash' => 'FCDE7AFFD9C650AFDAF2F7C841535A64A2AD8180',
            ],
        ]
    ],
    [
        'code' => '75959',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'rng-service/RngService.class',
                'hash' => '06242EA1D62E620707E0CFB9F3BD77C01FAC8183',
            ],
            [
                'name' => 'rng-blackjack/LightningMultipliers.class',
                'hash' => '0BF56ACF6AFC2B9718B47076596E98DE29305527',
            ],
            [
                'name' => 'rng-blackjack/RuleDescription.class',
                'hash' => '19695AF1E143B3390241BB5584D7B426BC1EC434',
            ],
            [
                'name' => 'rng-blackjack/SideBet.class',
                'hash' => '2C413B940DABB0DBB40A925DEA152017171AF949',
            ],
            [
                'name' => 'rng-service-extras/WeightedChoice.class',
                'hash' => '3FAABB401B97C9CD8A07B5EF82BB151483F1D727',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'rng-ub/RngWeighted.class',
                'hash' => '4476047A69D24CBCD0D7E32714352612DB958550',
            ],
            [
                'name' => 'rng-service-extras/FloatRngService.class',
                'hash' => '4B5B324C80D0E64E9317B7E23795372EF3C8FD6D',
            ],
            [
                'name' => 'rng-service-extras/ShufflingRngService.class',
                'hash' => '50741B2C3B26F063FAD62F530978EEF53144C2B2',
            ],
            [
                'name' => 'rng-ub/RngInt.class',
                'hash' => '53162A5EA1AB89E93FF12742757BC38F83926D97',
            ],
            [
                'name' => 'rng-ub/RngShuffle.class',
                'hash' => '579732894BD5428E2538599470373EB5E03037D7',
            ],
            [
                'name' => 'rng-service-extras/WeightedRngService.class',
                'hash' => '584681BBB6CEB7FA7EB6DFA800E0DF5E0CD2B111',
            ],
            [
                'name' => 'rng-ub/RngShuffleImpl.class',
                'hash' => '6E19FCDA545AF027F403B25FE5DA5084A0AF2B04',
            ],
            [
                'name' => 'rng-ub/RngIntImpl.class',
                'hash' => '76BBE7CDFC507D54D588D92B91AB808E67D0AD1B',
            ],
            [
                'name' => 'rng-blackjack/Outcome.class',
                'hash' => '8051F85D2413C3388A56BAD7F48D705C2FB1B584',
            ],
            [
                'name' => 'rng-blackjack/DealingService.class',
                'hash' => '817369E468527F37231665C62E5B1FD4C5541825',
            ],
            [
                'name' => 'rng-blackjack/BetsValidationLogic.class',
                'hash' => '98A82C47DEA14046EC1FB153B6D40DE39C63C502',
            ],
            [
                'name' => 'rng-ub/RngWeightedImpl.class',
                'hash' => 'A5D11515B73AE003B22B672329563F25604D805C',
            ],
            [
                'name' => 'rng-blackjack/PayoutCalculation.class',
                'hash' => 'A67214362D83AACCA82F0B87E6951E8085CC1B71',
            ],
            [
                'name' => 'rng-blackjack/Payout.class',
                'hash' => 'ADAD8EBA9D19D117AC4BA8939A593B46D3B134BB',
            ],
            [
                'name' => 'rng-service-extras/RngServiceExtras.class',
                'hash' => 'C567DED9539043425AB362564F0282BA95FFE8A3',
            ],
            [
                'name' => 'rng-blackjack/BlackjackTableConfig.class',
                'hash' => 'C6624009FB07988C30FBC2AB92DAB2D89299EA47',
            ],
            [
                'name' => 'rng-blackjack/GameMetaData.class',
                'hash' => 'CB6F0AEDD2AF2F419DCB620064148CED13D34E59',
            ],
            [
                'name' => 'rng-blackjack/GameAggregate.class',
                'hash' => 'CDC0C2A56103C500DDA8BC7513DF3029C2F7CCB9',
            ],
            [
                'name' => 'rng-blackjack/PlayerAggregate.class',
                'hash' => 'E830CF6BD03AFD4B080AA7610A53472D9CA16042',
            ],
            [
                'name' => 'rng-service-extras/Weighted.class',
                'hash' => 'FB855DC1DE72CC935BCC1A793E1B5ABB22A76CB5',
            ],
            [
                'name' => 'rng-blackjack/BlackjackGame.class',
                'hash' => 'FF5B09DA418A4271F882CD51F150C0DA21856983',
            ],
        ]
    ],
    [
        'code' => '75960',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'baccarat-domain/BaccaratPayouts.class',
                'hash' => '042E3BC890A305299369586A38B968F445A98D5C',
            ],
            [
                'name' => 'rng-service/RngService.class',
                'hash' => '06242EA1D62E620707E0CFB9F3BD77C01FAC8183',
            ],
            [
                'name' => 'baccarat-domain/LightningConstants.class',
                'hash' => '0F07CB1E089A2F8B749B399C31BE7B658FC98BAC',
            ],
            [
                'name' => 'rng-baccarat/TableConfig.class',
                'hash' => '19ADFE0B6A3DFF620445864BDCAE76FDD7AEB73F',
            ],
            [
                'name' => 'rng-baccarat/ShoeState.class',
                'hash' => '21A977189160210A28C89517F491D55888F28598',
            ],
            [
                'name' => 'baccarat-domain/LightningMultipliersGenerator.class',
                'hash' => '252B047038EED4420A66A518B36466B738B8DB27',
            ],
            [
                'name' => 'rng-service-extras/WeightedChoice.class',
                'hash' => '3FAABB401B97C9CD8A07B5EF82BB151483F1D727',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'rng-ub/RngWeighted.class',
                'hash' => '4476047A69D24CBCD0D7E32714352612DB958550',
            ],
            [
                'name' => 'rng-service-extras/FloatRngService.class',
                'hash' => '4B5B324C80D0E64E9317B7E23795372EF3C8FD6D',
            ],
            [
                'name' => 'rng-service-extras/ShufflingRngService.class',
                'hash' => '50741B2C3B26F063FAD62F530978EEF53144C2B2',
            ],
            [
                'name' => 'rng-ub/RngInt.class',
                'hash' => '53162A5EA1AB89E93FF12742757BC38F83926D97',
            ],
            [
                'name' => 'rng-ub/RngShuffle.class',
                'hash' => '579732894BD5428E2538599470373EB5E03037D7',
            ],
            [
                'name' => 'baccarat-domain/BaccaratModel.class',
                'hash' => '581FE6B52492790D3B7CC4068B93B2CD10C02498',
            ],
            [
                'name' => 'rng-service-extras/WeightedRngService.class',
                'hash' => '584681BBB6CEB7FA7EB6DFA800E0DF5E0CD2B111',
            ],
            [
                'name' => 'baccarat-domain/Hand.class',
                'hash' => '6C3DB7F86687E529EC3F90655DAB4836FA28DFA8',
            ],
            [
                'name' => 'rng-ub/RngShuffleImpl.class',
                'hash' => '6E19FCDA545AF027F403B25FE5DA5084A0AF2B04',
            ],
            [
                'name' => 'rng-ub/RngIntImpl.class',
                'hash' => '76BBE7CDFC507D54D588D92B91AB808E67D0AD1B',
            ],
            [
                'name' => 'rng-baccarat/ShoeAggregate.class',
                'hash' => '7D8B2E6CB502EC2F605516567946D5A594AC70E4',
            ],
            [
                'name' => 'rng-baccarat/BaccaratGame.class',
                'hash' => '87DBE89D58E3F7DA43BD266887C2EC52F2DF47A2',
            ],
            [
                'name' => 'rng-ub/RngWeightedImpl.class',
                'hash' => 'A5D11515B73AE003B22B672329563F25604D805C',
            ],
            [
                'name' => 'baccarat-domain/ShoeUtil.class',
                'hash' => 'BCBEDB8C53176E47E53D8D0F4D1FFA7B71344F56',
            ],
            [
                'name' => 'rng-service-extras/RngServiceExtras.class',
                'hash' => 'C567DED9539043425AB362564F0282BA95FFE8A3',
            ],
            [
                'name' => 'rng-service-extras/Weighted.class',
                'hash' => 'FB855DC1DE72CC935BCC1A793E1B5ABB22A76CB5',
            ],
        ]
    ],
    [
        'code' => '75961',
        'cert_ver' => '3',
        'software_modules' => [
            [
                'name' => 'baccarat-domain/BaccaratPayouts.class',
                'hash' => '042E3BC890A305299369586A38B968F445A98D5C',
            ],
            [
                'name' => 'rng-service/RngService.class',
                'hash' => '06242EA1D62E620707E0CFB9F3BD77C01FAC8183',
            ],
            [
                'name' => 'baccarat-domain/LightningConstants.class',
                'hash' => '0F07CB1E089A2F8B749B399C31BE7B658FC98BAC',
            ],
            [
                'name' => 'rng-baccarat/TableConfig.class',
                'hash' => '19ADFE0B6A3DFF620445864BDCAE76FDD7AEB73F',
            ],
            [
                'name' => 'rng-baccarat/ShoeState.class',
                'hash' => '21A977189160210A28C89517F491D55888F28598',
            ],
            [
                'name' => 'rng-service-extras/WeightedChoice.class',
                'hash' => '3FAABB401B97C9CD8A07B5EF82BB151483F1D727',
            ],
            [
                'name' => 'evo-pipeline-steps/Compliance.groovy',
                'hash' => '4429BCF56F3787980A105C8B1EF11BAE5EBC49C9',
            ],
            [
                'name' => 'rng-ub/RngWeighted.class',
                'hash' => '4476047A69D24CBCD0D7E32714352612DB958550',
            ],
            [
                'name' => 'rng-service-extras/FloatRngService.class',
                'hash' => '4B5B324C80D0E64E9317B7E23795372EF3C8FD6D',
            ],
            [
                'name' => 'rng-service-extras/ShufflingRngService.class',
                'hash' => '50741B2C3B26F063FAD62F530978EEF53144C2B2',
            ],
            [
                'name' => 'rng-ub/RngInt.class',
                'hash' => '53162A5EA1AB89E93FF12742757BC38F83926D97',
            ],
            [
                'name' => 'rng-ub/RngShuffle.class',
                'hash' => '579732894BD5428E2538599470373EB5E03037D7',
            ],
            [
                'name' => 'baccarat-domain/BaccaratModel.class',
                'hash' => '581FE6B52492790D3B7CC4068B93B2CD10C02498',
            ],
            [
                'name' => 'rng-service-extras/WeightedRngService.class',
                'hash' => '584681BBB6CEB7FA7EB6DFA800E0DF5E0CD2B111',
            ],
            [
                'name' => 'baccarat-domain/Hand.class',
                'hash' => '6C3DB7F86687E529EC3F90655DAB4836FA28DFA8',
            ],
            [
                'name' => 'rng-ub/RngShuffleImpl.class',
                'hash' => '6E19FCDA545AF027F403B25FE5DA5084A0AF2B04',
            ],
            [
                'name' => 'rng-ub/RngIntImpl.class',
                'hash' => '76BBE7CDFC507D54D588D92B91AB808E67D0AD1B',
            ],
            [
                'name' => 'rng-baccarat/ShoeAggregate.class',
                'hash' => '7D8B2E6CB502EC2F605516567946D5A594AC70E4',
            ],
            [
                'name' => 'rng-baccarat/BaccaratGame.class',
                'hash' => '87DBE89D58E3F7DA43BD266887C2EC52F2DF47A2',
            ],
            [
                'name' => 'rng-ub/RngWeightedImpl.class',
                'hash' => 'A5D11515B73AE003B22B672329563F25604D805C',
            ],
            [
                'name' => 'baccarat-domain/GoldenWealthMultipliersGenerator.class',
                'hash' => 'ACAA5EC489B4AF714EE0D33FACE57C7C7DC5130B',
            ],
            [
                'name' => 'baccarat-domain/ShoeUtil.class',
                'hash' => 'BCBEDB8C53176E47E53D8D0F4D1FFA7B71344F56',
            ],
            [
                'name' => 'rng-service-extras/RngServiceExtras.class',
                'hash' => 'C567DED9539043425AB362564F0282BA95FFE8A3',
            ],
            [
                'name' => 'rng-service-extras/Weighted.class',
                'hash' => 'FB855DC1DE72CC935BCC1A793E1B5ABB22A76CB5',
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

