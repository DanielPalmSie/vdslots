<?php
require_once 'TestGp.php';

class TestPlaytech extends TestGp
{
    const PROVIDER = 'Playtech';
    const PREFIX = 'playtech_';

    public function __construct()
    {
        $this->injectDependency(phive(self::PROVIDER))
            ->injectDependency(phive('UserHandler'));

        $this->initScenarios();
    }

    /**
     * This method stores the configuration for the test module and test scenarios for the test methods it has.
     *
     * @return void|mixed
     */
    public function initScenarios()
    {
        $this->test_settings = $this->_m_oGp->getSetting('test_settings', [
            's_url' => phive()->getSiteUrl() . "/diamondbet/soap/playtech.php",
            'b_output' => true,
            's_currency' => 'EUR',
            'i_bet_amount' => 100,
            'i_win_amount' => 5000,
        ]);

        $this->test_data = $this->_m_oGp->getSetting('test_data', [
            'testConfirmedWins' => [
                "bet" => [
                    'data' => [
                        [
                            [
                                'command' => 'bet',
                                'parameters' => array(
                                    'roundid' => null,
                                    'amount' => $this->test_settings['i_bet_amount'],  // $iBetAmount,
                                    'internalFundChanges' => -10,
                                    'remoteBonusCode' => 2, //bonus_entry id
                                )
                            ]
                        ]
                    ],
                    'key' => 'bets',
                    'successful' => true,
                ],
                "win" => [
                    'data' => [
                        [
                            [
                                'command' => 'gameroundresult',
                                'parameters' => [
                                    'amount' => $this->test_settings['i_win_amount'],
                                    'transactionCode' => '1543981424',   //5afe894d570c3',
                                    'roundid' => null,
//                                    'transactionType' => 'REFUND',
                                    'transactionType' => 'WIN',
                                    'parentgameroundcode' => '1543981424'
                                ]
                            ]
                        ]
                    ],
                    'key' => 'wins',
                    'successful' => false,
                ],
                "bet_win" => [
                    'data' => [
                        [
                            [
                                'command' => 'bet',
                                'parameters' => array(
                                    'roundid' => null,
                                    'amount' => $this->test_settings['i_bet_amount'],  // $iBetAmount,
                                    'internalFundChanges' => -10,
                                    'remoteBonusCode' => 2, //bonus_entry id
                                )
                            ]
                        ],
                        [
                            [
                                'command' => 'gameroundresult',
                                'parameters' => [
                                    'amount' => $this->test_settings['i_win_amount'],
                                    'transactionCode' => '',
                                    'roundid' => null,
                                    'transactionType' => 'WIN',
                                    'parentgameroundcode' => ''
                                ]
                            ]
                        ]
                    ],
                    'key' => 'bets_wins',
                    'successful' => true,
                ],
            ],
        ]);
    }

    /**
     * Execute the command passed and outputs the response from the url that is called by the post.
     * Optionally output what is send to the url upfront.
     *
     * @param array $p_aAction
     *
     * @return mixed Depends on the response of the requested url
     */
    public function exec($p_aAction)
    {
        (empty($this->_m_iUserId) ? die('Please set the user ID using setUserId()') : $this->_m_iUserId);
        (empty($this->_m_mGameId) ? die('Please set the game ID using setGameId()') : $this->_m_mGameId);

        foreach ($p_aAction as $key => $aAction) {
            $this->_m_sGpMethod = $aAction['command'];
            $this->_m_sMethod = $this->_m_oGp->getWalletMethodByGpMethod($aAction['command']);
            $aParameters = (isset($aAction['parameters']) ? $this->_urlParams($aAction['parameters']) : $this->_urlParams());
        }

        return $this->_post($aParameters);
    }

    /**
     * Get array with data for reconsile or bet and win in 1 request reconOrBetWin method is used to settle bet + win.
     * which should then internally validate the transaction and return the updated state of the balance.
     *
     * @param array $p_aParameters Array with parameter. If empty default params will be used.
     *
     * @return array
     */
    private function _urlParams(array $p_aParameters = array())
    {
        $sToken = $this->_m_oGp->getGuidv4();
        $this->_m_oGp->toSession($sToken, $this->_m_iUserId, $this->_m_mGameId);

        $aAddParams = array(
            'externalToken' => $sToken,
            'username' => $this->_m_iUserId,
            'requestId' => (isset($p_aParameters['requestid']) ? $p_aParameters['requestid'] : $this->_getHash())
        );

        if (!in_array($this->_m_sMethod, array('_balance', '_init', '_createToken', '_realityCheck'))) {
            $aAddParams['gameCodeName'] = $this->_m_mGameId;
            $aAddParams['gameRoundCode'] = (isset($p_aParameters['roundid']) ? $p_aParameters['roundid'] : $this->_m_oGp->randomNumber(10));
        }

        switch ($this->_m_sMethod) {

            case '_balance':
                break;

            case '_init':
                break;

            case '_bet':

                $fundChange = array(
                    'type' => 'REAL',
                    'amount' => (isset($p_aParameters['internalFundChanges']) ? $p_aParameters['internalFundChanges'] : '0.00')
                );

                $betDetails = array(
                    'tableCoverage' => '0'
                );

                $aAddParams['transactionCode'] = (isset($p_aParameters['transactionCode']) ? $p_aParameters['transactionCode'] : $this->_getHash());
                $aAddParams['amount'] = $this->_m_oGp->convertFromToCoinage($p_aParameters['amount'], Gp::COINAGE_CENTS, Gp::COINAGE_UNITS);
                $aAddParams['transactionDate'] = '2017-05-15 08:00:00';

                $aAddParams['internalFundChanges'] = $fundChange;
                $aAddParams['betDetails'] = $betDetails;

                if (!empty($p_aParameters['remoteBonusCode'])) {
                    $aAddParams['remoteBonusCode'] = $p_aParameters['remoteBonusCode'];
                }

                break;

//             case '_cancel':
//                 $aAddParams['RefTransactionId'] = (isset($p_aParameters['transactionid']) ? $p_aParameters['transactionid'] : $this->_getHash());
//                 $aAddParams['Amount'] = $this->_m_oGp->convertFromToCoinage($p_aParameters['amount'],Gp::COINAGE_CENTS, Gp::COINAGE_UNITS);
//                 $aAddParams['CancelEntireRound'] = 'true';
//                 break;

            case '_end':
                $aAddParams['roundCompleted'] = 'true';
                break;

            case '_createToken':
                $aAddParams['GameCode'] = $this->_m_mGameId;
                break;

            case '_createFreespin':
                $aAddParams['templateCode'] = 49730; // to be set
                $aAddParams['count'] = 5;
                $aAddParams['transactionCode'] = 123;  // to be set according
                $aAddParams['transactionDate'] = '2017-05-15 08:00:00';  // to be set according
                break;

            case '_gameroundresult':
                if (isset($p_aParameters['transactionType'])) {
                    $aAddParams['pay'] = array(
                        'transactionCode' => (isset($p_aParameters['transactionCode']) ? $p_aParameters['transactionCode'] : $this->_getHash()),
                        'transactionDate' => '2017-05-15 08:00:00',
                        'amount' => $this->_m_oGp->convertFromToCoinage($p_aParameters['amount'], Gp::COINAGE_CENTS, Gp::COINAGE_UNITS),
                        'type' => (isset($p_aParameters['transactionType']) ? $p_aParameters['transactionType'] : ""),
                        'internalFundChanges' => array(),

                    );
                }

                if ($p_aParameters['transactionType'] != 'REFUND' && isset($p_aParameters['parentgameroundcode'])) {
                    $aAddParams['parentGameRoundCode'] = (isset($p_aParameters['parentgameroundcode']) ? $p_aParameters['parentgameroundcode'] : $this->_getHash());
                }

                if (isset($p_aParameters['relatedTransactionCode'])) {
                    $aAddParams['pay']['relatedTransactionCode'] = $p_aParameters['relatedTransactionCode'];
                }




                $aAddParams['jackpot'] = array(
                    'contributionAmount' => '0.00',
                    'winAmount' => '0.00'
                );

                $aAddParams['gameRoundClose'] = array(
                    'date' => '2017-05-15 08:00:00'
                );


                break;

            case '_realityCheck':
                $aAddParams['dialogId'] = 49730;
                $aAddParams['realityCheckChoice'] = (isset($p_aParameters['realitycheckchoice']) ? $p_aParameters['realitycheckchoice'] : 'CONTINUE');
                break;

        }

        return $aAddParams;
    }

    /**
     * Post the data in JSON format
     *
     * @param array $p_aData An array with data to post.
     * @see outputRequest()
     * @return mixed Outputs the response from the url that is called by the post and optionally can output what is
     *               send to the url upfront.
     */

    protected function _post($p_aData)
    {
        $sValue = json_encode($p_aData);
        $this->_m_sUrl .= '/' . $this->_m_sGpMethod;

        if ($this->_m_bOutput === true) {
            $this->printInputData('URL:' . PHP_EOL . $this->_m_sUrl . PHP_EOL . "DATA:" . PHP_EOL . $sValue . PHP_EOL, true);
        }

        return phive()->post($this->_m_sUrl, $sValue, 'application/json', '', $this->_m_oGp->getGpName() . '-out',
                'POST') . PHP_EOL;

    }

    /**
     * Test if the providers correctly processes bet - win scenarios
     *
     * @param null $test_case_type_param
     * @return void
     */
    public function testConfirmedWins($test_case_type_param = null)
    {
        $testing_data = $this->test_data[__FUNCTION__];
        $test_settings = $this->test_settings;

        $player = $this->getTestPlayer("AL");
        $game = $this->getTestGame(self::PREFIX . 'cool_game', self::PROVIDER);
        $game_id = str_replace(self::PREFIX, "", $game['game_id']);

        $this
            ->setGameId($game_id)
            ->forceSecureToken(false)
            ->setUserId($player->getId())
            ->setUrl($test_settings['s_url'])
            ->setUserCurrency($player->getCurrency())
            ->outputRequest($test_settings['b_output']);

        foreach ($testing_data as $test_case_type => $test_case) {
            if ($test_case_type_param !== null && $test_case_type_param !== $test_case_type) {
                continue;
            }

            echo PHP_EOL . PHP_EOL . "Test Case: " . strtoupper($test_case_type);

            $old_balance = (int)cu($player->getId())->getBalance();

            $responses = [];

            $randid = $this->randId();
            $transactionCode = $this->randId();

            foreach ($test_case['data'] as $test) {
                $test[0]['parameters']['roundid'] = $randid;
                $test[0]['parameters']['transactionCode'] = $transactionCode;
                $test[0]['parameters']['parentgameroundcode'] = $randid;

                $response = $this->execWallet($test);

                $this->printOutputData($response, true);

                $action = $test[0]['command'];
                $responses[$action]["mg_id"] = self::PREFIX . $randid;
                $responses[$action]["amount"] = $test[0]['parameters']['amount'];


            }

            $bet_mg_id = (!empty($responses['bet']["mg_id"]) ? $responses['bet']["mg_id"] : "");
            $win_mg_id = (!empty($responses['gameroundresult']["mg_id"]) ? $responses['gameroundresult']["mg_id"] : "");

            $bet_wins_result = $this->checkBetsWins(
                    $test_case['key'],
                    $bet_mg_id,
                    $win_mg_id,
                    $player->getId()
                ) === $test_case['successful'];

            $rounds_table_insertion_result = $this->checkRounds(
                    $test_case['key'],
                    $bet_mg_id,
                    $win_mg_id,
                    $player->getId(),
                    $bet_mg_id ?? $win_mg_id
                ) === $test_case['successful'];

            $this->msg(($bet_wins_result && $rounds_table_insertion_result), "Database insertion test failed", "Database insertion test passed", false, true);

            $new_balance = (int)cu($player->getId())->getBalance();
            $bet_amount = (!empty($responses['bet']["amount"]) ? $responses['bet']["amount"] : 0);
            $win_amount = (!empty($responses['gameroundresult']["amount"]) ? $responses['gameroundresult']["amount"] : 0);

            $balance_result = TestGp::didBalanceTransactionOccurred(
                    $old_balance,
                    $new_balance,
                    (int)$bet_amount,
                    (int)$win_amount
                ) === $test_case['successful'];

            $this->msg($balance_result, "Balance test failed", "Balance test passed");

            $result = false;
            if ($bet_wins_result && $balance_result) {
                $result = true;
            }

            $this->msg($result);
        }

        phive('SQL')->shs()->query("DELETE FROM micro_games WHERE ext_game_name = '{$this->game['ext_game_name']}'");
        $this->cleanupTestPlayer($player->getId(), ["bets", "wins", "rounds","deposits", "cash_transactions"]);
    }
}