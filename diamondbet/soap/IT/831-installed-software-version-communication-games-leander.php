<?php

require_once '/var/www/videoslots.it/phive/phive.php';
$user = cu($argv[1] ?? 6624618);

$games = [
    [
        'code' => '63708',
        'software_modules' => [
            [
                'name' => 'LegaCSPRNG.py',
                'hash' => 'FE3470A5441392ED827C3D85980AFF7C0E59FD17',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'Megan.py',
                'hash' => '06B7C4AB53E10FF609000C7C3DCB5E1332D4F390',
            ],
            [
                'name' => 'Rebecca.py',
                'hash' => '8CBA8B6597D9288519C374A030E141362AB962CC',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => 'F4CE28B628B1B796D45C6BF848A79B74BC954424',
            ],
            [
                'name' => '__init__.py',
                'hash' => '7A409C5C01887E5DE3365361CF5FA320ABED76D5',
            ],
            [
                'name' => 'config.py',
                'hash' => '1CE9C998D3728F6D6B230C14C905C49BB6371F09',
            ],
            [
                'name' => 'getsha1.py',
                'hash' => '15B99C42DD39AB687C8D1B550493BFDA53C12DED',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => 'F21DDA94AA1538D63754E5B4EF30526703327177',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '002538AE722A3193C600BBBD4DBABE16ED33C648',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '87F4A54C13F27F1CACF5A2ECA0622DD5752BAEAC',
            ],
        ]
    ],
    [
        'code' => '63723',
        'software_modules' => [
            [
                'name' => 'config.py.template',
                'hash' => '120FD92595786350E32FA35921B4D50AECB1D3E5',
            ],
            [
                'name' => '__init__.py',
                'hash' => '1D0CA0359EACD65705490CFB97A5D0484A209EB0',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '1DAF27838EA673FC9A90759C5CCB170C8BF23E44',
            ],
            [
                'name' => 'GameSession.py',
                'hash' => '2050BD8E21590E66ED119B9168238DF38A58D922',
            ],
            [
                'name' => 'Testing.py',
                'hash' => '23F702C6F94FD37414B5274ABE504F1A30929B74',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '27A274E6F43F8796593F48544526D7EEE69284B3',
            ],
            [
                'name' => '_init__.py',
                'hash' => '2D2AA7573E10182369C1DC31BED757712FC515B1',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '50E27B1CA9A169EA5E5959F1997E61592C598040',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'RandomSuply.py',
                'hash' => 'AB0DF43A51439003EE98088E7C5E0A2F75F9D5CA',
            ],
            [
                'name' => 'ValueObjects.py',
                'hash' => 'BC893184CCEB3C7FC9FC73857C817B523646A3E4',
            ],
            [
                'name' => 'config.py',
                'hash' => 'BD0844613C1C469874E282017C6435047DB2A674',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'Megan.py',
                'hash' => 'C532D8670327207B21271818E1290A347030FBFE',
            ],
            [
                'name' => 'DataAccess.py',
                'hash' => 'D3FBE46D82341CFD2C6FDF49D56F7FEDDCD8D230',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'E29705C2E2B4E4EF01C08ACB57E14D6D6B19341F',
            ],
        ]
    ],
    [
        'code' => '63724',
        'software_modules' => [
            [
                'name' => 'config.py.template',
                'hash' => '120FD92595786350E32FA35921B4D50AECB1D3E5',
            ],
            [
                'name' => '_init__.py',
                'hash' => '1D0CA0359EACD65705490CFB97A5D0484A209EB0',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '1DAF27838EA673FC9A90759C5CCB170C8BF23E44',
            ],
            [
                'name' => 'GameSession.py',
                'hash' => '2050BD8E21590E66ED119B9168238DF38A58D922',
            ],
            [
                'name' => 'Testing.py',
                'hash' => '23F702C6F94FD37414B5274ABE504F1A30929B74',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '27A274E6F43F8796593F48544526D7EEE69284B3',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '332052282E4F4CA56F8F272886557526AE6A4A8A',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '50E27B1CA9A169EA5E5959F1997E61592C598040',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
            [
                'name' => 'config.py',
                'hash' => '6A31359E6007ECEE0B741A80372A64D034208232',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'RandomSuply.py',
                'hash' => 'AB0DF43A51439003EE98088E7C5E0A2F75F9D5CA',
            ],
            [
                'name' => 'ValueObjects.py',
                'hash' => 'BC893184CCEB3C7FC9FC73857C817B523646A3E4',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'Megan.py',
                'hash' => 'C532D8670327207B21271818E1290A347030FBFE',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'CC0A51FA1FF82BACEC846AA8F43024C8688C90DC',
            ],
            [
                'name' => 'DataAccess.py',
                'hash' => 'D3FBE46D82341CFD2C6FDF49D56F7FEDDCD8D230',
            ],
        ]
    ],
    [
        'code' => '63725',
        'software_modules' => [
            [
                'name' => 'config.py.template',
                'hash' => '120FD92595786350E32FA35921B4D50AECB1D3E5',
            ],
            [
                'name' => '__init__.py',
                'hash' => '1A29B9BA66A9187BBC5310D5E2937CC2556BCECB',
            ],
            [
                'name' => '_init__.py',
                'hash' => '1D0CA0359EACD65705490CFB97A5D0484A209EB0',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '1DAF27838EA673FC9A90759C5CCB170C8BF23E44',
            ],
            [
                'name' => 'GameSession.py',
                'hash' => '2050BD8E21590E66ED119B9168238DF38A58D922',
            ],
            [
                'name' => 'Testing.py',
                'hash' => '23F702C6F94FD37414B5274ABE504F1A30929B74',
            ],
            [
                'name' => 'config.py',
                'hash' => '274685EE86467075AF602BEC3E3A28B86767C23D',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '27A274E6F43F8796593F48544526D7EEE69284B3',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '50E27B1CA9A169EA5E5959F1997E61592C598040',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'RandomSuply.py',
                'hash' => 'AB0DF43A51439003EE98088E7C5E0A2F75F9D5CA',
            ],
            [
                'name' => 'ValueObjects.py',
                'hash' => 'BC893184CCEB3C7FC9FC73857C817B523646A3E4',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'Megan.py',
                'hash' => 'C532D8670327207B21271818E1290A347030FBFE',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'CE9565DA30B21F8469B1A184230D531F79CED2C2',
            ],
            [
                'name' => 'DataAccess.py',
                'hash' => 'D3FBE46D82341CFD2C6FDF49D56F7FEDDCD8D230',
            ],
        ]
    ],
    [
        'code' => '63727',
        'software_modules' => [
            [
                'name' => 'config.py.template',
                'hash' => '120FD92595786350E32FA35921B4D50AECB1D3E5',
            ],
            [
                'name' => '__init__.py',
                'hash' => '1D0CA0359EACD65705490CFB97A5D0484A209EB0',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '1DAF27838EA673FC9A90759C5CCB170C8BF23E44',
            ],
            [
                'name' => 'GameSession.py',
                'hash' => '2050BD8E21590E66ED119B9168238DF38A58D922',
            ],
            [
                'name' => 'Testing.py',
                'hash' => '23F702C6F94FD37414B5274ABE504F1A30929B74',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '27A274E6F43F8796593F48544526D7EEE69284B3',
            ],
            [
                'name' => '_init__.py',
                'hash' => '4BDBC573DB5B4447242A59886C0E3DDB634AB138',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '50E27B1CA9A169EA5E5959F1997E61592C598040',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
            [
                'name' => 'config.py',
                'hash' => '824477A1521145EFC830255B0D3E6A26B30A6E3F',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'RandomSuply.py',
                'hash' => 'AB0DF43A51439003EE98088E7C5E0A2F75F9D5CA',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'AEA980E06C659E19047BA4251882077D442B55C2',
            ],
            [
                'name' => 'ValueObjects.py',
                'hash' => 'BC893184CCEB3C7FC9FC73857C817B523646A3E4',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'Megan.py',
                'hash' => 'C532D8670327207B21271818E1290A347030FBFE',
            ],
            [
                'name' => 'DataAccess.py',
                'hash' => 'D3FBE46D82341CFD2C6FDF49D56F7FEDDCD8D230',
            ],
        ]
    ],
    [
        'code' => '63728',
        'software_modules' => [
            [
                'name' => 'config.py.template',
                'hash' => '120FD92595786350E32FA35921B4D50AECB1D3E5',
            ],
            [
                'name' => '_init__.py',
                'hash' => '131D553029161F74C21AC677158A47D74E19B27B',
            ],
            [
                'name' => '__init__.py',
                'hash' => '1D0CA0359EACD65705490CFB97A5D0484A209EB0',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '1DAF27838EA673FC9A90759C5CCB170C8BF23E44',
            ],
            [
                'name' => 'GameSession.py',
                'hash' => '2050BD8E21590E66ED119B9168238DF38A58D922',
            ],
            [
                'name' => 'Testing.py',
                'hash' => '23F702C6F94FD37414B5274ABE504F1A30929B74',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '27A274E6F43F8796593F48544526D7EEE69284B3',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '50E27B1CA9A169EA5E5959F1997E61592C598040',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'RandomSuply.py',
                'hash' => 'AB0DF43A51439003EE98088E7C5E0A2F75F9D5CA',
            ],
            [
                'name' => 'ValueObjects.py',
                'hash' => 'BC893184CCEB3C7FC9FC73857C817B523646A3E4',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'Megan.py',
                'hash' => 'C532D8670327207B21271818E1290A347030FBFE',
            ],
            [
                'name' => 'DataAccess.py',
                'hash' => 'D3FBE46D82341CFD2C6FDF49D56F7FEDDCD8D230',
            ],
            [
                'name' => 'config.py',
                'hash' => 'DB5B040DA01C0FE1CB1C32D8AB83C185B9BBCC74',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'E30370A76017454FE5D6D9C83B4D1BE0CC78BDCA',
            ],
        ]
    ],
    [
        'code' => '63731',
        'software_modules' => [
            [
                'name' => 'config.py.template',
                'hash' => '120FD92595786350E32FA35921B4D50AECB1D3E5',
            ],
            [
                'name' => '_init__.py',
                'hash' => '1A29B9BA66A9187BBC5310D5E2937CC2556BCECB',
            ],
            [
                'name' => '__init__.py',
                'hash' => '1D0CA0359EACD65705490CFB97A5D0484A209EB0',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '1DAF27838EA673FC9A90759C5CCB170C8BF23E44',
            ],
            [
                'name' => 'GameSession.py',
                'hash' => '2050BD8E21590E66ED119B9168238DF38A58D922',
            ],
            [
                'name' => 'Testing.py',
                'hash' => '23F702C6F94FD37414B5274ABE504F1A30929B74',
            ],
            [
                'name' => 'config.py',
                'hash' => '274685EE86467075AF602BEC3E3A28B86767C23D',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '27A274E6F43F8796593F48544526D7EEE69284B3',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '50E27B1CA9A169EA5E5959F1997E61592C598040',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'RandomSuply.py',
                'hash' => 'AB0DF43A51439003EE98088E7C5E0A2F75F9D5CA',
            ],
            [
                'name' => '__test__.py',
                'hash' => 'B7B9449A24999D7330794CA0EF6C8F0B00B574CE',
            ],
            [
                'name' => 'ValueObjects.py',
                'hash' => 'BC893184CCEB3C7FC9FC73857C817B523646A3E4',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'Megan.py',
                'hash' => 'C532D8670327207B21271818E1290A347030FBFE',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'CE9565DA30B21F8469B1A184230D531F79CED2C2',
            ],
            [
                'name' => 'DataAccess.py',
                'hash' => 'D3FBE46D82341CFD2C6FDF49D56F7FEDDCD8D230',
            ],
        ]
    ],
    [
        'code' => '63738',
        'software_modules' => [
            [
                'name' => 'config.py.template',
                'hash' => '120FD92595786350E32FA35921B4D50AECB1D3E5',
            ],
            [
                'name' => '__init__.py',
                'hash' => '1D0CA0359EACD65705490CFB97A5D0484A209EB0',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '1DAF27838EA673FC9A90759C5CCB170C8BF23E44',
            ],
            [
                'name' => 'GameSession.py',
                'hash' => '2050BD8E21590E66ED119B9168238DF38A58D922',
            ],
            [
                'name' => 'Testing.py',
                'hash' => '23F702C6F94FD37414B5274ABE504F1A30929B74',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '27A274E6F43F8796593F48544526D7EEE69284B3',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '50E27B1CA9A169EA5E5959F1997E61592C598040',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => '_init__.py',
                'hash' => '8E98AAA50DE01D0336CAFBF9F51617362ED6B1F5',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'RandomSuply.py',
                'hash' => 'AB0DF43A51439003EE98088E7C5E0A2F75F9D5CA',
            ],
            [
                'name' => 'ValueObjects.py',
                'hash' => 'BC893184CCEB3C7FC9FC73857C817B523646A3E4',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'Megan.py',
                'hash' => 'C532D8670327207B21271818E1290A347030FBFE',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'CD226E8385B20321ACBB7241FB3D2BA4923DF3A1',
            ],
            [
                'name' => 'DataAccess.py',
                'hash' => 'D3FBE46D82341CFD2C6FDF49D56F7FEDDCD8D230',
            ],
            [
                'name' => 'config.py',
                'hash' => 'F6321D48A6DBBF5483EF1C85E216CE02D1AE5984',
            ],
        ]
    ],
    [
        'code' => '63806',
        'software_modules' => [
            [
                'name' => '__init__.py',
                'hash' => '01FB355F99449B971D363B873316372B0D7BDE22',
            ],
            [
                'name' => 'config.py',
                'hash' => '10F56927AC7D098193528A5D3411CFF60F2D6C68',
            ],
            [
                'name' => 'config.py.template',
                'hash' => '120FD92595786350E32FA35921B4D50AECB1D3E5',
            ],
            [
                'name' => '_init__.py',
                'hash' => '1D0CA0359EACD65705490CFB97A5D0484A209EB0',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '1DAF27838EA673FC9A90759C5CCB170C8BF23E44',
            ],
            [
                'name' => 'GameSession.py',
                'hash' => '2050BD8E21590E66ED119B9168238DF38A58D922',
            ],
            [
                'name' => 'Testing.py',
                'hash' => '23F702C6F94FD37414B5274ABE504F1A30929B74',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '27A274E6F43F8796593F48544526D7EEE69284B3',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '50E27B1CA9A169EA5E5959F1997E61592C598040',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '70911C673EBDECBE0E4E0C48AD1F39EAF03ED024',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'RandomSuply.py',
                'hash' => 'AB0DF43A51439003EE98088E7C5E0A2F75F9D5CA',
            ],
            [
                'name' => 'ValueObjects.py',
                'hash' => 'BC893184CCEB3C7FC9FC73857C817B523646A3E4',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'Megan.py',
                'hash' => 'C532D8670327207B21271818E1290A347030FBFE',
            ],
            [
                'name' => 'DataAccess.py',
                'hash' => 'D3FBE46D82341CFD2C6FDF49D56F7FEDDCD8D230',
            ],
        ]
    ],
    [
        'code' => '63807',
        'software_modules' => [
            [
                'name' => 'config.py.template',
                'hash' => '120FD92595786350E32FA35921B4D50AECB1D3E5',
            ],
            [
                'name' => '_init__.py',
                'hash' => '1D0CA0359EACD65705490CFB97A5D0484A209EB0',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '1DAF27838EA673FC9A90759C5CCB170C8BF23E44',
            ],
            [
                'name' => 'GameSession.py',
                'hash' => '2050BD8E21590E66ED119B9168238DF38A58D922',
            ],
            [
                'name' => 'Testing.py',
                'hash' => '23F702C6F94FD37414B5274ABE504F1A30929B74',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '27A274E6F43F8796593F48544526D7EEE69284B3',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '50E27B1CA9A169EA5E5959F1997E61592C598040',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '54FC0B6262F0625697BF56076A5067C1A254B8F1',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'RandomSuply.py',
                'hash' => 'AB0DF43A51439003EE98088E7C5E0A2F75F9D5CA',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'B518AAEEA602C81A2128FF8D4C894EF94FD85FB7',
            ],
            [
                'name' => 'ValueObjects.py',
                'hash' => 'BC893184CCEB3C7FC9FC73857C817B523646A3E4',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'Megan.py',
                'hash' => 'C532D8670327207B21271818E1290A347030FBFE',
            ],
            [
                'name' => 'DataAccess.py',
                'hash' => 'D3FBE46D82341CFD2C6FDF49D56F7FEDDCD8D230',
            ],
            [
                'name' => 'config.py',
                'hash' => 'FBC1244FC72E5CC7402043847FCBA1347426FD3A',
            ],
        ]
    ],
    [
        'code' => '63809',
        'software_modules' => [
            [
                'name' => 'config.py.template',
                'hash' => '120FD92595786350E32FA35921B4D50AECB1D3E5',
            ],
            [
                'name' => '_init__.py',
                'hash' => '1D0CA0359EACD65705490CFB97A5D0484A209EB0',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '1DAF27838EA673FC9A90759C5CCB170C8BF23E44',
            ],
            [
                'name' => 'GameSession.py',
                'hash' => '2050BD8E21590E66ED119B9168238DF38A58D922',
            ],
            [
                'name' => 'Testing.py',
                'hash' => '23F702C6F94FD37414B5274ABE504F1A30929B74',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '27A274E6F43F8796593F48544526D7EEE69284B3',
            ],
            [
                'name' => '__init__.py',
                'hash' => '33035B6702166590E94BC101AD1BC40AAFFF4EAC',
            ],
            [
                'name' => 'config.py',
                'hash' => '33B90D89D509BD8A613D8DF8A91F9D57FD9B9446',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '50E27B1CA9A169EA5E5959F1997E61592C598040',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '56F04CEBF8E6D8787E1B42678BCB1ABDFF0C97C9',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'RandomSuply.py',
                'hash' => 'AB0DF43A51439003EE98088E7C5E0A2F75F9D5CA',
            ],
            [
                'name' => 'ValueObjects.py',
                'hash' => 'BC893184CCEB3C7FC9FC73857C817B523646A3E4',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'Megan.py',
                'hash' => 'C532D8670327207B21271818E1290A347030FBFE',
            ],
            [
                'name' => 'DataAccess.py',
                'hash' => 'D3FBE46D82341CFD2C6FDF49D56F7FEDDCD8D230',
            ],
        ]
    ],
    [
        'code' => '63810',
        'software_modules' => [
            [
                'name' => 'config.py.template',
                'hash' => '120FD92595786350E32FA35921B4D50AECB1D3E5',
            ],
            [
                'name' => '_init__.py',
                'hash' => '1D0CA0359EACD65705490CFB97A5D0484A209EB0',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '1DAF27838EA673FC9A90759C5CCB170C8BF23E44',
            ],
            [
                'name' => 'GameSession.py',
                'hash' => '2050BD8E21590E66ED119B9168238DF38A58D922',
            ],
            [
                'name' => 'config.py',
                'hash' => '20D63975C2FEC5CB95531A09814A24B897C9D4D6',
            ],
            [
                'name' => 'Testing.py',
                'hash' => '23F702C6F94FD37414B5274ABE504F1A30929B74',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '27A274E6F43F8796593F48544526D7EEE69284B3',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '50E27B1CA9A169EA5E5959F1997E61592C598040',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '640EAF6535930EADA16DA9213C051A19AEC2A368',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'RandomSuply.py',
                'hash' => 'AB0DF43A51439003EE98088E7C5E0A2F75F9D5CA',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'AC9F563C295AB1EA830B3DB8C645B1488E655E8C',
            ],
            [
                'name' => 'ValueObjects.py',
                'hash' => 'BC893184CCEB3C7FC9FC73857C817B523646A3E4',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'Megan.py',
                'hash' => 'C532D8670327207B21271818E1290A347030FBFE',
            ],
            [
                'name' => 'DataAccess.py',
                'hash' => 'D3FBE46D82341CFD2C6FDF49D56F7FEDDCD8D230',
            ],
        ]
    ],
    [
        'code' => '63811',
        'software_modules' => [
            [
                'name' => 'config.py.template',
                'hash' => '120FD92595786350E32FA35921B4D50AECB1D3E5',
            ],
            [
                'name' => '_init__.py',
                'hash' => '1D0CA0359EACD65705490CFB97A5D0484A209EB0',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '1DAF27838EA673FC9A90759C5CCB170C8BF23E44',
            ],
            [
                'name' => 'GameSession.py',
                'hash' => '2050BD8E21590E66ED119B9168238DF38A58D922',
            ],
            [
                'name' => 'Testing.py',
                'hash' => '23F702C6F94FD37414B5274ABE504F1A30929B74',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '27A274E6F43F8796593F48544526D7EEE69284B3',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '50E27B1CA9A169EA5E5959F1997E61592C598040',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'RandomSuply.py',
                'hash' => 'AB0DF43A51439003EE98088E7C5E0A2F75F9D5CA',
            ],
            [
                'name' => 'ValueObjects.py',
                'hash' => 'BC893184CCEB3C7FC9FC73857C817B523646A3E4',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'C12DAF06165ADB9034D7ADB8726E272483201060',
            ],
            [
                'name' => 'Megan.py',
                'hash' => 'C532D8670327207B21271818E1290A347030FBFE',
            ],
            [
                'name' => 'DataAccess.py',
                'hash' => 'D3FBE46D82341CFD2C6FDF49D56F7FEDDCD8D230',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'DF91318AA200C6A3FBA56DAB97C2496F2E2EF867',
            ],
            [
                'name' => 'config.py',
                'hash' => 'E8AAD9A41966C3E0B603B960FBA1E700C55CCBBE',
            ],
        ]
    ],
    [
        'code' => '63739',
        'software_modules' => [
            [
                'name' => 'BaseGame.py',
                'hash' => 'F21DDA94AA1538D63754E5B4EF30526703327177',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '41E8D7E0B5BCDA39828CA99BBF36EE3C93296B97',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '87F4A54C13F27F1CACF5A2ECA0622DD5752BAEAC',
            ],
            [
                'name' => 'LegaCSPRNG.py',
                'hash' => 'FE3470A5441392ED827C3D85980AFF7C0E59FD17',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'Megan.py',
                'hash' => '06B7C4AB53E10FF609000C7C3DCB5E1332D4F390',
            ],
            [
                'name' => 'Rebecca.py',
                'hash' => '8CBA8B6597D9288519C374A030E141362AB962CC',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => 'F4CE28B628B1B796D45C6BF848A79B74BC954424',
            ],
            [
                'name' => '__init__.py',
                'hash' => '7154E2AA82915367ECBA85A10ABC7035B00A8A9D',
            ],
            [
                'name' => 'config.py',
                'hash' => '1A5CD9C05B1EBF8A956889DAF003717743301026',
            ],
            [
                'name' => 'getsha1.py',
                'hash' => '15B99C42DD39AB687C8D1B550493BFDA53C12DED',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
        ]
    ],
    [
        'code' => '63732',
        'software_modules' => [
            [
                'name' => 'BaseGame.py',
                'hash' => 'F21DDA94AA1538D63754E5B4EF30526703327177',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '89C2993C8B84EAE621085234189747F7338C9C6C',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '87F4A54C13F27F1CACF5A2ECA0622DD5752BAEAC',
            ],
            [
                'name' => 'LegaCSPRNG.py',
                'hash' => 'FE3470A5441392ED827C3D85980AFF7C0E59FD17',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'Megan.py',
                'hash' => '06B7C4AB53E10FF609000C7C3DCB5E1332D4F390',
            ],
            [
                'name' => 'Rebecca.py',
                'hash' => '8CBA8B6597D9288519C374A030E141362AB962CC',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => 'F4CE28B628B1B796D45C6BF848A79B74BC954424',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'F2B9865728C9BD4CB5843A43CDB1AA3726114E89',
            ],
            [
                'name' => 'config.py',
                'hash' => '7EBD85F12A6A08B1ADDF634CF14C52BBC2160928',
            ],
            [
                'name' => 'config_94.py',
                'hash' => 'ED6809916C3A087D9AA89E4A668E7A95342C04B6',
            ],
            [
                'name' => 'getsha1.py',
                'hash' => '15B99C42DD39AB687C8D1B550493BFDA53C12DED',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
        ]
    ],
    [
        'code' => '63730',
        'software_modules' => [
            [
                'name' => 'BaseGame.py',
                'hash' => '9D1C0E56D361F74004799EF9438A2617D5FEE887',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => '24663256BCD2225EE4E5816CA8907062C3279D5A',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '2546F80B1102624D509F6BB5F71504D633902CDB',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '95A8910AA84CC655C7771B76AF7F4A9FBF129F31',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '87F4A54C13F27F1CACF5A2ECA0622DD5752BAEAC',
            ],
            [
                'name' => 'LegaCSPRNG.py',
                'hash' => 'FE3470A5441392ED827C3D85980AFF7C0E59FD17',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => '28E26F2552C54D4ED75A53C8AC18C9F8A0CA8AB9',
            ],
            [
                'name' => 'Megan.py',
                'hash' => '575599D87C7185D437E161E7B9CB7DDF10C852AD',
            ],
            [
                'name' => 'Rebecca.py',
                'hash' => '8CBA8B6597D9288519C374A030E141362AB962CC',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '901537135D9232A015686386949149783DF00311',
            ],
            [
                'name' => '__init__.py',
                'hash' => '952910E034E50031DC2DF7A4540134C5C940C601',
            ],
            [
                'name' => 'config.py',
                'hash' => '9691C9D374F624BD4046B5C5EC4DE485C047695E',
            ],
            [
                'name' => 'getsha1.py',
                'hash' => '15B99C42DD39AB687C8D1B550493BFDA53C12DED',
            ],
            [
                'name' => 'utils.py',
                'hash' => 'D8AE64C8C34D3C18958974F3A302E761CC5C97C9',
            ],
        ]
    ],
    [
        'code' => '63729',
        'software_modules' => [
            [
                'name' => 'BaseGame.py',
                'hash' => 'F21DDA94AA1538D63754E5B4EF30526703327177',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '6B8FF0123E6CA2F29E68134338885755859BB346',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '87F4A54C13F27F1CACF5A2ECA0622DD5752BAEAC',
            ],
            [
                'name' => 'LegaCSPRNG.py',
                'hash' => 'FE3470A5441392ED827C3D85980AFF7C0E59FD17',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'Megan.py',
                'hash' => '06B7C4AB53E10FF609000C7C3DCB5E1332D4F390',
            ],
            [
                'name' => 'Rebecca.py',
                'hash' => '8CBA8B6597D9288519C374A030E141362AB962CC',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => 'F4CE28B628B1B796D45C6BF848A79B74BC954424',
            ],
            [
                'name' => '__init__.py',
                'hash' => '0BE1D16E56C77A5532D9749142BE927DBF16BB08',
            ],
            [
                'name' => 'config.py',
                'hash' => '97927524C5D876FE3EF9CFDDC27B671472D29866',
            ],
            [
                'name' => 'config_94.py',
                'hash' => '9F1237711EFEC0BC14C0971540E1FFC4F6FCA8C9',
            ],
            [
                'name' => 'getsha1.py',
                'hash' => '15B99C42DD39AB687C8D1B550493BFDA53C12DED',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
        ]
    ],
    [
        'code' => '63756',
        'software_modules' => [
            [
                'name' => 'BaseGame.py',
                'hash' => '9D1C0E56D361F74004799EF9438A2617D5FEE887',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => '24663256BCD2225EE4E5816CA8907062C3279D5A',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '2546F80B1102624D509F6BB5F71504D633902CDB',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '5056FD2ED06F9B6318CB64DE95ED56EACA9C68C7',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '87F4A54C13F27F1CACF5A2ECA0622DD5752BAEAC',
            ],
            [
                'name' => 'LegaCSPRNG.py',
                'hash' => 'FE3470A5441392ED827C3D85980AFF7C0E59FD17',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => '28E26F2552C54D4ED75A53C8AC18C9F8A0CA8AB9',
            ],
            [
                'name' => 'Megan.py',
                'hash' => '575599D87C7185D437E161E7B9CB7DDF10C852AD',
            ],
            [
                'name' => 'Rebecca.py',
                'hash' => '8CBA8B6597D9288519C374A030E141362AB962CC',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '901537135D9232A015686386949149783DF00311',
            ],
            [
                'name' => '__init__.py',
                'hash' => '2A7982B1773E0D9470909E87F2600B67085E11EB',
            ],
            [
                'name' => 'config.py',
                'hash' => 'A618AB99EFB913D09A6C2E61DC7E58AC74BDCBDB',
            ],
            [
                'name' => 'getsha1.py',
                'hash' => '15B99C42DD39AB687C8D1B550493BFDA53C12DED',
            ],
            [
                'name' => 'utils.py',
                'hash' => 'D8AE64C8C34D3C18958974F3A302E761CC5C97C9',
            ],
        ]
    ],
    [
        'code' => '91051',
        'software_modules' => [
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '03D89F0F1D403DD82CFF88DD09BABDECEC42E9D6',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'EF1800D4F40AD23287D6CAE77E6435CBBA1619B7',
            ],
            [
                'name' => 'config.py',
                'hash' => '28A9076592F892B858615E4BC5B9D981F38F1444',
            ],
            [
                'name' => 'config_94.py',
                'hash' => 'F73798D1FA3A8DC3EBA3477DF7BF905B4E043727',
            ],
        ]
    ],
    [
        'code' => '91052',
        'software_modules' => [
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '0145088B59BACD26917EED4B695F069E7EAB0078',
            ],
            [
                'name' => '__init__.py',
                'hash' => '3432B61B3CC5D58B7A28F5550824744B9FA013A4',
            ],
            [
                'name' => 'config.py',
                'hash' => 'B047D3AE2E8F3B43BB075B81F05DBE28824B05F5',
            ],
            [
                'name' => 'config_94.py',
                'hash' => '7AD3F694DE6867AF9ED131304FEBC6BB4402A037',
            ],
        ]
    ],
    [
        'code' => '98160',
        'software_modules' => [
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '6C60E518FF888247CACBEAA48A275548861FBA96',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'B5C145545A706367C1118F4C6C5364DA8F3DE70D',
            ],
            [
                'name' => 'config.py',
                'hash' => 'DC6CFD7EA57BB3711B7798980E047BA9D70B4637',
            ],
        ]
    ],
    [
        'code' => '98166',
        'software_modules' => [
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '3BBA3D27788EC77E582F5BC5D85090E7CB870757',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'F725040D8E94D63A2B19EF81665C6E7448053FA5',
            ],
            [
                'name' => 'config.py',
                'hash' => 'B05B0E4BB704C3E0BE2E0F206BF96FB9BA69A688',
            ],
            [
                'name' => 'config_94.py',
                'hash' => '86F8EE68CA43AD97E522441A6FFD6CCAAE553510',
            ],
        ]
    ],
    [
        'code' => '63721',
        'software_modules' => [
            [
                'name' => 'BaseGame.py',
                'hash' => '9D1C0E56D361F74004799EF9438A2617D5FEE887',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => '24663256BCD2225EE4E5816CA8907062C3279D5A',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '2546F80B1102624D509F6BB5F71504D633902CDB',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'A7D355BECDEEF346849977588138019D9F6E4B69',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '87F4A54C13F27F1CACF5A2ECA0622DD5752BAEAC',
            ],
            [
                'name' => 'LegaCSPRNG.py',
                'hash' => 'FE3470A5441392ED827C3D85980AFF7C0E59FD17',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => '28E26F2552C54D4ED75A53C8AC18C9F8A0CA8AB9',
            ],
            [
                'name' => 'Megan.py',
                'hash' => '575599D87C7185D437E161E7B9CB7DDF10C852AD',
            ],
            [
                'name' => 'Rebecca.py',
                'hash' => '8CBA8B6597D9288519C374A030E141362AB962CC',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '901537135D9232A015686386949149783DF00311',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'A658EAA1F7541B37F33CA36FB2DBC0819BFD1D0A',
            ],
            [
                'name' => 'config.py',
                'hash' => '7F475A206C05FC3414367F2F62CD4707D5EB6837',
            ],
            [
                'name' => 'config_94.py',
                'hash' => '3E7FFA954CEEFC40A0F668ADE3D0F43A783FEE75',
            ],
            [
                'name' => 'getsha1.py',
                'hash' => '15B99C42DD39AB687C8D1B550493BFDA53C12DED',
            ],
            [
                'name' => 'utils.py',
                'hash' => 'D8AE64C8C34D3C18958974F3A302E761CC5C97C9',
            ],
        ]
    ],
    [
        'code' => '63722',
        'software_modules' => [
            [
                'name' => 'BaseGame.py',
                'hash' => '9D1C0E56D361F74004799EF9438A2617D5FEE887',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => '24663256BCD2225EE4E5816CA8907062C3279D5A',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '2546F80B1102624D509F6BB5F71504D633902CDB',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '7D6FFD58BA62FDD21CB42E93ADC2EBB8907C1D2F',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '87F4A54C13F27F1CACF5A2ECA0622DD5752BAEAC',
            ],
            [
                'name' => 'LegaCSPRNG.py',
                'hash' => 'FE3470A5441392ED827C3D85980AFF7C0E59FD17',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => '28E26F2552C54D4ED75A53C8AC18C9F8A0CA8AB9',
            ],
            [
                'name' => 'Megan.py',
                'hash' => '575599D87C7185D437E161E7B9CB7DDF10C852AD',
            ],
            [
                'name' => 'Rebecca.py',
                'hash' => '8CBA8B6597D9288519C374A030E141362AB962CC',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '901537135D9232A015686386949149783DF00311',
            ],
            [
                'name' => '__init__.py',
                'hash' => '6FCE53C2A71FF6650E8BA066AE1BCFE6FF4B0EC8',
            ],
            [
                'name' => 'config.py',
                'hash' => 'C7792F53EBA7EE31E58DAC36D27AFDE95EF49F75',
            ],
            [
                'name' => 'getsha1.py',
                'hash' => '15B99C42DD39AB687C8D1B550493BFDA53C12DED',
            ],
            [
                'name' => 'utils.py',
                'hash' => 'D8AE64C8C34D3C18958974F3A302E761CC5C97C9',
            ],
        ]
    ],
    [
        'code' => '63733',
        'software_modules' => [
            [
                'name' => 'BaseGame.py',
                'hash' => 'F21DDA94AA1538D63754E5B4EF30526703327177',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'F5B17DCD49D966ED7CF32BB892B84DC15A4E1FD4',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '87F4A54C13F27F1CACF5A2ECA0622DD5752BAEAC',
            ],
            [
                'name' => 'LegaCSPRNG.py',
                'hash' => 'FE3470A5441392ED827C3D85980AFF7C0E59FD17',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'Megan.py',
                'hash' => '06B7C4AB53E10FF609000C7C3DCB5E1332D4F390',
            ],
            [
                'name' => 'Rebecca.py',
                'hash' => '8CBA8B6597D9288519C374A030E141362AB962CC',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => 'F4CE28B628B1B796D45C6BF848A79B74BC954424',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'C4F68BFA0A787CE369E5F363572CF622AC314BDA',
            ],
            [
                'name' => 'config.py',
                'hash' => '80D3022BD4B034C35D690C586329DDE16A842AF0',
            ],
            [
                'name' => 'getsha1.py',
                'hash' => '15B99C42DD39AB687C8D1B550493BFDA53C12DED',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
        ]
    ],
    [
        'code' => '63734',
        'software_modules' => [
            [
                'name' => 'BaseGame.py',
                'hash' => 'F21DDA94AA1538D63754E5B4EF30526703327177',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '89C1AC6A3EE85394413A052D61E421C8855242B1',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '87F4A54C13F27F1CACF5A2ECA0622DD5752BAEAC',
            ],
            [
                'name' => 'LegaCSPRNG.py',
                'hash' => 'FE3470A5441392ED827C3D85980AFF7C0E59FD17',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'Megan.py',
                'hash' => '06B7C4AB53E10FF609000C7C3DCB5E1332D4F390',
            ],
            [
                'name' => 'Rebecca.py',
                'hash' => '8CBA8B6597D9288519C374A030E141362AB962CC',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => 'F4CE28B628B1B796D45C6BF848A79B74BC954424',
            ],
            [
                'name' => '__init__.py',
                'hash' => '927E5FE5A2ED140463940945764163144C162721',
            ],
            [
                'name' => 'config.py',
                'hash' => '78E1CCBA64338A69DCA5AE28A8DB13803FB580E3',
            ],
            [
                'name' => 'getsha1.py',
                'hash' => '15B99C42DD39AB687C8D1B550493BFDA53C12DED',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
        ]
    ],
    [
        'code' => '63736',
        'software_modules' => [
            [
                'name' => 'BaseGame.py',
                'hash' => 'F21DDA94AA1538D63754E5B4EF30526703327177',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'E52DFCB28696E995D05A0B537F688AE6F2DBE4B7',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '87F4A54C13F27F1CACF5A2ECA0622DD5752BAEAC',
            ],
            [
                'name' => 'LegaCSPRNG.py',
                'hash' => 'FE3470A5441392ED827C3D85980AFF7C0E59FD17',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'Megan.py',
                'hash' => '06B7C4AB53E10FF609000C7C3DCB5E1332D4F390',
            ],
            [
                'name' => 'Rebecca.py',
                'hash' => '8CBA8B6597D9288519C374A030E141362AB962CC',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => 'F4CE28B628B1B796D45C6BF848A79B74BC954424',
            ],
            [
                'name' => '__init__.py',
                'hash' => '8437615C817DC1E8E0B2D6A8643868C70CAC05F6',
            ],
            [
                'name' => 'config.py',
                'hash' => '8FDE0EABFA7EC39B2B25B6F52C39EDCD7C3EBB64',
            ],
            [
                'name' => 'getsha1.py',
                'hash' => '15B99C42DD39AB687C8D1B550493BFDA53C12DED',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
        ]
    ],
    [
        'code' => '63737',
        'software_modules' => [
            [
                'name' => 'BaseGame.py',
                'hash' => 'F21DDA94AA1538D63754E5B4EF30526703327177',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'B0408365D0FBCB2632308313CC9D499FCC0E2B80',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '87F4A54C13F27F1CACF5A2ECA0622DD5752BAEAC',
            ],
            [
                'name' => 'LegaCSPRNG.py',
                'hash' => 'FE3470A5441392ED827C3D85980AFF7C0E59FD17',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'Megan.py',
                'hash' => '06B7C4AB53E10FF609000C7C3DCB5E1332D4F390',
            ],
            [
                'name' => 'Rebecca.py',
                'hash' => '8CBA8B6597D9288519C374A030E141362AB962CC',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => 'F4CE28B628B1B796D45C6BF848A79B74BC954424',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'E30F8C0FF0732E3B171B06AC2CBAAC9DC4C23FE3',
            ],
            [
                'name' => 'config.py',
                'hash' => '38122D415A6E937316F275232BFB22966C6ED7FE',
            ],
            [
                'name' => 'getsha1.py',
                'hash' => '15B99C42DD39AB687C8D1B550493BFDA53C12DED',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
        ]
    ],
    [
        'code' => '63750',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => '__init__.py',
                'hash' => '51FF5665C3254E4184023CEED6D61832BB1D4931',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '7FD51B31973D3055C5867BFB8862BCC384343F85',
            ],
            [
                'name' => 'config.py',
                'hash' => 'D0211FD736969D0336CBD2CD4AE0F706A7A03DB3',
            ],
        ]
    ],
    [
        'code' => '63752',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'config.py',
                'hash' => 'A5653FC9FBF621BD980BEA8181390812DF29490B',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'EA5726D10AA5FEECCF2AF85905181A2A13DC82A5',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'EFF5CE7408FEF32B2FA65D9267DF20380D76925D',
            ],
        ]
    ],
    [
        'code' => '63787',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => '__init__.py',
                'hash' => '8646895144988471AB0637086E60B7FB448AD5D9',
            ],
            [
                'name' => 'config.py',
                'hash' => 'BFDB911E3BC146BF78CEC563745652FECAA7ABAF',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'E7D45CDCEB5920BDC305C4935F65B3083501B0FC',
            ],
        ]
    ],
    [
        'code' => '63375',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'config.py',
                'hash' => '0AF93A17FD10EE4877EE9AE8E5BFB06D26350847',
            ],
            [
                'name' => 'config.py.template',
                'hash' => '120FD92595786350E32FA35921B4D50AECB1D3E5',
            ],
            [
                'name' => '_init__.py',
                'hash' => '1D0CA0359EACD65705490CFB97A5D0484A209EB0',
            ],
            [
                'name' => 'GameSession.py',
                'hash' => '2050BD8E21590E66ED119B9168238DF38A58D922',
            ],
            [
                'name' => 'Testing.py',
                'hash' => '23F702C6F94FD37414B5274ABE504F1A30929B74',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '27A274E6F43F8796593F48544526D7EEE69284B3',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '50E27B1CA9A169EA5E5959F1997E61592C598040',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '585CE76ECE152C87CB8134E4ED05EBC0C5B29E06',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'RandomSuply.py',
                'hash' => 'AB0DF43A51439003EE98088E7C5E0A2F75F9D5CA',
            ],
            [
                'name' => 'ValueObjects.py',
                'hash' => 'BC893184CCEB3C7FC9FC73857C817B523646A3E4',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'Megan.py',
                'hash' => 'C532D8670327207B21271818E1290A347030FBFE',
            ],
            [
                'name' => 'DataAccess.py',
                'hash' => 'D3FBE46D82341CFD2C6FDF49D56F7FEDDCD8D230',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'E1F6254ACD93B3EE6C75C1B15380FBD10BEAD90B',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '1DAF27838EA673FC9A90759C5CCB170C8BF23E44',
            ],
        ]
    ],
    [
        'code' => '63751',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'config.py',
                'hash' => '0C8A602633A02571FF44D5156E2425DB596083ED',
            ],
            [
                'name' => '__init__.py',
                'hash' => '85962B3ED86F4B39A01F34EB652D7B9A20E7C6DF',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'B84C9B84881D7FE60D7DEF4CDD042907DCBE82B2',
            ],
        ]
    ],
    [
        'code' => '63789',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => '__init__.py',
                'hash' => '010F48E99D494A63E853FBF4706F160CCFFFD202',
            ],
            [
                'name' => 'config.py',
                'hash' => '6E4E0B388311A60358B80001FE5B8915BFB2E0D8',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '650BBD601851D54A6B0D6250BA316BB6A88A370F',
            ],
        ]
    ],
    [
        'code' => '63791',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => '__init__.py',
                'hash' => '0C92EFD5306858E688B17FB9A2BBE9272CD5A312',
            ],
            [
                'name' => 'config.py',
                'hash' => 'FF8A578CC0237F40B09E0530F18C7BF922E85076',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '7432896DE806C40288641026BBBD62A5D8ADFF93',
            ],
        ]
    ],
    [
        'code' => '63792',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'config.py',
                'hash' => '095FAB096BB5D5BC7735EB594A37DEF1D87EAA27',
            ],
            [
                'name' => 'config.py.template',
                'hash' => '120FD92595786350E32FA35921B4D50AECB1D3E5',
            ],
            [
                'name' => '_init__.py',
                'hash' => '1D0CA0359EACD65705490CFB97A5D0484A209EB0',
            ],
            [
                'name' => 'GameSession.py',
                'hash' => '2050BD8E21590E66ED119B9168238DF38A58D922',
            ],
            [
                'name' => '__init__.py',
                'hash' => '23F38F76D17AF31D74950FF81099E86EEB928706',
            ],
            [
                'name' => 'Testing.py',
                'hash' => '23F702C6F94FD37414B5274ABE504F1A30929B74',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '27A274E6F43F8796593F48544526D7EEE69284B3',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '50E27B1CA9A169EA5E5959F1997E61592C598040',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'RandomSuply.py',
                'hash' => 'AB0DF43A51439003EE98088E7C5E0A2F75F9D5CA',
            ],
            [
                'name' => 'ValueObjects.py',
                'hash' => 'BC893184CCEB3C7FC9FC73857C817B523646A3E4',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'C41C1A301F1AE3BBD104BE8D85E97FA6DCC5CB9A',
            ],
            [
                'name' => 'Megan.py',
                'hash' => 'C532D8670327207B21271818E1290A347030FBFE',
            ],
            [
                'name' => 'DataAccess.py',
                'hash' => 'D3FBE46D82341CFD2C6FDF49D56F7FEDDCD8D230',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '1DAF27838EA673FC9A90759C5CCB170C8BF23E44',
            ],
        ]
    ],
    [
        'code' => '63802',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => '_init__.py',
                'hash' => 'B089A7C5601E6D04B7655B75914FB2E49A474262',
            ],
            [
                'name' => 'config.py',
                'hash' => 'FD930895B7AD6D0F00BEA87646BEDD744C61484F',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '5F22A3904C5DCD0FDF464A25F01F5BBB9812423B',
            ],
        ]
    ],
    [
        'code' => '63803',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => '__init__.py',
                'hash' => '1C6415E42E398B3E8EB7AD1EFADEB4C054A32F73',
            ],
            [
                'name' => 'config.py',
                'hash' => '41E09CA17A4F691F39B5580D259002A49023CAD5',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '41FB12C4372B87FB485C313BDF901939DC587194',
            ],
        ]
    ],
    [
        'code' => '79155',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'config.py.template',
                'hash' => '120FD92595786350E32FA35921B4D50AECB1D3E5',
            ],
            [
                'name' => '__init__.py',
                'hash' => '1D0CA0359EACD65705490CFB97A5D0484A209EB0',
            ],
            [
                'name' => 'GameSession.py',
                'hash' => '2050BD8E21590E66ED119B9168238DF38A58D922',
            ],
            [
                'name' => '_init__.py',
                'hash' => '21BBAE1379764B977430006361FDB673E87E3FC1',
            ],
            [
                'name' => 'Testing.py',
                'hash' => '23F702C6F94FD37414B5274ABE504F1A30929B74',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '27A274E6F43F8796593F48544526D7EEE69284B3',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '50E27B1CA9A169EA5E5959F1997E61592C598040',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '793074BBD3A7AA349651265CAE0B0058C4AF8F94',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'RandomSuply.py',
                'hash' => 'AB0DF43A51439003EE98088E7C5E0A2F75F9D5CA',
            ],
            [
                'name' => 'ValueObjects.py',
                'hash' => 'BC893184CCEB3C7FC9FC73857C817B523646A3E4',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'Megan.py',
                'hash' => 'C532D8670327207B21271818E1290A347030FBFE',
            ],
            [
                'name' => 'DataAccess.py',
                'hash' => 'D3FBE46D82341CFD2C6FDF49D56F7FEDDCD8D230',
            ],
            [
                'name' => 'config.py',
                'hash' => 'EDECB91228FBF068A80E66DA2F02E55DEFD7691C',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '1DAF27838EA673FC9A90759C5CCB170C8BF23E44',
            ],
        ]
    ],
    [
        'code' => '90985',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'config.py',
                'hash' => '325DB9FAA43B694AC6AA4663F3D346DF410B61C6',
            ],
            [
                'name' => '__init__.py',
                'hash' => '7F9AA12DA470F0BCA64DA09247142F2F8C8A0AB9',
            ],
            [
                'name' => 'config_94.py',
                'hash' => 'DAF73D1D6ACCD6D53F411047D990AA34A15862DF',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'DE19B422C051E8A368AC1AA7F3543197442615AD',
            ],
        ]
    ],
    [
        'code' => '90989',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'config.py',
                'hash' => '1E938F8446E543D29585AC1992DC47DA0A940C1A',
            ],
            [
                'name' => '__init__.py',
                'hash' => '8AB1C5A3280D3CAFDEBF6DA538AD383AF5221168',
            ],
            [
                'name' => 'config_94.py',
                'hash' => 'B21AD136EC12F188CF8022A28BE4E721B3B96287',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '5B3D2A60C6B5E8EEDBCB41BE3D2406A7B9480D02',
            ],
        ]
    ],
    [
        'code' => '90996',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => '__init__.py',
                'hash' => '04FCF897C139D7C827FDE251BA16DF9BA2C7183F',
            ],
            [
                'name' => 'config_94.py',
                'hash' => '7D3BD041E7F27A097A119F812C4FCF89D46024B8',
            ],
            [
                'name' => 'config.py',
                'hash' => 'A00D9EAAB75450B0EF7239C2D167105B93B38C67',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'FE3DDB4657BD9E00765757D7656E3BA3E3B09D18',
            ],
        ]
    ],
    [
        'code' => '91037',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => '__init__.py',
                'hash' => '72EBD54A8697F806FDA58BF21005B545931822E6',
            ],
            [
                'name' => 'config.py',
                'hash' => '7D2547305A5949583EBD0C34373798296A621133',
            ],
            [
                'name' => 'config_94.py',
                'hash' => 'EBC0D69708A154697ABF4728AEB341501DFB5F49',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '7B8A0D5237DA19B51E6206612BE4B6EE6D5C4D2B',
            ],
        ]
    ],
    [
        'code' => '104629',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => '__init__.py',
                'hash' => '16495B6A7997E523AFAF562835A82BBF1EFB5C7D',
            ],
            [
                'name' => 'config_94.py',
                'hash' => '4299DE6AAA6B3387A35B7CCD4655FE6223BDB334',
            ],
            [
                'name' => 'config.py',
                'hash' => 'C15531F864D7B2A017B3DFC808C15848D837F3A5',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '528B87483CDA3038F3C6E4B863972FF39FB19F75',
            ],
        ]
    ],
    [
        'code' => '104819',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => '__init__.py',
                'hash' => '13AE14BCF6EF42961E7BACB4B8F5FC54C0F971DF',
            ],
            [
                'name' => 'config_94.py',
                'hash' => '656D8CDF3F8714CF62A29F3AD4DD6FF66AAF0DDC',
            ],
            [
                'name' => 'config.py',
                'hash' => '8D528283E3A9B93861D25919FAE5AB254DD9182B',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '311CDF4929DDCFA542AC4D2EB7D5596447A4715C',
            ],
        ]
    ],
    [
        'code' => '106098',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'config.py',
                'hash' => '18C1E01CDCD948BC630B9A8F3F4514F2F646265D',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '6144F87FDFFDD301A92219D888C8E920E39DA192',
            ],
            [
                'name' => 'config_94.py',
                'hash' => 'A1A7F5B27E2F5DACEFD0115BFFABA49B944D2230',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'C34523CC0FDEC21ABE00E3E0BD5CA00BEF6B8062',
            ],
        ]
    ],
    [
        'code' => '106099',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'config.py',
                'hash' => '414D3F1EBF44A03C140FE6CCE6945A8EB8D37815',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'B4BFC1CE9E0AF5CC40102756E382FBF59939EBE7',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'B77FF4097D062659B77C494817E77C54AABFEA84',
            ],
        ]
    ],
    [
        'code' => '63705',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'getsha1.py',
                'hash' => '15B99C42DD39AB687C8D1B550493BFDA53C12DED',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => '24663256BCD2225EE4E5816CA8907062C3279D5A',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '2546F80B1102624D509F6BB5F71504D633902CDB',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => '28E26F2552C54D4ED75A53C8AC18C9F8A0CA8AB9',
            ],
            [
                'name' => 'config.py',
                'hash' => '4EBD4510C3172D71AF10B7C4CAC4BA1A42576C13',
            ],
            [
                'name' => 'Megan.py',
                'hash' => '575599D87C7185D437E161E7B9CB7DDF10C852AD',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '87F4A54C13F27F1CACF5A2ECA0622DD5752BAEAC',
            ],
            [
                'name' => 'Rebecca.py',
                'hash' => '8CBA8B6597D9288519C374A030E141362AB962CC',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '901537135D9232A015686386949149783DF00311',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'D56AAE938CF027E007AFCE9C5DC491F9FC161694',
            ],
            [
                'name' => 'utils.py',
                'hash' => 'D8AE64C8C34D3C18958974F3A302E761CC5C97C9',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'E27E8263154A69570EA4D038683734419840B751',
            ],
            [
                'name' => 'LegaCSPRNG.py',
                'hash' => 'FE3470A5441392ED827C3D85980AFF7C0E59FD17',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '9D1C0E56D361F74004799EF9438A2617D5FEE887',
            ],
        ]
    ],
    [
        'code' => '63706',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'Megan.py',
                'hash' => '06B7C4AB53E10FF609000C7C3DCB5E1332D4F390',
            ],
            [
                'name' => 'getsha1.py',
                'hash' => '15B99C42DD39AB687C8D1B550493BFDA53C12DED',
            ],
            [
                'name' => 'utils.py',
                'hash' => '5C18E804E0AC715C091020E35C0E435E22AF6401',
            ],
            [
                'name' => '__init__.py',
                'hash' => '5C80D942C83410E62F46201FFD2264D53A836AE4',
            ],
            [
                'name' => 'config_93.py',
                'hash' => '84D5545195C26E4FB190D55290F1875CE10CD098',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '851325931EE66462ABE5D5ACC15EF3EBC45E9A45',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '87F4A54C13F27F1CACF5A2ECA0622DD5752BAEAC',
            ],
            [
                'name' => 'Rebecca.py',
                'hash' => '8CBA8B6597D9288519C374A030E141362AB962CC',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => 'A8A4EAA47305B06172F99350A4F0923FCFFB91B1',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'BDD9377636766C206CC5E5A56BD9975AF9910C55',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'CA739B988FF01BEE94B6FCC5E206F9E4498A860B',
            ],
            [
                'name' => 'config.py',
                'hash' => 'EC94F3273C87005522065F938CC3B33B9684B1E7',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => 'F4CE28B628B1B796D45C6BF848A79B74BC954424',
            ],
            [
                'name' => 'LegaCSPRNG.py',
                'hash' => 'FE3470A5441392ED827C3D85980AFF7C0E59FD17',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => 'F21DDA94AA1538D63754E5B4EF30526703327177',
            ],
        ]
    ],
    [
        'code' => '63709',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'getsha1.py',
                'hash' => '15B99C42DD39AB687C8D1B550493BFDA53C12DED',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => '24663256BCD2225EE4E5816CA8907062C3279D5A',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '2546F80B1102624D509F6BB5F71504D633902CDB',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => '28E26F2552C54D4ED75A53C8AC18C9F8A0CA8AB9',
            ],
            [
                'name' => 'Megan.py',
                'hash' => '575599D87C7185D437E161E7B9CB7DDF10C852AD',
            ],
            [
                'name' => 'config.py',
                'hash' => '652BF09318B1FD89EEBE5F30ECCFCE6EE5B168DF',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '83589EE1A43E1D0FFF523567A9EE18FBB14B1177',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '87F4A54C13F27F1CACF5A2ECA0622DD5752BAEAC',
            ],
            [
                'name' => 'Rebecca.py',
                'hash' => '8CBA8B6597D9288519C374A030E141362AB962CC',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '901537135D9232A015686386949149783DF00311',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'C4A8A75CD5C05ED54A2F0A59D5CC0EFC513DFF36',
            ],
            [
                'name' => 'utils.py',
                'hash' => 'D8AE64C8C34D3C18958974F3A302E761CC5C97C9',
            ],
            [
                'name' => 'LegaCSPRNG.py',
                'hash' => 'FE3470A5441392ED827C3D85980AFF7C0E59FD17',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '9D1C0E56D361F74004799EF9438A2617D5FEE887',
            ],
        ]
    ],
    [
        'code' => '63711',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'getsha1.py',
                'hash' => '15B99C42DD39AB687C8D1B550493BFDA53C12DED',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => '24663256BCD2225EE4E5816CA8907062C3279D5A',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '2546F80B1102624D509F6BB5F71504D633902CDB',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => '28E26F2552C54D4ED75A53C8AC18C9F8A0CA8AB9',
            ],
            [
                'name' => 'Megan.py',
                'hash' => '575599D87C7185D437E161E7B9CB7DDF10C852AD',
            ],
            [
                'name' => '__init__.py',
                'hash' => '5C80D942C83410E62F46201FFD2264D53A836AE4',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '87F4A54C13F27F1CACF5A2ECA0622DD5752BAEAC',
            ],
            [
                'name' => 'Rebecca.py',
                'hash' => '8CBA8B6597D9288519C374A030E141362AB962CC',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '901537135D9232A015686386949149783DF00311',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'CA739B988FF01BEE94B6FCC5E206F9E4498A860B',
            ],
            [
                'name' => 'utils.py',
                'hash' => 'D8AE64C8C34D3C18958974F3A302E761CC5C97C9',
            ],
            [
                'name' => 'config.py',
                'hash' => 'EC94F3273C87005522065F938CC3B33B9684B1E7',
            ],
            [
                'name' => 'LegaCSPRNG.py',
                'hash' => 'FE3470A5441392ED827C3D85980AFF7C0E59FD17',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '9D1C0E56D361F74004799EF9438A2617D5FEE887',
            ],
        ]
    ],
    [
        'code' => '63714',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'getsha1.py',
                'hash' => '15B99C42DD39AB687C8D1B550493BFDA53C12DED',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => '24663256BCD2225EE4E5816CA8907062C3279D5A',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '2546F80B1102624D509F6BB5F71504D633902CDB',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => '28E26F2552C54D4ED75A53C8AC18C9F8A0CA8AB9',
            ],
            [
                'name' => 'Megan.py',
                'hash' => '575599D87C7185D437E161E7B9CB7DDF10C852AD',
            ],
            [
                'name' => 'config.py',
                'hash' => '7D7A83A32D697F2509094B3DB7D33E4E8ACE78CE',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '87F4A54C13F27F1CACF5A2ECA0622DD5752BAEAC',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '8A1DA46A30CC2E491BF01560CEFC4A6EF59CD42C',
            ],
            [
                'name' => 'Rebecca.py',
                'hash' => '8CBA8B6597D9288519C374A030E141362AB962CC',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '901537135D9232A015686386949149783DF00311',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'BC027436D64EC4D4BC636079245AC5C67B4EAA9C',
            ],
            [
                'name' => 'utils.py',
                'hash' => 'D8AE64C8C34D3C18958974F3A302E761CC5C97C9',
            ],
            [
                'name' => 'LegaCSPRNG.py',
                'hash' => 'FE3470A5441392ED827C3D85980AFF7C0E59FD17',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '9D1C0E56D361F74004799EF9438A2617D5FEE887',
            ],
        ]
    ],
    [
        'code' => '63715',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'getsha1.py',
                'hash' => '15B99C42DD39AB687C8D1B550493BFDA53C12DED',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => '24663256BCD2225EE4E5816CA8907062C3279D5A',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '2546F80B1102624D509F6BB5F71504D633902CDB',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => '28E26F2552C54D4ED75A53C8AC18C9F8A0CA8AB9',
            ],
            [
                'name' => 'Megan.py',
                'hash' => '575599D87C7185D437E161E7B9CB7DDF10C852AD',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '5AB49836CB1CAA4641F1E3701AB06BF414C1AE9E',
            ],
            [
                'name' => '__init__.py',
                'hash' => '5CF9B3F01729C80BD4BEB86C85487B2787C956F5',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '87F4A54C13F27F1CACF5A2ECA0622DD5752BAEAC',
            ],
            [
                'name' => 'Rebecca.py',
                'hash' => '8CBA8B6597D9288519C374A030E141362AB962CC',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '901537135D9232A015686386949149783DF00311',
            ],
            [
                'name' => 'config.py',
                'hash' => 'C6A895B7338C78BA5F9882C04E60C2D5EB736555',
            ],
            [
                'name' => 'utils.py',
                'hash' => 'D8AE64C8C34D3C18958974F3A302E761CC5C97C9',
            ],
            [
                'name' => 'LegaCSPRNG.py',
                'hash' => 'FE3470A5441392ED827C3D85980AFF7C0E59FD17',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '9D1C0E56D361F74004799EF9438A2617D5FEE887',
            ],
        ]
    ],
    [
        'code' => '63716',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'getsha1.py',
                'hash' => '15B99C42DD39AB687C8D1B550493BFDA53C12DED',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => '24663256BCD2225EE4E5816CA8907062C3279D5A',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '2546F80B1102624D509F6BB5F71504D633902CDB',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => '28E26F2552C54D4ED75A53C8AC18C9F8A0CA8AB9',
            ],
            [
                'name' => 'Megan.py',
                'hash' => '575599D87C7185D437E161E7B9CB7DDF10C852AD',
            ],
            [
                'name' => 'config.py',
                'hash' => '7D7A83A32D697F2509094B3DB7D33E4E8ACE78CE',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '87F4A54C13F27F1CACF5A2ECA0622DD5752BAEAC',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '8A1DA46A30CC2E491BF01560CEFC4A6EF59CD42C',
            ],
            [
                'name' => 'Rebecca.py',
                'hash' => '8CBA8B6597D9288519C374A030E141362AB962CC',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '901537135D9232A015686386949149783DF00311',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'BC027436D64EC4D4BC636079245AC5C67B4EAA9C',
            ],
            [
                'name' => 'utils.py',
                'hash' => 'D8AE64C8C34D3C18958974F3A302E761CC5C97C9',
            ],
            [
                'name' => 'LegaCSPRNG.py',
                'hash' => 'FE3470A5441392ED827C3D85980AFF7C0E59FD17',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '9D1C0E56D361F74004799EF9438A2617D5FEE887',
            ],
        ]
    ],
    [
        'code' => '63718',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'getsha1.py',
                'hash' => '15B99C42DD39AB687C8D1B550493BFDA53C12DED',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => '24663256BCD2225EE4E5816CA8907062C3279D5A',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '2546F80B1102624D509F6BB5F71504D633902CDB',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => '28E26F2552C54D4ED75A53C8AC18C9F8A0CA8AB9',
            ],
            [
                'name' => 'Megan.py',
                'hash' => '575599D87C7185D437E161E7B9CB7DDF10C852AD',
            ],
            [
                'name' => 'config.py',
                'hash' => '7D7A83A32D697F2509094B3DB7D33E4E8ACE78CE',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '87F4A54C13F27F1CACF5A2ECA0622DD5752BAEAC',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '8A1DA46A30CC2E491BF01560CEFC4A6EF59CD42C',
            ],
            [
                'name' => 'Rebecca.py',
                'hash' => '8CBA8B6597D9288519C374A030E141362AB962CC',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '901537135D9232A015686386949149783DF00311',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'BC027436D64EC4D4BC636079245AC5C67B4EAA9C',
            ],
            [
                'name' => 'utils.py',
                'hash' => 'D8AE64C8C34D3C18958974F3A302E761CC5C97C9',
            ],
            [
                'name' => 'LegaCSPRNG.py',
                'hash' => 'FE3470A5441392ED827C3D85980AFF7C0E59FD17',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '9D1C0E56D361F74004799EF9438A2617D5FEE887',
            ],
        ]
    ],
    [
        'code' => '63719',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'getsha1.py',
                'hash' => '15B99C42DD39AB687C8D1B550493BFDA53C12DED',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => '24663256BCD2225EE4E5816CA8907062C3279D5A',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '2546F80B1102624D509F6BB5F71504D633902CDB',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => '28E26F2552C54D4ED75A53C8AC18C9F8A0CA8AB9',
            ],
            [
                'name' => 'Megan.py',
                'hash' => '575599D87C7185D437E161E7B9CB7DDF10C852AD',
            ],
            [
                'name' => 'config.py',
                'hash' => '7D7A83A32D697F2509094B3DB7D33E4E8ACE78CE',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '87F4A54C13F27F1CACF5A2ECA0622DD5752BAEAC',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '8A1DA46A30CC2E491BF01560CEFC4A6EF59CD42C',
            ],
            [
                'name' => 'Rebecca.py',
                'hash' => '8CBA8B6597D9288519C374A030E141362AB962CC',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '901537135D9232A015686386949149783DF00311',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'BC027436D64EC4D4BC636079245AC5C67B4EAA9C',
            ],
            [
                'name' => 'utils.py',
                'hash' => 'D8AE64C8C34D3C18958974F3A302E761CC5C97C9',
            ],
            [
                'name' => 'LegaCSPRNG.py',
                'hash' => 'FE3470A5441392ED827C3D85980AFF7C0E59FD17',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '9D1C0E56D361F74004799EF9438A2617D5FEE887',
            ],
        ]
    ],
    [
        'code' => '91023',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'getsha1.py',
                'hash' => '15B99C42DD39AB687C8D1B550493BFDA53C12DED',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => '24663256BCD2225EE4E5816CA8907062C3279D5A',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => '2546F80B1102624D509F6BB5F71504D633902CDB',
            ],
            [
                'name' => 'MainClasses.py',
                'hash' => '28E26F2552C54D4ED75A53C8AC18C9F8A0CA8AB9',
            ],
            [
                'name' => 'Megan.py',
                'hash' => '575599D87C7185D437E161E7B9CB7DDF10C852AD',
            ],
            [
                'name' => '__init__.py',
                'hash' => '83025DEB6B61D8C471A2EC4B2A4C8621B5F934C7',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '87F4A54C13F27F1CACF5A2ECA0622DD5752BAEAC',
            ],
            [
                'name' => 'Rebecca.py',
                'hash' => '8CBA8B6597D9288519C374A030E141362AB962CC',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '901537135D9232A015686386949149783DF00311',
            ],
            [
                'name' => 'config.py',
                'hash' => 'A9D4E4FBE97D5BAF0E4B37AF742C1F60147016F4',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'C14627997A7AFE0FFBE5047D34F08690DA5E124E',
            ],
            [
                'name' => 'utils.py',
                'hash' => 'D8AE64C8C34D3C18958974F3A302E761CC5C97C9',
            ],
            [
                'name' => 'LegaCSPRNG.py',
                'hash' => 'FE3470A5441392ED827C3D85980AFF7C0E59FD17',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '9D1C0E56D361F74004799EF9438A2617D5FEE887',
            ],
        ]
    ],
    [
        'code' => '63786',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => '__init__.py',
                'hash' => '0E2BFF0520D58F145992E2480084D13833A5088E',
            ],
            [
                'name' => 'config.py',
                'hash' => '25412836F60C5E9F93D82B6623FFABF737CD3465',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '4B2E43F57527D9C416EB8668E67D0734F0045135',
            ],
        ]
    ],
    [
        'code' => '108245',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => '__init__.py',
                'hash' => '121611B4A9D583D97ED1E29BEA3E011B8ABBAA19',
            ],
            [
                'name' => 'config.py',
                'hash' => '6BF5CC67CDE4AA3003023BE450A0885E47E14068',
            ],
            [
                'name' => 'config_94.py',
                'hash' => 'D30DCD19FD2E112108F9121F6ED259D359216B3A',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '5183391D260E250E2CFBFAD2A3391AF71328483A',
            ],
        ]
    ],
    [
        'code' => '108247',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'config.py',
                'hash' => '41D24B934A9482B6BCD655E5C7FFC54694E13FD0',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'CE47132284404B90DD0767425D5EA408C192BACB',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '3D0FCAB2DA8C922DC553E362BD895B6A2D8E26A0',
            ],
        ]
    ],
    [
        'code' => '108670',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => '__init__.py',
                'hash' => '0C4D249C7B9745E5024203C830D368C65AF0134F',
            ],
            [
                'name' => 'config.py',
                'hash' => '3DFDED67020E8188A0A766A58874D4767698639B',
            ],
            [
                'name' => 'config_94.py',
                'hash' => 'F150064945E3F92F222346033DAAD0F4669F496D',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '4538F64B078ABC89C252926021C64CF96223FCD8',
            ],
        ]
    ],
    [
        'code' => '108671',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => '__init__.py',
                'hash' => '03FCC970CC6BD6A527DC5A76CADCCA39382240EC',
            ],
            [
                'name' => 'config_94.py',
                'hash' => '6C79DD9BC8B32E04B17BF3A0CFFE07437913F450',
            ],
            [
                'name' => 'config.py',
                'hash' => 'B3C2342145CAFA5FF90C6D6B85D822BA3CAA3C28',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '09108D29CE99AD4141F72D1365CF17E34E586E2E',
            ],
        ]
    ],
    [
        'code' => '108672',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'config.py',
                'hash' => '007FDB3D39414943602696BC66DCDF107350B6D7',
            ],
            [
                'name' => 'config_94.py',
                'hash' => '18C8302B7781CF4742A0B4BBFC7F6C92E3FCBEB5',
            ],
            [
                'name' => '__init__.py',
                'hash' => '6062B4D20F201527AE4C19D6B676F7DF373E6F1A',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '297178574564D08D60024E816B50E04714FEDEF6',
            ],
        ]
    ],
    [
        'code' => '108673',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => '__init__.py',
                'hash' => '0C4D249C7B9745E5024203C830D368C65AF0134F',
            ],
            [
                'name' => 'config.py',
                'hash' => '3DFDED67020E8188A0A766A58874D4767698639B',
            ],
            [
                'name' => 'config_94.py',
                'hash' => 'F150064945E3F92F222346033DAAD0F4669F496D',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '4538F64B078ABC89C252926021C64CF96223FCD8',
            ],
        ]
    ],
    [
        'code' => '108674',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'MainClasses.py',
                'hash' => '0FB3EC8BEAB870C4EAD176C15F92C402F79DC8B8',
            ],
            [
                'name' => 'getsha1.py',
                'hash' => '15B99C42DD39AB687C8D1B550493BFDA53C12DED',
            ],
            [
                'name' => 'Megan.py',
                'hash' => '191C62C0B9167E483D3C8D9014C331A875501FD6',
            ],
            [
                'name' => 'utils.py',
                'hash' => '476A5D6172BB798D7E1AA35CA399F1991583F42E',
            ],
            [
                'name' => 'SlotEvaluator.py',
                'hash' => '4D16AF60531F34FE2CC23F1521B65410CC799CBA',
            ],
            [
                'name' => '__init__.py',
                'hash' => '68FE5F59F941BFDD1EFDA5F530E498891788AD29',
            ],
            [
                'name' => 'config_94.py',
                'hash' => '6E964954E1006E2E04FAACC8D3E0C36B33C3049D',
            ],
            [
                'name' => 'LeanderRandom.py',
                'hash' => '87F4A54C13F27F1CACF5A2ECA0622DD5752BAEAC',
            ],
            [
                'name' => 'Rebecca.py',
                'hash' => '8CBA8B6597D9288519C374A030E141362AB962CC',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'AFFD423991B59DA4C6D0199D2654441F1866A43D',
            ],
            [
                'name' => 'config.py',
                'hash' => 'C7C3289E98A13F1B983893749B1361DC54E5CC36',
            ],
            [
                'name' => 'BonusFreeSpins.py',
                'hash' => 'CB071BFB9339997C50314377AE712161346DF807',
            ],
            [
                'name' => 'BaseSteps.py',
                'hash' => 'E0CD882EDE92B1E9C26326D2C7AC7362EBABED9C',
            ],
            [
                'name' => 'LegaCSPRNG.py',
                'hash' => 'FE3470A5441392ED827C3D85980AFF7C0E59FD17',
            ],
            [
                'name' => 'BaseGame.py',
                'hash' => '6B38227D6B3B9A5FD94DBD9E92A0862B544C1D9D',
            ],
        ]
    ],
    [
        'code' => '114749',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'config_94.py',
                'hash' => '26C72FAE970814A5378706E60551E57582CD36AB',
            ],
            [
                'name' => '__init__.py',
                'hash' => '46AB4858B399EA28BE022C347CA58EAAD0CBDA1F',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '92FF5D70ECBD6524DF0E5B8FC250EC357B99E73E',
            ],
            [
                'name' => 'config.py',
                'hash' => '95D481B0AA5ECA6B24A1F90BC118F177646808B0',
            ],
        ]
    ],
    [
        'code' => '114750',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '3CE7441B65CF69DFAAFB47BD8B8B5E7CBA4289A4',
            ],
            [
                'name' => 'config.py',
                'hash' => 'C44A5E95534BE7BE1DB6CA92CF87EA938F679CAD',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'F748B34734929DF3852540F6E374E78A7AC25548',
            ],
        ]
    ],
    [
        'code' => '114753',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'config.py',
                'hash' => '0E3B951379571C886CA4DE338F5B11BB69647187',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'AC6DF8B11CDCC5BB44EF95D7929C2BB7D98AA8DA',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'E62F3427AC5F1C3CC40905FBFBBFBE3A663072BF',
            ],
        ]
    ],
    [
        'code' => '113989',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => '__init__.py',
                'hash' => '1D3FB803884636D8BB02E1454DA91D2F640A0A07',
            ],
            [
                'name' => 'config.py',
                'hash' => '71F1D28C079C49A68B106422C460BF0230ECDAFE',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '8359E022B66E0183990F90EE28BAE4290B81BEA6',
            ],
        ]
    ],
    [
        'code' => '125973',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => '__init__.py',
                'hash' => '21F4A3800B6A5E1A071C56F1B6E265E922FCE1E9',
            ],
            [
                'name' => 'config.py',
                'hash' => '53F974B440B41F7134A5B48B7FD1708C2BC3B6B1',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'ED3A48338E463B7067197A98DCA5C06D6049233F',
            ],
        ]
    ],
    [
        'code' => '125976',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'config.py',
                'hash' => '8723DCBD69DAB0075EED53370707669A956EA85B',
            ],
            [
                'name' => '__init__.py',
                'hash' => '995F2B9754141CD23B5A587AEB99EC4248920003',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'D4EC214DECF8AFB5859BDD0EB5861853A0692742',
            ],
        ]
    ],
    [
        'code' => '125978',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'config.py',
                'hash' => '6C2A4533348284B09581C8521944412F76A6E542',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '9E761CAFB95000E8883704029155CC9184701CF2',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'C6FAA59D41319A1CC80E2A5EE03B79D94D75F268',
            ],
        ]
    ],
    [
        'code' => '125981',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'config.py',
                'hash' => '48DF1ACE6CE00A0D747D1CA286EAB0DCF1A78CFD',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '5EE383AC16E11C65737BF4F5AB0D34BAFF664325',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'D4605E6A8EC2575C72C203556197FEF409DD6700',
            ],
        ]
    ],
    [
        'code' => '125983',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => '__init__.py',
                'hash' => '5EBC55B042775A71CB7307C40E182BC3BFCC0EF4',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '846B566936F22CDCEECB03E59A157BDBF85544B6',
            ],
            [
                'name' => 'config.py',
                'hash' => 'A6498C932A154ADA2FDD05EABA59438130F2963E',
            ],
        ]
    ],
    [
        'code' => '125986',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => '__init__.py',
                'hash' => '7779FBAFE4BC4A470C703932D66249C2CB0F9366',
            ],
            [
                'name' => 'config.py',
                'hash' => '9E8AC589D15E9253984790961DBAE8A9DEC35A97',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'F19979815508078DFA1161766B9FEFE9A556A8BA',
            ],
        ]
    ],
    [
        'code' => '127845',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '0F6029D23E40AC9F5659AB00B31E644F66F843B6',
            ],
            [
                'name' => 'config.py',
                'hash' => '4AE949B5184F5213F0FBF91108620E346DD7B1A7',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'A9A9529687E8D22A453A53E26601967C2726E878',
            ],
        ]
    ],
    [
        'code' => '136069',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'config.py',
                'hash' => '0F6E23177F26BFA5DE4CB7B3F283887B48573960',
            ],
            [
                'name' => '__init__.py',
                'hash' => '48096B78962A74A8F9E8656A10B25AF63CF2D42B',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => '69877C865CC5212E1285D9AAABF1F5591F7BC43F',
            ],
        ]
    ],
    [
        'code' => '125988',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => '__init__.py',
                'hash' => '7d34239002ddf59c7e47a39a4907e83f699041df',
            ],
            [
                'name' => 'config.py',
                'hash' => 'abdab375d1949a72302b1ef1bac362aa0b4e1b92',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'e1c83e6904f6b18f3c0fe3373b5f9d473c29b683',
            ],
        ]
    ],
    [
        'code' => '138498',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => 'config.py',
                'hash' => '60c01c7ad88d29ce0c1cdc23fd88ade6fdf220f4',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'cd95a71825b59a148abdcc645e9476ed17a021ea',
            ],
            [
                'name' => '__init__.py',
                'hash' => 'e803dd65dc114f9849bc67847535982da0c8bc1b',
            ],
        ]
    ],
    [
        'code' => '140387',
        'cert_ver' => '1',
        'software_modules' => [
            [
                'name' => '__init__.py',
                'hash' => '278a6498b2ea19af48fd6310e5582c157ed39a80',
            ],
            [
                'name' => 'config.py',
                'hash' => '7789d11fc841d1b3d0024fdf02ac3ad35584ad4f',
            ],
            [
                'name' => 'ExecutionFlow.py',
                'hash' => 'd52887f00236d1d255d32cc63a53581b4a8564f4',
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
