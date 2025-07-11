<?php
require_once 'TestGp.php';

class TestNektan extends TestGp
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
        $this->setUrl($this->_m_sUrl . '?action=' . $this->_m_sGpMethod);
        return $this->_post($aParameters);
    }
    
    /**
     * Get array with data for reconsile or bet and win in 1 request reconOrBetWin method is used to settle bet + win.re
     * which should then internally validate the transaction and return the updated state of the balance.
     *
     * @param array $p_aParameters Array with parameter. If empty default params will be used.
     *
     * @return array
     */
    private function _urlParams(array $p_aParameters = array())
    {
        
        $a = array();
        $sToken = $this->_m_oGp->getGuidv4();
        $this->_m_oGp->toSession($sToken, $this->_m_iUserId, $this->_m_mGameId);
        
        $a['method'] = $this->_m_sGpMethod;
        $aParams = array(
            'sessionToken' => $sToken,
            'username' => $this->_m_iUserId,
            'gameId' => $this->_m_mGameId
        );
        
        $a['params'] = $aParams;
        if ($this->_m_sMethod !== '_balance') {
            $aAddParams = array();
            $aAddParams['gameRoundId'] = (isset($p_aParameters['roundid']) ? $p_aParameters['roundid'] : random_int(10000000,
                99999999));
            $aAddParams['transactionTimeStamp'] = date('Y-m-d\TH:i:s\.\0\0\0O', time());
            switch ($this->_m_sGpMethod) {
                case 'closeGameRound':
                    $aAddParams['roundCompleted'] = 'true';
                    break;
                
                case 'debit':
                case 'credit':
                case 'cancelCredit':
                case 'cancelDebit':
                    $aAddParams['transactionId'] = (isset($p_aParameters['transactionid']) ? $p_aParameters['transactionid'] : $this->_getHash());
                    $aAddParams['amount'] = $this->_m_oGp->convertFromToCoinage($p_aParameters['amount'],
                        Gp::COINAGE_CENTS, Gp::COINAGE_UNITS);
                    if (!in_array($this->_m_sGpMethod, array('cancelCredit', 'cancelDebit'))) {
                        $aAddParams['type'] = 'game';
                        $aAddParams['roundCompleted'] = 'false';
                    }
                    break;
                
                case 'creditDebit':
                case 'cancelCreditDebit':
                    $aAddParams['debitTransactionId'] = (isset($p_aParameters['debitTransactionid']) ? $p_aParameters['debitTransactionid'] : $this->_getHash());
                    $aAddParams['creditTransactionId'] = (isset($p_aParameters['creditTransactionid']) ? $p_aParameters['creditTransactionid'] : $this->_getHash());
                    $aAddParams['debitAmount'] = $this->_m_oGp->convertFromToCoinage($p_aParameters['debitAmount'],
                        Gp::COINAGE_CENTS, Gp::COINAGE_UNITS);
                    $aAddParams['creditAmount'] = $this->_m_oGp->convertFromToCoinage($p_aParameters['creditAmount'],
                        Gp::COINAGE_CENTS, Gp::COINAGE_UNITS);
                    if (!$this->_m_sGpMethod === 'cancelCreditDebit') {
                        $aAddParams['type'] = 'game';
                        $aAddParams['roundCompleted'] = 'true';
                    }
                    break;
            }
            $a['params'] = array_merge($aParams, $aAddParams);
        }
        
        return $a;
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
            echo 'URL:' . PHP_EOL . $this->_getUrl() . PHP_EOL . "DATA:" . PHP_EOL . $sValue . PHP_EOL;
        }
        
        return phive()->post($this->_getUrl(), $sValue, 'application/json', '', $this->_m_oGp->getGpName() . '-out',
                'POST') . PHP_EOL;
        
    }
}
