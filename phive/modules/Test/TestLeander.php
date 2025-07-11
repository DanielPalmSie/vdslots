<?php
require_once 'TestGp.php';

class TestLeander extends TestGp
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
        
        $sToken = $this->_m_oGp->getGuidv4();
        $this->_m_oGp->toSession($sToken, $this->_m_iUserId, $this->_m_mGameId);
        
        $a = array();
        $a['sessionId'] = uniqid();
        $a['gameId'] = $this->_m_mGameId;
        $a['userId'] = $this->_m_iUserId;
        $a['token'] = $sToken;
        $a['gameMode'] = 'REAL';
        $a['channel'] = 'desktop';
        if (isset($p_aParameters['amount'])) {
            $a['amount'] = $this->_m_oGp->convertFromToCoinage($p_aParameters['amount'], Gp::COINAGE_CENTS,
                Gp::COINAGE_UNITS);
        }
        
        if (in_array($this->_m_sMethod, array('_bet', '_win'))) {
            $a['playId'] = rand(8, 8);
            $a['operation'] = (($this->_m_sMethod == '_bet') ? 'DEBIT' : 'CREDIT');
            $a['transactionId'] = (isset($p_aParameters['transactionid']) ? $p_aParameters['transactionid'] : $this->_getHash());
        }
        
        if ($this->_m_sMethod === '_status') {
            $a['playStatus'] = $p_aParameters['play_status'];
            $a['promotionCode'] = $p_aParameters['bonus_entries_id'];
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
        
        $this->_m_sUrl = $this->_m_sUrl . '/' . $this->_m_sGpMethod . '?' . http_build_query($aUrl);
        
        if ($this->_m_bOutput === true) {
            echo 'URL:' . PHP_EOL . $this->_m_sUrl . PHP_EOL;
        }
        
        return phive()->post($this->_m_sUrl, '', 'text/html', '', $this->_m_oGp->getGpName() . '-out', 'GET');
    }
}
