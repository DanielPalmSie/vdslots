<?php

use Opis\Closure\SerializableClosure;

require_once 'TestGp.php';

class TestPragmatic extends TestGp
{
    public const EUR_CURRENCY = 'EUR';

    public const SLEEP_TIME = 100000;

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
            
            $this->_m_sMethod = $aAction['command'];
            $this->_m_sGpMethod = $this->_m_oGp->getGpMethodByWalletMethod($this->_m_sMethod);
            
            $aParameters = (isset($aAction['parameters']) ? $this->_urlParams($aAction['parameters']) : $this->_urlParams());
            
            // this GP provides only one command x request
            break;
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
        if(empty($p_aParameters['token'])){
            $iSessionID = $this->_m_oGp->getGuidv4($this->_m_iUserId);
            $this->_m_oGp->toSession($iSessionID, $this->_m_iUserId, $this->_m_oGp->stripPrefix($this->_m_mGameId));
        }
        $sProviderId = $this->_m_oGp->getSetting('providerId');
        
        switch ($this->_m_sGpMethod) {
            case 'authenticate':
                $a['token'] = $iSessionID;
                $a['ProviderId'] = $sProviderId;
            
            case 'balance':
                $a['ProviderId'] = $sProviderId;
                $a['userId'] = $this->_m_iUserId;
                $a['token'] = (isset($p_aParameters['token']) ? $p_aParameters['token'] : $iSessionID);
                break;
            
            case 'bet':
            case 'result':
            case 'bonusWin':
            case 'jackpotWin':
            case 'endRound':
            case 'refund':
                $a['token'] = (isset($p_aParameters['token']) ? $p_aParameters['token'] : $iSessionID);
                $a['userId'] = $this->_m_iUserId;
                $a['roundId'] = '1234567890';
                
                if ($this->_m_sGpMethod !== 'endRound') {
                    if ($this->_m_sGpMethod === 'bet' || $this->_m_sGpMethod === 'result') {
                        $a['amount'] = $this->_m_oGp->convertFromToCoinage(
                            $this->_m_sGpMethod === 'bet' ? $p_aParameters['amountBet'] : $p_aParameters['amountWin'],
                            Gp::COINAGE_CENTS,
                            Gp::COINAGE_UNITS);
                    } else {
                        $a['amount'] = $this->_m_oGp->convertFromToCoinage($p_aParameters['amount'], Gp::COINAGE_CENTS,
                            Gp::COINAGE_UNITS);
                    }
                    $a['reference'] = ($p_aParameters['transactionid'] ?? $this->_getHash());
                    $a['ProviderId'] = $sProviderId;
                    /**
                     * @todo
                     * $a['timestamp'] = ''; // optional field
                     */
                }
                
                if ($this->_m_sGpMethod !== 'jackpotWin') {
                    
                    if (!isset($p_aParameters['freespin'])) {
                        $a['bonusCode'] = 0;
                    } else {
                        if ($p_aParameters['freespin'] === true) {
                            $aFreespins = $this->_m_oGp->getBonusEntryByGameId($this->_m_iUserId, $this->_m_mGameId);
                            //$frb_denomination = $aFreespins['frb_denomination'];
                            $frb_id = $aFreespins['id'];
                        } else {
                            //$frb_denomination = $p_aParameters['freespin']['frb_bet'];
                            $frb_id = $p_aParameters['freespin']['frb_id'];
                        }
                        $a['bonusCode'] = $frb_id;
                    }
                } else {
                    $a['jackpotId'] = $p_aParameters['jackpotid'];
                }
                
                if ($this->_m_sGpMethod !== 'bonusWin') {
                    $a['gameId'] = $this->_m_oGp->stripPrefix($this->_m_mGameId); // not with bonusWin
                    if ($this->_m_sGpMethod !== 'jackpotWin') {
                        $a['platform'] = 'WEB'; // MOBILE // not with bonusWin
                        if (!empty($a['bonusCode'])) {
                            $a['originalRoundID'] = '1234567890'; // Id of the game round where free spins started. This field is relevant only for free spins.
                        }
                    }
                }
                break;
        }
        
        ksort($a);
        
        $a['hash'] = $this->_m_oGp->getHash(http_build_query($a), Gp::ENCRYPTION_MD5);
        
        return $a;
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
        $wallet_url = $this->_m_sUrl . '?action=' . $this->_m_oGp->getGpMethodByWalletMethod($this->_m_sMethod) . '.html';

        /**
         * @todo
         $p_aData = array();
         $p_aData["roundDetails"] = "spin";
         $p_aData["reference"] = "5880b5552a7a67636af7200e";
         $p_aData["gameId"] = "vs7monkeys";
         $p_aData["bonusCode"] = "test_vs_frb119";
         $p_aData["amount"] = "0.0";
         $p_aData["providerId"] = "PragmaticPlay";
         $p_aData["userId"] = "5235886";
         $p_aData["roundId"] = "2351619303";
         $p_aData["platform"] = "WEB";
         $p_aData["hash"] = "db976aa102df4223dc923e29867f6675";
         $p_aData["timestamp"] = "1484830037567";
         */

        $sValue = http_build_query($p_aData);
        
        if ($this->_m_bOutput === true) {
            echo 'URL:' . PHP_EOL . $wallet_url . PHP_EOL . "DATA:" . PHP_EOL . $sValue . PHP_EOL;
        }
        
        if ($this->_m_bTestRc === true) {
            
            $aRc = $this->_m_oGp->getRc($this->_m_iUserId);
            
            if (!empty($aRc)) {
                phMset(Gp::PREFIX_MOB_RC_TIMEOUT . $this->_m_iUserId, '1', $aRc['reality_check_interval']);
            }
        }
        
        return phive()->post($wallet_url, $sValue, 'application/x-www-form-urlencoded', '',
                $this->_m_oGp->getGpName() . '-out', 'POST') . PHP_EOL;
        
    }

    public function setCallerId(?string $caller_id = null): TestPragmatic
    {
        if (!is_null($caller_id)) {
            $this->caller_id = $caller_id;
        }

        return $this;
    }

    /**
     * @return mixed|void
     */
    public function initScenarios()
    {
        // TODO: Implement initScenarios() method.
    }

    public function testConfirmedWins($test_case_type_param = null)
    {
        // TODO: Implement testConfirmedWins() method.
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

    public function setCallerPassword(string $caller_password): TestPragmatic
    {
        if (!is_null($caller_password)) {
            $this->caller_pwd = $caller_password;
        }

        return $this;
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
        $pragmatic = TestPhive::getModule("Pragmatic");
        /* @var SerializableClosure */
        $sg = unserialize(base64_decode($session_generator));
        $wg = unserialize(base64_decode($win_generator));

        $pragmatic->injectDependency(phive('UserHandler'));
        $pragmatic->injectDependency(phive('Pragmatic'));
        $pragmatic->setUserId($tournament_entry_id);
        $this->_m_sUserCurrency = $this::EUR_CURRENCY;
        $pragmatic->setSessionGenerator($sg->getClosure());
        $pragmatic->setWinGenerator($wg->getClosure());
        $pragmatic->setBet(['amountBet' => $bet_amount]);
        $pragmatic->setNumberOfSpins($number_of_spins);
        $pragmatic->setCallerId($caller_id);
        $pragmatic->setCallerPassword($caller_password);
        $pragmatic->setUrl($testing_server_url);
        $pragmatic->setGameId($game_id);
        $pragmatic->outputRequest(true);

        $this->playGame($pragmatic, $user_id);
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

    public function setWin(array $win): void
    {
        $this->win['parameters'] = array_merge($this->win['parameters'], $win);
    }

    protected function getWin(): array
    {
        return $this->win;
    }

    protected function generateWin($spin_number)
    {
        $generator = Closure::fromCallable($this->win_generator);
        return $generator->call($this, $spin_number);
    }

    public function setNumberOfSpins(int $number_of_spins): void
    {
        $this->number_of_spins = $number_of_spins;
    }

    protected function getNumberOfSpins(): int
    {
        return $this->number_of_spins;
    }

    protected function generateSessionId(): string
    {
        $generator = Closure::fromCallable($this->session_generator);
        return $generator->call($this);
    }

    protected function spin(): void
    {
        $session_id = $this->generateSessionId();

        $this->exec([["command" => "_init", "parameters" => ['promotions' => 'N', "session_id" => $session_id, '']]]);
        $this->exec([["command" => "_balance", 'parameters' => ["session_id" => $session_id]]]);

        for ($spin_number = 0; $spin_number < $this->getNumberOfSpins(); $spin_number++) {
            usleep($this::SLEEP_TIME);
            $this->setBet(["session_id" => $session_id]);
            $this->exec([$this->getBet()]);
            usleep($this::SLEEP_TIME);

            $this->setWin(["session_id" => $session_id, "amountWin" => $this->generateWin($spin_number)]);
            $this->exec([$this->getWin()]);
        }

        $this->exec([["command" => "_end", "parameters" => ["session_id" => $session_id,]]]);
    }

    /**
     * @param TestPragmatic $game_module
     * @param int $user_id
     * @param string|null $tournament_type
     */
    private function playGame(TestPragmatic $game_module, int $user_id, ?string $tournament_type = "normal"): void
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
