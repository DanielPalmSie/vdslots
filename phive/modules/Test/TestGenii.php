<?php
require_once 'TestGp.php';

class TestGenii extends TestGp
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
        
        $iSessionID = $this->_m_oGp->getGuidv4();
        $this->_m_oGp->toSession($iSessionID, $this->_m_iUserId, $this->_m_mGameId);
        $aCallerAuth = $this->_m_oGp->getSetting('callerauth');
        
        $a = array(
            'request' => $this->_m_sGpMethod,
            'callerauth' => $aCallerAuth[array_rand($aCallerAuth, 1)],
            'callerpassword' => $this->_m_oGp->getSetting('callerpassword'),
            'sessionid' => $iSessionID,
        );
        
        switch ($this->_m_sGpMethod) {
            
            case 'getbalance':
                $a['gameid'] = $this->_m_mGameId;
                break;
            
            case 'wager':
            case 'result':
            case 'cancelwager':
                switch ($this->_m_sGpMethod) {
                    case 'wager':
                        $key = 'betamount';
                        break;
                    case 'result':
                        $key = 'result';
                        break;
                    case 'cancelwager':
                        $key = 'cancelwageramount';
                        if (!empty($p_aParameters['transactionid'])) {
                            $aTxn = explode('-', $p_aParameters['transactionid']);
                            $p_aParameters['transactionid'] = $aTxn[0];
                            $a['callerauth'] = $aTxn[1];
                        }
                        break;
                }
                
                $a[$key] = $this->_m_oGp->convertFromToCoinage($p_aParameters['amount'], Gp::COINAGE_CENTS,
                    Gp::COINAGE_UNITS);
                $a['gameid'] = $this->_m_mGameId;
                $a['accountid'] = $this->_m_iUserId;
                $a['roundid'] = '1234567890';
                $a['description'] = '';
                $a['transactionid'] = (isset($p_aParameters['transactionid']) ? $p_aParameters['transactionid'] : $this->_getHash());
                $a['gameclienttype'] = 'Flash';  // or Html
                $a['gamestatus'] = 'complete';  // or pending
                break;
        }
        
        return $a;
    }
    
    /**
     * Post the data in JSON format
     *
     * @param array $p_aData An array with data to post.
     *
     * @see outputRequest()
     * @return mixed Outputs the response from the url that is called by the post and optionally can output what is send to the url upfront.
     */
    protected function _post($aUrl)
    {
        
        $this->_m_sUrl = $this->_m_sUrl . '?' . http_build_query($aUrl);
        
        if ($this->_m_bOutput === true) {
            echo 'URL:' . PHP_EOL . $this->_m_sUrl . PHP_EOL;
        }
        
        return phive()->post($this->_m_sUrl, '', 'text/html', '', $this->_m_oGp->getGpName() . '-out', 'GET');
    }
}
