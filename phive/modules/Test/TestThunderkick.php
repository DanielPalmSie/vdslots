<?php

use Opis\Closure\SerializableClosure;

require_once 'TestGp.php';

class TestThunderkick extends TestGp
{

    const EUR_CURRENCY = 'EUR';
    const SLEEP_TIME = 100000;

    /**
     * @var string
     */
    public string $caller_id = 'testmerchant';

    /**
     * @var string
     */
    public string $caller_pwd = '';

    /**
     * @var Closure
     */
    private Closure $session_generator;

    private Closure $win_generator;

    private array $bet = [
        'command' => '_bet',
        'parameters' => [
            'amountBet' => 10,
            "session_id" => ""
        ]
    ];

    private array $win = [
        'command' => '_win',
        'parameters' => [
            'amountWin' => 120,
            /**
             * @todo if using true for freespin make sure that bonus_entries rewards is not 0 when generate a win.
             * 'freespin' => true,// or array('frb_id' => $frb_id, 'frb_lines' => $frb_lines, 'frb_bet' => $frb_bet),
             * 'transaction_id' => '5804a02a11f08',
             */
            "session_id" => ""
        ]
    ];

    private int $number_of_spins;

    function rawPost($url, $arr)
    {
        $tk = phive('Thunderkick');
        $json = json_encode($arr);
        return phive()->post($url, $json, $tk->getHttpContentType(),
            "Authorization: Basic " . base64_encode($tk->getSetting('vs_username') . ':' . $tk->getSetting('vs_passwd')) . "\r\n",
            $tk->getGamePrefix() . 'out', 'POST');
    }
    
    
    /**
     * Execute the command passed and outputs the response from the url that is called by the post.
     * Optionally output what is send to the url upfront.
     *
     * @param array $p_aAction
     * @return mixed Depends on the response of the requested url
     */
    public function exec($p_aAction)
    {
        
        (empty($this->_m_iUserId) ? die('Please set the user ID using setUserId()') : $this->_m_iUserId);
        (empty($this->_m_mGameId) ? die('Please set the game ID using setGameId()') : $this->_m_mGameId);
        
        foreach ($p_aAction as $key => $aAction) {
            
            $this->_m_sMethod = $aAction['command'];
            
            $aParameters[] = (isset($aAction['parameters']) ? $this->_jsonParams($aAction['parameters']) : $this->_jsonParams());
            
        }
        
        if (count($aParameters) > 1) {
            $aParameters = array_merge($aParameters[0], $aParameters[1]);
        } else {
            $aParameters = $aParameters[0];
        }
        
        return $this->_post($aParameters);
    }

    public function getTournamentDetails(int $tournament_id): array
    {
        $sql = <<<EOS
SELECT
    game_ref, cost, xspin_info, min_bet, max_bet, max_players
FROM tournaments
WHERE
    id = {$tournament_id}
    AND status IN ('open', 'late.registration')
LIMIT 1
EOS;
        $tournament = $this->_m_oSql->loadArray($sql);

        return $tournament[0] ?? [];
    }

    public function testBattleOfSlots(
        int $user_id,
        string $tournament_entry_id,
        string $game_id,
        string $session_generator,
        string $win_generator,
        int $bet_amount,
        int $number_of_spins,
        ?string $caller_id = null,
        ?string $caller_password = null,
        ?string $testing_server_url = null
    ) {
        $thunderkick = TestPhive::getModule("Thunderkick");
        /* @var SerializableClosure */
        $sg = unserialize(base64_decode($session_generator));
        $wg = unserialize(base64_decode($win_generator));

        $thunderkick->injectDependency(phive('UserHandler'));
        $thunderkick->injectDependency(phive('Thunderkick'));
        $thunderkick->setUserId($tournament_entry_id);
        $this->_m_sUserCurrency = $this::EUR_CURRENCY;
        $thunderkick->setSessionGenerator($sg->getClosure());
        $thunderkick->setWinGenerator($wg->getClosure());
        $thunderkick->setBet(['amountBet' => $bet_amount]);
        $thunderkick->setNumberOfSpins($number_of_spins);
        $thunderkick->setCallerId($caller_id);
        $thunderkick->setCallerPassword($caller_password);
        $thunderkick->setUrl($testing_server_url);
        $thunderkick->setGameId($game_id);
        $thunderkick->outputRequest(true);

        $this->playGame($thunderkick, $user_id);
    }

    public function setSessionGenerator(Closure $generator): void
    {
        $this->session_generator = $generator;
    }

    public function setWinGenerator(Closure $generator): void
    {
        $this->win_generator = $generator;
    }

    public function setBet(array $bet): void
    {
        $this->bet['parameters'] = array_merge($this->bet['parameters'], $bet);
    }

    protected function getBet(): array
    {
        return $this->bet;
    }

    public function setNumberOfSpins(int $number_of_spins): void
    {
        $this->number_of_spins = $number_of_spins;
    }

    public function setCallerId(?string $caller_id = null): TestThunderkick
    {
        if (!is_null($caller_id)) {
            $this->caller_id = $caller_id;
        }

        return $this;
    }

    public function setCallerPassword(string $caller_password): TestThunderkick
    {
        if (!is_null($caller_password)) {
            $this->caller_pwd = $caller_password;
        }

        return $this;
    }

    public function setWin(array $win): void
    {
        $this->win['parameters'] = array_merge($this->win['parameters'], $win);
    }

    public function initScenarios()
    {
        // TODO: Implement initScenarios() method.
    }

    public function testConfirmedWins($test_case_type_param = null)
    {
        // TODO: Implement testConfirmedWins() method.
    }

    /**
     * Post the data in JSON format
     *
     * @param array $p_aData An array with data to post.
     * @see outputRequest()
     * @return mixed Outputs the response from the url that is called by the post and optionally can output what is send to the url upfront.
     */
    protected function _post($p_aData)
    {
        
        $a = array();
        $iSessionID = $this->_m_oGp->getGuidv4();
        $this->_m_oGp->toSession($iSessionID, $this->_m_iUserId, $this->_m_mGameId);
        
        $a['playerId'] = $this->_m_iUserId;
        //$a['gameSessionToken'] = $iSessionID;
        //$a['playerSessionToken'] = $iSessionID; // normally received from GP after player has logged during game launch
        $a['operatorSessionToken'] = $iSessionID;
        $a['distributionChannel'] = 'WEB'; // or MOBILE
        $a['gameName'] = $this->_m_mGameId;
        
        if (is_array($p_aData)) {
            $a = array_merge($a, $p_aData);
        }
        
        if (isset($a['betTransactionId']) && isset($a['winTransactionId'])) {
            $sMethod = 'rollbackBetAndWin';
        } else {
            if (isset($a['bets']) && isset($a['wins'])) {
                $sMethod = 'betAndWin';
                $a['winTransactionId'] = $this->_getHash();
            } else {
                $sMethod = $this->_m_oGp->getGpMethodByWalletMethod($this->_m_sMethod);
            }
        }
        
        $txnId = '';
        if (in_array($sMethod, array('bet', 'win', 'betAndWin'))) {
            // in case of bet its a bet txnId,
            // in case of win its a win txnId,
            // in case of betAndWin its a betTxnId and an extra param is added above eg: $a['winTransactionId']
            $txnId = '/' . $this->_getHash();
        }

        $wallet_url = $this->_m_sUrl . '/' . $sMethod . $txnId;
        
        $json = json_encode($a);
        if ($this->_m_bOutput === true) {
            echo 'URL:' . PHP_EOL . $wallet_url . PHP_EOL . "JSON:" . PHP_EOL . $json . PHP_EOL;
        }

//     $json = '{"playerId":27822,"playerExternalReference":"5216027","ipAddress":"195.158.92.198","distributionChannel":"WEB",';
//     $json .= '"gameRound":{"gameName":"tk-magicians-a","gameRoundId":98237001,"providerGameRoundId":1490778255320220001,"providerId":1,';
//     $json .= '"gameRoundStartDate":"2017-03-29T09:04:15.320+0000","gameRoundEndDate":"2017-03-29T09:04:15.320+0000","numberOfBets":1,"numberOfWins":1},';
//     $json .= '"gameSessionToken":"a10f7330-2db6-4332-b9f3-18c2e5524f8c","playerSessionToken":"1b95d7238b2e41a5a9296fee3226efdd0001",';
//     $json .= '"operatorSessionToken":"42864045e1c2c148230b00003bd1f20c","bets":[{"bet":{"amount":0.250000,"currency":"EUR"},"accountId":"5216027-1",';
//     $json .= '"accountType":"FREE_ROUND","jackpotContributions":null}],"betTime":"2017-03-29T09:04:15.320+0000","wins":[{"win":{"amount":0.040000,"currency":"EUR"},';
//     $json .= '"accountId":"5216027-1","accountType":"FREE_ROUND"}],"winTime":"2017-03-29T09:04:15.320+0000","winTransactionId":196473901}';
        
        $res = phive()->post($wallet_url, $json, $this->_m_oGp->getHttpContentType(),
            "Authorization: Basic " . base64_encode($this->_m_oGp->getSetting('vs_username') . ':' . $this->_m_oGp->getSetting('vs_passwd')) . "\r\n",
            $this->_m_oGp->getGpName() . '-out', 'POST');
        return $res;
    }

    protected function spin(): void
    {
        $session_id = $this->generateSessionId();

        $this->exec([["command" => "_balance", 'parameters' => ["session_id" => $session_id]]]);

        for ($spin_number = 0; $spin_number < $this->getNumberOfSpins(); $spin_number++) {
            usleep($this::SLEEP_TIME);
            $this->setBet(["session_id" => $session_id]);
            $this->exec([$this->getBet()]);
            usleep($this::SLEEP_TIME);

            $this->setWin(["session_id" => $session_id, "amountWin" => $this->generateWin($spin_number)]);
            $this->exec([$this->getWin()]);
        }
    }

    protected function generateSessionId(): string
    {
        $generator = Closure::fromCallable($this->session_generator);
        return $generator->call($this);
    }

    protected function getNumberOfSpins(): int
    {
        return $this->number_of_spins;
    }

    protected function generateWin($spin_number)
    {
        $generator = Closure::fromCallable($this->win_generator);
        return $generator->call($this, $spin_number);
    }

    protected function getWin(): array
    {
        return $this->win;
    }

    /**
     * Get array with data for reconsile or bet and win in 1 request reconOrBetWin method is used to settle bet + win.
     * which should then internally validate the transaction and return the updated state of the balance.
     *
     * @param array $p_aParameters Array with parameter. If empty default params will be used.
     *
     * @return array
     */
    private function _jsonParams(array $p_aParameters = array())
    {

        if (in_array($this->_m_sMethod, array('_bet', '_win'))) {
            $a[(($this->_m_sMethod === '_bet') ? 'bets' : 'wins')][] = array(
                (($this->_m_sMethod === '_bet') ? 'bet' : 'win') => array(
                    'amount' => $this->_m_oGp->convertFromToCoinage((($this->_m_sMethod == '_bet') ? $p_aParameters['amountBet'] : $p_aParameters['amountWin']),
                        Gp::COINAGE_CENTS, Gp::COINAGE_UNITS),
                    'currency' => $this->_m_sUserCurrency
                ),
                'accountId' => $this->_m_iUserId,
                'accountType' => (isset($p_aParameters['freespin']) ? 'FREE_ROUND' : 'REAL'),
            );
            $a['gameRound']['gameName'] = $this->_m_mGameId;
            $a['gameRound']['gameRoundId'] = (isset($p_aParameters['roundid']) ? $p_aParameters['roundid'] : $this->_m_oGp->randomNumber(10));

        }

        if ($this->_m_sMethod === '_cancel') {
            $a['betTransactionId'] = $p_aParameters['transactionidBet'];
            if (isset($p_aParameters['transactionidWin'])) {
                $a['winTransactionId'] = $p_aParameters['transactionidWin'];
            }
        }

        return $a;
    }

    /**
     * @param TestThunderkick $game_module
     * @param int $user_id
     * @param string|null $tournament_type
     */
    private function playGame(TestThunderkick $game_module, int $user_id, ?string $tournament_type = "normal"): void
    {
        switch ($tournament_type) {
            case 'normal':
                toWs(['gameRoundStarted'], 'mpextendtest', $user_id);
                toWs(['spinStarted'], 'mpextendtest', $user_id);
                usleep($this::SLEEP_TIME);
                $game_module->spin();
                usleep($this::SLEEP_TIME);
                toWs(['spinEnded'], 'mpextendtest', $user_id);
                toWs(['gameRoundEnded'], 'mpextendtest', $user_id);
                break;
            default:
                throw new InvalidArgumentException(
                    "Testing of {$tournament_type} tournament type is not implemented.",
                    333101
                );
        }
    }
}

