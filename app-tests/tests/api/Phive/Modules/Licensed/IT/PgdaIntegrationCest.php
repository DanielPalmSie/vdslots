 <?php

/**
 * Class PgdaIntegrationCest
 */
class PgdaIntegrationCest
{
    /**
     * @var int
     */
    protected $game_code = 45014;

    /**
     * @var int
     */
    protected $game_type = 2;

    /**
     * @var int
     */
    private $request_id ;

    /**
     * @var string
     */
    protected $session_id;

    /**
     * @var string
     */
    private $participation_code;

    /**
     * Calls an action method in the IT class
     *
     * @param $action
     * @param $input
     * @return mixed
     */
    private function callAction($action, $input)
    {
        sleep(1);
        $data = [
            0 => $input
        ];

        return phive('Licensed')->doLicense('IT', $action, $data);
    }

    /**
     * Verifies the result of an action against the expected response
     *
     * @param UnitTester $I
     * @param $result
     * @param $expected_response
     */
    private function verifyResult(UnitTester $I, $result, $expected_response)
    {
        foreach ($expected_response as $expected_key => $expected_value) {
            $I->assertEquals($expected_response['message'], $result['message'], "Expected response message \"{$expected_response['message']}\", got response \"{$result['message']}\" instead");
            $I->assertEquals($expected_response['code'], $result['code'], "Expected response code \"{$expected_response['code']}\", got response \"{$result['code']}\" instead");
        }
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     * @dataProvider _dataStartGameSessions
     */
    public function testStartGameSessions(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('startGameSessions', $dataprovider['input']);
        $this->verifyResult($I, $result, $dataprovider['response']);

        $this->session_id = $result['response']['session_id'];
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     *
     * @dataProvider _dataAcquisitionParticipationRightMessage
     */
    public function testAcquisitionParticipationRightMessage(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $input = $dataprovider['input'];
        $input['central_system_session_id'] = $this->session_id;
        $result = $this->callAction('acquisitionParticipationRightMessage', $input);
        $this->verifyResult($I, $result, $dataprovider['response']);

        $this->participation_code = $result['response']['participation_code'];
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     * @dataProvider _dataReportedAnomalies
     */
    public function testReportedAnomalies(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('reportedAnomalies', $dataprovider['input']);
        $this->verifyResult($I, $result, $dataprovider['response']);

        $this->request_id = $result['response']['request_id'];
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataSessionReportedAnomalies
     */
    public function testSessionReportedAnomalies(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $input = $dataprovider['input'];
        $input['request_id'] =  $this->request_id;
        $result = $this->callAction('sessionReportedAnomalies', $input);
        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     * @dataProvider _dataSessionEndDateUpdateRequest
     */
    public function testSessionEndDateUpdateRequest(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $input = $dataprovider['input'];
        $input['central_system_session_id'] = $this->session_id;
        $result = $this->callAction('sessionEndDateUpdateRequest', $input);
        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     * @dataProvider _dataRequestFinancialAccounting
     */
    public function testRequestFinancialAccounting(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('requestFinancialAccounting', $dataprovider['input']);
        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     * @dataProvider _dataInstalledSoftwareVersionCommunication
     */
    public function testInstalledSoftwareVersionCommunication(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('installedSoftwareVersionCommunication', $dataprovider['input']);
        $this->verifyResult($I, $result, $dataprovider['response']);
    }


    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @skip
     *
     * @dataProvider _dataAdditionSignatureCertificate
     */
    public function testAdditionSignatureCertificate(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('additionSignatureCertificate', $dataprovider['input']);
        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataGameExecutionCommunication
     */
    public function testGameExecutionCommunication(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $input = $dataprovider['input'];
        $input['session_id'] = $this->session_id;
        $input['game_stages'][0]['players'][0]['identifier'] = $this->participation_code;
        $result = $this->callAction('gameExecutionCommunication', $input);
        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     * @dataProvider _dataGameSessionsAlignmentCommunication
     */
    public function testGameSessionsAlignmentCommunication(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $input = $dataprovider['input'];
        $input['central_system_session_id'] = $this->session_id;
        $result = $this->callAction('gameSessionsAlignmentCommunication', $input);
        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataEndParticipationFinalPlayerBalance
     */
    public function testEndParticipationFinalPlayerBalance(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $input = $dataprovider['input'];
        $input['central_system_session_id'] = $this->session_id;
        $input['participation_id_code'] = $this->participation_code;
        $result = $this->callAction('endParticipationFinalPlayerBalance', $input);
        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     * @dataProvider _dataEndGameSession
     */
    public function testEndGameSession(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $input = $dataprovider['input'];
        $input['central_system_session_id'] = $this->session_id;
        $result = $this->callAction('endGameSession', $input);
        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataAcquisitionParticipationRightMessage()
    {
        return [
            'AcquisitionParticipationRightMessage' => [
                '_label' => 'AcquisitionParticipationRightMessage',
                'input' => [
                    'game_code' => $this->game_code,
                    'game_type' => $this->game_type,
                    'participation_id_code' => '',
                    'progressive_participation_number' => 1,
                    'participation_fee' => 100 * 100,
                    'real_bonus_participation_fee' => 0,
                    'participation_amount_resulting_play_bonus' => 0,
                    'regional_code' => 3,
                    'ip_address' => "192.198.0.1",
                    'code_license_account_holder'  => 15427,
                    'network_code'  => 14,
                    'gambling_account' => '4002',
                    'player_pseudonym' => 'test',
                    'date_participation' => [
                        'date' => [
                            'day' => date('d'),
                            'month' => date('m'),
                            'year' => date('Y'),
                        ],
                        'time' => [
                            'hour' => date('h'),
                            'minutes' => date('i'),
                            'seconds' => date('s')
                        ],
                    ],
                    'initial_stage_progressive_number' => 1,
                    'code_type_tag' => 5
                ],
                'response' => [
                    'code' => '0',
                    'message' => 'Success',
                ],
            ]

        ];
    }

    /**
     * @return array
     */
    public function _dataGameExecutionCommunication()
    {
        return [
            'GameExecutionCommunication' => [
                '_label' => 'GameExecutionCommunication',
                'input' => [
                    'game_code' => $this->game_code,
                    'game_type' => $this->game_type,
                    'initial_progressive_number' => 1,
                    'last_progressive_number' => 1,
                    'stage_date' => date('Ymd'),
                    'flag_closing_day' => 1,
                    'game_stages' => [
                        [
                            'total_taxable_amount' => 0,
                            'stage_progressive_number' => 1,
                            'datetime' => date('Ymdhis'),
                            'players' => [
                                [
                                    'amount_available' => 100 * 100,
                                    'amount_returned' => 0,
                                    'bet_amount' => 0,
                                    'taxable_amount' => 0,
                                    'license_code' => 15427,
                                    'jackpot_amount' => 0,
                                    'amount_available_real_bonuses' => 0,
                                    'amount_available_play_bonuses' => 0,
                                    'amount_waged_real_bonuses' => 0,
                                    'amount_staked_resulting_play_bonuses' => 0,
                                    'amount_returned_real_bonuses' => 0,
                                    'amount_returned_play_bonuses' => 0,
                                    'amount_returned_resulting_jackpots' => 0,
                                    'amount_returned_resulting_additional_jackpots' => 0,
                                ],
                            ],

                        ]
                    ]
                ],
                'response' => [
                    'code' => '0',
                    'message' => 'Success',
                ],
            ]

        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataEndParticipationFinalPlayerBalance()
    {
        return [
            'EndParticipationFinalPlayerBalance' => [
                '_label' => 'EndParticipationFinalPlayerBalance',
                'input' => [
                    'game_code' => $this->game_code,
                    'game_type' => $this->game_type,
                    'number_stage_undertaken_player' => 0,
                    'participation_amount' => 100 * 100,
                    'real_bonus_participation_amount' => 0,
                    'play_bonus_participation_amount' => 0,
                    'amount_staked' => 0,
                    'real_bonus_staked_amount' => 0,
                    'amount_staked_resulting_play_bonus' => 0,
                    'taxable_amount' => 0,
                    'amount_returned_winnings' => 0,
                    'amount_returned_resulting_jackpots' => 0,
                    'amount_returned_resulting_additional_jackpots' => 0,
                    'amount_returned_assigned_as_real_bonus' => 0,
                    'amount_giver_over_play_bonus' => 0,
                    'code_license_account_holder' => 15427,
                    'network_code' => 14,
                    'gambling_account' => '4002',
                    'end_stage_progressive_number' => 1,
                    'date_final_balance' => [
                        'date' => [
                            'day' => date('d'),
                            'month' => date('m'),
                            'year' => date('Y'),
                        ],
                        'time' => [
                            'hour' => date('h'),
                            'minutes' => date('i'),
                            'seconds' => date('s')
                        ],
                    ],
                    'jackpot_fund_amount' => 0,
                ],
                'response' => [
                    'code' => '0',
                    'message' => 'Success',
                ],
            ]

        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataGameSessionsAlignmentCommunication()
    {
        return [
            'GameSessionsAlignmentCommunication' => [
                '_label' => 'GameSessionsAlignmentCommunication',
                'input' => [
                    'game_code' => $this->game_code,
                    'game_type' => $this->game_type,
                    'reference_date' => date('dmY'), //ddmmyyyy
                    'total_number_stages_played' => 1,
                    'number_stages_completed' => 1,
                    'round_up_list' => [
                        [
                            'license_code' => '15427',
                            'total_amounts_waged' => 0,
                            'total_amounts_returned' => 0,
                            'total_taxable_amount' => 0,
                            'total_mount_returned_resulting_jackpot' => 0,
                            'total_mount_returned_resulting_additional_jackpot' => 0,
                            'jackpot_amount' => 0,
                            'total_amount_waged_real_bonuses' => 0,
                            'total_amount_waged_play_bonuses' => 0,
                            'total_amount_returned_real_bonuses' => 0,
                            'total_amount_returned_play_bonuses' => 0,
                        ]
                    ]
                ],
                'response' => [
                    'code' => '0',
                    'message' => 'Success',
                ],
            ]

        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataSessionEndDateUpdateRequest()
    {
        return [
            'SessionEndDateUpdateRequest' => [
                '_label' => 'SessionEndDateUpdateRequest',
                'input' => [
                    'game_code' => 0,
                    'game_type' => 0,
                    'end_date_session' => [
                        'day' => date('d'),
                        'month' => date('m'),
                        'year' => date('Y'),
                    ],
                ],
                'response' => [
                    'code' => '0',
                    'message' => 'Success',
                ],
            ]

        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataEndGameSession()
    {
        return [
            'EndGameSession' => [
                '_label' => 'EndGameSession',
                'input' => [
                    'game_code' => $this->game_code,
                    'game_type' => $this->game_type,
                    'session_end_date' => [
                        'date' => [
                            'day' => date('d'),
                            'month' => date('m'),
                            'year' => date('Y'),
                        ],
                        'time' => [
                            'hour' => date('h'),
                            'minutes' => date('i'),
                            'seconds' => date('s')
                        ],
                    ]
                ],
                'response' => [
                    'code' => '0',
                    'message' => 'Success',
                ],
            ]

        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataStartGameSessions()
    {
        return [
            'StartGameSessions' => [
                '_label' => 'StartGameSessions',
                'input' => [
                    'game_code' => $this->game_code,
                    'game_type' => $this->game_type,
                    'license_session_id' => time(),
                    'start_date_session' => [
                        'date' => [
                            'day' => date('d'),
                            'month' => date('m'),
                            'year' => date('Y'),
                        ],
                        'time' => [
                            'hour' => date('h'),
                            'minutes' => date('i'),
                            'seconds' => date('s')
                        ],
                    ],
                    'end_date_session' => [
                        'day' => date('d'),
                        'month' => date('m'),
                        'year' => date('Y'),
                    ]
                ],
                'response' => [
                    'code' => '0',
                    'message' => 'Success',
                ],
            ]

        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataSessionReportedAnomalies()
    {
        return [
            'SessionReportedAnomalies' => [
                '_label' => 'SessionReportedAnomalies',
                'input' => [
                    'game_code' => $this->game_code,
                    'game_type' => $this->game_type,
                ],
                'response' => [
                    'code' => '2393',
                    'message' => 'Day not yet checked',
                ],
            ]

        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataReportedAnomalies()
    {
        return [
            'ReportedAnomalies' => [
                '_label' => 'ReportedAnomalies',
                'input' => [
                    'game_code' => $this->game_code,
                    'game_type' => $this->game_type,
                    'date_session_opened' => [
                        'day' => date('d'),
                        'month' => date('m'),
                        'year' => date('Y'),
                    ]
                ],
                'response' => [
                    'code' => '0',
                    'message' => 'Success',
                ],
            ]

        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataRequestFinancialAccounting()
    {
        return [
            'RequestFinancialAccounting' => [
                '_label' => 'RequestFinancialAccounting',
                'input' => [
                    'game_code' => 0,
                    'game_type' => 0,
                    'period_start_date' => [
                        'day' => date('d'),
                        'month' => date('m'),
                        'year' => date('Y'),

                    ],
                    'period_end_date' => [
                        'day' => date('d'),
                        'month' => date('m'),
                        'year' => date('Y'),
                    ]
                ],
                'response' => [
                    'code' => '2451',
                    'message' => 'No accounting data present',
                ],
            ]

        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataInstalledSoftwareVersionCommunication()
    {
        return [
            'InstalledSoftwareVersionCommunication' => [
                '_label' => 'InstalledSoftwareVersionCommunication',
                'input' => [
                    'game_code' => 0,
                    'game_type' => 0,
                    'cod_element_type' => 2,
                    'cod_element' => 45014,
                    'prog_cert_version' => 1,
                    'prog_sub_cert_version' => 0,
                    'software_modules'=> [
                        [
                            'name_critical_module' => 'Modulo1',
                            'hash_critical_module' => '1111111111111111111111111111111111111111'
                        ],
                        [
                            'name_critical_module' => 'Modulo2',
                            'hash_critical_module' => '2222222222222222222222222222222222222222'
                        ],
                        [
                            'name_critical_module' => 'Modulo3',
                            'hash_critical_module' => '3333333333333333333333333333333333333333'
                        ],
                        [
                            'name_critical_module' => 'Modulo4',
                            'hash_critical_module' => '4444444444444444444444444444444444444444'
                        ],
                    ]
                ],
                'response' => [
                    'code' => '0',
                    'message' => 'Success',
                ],
            ]

        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataAdditionSignatureCertificate()
    {
        return [
            'AdditionSignatureCertificate' => [
                '_label' => 'AdditionSignatureCertificate',
                'input' => [
                    'game_code' => 0,
                    'game_type' => 0,
                    'certificate_serial_number' => 'idunno',
                    'certificate' => 'idunno',
                ],
                'response' => [
                    'code' => '0',
                    'message' => 'Success',
                ],
            ]

        ];
    }

}
