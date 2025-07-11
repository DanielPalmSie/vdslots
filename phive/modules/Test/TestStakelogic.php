<?php
use Opis\Closure\SerializableClosure;

require_once 'TestGp.php';

class TestStakelogic extends TestGp
{

    public const EUR_CURRENCY = 'EUR';

    public const SLEEP_TIME = 100000;

    /**
     * @var string
     */
    public string $url = 'testing';

    /**
     * @var string
     */
    public string $caller_id = 'testmerchant';

    /**
     * @var string
     */
    public string $caller_pwd = '';
    /**
     * @var mixed|string
     */
    private $last_bet_id;

    private array $bet = [
        'command' => 'bet',
        'parameters' => [
            'amountBet' => 10,
            "session_id" => ""
        ]
    ];

    private array $win = [
        'command' => 'win',
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

    /**
     * @var Closure
     */
    private Closure $session_generator;

    private Closure $win_generator;

    private int $number_of_spins;

    public function setCallerId(?string $caller_id = null): TestStakelogic
    {
        if (!is_null($caller_id)) {
            $this->caller_id = $caller_id;
        }

        return $this;
    }

    public function setCallerPassword(string $caller_password): TestStakelogic
    {
        if (!is_null($caller_password)) {
            $this->caller_pwd = $caller_password;
        }

        return $this;
    }

    public function setSessionGenerator(Closure $generator)
    {

        $this->session_generator = $generator;
    }

    public function setWinGenerator(Closure $generator)
    {
        $this->win_generator = $generator;
    }

    protected function generateSessionId(): string
    {
        $generator = Closure::fromCallable($this->session_generator);
        return $generator->call($this);
    }

    public function setBet(array $bet)
    {
        $this->bet['parameters'] = array_merge($this->bet['parameters'], $bet);
    }

    protected function getBet(): array
    {
        return $this->bet;
    }

    protected function generateWin($spin_number)
    {
        $generator = Closure::fromCallable($this->win_generator);
        return $generator->call($this, $spin_number);
    }

    public function setWin(array $win)
    {
        $this->win['parameters'] = array_merge($this->win['parameters'], $win);
    }

    protected function getWin(): array
    {
        return $this->win;
    }

    protected function generateTransactionId(string $tournament_user_id): string
    {
        return $this->_m_oGp->randomNumber(10);
//        return $tournament_user_id . "_" . $this->_m_oGp->randomNumber(10);
    }

    public function setNumberOfSpins(int $number_of_spins)
    {
        $this->number_of_spins = $number_of_spins;
    }

    protected function getNumberOfSpins(): int
    {
        return $this->number_of_spins;
    }

    protected function spin()
    {
        $session_id = $this->generateSessionId();

        $this->exec([["command" => "init", "parameters" => ['promotions' => 'N', "session_id" => $session_id]]]);
        $this->exec([["command" => "balance", 'parameters' => ["session_id" => $session_id]]]);

        for ($spin_number = 0; $spin_number < $this->getNumberOfSpins(); $spin_number++) {
            usleep($this::SLEEP_TIME);
            $this->setBet(["session_id" => $session_id]);
            $this->exec([$this->getBet()]);

            usleep($this::SLEEP_TIME);

            $this->setWin(["session_id" => $session_id, "amountWin" => $this->generateWin($spin_number)]);
            $this->exec([$this->getWin()]);
        }

        $this->exec([["command" => "end", "parameters" => ["session_id" => $session_id,]]]);
    }

    /**
     * @param TestStakelogic $game_module
     * @param int $user_id
     * @param string|null $tournament_type
     */
    private function playGame(TestStakelogic $game_module, int $user_id, ?string $tournament_type = "normal")
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
        $stakelogic = TestPhive::getModule("Stakelogic");
        /* @var SerializableClosure */
        $sg = unserialize(base64_decode($session_generator));
        $wg = unserialize(base64_decode($win_generator));

        $stakelogic->injectDependency(phive("UserHandler"));
        $stakelogic->injectDependency(phive("Stakelogic"));
        $stakelogic->setUserId($tournament_entry_id);
        $this->_m_sUserCurrency = $this::EUR_CURRENCY;
        $stakelogic->setSessionGenerator($sg->getClosure());
        $stakelogic->setWinGenerator($wg->getClosure());
        $stakelogic->setBet(['amountBet' => $bet_amount]);
        $stakelogic->setNumberOfSpins($number_of_spins);
        $stakelogic->setCallerId($caller_id);
        $stakelogic->setCallerPassword($caller_password);
        $stakelogic->setUrl($testing_server_url);
        $stakelogic->setGameId($game_id);
        $stakelogic->outputRequest(true);

        $this->playGame($stakelogic, $user_id);
    }

    protected function getTournamentUserId(): string
    {
        return $this->_m_iUserId;
    }

    protected function getGameId(): string
    {
        return $this->_m_mGameId;
    }

    protected function setMethod(string $method): self
    {
        $this->_m_sMethod = '_' . $method;

        return $this;
    }

    protected function getMethod(): string
    {
        return $this->_m_sMethod;
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

    /**
     * Execute the command passed and outputs the response from the url that is called by the post.
     * Optionally output what is send to the url upfront.
     * GP allows only one command per request
     *
     * @param array $actions
     * @return mixed Depends on the response of the requested url
     */
    public function exec($actions)
    {
        $tournament_user_id = $this->getTournamentUserId();
        $game_id = $this->getGameId();
        (empty($tournament_user_id) ? die('Please set the user ID using setUserId()') : $this->_m_iUserId);
        (empty($game_id) ? die('Please set the game ID using setGameId()') : $this->_m_mGameId);

        $this->setMethod($actions[0]["command"]);
        $parameters = (isset($actions[0]['parameters']) ? $this->jsonParams(
            $actions[0]['parameters']
        ) : $this->jsonParams());

        return $this->_post($parameters);
    }

    /**
     * Get array with data for reconcile or bet and win in 1 request reconOrBetWin method is used to settle bet + win.
     * which should then internally validate the transaction and return the updated state of the balance.
     *
     * @param array $parameters Array with parameter. If empty default params will be used.
     *
     * @return array
     */
    private function jsonParams(array $parameters = []): array
    {
        switch (true) {
            case isset($parameters['session_id']):
                $session_id = $parameters['session_id'];
                break;
            default:
                $session_id = $this->_m_oGp->getGuidv4();
        }
        $secure_token = sprintf("stakelogic_%s_%s", (string)$this->getTournamentUserId(), (string)$session_id);

        $this->_m_oGp->toSession($secure_token, $this->getTournamentUserId(), $this->getGameId());


        $a['playerId'] = $this->getTournamentUserId();
        $a['gameSessionId'] = $session_id;
        $a['authToken'] = null;

        if (in_array($this->getMethod(), array('_bet', '_win', '_balance'))) {
            $a['currencyCode'] = $this->_m_sUserCurrency;
        }

        if (in_array($this->getMethod(), array('_bet', '_win', '_cancel'))) {
            $a['gameRoundId'] = $parameters['transaction_id'] ?? $this->generateTransactionId(
                    $this->getTournamentUserId()
                );
            if ("_bet" === $this->getMethod()) {
                $this->last_bet_id = $a['gameRoundId'];
            } elseif (!empty($this->last_bet_id)) {
                $a['gameRoundId'] = $this->last_bet_id;
            }
        }

        if (in_array($this->getMethod(), array('_bet', '_win'))) {
            if (!isset($parameters['freespin'])) {
                $a['roundType'] = 'NORMAL';
                $a['realMoney' . (($this->getMethod() == '_bet') ? 'Stake' : 'Win')] = $this->_m_oGp->convertFromToCoinage(
                    (($this->getMethod() == '_bet') ? $parameters['amountBet'] : $parameters['amountWin']),
                    Gp::COINAGE_CENTS,
                    Gp::COINAGE_UNITS
                );
                $a['funMoney' . (($this->getMethod() == '_bet') ? 'Stake' : 'Win')] = null;
                // implementation for jackpot win
                if ($parameters['jackpotAmount']) {
                    $a['jackpotType'] = 'POOLED';
                    $a['realMoneyJackpotWinInPlayerCurrency'] = $this->_m_oGp->convertFromToCoinage(
                        $parameters['jackpotAmount'],
                        Gp::COINAGE_CENTS,
                        Gp::COINAGE_UNITS
                    );
                }
            } else {
                $a['roundType'] = 'CASINO_FREE_SPIN';
                $a['realMoney' . (($this->getMethod() == '_bet') ? 'Stake' : 'Win')] = null;
                $a['funMoney' . (($this->getMethod() == '_bet') ? 'Stake' : 'Win')] = null;

                if ($parameters['freespin'] === true) {
                    $aFreespins = $this->_m_oGp->getBonusEntryByGameId($this->getTournamentUserId(), $this->getGameId());
                    $frb_denomination = $aFreespins['frb_denomination'];
                    $frb_id = $aFreespins['id'];
                } else {
                    $frb_denomination = $parameters['freespin']['frb_bet'];
                    $frb_id = $parameters['freespin']['frb_id'];
                }

                $a['casinoFreeSpin'] = array(
                    'freeSpinsId' => $frb_id,
                    'stake' => $this->_m_oGp->convertFromToCoinage(
                        mc(
                            $frb_denomination,
                            $this->_m_sUserCurrency,
                            'multi',
                            false
                        ),
                        Gp::COINAGE_CENTS,
                        Gp::COINAGE_UNITS
                    ),
                );
            }
        }

        return $a;
    }


    /**
     * Post the data in JSON format
     *
     * @param array $p_aData An array with data to post.
     * @return mixed Outputs the response from the url that is called by the post and optionally can output what is send to the url upfront.
     * @see outputRequest()
     */
    protected function _post($p_aData)
    {
        $json = json_encode($p_aData);

        $wallet_url = $this->_m_sUrl . '/dw/' . $this->_m_oGp->getGpMethodByWalletMethod($this->getMethod());
        //$json = '{"playerId":5235889,"gameSessionId":"12ddf19329a1dea8aa840000177d556c","currencyCode":"EUR","gameRoundId":"592ea7651e6d0","roundType":"NORMAL","realMoneyStake":1,"funMoneyStake":null}';
        //$json = '{"playerId":5235889,"gameSessionId":"79ef2be5bb8c00715a4186","gameRoundId":"303997859"}';

        if ($this->_m_bOutput === true) {
            echo 'URL:' . PHP_EOL . $wallet_url . PHP_EOL . "JSON:" . PHP_EOL . $json . PHP_EOL;
        }

        return phive()->post(
            $wallet_url,
            $json,
            $this->_m_oGp->getHttpContentType(),
            '',
            $this->_m_oGp->getGpName() . '-out',
        );
    }
}
