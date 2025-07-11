<?php
require_once 'TestGp.php';

class TestSkywind extends TestGp
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
        
        $a = $aAddParams = $aParams = array();
        $sToken = $this->_m_oGp->getGuidv4($this->_m_iUserId);
        $this->_m_oGp->toSession($sToken, $this->_m_iUserId, $this->_m_mGameId);
        
        $a['cust_session_id'] = $sToken;
        $a['merch_id'] = $this->_m_oGp->getSetting('operator');
        $a['merch_pwd'] = $this->_m_oGp->getSetting('secretkey');
        if(!in_array($this->_m_sGpMethod, array('rollback'))) {
            $a['cust_id'] = $this->_m_iUserId;
        }
        $a['game_code'] = $this->_m_mGameId;
        
        if(in_array($this->_m_sGpMethod, array('rollback', 'credit', 'debit'))) {
            $a['game_id'] = (isset($p_aParameters['roundid']) ? $p_aParameters['roundid'] : $this->_m_oGp->randomNumber(10));
            $a['trx_id'] = (isset($p_aParameters['transactionid']) ? $p_aParameters['transactionid'] : $this->_getHash());
            
//            if(isset($p_aParameters['freespinid'])){
//                $a['params']['promoName'] = $p_aParameters['freespinid'];
//            }
            
            if($this->_m_sGpMethod !== 'rollback') {
                $a['amount'] = $this->_m_oGp->convertFromToCoinage($p_aParameters['amount'], Gp::COINAGE_CENTS, Gp::COINAGE_UNITS);
            }
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
//        $p_aData['game_type'] = 'bonusgame';
//        $p_aData['game_status'] = 'bonusgame';
//        $p_aData['trx_id'] = 'E92P4wnpb3QAAALDE92P4zQaX+w=';
//        $p_aData['event_type'] = $this->_m_sGpMethod;

        $sValue = http_build_query($p_aData);
        $this->_m_sUrl .= '?action=' . $this->_m_sGpMethod;
    
    
    
        if ($this->_m_bOutput === true) {
            echo 'URL:' . PHP_EOL . $this->_m_sUrl . PHP_EOL . "DATA:" . PHP_EOL . $sValue . PHP_EOL;
        }
        return phive()->post($this->_m_sUrl, $sValue, Gpinterface::HTTP_CONTENT_TYPE_APPLICATION_FORM_URLENCODED, '', $this->_m_oGp->getGpName() . '-out', 'POST') . PHP_EOL;
        
    }
}
