<?php

class TestIsoftbet extends TestStandalone
{
    
    /**
     * Instance of Isoftbet
     * @var Isoftbet
     */
    private $_m_oIsoftbet;
    
    /**
     * The user ID to test with
     * @var int
     */
    private $_m_iUserId;
    
    /**
     * The user currency
     * @var string
     */
    private $_m_sUserCurrency;
    
    /**
     * The game ID to test
     * @var mixed
     */
    private $_m_mGameId;
    
    /**
     * Do we echo the json post data
     * @var bool
     */
    private $_m_bOutputPost;
    
    /**
     * HMAC encoded string
     * @var string
     */
    private $_m_sHmac;
    
    /**
     * Construct: will generate randomly transaction ID for bet, win and trans_id
     */
    public function __construct()
    {
    }

    public function initScenarios()
    {
        // TODO: Implement initScenarios() method.
    }

    public function parseData(): string
    {
        // TODO: Implement parseData() method.
    }

    public function testConfirmedWins($test_case_type_param = null)
    {
        // TODO: Implement testConfirmedWins() method.
    }

    /**
     * Inject class dependencies
     *
     * @param object $p_oDependency Instance of the dependent class
     * @return mixed TestIsoftbet|bool false if dependency couldn't be set
     */
    public function injectDependency($p_oDependency)
    {
        switch ($p_oDependency) {
            case $p_oDependency instanceof Isoftbet:
                $this->_m_oIsoftbet = $p_oDependency;
                break;
            default:
                return false;
        }
        return $this;
    }
    
    /**
     * Execute the command passed and outputs the response from the url that is called by the post.
     * Optionally output what is send to the url upfront.
     *
     * @param array $p_aAction
     * @return mixed Depends on the repsonse of the requested url
     */
    public function exec($p_aAction)
    {
        
        $aPost = array();
        $aPost['playerid'] = (empty($this->_m_iUserId) ? die('Please set the user ID using setUserId()') : $this->_m_iUserId);
        $aPost['currency'] = (empty($this->_m_sUserCurrency) ? die('Please set the currency using setUserCurrency()') : $this->_m_sUserCurrency);
        $aPost['skinid'] = (empty($this->_m_mGameId) ? die('Please set the game ID using setGameId()') : $this->_m_mGameId);
        
        $aPost['operator'] = '';
        $aPost['sessionid'] = '';
        
        foreach ($p_aAction as $key => $aAction) {
            
            $sCommand = '_' . $aAction['command'];
            
            $aParameters = (isset($aAction['parameters']) ? $this->$sCommand($aAction['parameters']) : $this->$sCommand());
            
            $a = array();
            $a['command'] = $aAction['command'];
            
            if (!empty($aParameters)) {
                $a['parameters'] = $aParameters;
            }
            
            if (count($p_aAction) > 1 && in_array($aAction['command'], array('bet', 'win'))) {
                
                $aPost['actions'][] = $a;
                
            } else {
                
                $aPost['action'] = $a;
                
            }
            
        }
        
        $aPost['state'] = (isset($aPost['action']) ? 'single' : 'multi');
        
        return $this->_post($aPost);
    }
    
    /**
     * Set the users currency
     * @param string $p_sCurrency eg. EUR
     * @return TestIsoftbet
     */
    public function setUserCurrency($p_sCurrency)
    {
        $this->_m_sUserCurrency = $p_sCurrency;
        return $this;
    }
    
    /**
     * Set the user ID to test with
     *
     * @param int $p_iUserId
     * @return TestIsoftbet
     */
    public function setUserId($p_iUserId)
    {
        $this->_m_iUserId = $p_iUserId;
        return $this;
    }
    
    /**
     * Set the URL to post the json data to
     *
     * @param string $p_sUrl The url
     * @return TestIsoftbet
     */
    public function setUrl($p_sUrl)
    {
        $this->_m_sUrl = $p_sUrl;
        return $this;
    }
    
    /**
     * Set the game ID
     *
     * @param mixed $p_mGameId The game ID
     * @return TestIsoftbet
     */
    public function setGameId($p_mGameId)
    {
        $this->_m_mGameId = $p_mGameId;
        return $this;
    }
    
    /**
     * Output the json post (what normally is send by isoftgame)
     *
     * @param bool $p_bOutput Do we output the post data. Default: false.
     * @return TestIsoftbet
     */
    public function outputPost($p_bOutput = false)
    {
        $this->_m_bOutputPost = $p_bOutput;
        return $this;
    }
    
    
    /**
     * Set HMAC encoded string instead of using the automatic generated one
     * @return TestIsoftbet
     */
    public function setHmac($p_sHmac)
    {
        $this->_m_sHmac = $p_sHmac;
        return $this;
    }
    
    /**
     * This is the first call of every session. Requires token from the Game Start Communication Process.
     * Required parameters are mandatory to start Game Client.
     *
     * @return array
     */
    private function _init()
    {
        return array(
            'token' => $this->_getHash(),
            'game_type' => ''
        );
    }
    
    /**
     * Gets current Player’s balance.
     * @return array
     */
    private function _balance()
    {
        return array();
    }
    
    /**
     * The bet method is used to place bets and simultaneously validate free round usage. It passes all necessary
     * information about each bet to the operator, who should then internally validate the transaction and return
     * the updated state of the balance and free rounds.
     *
     * @param array $p_aParameters Array with parameter. If empty default params will be used.
     * @return array
     */
    private function _bet(array $p_aParameters = array())
    {
        return array_merge(array('jpc' => ''), $this->_getParams($p_aParameters));
    }
    
    /**
     * The win method is used to settle bets. It passes all necessary information about each win to the operator,
     * which should then internally validate the transaction and return the updated state of the balance.
     *
     * @param array $p_aParameters Array with parameter. If empty default params will be used.
     * @return array
     */
    private function _win(array $p_aParameters = array())
    {
        return array_merge(array('jpw' => ''), $this->_getParams($p_aParameters));
    }
    
    /**
     * This method is used to cancel a bet or win on the operator’s side. It passes all necessary information about the transaction that should be cancelled to the operator,
     * which should then internally cancel the transaction and add money to the player’s balance in case of a cancelled bet, or deduct money from the player’s
     * balance in case of a cancelled win. This method should return the successful result of this operation.
     *
     * @param array $p_aParameters Array with parameter. If empty default params will be used.
     * @return array
     */
    private function _cancel(array $p_aParameters = array())
    {
        return array_merge(array('jpc' => ''), $this->_getParams($p_aParameters));
    }
    
    /**
     * This method is used to inform the operator about the end session state. It passes all necessary information
     * about a session to the operator which should then internally close the session and return the successful result of this operation.
     * The end method is used in order to know when the game session has properly ended.
     *
     * @return array
     */
    private function _end()
    {
        return array(
            'sessionstatus' => 'CLOSE' // either CLOSE|ERROR
        );
    }
    
    
    /**
     * Get default params for several commands
     * @return array
     */
    private function _getParams(array $p_aParameters = array())
    {
        return array(
            'transactionid' => (isset($p_aParameters['transactionid']) ? $p_aParameters['transactionid'] : $this->_getHash()),
            'roundid' => (isset($p_aParameters['roundid']) ? $p_aParameters['roundid'] : $this->_getHash()),
            'amount' => (isset($p_aParameters['amount']) ? $p_aParameters['amount'] : 0)
        );
    }
    
    /**
     * Get a unique hash
     * @return string
     */
    private function _getHash()
    {
        return md5(uniqid(null, true));
    }
    
    /**
     * Post the data in JSON format
     *
     * @param array $p_aData An array with data to post.
     * @see outputPost()
     * @return mixed Outputs the response from the url that is called by the post and optionally can output what is send to the url upfront.
     */
    private function _post(array $p_aData = array())
    {
        
        $json = json_encode($p_aData);
        
        $this->_m_sUrl = $this->_m_sUrl . '?hash=' . (isset($this->_m_sHmac) ? $this->_m_sHmac : $this->_m_oIsoftbet->getHashHmac($json));
        
        if ($this->_m_bOutputPost === true) {
            echo 'URL:' . PHP_EOL . $this->_m_sUrl . PHP_EOL . "JSON:" . PHP_EOL . $json;
        }
        
        return phive()->post($this->_m_sUrl, $json, 'application/json', '', 'isoftbet-out');
    }
}
