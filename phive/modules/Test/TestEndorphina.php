<?php
require_once 'TestGp.php';

class TestEndorphina extends TestGp
{
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
     *
     * @param array $p_aAction
     * @return mixed Depends on the response of the requested url
     */
    public function exec($p_aAction)
    {
        
        (empty($this->_m_iUserId) ? die('Please set the user ID using setUserId()') : $this->_m_iUserId);
        (empty($this->_m_sUserCurrency) ? die('Please set the currency using setUserCurrency()') : $this->_m_sUserCurrency);
        (empty($this->_m_mGameId) ? die('Please set the game ID using setGameId()') : $this->_m_mGameId);
        
        foreach ($p_aAction as $key => $aAction) {
            
            $this->_m_sGpMethod = $aAction['command'];
            $this->_m_sMethod = $sCommand = $this->_m_oGp->getWalletMethodByGpMethod($aAction['command']);
            
            $aParameters = (isset($aAction['parameters']) ? $this->$sCommand($aAction['parameters']) : $this->$sCommand());
            
            // this GP provides only one command x request
            break;
        }
        
        return $this->_post($aParameters);
    }
    
    /**
     * Set the users currency
     * @param string $p_sCurrency eg. EUR
     * @return TestEndorphina
     */
    public function setUserCurrency($p_sCurrency)
    {
        $this->_m_sUserCurrency = $p_sCurrency;
        return $this;
    }
    
    /**
     * Set the URL to post the json data to
     *
     * @param string $p_sUrl The url
     * @return TestEndorphina
     */
    public function setUrl($p_sUrl)
    {
        $this->_m_sUrl = $p_sUrl;
        return $this;
    }

    /**
     * Output the json post (what normally is send by isoftgame)
     *
     * @param bool $p_bOutput Do we output the post data. Default: false.
     * @return TestEndorphina
     */
    public function outputRequest($p_bOutput = false)
    {
        $this->_m_bOutput = $p_bOutput;
        return $this;
    }
    
    /**
     * Get the launch URL
     * @return string
     */
    public function getLaunchUrl()
    {
        return $this->_m_oGp->setUserId($this->_m_iUserId)->getDepUrl($this->_m_mGameId, 'en');
    }
    
    public function checkLaunchUrl()
    {
        
        $sExitUrl = $this->_m_oGp->getSetting('exit_url');
        $sNodeId = $this->_m_oGp->getSetting('node_id');
        $sSecret = $this->_m_oGp->getSetting('secretkey');
        $sLaunchUrl = $this->getLaunchUrl();
        
        $aUrlParts = explode('=', $sLaunchUrl);
        $sSign = array_pop($aUrlParts);
        $sToken = substr(array_pop($aUrlParts), 0, -5);
        if ($sSign == sha1($sExitUrl . $sNodeId . $sToken . $sSecret)) {
            echo 'Signature is correct!';
        } else {
            echo 'Signature is NOT correct' . $sLaunchUrl . ' HASH: ' . sha1($sExitUrl . $sNodeId . $sToken . $sSecret);
        }
    }
    
    /**
     * Set hash encoded string instead of using the automatic generated one
     * @return TestEndorphina
     */
    public function setHash($p_sHash)
    {
        $this->_m_sHash = $p_sHash;
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
        return array();
    }
    
    /**
     * Gets current Player’s balance.
     * @return array
     */
    private function _balance()
    {
        return $this->_getParams(array());
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
        return $this->_getParams($p_aParameters);
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
        return $this->_getParams($p_aParameters);
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
        return $this->_getParams($p_aParameters);
    }
    
    /**
     * Get default params for several commands
     * @return array
     */
    private function _getParams(array $p_aParameters = array())
    {
        $a = array();
        
        if (!in_array($this->_m_sMethod, array('_init', '_balance'))) {
            $a['amount'] = (isset($p_aParameters['amount']) ? $this->_m_oGp->convertFromToCoinage($p_aParameters['amount'],
                Endorphina::COINAGE_CENTS, Endorphina::COINAGE_MILLES) : 0);
            $a['date'] = time();
            $a['gameId'] = $this->_m_mGameId;
            $a['id'] = (isset($p_aParameters['transactionid']) ? $p_aParameters['transactionid'] : $this->_getHash());
            if($this->_m_sMethod == '_win'){
                $a['betTransactionId'] = $p_aParameters['bet_transactionid'] ?? $this->_getHash();
            }
        }
        
        if ($this->_m_sMethod !== '_init') {
            $a['game'] = $this->_m_mGameId;
            $a['currency'] = $this->_m_sUserCurrency;
            $a['player'] = $this->_m_iUserId;
        }
        
        if ($this->_m_sMethod == '_win') {
            $a['progressive'] = '';
            $a['progressiveDesc'] = '';
        }
        
        ksort($a);
        
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
        
        $bIsGet = (in_array($this->_m_sMethod, array('_init', '_balance')) ? true : false);

        $url = $this->_m_sUrl . '/' . $this->_m_sGpMethod;

        if(empty($this->sess_key)){            
            $sToken = $this->_m_oGp->getGuidv4();
            $this->_m_oGp->toSession($sToken, $this->_m_iUserId, $this->_m_mGameId);
            $this->sess_key = $sToken;
        } else {
            $sToken = $this->sess_key;
        }
        
        if ($bIsGet === false) {
            // we need to add the token to the hash
            $p_aData['token'] = $sToken;
        }

        // we need to create the hash without the sign param
        $sSign = (!empty($this->_m_sHash) ? $this->_m_sHash : $this->_m_oGp->getHash((($bIsGet) ? (($this->_m_sMethod === '_balance') ? implode('',
                $p_aData) : '') . $sToken : $p_aData)));
        if ($bIsGet === true) {
            $url .= '?token=' . $sToken . (($this->_m_sMethod === '_balance') ? '&' . http_build_query($p_aData) : '') . '&sign=' . $sSign;
        } else {
            $p_aData['sign'] = $sSign;
        }
        
        $sValue = (($bIsGet) ? '' : http_build_query($p_aData));

        print_r($p_aData);
        
        if ($this->_m_bOutput === true) {
            echo 'URL:' . PHP_EOL . $url . PHP_EOL . "JSON:" . PHP_EOL . $sValue . PHP_EOL;
        }
        
        return phive()->post($url, $sValue, 'application/x-www-form-urlencoded', '',
            $this->_m_oGp->getGpName() . '-out', (($bIsGet === true) ? 'GET' : 'POST'));
        
    }

    public function doFullRun($args){
        $this->setGameId($args['gid'])->forceSecureToken(false)->setUserId($args['uid'])->setUrl($args['url'])->setUserCurrency($args['u_obj']->getCurrency())->outputRequest(true);

        $res = $this->setupAjaxInitGameSession($args);

        $aAction = array(array('command' => 'session', 'parameters' => []));
        $res = $this->exec($aAction); 
        echo "\nAuth Result: $res \n\n";
        
        $aAction = array(array('command' => 'balance', 'parameters' => []));
        $res = $this->exec($aAction); 
        echo "\nBalance Result: $res \n\n";
        
        $aAction = [[
            'command' => 'bet',
            'parameters' => [
                'amount' => $args['bet'],
                'transactionid' => $args['mg_id']
            ]
        ]];
        $res = $this->exec($aAction); 
        echo "\nBet Result: $res \n\n";

        $aAction = [[
            'command' => 'win',
            'parameters' => [
                'amount' => $args['win'],
                'transactionid' => $args['mg_id'],
                'bet_transactionid' => $args['mg_id']
            ]
        ]];
        $res = $this->exec($aAction);
        echo "\nWin Result: $res \n";
    }
    
}
