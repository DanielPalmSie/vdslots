<?php
require_once 'TestGp.php';

class TestSpigo extends TestGp
{

    /**
     * Execute the command passed and outputs the response from the url that is called by the post.
     * Optionally output what is send to the url upfront.
     *
     * @param array $p_aAction5
     *
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

    private $a_callParams = [
        'isPlayerLoggedIn' => [
            'event', 'playerIdentifier', 'playerPartnerIdentifier', 'sessionId'
        ],
        'getPlayerBalance' => [
            'event', 'playerIdentifier', 'playerPartnerIdentifier', 'gameId'
        ],
        'gameRequestBuy' => [
            'event', 'playerIdentifier', 'playerPartnerIdentifier', 'gameId', 'currencyISO4217',
            'money', 'IsCoins','gameRequestId', 'ruleId', 'transactionId',
            'gameRules', 'createdEpochMillis', 'IsMobile'
        ],
        'requestRefund' => [
            'event', 'playerIdentifier', 'playerPartnerIdentifier', 'gameId', 'partnerGameRequestIdentifier',
            'transactionId', 'buyTransactionId', 'bonusCode', 'isMobile'
        ],
        'checkValidRequest' => [
            'event', 'transactionId', 'playerIdentifier', 'playerPartnerIdentifier'
        ],
        'jackpotPayout' => [ // TODO
            'event', 'jackpotIdentifier', 'playerPartnerIdentifier', 'playerIdentifier', 'currencyISO4217',
            'moneyWon', 'coinsWon', 'isMoney', 'epochReleasedMillis', 'isPrimary', 'transactionId',
            'buyTransactionId', 'partnerGameRequestIdentifier', 'gameSessionId', 'gameId', 'bonusCode', 'isMobile'
        ],
        'gameSessionEnd' => [ // TODO spins etc
            'event', 'endEpochMillis', 'dateEnd','gameSessionIdentifier', 'gameSessionId',
            'transactionId', 'index', 'gameId', 'isFreeCampaignGameWithPrize', 'players', 'isResend'
        ],
        'gameSessionRequestBuy' => [ // TODO EL FORMATO DE SALIDA NO SE CORRESPONDE CON EL DE LA API
            'event', 'gameSessionIdentifier', 'playerIdentifier', 'playerPartnerIdentifier', 'currencyISO4217',
            'money', 'coins', 'isCoins', 'transactionId', 'gameSessionId', 'partnerGameRequestIdentifier',
            'gameId', 'bonusCode', 'isMobile'
        ],
        'gameSessionDeposit' => [
            'event', 'gameSessionIdentifier', 'playerIdentifier', 'playerPartnerIdentifier', 'currencyISO4217',
            'money', 'coins', 'isCoins', 'transactionId', 'buyTransactionId', 'transactionType', 'gameSessionId',
            'partnerGameRequestIdentifier', 'gameId', 'bonusCode'
        ],
        'gameSessionBuyRefund' => [
            'event', 'gameSessionIdentifier', 'playerIdentifier', 'playerPartnerIdentifier', 'transactionId',
            'buyTransactionId', 'gameSessionId', 'partnerGameRequestIdentifier','gameId','bonusCode'
        ]
    ];

    private function transactionType($a_params=[])
    {
        // Only this two?
        $aTypes = [
            //'SLOTMACHINE_SCATTER',
            'SLOTMACHINE_FREESPIN',
            'SLOTMACHINE_BONUS_GAME',
            //'SLOTMACHINE_SPIN'
        ];
        return isset($a_params['transactionType']) ? $a_params['transactionType'] : $aTypes[rand(0, 1)];
    }

    public function sessionId($a_params='')
    {
        $uid = $this->_m_iUserId;
        $token = $this->_m_oGp->getGuidv4($uid);
        $p_mGameId = $this->_m_mGameId;
        $this->_m_oGp->toSession($token, $uid, $p_mGameId, $p_sTarget);
        return ;
    }
    private function coins($a_params=[])
    {
        return null;
    }
    private function endEpochMillis($a_params=[])
    {
        return null;
    }
    private function dateEnd($a_params=[])
    {
        return null;
    }
    private function gameSessionIdentifier($a_params=[])
    {
        return null;
    }
    private function index($a_params=[])
    {
        return null;
    }
    private function isFreeCampaignGameWithPrize($a_params=[])
    {
        return null;
    }
    // TODO
    private function players($a_params=[])
    {
        if (isset($a_params['players'])) {
            $player = $a_params['players'];
            $player['playerPartnerIdentifier'] = $this->playerPartnerIdentifier();
            return [$player];
        }
        $moneyWon = isset($a_params['moneyWon']) ? $a_params['moneyWon'] : 0;
        $player = [
            'playerPartnerIdentifier' => $this->playerPartnerIdentifier(),
            'buyTransactionId' => $this->buyTransactionId(),
            'transactionId' => $this->transactionId(),
            'moneyWon' => $this->_m_oGp->convertFromToCoinage($moneyWon, Gp::COINAGE_CENTS, Gp::COINAGE_CENTS)
        ];
        return [ ['playerPartnerIdentifier' => $this->playerPartnerIdentifier()] ];
    }
    private function isResend($a_params=[])
    {
        return null;
    }
    private function gameSessionId($a_params=[])
    {
        return null;
    }
    private function isPrimary($a_params=[])
    {
        return null;
    }
    private function epochReleasedMillis($a_params=[])
    {
        return null;
    }
    private function isMoney($a_params=[])
    {
        return null;
    }
    private function coinsWon($a_params=[])
    {
        return null;
    }
    private function moneyWon($a_params=[])
    {
        $moneyWon = isset($a_params['moneyWon']) ? $a_params['moneyWon'] : 0;
        return $this->_m_oGp->convertFromToCoinage($moneyWon, Gp::COINAGE_CENTS, Gp::COINAGE_CENTS);
    }
    private function jackpotIdentifier($a_params=[])
    {
        return null;
    }
    private function partnerGameRequestIdentifier($a_params=[])
    {
        return null;
    }
    private function buyTransactionId($a_params=[])
    {
        // dd($a_params);
        return isset($a_params['buyTransactionId']) ? $a_params['buyTransactionId'] : $this->_getHash();
    }
    private function bonusCode($a_params=[])
    {
        return isset($a_params['bonusCode']) ? $a_params['bonusCode'] : null;
    }
    private function isMobile($a_params=[])
    {
        return false;
    }
    private function createdEpochMillis($a_params=[])
    {
        return null;
    }
    private function gameRules($a_params=[])
    {
        return null;
    }
    private function transactionId($a_params=[])
    {
        return isset($a_params['transactionId']) ? $a_params['transactionId'] : $this->_getHash();
    }
    private function ruleId($a_params=[])
    {
        return null;
    }
    private function gameRequestId($a_params=[])
    {
        return isset($a_params['gameRequestId']) ? $a_params['gameRequestId'] : null;
        ;
    }// _m_iWalletTxn
    private function isCoins($a_params=[])
    {
        return null;
    }
    // The players bet comming from gp
    private function money($a_params=[])
    {
        return isset($a_params['money']) ? $a_params['money'] : null;
    }
    private function currencyISO4217($a_params=[])
    {
        return $this->_m_sUserCurrency;
    }

    private function event()
    {
        return $this->_m_sGpMethod;
    }
    private function playerIdentifier($a_params=[])
    {
        //$sToken = $this->_m_oGp->getGuidv4($this->_m_iUserId);
        $sToken = $this->_m_iUserId;
        //$this->_m_oGp->toSession($sToken, $this->_m_iUserId,
        return $sToken;
    }
    private function playerPartnerIdentifier($a_params=[])
    {
        //$sToken = $this->_m_oGp->getGuidv4($this->_m_iUserId);
        $sToken = $this->_m_iUserId;
        //$this->_m_oGp->toSession($sToken, $this->_m_iUserId,
        return $sToken;
    }

    private function gameId($a_params=[])
    {
        return $this->_m_mGameId;
    }

    /**
     * Get array with data for reconsile or bet and win in 1 request reconOrBetWin method is used to settle bet + win.
     * which should then internally validate the transaction and return the updated state of the balance.
     *
     * @param array $p_aParameters Array with parameter. If empty default params will be used.
     *
     * @return array
     */
    private function _urlParams(array $p_aParameters = [])
    {
        if (! isset($this->a_callParams[$this->_m_sGpMethod])) {
            echo "NO METHOD PROVIDED";
            // dd($p_aParameters);
        }

        $a_urlParams = array_flip($this->a_callParams[$this->_m_sGpMethod]);

        foreach ($a_urlParams as $method => $value) {
            $a_urlParams[$method]  = $this->{lcfirst($method)}($p_aParameters);
        }
        
        $a_urlParams = array_filter($a_urlParams, function ($value) {
            return ! is_null($value);
        });

        // dd($a_urlParams);

        return $a_urlParams;
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
        if ($this->_m_bOutput === true) {
            echo 'URL:' . PHP_EOL . $this->_m_sUrl . PHP_EOL . "DATA:" . PHP_EOL . $sValue . PHP_EOL.$this->_m_oGp->getAuthHeader();
        }
        return phive()->post($this->_m_sUrl, $sValue, Gpinterface::HTTP_CONTENT_TYPE_APPLICATION_JSON, $this->_m_oGp->getAuthHeader(), $this->_m_oGp->getGpName() . '-out', 'POST') . PHP_EOL;
    }
}
