<?php
require_once 'TestGp.php';

class TestGanapati extends TestGp
{
    protected $launchToken;
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
        // dd($p_aAction);
        foreach ($p_aAction as $key => $aAction) {
            $this->_m_sGpMethod = $aAction['command'];
            $this->_m_sMethod = $this->_m_oGp->getWalletMethodByGpMethod($aAction['command']);
            $aParameters = (isset($aAction['parameters']) ? $this->_urlParams($aAction['parameters']) : $this->_urlParams());
        }
        
        return $this->_post($aParameters);
    }

    /*
    * For each call from GP we keep in this array the method name and the fields associated to that call
    * This way we can make a fake implementation for each field just writting a simple function
    * many fields tend to repeat so it's a quick way of getting tests up and running quick
    */
    private $a_callParams = [
        'authenticate' => [
            'launchToken'
        ],
        'fetchBalance' => [
            'playerId', 'sessionId'
        ],
        'withdraw' => [
            'playerId', 'sessionId', 'amount', 'currency', 'transactionId', 'game', 'gameRound', 'roundEnd', 'extra'
        ],
        'deposit' => [
            'playerId', 'sessionId', 'amount', 'currency', 'transactionId', 'game', 'gameRound', 'roundEnd', 'extra'
        ],
        'rollback' => [
            'playerId', 'sessionId', 'game', 'gameRound', 'description'
        ],
        'insertGame' => [
            'skinid'
        ],
        'createfrbGanapati' => [
             'playerId', 'sessionId', 'skinid'
        ]
    ];

    private function skinid($a_params=[])
    {
        return isset($a_params['skinid']) ? $a_params['skinid'] : 'test';
    }

    private function description()
    {
        return "Round Rollback";
    }

    private function extra($a_params=[])
    {
        return isset($a_params['extra']) ? $a_params['extra'] : [];
    }

    private function gameRound($a_params=[])
    {
        $round = isset($a_params['gameRound']) ? $a_params['gameRound'] : rand(1, 100);
        return (string)$round;
    }

    private function roundEnd()
    {
        return (bool)rand(0, 1);
    }

    private function sessionId()
    {
        $sToken = $this->_m_oGp->getGuidv4();
        $this->_m_oGp->toSession($sToken, $this->_m_iUserId, $this->_m_mGameId);
        echo "SessionID: ".$sToken.PHP_EOL;
        $s_sessionId = base64_encode($sToken);
        return $s_sessionId;
    }
    private function playerId()
    {
        return $this->_m_iUserId;
    }

    // launch token should be the players game session id
    private function launchToken()
    {
        $sToken = $this->_m_oGp->getGuidv4();
        $this->_m_oGp->toSession($sToken, $this->_m_iUserId, $this->_m_mGameId);
        return $sToken;
    }


    private function amount($a_params=[])
    {
        $amount = isset($a_params['amount']) ? $a_params['amount'] : 0;
        return $this->_m_oGp->convertFromToCoinage($amount, Gp::COINAGE_UNITS, Gp::COINAGE_UNITS);
    }

    private function buyTransactionId($a_params=[])
    {
        // dd($a_params);
        return isset($a_params['buyTransactionId']) ? $a_params['buyTransactionId'] : $this->_getHash();
    }

    private function transactionId($a_params=[])
    {
        return isset($a_params['transactionId']) ? $a_params['transactionId'] : $this->_getHash();
    }

    private function currency($a_params=[])
    {
        return $this->_m_sUserCurrency;
    }

    private function event()
    {
        return $this->_m_sGpMethod;
    }

    private function game($a_params=[])
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
        $this->_m_sUrl .= '/'.$this->_m_sGpMethod;
        // call the method associated with each param
        $a_urlParams = array_flip($this->a_callParams[$this->_m_sGpMethod]); // Which method should we call
        foreach ($a_urlParams as $method => $value) {
            $a_urlParams[$method]  = $this->{lcfirst($method)}($p_aParameters); // fill the params calling it's function
        }
        // remove null entries
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
            echo 'URL:' . PHP_EOL . $this->_m_sUrl . PHP_EOL . "DATA:" . PHP_EOL . $sValue . PHP_EOL.$this->_m_oGp->getAuthHeader($this->_m_sGpMethod, $sValue);
        }
        
        return phive()->post($this->_m_sUrl, $sValue, Gpinterface::HTTP_CONTENT_TYPE_APPLICATION_JSON, $this->_m_oGp->getAuthHeader($this->_m_sGpMethod, $sValue), $this->_m_oGp->getGpName() . '-out', 'POST') . PHP_EOL;
    }
}
