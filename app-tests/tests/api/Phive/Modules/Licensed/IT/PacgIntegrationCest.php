<?php

/**
 * Class PacgIntegrationCest
 */
class PacgIntegrationCest
{
    /**
     * @var array
     */
    protected $transaction_receipt_ids = [];

    /**
     * Calls an action method in the IT class
     *
     * @param $action
     * @param $input
     * @return mixed
     */
    private function callAction($action, $input)
    {
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
     * NOTE: in order to run this test, the data needs to be reset on the external api.
     *
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @skip
     *
     * @dataProvider _dataProviderOpenAccountNaturalPerson
     */
    public function testOpenAccountNaturalPerson2(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('onOpenAccountNaturalPerson', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderAccountTransactions
     */
    public function testAccountTransactions(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('accountTransactions', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);

        //Set transaction_receipt_id
        $this->transaction_receipt_ids[] = $result['response']['idRicevuta'];

    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderReversalOfAccountTransactions
     */
    public function testReversalOfAccountTransactions(UnitTester $I, \Codeception\Example $dataprovider)
    {
        if (empty($this->transaction_receipt_ids)) {
            $_data_provider_account_transactions = new \Codeception\Example(
                end($this->_dataProviderAccountTransactions())
            );
            $this->testAccountTransactions($I, $_data_provider_account_transactions);

        }
        /**
         * Set transaction_receipt_id
         * We know the first 2 deposits were added to the array, so we can shift the elements off
         * $transaction_receipt_id = array_shift($this->transaction_receipt_ids);
         */
        $input = $dataprovider['input'];
        $input['transaction_receipt_id'] = array_shift($this->transaction_receipt_ids);

        $result = $this->callAction('reversalAccountTransactions', $input);

        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderAccountBonusTransactions
     */
    public function testAccountBonusTransactions(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('bonusAccountTransactions', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderAccountBalance
     */
    public function testAccountBalance(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('accountBalance', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderSubregistration
     */
    public function testSubregistration(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('subregistration', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderUpdateProvinceOfResidence
     */
    public function testUpdateProvinceOfResidence(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('changeAccountProvinceOfResidence', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderUpdateAccountStatus
     */
    public function testUpdateAccountStatus(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('changeAccountStatus', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderQueryAccountStatus
     */
    public function testQueryAccountStatus(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('onAccountStatusQuery', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderUpdateDocumentData
     */
    public function testUpdateDocumentData(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('updatingOwnerIdDocumentDetails', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderSummaryOfServiceOperations
     */
    public function testSummaryOfServiceOperations(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('summaryOfServiceOperations', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderSummaryOfTransactionsOperations
     */
    public function testSummaryOfTransactionsOperations(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('summaryOfTransactionOperations', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderListAccountsWithoutSubregistration
     */
    public function testListAccountsWithoutSubregistration(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('listAccountsWithoutSubRegistration', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderListDormantAccounts
     */
    public function testListDormantAccounts(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('listDormantAccounts', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderUpdateAccountLimits
     */
    public function testUpdateAccountLimits(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('updateAccountLimit', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderQueryAccountLimits
     */
    public function testQueryAccountLimits(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('accountLimitQuery', $dataprovider['input']);

        /**
         * @todo verify that the limits that are returned, are indeed the correct ones
         */
        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProvideListSelfExcludedAccounts
     */
    public function testListSelfExcludedAccounts(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('listSelfExcludedAccounts', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderQuerySelfExcludedSubject
     */
    public function testQuerySelfExcludedSubject(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('querySelfExcludedSubject', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderUpdateAccountPseudonym
     */
    public function testUpdateAccountPseudonym(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('updateAccountPseudonym', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderUpdateAccountEmail
     */
    public function testUpdateAccountEmail(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('updateEmailAccount', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderQuerySelfExcludedSubjectHistory
     */
    public function testQuerySelfExcludedSubjectHistory(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('querySelfExcludedSubjectHistory', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderTraversalSelfExclusionManagement
     */
    public function testQueryTraversalSelfExclusionManagement(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('trasversalSelfExclusionManagement', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderQueryProvinceOfResidence
     */
    public function testQueryProvinceOfResidence(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('onAccountProvinceQuery', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);

        // Verify the correct province is returned
        $I->assertEquals('RM', $result['response']['provincia']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderQueryAccountPseudonym
     */
    public function testQueryAccountPseudonym(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('accountPseudonymQuery', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);

        // Verify the correct pseudonym is returned
        $I->assertEquals($dataprovider['response']['pseudonym'], $result['response']['pseudonimo']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderQueryAccountEmail
     */
    public function testQueryAccountEmail(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('accountEmailQuery', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);

        // Verify the correct email address is returned
        $I->assertEquals($dataprovider['response']['email'], $result['response']['postaElettronica']);
    }

    /**
     * @param UnitTester $I
     * @param $dataprovider
     *
     * @dataProvider _dataProviderOpenAccountLegalEntity
     */
    public function testOpenAccountLegalEntity(UnitTester $I, \Codeception\Example $dataprovider)
    {
        $result = $this->callAction('onOpenAccountLegalEntity', $dataprovider['input']);

        $this->verifyResult($I, $result, $dataprovider['response']);
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderOpenAccountLegalEntity()
    {
        return [
            'open_account' => [
                '_label' => 'open_account',
                'input' => [
                    'account_code'   => 'legal02',
                    'account_holder' => [
                        'vat_number' => '91405930370',
                        'company_name' => 'Videoslots Ltd',
                        'email' => 'info@videoslots.com',
                        'company_headquarter' => [
                            'residential_address'          => 'Via Roma 1',
                            'municipality_of_residence'    => 'Estero',
                            'residential_province_acronym' => 'EE',
                            'residential_post_code'        => '00100',
                            'country'                      => 'Malta'
                        ],
                        'pseudonym' => 'legal02',
                    ],
                ],
                'response' => [
                    // NOTE: this will only return OK if the database has been reset
//                    'code' => '1024',
//                    'message' => 'Outcome ok',
                    'code' => '1201',
                    'message' => 'Existing account', //Existing account
                ],
            ],
            'open_account_same_value' => [
                '_label' => 'open_account_same_value',
                'input' => [
                    'account_code'   => 'legal02',
                    'account_holder' => [
                        'vat_number' => '91405930370',
                        'company_name' => 'Videoslots Ltd',
                        'email' => 'info@videoslots.com',
                        'company_headquarter' => [
                            'residential_address'          => 'Via Roma 1',
                            'municipality_of_residence'    => 'Estero',
                            'residential_province_acronym' => 'EE',
                            'residential_post_code'        => '00100',
                            'country'                      => 'Malta'
                        ],
                        'pseudonym' => 'legal02',
                    ],
                ],
                'response' => [
                    'code' => '1201',
                    'message' => 'Existing account',
                ],
            ],
        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderQueryAccountStatus()
    {
        return [
            'query' => [
                '_label' => 'query',
                'input' => [
                    'account_code' => '40003',
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ]
        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderUpdateAccountStatus()
    {
        return [
            'update_status' => [
                '_label' => 'update_status',
                'input' => [
                    'account_code' => '40003',
                    'status' => '2', //[1 => Open, 2 => Suspended, 3 => Closed, 4 => Dormant, 5 => Blocked]
                    'reason' => '2',
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ],
            'update_status_same_value' => [
                '_label' => 'update_status_same_value',
                'input' => [
                    'account_code' => '40003',
                    'status' => '2', //[1 => Open, 2 => Suspended, 3 => Closed, 4 => Dormant, 5 => Blocked]
                    'reason' => '2',
                ],
                'response' => [
                    'code' => '1211',
                    'message' => 'Account suspension not allowed: account suspended',
                ],
            ],
            'restore_to_default' => [
                '_label' => 'restore_to_default',
                'input' => [
                    'account_code' => '40003',
                    'status' => '1', //[1 => Open, 2 => Suspended, 3 => Closed, 4 => Dormant, 5 => Blocked]
                    'reason' => '2',
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ]
        ];
    }


    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderUpdateProvinceOfResidence()
    {
        return [
            'update_province' => [
                '_label' => 'update_province',
                'input' => [
                    'account_code' => '40003',
                    'province' => 'RO',
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ],
            'update_province_same_value' => [
                '_label' => 'update_province_same_value',
                'input' => [
                    'account_code' => '40003',
                    'province' => 'RO',
                ],
                'response' => [
                    'code' => '1422',
                    'message' => 'Province of residence already existing',
                ],
            ],
            'restore_to_default' => [
                '_label' => 'restore_to_default',
                'input' => [
                    'account_code' => '40003',
                    'province' => 'RM',
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ]
        ];
    }


    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderSubregistration()
    {
        return [
            'query' => [
                '_label' => 'query',
                'input' => [
                    'account_code' => '40003',
                    'balance_amount' => '13000',
                    'balance_bonus_amount' => '4000',
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ]
        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderAccountBalance()
    {
        return [
            'query' => [
                '_label' => 'query',
                'input' => [
                    'account_code' => '40003',
                    'balance_amount' => '13000',
                    'total_bonus_balance_on_account' => '0',
                    'transaction_datetime' => [
                        'date' => [
                            'day' => date('d'),
                            'month' => date('m'),
                            'year' => date('Y')
                        ],
                        'time' => [
                            'hours' => date('H'),
                            'minutes' => date('i'),
                            'seconds' => date('s')
                        ]
                    ],
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ]
        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderAccountBonusTransactions()
    {
        return [
            'bonus_top_up' => [
                '_label' => 'bonus_top_up',
                'input' => [
                    'account_code' => '40003',
                    'payment_method' => '1',
                    'total_bonus_balance_on_account' => '3000',
                    'bonus_balance_amount' => '3000',
                    'balance_amount' => '8000',
                    'transaction_reason' => '5', //Bonus
                    'transaction_amount' => '3000',
                    'transaction_datetime' => [
                        'date' => [
                            'day' => date('d'),
                            'month' => date('m'),
                            'year' => date('Y')
                        ],
                        'time' => [
                            'hours' => date('H'),
                            'minutes' => date('i'),
                            'seconds' => date('s')
                        ]
                    ],
                    'bonus_details' => [
                        [
                            'gaming_family' => '6',
                            'gaming_type' => '2',
                            'bonus_amount' => '3000',
                        ],
                    ],
                    'bonus_balance_details' => [
                        [
                            'gaming_family' => '6',
                            'gaming_type' => '2',
                            'bonus_amount' => '3000',
                        ],
                    ],
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ],
            'bonus_reversal' => [
                '_label' => 'bonus_reversal',
                'input' => [
                    'account_code' => '40003',
                    'payment_method' => '1',
                    'total_bonus_balance_on_account' => '3000',
                    'bonus_balance_amount' => '3000',
                    'balance_amount' => '9000',
                    'transaction_reason' => '6', //Bonus reversal
                    'transaction_amount' => '3000',
                    'transaction_datetime' => [
                        'date' => [
                            'day' => date('d'),
                            'month' => date('m'),
                            'year' => date('Y')
                        ],
                        'time' => [
                            'hours' => date('H'),
                            'minutes' => date('i'),
                            'seconds' => date('s')
                        ]
                    ],
                    'bonus_balance_details' => [
                        [
                            'gaming_family' => '6',
                            'gaming_type' => '2',
                            'bonus_amount' => '3000',
                        ],
                    ],
                    'bonus_details' => [
                        [
                            'gaming_family' => '6',
                            'gaming_type' => '2',
                            'bonus_amount' => '3000',
                        ],
                    ],
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ],
        ];
    }


    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderReversalOfAccountTransactions()
    {
        return [
            'top_up_credit_card_reversal' => [
                '_label' => 'top_up_credit_card_reversal',
                'input' => [
                    'account_code' => '40003',
                    'transaction_receipt_id' => '',  // we get this from previous topup
                    'account_sales_network_id' => '14',
                    'account_network_id' => '15427',
                    'payment_method' => '2',    // Credit card
                    'transaction_description' => '2', //Top Up Reversal
                    'reversal_type' => '1',
                    'transaction_amount' => '5000',   // Previous balance was 15000, so new balance will be 10000
                    'balance_amount' => '10000',
                    'balance_bonus_amount' => '0',
                    'datetime' => [
                        'date' => [
                            'day' => date('d'),
                            'month' => date('m'),
                            'year' => date('Y')
                        ],
                        'time' => [
                            'hours' => date('H'),
                            'minutes' => date('i'),
                            'seconds' => date('s')
                        ]
                    ],
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ],
            'top_up_pre_paid_reversal' => [
                '_label' => 'top_up_pre_paid_reversal',
                'input' => [
                    'account_code' => '40003',
                    'transaction_receipt_id' => '',  // we get this from previous topup
                    'account_sales_network_id' => '14',
                    'account_network_id' => '15427',
                    'payment_method' => '2',    // Credit card
                    'transaction_description' => '2', //Top Up Reversal
                    'reversal_type' => '1',
                    'transaction_amount' => '10000',   // Previous balance was 10000, so new balance will be 0
                    'balance_amount' => '0',
                    'balance_bonus_amount' => '0',
                    'datetime' => [
                        'date' => [
                            'day' => date('d'),
                            'month' => date('m'),
                            'year' => date('Y')
                        ],
                        'time' => [
                            'hours' => date('H'),
                            'minutes' => date('i'),
                            'seconds' => date('s')
                        ]
                    ],
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ],
        ];
    }


    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderAccountTransactions()
    {
        return [
            'top_up_credit_card' => [
                '_label' => 'top_up_credit_card',
                'input' => [
                    'account_code' => '40003',
                    'account_sales_network_id' => '14',
                    'account_network_id' => '15427',
                    'transaction_reason' => '1', // Deposit / Top Up
                    'transaction_amount' => '5000',     // first deposit, so balance after this would be 5000
                    'balance_amount' => '5000',
                    'total_bonus_balance_on_account' => '0',
                    'payment_method' => '2',  // credit_card
                    'transaction_datetime' => [
                        'date' => [
                            'day' => date('d'),
                            'month' => date('m'),
                            'year' => date('Y')
                        ],
                        'time' => [
                            'hours' => date('H'),
                            'minutes' => date('i'),
                            'seconds' => date('s')
                        ]
                    ]
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok'
                ],
            ],
            'top_up_pre_paid' => [
                '_label' => 'top_up_pre_paid',
                'input' => [
                    'account_code' => '40003',
                    'account_sales_network_id' => '14',
                    'account_network_id' => '15427',
                    'transaction_reason' => '1', // Deposit / Top Up
                    'transaction_amount' => '10000',     // second deposit, so balance after this would be 15000
                    'balance_amount' => '15000',
                    'total_bonus_balance_on_account' => '0',
                    'payment_method' => '3',  // Debit card
                    'transaction_datetime' => [
                        'date' => [
                            'day' => date('d'),
                            'month' => date('m'),
                            'year' => date('Y')
                        ],
                        'time' => [
                            'hours' => date('H'),
                            'minutes' => date('i'),
                            'seconds' => date('s')
                        ]
                    ]
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok'
                ],
            ]
        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderQueryAccountEmail()
    {
        return [
            'query' => [
                '_label' => 'query',
                'input' => [
                    'account_code' => '40003',
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                    'email' => 'devtestit40003@devtest.com'
                ],
            ]
        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderQueryAccountPseudonym()
    {
        return [
            'query' => [
                '_label' => 'query',
                'input' => [
                    'account_code' => '40003',
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                    'pseudonym' => 'devtestit40003'
                ],
            ]
        ];
    }


    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderQueryProvinceOfResidence()
    {
        return [
            'query' => [
                '_label' => 'query',
                'input' => [
                    'account_code' => '40003',
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ]
        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderTraversalSelfExclusionManagement()
    {
        return [
            'self_exclusion' => [
                '_label' => 'self_exclusion',
                'input' => [
                    'tax_code' => 'VRDFNC65P49H501Z',
                    'self_exclusion_management' => '1', //1 for a self-exclusion and 2 for a reactivation
                    'self_exclusion_type' => '1',
                ],
                'response' => [
                    // NOTE: this will only return OK if the database has been reset
//                    'code' => '1024',
//                    'message' => 'Outcome ok',
                    'code' => '1297',
                    'message' => 'Self-exclusion not allowed: self-exclusion already in force',
                ],
            ],
            'already_self_excluded' => [
                '_label' => 'already_self_excluded',
                'input' => [
                    'tax_code' => 'VRDFNC65P49H501Z',
                    'self_exclusion_management' => '1', //1 for a self-exclusion and 2 for a reactivation
                    'self_exclusion_type' => '1',
                ],
                'response' => [
                    'code' => '1297',
                    'message' => 'Self-exclusion not allowed: self-exclusion already in force',
                ],
            ],
            're_activation' => [
                '_label' => 're_activation',
                'input' => [
                    'tax_code' => 'VRDFNC65P49H501Z',
                    'self_exclusion_management' => '2', //1 for a self-exclusion and 2 for a reactivation
                    'self_exclusion_type' => '0',
                ],
                'response' => [
                    'code' => '1296',
                    'message' => 'Reactivation not allowed: self-exclusion for less than six months',
                ],
            ]
        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderQuerySelfExcludedSubjectHistory()
    {
        return [
            'never_excluded' => [
                '_label' => 'never_excluded',
                'input' => [
                    'tax_code' => 'MRCFNZ85P17F205C',
                ],
                'response' => [
                    'code' => '1416',
                    'message' => 'The subject has never been self-excluded',
                ],
            ],
            'has_been_excluded' => [
                '_label' => 'has_been_excluded',
                'input' => [
                    'tax_code' => 'AAADDD75L17H501Q',
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ]
        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderUpdateAccountEmail()
    {
        return [
            'update' => [
                '_label' => 'update',
                'input' => [
                    'account_code' => '40003',
                    'email' => 'devtestit40003updated@devtest.com',
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ],
            'update_same_value' => [
                '_label' => 'update_same_value',
                'input' => [
                    'account_code' => '40003',
                    'email' => 'devtestit40003updated@devtest.com',
                ],
                'response' => [
                    'code' => '1412',
                    'message' => 'E-mail address the same as previous one',
                ],
            ],
            'restore_default' => [
                '_label' => 'restore_default',
                'input' => [
                    'account_code' => '40003',
                    'email' => 'devtestit40003@devtest.com',
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ]
        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderUpdateAccountPseudonym()
    {
        return [
            'update' => [
                '_label' => 'update',
                'input' => [
                    'account_code' => '40003',
                    'pseudonym' => 'Pseudonym Test',
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ],
            'update_same_value' => [
                '_label' => 'update_same_value',
                'input' => [
                    'account_code' => '40003',
                    'pseudonym' => 'Pseudonym Test',
                ],
                'response' => [
                    'code' => '1408',
                    'message' => 'Pseudonym the same as the previous one',
                ],
            ],
            'restore_default' => [
                '_label' => 'restore_default',
                'input' => [
                    'account_code' => '40003',
                    'pseudonym' => 'devtestit40003',
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ]
        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderQuerySelfExcludedSubject()
    {
        return [
            'self_excluded' => [
                '_label' => 'self_excluded',
                'input' => [
                    'tax_code' => 'AAABBB75L17H501Q',
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
//                    'self_exclusion_type' => '1',
//                    'start_date' => '2018-09-04 12:13:09'
                ],
            ],
            'not_self_excluded' => [
                '_label' => 'not_self_excluded',
                'input' => [
                    'tax_code' => 'DCRMRA60R10H501E',
                ],
                'response' => [
                    'code' => '1404',
                    'message' => 'The subject is not self-excluded',
                ],
            ]
        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataProvideListSelfExcludedAccounts()
    {
        return [
            'default' => [
                '_label' => 'default',
                'input' => [
                    'start' => 1,
                    'end' => 100,
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ]
        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderQueryAccountLimits()
    {
        return [
            'query' => [
                '_label' => 'query',
                'input' => [
                    'account_code' => '40003',
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ]
        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderUpdateAccountLimits()
    {
        return [
            'update_existing_limit' => [
                '_label' => 'update_existing_limit',
                'input' => [
                    'account_code' => '40003',
                    'limit_management' => 1,
                    'limit' => [
                        'limit_type' => 3,
                        'amount' => 10000,
                    ],
                ],
                'response' => [
                    // NOTE: this will only return OK if the database has been reset
//                    'code' => '1024',
//                    'message' => 'Outcome ok',
                    'code' => '1290',
                    'message' => 'Account limit cannot be set: limit amount the same as previous limit',
                ],
            ],
            'new_limit' => [
                '_label' => 'new_limit',
                'input' => [
                    'account_code' => '40003',
                    'limit_management' => 1,
                    'limit' => [
                        'limit_type' => 1,
                        'amount' => 10000,
                    ],
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ],
            'new_limit_same_value' => [
                '_label' => 'new_limit_same_value',
                'input' => [
                    'account_code' => '40003',
                    'limit_management' => 1,
                    'limit' => [
                        'limit_type' => 1,
                        'amount' => 10000,
                    ],
                ],
                'response' => [
                    'code' => '1290',
                    'message' => 'Account limit cannot be set: limit amount the same as previous limit',
                ],
            ],
            'remove_new_limit' => [
                '_label' => 'remove_new_limit',
                'input' => [
                    'account_code' => '40003',
                    'limit_management' => 2,
                    'limit' => [
                        'limit_type' => 1,
                        'amount' => 0,
                    ],
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ],
            'remove_new_limit_again' => [
                '_label' => 'remove_new_limit_again',
                'input' => [
                    'account_code' => '40003',
                    'limit_management' => 2,
                    'limit' => [
                        'limit_type' => 1,
                        'amount' => 0,
                    ],
                ],
                'response' => [
                    'code' => '1402',
                    'message' => 'Limit removal not allowed: limit type not present',
                ],
            ],
            'reset_to_default' => [  // use the limits that were send when creating the account
                '_label' => 'reset_to_default',
                'input' => [
                    'account_code' => '40003',
                    'limit_management' => 1,
                    'limit' => [
                        'limit_type' => 3,
                        'amount' => 20000,
                    ],
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ]
        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderListDormantAccounts()
    {
        return [
            'default' => [
                '_label' => 'default',
                'input' => [
                    'date_request' => [
                        'day' => '01',
                        'month' => '04',
                        'year' => '2020'
                    ],
                    'start' => 1,
                    'end' => 100,
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ],
            'page_2' => [
                '_label' => 'page_2',
                'input' => [
                    'date_request' => [
                        'day' => '01',
                        'month' => '04',
                        'year' => '2020'
                    ],
                    'start' => 101,
                    'end' => 200,
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ]
        ];
    }



    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderListAccountsWithoutSubregistration()
    {
        return [
            'open' => [
                '_label' => 'open',
                'input' => [
                    'date_request' => [
                        'day' => '01',
                        'month' => '04',
                        'year' => '2020'
                    ],
                    'status' => 1,// [1 => (OPEN), 2 => (SUSPENDED), 3 => (CLOSED)]
                    'start' => 1,
                    'end' => 100,
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ],
            'suspended' => [
                '_label' => 'suspended',
                'input' => [
                    'date_request' => [
                        'day' => '01',
                        'month' => '04',
                        'year' => '2020'
                    ],
                    'status' => 2,// [1 => (OPEN), 2 => (SUSPENDED), 3 => (CLOSED)]
                    'start' => 1,
                    'end' => 100,
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ],
            'closed' => [
                '_label' => 'closed',
                'input' => [
                    'date_request' => [
                        'day' => '01',
                        'month' => '04',
                        'year' => '2020'
                    ],
                    'status' => 3,// [1 => (OPEN), 2 => (SUSPENDED), 3 => (CLOSED)]
                    'start' => 1,
                    'end' => 100,
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ]
        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderSummaryOfTransactionsOperations()
    {
        return [
            'summery' => [
                '_label' => 'summery',
                'input' => [
                    'date'=> [
                        'day' => '10',
                        'month' => '06',
                        'year' => '2020'
                    ],
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ]
        ];
    }


    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderSummaryOfServiceOperations()
    {
        return [
            'summery' => [
                '_label' => 'summery',
                'input' => [
                    'date'=> [
                        'day' => '10',
                        'month' => '06',
                        'year' => '2020'
                    ],
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ]
        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderUpdateDocumentData()
    {
        return [
            'new_document' => [
                '_label' => 'new_document',
                'input' => [
                    'account_code' => '40003',
                    'document' => [
                        'document_type' => 3,
                        'date_of_issue'=> [
                            'day' => '01',
                            'month' => '04',
                            'year' => '2020'
                        ],
                        'document_number' => 'YA0333567',
                        'issuing_authority' => 'Ministro Affari Esteri',
                        'where_issued' => 'Roma',
                    ],
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ]

        ];
    }

    /**
     * Data provider.
     * @return array
     */
    public function _dataProviderOpenAccountNaturalPerson()
    {
        return [
            'invalid_data' => [
                '_label' => 'invalid_data',
                'input' => [
                    'account_code'   => '40003',
                    'account_holder' => [
                        'tax_code'      => 1234,
                        'surname'       => "Petri",
                        'name'          => "Aldo",
                        'gender'        => 'M',
                        'email'         => "not__valid__email",
                        'pseudonym'     => "devtestit002",
                        'birth_data' => [
                            'date_of_birth' => [
                                'day'   => '123',
                                'month' => '999',
                                'year'  => 'year'
                            ],
                            'birthplace'                  => 'Roma',
                            'birthplace_province_acronym' => 'ABC',
                        ],
                        'residence' => [
                            'residential_address'          => "21 Pizza Street",
                            'municipality_of_residence'    => 'Roma',
                            'residential_province_acronym' => 'Rm',
                            'residential_post_code'        => '1234',
                            'country'                      => 'Italy',
                        ],
                        'document'                     => [
                            'document_type'     => 3,
                            'date_of_issue'     => [
                                'day'   => date('d', strtotime('2018-02-01')),
                                'month' => date('m', strtotime('2018-02-01')),
                                'year'  => date('Y', strtotime('2018-02-01')),
                            ],
                            'document_number'   => 'YA0101456',
                            'issuing_authority' => 'Ministro Affari Esteri',
                            'where_issued'      => 'Roma',
                        ],
                    ],
                    'number_of_limits' => 2,
                    'limits' => [

                        [
                            'limit_type' => 't',
                            'amount'     => 'fdhud',
                        ]
                    ]
                ],
                'response' => [
                    'code' => '500',
                    'message' => 'tax_code : max - The Tax code maximum is 16
country : required - The Country is required
birthplace_province_acronym : max - The Birthplace province acronym maximum is 2
day : date - The Day is not valid date format
month : date - The Month is not valid date format
year : date - The Year is not valid date format
residential_post_code : min - The Residential post code minimum is 5
document_type : document_type - 99 is not a valid document type to be send to the Italian Regulator
limit_type : limit_type - t is not a valid limit type to be send to the Italian Regulator
amount : integer - The Amount must be integer',
                ],
            ],
            'new_account' => [
                '_label' => 'new_account',
                'input' => [
                    'account_code'   => '40003',
                    'account_holder' => [
                        'tax_code'      => 'DCRMRA60R10H501E',
                        'surname'       => "MARIO",
                        'name'          => "DI CARLO",
                        'gender'        => 'M',
                        'email'         => "devtestit40003@devtest.com",
                        'pseudonym'     => "devtestit40003",
                        'birth_data' => [
                            'date_of_birth' => [
                                'day'   => '10',
                                'month' => '10',
                                'year'  => '1960'
                            ],
                            'birthplace'                  => 'Roma',
                            'birthplace_province_acronym' => 'RM',
                        ],
                        'residence' => [
                            'residential_address'          => "21 Pizza Street",
                            'municipality_of_residence'    => 'Roma',
                            'residential_province_acronym' => 'RM',
                            'residential_post_code'        => '12345',
                            'country'                      => 'Italy',
                        ],
                        'document' => [
                            'document_type'     => 3,
                            'date_of_issue'     => [
                                'day'   => date('d', strtotime('2018-02-01')),
                                'month' => date('m', strtotime('2018-02-01')),
                                'year'  => date('Y', strtotime('2018-02-01')),
                            ],
                            'document_number'   => 'YA0202567',
                            'issuing_authority' => 'Ministro Affari Esteri',
                            'where_issued'      => 'Roma',
                        ],
                    ],
                    'number_of_limits' => 1,
                    'limits' => [
                        [
                            'limit_type' => 3,
                            'amount' => 20000,
                        ]
                    ]
                ],
                'response' => [
                    'code' => '1024',
                    'message' => 'Outcome ok',
                ],
            ]
        ];
    }
}
