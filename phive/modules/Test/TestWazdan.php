<?php
require_once 'TestGp.php';

class TestWazdan extends TestGp
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

         $aAddParams = [
             'token' => (isset($p_aParameters['token']) ? $p_aParameters['token'] : $this->_m_oGp->randomNumber(10)),
             'remoteUserId' => $this->_m_iUserId,
             'sessionId'=> (isset($p_aParameters['requestid']) ? $p_aParameters['requestid'] : $this->_getHash())
         ];
    
        if(!in_array($this->_m_sMethod, array('_balance', '_init'))) {
            $aAddParams['gameId'] = $this->_m_mGameId;
            $aAddParams['gameNo'] = (isset($p_aParameters['gameNo']) ? $p_aParameters['gameNo'] : $this->_m_oGp->randomNumber(10));
        }
        
        switch ($this->_m_sMethod) {
            
            case '_balance':
                break;
                
            case '_init':
                break; 
                
            case '_bet':
                $aAddParams['transactionId'] = (isset($p_aParameters['transactionId']) ? $p_aParameters['transactionId'] : $this->_getHash());
                $aAddParams['stake'] = $this->_m_oGp->convertFromToCoinage($p_aParameters['stake'],Gp::COINAGE_CENTS, Gp::COINAGE_UNITS);  
                $aAddParams['zeroStakeReason'] = $p_aParameters['zeroStakeReason'];
                $aAddParams['txId'] = $p_aParameters['txId'];
                break;
                
                
            case '_win':
                $aAddParams['transactionId'] = (isset($p_aParameters['transactionId']) ? $p_aParameters['transactionId'] : $this->_getHash());
                $aAddParams['win'] = $this->_m_oGp->convertFromToCoinage($p_aParameters['win'],Gp::COINAGE_CENTS, Gp::COINAGE_UNITS);
                $aAddParams['freeroundsId'] = $p_aParameters['freeroundsId'];
                break;
                         
            case '_cancel':
                $aAddParams['transactionId'] = (isset($p_aParameters['originalTransactionId']) ? $p_aParameters['originalTransactionId'] : $this->_getHash());
                $aAddParams['stake'] = $this->_m_oGp->convertFromToCoinage($p_aParameters['stake'],Gp::COINAGE_CENTS, Gp::COINAGE_UNITS);
                break;
                
            case '_frbStatus':
                $aAddParams['transactionId'] = (isset($p_aParameters['transactionId']) ? $p_aParameters['transactionId'] : $this->_getHash());
                $aAddParams['win'] = $this->_m_oGp->convertFromToCoinage($p_aParameters['win'],Gp::COINAGE_CENTS, Gp::COINAGE_UNITS);
                $aAddParams['txId'] = $p_aParameters['txId'];
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
            echo 'URL:' . PHP_EOL . $this->_m_sUrl . PHP_EOL . "DATA:" . PHP_EOL . $sValue . PHP_EOL . "RESULT:" . PHP_EOL;
        }

        return phive()->post($this->_m_sUrl, $sValue, 'application/json', '', $this->_m_oGp->getGpName() . '-out',
                'POST') . PHP_EOL;
        
    }
}
