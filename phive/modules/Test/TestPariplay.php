<?php
require_once 'TestGp.php';

class TestPariplay extends TestGp
{
    
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
            'Token' => $sToken,
            'PlayerId' => $this->_m_iUserId,
            'PlatformType' => 1,
            'Account' => array('UserName' => $this->_m_oGp->getLicSetting('username'), 'Password' => $this->_m_oGp->getLicSetting('password'))
        );
    
        if(!in_array($this->_m_sMethod, array('_balance', '_init', '_createToken'))) {
            $aAddParams['GameCode'] = $this->_m_mGameId;
            $aAddParams['RoundId'] = (isset($p_aParameters['roundid']) ? $p_aParameters['roundid'] : $this->_m_oGp->randomNumber(10));
        }

        
        switch ($this->_m_sMethod) {
            case '_win':
                $aAddParams['CreditType'] = 'NormalWin'; // ProgressiveWin
                $aAddParams['EndGame'] = 'true';
                
            case '_bet':
            case '_win':
                $aAddParams['TransactionId'] = (isset($p_aParameters['transactionid']) ? $p_aParameters['transactionid'] : $this->_getHash());
                $aAddParams['Feature'] = (isset($p_aParameters['freespinid']) ? 'BonusWin' : 'Normal'); // or TournamentWin
                $aAddParams['FeatureId'] = (isset($p_aParameters['freespinid']) ? $p_aParameters['freespinid'] : ''); // bonus_entries:ext_id
                $aAddParams['Amount'] = $this->_m_oGp->convertFromToCoinage($p_aParameters['amount'],Gp::COINAGE_CENTS, Gp::COINAGE_UNITS);
                break;
                
            case '_cancel':
                $aAddParams['RefTransactionId'] = (isset($p_aParameters['transactionid']) ? $p_aParameters['transactionid'] : $this->_getHash());
                $aAddParams['Amount'] = $this->_m_oGp->convertFromToCoinage($p_aParameters['amount'],Gp::COINAGE_CENTS, Gp::COINAGE_UNITS);
                $aAddParams['CancelEntireRound'] = 'true';
                    break;
                
            case '_end':
                $aAddParams['roundCompleted'] = 'true';
                break;
                
            case '_createToken':
                $aAddParams['GameCode'] = $this->_m_mGameId;
                break;
                
            case '_createFreespin':
                $aAddParams['GameCode'] = $this->_m_mGameId;
                $aAddParams['BonusId'] = $this->_m_oGp->randomNumber(5);
                $aAddParams['NumberFreeRounds'] = isset($p_aParameters['NumberFreeRounds']) ? $p_aParameters['NumberFreeRounds'] : 5;
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
            echo 'URL:' . PHP_EOL . $this->_m_sUrl . PHP_EOL . "DATA:" . PHP_EOL . $sValue . PHP_EOL;
        }
        return phive()->post($this->_m_sUrl, $sValue, 'application/json', '', $this->_m_oGp->getGpName() . '-out',
                'POST') . PHP_EOL;
        
    }

    public function initScenarios()
    {
        // TODO: Implement initScenarios() method.
    }

    public function testConfirmedWins($test_case_type_param = null)
    {
        // TODO: Implement testConfirmedWins() method.
    }
}
